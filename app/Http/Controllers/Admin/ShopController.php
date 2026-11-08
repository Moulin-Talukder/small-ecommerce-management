<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreShopRequest;
use App\Http\Requests\Admin\UpdateShopRequest;
use App\Models\Shop;
use App\Models\User;
use App\Support\ImageUploader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    private const LOGO_DIRECTORY = 'shops';

    public function __construct(private readonly ImageUploader $images) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Shop::class);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : '',
            'owner' => $request->integer('owner') ?: '',
        ];

        $shops = Shop::query()
            ->with('owner:id,name,email,is_active')
            ->withCount('shopProducts')
            ->when($filters['search'] !== '', fn (Builder $q) => $q->where('name', 'like', "%{$filters['search']}%"))
            ->when($filters['status'] !== '', fn (Builder $q) => $q->where('is_active', $filters['status'] === 'active'))
            ->when($filters['owner'] !== '', fn (Builder $q) => $q->where('owner_id', $filters['owner']))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Shops/Index', [
            'shops' => $shops,
            'filters' => $filters,
            'owners' => $this->ownerOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Shop::class);

        return Inertia::render('Admin/Shops/Create', [
            'owners' => $this->ownerOptions(),
        ]);
    }

    public function store(StoreShopRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->images->store($request->file('logo'), self::LOGO_DIRECTORY);
        }

        $shop = Shop::create($data);

        return redirect()->route('admin.shops.index')
            ->with('success', "Shop \"{$shop->name}\" created.");
    }

    public function edit(Shop $shop): Response
    {
        $this->authorize('update', $shop);

        return Inertia::render('Admin/Shops/Edit', [
            'shop' => $shop->loadCount('shopProducts'),
            'owners' => $this->ownerOptions(),
        ]);
    }

    public function update(UpdateShopRequest $request, Shop $shop): RedirectResponse
    {
        $data = $request->safe()->except(['logo', 'remove_logo']);

        $data['logo'] = $this->images->replace(
            $request->file('logo'),
            $shop->logo,
            self::LOGO_DIRECTORY,
            $request->boolean('remove_logo'),
        );

        $shop->update($data);

        return redirect()->route('admin.shops.index')
            ->with('success', "Shop \"{$shop->name}\" updated.");
    }

    public function destroy(Shop $shop): RedirectResponse
    {
        $this->authorize('delete', $shop);

        // Collect files first: listings are removed by the FK cascade.
        $files = $shop->shopProducts()->whereNotNull('custom_thumbnail')->pluck('custom_thumbnail')
            ->push($shop->logo)
            ->all();

        DB::transaction(fn () => $shop->delete());

        $this->images->delete(...$files);

        return redirect()->route('admin.shops.index')
            ->with('success', "Shop \"{$shop->name}\" deleted.");
    }

    /** Shop owners for the owner <select>, inactive ones flagged. */
    private function ownerOptions(): array
    {
        return User::shopOwners()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_active'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'label' => $u->name.' ('.$u->email.')'.($u->is_active ? '' : ' — inactive'),
            ])
            ->all();
    }
}
