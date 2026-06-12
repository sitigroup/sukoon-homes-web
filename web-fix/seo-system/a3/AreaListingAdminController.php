<?php

namespace App\Plugins\AreaListing\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Plugins\AreaListing\Models\Area;
use App\Plugins\AreaListing\Models\City;
use App\Plugins\AreaListing\Models\State;
use App\Plugins\AreaListing\Models\SubArea;
use App\Plugins\AreaListing\Services\AreaListingPropertyLocationRepairService;
use App\Plugins\AreaListing\Services\AreaListingService;
use App\Plugins\AreaListing\Support\AreaWorkflowState;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AreaListingAdminController extends Controller
{
    public function index(Request $request)
    {
        $cityOptions = Area::query()
            ->selectRaw('MAX(city_id) as city_id, COALESCE(NULLIF(city_name, ""), city) as city, state, country')
            ->where(function ($query) {
                $query->whereNotNull('city_name')->where('city_name', '!=', '')
                    ->orWhere(function ($inner) {
                        $inner->whereNotNull('city')->where('city', '!=', '');
                    });
            })
            ->groupByRaw('COALESCE(NULLIF(city_name, ""), city), state, country')
            ->orderBy('state')
            ->orderByRaw('COALESCE(NULLIF(city_name, ""), city)')
            ->get();

        $selectedCity = trim((string) $request->query('city', ''));
        $selectedState = trim((string) $request->query('state', ''));
        $selectedCountry = trim((string) $request->query('country', ''));

        if ($selectedCity !== '' && $cityOptions->isNotEmpty()) {
            $selectedCityExists = $cityOptions->contains(function ($cityOption) use ($selectedCity, $selectedState, $selectedCountry) {
                if ((string) $cityOption->city !== $selectedCity) {
                    return false;
                }

                if ($selectedState !== '' && (string) $cityOption->state !== $selectedState) {
                    return false;
                }

                if ($selectedCountry !== '' && (string) $cityOption->country !== $selectedCountry) {
                    return false;
                }

                return true;
            });

            if (! $selectedCityExists) {
                $fallbackCity = $cityOptions->first(function ($cityOption) {
                    return Str::lower((string) $cityOption->city) === 'barmer';
                }) ?: $cityOptions->first();

                $query = [
                    'city' => (string) $fallbackCity->city,
                    'state' => (string) $fallbackCity->state,
                    'country' => (string) $fallbackCity->country,
                ];

                if ($request->query('tab')) {
                    $query['tab'] = $request->query('tab');
                }

                return redirect()
                    ->route('area-listing.index', $query)
                    ->with('warning', __('Selected city has no area records. Showing available city data instead.'));
            }
        }

        if ($selectedCity === '' && $cityOptions->isNotEmpty()) {
            $firstCity = $cityOptions->first(function ($cityOption) {
                return Str::lower((string) $cityOption->city) === 'barmer';
            }) ?: $cityOptions->first();
            $selectedCity = (string) $firstCity->city;
            $selectedState = (string) $firstCity->state;
            $selectedCountry = (string) $firstCity->country;
        }

        $areasQuery = Area::query()
            ->withCount('subAreas')
            ->active()
            ->orderByRaw('COALESCE(NULLIF(city_name, ""), city)')
            ->orderBy('name');
        $this->applyAreaCityFilter($areasQuery, $selectedCity, $selectedState, $selectedCountry);
        $areas = $areasQuery->get();

        $areaIds = $areas->pluck('id')->all();
        $subAreas = SubArea::with('area')
            ->whereIn('area_id', $areaIds)
            ->active()
            ->orderBy('name')
            ->get();

        $areaPropertyCounts = DB::table('area_listing_property_locations')
            ->select('area_id', DB::raw('COUNT(*) as total'))
            ->whereIn('area_id', $areaIds)
            ->groupBy('area_id')
            ->pluck('total', 'area_id');

        $areaProjectCounts = DB::table('area_listing_project_locations')
            ->select('area_id', DB::raw('COUNT(*) as total'))
            ->whereIn('area_id', $areaIds)
            ->groupBy('area_id')
            ->pluck('total', 'area_id');
        $areaPropertyLastUsed = DB::table('area_listing_property_locations')
            ->select('area_id', DB::raw('MAX(updated_at) as last_used_at'))
            ->whereIn('area_id', $areaIds)
            ->groupBy('area_id')
            ->pluck('last_used_at', 'area_id');
        $areaProjectLastUsed = DB::table('area_listing_project_locations')
            ->select('area_id', DB::raw('MAX(updated_at) as last_used_at'))
            ->whereIn('area_id', $areaIds)
            ->groupBy('area_id')
            ->pluck('last_used_at', 'area_id');
        $areaLastUsed = collect($areaIds)->mapWithKeys(function ($areaId) use ($areaPropertyLastUsed, $areaProjectLastUsed) {
            return [$areaId => collect([$areaPropertyLastUsed[$areaId] ?? null, $areaProjectLastUsed[$areaId] ?? null])->filter()->sort()->last()];
        });

        $subAreaIds = $subAreas->pluck('id')->all();
        $subAreaPropertyCounts = DB::table('area_listing_property_locations')
            ->select('sub_area_id', DB::raw('COUNT(*) as total'))
            ->whereIn('sub_area_id', $subAreaIds)
            ->groupBy('sub_area_id')
            ->pluck('total', 'sub_area_id');

        $subAreaProjectCounts = DB::table('area_listing_project_locations')
            ->select('sub_area_id', DB::raw('COUNT(*) as total'))
            ->whereIn('sub_area_id', $subAreaIds)
            ->groupBy('sub_area_id')
            ->pluck('total', 'sub_area_id');
        $subAreaPropertyLastUsed = DB::table('area_listing_property_locations')
            ->select('sub_area_id', DB::raw('MAX(updated_at) as last_used_at'))
            ->whereIn('sub_area_id', $subAreaIds)
            ->groupBy('sub_area_id')
            ->pluck('last_used_at', 'sub_area_id');
        $subAreaProjectLastUsed = DB::table('area_listing_project_locations')
            ->select('sub_area_id', DB::raw('MAX(updated_at) as last_used_at'))
            ->whereIn('sub_area_id', $subAreaIds)
            ->groupBy('sub_area_id')
            ->pluck('last_used_at', 'sub_area_id');
        $subAreaLastUsed = collect($subAreaIds)->mapWithKeys(function ($subAreaId) use ($subAreaPropertyLastUsed, $subAreaProjectLastUsed) {
            return [$subAreaId => collect([$subAreaPropertyLastUsed[$subAreaId] ?? null, $subAreaProjectLastUsed[$subAreaId] ?? null])->filter()->sort()->last()];
        });

        $pendingSuggestions = DB::table('area_listing_suggestions as suggestions')
            ->leftJoin('customers', 'customers.id', '=', 'suggestions.suggested_by')
            ->leftJoin('area_listing_areas as parent_area', 'parent_area.id', '=', 'suggestions.area_id')
            ->select(
                'suggestions.*',
                'customers.name as suggested_by_name',
                'customers.email as suggested_by_email',
                'customers.mobile as suggested_by_mobile',
                'parent_area.name as parent_area_name',
                'parent_area.city as parent_area_city',
                'parent_area.state as parent_area_state'
            )
            ->where('suggestions.status', 'pending')
            ->orderByDesc('suggestions.created_at')
            ->get();

        $suggestionDisplayItems = $this->buildSuggestionDisplayItems($pendingSuggestions);

        $mergeAreaOptions = Area::query()
            ->active()
            ->orderByRaw('COALESCE(NULLIF(city_name, ""), city)')
            ->orderBy('name')
            ->get();

        $mergeSubAreaOptions = SubArea::query()
            ->with('area')
            ->active()
            ->whereHas('area', fn ($query) => $query->active())
            ->orderBy('name')
            ->get();

        $archivedAreasQuery = Area::query()
            ->withCount('subAreas')
            ->where('workflow_status', 'archived')
            ->orderByRaw('COALESCE(NULLIF(city_name, ""), city)')
            ->orderBy('name');
        $this->applyAreaCityFilter($archivedAreasQuery, $selectedCity, $selectedState, $selectedCountry);
        $archivedAreas = $archivedAreasQuery->get()->map(function (Area $area) {
            return $this->attachArchivedAgeMeta($area);
        });

        $archivedSubAreasQuery = SubArea::query()
            ->with('area')
            ->where('workflow_status', 'archived')
            ->orderBy('name');
        if ($selectedCity !== '') {
            $archivedSubAreasQuery->whereHas('area', function ($query) use ($selectedCity, $selectedState, $selectedCountry) {
                $this->applyAreaCityFilter($query, $selectedCity, $selectedState, $selectedCountry);
            });
        }
        $archivedSubAreas = $archivedSubAreasQuery->get();

        $archivedAreaIds = $archivedAreas->pluck('id')->all();
        $archivedAreaPropertyCounts = DB::table('area_listing_property_locations')
            ->select('area_id', DB::raw('COUNT(*) as total'))
            ->whereIn('area_id', $archivedAreaIds)
            ->groupBy('area_id')
            ->pluck('total', 'area_id');
        $archivedAreaProjectCounts = DB::table('area_listing_project_locations')
            ->select('area_id', DB::raw('COUNT(*) as total'))
            ->whereIn('area_id', $archivedAreaIds)
            ->groupBy('area_id')
            ->pluck('total', 'area_id');

        $archivedSubAreaIds = $archivedSubAreas->pluck('id')->all();
        $archivedSubAreaPropertyCounts = DB::table('area_listing_property_locations')
            ->select('sub_area_id', DB::raw('COUNT(*) as total'))
            ->whereIn('sub_area_id', $archivedSubAreaIds)
            ->groupBy('sub_area_id')
            ->pluck('total', 'sub_area_id');
        $archivedSubAreaProjectCounts = DB::table('area_listing_project_locations')
            ->select('sub_area_id', DB::raw('COUNT(*) as total'))
            ->whereIn('sub_area_id', $archivedSubAreaIds)
            ->groupBy('sub_area_id')
            ->pluck('total', 'sub_area_id');

        return view('area-listing::admin.index', compact(
            'areas',
            'subAreas',
            'areaPropertyCounts',
            'areaProjectCounts',
            'areaLastUsed',
            'subAreaPropertyCounts',
            'subAreaProjectCounts',
            'subAreaLastUsed',
            'cityOptions',
            'selectedCity',
            'selectedState',
            'selectedCountry',
            'pendingSuggestions',
            'suggestionDisplayItems',
            'mergeAreaOptions',
            'mergeSubAreaOptions',
            'archivedAreas',
            'archivedSubAreas',
            'archivedAreaPropertyCounts',
            'archivedAreaProjectCounts',
            'archivedSubAreaPropertyCounts',
            'archivedSubAreaProjectCounts'
        ));
    }

    public function areasIndex()
    {
        return redirect()->route('area-listing.index');
    }

    public function subAreasIndex()
    {
        return redirect()->route('area-listing.index', ['tab' => 'sub-areas']);
    }

    public function areasData(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $city = trim((string) $request->input('city'));
        $state = trim((string) $request->input('state'));
        $country = trim((string) $request->input('country'));

        if ($request->boolean('archived')) {
            $query = Area::query()
                ->withCount('subAreas')
                ->where('workflow_status', 'archived')
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($inner) use ($search) {
                        $inner->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('city', 'LIKE', "%{$search}%")
                            ->orWhere('state', 'LIKE', "%{$search}%")
                            ->orWhere('country', 'LIKE', "%{$search}%");
                    });
                });
            $this->applyAreaCityFilter($query, $city, $state, $country);

            $rows = $query->orderByDesc('archived_at')->orderBy('name')->get()->map(function (Area $area) {
                $meta = $this->archivedAgeMeta($area);

                return [
                    'id' => $area->id,
                    'name' => e($area->name),
                    'city' => e($area->city_name ?: $area->city),
                    'state' => e($area->state),
                    'country' => e($area->country),
                    'sub_areas_count' => $area->sub_areas_count,
                    'archived_at' => $meta['archived_at'],
                    'days_archived' => $meta['days_archived'],
                    'archived_label' => $meta['archived_label'],
                ];
            });

            return response()->json(['total' => $rows->count(), 'rows' => $rows]);
        }

        $offset = (int) $request->input('offset', 0);
        $limit = (int) $request->input('limit', 10);
        $sort = $this->safeSort($request->input('sort'), ['id', 'name', 'city', 'state', 'country', 'sort_order', 'status'], 'sort_order');
        $order = strtoupper($request->input('order', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $query = Area::query()
            ->withCount([
                'subAreas',
                'subAreas as active_sub_areas_count' => fn ($q) => $q->where('status', 1),
            ])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('city', 'LIKE', "%{$search}%")
                        ->orWhere('state', 'LIKE', "%{$search}%")
                        ->orWhere('country', 'LIKE', "%{$search}%");
                });
            });
        $this->applyAreaCityFilter($query, $city, $state, $country);

        $total = $query->count();
        $rows = $query->orderBy($sort, $order)->skip($offset)->take($limit)->get()->map(function ($area) {
            $propertyCount = DB::table('area_listing_property_locations')->where('area_id', $area->id)->count();
            $projectCount = DB::table('area_listing_project_locations')->where('area_id', $area->id)->count();

            return [
                'id' => $area->id,
                'name' => e($area->name),
                'city' => e($area->city_name ?: $area->city),
                'state' => e($area->state),
                'country' => e($area->country),
                'sub_areas_count' => $area->sub_areas_count,
                'listings_count' => $propertyCount . ' / ' . $projectCount,
                'sort_order' => $area->sort_order,
                'status' => $area->status ? __('Enabled') : __('Disabled'),
                'operate' => view('area-listing::admin.partials.area-actions', compact('area'))->render(),
            ];
        });

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function subAreasData(Request $request)
    {
        $offset = (int) $request->input('offset', 0);
        $limit = (int) $request->input('limit', 10);
        $sort = $this->safeSort($request->input('sort'), ['id', 'name', 'sort_order', 'status'], 'sort_order');
        $order = strtoupper($request->input('order', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $search = trim((string) $request->input('search'));
        $areaId = $request->input('area_id');
        $city = trim((string) $request->input('city'));
        $state = trim((string) $request->input('state'));
        $country = trim((string) $request->input('country'));

        $query = SubArea::with('area')
            ->when($areaId, fn ($q) => $q->where('area_id', $areaId))
            ->when($city !== '', function ($q) use ($city, $state, $country) {
                $q->whereHas('area', function ($areaQuery) use ($city, $state, $country) {
                    $this->applyAreaCityFilter($areaQuery, $city, $state, $country);
                });
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'LIKE', "%{$search}%")
                        ->orWhereHas('area', function ($areaQuery) use ($search) {
                            $areaQuery->where('name', 'LIKE', "%{$search}%")
                                ->orWhere('city', 'LIKE', "%{$search}%");
                        });
                });
            });

        $total = $query->count();
        $rows = $query->orderBy($sort, $order)->skip($offset)->take($limit)->get()->map(function ($subArea) {
            $propertyCount = DB::table('area_listing_property_locations')->where('sub_area_id', $subArea->id)->count();
            $projectCount = DB::table('area_listing_project_locations')->where('sub_area_id', $subArea->id)->count();

            return [
                'id' => $subArea->id,
                'area_name' => e($subArea->area?->name),
                'city' => e($subArea->area?->city_name ?: $subArea->area?->city),
                'name' => e($subArea->name),
                'listings_count' => $propertyCount . ' / ' . $projectCount,
                'sort_order' => $subArea->sort_order,
                'status' => $subArea->status ? __('Enabled') : __('Disabled'),
                'operate' => view('area-listing::admin.partials.sub-area-actions', compact('subArea'))->render(),
            ];
        });

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function storeArea(Request $request)
    {
        $validator = $this->areaValidator($request);
        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $validator->errors()->first()], 422);
            }

            return back()->with('error', $validator->errors()->first())->withInput();
        }

        $areaContext = $this->resolveAreaContext($request);

        if (! $request->boolean('force_create')) {
            $similar = $this->getSimilarExistingArea(
                $this->normalizedName($request->name),
                $areaContext['city_id'],
                (string) $areaContext['city_name'],
                (string) $areaContext['state']
            );

            if ($similar) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'status' => 'similar_exists',
                        'message' => 'Similar area already exists',
                        'suggestion' => $similar,
                    ], 409);
                }

                return back()
                    ->with('error', __('Similar area ":name" already exists.', ['name' => $similar['name']]))
                    ->withInput();
            }
        }

        $area = Area::updateOrCreate(
            [
                'city' => $this->cleanName($request->city),
                'city_name' => $this->cleanName($request->city),
                'state' => $this->cleanName($request->state),
                'country' => $this->cleanName($request->country ?: 'India'),
                'normalized_name' => $this->normalizedName($request->name),
            ],
            $this->areaPayload($request)
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'data' => [
                    'id' => $area->id,
                    'name' => $area->name,
                    'city_id' => $area->city_id,
                    'city' => $area->city_name ?: $area->city,
                    'state_id' => $area->state_id,
                    'state' => $area->state,
                    'country' => $area->country,
                ],
            ]);
        }

        return back()->with('success', __('Area saved successfully'));
    }

    public function updateArea(Request $request, Area $area)
    {
        $validator = $this->areaValidator($request);
        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first())->withInput();
        }

        $area->fill($this->areaPayload($request));
        $area->save();

        return back()->with('success', __('Area updated successfully'));
    }

    public function destroyArea(Area $area)
    {
        if (
            DB::table('area_listing_property_locations')->where('area_id', $area->id)->exists()
            || DB::table('area_listing_project_locations')->where('area_id', $area->id)->exists()
        ) {
            $this->applyAreaArchiveState($area);
            return back()->with('success', __('This area is used by listings, so it was archived instead of deleted.'));
        }

        $area->delete();
        return back()->with('success', __('Area deleted successfully'));
    }

    public function restoreArea(Area $area)
    {
        try {
            $this->applyAreaRestoreState($area);
        } catch (\Throwable $e) {
            return back()->with('error', __('Unable to restore area.'));
        }

        return back()->with('success', __('Area restored successfully.'));
    }

    public function forceDeleteArea(Area $area)
    {
        if ($this->areaIsUsed($area)) {
            return back()->with('error', __('This area is used by listings, so it cannot be permanently deleted.'));
        }

        if (SubArea::where('area_id', $area->id)->exists()) {
            return back()->with('error', __('Delete or restore this area sub areas before permanently deleting the area.'));
        }

        $area->delete();

        return back()->with('success', __('Area permanently deleted successfully.'));
    }

    public function storeSubArea(Request $request)
    {
        $validator = $this->subAreaValidator($request);
        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $validator->errors()->first()], 422);
            }

            return back()->with('error', $validator->errors()->first())->withInput();
        }

        if (! $request->boolean('force_create')) {
            $similar = $this->getSimilarExistingSubArea(
                (int) $request->input('area_id'),
                $this->normalizedName($request->name)
            );

            if ($similar) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'status' => 'similar_exists',
                        'message' => 'Similar sub area already exists',
                        'suggestion' => $similar,
                    ], 409);
                }

                return back()
                    ->with('error', __('Similar sub area ":name" already exists.', ['name' => $similar['name']]))
                    ->withInput();
            }
        }

        $subArea = SubArea::updateOrCreate(
            [
                'area_id' => $request->area_id,
                'normalized_name' => $this->normalizedName($request->name),
            ],
            $this->subAreaPayload($request)
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'data' => [
                    'id' => $subArea->id,
                    'area_id' => $subArea->area_id,
                    'name' => $subArea->name,
                ],
            ]);
        }

        return back()->with('success', __('Sub Area saved successfully'));
    }

    public function updateSubArea(Request $request, SubArea $subArea)
    {
        $validator = $this->subAreaValidator($request);
        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first())->withInput();
        }

        $subArea->fill($this->subAreaPayload($request));
        $subArea->save();

        return back()->with('success', __('Sub Area updated successfully'));
    }

    public function destroySubArea(SubArea $subArea)
    {
        if (
            DB::table('area_listing_property_locations')->where('sub_area_id', $subArea->id)->exists()
            || DB::table('area_listing_project_locations')->where('sub_area_id', $subArea->id)->exists()
        ) {
            $this->applySubAreaArchiveState($subArea);
            return back()->with('success', __('This sub area is used by listings, so it was archived instead of deleted.'));
        }

        $subArea->delete();
        return back()->with('success', __('Sub Area deleted successfully'));
    }

    public function restoreSubArea(SubArea $subArea)
    {
        try {
            if ($subArea->area && AreaWorkflowState::isArchived($subArea->area)) {
                $this->applyAreaRestoreState($subArea->area);
            }
            $this->applySubAreaRestoreState($subArea);
        } catch (\Throwable $e) {
            return back()->with('error', __('Unable to restore sub area.'));
        }

        return back()->with('success', __('Sub area restored successfully.'));
    }

    public function forceDeleteSubArea(SubArea $subArea)
    {
        if ($this->subAreaIsUsed($subArea)) {
            return back()->with('error', __('This sub area is used by listings, so it cannot be permanently deleted.'));
        }

        $subArea->delete();

        return back()->with('success', __('Sub area permanently deleted successfully.'));
    }

    public function bulkArchiveAreas(Request $request)
    {
        return response()->json($this->bulkArchiveAreasByIds($this->bulkIdsFromRequest($request)));
    }

    public function bulkDeleteAreas(Request $request)
    {
        return response()->json($this->bulkDeleteAreasByIds($this->bulkIdsFromRequest($request)));
    }

    public function bulkRestoreAreas(Request $request)
    {
        return response()->json($this->bulkRestoreAreasByIds($this->bulkIdsFromRequest($request)));
    }

    public function bulkArchiveSubAreas(Request $request)
    {
        return response()->json($this->bulkArchiveSubAreasByIds($this->bulkIdsFromRequest($request)));
    }

    public function bulkDeleteSubAreas(Request $request)
    {
        return response()->json($this->bulkDeleteSubAreasByIds($this->bulkIdsFromRequest($request)));
    }

    public function bulkRestoreSubAreas(Request $request)
    {
        return response()->json($this->bulkRestoreSubAreasByIds($this->bulkIdsFromRequest($request)));
    }

    public function approveGroupSuggestion(Request $request, string $groupToken)
    {
        $rows = $this->pendingSuggestionsByGroupToken($groupToken);
        if ($rows->isEmpty()) {
            return back()->with('error', __('Pending suggestion group not found.'));
        }

        $note = $request->input('review_note');

        $result = $this->approveSuggestionGroupByToken($groupToken, $note);
        if (($result['approved'] ?? 0) === 0 && ($result['errors'] ?? 0) > 0) {
            return back()->with('error', __('Could not approve suggestions in this group.'));
        }
        if (($result['approved'] ?? 0) === 0) {
            return back()->with('error', __('Pending suggestion group not found.'));
        }

        return back()->with('success', __('Grouped suggestions approved successfully.'));
    }

    public function approveAreaOnlySuggestion(Request $request, string $groupToken)
    {
        $areaRow = $this->pendingSuggestionsByGroupToken($groupToken)->firstWhere('type', 'area');
        if (! $areaRow) {
            return back()->with('error', __('Area suggestion not found in this group.'));
        }

        if (! $this->executeApproveSuggestion($areaRow, $request->input('review_note'))) {
            return back()->with('error', __('Could not approve area suggestion. Ensure city and parent area are set.'));
        }

        return back()->with('success', __('Area suggestion approved. Sub area suggestion remains pending.'));
    }

    public function rejectGroupSuggestion(Request $request, string $groupToken)
    {
        $validator = Validator::make($request->all(), [
            'review_note' => 'required|string|max:1000',
        ]);
        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first());
        }

        $result = $this->rejectSuggestionGroupByToken($groupToken, $request->input('review_note'));
        if (($result['rejected'] ?? 0) === 0) {
            return back()->with('error', __('Pending suggestion group not found.'));
        }

        return back()->with('success', __('Grouped suggestions rejected.'));
    }

    public function bulkApproveSuggestions(Request $request)
    {
        return response()->json($this->bulkProcessSuggestions($request, 'approve'));
    }

    public function bulkApproveAreaOnlySuggestions(Request $request)
    {
        $groupTokens = collect($request->input('group_tokens', []))->map(fn ($t) => trim((string) $t))->filter()->unique()->values();
        $note = $request->input('review_note');
        $approved = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($groupTokens as $token) {
            $areaRow = $this->pendingSuggestionsByGroupToken($token)->firstWhere('type', 'area');
            if (! $areaRow) {
                $skipped++;
                continue;
            }

            try {
                if ($this->executeApproveSuggestion($areaRow, $note)) {
                    $approved++;
                } else {
                    $errors++;
                }
            } catch (\Throwable $e) {
                $errors++;
            }
        }

        return response()->json(['approved' => $approved, 'skipped' => $skipped, 'errors' => $errors]);
    }

    public function bulkRejectSuggestions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'review_note' => 'required|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        return response()->json($this->bulkProcessSuggestions($request, 'reject'));
    }

    public function exportCsv()
    {
        $filename = 'area-wise-export-' . now()->format('Ymd_His') . '.csv';

        return Response::streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['city', 'state', 'country', 'area', 'sub_area']);

            Area::with('subAreas')->orderByRaw('COALESCE(NULLIF(city_name, ""), city)')->orderBy('name')->chunk(100, function ($areas) use ($handle) {
                foreach ($areas as $area) {
                    $city = $area->city_name ?: $area->city;
                    if ($area->subAreas->isEmpty()) {
                        fputcsv($handle, [$city, $area->state, $area->country, $area->name, '']);
                        continue;
                    }

                    foreach ($area->subAreas as $subArea) {
                        fputcsv($handle, [$city, $area->state, $area->country, $area->name, $subArea->name]);
                    }
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function importCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);
        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json($this->emptyDryRunResult([
                    'invalid' => [[
                        'row' => 0,
                        'reason' => $validator->errors()->first(),
                    ]],
                ]), 422);
            }

            return back()->with('error', $validator->errors()->first());
        }

        $dryRun = $this->buildCsvDryRun($request->file('csv_file')->getRealPath());
        $token = (string) Str::uuid();
        Cache::put($this->csvDryRunCacheKey($token), $dryRun['will_create'], now()->addMinutes(45));

        if ($request->ajax() || $request->wantsJson()) {
            return response()
                ->json($dryRun)
                ->header('X-Area-Listing-Dry-Run-Token', $token);
        }

        return back()->with('success', __('CSV dry-run completed. Review the result before confirming import.'))
            ->with('csv_dry_run', $dryRun)
            ->with('csv_dry_run_token', $token);
    }

    public function confirmCsvImport(Request $request)
    {
        $token = (string) $request->input('dry_run_token', $request->header('X-Area-Listing-Dry-Run-Token'));
        $cacheKey = $this->csvDryRunCacheKey($token);
        $willCreate = $token !== '' ? Cache::get($cacheKey) : null;

        if (! is_array($willCreate)) {
            $message = __('CSV dry-run token is missing or expired. Please run dry-run again.');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => true, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $createdAreas = 0;
        $createdSubAreas = 0;
        $createdAreaIdsByKey = [];

        DB::transaction(function () use ($willCreate, &$createdAreas, &$createdSubAreas, &$createdAreaIdsByKey) {
            foreach ($willCreate as $item) {
                if (($item['type'] ?? '') === 'area') {
                    $area = Area::create([
                        'state_id' => $item['state_id'] ?? null,
                        'city_id' => $item['city_id'] ?? null,
                        'city_name' => $item['city'] ?? '',
                        'city' => $item['city'] ?? '',
                        'state' => $item['state'] ?? '',
                        'country' => $item['country'] ?? 'India',
                        'name' => $item['area_name'] ?? '',
                        'normalized_name' => $item['area_normalized_name'] ?? $this->normalizedName($item['area_name'] ?? ''),
                        'slug' => $item['area_slug'] ?? Str::slug($item['area_name'] ?? ''),
                        'status' => true,
                        'workflow_status' => 'active',
                    ]);

                    if (! empty($item['area_key'])) {
                        $createdAreaIdsByKey[$item['area_key']] = $area->id;
                    }

                    $createdAreas++;
                    continue;
                }

                if (($item['type'] ?? '') === 'sub_area') {
                    $areaId = $item['area_id'] ?? null;
                    if (! $areaId && ! empty($item['area_key'])) {
                        $areaId = $createdAreaIdsByKey[$item['area_key']] ?? null;
                    }

                    if (! $areaId) {
                        throw new \RuntimeException('Stored dry-run sub-area item has no parent area.');
                    }

                    SubArea::create([
                        'area_id' => $areaId,
                        'name' => $item['sub_area_name'] ?? '',
                        'normalized_name' => $item['sub_area_normalized_name'] ?? $this->normalizedName($item['sub_area_name'] ?? ''),
                        'slug' => $item['sub_area_slug'] ?? Str::slug($item['sub_area_name'] ?? ''),
                        'status' => true,
                        'workflow_status' => 'active',
                    ]);

                    $createdSubAreas++;
                }
            }
        });

        Cache::forget($cacheKey);

        $message = __("Import confirmed. Areas: :areas, Sub Areas: :subareas", [
            'areas' => $createdAreas,
            'subareas' => $createdSubAreas,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'error' => false,
                'message' => $message,
                'created' => [
                    'areas' => $createdAreas,
                    'sub_areas' => $createdSubAreas,
                ],
            ]);
        }

        return back()->with('success', $message);
    }

    public function cancelCsvImport(Request $request)
    {
        $token = (string) $request->input('dry_run_token', $request->header('X-Area-Listing-Dry-Run-Token'));
        if ($token !== '') {
            Cache::forget($this->csvDryRunCacheKey($token));
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['error' => false, 'message' => __('CSV dry-run cancelled.')]);
        }

        return back()->with('success', __('CSV dry-run cancelled.'));
    }

    private function buildCsvDryRun(string $filePath): array
    {
        $result = $this->emptyDryRunResult();
        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle);
        $rowNumber = 1;
        $plannedAreaKeys = [];
        $plannedSubAreaKeys = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $result['summary']['total_rows']++;
            $mapped = $this->mapCsvRow($header, $row);
            $city = $this->cleanName($mapped['city'] ?? '');
            $state = $this->cleanName($mapped['state'] ?? '');
            $country = $this->cleanName($mapped['country'] ?? 'India') ?: 'India';
            $areaName = $this->cleanName($mapped['area'] ?? $mapped['name'] ?? '');
            $subAreaName = $this->cleanName($mapped['sub_area'] ?? $mapped['subarea'] ?? '');

            if ($city === '' || $areaName === '') {
                $this->pushDryRunItem($result, 'invalid', [
                    'row' => $rowNumber,
                    'city' => $city,
                    'state' => $state,
                    'country' => $country,
                    'area' => $areaName,
                    'sub_area' => $subAreaName,
                    'reason' => $city === '' ? 'missing_city' : 'missing_area',
                ]);
                continue;
            }

            $cityMatch = $this->resolveCsvCity($city, $state, $country);
            if ($cityMatch['status'] !== 'ok') {
                $this->pushDryRunItem($result, $cityMatch['status'] === 'conflict' ? 'conflicts' : 'invalid', [
                    'row' => $rowNumber,
                    'city' => $city,
                    'state' => $state,
                    'country' => $country,
                    'area' => $areaName,
                    'sub_area' => $subAreaName,
                    'reason' => $cityMatch['reason'],
                    'matches' => $cityMatch['matches'] ?? [],
                ]);
                continue;
            }

            $cityRecord = $cityMatch['city'];
            $areaDecision = $this->classifyCsvArea($rowNumber, $cityRecord, $state, $country, $areaName, $plannedAreaKeys);

            if ($areaDecision['status'] === 'conflict') {
                $this->pushDryRunItem($result, 'conflicts', $areaDecision['item'] + ['sub_area' => $subAreaName]);
                continue;
            }

            if ($areaDecision['status'] === 'duplicate' && $subAreaName === '') {
                $this->pushDryRunItem($result, 'duplicates_skipped', $areaDecision['item']);
                continue;
            }

            if ($areaDecision['status'] === 'create') {
                $this->pushDryRunItem($result, 'will_create', $areaDecision['item']);
                $plannedAreaKeys[$areaDecision['item']['area_key']] = $areaDecision['item'];
            }

            if ($subAreaName === '') {
                if ($areaDecision['status'] === 'planned_duplicate') {
                    $this->pushDryRunItem($result, 'duplicates_skipped', $areaDecision['item']);
                }
                continue;
            }

            $subAreaDecision = $this->classifyCsvSubArea($rowNumber, $areaDecision, $subAreaName, $plannedSubAreaKeys);

            if ($subAreaDecision['status'] === 'conflict') {
                $this->pushDryRunItem($result, 'conflicts', $subAreaDecision['item']);
                continue;
            }

            if ($subAreaDecision['status'] === 'duplicate') {
                $this->pushDryRunItem($result, 'duplicates_skipped', $subAreaDecision['item']);
                continue;
            }

            $this->pushDryRunItem($result, 'will_create', $subAreaDecision['item']);
            $plannedSubAreaKeys[$subAreaDecision['item']['sub_area_key']] = $subAreaDecision['item'];
        }
        fclose($handle);

        return $result;
    }

    private function emptyDryRunResult(array $overrides = []): array
    {
        $result = [
            'will_create' => [],
            'duplicates_skipped' => [],
            'conflicts' => [],
            'invalid' => [],
            'summary' => [
                'total_rows' => 0,
                'will_create' => 0,
                'duplicates_skipped' => 0,
                'conflicts' => 0,
                'invalid' => 0,
            ],
        ];

        foreach ($overrides as $key => $value) {
            if (array_key_exists($key, $result)) {
                $result[$key] = $value;
                if (isset($result['summary'][$key]) && is_array($value)) {
                    $result['summary'][$key] = count($value);
                }
            }
        }

        return $result;
    }

    private function pushDryRunItem(array &$result, string $bucket, array $item): void
    {
        $result[$bucket][] = $item;
        $result['summary'][$bucket] = count($result[$bucket]);
    }

    private function csvDryRunCacheKey(string $token): string
    {
        return 'area_listing_csv_dry_run:' . (Auth::id() ?: 'guest') . ':' . $token;
    }

    private function resolveCsvCity(string $city, string $state, string $country): array
    {
        $normalizedCity = $this->normalizedName($city);
        $candidates = City::query()
            ->where('normalized_name', $normalizedCity)
            ->get();

        if ($candidates->isEmpty()) {
            return [
                'status' => 'invalid',
                'reason' => 'city_not_found',
            ];
        }

        $matches = $candidates->filter(function (City $candidate) use ($state, $country) {
            $candidateState = $this->normalizedName($candidate->state);
            $candidateCountry = $this->normalizedName($candidate->country ?: 'India');
            $requestedState = $this->normalizedName($state);
            $requestedCountry = $this->normalizedName($country ?: 'India');

            return ($requestedState === '' || $candidateState === $requestedState)
                && $candidateCountry === $requestedCountry;
        });

        if ($matches->count() === 1) {
            return [
                'status' => 'ok',
                'city' => $matches->first(),
            ];
        }

        return [
            'status' => 'conflict',
            'reason' => $matches->count() > 1 ? 'ambiguous_city_match' : 'city_state_country_mismatch',
            'matches' => $candidates->map(fn (City $candidate) => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'state' => $candidate->state,
                'country' => $candidate->country,
            ])->values()->all(),
        ];
    }

    private function classifyCsvArea(int $rowNumber, City $city, string $state, string $country, string $areaName, array $plannedAreaKeys): array
    {
        $areaNormalized = $this->normalizedName($areaName);
        $areaKey = $city->id . '|' . $areaNormalized;
        $baseItem = [
            'type' => 'area',
            'row' => $rowNumber,
            'city_id' => $city->id,
            'state_id' => $city->state_id,
            'city' => $city->name,
            'state' => $city->state ?: $state,
            'country' => $city->country ?: $country,
            'area_name' => $areaName,
            'area_normalized_name' => $areaNormalized,
            'area_slug' => Str::slug($areaName),
            'area_key' => $areaKey,
        ];

        $sameNameDifferentCity = Area::query()
            ->where('normalized_name', $areaNormalized)
            ->where(function ($query) use ($city, $state, $country) {
                $query->where('city_id', '!=', $city->id)
                    ->orWhereNull('city_id')
                    ->orWhere('state', '!=', $city->state ?: $state)
                    ->orWhere('country', '!=', $city->country ?: $country);
            })
            ->first();

        if ($sameNameDifferentCity) {
            return [
                'status' => 'conflict',
                'item' => $baseItem + [
                    'reason' => 'same_normalized_area_exists_with_different_city_state_country',
                    'existing' => $this->areaConflictPayload($sameNameDifferentCity),
                ],
            ];
        }

        $exactArea = Area::query()
            ->where('city_id', $city->id)
            ->where('normalized_name', $areaNormalized)
            ->first();

        if ($exactArea) {
            return [
                'status' => 'duplicate',
                'item' => $baseItem + [
                    'area_id' => $exactArea->id,
                    'reason' => 'area_already_exists',
                ],
            ];
        }

        $similarArea = Area::query()
            ->where('city_id', $city->id)
            ->get()
            ->first(fn (Area $area) => $this->isSimilarName($areaNormalized, $area->normalized_name));

        if ($similarArea) {
            return [
                'status' => 'conflict',
                'item' => $baseItem + [
                    'reason' => 'similar_area_name_exists',
                    'existing' => $this->areaConflictPayload($similarArea),
                ],
            ];
        }

        if (isset($plannedAreaKeys[$areaKey])) {
            return [
                'status' => 'planned_duplicate',
                'item' => $baseItem + [
                    'reason' => 'area_already_planned_in_this_dry_run',
                ],
            ];
        }

        return [
            'status' => 'create',
            'item' => $baseItem,
        ];
    }

    private function classifyCsvSubArea(int $rowNumber, array $areaDecision, string $subAreaName, array $plannedSubAreaKeys): array
    {
        $subAreaNormalized = $this->normalizedName($subAreaName);
        $areaId = $areaDecision['item']['area_id'] ?? null;
        $areaKey = $areaDecision['item']['area_key'] ?? null;
        $subAreaKey = ($areaId ?: $areaKey) . '|' . $subAreaNormalized;
        $baseItem = [
            'type' => 'sub_area',
            'row' => $rowNumber,
            'area_id' => $areaId,
            'area_key' => $areaKey,
            'sub_area_name' => $subAreaName,
            'sub_area_normalized_name' => $subAreaNormalized,
            'sub_area_slug' => Str::slug($subAreaName),
            'sub_area_key' => $subAreaKey,
        ];

        $sameSubAreaDifferentParent = SubArea::query()
            ->where('normalized_name', $subAreaNormalized)
            ->when($areaId, fn ($query) => $query->where('area_id', '!=', $areaId))
            ->first();

        if ($sameSubAreaDifferentParent) {
            return [
                'status' => 'conflict',
                'item' => $baseItem + [
                    'reason' => 'same_normalized_sub_area_exists_with_different_parent',
                    'existing' => $this->subAreaConflictPayload($sameSubAreaDifferentParent),
                ],
            ];
        }

        if ($areaId) {
            $exactSubArea = SubArea::query()
                ->where('area_id', $areaId)
                ->where('normalized_name', $subAreaNormalized)
                ->first();

            if ($exactSubArea) {
                return [
                    'status' => 'duplicate',
                    'item' => $baseItem + [
                        'reason' => 'sub_area_already_exists',
                        'sub_area_id' => $exactSubArea->id,
                    ],
                ];
            }

            $similarSubArea = SubArea::query()
                ->where('area_id', $areaId)
                ->get()
                ->first(fn (SubArea $subArea) => $this->isSimilarName($subAreaNormalized, $subArea->normalized_name));

            if ($similarSubArea) {
                return [
                    'status' => 'conflict',
                    'item' => $baseItem + [
                        'reason' => 'similar_sub_area_name_exists',
                        'existing' => $this->subAreaConflictPayload($similarSubArea),
                    ],
                ];
            }
        }

        if (isset($plannedSubAreaKeys[$subAreaKey])) {
            return [
                'status' => 'duplicate',
                'item' => $baseItem + [
                    'reason' => 'sub_area_already_planned_in_this_dry_run',
                ],
            ];
        }

        return [
            'status' => 'create',
            'item' => $baseItem,
        ];
    }

    private function isSimilarName(string $left, ?string $right): bool
    {
        $right = (string) $right;
        if ($left === '' || $right === '' || $left === $right) {
            return false;
        }

        if (abs(strlen($left) - strlen($right)) > 4) {
            return false;
        }

        similar_text($left, $right, $percent);

        return $percent >= 84 || levenshtein($left, $right) <= 2;
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function archivedAgeMeta(Area $area): array
    {
        $archivedAt = $area->archived_at ?? $area->updated_at;
        $daysArchived = $archivedAt
            ? Carbon::parse($archivedAt)->startOfDay()->diffInDays(now()->startOfDay())
            : null;

        return [
            'archived_at' => $archivedAt ? Carbon::parse($archivedAt)->toIso8601String() : null,
            'days_archived' => $daysArchived,
            'archived_label' => $this->formatArchivedAgeLabel($daysArchived),
        ];
    }

    private function attachArchivedAgeMeta(Area $area): Area
    {
        $meta = $this->archivedAgeMeta($area);
        $area->days_archived = $meta['days_archived'];
        $area->archived_label = $meta['archived_label'];

        return $area;
    }

    private function formatArchivedAgeLabel(?int $daysArchived): string
    {
        if ($daysArchived === null) {
            return '-';
        }

        if ($daysArchived === 0) {
            return __('Today');
        }

        if ($daysArchived === 1) {
            return __('1 day ago');
        }

        return __(':days days ago', ['days' => $daysArchived]);
    }

    private function getSimilarExistingArea(string $normalizedName, ?int $cityId, string $city = '', string $state = ''): ?array
    {
        if ($normalizedName === '') {
            return null;
        }

        $query = Area::query()->where('status', 1);

        if ($cityId) {
            $query->where('city_id', $cityId);
        } elseif ($city !== '') {
            $cityName = $this->cleanName($city);
            $stateName = $this->cleanName($state);
            $query->where(function ($scope) use ($cityName, $stateName) {
                $scope->where('city_name', $cityName)->orWhere('city', $cityName);
                if ($stateName !== '') {
                    $scope->where('state', $stateName);
                }
            });
        } else {
            return null;
        }

        foreach ($query->orderBy('id')->get() as $area) {
            if ((string) $area->normalized_name === $normalizedName) {
                continue;
            }

            if ($this->isSimilarName($normalizedName, $area->normalized_name)) {
                return [
                    'id' => (int) $area->id,
                    'name' => (string) $area->name,
                ];
            }
        }

        return null;
    }

    /**
     * @return array{id: int, name: string, area_id: int}|null
     */
    private function getSimilarExistingSubArea(int $areaId, string $normalizedName): ?array
    {
        if ($normalizedName === '' || $areaId <= 0) {
            return null;
        }

        foreach (SubArea::query()
            ->where('area_id', $areaId)
            ->where('status', 1)
            ->orderBy('id')
            ->get() as $subArea) {
            if ((string) $subArea->normalized_name === $normalizedName) {
                continue;
            }

            if ($this->isSimilarName($normalizedName, $subArea->normalized_name)) {
                return [
                    'id' => (int) $subArea->id,
                    'name' => (string) $subArea->name,
                    'area_id' => (int) $subArea->area_id,
                ];
            }
        }

        return null;
    }

    private function areaConflictPayload(Area $area): array
    {
        return [
            'id' => $area->id,
            'name' => $area->name,
            'city_id' => $area->city_id,
            'city' => $area->city_name ?: $area->city,
            'state' => $area->state,
            'country' => $area->country,
        ];
    }

    private function subAreaConflictPayload(SubArea $subArea): array
    {
        return [
            'id' => $subArea->id,
            'name' => $subArea->name,
            'area_id' => $subArea->area_id,
            'area' => $subArea->area?->name,
            'city' => $subArea->area?->city_name ?: $subArea->area?->city,
            'state' => $subArea->area?->state,
            'country' => $subArea->area?->country,
        ];
    }

    public function normalizeAll()
    {
        $mergedAreas = 0;
        $mergedSubAreas = 0;

        DB::transaction(function () use (&$mergedAreas, &$mergedSubAreas) {
            Area::orderBy('id')->get()->each(function (Area $area) {
                $area->city = $this->cleanName($area->city);
                $area->state = $this->cleanName($area->state);
                $area->country = $this->cleanName($area->country);
                $area->name = $this->cleanName($area->name);
                $area->slug = Str::slug($area->name);
                $area->save();
            });

            $groups = Area::all()->groupBy(function (Area $area) {
                $cityKey = $area->city_id ? 'id:' . $area->city_id : 'name:' . mb_strtolower(trim(($area->city_name ?: $area->city) . '|' . $area->state));

                return $cityKey . '|' . mb_strtolower(trim($area->name));
            });
            foreach ($groups as $group) {
                if ($group->count() < 2) {
                    continue;
                }
                $keeper = $group->sortBy('id')->first();
                foreach ($group->where('id', '!=', $keeper->id) as $duplicate) {
                    DB::table('area_listing_property_locations')->where('area_id', $duplicate->id)->update(['area_id' => $keeper->id]);
                    DB::table('area_listing_project_locations')->where('area_id', $duplicate->id)->update(['area_id' => $keeper->id]);
                    SubArea::where('area_id', $duplicate->id)->update(['area_id' => $keeper->id]);
                    $this->logMerge('area', $duplicate->id, $keeper->id);
                    $duplicate->delete();
                    $mergedAreas++;
                }
            }

            SubArea::orderBy('id')->get()->each(function (SubArea $subArea) {
                $subArea->name = $this->cleanName($subArea->name);
                $subArea->slug = Str::slug($subArea->name);
                $subArea->save();
            });

            $subGroups = SubArea::all()->groupBy(fn (SubArea $subArea) => $subArea->area_id . '|' . mb_strtolower($subArea->slug));
            foreach ($subGroups as $group) {
                if ($group->count() < 2) {
                    continue;
                }
                $keeper = $group->sortBy('id')->first();
                foreach ($group->where('id', '!=', $keeper->id) as $duplicate) {
                    DB::table('area_listing_property_locations')->where('sub_area_id', $duplicate->id)->update(['sub_area_id' => $keeper->id]);
                    DB::table('area_listing_project_locations')->where('sub_area_id', $duplicate->id)->update(['sub_area_id' => $keeper->id]);
                    $this->logMerge('sub_area', $duplicate->id, $keeper->id);
                    $duplicate->delete();
                    $mergedSubAreas++;
                }
            }
        });

        return back()->with('success', __("Normalize complete. Merged areas: :areas, merged sub areas: :subareas", [
            'areas' => $mergedAreas,
            'subareas' => $mergedSubAreas,
        ]));
    }

    public function mergePreview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|in:area,sub_area',
            'source_id' => 'required|integer',
            'target_id' => 'required|integer|different:source_id',
        ]);

        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first())->withInput();
        }

        $preview = $this->buildMergePreview(
            $request->input('entity_type'),
            (int) $request->input('source_id'),
            (int) $request->input('target_id')
        );

        if (! empty($preview['error'])) {
            return back()->with('error', $preview['message'])->withInput();
        }

        return back()
            ->with('merge_preview', $preview)
            ->with('success', __('Merge preview ready. Review warnings before confirming.'));
    }

    public function mergeConfirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|in:area,sub_area',
            'source_id' => 'required|integer',
            'target_id' => 'required|integer|different:source_id',
            'preview_sub_areas' => 'required|integer|min:0',
            'preview_properties' => 'required|integer|min:0',
            'preview_projects' => 'required|integer|min:0',
            'accept_changed_counts' => 'nullable|in:1',
        ]);

        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first())->withInput();
        }

        $entityType = $request->input('entity_type');
        $sourceId = (int) $request->input('source_id');
        $targetId = (int) $request->input('target_id');
        $previewCounts = [
            'sub_areas' => (int) $request->input('preview_sub_areas'),
            'properties' => (int) $request->input('preview_properties'),
            'projects' => (int) $request->input('preview_projects'),
        ];

        $livePreview = $this->buildMergePreview($entityType, $sourceId, $targetId);
        if (! empty($livePreview['error'])) {
            return back()->with('error', $livePreview['message']);
        }

        $liveCounts = $livePreview['counts'];
        $countsChanged = $liveCounts !== $previewCounts;
        if ($countsChanged && ! $request->boolean('accept_changed_counts')) {
            $livePreview['counts_changed'] = true;
            $livePreview['preview_counts'] = $previewCounts;

            return back()
                ->with('merge_preview', $livePreview)
                ->with('warning', __('Affected counts changed after preview. Review the live counts and confirm deliberately if you still want to merge.'));
        }

        DB::transaction(function () use ($entityType, $sourceId, $targetId, $livePreview, $liveCounts) {
            if ($entityType === 'area') {
                $this->executeAreaMerge($sourceId, $targetId, $livePreview, $liveCounts);
                return;
            }

            $this->executeSubAreaMerge($sourceId, $targetId, $livePreview, $liveCounts);
        });

        return back()->with('success', __('Merge completed. Source was archived and audit log was created.'));
    }

    public function approveSuggestion(Request $request, int $id)
    {
        $suggestion = $this->pendingSuggestion($id);

        if (! $suggestion) {
            return back()->with('error', __('Pending suggestion not found.'));
        }

        if (! $this->executeApproveSuggestion($suggestion, $request->input('review_note'))) {
            return back()->with('error', __('Could not create master area/sub-area. Ensure city and parent area are set on the suggestion.'));
        }

        return back()->with('success', __('Suggestion approved successfully.'));
    }

    public function rejectSuggestion(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'review_note' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first());
        }

        $suggestion = $this->pendingSuggestion($id);
        if (! $suggestion) {
            return back()->with('error', __('Pending suggestion not found.'));
        }

        $this->executeRejectSuggestion($suggestion, $request->input('review_note'));

        return back()->with('success', __('Suggestion rejected successfully.'));
    }

    public function mergeSuggestion(Request $request, int $id)
    {
        $suggestion = $this->pendingSuggestion($id);
        if (! $suggestion) {
            return back()->with('error', __('Pending suggestion not found.'));
        }

        $rules = $suggestion->type === 'area'
            ? ['merge_area_id' => 'required|exists:area_listing_areas,id']
            : ['merge_sub_area_id' => 'required|exists:area_listing_sub_areas,id'];

        $validator = Validator::make($request->all(), $rules + [
            'review_note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first());
        }

        $targetId = $suggestion->type === 'area'
            ? (int) $request->input('merge_area_id')
            : (int) $request->input('merge_sub_area_id');

        DB::table('area_listing_merge_audits')->insert([
            'entity_type' => $suggestion->type,
            'old_id' => (int) $suggestion->id,
            'new_id' => $targetId,
            'affected_properties' => 0,
            'affected_projects' => 0,
            'admin_user_id' => Auth::id(),
            'snapshot' => json_encode([
                'source' => 'suggestion_merge',
                'suggested_name' => $suggestion->name,
                'review_note' => $request->input('review_note'),
                'merged_at' => now()->toDateTimeString(),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->markSuggestionReviewed($suggestion->id, 'merged', $request->input('review_note'));

        return back()->with('success', __('Suggestion merged with existing record.'));
    }

    private function areaValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'center_lat' => 'nullable|numeric',
            'center_lng' => 'nullable|numeric',
            'radius_meters' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'nullable|in:0,1',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
        ]);
    }

    private function subAreaValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'area_id' => 'required|exists:area_listing_areas,id',
            'name' => 'required|string|max:255',
            'center_lat' => 'nullable|numeric',
            'center_lng' => 'nullable|numeric',
            'radius_meters' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'nullable|in:0,1',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
        ]);
    }

    private function resolveAreaContext(Request $request): array
    {
        $cityId = $request->filled('city_id') ? (int) $request->input('city_id') : null;
        $cityModel = $cityId ? City::find($cityId) : null;

        $cityName = $this->cleanName($request->city);
        $stateName = $this->cleanName($request->state);
        $country = $this->cleanName($request->country ?: 'India');

        if (! $cityModel && $cityName !== '') {
            $cityQuery = City::query()->where('name', $cityName);
            if ($stateName !== '') {
                $cityQuery->where('state', $stateName);
            }
            $cityModel = $cityQuery->first();
        }

        if (! $cityModel && $cityName !== '' && $stateName !== '') {
            $stateModel = $request->filled('state_id')
                ? State::find((int) $request->input('state_id'))
                : null;
            if (! $stateModel) {
                $stateModel = State::firstOrCreate(
                    ['country' => $country, 'slug' => Str::slug($stateName)],
                    ['name' => $stateName, 'status' => true]
                );
            }
            $cityModel = City::firstOrCreate(
                ['state_id' => $stateModel->id, 'slug' => Str::slug($cityName)],
                ['name' => $cityName, 'state' => $stateName, 'country' => $country, 'status' => true]
            );
        }

        if ($cityModel) {
            $cityId = (int) $cityModel->id;
            $cityName = $cityModel->name;
            $stateName = $cityModel->state ?: $stateName;
            $country = $cityModel->country ?: $country;
        }

        return [
            'state_id' => $cityModel?->state_id ?? $request->input('state_id'),
            'city_id' => $cityId,
            'city_name' => $cityName,
            'city' => $cityName,
            'state' => $stateName,
            'country' => $country,
        ];
    }


    private function resolveCityIdForAreaRequest(Request $request): ?int
    {
        if ($request->filled('city_id')) {
            return (int) $request->input('city_id');
        }

        $city = $this->cleanName((string) $request->input('city', ''));
        $state = $this->cleanName((string) $request->input('state', ''));
        if ($city === '') {
            return null;
        }

        $query = DB::table('area_listing_cities')->where('name', $city);
        if ($state !== '') {
            $query->where('state', $state);
        }

        $row = $query->orderBy('id')->first();

        return $row ? (int) $row->id : null;
    }
    private function areaPayload(Request $request): array
    {
        return array_merge($this->resolveAreaContext($request), [
            'name' => $this->cleanName($request->name),
            'slug' => Str::slug($this->cleanName($request->name)),
            'normalized_name' => $this->normalizedName($request->name),
            'center_lat' => $request->input('center_lat'),
            'center_lng' => $request->input('center_lng'),
            'radius_meters' => $request->input('radius_meters'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'status' => true,
            'workflow_status' => 'active',
            'seo_title' => $request->input('seo_title'),
            'seo_description' => $request->input('seo_description'),
        ]);
    }

    private function subAreaPayload(Request $request): array
    {
        $name = $this->cleanName($request->name);
        return [
            'area_id' => $request->area_id,
            'name' => $name,
            'slug' => Str::slug($name),
            'normalized_name' => $this->normalizedName($name),
            'center_lat' => $request->input('center_lat'),
            'center_lng' => $request->input('center_lng'),
            'radius_meters' => $request->input('radius_meters'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'status' => true,
            'workflow_status' => 'active',
            'seo_title' => $request->input('seo_title'),
            'seo_description' => $request->input('seo_description'),
        ];
    }

    private function cleanName($value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', (string) $value));
        return $value === '' ? '' : Str::title(Str::lower($value));
    }

    private function normalizedName($value): string
    {
        return Str::of((string) $value)->lower()->squish()->value();
    }

    private function logMerge(string $entityType, int $oldId, int $newId): void
    {
        $propertyColumn = $entityType === 'area' ? 'area_id' : 'sub_area_id';
        $affectedProperties = DB::table('area_listing_property_locations')->where($propertyColumn, $newId)->count();
        $affectedProjects = DB::table('area_listing_project_locations')->where($propertyColumn, $newId)->count();

        DB::table('area_listing_merge_audits')->insert([
            'entity_type' => $entityType,
            'old_id' => $oldId,
            'new_id' => $newId,
            'affected_properties' => $affectedProperties,
            'affected_projects' => $affectedProjects,
            'admin_user_id' => Auth::id(),
            'snapshot' => json_encode(['merged_at' => now()->toDateTimeString()]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function pendingSuggestion(int $id)
    {
        return DB::table('area_listing_suggestions')
            ->where('id', $id)
            ->where('status', 'pending')
            ->first();
    }

    private function pendingSuggestionsByGroupToken(string $groupToken)
    {
        return DB::table('area_listing_suggestions')
            ->where('group_token', $groupToken)
            ->where('status', 'pending')
            ->orderByRaw("CASE WHEN type = 'area' THEN 0 ELSE 1 END")
            ->get();
    }

    private function buildSuggestionDisplayItems($pendingSuggestions): array
    {
        $items = [];
        $groupedTokens = [];

        foreach ($pendingSuggestions as $row) {
            $token = trim((string) ($row->group_token ?? ''));
            if ($token === '') {
                $items[] = ['kind' => 'single', 'row' => $row];

                continue;
            }

            if (isset($groupedTokens[$token])) {
                continue;
            }

            $groupedTokens[$token] = true;
            $groupRows = $pendingSuggestions->where('group_token', $token);
            $items[] = [
                'kind' => 'group',
                'token' => $token,
                'area' => $groupRows->firstWhere('type', 'area'),
                'sub_area' => $groupRows->firstWhere('type', 'sub_area'),
            ];
        }

        return $items;
    }

    private function executeApproveSuggestion(object $suggestion, ?string $reviewNote = null): bool
    {
        $master = \App\Plugins\AreaListing\Services\AreaListingService::createMasterFromSuggestionRecord($suggestion);
        if (! $master) {
            return false;
        }

        DB::transaction(function () use ($suggestion, $reviewNote) {
            $this->markSuggestionReviewed($suggestion->id, 'approved', $reviewNote);
            if (class_exists(\App\Plugins\AreaListing\Services\AreaListingService::class)) {
                \App\Plugins\AreaListing\Services\AreaListingService::propagateApprovedSuggestion($suggestion);
            }
        });

        return true;
    }

    private function executeRejectSuggestion(object $suggestion, string $reviewNote): void
    {
        $this->markSuggestionReviewed($suggestion->id, 'rejected', $reviewNote);
    }

    private function approveSuggestionGroupByToken(string $groupToken, ?string $reviewNote = null): array
    {
        $approved = 0;
        $skipped = 0;
        $errors = 0;

        $rows = $this->pendingSuggestionsByGroupToken($groupToken);
        if ($rows->isEmpty()) {
            return ['approved' => 0, 'skipped' => 1, 'errors' => 0];
        }

        $areaRow = $rows->firstWhere('type', 'area');
        $subRow = $rows->firstWhere('type', 'sub_area');

        try {
            if ($areaRow) {
                if ($this->executeApproveSuggestion($areaRow, $reviewNote)) {
                    $approved++;
                } else {
                    $errors++;
                }
                if ($subRow) {
                    $areaNorm = $this->normalizedName((string) $areaRow->name);
                    $linkedArea = Area::query()
                        ->when(! empty($areaRow->city), fn ($q) => $q->whereRaw('LOWER(TRIM(COALESCE(NULLIF(city_name, ""), city))) = ?', [mb_strtolower(trim((string) $areaRow->city))]))
                        ->where('normalized_name', $areaNorm)
                        ->orderByDesc('id')
                        ->first();
                    if ($linkedArea) {
                        DB::table('area_listing_suggestions')
                            ->where('id', $subRow->id)
                            ->where('status', 'pending')
                            ->update(['area_id' => $linkedArea->id, 'updated_at' => now()]);
                    }
                    $subRow = $this->pendingSuggestion((int) $subRow->id) ?: $subRow;
                }
            }

            if ($subRow && ($subRow->status ?? '') === 'pending') {
                if ($this->executeApproveSuggestion($subRow, $reviewNote)) {
                    $approved++;
                } else {
                    $errors++;
                }
            }
        } catch (\Throwable $e) {
            $errors++;
        }

        return ['approved' => $approved, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function rejectSuggestionGroupByToken(string $groupToken, string $reviewNote): array
    {
        $rejected = 0;
        $skipped = 0;
        $errors = 0;

        $rows = $this->pendingSuggestionsByGroupToken($groupToken);
        if ($rows->isEmpty()) {
            return ['rejected' => 0, 'skipped' => 1, 'errors' => 0];
        }

        foreach ($rows as $row) {
            try {
                $this->executeRejectSuggestion($row, $reviewNote);
                $rejected++;
            } catch (\Throwable $e) {
                $errors++;
            }
        }

        return ['rejected' => $rejected, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function bulkProcessSuggestions(Request $request, string $action): array
    {
        $ids = collect($request->input('ids', []))->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values();
        $groupTokens = collect($request->input('group_tokens', []))->map(fn ($t) => trim((string) $t))->filter()->unique()->values();
        $note = $request->input('review_note');

        $approved = 0;
        $rejected = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($groupTokens as $token) {
            if ($action === 'approve') {
                $result = $this->approveSuggestionGroupByToken($token, $note);
                $approved += (int) ($result['approved'] ?? 0);
                $skipped += (int) ($result['skipped'] ?? 0);
                $errors += (int) ($result['errors'] ?? 0);
            } else {
                $result = $this->rejectSuggestionGroupByToken($token, (string) $note);
                $rejected += (int) ($result['rejected'] ?? 0);
                $skipped += (int) ($result['skipped'] ?? 0);
                $errors += (int) ($result['errors'] ?? 0);
            }
        }

        foreach ($ids as $id) {
            $row = $this->pendingSuggestion($id);
            if (! $row) {
                $skipped++;
                continue;
            }
            if (! empty($row->group_token) && $groupTokens->contains($row->group_token)) {
                continue;
            }

            try {
                if ($action === 'approve') {
                    if ($this->executeApproveSuggestion($row, $note)) {
                        $approved++;
                    } else {
                        $errors++;
                    }
                } else {
                    $this->executeRejectSuggestion($row, (string) $note);
                    $rejected++;
                }
            } catch (\Throwable $e) {
                $errors++;
            }
        }

        if ($action === 'approve') {
            return ['approved' => $approved, 'skipped' => $skipped, 'errors' => $errors];
        }

        return ['rejected' => $rejected, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function bulkIdsFromRequest(Request $request): array
    {
        $ids = $request->input('ids', $request->input('id', []));

        return collect(is_array($ids) ? $ids : [$ids])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function bulkArchiveAreasByIds(array $ids): array
    {
        $archived = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($ids as $id) {
            try {
                $area = Area::find($id);
                if (! $area) {
                    $skipped++;
                    continue;
                }
                $this->applyAreaArchiveState($area);
                $archived++;
            } catch (\Throwable $e) {
                $errors++;
            }
        }

        return ['archived' => $archived, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function bulkRestoreAreasByIds(array $ids): array
    {
        $restored = 0;
        $errors = 0;

        foreach ($ids as $id) {
            try {
                $area = Area::find($id);
                if (! $area) {
                    continue;
                }
                $this->applyAreaRestoreState($area);
                $restored++;
            } catch (\Throwable $e) {
                $errors++;
            }
        }

        return ['restored' => $restored, 'errors' => $errors];
    }

    private function bulkDeleteAreasByIds(array $ids): array
    {
        $deleted = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($ids as $id) {
            try {
                $area = Area::find($id);
                if (! $area) {
                    $skipped++;
                    continue;
                }
                if ($this->areaIsUsed($area) || SubArea::where('area_id', $area->id)->exists()) {
                    $skipped++;
                    continue;
                }
                $area->delete();
                $deleted++;
            } catch (\Throwable $e) {
                $errors++;
            }
        }

        return ['deleted' => $deleted, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function bulkArchiveSubAreasByIds(array $ids): array
    {
        $archived = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($ids as $id) {
            try {
                $subArea = SubArea::find($id);
                if (! $subArea) {
                    $skipped++;
                    continue;
                }
                $this->applySubAreaArchiveState($subArea);
                $archived++;
            } catch (\Throwable $e) {
                $errors++;
            }
        }

        return ['archived' => $archived, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function bulkRestoreSubAreasByIds(array $ids): array
    {
        $restored = 0;
        $errors = 0;

        foreach ($ids as $id) {
            try {
                $subArea = SubArea::find($id);
                if (! $subArea) {
                    continue;
                }
                if ($subArea->area && AreaWorkflowState::isArchived($subArea->area)) {
                    $this->applyAreaRestoreState($subArea->area);
                }
                $this->applySubAreaRestoreState($subArea);
                $restored++;
            } catch (\Throwable $e) {
                $errors++;
            }
        }

        return ['restored' => $restored, 'errors' => $errors];
    }

    private function bulkDeleteSubAreasByIds(array $ids): array
    {
        $deleted = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($ids as $id) {
            try {
                $subArea = SubArea::find($id);
                if (! $subArea) {
                    $skipped++;
                    continue;
                }
                if ($this->subAreaIsUsed($subArea)) {
                    $skipped++;
                    continue;
                }
                $subArea->delete();
                $deleted++;
            } catch (\Throwable $e) {
                $errors++;
            }
        }

        return ['deleted' => $deleted, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function markSuggestionReviewed(int $id, string $status, ?string $reviewNote = null): void
    {
        DB::table('area_listing_suggestions')->where('id', $id)->update([
            'status' => $status,
            'review_note' => $reviewNote,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function buildMergePreview(string $entityType, int $sourceId, int $targetId): array
    {
        if ($entityType === 'area') {
            $source = Area::find($sourceId);
            $target = Area::find($targetId);

            if (! $source || ! $target) {
                return ['error' => true, 'message' => __('Source or target area was deleted.')];
            }

            if (! $this->isActiveWorkflow($source)) {
                return ['error' => true, 'message' => __('Source area is already archived or inactive.')];
            }

            if (! $this->isActiveWorkflow($target)) {
                return ['error' => true, 'message' => __('Target area is archived or inactive, so merge cannot proceed.')];
            }

            $counts = $this->areaMergeCounts($source->id);

            return [
                'entity_type' => 'area',
                'source' => $this->areaMergePayload($source),
                'target' => $this->areaMergePayload($target),
                'city_state_country' => $this->locationSnapshotFromArea($source),
                'counts' => $counts,
                'warnings' => $this->mergeWarnings(),
            ];
        }

        $source = SubArea::with('area')->find($sourceId);
        $target = SubArea::with('area')->find($targetId);

        if (! $source || ! $target) {
            return ['error' => true, 'message' => __('Source or target sub area was deleted.')];
        }

        if (! $this->isActiveWorkflow($source)) {
            return ['error' => true, 'message' => __('Source sub area is already archived or inactive.')];
        }

        if (! $this->isActiveWorkflow($target)) {
            return ['error' => true, 'message' => __('Target sub area is archived or inactive, so merge cannot proceed.')];
        }

        if (! $source->area || ! $this->isActiveWorkflow($source->area)) {
            return ['error' => true, 'message' => __('Source parent area is archived or missing.')];
        }

        if (! $target->area || ! $this->isActiveWorkflow($target->area)) {
            return ['error' => true, 'message' => __('Target parent area is archived or missing.')];
        }

        $counts = $this->subAreaMergeCounts($source->id);

        return [
            'entity_type' => 'sub_area',
            'source' => $this->subAreaMergePayload($source),
            'target' => $this->subAreaMergePayload($target),
            'city_state_country' => $this->locationSnapshotFromArea($source->area),
            'counts' => $counts,
            'warnings' => $this->mergeWarnings(),
        ];
    }

    private function mergeWarnings(): array
    {
        return [
            __('Source will be archived after merge.'),
            __('An audit log will be created.'),
            __('Undo requires admin restore or manual correction.'),
        ];
    }

    private function executeAreaMerge(int $sourceId, int $targetId, array $preview, array $counts): void
    {
        $source = Area::with('subAreas')->findOrFail($sourceId);
        $target = Area::findOrFail($targetId);
        $sourceSubAreaIds = $source->subAreas->pluck('id')->all();

        foreach (['area_listing_property_locations', 'area_listing_project_locations'] as $table) {
            DB::table($table)
                ->where('area_id', $source->id)
                ->update([
                    'state_id' => $target->state_id,
                    'city_id' => $target->city_id,
                    'area_id' => $target->id,
                    'state' => $target->state,
                    'city' => $target->city_name ?: $target->city,
                    'area_name' => $target->name,
                    'updated_by' => Auth::id(),
                    'updated_at' => now(),
                ]);

            if (! empty($sourceSubAreaIds)) {
                DB::table($table)
                    ->whereIn('sub_area_id', $sourceSubAreaIds)
                    ->update([
                        'sub_area_id' => null,
                        'sub_area_name' => null,
                        'updated_by' => Auth::id(),
                        'updated_at' => now(),
                    ]);
            }
        }

        if (! empty($sourceSubAreaIds)) {
            SubArea::whereIn('id', $sourceSubAreaIds)->update([
                'workflow_status' => 'archived',
                'status' => false,
                'archived_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $source->forceFill(AreaWorkflowState::archivePayload())->save();

        $this->writeMergeAudit('area', $source->id, $target->id, $preview, $counts);
    }

    private function executeSubAreaMerge(int $sourceId, int $targetId, array $preview, array $counts): void
    {
        $source = SubArea::with('area')->findOrFail($sourceId);
        $target = SubArea::with('area')->findOrFail($targetId);
        $targetArea = $target->area;

        foreach (['area_listing_property_locations', 'area_listing_project_locations'] as $table) {
            DB::table($table)
                ->where('sub_area_id', $source->id)
                ->update([
                    'state_id' => $targetArea?->state_id,
                    'city_id' => $targetArea?->city_id,
                    'area_id' => $targetArea?->id,
                    'sub_area_id' => $target->id,
                    'state' => $targetArea?->state,
                    'city' => $targetArea?->city_name ?: $targetArea?->city,
                    'area_name' => $targetArea?->name,
                    'sub_area_name' => $target->name,
                    'updated_by' => Auth::id(),
                    'updated_at' => now(),
                ]);
        }

        $source->forceFill(AreaWorkflowState::archivePayload())->save();

        $this->writeMergeAudit('sub_area', $source->id, $target->id, $preview, $counts);
    }

    private function writeMergeAudit(string $entityType, int $sourceId, int $targetId, array $preview, array $counts): void
    {
        DB::table('area_listing_merge_audits')->insert([
            'entity_type' => $entityType,
            'old_id' => $sourceId,
            'new_id' => $targetId,
            'affected_properties' => $counts['properties'],
            'affected_projects' => $counts['projects'],
            'admin_user_id' => Auth::id(),
            'snapshot' => json_encode([
                'action_type' => 'merge_preview_confirm_archive_source',
                'source_id' => $sourceId,
                'source_name' => $preview['source']['name'] ?? null,
                'target_id' => $targetId,
                'target_name' => $preview['target']['name'] ?? null,
                'counts_at_merge' => [
                    'sub_areas' => $counts['sub_areas'],
                    'properties' => $counts['properties'],
                    'projects' => $counts['projects'],
                ],
                'city_state_country' => $preview['city_state_country'] ?? [],
                'source_snapshot' => $preview['source'] ?? [],
                'target_snapshot' => $preview['target'] ?? [],
                'source_archived' => true,
                'merged_at' => now()->toDateTimeString(),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function areaMergeCounts(int $sourceAreaId): array
    {
        return [
            'sub_areas' => SubArea::where('area_id', $sourceAreaId)->where('workflow_status', 'active')->count(),
            'properties' => DB::table('area_listing_property_locations')->where('area_id', $sourceAreaId)->count(),
            'projects' => DB::table('area_listing_project_locations')->where('area_id', $sourceAreaId)->count(),
        ];
    }

    private function subAreaMergeCounts(int $sourceSubAreaId): array
    {
        return [
            'sub_areas' => 1,
            'properties' => DB::table('area_listing_property_locations')->where('sub_area_id', $sourceSubAreaId)->count(),
            'projects' => DB::table('area_listing_project_locations')->where('sub_area_id', $sourceSubAreaId)->count(),
        ];
    }

    private function areaMergePayload(Area $area): array
    {
        return [
            'id' => $area->id,
            'name' => $area->name,
            'city' => $area->city_name ?: $area->city,
            'state' => $area->state,
            'country' => $area->country,
            'workflow_status' => $area->workflow_status,
        ];
    }

    private function subAreaMergePayload(SubArea $subArea): array
    {
        return [
            'id' => $subArea->id,
            'name' => $subArea->name,
            'area_id' => $subArea->area_id,
            'area_name' => $subArea->area?->name,
            'city' => $subArea->area?->city_name ?: $subArea->area?->city,
            'state' => $subArea->area?->state,
            'country' => $subArea->area?->country,
            'workflow_status' => $subArea->workflow_status,
        ];
    }

    private function locationSnapshotFromArea(?Area $area): array
    {
        return [
            'city' => $area?->city_name ?: $area?->city,
            'state' => $area?->state,
            'country' => $area?->country,
        ];
    }

    private function isActiveWorkflow($model): bool
    {
        return $model && (bool) $model->status && $model->workflow_status === 'active';
    }

    private function areaIsUsed(Area $area): bool
    {
        return DB::table('area_listing_property_locations')->where('area_id', $area->id)->exists()
            || DB::table('area_listing_project_locations')->where('area_id', $area->id)->exists();
    }

    private function subAreaIsUsed(SubArea $subArea): bool
    {
        return DB::table('area_listing_property_locations')->where('sub_area_id', $subArea->id)->exists()
            || DB::table('area_listing_project_locations')->where('sub_area_id', $subArea->id)->exists();
    }

    private function safeSort(?string $sort, array $allowed, string $default): string
    {
        return in_array($sort, $allowed, true) ? $sort : $default;
    }

    private function applyAreaCityFilter($query, string $city, string $state = '', string $country = ''): void
    {
        if ($city === '') {
            return;
        }

        $cityName = $this->cleanName($city);
        $stateName = $this->cleanName($state);
        $countryName = $this->cleanName($country);
        $cityIds = $this->matchingCityIds($cityName, $stateName, $countryName);

        $query->where(function ($filter) use ($cityIds, $cityName, $stateName, $countryName) {
            if ($cityIds->isNotEmpty()) {
                $filter->whereIn('city_id', $cityIds);
            }

            $filter->orWhere(function ($fallback) use ($cityName, $stateName, $countryName) {
                $fallback->whereNull('city_id')
                    ->where(function ($cityQuery) use ($cityName) {
                        $cityQuery->where('city_name', $cityName)
                            ->orWhere('city', $cityName);
                    });

                if ($stateName !== '') {
                    $fallback->where('state', $stateName);
                }

                if ($countryName !== '') {
                    $fallback->where('country', $countryName);
                }
            });
        });
    }

    private function matchingCityIds(string $city, string $state = '', string $country = '')
    {
        if ($city === '') {
            return collect();
        }

        return City::query()
            ->where(function ($query) use ($city) {
                $query->where('name', $city)
                    ->orWhere('slug', Str::slug($city));
            })
            ->when($state !== '', fn ($query) => $query->where('state', $state))
            ->when($country !== '', fn ($query) => $query->where('country', $country))
            ->pluck('id');
    }

    private function mapCsvRow($header, array $row): array
    {
        if (! is_array($header)) {
            return [];
        }

        $mapped = [];
        foreach ($header as $index => $name) {
            $key = Str::of((string) $name)->lower()->replace([' ', '-'], '_')->value();
            $mapped[$key] = $row[$index] ?? null;
        }
        return $mapped;
    }

    public function checkCityDrift()
    {
        return response()->json($this->collectCityDriftReport(20));
    }

    public function syncSnapshotsDryRun()
    {
        $report = $this->collectCityDriftReport(20);
        $previewSample = collect($report['sample'])
            ->where('drift_status', 'snapshot_drift')
            ->values()
            ->take(20)
            ->all();

        return response()->json([
            'preview' => true,
            'missing_city_id_count' => $report['missing_city_id_count'],
            'snapshot_drift_count' => $report['snapshot_drift_count'],
            'total_drift' => $report['total_drift'],
            'would_update_count' => $report['snapshot_drift_count'],
            'sample' => $previewSample,
        ]);
    }

    public function syncSnapshotsExecute()
    {
        $before = $this->collectCityDriftReport(0);

        $updated = DB::update(
            'UPDATE area_listing_areas a
            INNER JOIN area_listing_cities c ON c.id = a.city_id
            SET a.city_name = c.name,
                a.state = c.state,
                a.country = c.country,
                a.updated_at = ?
            WHERE a.city_id IS NOT NULL
              AND (
                LOWER(COALESCE(a.city_name, a.city)) <> LOWER(c.name)
                OR LOWER(a.state) <> LOWER(c.state)
                OR LOWER(a.country) <> LOWER(c.country)
              )',
            [now()]
        );

        return response()->json([
            'updated' => (int) $updated,
            'snapshot_drift_before' => $before['snapshot_drift_count'],
            'missing_city_id_count' => $before['missing_city_id_count'],
        ]);
    }

    /**
     * @return array{missing_city_id_count: int, snapshot_drift_count: int, total_drift: int, sample: array<int, array<string, mixed>>}
     */
    private function collectCityDriftReport(int $sampleLimit = 20): array
    {
        $driftStatusSql = "CASE
            WHEN a.city_id IS NULL THEN 'missing_city_id'
            WHEN LOWER(COALESCE(a.city_name, a.city)) <> LOWER(c.name)
                OR LOWER(a.state) <> LOWER(c.state)
                OR LOWER(a.country) <> LOWER(c.country)
                THEN 'snapshot_drift'
            ELSE 'ok'
        END";

        $rows = DB::table('area_listing_areas as a')
            ->leftJoin('area_listing_cities as c', 'c.id', '=', 'a.city_id')
            ->select([
                'a.id',
                'a.name',
                'a.city_id',
                'a.city_name',
                'a.city',
                'a.state',
                'a.country',
                'a.status',
                'c.name as linked_city',
                'c.state as linked_state',
                'c.country as linked_country',
            ])
            ->selectRaw("{$driftStatusSql} as drift_status")
            ->where(function ($query) {
                $query->whereNull('a.city_id')
                    ->orWhereRaw('LOWER(COALESCE(a.city_name, a.city)) <> LOWER(c.name)')
                    ->orWhereRaw('LOWER(a.state) <> LOWER(c.state)')
                    ->orWhereRaw('LOWER(a.country) <> LOWER(c.country)');
            })
            ->orderBy('a.id')
            ->get();

        $missingCount = 0;
        $snapshotCount = 0;
        foreach ($rows as $row) {
            if ($row->drift_status === 'missing_city_id') {
                $missingCount++;
            } elseif ($row->drift_status === 'snapshot_drift') {
                $snapshotCount++;
            }
        }

        $sample = $rows->take($sampleLimit)->map(function ($row) {
            return $this->formatCityDriftSampleRow($row);
        })->values()->all();

        return [
            'missing_city_id_count' => $missingCount,
            'snapshot_drift_count' => $snapshotCount,
            'total_drift' => $missingCount + $snapshotCount,
            'sample' => $sample,
        ];
    }

    private function formatCityDriftSampleRow(object $row): array
    {
        $currentCity = $row->city_name ?: $row->city;

        return [
            'area_id' => (int) $row->id,
            'area_name' => (string) $row->name,
            'drift_status' => (string) $row->drift_status,
            'current' => [
                'city_name' => (string) ($currentCity ?? ''),
                'state' => (string) ($row->state ?? ''),
                'country' => (string) ($row->country ?? ''),
            ],
            'expected' => [
                'city_name' => (string) ($row->linked_city ?? ''),
                'state' => (string) ($row->linked_state ?? ''),
                'country' => (string) ($row->linked_country ?? ''),
            ],
        ];
    }

    public function repairLocationsDryRun()
    {
        $stats = AreaListingPropertyLocationRepairService::dryRun();
        $sampleIds = $stats['sample_property_ids'] ?? [];

        $sample = [];
        if (! empty($sampleIds)) {
            $sample = Property::query()
                ->whereIn('id', $sampleIds)
                ->orderBy('id')
                ->get(['id', 'title'])
                ->map(fn (Property $property) => [
                    'id' => (int) $property->id,
                    'title' => (string) ($property->title ?? ''),
                ])
                ->values()
                ->all();
        }

        return response()->json([
            'total' => (int) ($stats['scanned_total'] ?? 0),
            'missing' => (int) ($stats['missing_count'] ?? 0),
            'rows_to_create' => (int) ($stats['rows_to_create'] ?? 0),
            'sample' => $sample,
        ]);
    }

    public function repairLocationsExecute()
    {
        $result = AreaListingPropertyLocationRepairService::execute();

        return response()->json([
            'total' => (int) ($result['scanned_total'] ?? 0),
            'missing' => (int) ($result['missing_count'] ?? 0),
            'created' => (int) ($result['created'] ?? 0),
            'errors' => $result['errors'] ?? [],
            'error_count' => count($result['errors'] ?? []),
        ]);
    }
    public function resolveCoordinates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'city_id' => 'nullable|integer',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'location_components' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $locationComponents = json_decode((string) $request->input('location_components', '[]'), true);
        if (! is_array($locationComponents)) {
            $locationComponents = [];
        }

        $data = AreaListingService::resolveNearestFromCoordinates(
            (float) $request->latitude,
            (float) $request->longitude,
            $request->filled('city_id') ? (int) $request->city_id : null,
            (string) $request->input('city', ''),
            (string) $request->input('state', ''),
            (string) $request->input('country', 'India'),
            $locationComponents
        );

        return response()->json([
            'error' => false,
            'message' => $data ? __('Location resolved') : __('No matching area found'),
            'data' => $data,
        ]);
    }
}
