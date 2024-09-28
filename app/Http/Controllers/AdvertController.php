<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateSubscriptionRequest;
use App\Services\SubscribeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Advert;

class AdvertController extends Controller
{
    public function __construct(private readonly SubscribeService $subscribeService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $allRecs = $user->adverts()->get();
        return response()->json($allRecs);
    }

    /**
     * Create advert record and subscribe user for it
     * @param CreateSubscriptionRequest $request
     * @return JsonResponse
     */
    public function store(CreateSubscriptionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->subscribeService->subscribe($request->user(), $validated);
        return response()->json($result);
    }

    /**
     * Delete advert subscription
     * @param Request $request
     * @param Advert $advert
     * @return JsonResponse
     */
    public function destroy(Advert $advert, Request $request): JsonResponse
    {
        return response()->json($this->subscribeService->removeSubscription($request->user(), $advert));
    }
}
