<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    /**
     * ✅ ១. ទាញយកបញ្ជី Order ទាំងអស់មកបង្ហាញក្នុង View (React)
     * ប្រើសម្រាប់បង្ហាញក្នុង Table នៅលើ Admin Dashboard
     */
    public function index()
    {
        // ទាញយក Order ទាំងអស់ដោយតម្រៀបពីថ្មីទៅចាស់
        $orders = Order::orderBy('created_at', 'desc')->get();
        return response()->json([
            'success' => true,
            'list' => $orders
        ]);
    }

    /**
     * ✅ ២. ការរក្សាទុកការបញ្ជាទិញថ្មី (User Purchase)
     * ដំណើរការ៖ បង្កើត Order -> បង្កើត Details -> ដកស្តុកផលិតផលភ្លាមៗ
     */
    public function store(Request $request)
    {
        $request->validate([
            'buyer_name' => 'required|string|max:255',
            'buyer_phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'province_id' => 'required|integer',
            'note' => 'nullable|string',
            'payment_method' => 'required|string',
            'total' => 'required|numeric',
            'items' => 'required|string', // ទទួលជា JSON string ពី FormData
            'transaction_image' => 'nullable|image|max:5120',
        ]);

        // បំប្លែង items ពី JSON string ទៅជា Array
        $items = json_decode($request->items, true);

        if (!$items || !is_array($items)) {
            return response()->json(['success' => false, 'message' => 'Invalid items data.'], 400);
        }

        // Handle uploaded transaction image: store under storage/app/public/transactions
        $transactionFileName = null;
        if ($request->hasFile('transaction_image')) {
            $file = $request->file('transaction_image');
            $transactionFileName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
            $file->storeAs('public/transactions', $transactionFileName);
        }

        DB::beginTransaction();
        try {
            // កត់ត្រាចូលតារាង orders
            $order = Order::create([
                'buyer_name' => $request->buyer_name,
                'buyer_phone' => $request->buyer_phone,
                'address' => $request->address,
                'province_id' => $request->province_id,
                'note' => $request->note,
                'payment_method' => $request->payment_method,
                'total' => $request->total,
                'delivery_fee' => $request->delivery_fee ?? 0,
                'transaction_image' => $transactionFileName,
                'status' => 'pending' // កំណត់ជា Pending ជាមុនដើម្បីរង់ចាំ Admin ឆែកស្លីបលុយ
            ]);

            foreach ($items as $item) {
                // frontend sends item keys as { id, qty }
                $productId = $item['id'] ?? $item['product_id'] ?? null;
                $quantity = $item['qty'] ?? $item['quantity'] ?? 0;

                $product = Product::findOrFail($productId);

                // ត្រួតពិនិត្យស្តុកមុននឹងលក់
                if ($quantity > $product->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "ផលិតផល {$product->name} មិនគ្រប់គ្រាន់ក្នុងស្តុកឡើយ (នៅសល់ត្រឹម {$product->quantity})"
                    ], 400);
                }

                // កត់ត្រាចូលតារាង order_details
                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'price' => $product->price,
                    'quantity' => $quantity,
                    'subtotal' => $product->price * $quantity,
                ]);

                // ✅ ឈានដល់ការដកស្តុកទុកមុន (Reserve Stock)
                $product->decrement('quantity', $quantity);

                // កត់ត្រាទៅក្នុង stock_transactions សម្រាប់ audit (out)
                DB::table('stock_transactions')->insert([
                    'product_id' => $product->id,
                    'quantity'   => $quantity,
                    'type'       => 'out',
                    'reason'     => "Reserved by Order #{$order->id}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'message' => 'ការបញ្ជាទិញត្រូវបានបង្កើតដោយជោគជ័យ!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'បរាជ័យក្នុងការបង្កើត Order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ ៣. មុខងារសម្រាប់ប្តូរ Status (Pending -> Approved / Cancelled)
     * ប្រើនៅពេល Admin ចុចប៊ូតុងក្នុង Dashboard
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:approved,cancelled']);
        
        DB::beginTransaction();
        try {
            $order = Order::findOrFail($id);

            // ករណីទី១៖ បើ Admin ចុច Cancel (ប៊ូតុងក្រហម)
            // យើងត្រូវយកទំនិញដែលបានដកពីមុនមក "បូកបញ្ចូលក្នុងស្តុកវិញ"
            $oldStatus = $order->status;
            // Only restore stock if cancelling from a pre-payment state OR when an explicit admin override is provided
            $allowedRestoreFrom = ['pending', 'awaiting_payment', 'payment_rejected'];
            $forceRestore = (bool) ($request->force_restore ?? false);

            $canForce = false;
            try {
                $user = auth()->user();
                if ($user && isset($user->profile) && ($user->profile->type === 'super_admin' || $user->profile->type === 'manager')) {
                    $canForce = true;
                }
            } catch (\Exception $e) {
                $canForce = false;
            }

            if ($request->status == 'cancelled' && $oldStatus !== 'cancelled') {
                $shouldRestore = in_array($oldStatus, $allowedRestoreFrom, true) || ($forceRestore && $canForce);

                if ($shouldRestore) {
                    $details = DB::table('order_details')->where('order_id', $id)->get();
                    foreach ($details as $item) {
                        // Increment product quantity back
                        Product::where('id', $item->product_id)->increment('quantity', $item->quantity);

                        // Log stock transaction (in)
                        DB::table('stock_transactions')->insert([
                            'product_id' => $item->product_id,
                            'quantity'   => $item->quantity,
                            'type'       => 'in',
                            'reason'     => "Restored due to cancellation of Order #{$id}",
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                } else {
                    // Do not restore stock automatically; record an audit row explaining why (optional)
                    DB::table('stock_transactions')->insert([
                        'product_id' => 0,
                        'quantity'   => 0,
                        'type'       => 'note',
                        'reason'     => "Order #{$id} cancelled but stock NOT restored (previous status: {$oldStatus}).",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Update order status
            $order->update(['status' => $request->status]);
            
            DB::commit();
            return response()->json([
                'success' => true, 
                'message' => "ស្ថានភាព Order ត្រូវបានប្តូរទៅជា " . strtoupper($request->status)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false, 
                'message' => 'ការប្តូរស្ថានភាពបរាជ័យ: ' . $e->getMessage()
            ], 500);
        }
    }
}