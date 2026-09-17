<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Admin User Management.
 *
 * Allows /manage portal administrators to list, create, view and update customer accounts.
 */
class AdminUserController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->withCount(['vehicles', 'devices'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('created_at');

        $users = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'status' => ['nullable', Rule::in(['active', 'suspended', 'pending'])],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'status' => $data['status'] ?? 'active',
            'email_verified_at' => now(),
        ]);

        $this->audit->log('admin.users.created', $user, $request->user());

        return response()->json([
            'message' => 'User account created successfully.',
            'user' => $user->loadCount(['vehicles', 'devices']),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = User::query()
            ->with(['vehicles.devices', 'devices.vehicle'])
            ->withCount(['vehicles', 'devices'])
            ->where('uuid', $id)
            ->orWhere('id', $id)
            ->firstOrFail();

        return response()->json([
            'user' => $user,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::query()
            ->where('uuid', $id)
            ->orWhere('id', $id)
            ->firstOrFail();

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32', Rule::unique('users', 'phone')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'status' => ['nullable', Rule::in(['active', 'suspended', 'pending'])],
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update(array_filter($data));

        $this->audit->log('admin.users.updated', $user, $request->user());

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user->fresh()->loadCount(['vehicles', 'devices']),
        ]);
    }
}
