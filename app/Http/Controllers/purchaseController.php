<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class purchaseController extends Controller
{
    public function store(Request $request)
    {
        $request->merge([
            'payment_method' => strtolower((string) $request->input('payment_method')),
        ]);

        $validated = $request->validate([
            'buyer_name' => 'required|string|max:255',
            'buyer_phone' => 'required|string|max:50',
            'address' => 'required|string|max:255',
            'province_id' => 'required|integer|exists:provinces,id',
            'payment_method' => 'required|in:aba,acleda',
            'total' => 'required|numeric|min:0',
            'items' => 'required',
            'transaction_image' => 'nullable|image|mimes:jpeg,jpg,png,gif,bmp,webp|max:5120',
            'note' => 'nullable|string',
            'delivery_fee' => 'nullable|numeric|min:0',
        ]);

        $items = $this->normalizeItems($validated['items']);
        $imagePath = null;

        if ($request->hasFile('transaction_image')) {
            try {
                $imagePath = uploadImage('transactions', $request->file('transaction_image'));
            } catch (Throwable $e) {
                Log::error('Purchase transaction image upload failed.', [
                    'exception' => $e,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Unable to upload the payment image. Please try again.',
                ], 502);
            }
        }

        try {
            $purchase = DB::transaction(function () use ($validated, $items, $imagePath) {
                $products = Product::query()
                    ->whereIn('id', array_keys($items))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $subtotal = 0;
                foreach ($items as $productId => $quantity) {
                    $product = $products->get($productId);

                    if (! $product) {
                        throw ValidationException::withMessages([
                            'items' => ["Product {$productId} no longer exists."],
                        ]);
                    }

                    if ($product->quantity < $quantity) {
                        throw ValidationException::withMessages([
                            'items' => ["There is not enough stock for {$product->name}."],
                        ]);
                    }

                    $subtotal += (float) $product->price * $quantity;
                }

                $deliveryFee = (float) ($validated['delivery_fee'] ?? 0);
                $orderTotal = round($subtotal + $deliveryFee, 2);
                $now = now();

                $orderId = DB::table('orders')->insertGetId([
                    'buyer_name' => $validated['buyer_name'],
                    'buyer_phone' => $validated['buyer_phone'],
                    'address' => $validated['address'],
                    'province_id' => $validated['province_id'],
                    'note' => $validated['note'] ?? null,
                    'payment_method' => $validated['payment_method'],
                    'total' => $orderTotal,
                    'delivery_fee' => $deliveryFee,
                    'transaction_image' => $imagePath,
                    'status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $itemsText = '';
                foreach ($items as $productId => $quantity) {
                    $product = $products->get($productId);
                    $subtotal = round((float) $product->price * $quantity, 2);

                    DB::table('order_details')->insert([
                        'order_id' => $orderId,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price' => $product->price,
                        'subtotal' => $subtotal,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $product->decrement('quantity', $quantity);

                    DB::table('stock_transactions')->insert([
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'cost_price' => $product->price,
                        'type' => 'out',
                        'reason' => 'Purchase: Order #'.$orderId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $itemsText .= "• {$product->name} ({$quantity}x) = $"
                        .number_format($subtotal, 2)."\n";
                }

                return [
                    'order_id' => $orderId,
                    'items_text' => $itemsText,
                ];
            });
        } catch (ValidationException $e) {
            $this->deleteUploadedImage($imagePath);
            throw $e;
        } catch (Throwable $e) {
            $this->deleteUploadedImage($imagePath);
            Log::error('Purchase order transaction failed.', [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to submit your order right now. Please try again.',
            ], 500);
        }

        $order = DB::table('orders')->where('id', $purchase['order_id'])->first();
        $details = DB::table('order_details')->where('order_id', $purchase['order_id'])->get();
        $receiptUrl = $this->createReceipt($order, $details, $purchase['order_id']);

        $this->notifyTelegram($order, $purchase['items_text'], $receiptUrl);

        return response()->json([
            'success' => true,
            'order_id' => $purchase['order_id'],
            'receipt_url' => $receiptUrl,
        ]);
    }

    private function normalizeItems(mixed $value): array
    {
        if (is_string($value)) {
            try {
                $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw ValidationException::withMessages([
                    'items' => ['Items must be a valid JSON array.'],
                ]);
            }
        }

        if (! is_array($value) || ! array_is_list($value) || $value === []) {
            throw ValidationException::withMessages([
                'items' => ['At least one valid cart item is required.'],
            ]);
        }

        $items = [];
        foreach ($value as $index => $item) {
            if (! is_array($item)) {
                throw ValidationException::withMessages([
                    'items.'.$index => ['Each item must contain a product id and quantity.'],
                ]);
            }

            $productId = filter_var($item['id'] ?? $item['product_id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($item['qty'] ?? $item['quantity'] ?? null, FILTER_VALIDATE_INT);

            if (! $productId || $productId < 1 || ! $quantity || $quantity < 1) {
                throw ValidationException::withMessages([
                    'items.'.$index => ['Each item must have a positive product id and quantity.'],
                ]);
            }

            $items[$productId] = ($items[$productId] ?? 0) + $quantity;
        }

        return $items;
    }

    private function deleteUploadedImage(?string $imagePath): void
    {
        if (! $imagePath) {
            return;
        }

        try {
            deleteImage($imagePath);
        } catch (Throwable $e) {
            Log::warning('Could not remove an unused purchase image.', [
                'exception' => $e,
            ]);
        }
    }

    private function createReceipt(object $order, $details, int $orderId): ?string
    {
        try {
            $pdf = Pdf::loadView('receipt', compact('order', 'details'));
            $path = 'receipts/receipt_'.$orderId.'.pdf';

            if (! Storage::disk('public')->put($path, $pdf->output())) {
                Log::warning('Purchase receipt could not be saved.', [
                    'order_id' => $orderId,
                ]);

                return null;
            }

            return asset('storage/'.$path);
        } catch (Throwable $e) {
            Log::error('Purchase receipt generation failed.', [
                'order_id' => $orderId,
                'exception' => $e,
            ]);

            return null;
        }
    }

    private function notifyTelegram(object $order, string $itemsText, ?string $receiptUrl): void
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (! $botToken || ! $chatId) {
            Log::warning('Purchase Telegram notification is not configured.', [
                'order_id' => $order->id,
            ]);

            return;
        }

        try {
            $province = DB::table('provinces')->where('id', $order->province_id)->value('name') ?? 'Unknown';
            $caption = "📦 *New Purchase Received!*\n\n"
                ."👤 *Buyer:* {$order->buyer_name}\n"
                ."📞 *Phone:* {$order->buyer_phone}\n"
                ."🏠 *Address:* {$order->address}\n"
                ."📍 *Province:* {$province}\n"
                .'💳 *Payment:* '.strtoupper($order->payment_method)."\n"
                .'💰 *Total:* $'.number_format($order->total, 2)."\n"
                .($receiptUrl ? "🧾 [View Receipt]({$receiptUrl})\n\n" : '')
                ."🛒 *Items:*\n".($itemsText ?: 'No items listed.');

            if ($order->transaction_image) {
                $response = Http::post("https://api.telegram.org/bot{$botToken}/sendPhoto", [
                    'chat_id' => $chatId,
                    'photo' => getImage($order->transaction_image),
                    'caption' => $caption,
                    'parse_mode' => 'Markdown',
                ]);
            } else {
                $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $caption,
                    'parse_mode' => 'Markdown',
                    'disable_web_page_preview' => false,
                ]);
            }

            if (! $response->successful()) {
                Log::warning('Purchase Telegram notification failed.', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Purchase Telegram notification failed.', [
                'order_id' => $order->id,
                'exception' => $e,
            ]);
        }
    }
}
