<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        // Get authenticated user
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Validate request data
        $request->validate([
            'product_name' => 'required|string',
            'price' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'image' => 'nullable|string', // image URL for Telegram
        ]);

        $productName = $request->product_name;
        $price = $request->price;
        $discount = $request->discount ?? 0;
        $imageUrl = $request->image ?? null;

        // Telegram configuration
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');

        if (!$botToken || !$chatId) {
            return response()->json(['error' => 'Telegram bot not configured'], 500);
        }

        // Prepare Telegram message/caption
        $caption = "🛒 *New Add to Cart!*\n"
                 . "👤 *User:* {$user->name}\n"
                 . "📧 *Email:* {$user->email}\n"
                 . "📦 *Product:* {$productName}\n"
                 . "💰 *Price:* {$price}$\n"
                 . "🔖 *Discount:* {$discount}%";

        // Send either photo with caption or simple text message
        if ($imageUrl) {
            Http::post("https://api.telegram.org/bot{$botToken}/sendPhoto", [
                'chat_id' => $chatId,
                'photo' => $imageUrl, // must be a full URL
                'caption' => $caption,
                'parse_mode' => 'Markdown',
            ]);
        } else {
            Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $caption,
                'parse_mode' => 'Markdown',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Added to cart and Telegram notified.'
        ]);
    }
}
