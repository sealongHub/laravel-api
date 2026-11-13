<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Str;
use App\Models\User;

class UserController extends Controller
{
    // ── GET all users with profiles ────────────────────────────
    public function index(Request $request)
    {
        $query = User::with('profiles');

        // Search by name or email
        if ($request->text_search) {
            $query->where(function ($q) use ($request) {
                $q->where('name',  'like', '%' . $request->text_search . '%')
                  ->orWhere('email', 'like', '%' . $request->text_search . '%');
            });
        }

        // Filter by role/type (store type in profiles table)
        if ($request->type) {
            $query->whereHas('profiles', function ($q) use ($request) {
                $q->where('type', $request->type);
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(10);

        // Map profile.type to top-level `type` for convenience on frontend
        $items = $users->items();
        foreach ($items as $u) {
            $u->type = $u->profiles?->type ?? 'staff';
        }

        return response()->json([
            'users'      => $items,
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    // ── UPDATE user ────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'    => 'required|string',
            'email'   => 'required|email|unique:users,email,' . $id,
            'phone'   => 'required|string',
            'address' => 'required|string',
            'type'    => 'nullable|string|in:super_admin,manager,product_staff,sales_staff',
            'image'   => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
        ]);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        // ✅ Update profile (store type in profiles table)
        $imagePath = $user->profiles?->imagee;
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $imagePath = $request->file('image')->store('profiles', 'public');
        }

        $profileData = [
            'phone'   => $request->phone,
            'address' => $request->address,
            'imagee'  => $imagePath,
            'type'    => $request->type ?? ($user->profiles?->type ?? 'staff'),
        ];

        if ($user->profiles) {
            $user->profiles()->update($profileData);
        } else {
            $user->profiles()->create($profileData);
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user'    => $user->load('profiles'),
        ]);
    }

    // ── DELETE user ────────────────────────────────────────────
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->profiles()->delete();
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
