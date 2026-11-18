<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use PragmaRX\Google2FA\Google2FA;
use App\Models\User;

class AuthController extends Controller
{
    // ── Register ──────────────────────────────────────────────
    public function register(Request $rq)
    {
        $rq->validate([
            "name"      => "required|string",
            "email"     => "required|email|unique:users,email",
            "password"  => "required|string|min:6|confirmed",
            "phone"     => "required|string",
            "address"   => "required|string",
            "type"      => "nullable|string|in:super_admin,manager,product_staff,sales_staff",
            "image"     => "nullable|image|mimes:jpeg,jpg,png,gif,bmp,webp|max:2048",
        ]);

        // ✅ Create user
        $user = User::create([
            "name"     => $rq->name,
            "email"    => $rq->email,
            "password" => Hash::make($rq->password),
        ]);

        // ✅ Handle image upload
        $imagePath = null;
        if ($rq->hasFile('image') && $rq->file('image')->isValid()) {
            $imagePath = uploadImage('profiles', $rq->file('image'));
        }

        // ✅ Save profile
        $user->profiles()->create([
            "phone"   => $rq->phone,
            "address" => $rq->address,
            "imagee"  => $imagePath,
            "type"    => $rq->type ?? 'staff',
        ]);

        // ✅ Generate 2FA secret
        $google2fa = new Google2FA();
        $secret    = $google2fa->generateSecretKey();

        // ✅ Fix — ប្រើ DB::table update ដើម្បី bypass fillable
        \DB::table('users')->where('id', $user->id)->update([
            'google2fa_secret'   => $secret,
            'two_factor_enabled' => true,
        ]);

        // ✅ Fix — config('app.name') មិនមែន config('Sheerlong_Shop')
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return response()->json([
            "message"     => "User Registered Successfully",
            "user"        => $user->load('profiles'),
            "qr_code_url" => $qrCodeUrl,
            "secret"      => $secret,
        ], 201);
    }

    // ── Login Step 1: verify email + password ─────────────────
    public function login(Request $rq)
    {
        $rq->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!JWTAuth::attempt($rq->only('email', 'password'))) {
            return response()->json([
                "errors" => ["message" => ["Invalid email or password"]]
            ], 401);
        }

        $user = JWTAuth::user()->load('profiles');

        // ✅ Check 2FA
        if ($user->two_factor_enabled && $user->google2fa_secret) {

            // ✅ Fix — ប្រើ Str::random() មិនមែន str_random()
            $tempToken = base64_encode($user->id . '|' . time() . '|' . Str::random(16));
            Cache::put('2fa_pending_' . $tempToken, $user->id, now()->addMinutes(5));

            return response()->json([
                "requires_2fa" => true,
                "temp_token"   => $tempToken,
                "message"      => "Please enter your Google Authenticator code",
            ]);
        }

        // No 2FA — return token directly
        $token = JWTAuth::fromUser($user);

        return response()->json([
            "message"      => "Login Successful",
            "access_token" => $token,
            "users"        => $user,
            "image"        => getImage($user->profiles?->imagee),
        ]);
    }

    // ── Login Step 2: verify OTP code ─────────────────────────
    public function verifyTwoFactor(Request $rq)
    {
        $rq->validate([
            'temp_token' => 'required|string',
            'otp_code'   => 'required|string|size:6',
        ]);

        $userId = Cache::get('2fa_pending_' . $rq->temp_token);

        if (!$userId) {
            return response()->json([
                "errors" => ["message" => ["Session expired. Please login again."]]
            ], 401);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                "errors" => ["message" => ["User not found."]]
            ], 404);
        }

        // ✅ Verify OTP
        $google2fa = new Google2FA();
        $valid     = $google2fa->verifyKey($user->google2fa_secret, $rq->otp_code);

        if (!$valid) {
            return response()->json([
                "errors" => ["otp_code" => ["Invalid code. Please try again."]]
            ], 422);
        }

        // ✅ OTP correct — clear cache + return token
        Cache::forget('2fa_pending_' . $rq->temp_token);

        $user->load('profiles');
        $token = JWTAuth::fromUser($user);

        return response()->json([
            "message"      => "Login Successful",
            "access_token" => $token,
            "users"        => $user,
            "image"        => getImage($user->profiles?->imagee),
        ]);
    }
}
