<?php

namespace App\Plugins\NearbyPlaces\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Plugins\NearbyPlaces\Services\NearbyPlacesService;
use Illuminate\Http\Request;

class NearbyPlacesApiController extends Controller
{
    public function __construct(
        protected NearbyPlacesService $nearbyPlacesService,
    ) {
    }

    public function propertyNearby(Request $request, int $propertyId)
    {
        $payload = $this->nearbyPlacesService->getForProperty($propertyId, false);

        if (isset($payload['error'])) {
            return response()->json([
                'error' => true,
                'message' => $payload['error'],
                'data' => null,
            ], 404);
        }

        return response()->json([
            'error' => false,
            'message' => 'Nearby places fetched successfully',
            'data' => $payload,
        ]);
    }
}
