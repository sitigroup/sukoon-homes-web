<?php

namespace App\Plugins\TrustVerification\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\TrustVerification\Models\TvPackage;
use App\Plugins\TrustVerification\Services\TrustVerificationPermissionService;
use App\Plugins\TrustVerification\Services\TrustVerificationService;
use App\Plugins\TrustVerification\Support\ChecksTrustVerificationPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TrustVerificationPackageAdminController extends Controller
{
    use ChecksTrustVerificationPermissions;

    public function index(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'city_slug' => (string) $request->query('city_slug', ''),
            'type' => (string) $request->query('type', ''),
            'is_active' => (string) $request->query('is_active', ''),
        ];

        $query = TvPackage::query()->withCount('orders');

        if ($filters['city_slug'] !== '') {
            $query->where('city_slug', $filters['city_slug']);
        }

        if (in_array($filters['type'], ['tenant', 'owner'], true)) {
            $query->where('type', $filters['type']);
        }

        if ($filters['is_active'] === '1') {
            $query->where('is_active', true);
        } elseif ($filters['is_active'] === '0') {
            $query->where('is_active', false);
        }

        if ($filters['q'] !== '') {
            $term = '%'.$filters['q'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term);
            });
        }

        $packages = $query
            ->orderBy('city_slug')
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return view('trust-verification::admin.packages.index', [
            'packages' => $packages,
            'cityCatalog' => TrustVerificationService::cityCatalog(),
            'cityOptions' => TrustVerificationService::cityCatalog(),
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        return view('trust-verification::admin.packages.form', [
            'package' => new TvPackage([
                'type' => 'tenant',
                'city_slug' => TrustVerificationService::enabledCitySlugs()[0] ?? TrustVerificationService::CITY_BARMER,
                'currency' => 'INR',
                'delivery_hours' => 72,
                'sort_order' => 10,
                'is_active' => true,
            ]),
            'features' => TrustVerificationService::defaultFeatureTemplate('tenant'),
            'cityCatalog' => TrustVerificationService::cityCatalog(),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $data = $this->validatedPackage($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $this->buildSlug($data['type'], $data['name'], $data['city_slug']));
        $data['features'] = $this->buildFeatures($request, $data['type']);

        TvPackage::create($data);

        return redirect()->route('trust-verification.packages.index')->with('success', 'Package created');
    }

    public function edit(TvPackage $package)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $package->load('priceLogs');

        return view('trust-verification::admin.packages.form', [
            'package' => $package,
            'features' => $this->mergeFeatures($package),
            'cityCatalog' => TrustVerificationService::cityCatalog(),
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, TvPackage $package)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $data = $this->validatedPackage($request, $package);
        if ($request->filled('slug')) {
            $data['slug'] = $this->uniqueSlug($data['slug'], $package->id);
        } else {
            unset($data['slug']);
        }
        $data['features'] = $this->buildFeatures($request, $data['type']);

        $oldPrice = $package->price;
        $package->update($data);
        TrustVerificationService::logPackagePriceChange($package, (int) $oldPrice, (int) $data['price'], auth()->id());

        return redirect()->route('trust-verification.packages.index')->with('success', 'Package updated');
    }

    private function validatedPackage(Request $request, ?TvPackage $package = null): array
    {
        $citySlugs = TrustVerificationService::registeredCitySlugs();

        $data = $request->validate([
            'type' => ['required', Rule::in(['tenant', 'owner'])],
            'city_slug' => ['required', Rule::in($citySlugs)],
            'name' => 'required|string|max:120',
            'slug' => 'nullable|string|max:120',
            'price' => 'required|integer|min:1|max:999999',
            'currency' => 'nullable|string|max:8',
            'delivery_hours' => 'required|integer|min:1|max:720',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'is_active' => 'nullable|boolean',
        ]);

        $data['currency'] = $data['currency'] ?? 'INR';
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    private function buildSlug(string $type, string $name, string $citySlug): string
    {
        return Str::slug($type.'-'.Str::slug($name).'-'.$citySlug);
    }

    private function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $slug = Str::slug($slug);
        $base = $slug;
        $i = 1;

        while (TvPackage::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function buildFeatures(Request $request, string $type): array
    {
        $template = TrustVerificationService::defaultFeatureTemplate($type);
        $included = $request->input('features_included', []);

        return array_map(function ($feature) use ($included) {
            $key = $feature['key'];

            return [
                'key' => $key,
                'label' => $feature['label'],
                'included' => array_key_exists($key, $included),
            ];
        }, $template);
    }

    private function mergeFeatures(TvPackage $package): array
    {
        $template = TrustVerificationService::defaultFeatureTemplate($package->type);
        $saved = collect($package->features ?? [])->keyBy('key');

        return array_map(function ($feature) use ($saved) {
            $row = $saved->get($feature['key']);

            return [
                'key' => $feature['key'],
                'label' => $row['label'] ?? $feature['label'],
                'included' => (bool) ($row['included'] ?? false),
            ];
        }, $template);
    }
}
