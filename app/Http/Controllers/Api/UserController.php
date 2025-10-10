<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Only admin can list all users
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        $query = User::query();

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->with(['rombonganBelajar'])->orderBy('created_at', 'desc')->paginate(1000);

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Only admin can create users
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,user',
            'gender' => 'nullable|in:male,female',
            'rombongan_belajar_id' => 'nullable|exists:rombongan_belajar,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'gender' => $request->gender,
            'rombongan_belajar_id' => $request->rombongan_belajar_id,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'data' => $user,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        // Only admin can view other users
        if (!$request->user()->isAdmin() && $request->user()->id != $id) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        $user = User::with(['prayerRecords' => function($query) {
            $query->orderBy('date', 'desc')->limit(10);
        }, 'rombonganBelajar'])->findOrFail($id);

        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        // Only admin or the user themselves can update
        if (!$request->user()->isAdmin() && $request->user()->id != $id) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'gender' => 'nullable|in:male,female',
        ];

        // Only admin can change role and rombongan belajar
        if ($request->user()->isAdmin()) {
            $rules['role'] = 'sometimes|required|in:admin,user';
            $rules['rombongan_belajar_id'] = 'nullable|exists:rombongan_belajar,id';
        }

        // If password is being updated
        if ($request->has('password')) {
            $rules['password'] = 'required|string|min:8';
        }

        $request->validate($rules);

        $data = $request->only(['name', 'email', 'gender']);

        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->has('role') && $request->user()->isAdmin()) {
            $data['role'] = $request->role;
        }

        if ($request->has('rombongan_belajar_id') && $request->user()->isAdmin()) {
            $data['rombongan_belajar_id'] = $request->rombongan_belajar_id;
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully',
            'data' => $user,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        // Only admin can delete users
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Prevent deleting yourself
        if ($request->user()->id == $id) {
            return response()->json([
                'message' => 'Cannot delete your own account',
            ], 400);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
