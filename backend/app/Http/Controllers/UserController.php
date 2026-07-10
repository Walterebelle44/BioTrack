<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(User::select('id','name','email','role','service','phone','is_active','created_at')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $v = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role' => 'required|in:admin,directeur,responsable_si,technicien',
            'service' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);
        $v['password'] = Hash::make($v['password']);
        $user = User::create($v);
        return response()->json($user, 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $v = $request->validate([
            'name' => 'sometimes|string|max:255',
            'role' => 'sometimes|in:admin,directeur,responsable_si,technicien',
            'service' => 'nullable|string',
            'phone' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);
        $user->update($v);
        return response()->json($user);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->update(['is_active' => false]);
        return response()->json(['message' => 'Utilisateur désactivé.']);
    }
}
