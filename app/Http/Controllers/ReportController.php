<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function orderReport(Request $request)
    {

        $query = DB::table('orders');

        // Date filter
        if($request->from && $request->to){
            $query->whereBetween('created_at',[$request->from,$request->to]);
        }

        // Status filter
        if($request->status){
            $query->where('status',$request->status);
        }

        $orders = $query->orderBy('id','desc')->get();

        // ===== BUSINESS LOGIC =====

        $totalOrders = $orders->count();
        $revenue = $orders->where('status','approved')->sum('total');
        $approved = $orders->where('status','approved')->count();
        $cancelled = $orders->where('status','cancelled')->count();
        $pending = $orders->where('status','pending')->count();

        // Weekly sales chart
        $weeklySales = DB::table('orders')
        ->selectRaw('DATE(created_at) as date, SUM(total) as total')
        ->where('status','approved')
        ->groupBy('date')
        ->orderBy('date')
        ->limit(7)
        ->get();

        // Status chart
        $statusChart = [
            ["type"=>"Approved","value"=>$approved],
            ["type"=>"Pending","value"=>$pending],
            ["type"=>"Cancelled","value"=>$cancelled],
        ];

        return response()->json([
            "list"=>$orders,
            "summary"=>[
                "totalOrders"=>$totalOrders,
                "revenue"=>$revenue,
                "approved"=>$approved,
                "cancelled"=>$cancelled,
                "pending"=>$pending
            ],
            "weeklySales"=>$weeklySales,
            "statusChart"=>$statusChart
        ]);
    }

    public function exportPdf(Request $request)
    {
        $orders = DB::table('orders')
            ->orderBy('id', 'desc')
            ->get();

        $totalRevenue = $orders->where('status', 'approved')->sum('total');
        $totalOrders  = $orders->count();

        $data = [
            "orders"       => $orders,
            "totalRevenue" => $totalRevenue,
            "totalOrders"  => $totalOrders,
        ];

        $pdf = Pdf::loadView('report.orders_pdf', $data)
            ->setPaper('a4', 'landscape');  // <-- landscape A4

        return $pdf->download('order_report.pdf');
    }

    public function exportExcel(Request $request) 
    {
        $query = DB::table('orders');

        if($request->from && $request->to) 
            $query->whereBetween('created_at', [$request->from, $request->to]);
        if($request->status) 
            $query->where('status', $request->status);

        $orders = $query->orderBy('id', 'desc')->get();

        // Build Excel file
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headings
        $sheet->fromArray(['ID', 'Buyer Name', 'Buyer Phone', 'Address', 'Payment Method', 'Total', 'Delivery Fee', 'Status', 'Created At'], NULL, 'A1');

        // Data rows
        $row = 2;
        foreach ($orders as $order) {
            $sheet->fromArray([
                $order->id,
                $order->buyer_name,
                $order->buyer_phone,
                $order->address,
                $order->payment_method,
                $order->total,
                $order->delivery_fee,
                $order->status,
                $order->created_at,
            ], NULL, 'A' . $row);
            $row++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'order_report');
        $writer->save($tempFile);

        return response()->download($tempFile, 'order_report.xlsx')->deleteFileAfterSend(true);
    }
}