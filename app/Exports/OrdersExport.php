<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OrdersExport
{
    protected $orders;

    public function __construct($orders)
    {
        $this->orders = $orders;
    }

    public function download()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headings
        $sheet->fromArray(['ID', 'Buyer Name', 'Buyer Phone', 'Address', 'Payment Method', 'Total', 'Delivery Fee', 'Status', 'Created At'], NULL, 'A1');

        // Data
        $row = 2;
        foreach ($this->orders as $order) {
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

        $writer = new Xlsx($spreadsheet);

        $filename = 'order_report.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}