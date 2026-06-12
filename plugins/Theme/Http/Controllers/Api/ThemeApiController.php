<?php

namespace App\Plugins\Theme\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Plugins\Theme\Services\ThemeService;

class ThemeApiController extends Controller
{
    public function publicTheme()
    {
        return response()->json(ThemeService::publicApiResponse())
            ->header('Cache-Control', 'public, max-age=300');
    }
}
