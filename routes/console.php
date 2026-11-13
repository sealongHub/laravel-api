<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Release pending orders older than X hours and restore reserved stock
Artisan::command('orders:release-pending {hours=24}', function ($hours) {
    $hours = (int) $hours;
    $cutoff = \Carbon\Carbon::now()->subHours($hours);
    $this->info("Looking for pending orders created before {$cutoff}...");

    $orders = App\Models\Order::where('status', 'pending')
        ->where('created_at', '<=', $cutoff)
        ->get();

    $this->info('Found ' . $orders->count() . ' orders to release.');

    foreach ($orders as $order) {
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $details = \Illuminate\Support\Facades\DB::table('order_details')->where('order_id', $order->id)->get();
            foreach ($details as $d) {
                $product = App\Models\Product::find($d->product_id);
                if ($product) {
                    $product->increment('quantity', $d->quantity);

                    \Illuminate\Support\Facades\DB::table('stock_transactions')->insert([
                        'product_id' => $product->id,
                        'quantity'   => $d->quantity,
                        'type'       => 'in',
                        'reason'     => "Released from expired Order #{$order->id}",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $order->update(['status' => 'cancelled']);

            \Illuminate\Support\Facades\DB::commit();
            $this->info("Order #{$order->id} cancelled and stock restored.");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->error("Failed to release Order #{$order->id}: " . $e->getMessage());
        }
    }

    $this->info('Done.');
})->describe('Cancel pending orders older than X hours and restore reserved stock');
