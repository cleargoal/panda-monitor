<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Services\SubscribeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $allRecs = $user->with('adverts')->get();
        return response()->json($allRecs);
    }

    /**
     * Create price record and subscribe user for it
     * @param Request $request
     * @param SubscribeService $subscribeService
     * @return JsonResponse
     */
    public function store(Request $request, SubscribeService $subscribeService): JsonResponse
    {
        $result = $subscribeService->subscribe($request->user(), $request->all());
        return response()->json($result);
    }

}
