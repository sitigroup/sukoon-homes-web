<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\UserInterest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\ApiResponseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Dedoc\Scramble\Attributes\Group;

#[Group('Personalisation')]
class PersonalisationApiController extends Controller
{
    private function interestPayload(UserInterest $userInterest, int $loggedInUserId): array
    {
        return [
            'user_id' => $loggedInUserId,
            'category_ids' => ! empty($userInterest->category_ids) ? explode(',', $userInterest->category_ids) : '',
            'price_range' => $userInterest->property_type != null ? explode(',', $userInterest->price_range) : '',
            'property_type' => $userInterest->property_type == 0 || $userInterest->property_type == 1
                ? explode(',', $userInterest->property_type)
                : '',
            'outdoor_facilitiy_ids' => ! empty($userInterest->outdoor_facilitiy_ids)
                ? explode(',', $userInterest->outdoor_facilitiy_ids)
                : '',
            'city' => ! empty($userInterest->city) ? $userInterest->city : '',
            'state_id' => $userInterest->state_id ? (int) $userInterest->state_id : null,
            'city_id' => $userInterest->city_id ? (int) $userInterest->city_id : null,
            'area_id' => $userInterest->area_id ? (int) $userInterest->area_id : null,
            'sub_area_id' => $userInterest->sub_area_id ? (int) $userInterest->sub_area_id : null,
            'area_name' => $userInterest->area_name ?? '',
            'sub_area_name' => $userInterest->sub_area_name ?? '',
        ];
    }

    private function applyInterestLocation(UserInterest $userInterest, Request $request): void
    {
        $userInterest->city = $request->filled('city') ? $request->city : '';
        $userInterest->state_id = $request->filled('state_id') ? (int) $request->state_id : null;
        $userInterest->city_id = $request->filled('city_id') ? (int) $request->city_id : null;
        $userInterest->area_id = $request->filled('area_id') ? (int) $request->area_id : null;
        $userInterest->sub_area_id = $request->filled('sub_area_id') ? (int) $request->sub_area_id : null;
        $userInterest->area_name = $request->input('area_name') ?: null;
        $userInterest->sub_area_name = $request->input('sub_area_name') ?: null;

        if ($request->has('sub_area_id') && ! $request->filled('sub_area_id')) {
            $userInterest->sub_area_id = null;
            $userInterest->sub_area_name = null;
        }
    }

    public function getUserPersonalisedInterest(Request $request)
    {
        try {
            $loggedInUserId = Auth::user()->id;
            $data = [];

            $userInterest = UserInterest::where('user_id', $loggedInUserId)->first();
            if (collect($userInterest)->isNotEmpty()) {
                $data = $this->interestPayload($userInterest, $loggedInUserId);
            }

            return response()->json([
                'error' => false,
                'data' => $data,
                'message' => trans('Data Fetched Successfully'),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => trans('Something Went Wrong'),
            ], 500);
        }
    }

    public function storeUserPersonalisedInterest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_ids' => 'nullable|string',
            'outdoor_facilitiy_ids' => 'nullable|string',
            'price_range' => 'nullable|string',
            'city' => 'nullable|string',
            'property_type' => 'nullable|string',
            'state_id' => 'nullable|integer',
            'city_id' => 'nullable|integer',
            'area_id' => 'nullable|integer',
            'sub_area_id' => 'nullable|integer',
            'area_name' => 'nullable|string|max:255',
            'sub_area_name' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            ApiResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();
            $loggedInUserId = Auth::user()->id;

            $userInterest = UserInterest::where('user_id', $loggedInUserId)->first();

            if (collect($userInterest)->isNotEmpty()) {
                $response = ['error' => false, 'message' => trans('Data Updated Successfully')];
            } else {
                $userInterest = new UserInterest();
                $response = ['error' => false, 'message' => trans('Data Submitted Successfully')];
            }

            $userInterest->user_id = $loggedInUserId;
            $userInterest->category_ids = $request->filled('category_ids') ? $request->category_ids : '';
            $userInterest->outdoor_facilitiy_ids = $request->filled('outdoor_facilitiy_ids') ? $request->outdoor_facilitiy_ids : null;
            $userInterest->price_range = $request->filled('price_range') ? $request->price_range : '';
            $userInterest->property_type = $request->filled('property_type') && ($request->property_type == 0 || $request->property_type == 1)
                ? $request->property_type
                : '0,1';
            $this->applyInterestLocation($userInterest, $request);
            $userInterest->save();

            DB::commit();

            $response['data'] = $this->interestPayload($userInterest, $loggedInUserId);
            $response['message'] = trans('Data Fetched Successfully');

            return response()->json($response);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => trans('Something Went Wrong'),
            ], 500);
        }
    }

    public function deleteUserPersonalisedInterest(Request $request)
    {
        try {
            DB::beginTransaction();
            $loggedInUserId = Auth::user()->id;
            UserInterest::where('user_id', $loggedInUserId)->delete();
            DB::commit();

            return response()->json([
                'error' => false,
                'message' => trans('Data Deleted Successfully'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => trans('Something Went Wrong'),
            ], 500);
        }
    }
}
