<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Services\SubscribeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class PriceController extends Controller
{

    /**
     * @OA\Get(path="/source",
     *     tags={"source"},
     *     summary="Returns user subscriptions",
     *     description="",
     *     operationId="index",
     *     parameters={},
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\Schema(
     *             additionalProperties={
     *                 "type": "integer",
     *                 "format": "int64"
     *             }
     *         )
     *     ),
     *     security={{
     *         "api_key": {}
     *     }}
     * )
     */    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $allSources = $user->with('source')->get();
        return response()->json($allSources);
    }

    /**
     * Create source and subscribe user for it
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
