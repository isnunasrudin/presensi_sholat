<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'in:admin,user'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $request->validate([
            'credential' => 'required',
            'password' => 'required',
        ]);

        // Check if credential is email or phone
        $user = null;
        if (filter_var($request->credential, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $request->credential)->first();
        } else {
            $user = User::where('phone', $request->credential)->first();
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'credential' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $token = $request->user()->currentAccessToken();
        
        return response()->json([
            'user' => $user,
            'is_impersonating' => $token->name === 'impersonate_token',
            'original_admin_id' => $token->name === 'impersonate_token' ? json_decode($token->abilities[0] ?? '{}')->original_admin_id ?? null : null,
        ]);
    }

    /**
     * Impersonate a user (Admin only)
     */
    public function impersonate(Request $request, $userId)
    {
        // Check if user is admin
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized. Only admin can impersonate users.',
            ], 403);
        }

        // Find the target user
        $targetUser = User::findOrFail($userId);

        // Cannot impersonate another admin
        if ($targetUser->isAdmin()) {
            return response()->json([
                'message' => 'Cannot impersonate another admin.',
            ], 403);
        }

        // Store original admin ID in token metadata
        $originalAdminId = $request->user()->id;
        
        // Create a special token for impersonation
        $token = $targetUser->createToken('impersonate_token', [
            json_encode(['original_admin_id' => $originalAdminId])
        ])->plainTextToken;

        return response()->json([
            'message' => 'Impersonation started',
            'user' => $targetUser,
            'token' => $token,
            'is_impersonating' => true,
            'original_admin_id' => $originalAdminId,
        ]);
    }

    /**
     * Stop impersonating and return to original admin
     */
    public function stopImpersonating(Request $request)
    {
        $currentToken = $request->user()->currentAccessToken();
        
        // Check if currently impersonating
        if ($currentToken->name !== 'impersonate_token') {
            return response()->json([
                'message' => 'Not currently impersonating.',
            ], 400);
        }

        // Get original admin ID
        $abilities = $currentToken->abilities[0] ?? '{}';
        $metadata = json_decode($abilities, true);
        $originalAdminId = $metadata['original_admin_id'] ?? null;

        if (!$originalAdminId) {
            return response()->json([
                'message' => 'Original admin ID not found.',
            ], 400);
        }

        // Delete impersonation token
        $currentToken->delete();

        // Get original admin
        $originalAdmin = User::findOrFail($originalAdminId);

        // Create new token for original admin
        $token = $originalAdmin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Impersonation stopped',
            'user' => $originalAdmin,
            'token' => $token,
            'is_impersonating' => false,
        ]);
    }
}
