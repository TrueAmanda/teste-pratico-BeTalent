<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->canManageUsers()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $users = User::select('id', 'name', 'email', 'role', 'created_at', 'updated_at')
            ->orderBy('name')
            ->paginate(20);

        return response()->json($users);
    }

    public function show(User $user, Request $request)
    {
        if (!$request->user()->canManageUsers()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }

    public function store(Request $request)
    {
        if (!$request->user()->canManageUsers()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:ADMIN,MANAGER,FINANCE,USER',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ], 201);
    }

    public function update(User $user, Request $request)
    {
        if (!$request->user()->canManageUsers()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:ADMIN,MANAGER,FINANCE,USER',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = [];
        
        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }
        
        if ($request->has('email')) {
            $updateData['email'] = $request->email;
        }
        
        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }
        
        if ($request->has('role')) {
            $updateData['role'] = $request->role;
        }

        $user->update($updateData);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }

    public function destroy(User $user, Request $request)
    {
        if (!$request->user()->canManageUsers()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Cannot delete your own account'], 400);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
