<?php

namespace App\Plugins\TrustVerification\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\TrustVerification\Models\TvCity;
use App\Plugins\TrustVerification\Services\TrustVerificationPermissionService;
use App\Plugins\TrustVerification\Services\TrustVerificationService;
use App\Plugins\TrustVerification\Support\ChecksTrustVerificationPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TrustVerificationCityAdminController extends Controller
{
    use ChecksTrustVerificationPermissions;

    public function index(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'is_enabled' => (string) $request->query('is_enabled', ''),
        ];

        $query = TvCity::query()->withCount(['packages']);

        if ($filters['is_enabled'] === '1') {
            $query->where('is_enabled', true);
        } elseif ($filters['is_enabled'] === '0') {
            $query->where('is_enabled', false);
        }

        if ($filters['q'] !== '') {
            $term = '%'.$filters['q'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('label', 'like', $term)
                    ->orWhere('slug', 'like', $term);
            });
        }

        $cities = $query
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        return view('trust-verification::admin.cities.index', [
            'cities' => $cities,
            'filters' => $filters,
        ]);
    }

    public function store(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $data = $request->validate([
            'label' => 'required|string|max:120',
            'slug' => 'nullable|string|max:80',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'is_enabled' => 'nullable|boolean',
        ]);

        $slug = Str::slug($data['slug'] ?? $data['label']);
        if ($slug === '') {
            return redirect()->back()->withErrors(['label' => 'Invalid city name'])->withInput();
        }

        if (TvCity::where('slug', $slug)->exists()) {
            return redirect()->back()->withErrors(['slug' => 'City slug already exists'])->withInput();
        }

        TvCity::create([
            'slug' => $slug,
            'label' => $data['label'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        return redirect()->route('trust-verification.cities.index')->with('success', 'City added');
    }

    public function update(Request $request, TvCity $city)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $data = $request->validate([
            'label' => 'required|string|max:120',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'is_enabled' => 'nullable|boolean',
        ]);

        $willDisable = $request->has('is_enabled') && ! $request->boolean('is_enabled');
        if ($willDisable && $city->is_enabled) {
            $otherEnabled = TvCity::where('is_enabled', true)->where('id', '!=', $city->id)->exists();
            if (! $otherEnabled) {
                return redirect()->back()->with('error', 'At least one city must stay enabled.');
            }
        }

        $city->update([
            'label' => $data['label'],
            'sort_order' => (int) ($data['sort_order'] ?? $city->sort_order),
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        return redirect()->route('trust-verification.cities.index')->with('success', 'City updated');
    }

    public function toggle(TvCity $city)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        if ($city->is_enabled) {
            $otherEnabled = TvCity::where('is_enabled', true)->where('id', '!=', $city->id)->exists();
            if (! $otherEnabled) {
                return redirect()->back()->with('error', 'At least one city must stay enabled.');
            }
            $city->is_enabled = false;
        } else {
            $city->is_enabled = true;
        }

        $city->save();

        $state = $city->is_enabled ? 'enabled' : 'disabled';

        return redirect()->route('trust-verification.cities.index')->with('success', "City {$state}");
    }
}
