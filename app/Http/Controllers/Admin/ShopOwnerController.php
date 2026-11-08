<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetShopOwnerPasswordRequest;
use App\Http\Requests\Admin\StoreShopOwnerRequest;
use App\Http\Requests\Admin\UpdateShopOwnerRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopOwnerController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : '',
        ];

        $owners = User::shopOwners()
            ->when($filters['search'] !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('name', 'like', "%{$filters['search']}%")
                ->orWhere('email', 'like', "%{$filters['search']}%")))
            ->when($filters['status'] !== '', fn (Builder $q) => $q->where('is_active', $filters['status'] === 'active'))
            ->withCount(['shops', 'createdProducts'])
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'shops_count' => $user->shops_count,
                'created_products_count' => $user->created_products_count,
                'created_at' => $user->created_at->toDateString(),
            ]);

        return Inertia::render('Admin/ShopOwners/Index', [
            'owners' => $owners,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Admin/ShopOwners/Create');
    }

    public function store(StoreShopOwnerRequest $request): RedirectResponse
    {
        $owner = new User($request->validated());
        $owner->forceFill([
            'role' => Role::ShopOwner, // never taken from the request
            'email_verified_at' => now(),
        ])->save();

        return redirect()->route('admin.shop-owners.index')
            ->with('success', "Shop owner \"{$owner->name}\" created.");
    }

    public function edit(User $shopOwner): Response
    {
        $this->authorize('update', $shopOwner);

        $shopOwner->load(['shops' => fn ($q) => $q->orderBy('name')->select(['id', 'owner_id', 'name', 'is_active'])]);

        return Inertia::render('Admin/ShopOwners/Edit', [
            'owner' => [
                'id' => $shopOwner->id,
                'name' => $shopOwner->name,
                'email' => $shopOwner->email,
                'is_active' => $shopOwner->is_active,
                'shops' => $shopOwner->shops->map->only(['id', 'name', 'is_active']),
            ],
        ]);
    }

    public function update(UpdateShopOwnerRequest $request, User $shopOwner): RedirectResponse
    {
        $shopOwner->update($request->validated());

        return redirect()->route('admin.shop-owners.index')
            ->with('success', "Shop owner \"{$shopOwner->name}\" updated.");
    }

    public function toggleActive(User $shopOwner): RedirectResponse
    {
        $this->authorize('update', $shopOwner);

        $shopOwner->update(['is_active' => ! $shopOwner->is_active]);

        $state = $shopOwner->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Shop owner \"{$shopOwner->name}\" {$state}.");
    }

    public function resetPassword(ResetShopOwnerPasswordRequest $request, User $shopOwner): RedirectResponse
    {
        $shopOwner->update(['password' => $request->validated('password')]);

        return back()->with('success', "Password for \"{$shopOwner->name}\" has been reset.");
    }

    public function destroy(User $shopOwner): RedirectResponse
    {
        $this->authorize('delete', $shopOwner);

        $shopCount = $shopOwner->shops()->count();

        if ($shopCount > 0) {
            return back()->with('error', "\"{$shopOwner->name}\" still owns {$shopCount} shop(s). Reassign or delete those shops first.");
        }

        // Catalog products they created stay in the catalog (created_by is set to NULL).
        $shopOwner->delete();

        return redirect()->route('admin.shop-owners.index')
            ->with('success', "Shop owner \"{$shopOwner->name}\" deleted.");
    }
}
