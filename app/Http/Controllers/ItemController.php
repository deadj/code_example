<?php

namespace App\Http\Controllers;

use App\Application\Item\UseCases\GetItemUseCase;
use App\Application\Item\UseCases\GetReplacedItemUseCase;
use App\Infrastructure\Domain\Item\Models\Post;
use App\Http\Resources\Item\ReadyItemResource;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

class ItemController extends Controller
{
    public function getItem(
        Post $post,
        GetItemUseCase $useCase,
    ): JsonResponse|ReadyItemResource
    {
        try {
            $item = $useCase->execute($post, auth()->id());
            return new ReadyItemResource($item, null);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], $e->getCode());
        } 
    }

    public function getReplacedItem(
        Post $post,
        GetReplacedItemUseCase $useCase,
    ): JsonResponse|ReadyItemResource
    {
        try {
            $item = $useCase->execute($post, auth()->id());
            return new ReadyItemResource($item, null);
        } catch (AuthenticationException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], $e->getCode());
        } 
    }
}
