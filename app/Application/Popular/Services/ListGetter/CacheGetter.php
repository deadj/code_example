<?php

namespace App\Application\Popular\Services\ListGetter;

use App\Domain\Popular\Interfaces\Repositories\PopularReadRepositoryInterface;
use App\Domain\Popular\Interfaces\SourceGetterInterface;
use App\Application\Shared\Enums\CacheKey;
use App\Application\Popular\Services\Cache\PopularCacheService;
use App\Domain\Item\Services\ItemListConverter;
use App\Domain\Shared\DTO\PageSelectorDTO;
use App\Domain\Shared\Interfaces\Repositories\CacheRepositoryInterface;

class CacheGetter implements SourceGetterInterface
{
    public function __construct(
        protected PopularCacheService $cacheService,
        protected ItemListConverter $listConverter,
        protected PopularReadRepositoryInterface $popularReadRepository,
        protected CacheRepositoryInterface $cacheRepository,
    ) {}

    public function getItems(string $listType, PageSelectorDTO $pageDTO): array
    {   
        if (is_null($cacheKey = $this->getCacheListName($listType))) return [];
        
        $this->cacheRepository->setKey($cacheKey->value);
        
        if (is_null($list = $this->getCacheList($cacheKey, $pageDTO->page))) return [];

        $listWithUserAdditional = $this->addAdditionalInfoToList($list, $pageDTO->userId);
        return $listWithUserAdditional;
    }    

    protected function getCacheListName(string $listType): ?CacheKey
    {
        return match ($listType) {
            PopularListGetter::LIST_TYPE_POPULAR => CacheKey::PopularList,
            PopularListGetter::LIST_TYPE_NEW => CacheKey::NewList,
            default => null,
        };
    }

    protected function getCacheList(CacheKey $cacheKey, int $page): ?array
    {
        $itemsInList = (int) config('popular.items_in_list_count');
        
        while (true) {
            $itemsCountInCache = $this->cacheRepository->getListLength();
            
            if ($itemsCountInCache > 0) {
                $rangeStart = ($page - 1) * $itemsInList;
                $rangeEnd = $page * $itemsInList - 1;

                $items = $this->cacheRepository->getRangeFromList($rangeStart, $rangeEnd);

                if (count($items) === 0) {
                    $addedStatus = $this->cacheService->addItemsToCache(
                        $cacheKey, 
                        new PageSelectorDTO(page: $page)
                    );
                } else {
                    return $items;
                }
            } else {
                $addedStatus = $this->cacheService->updateWholeCacheList(
                    key: $cacheKey,
                    isSchedule: false,
                );
            }

            if (!$addedStatus)
                return null;
        }
    }

    protected function addAdditionalInfoToList(array $list, int $userId): array
    {
        $postIds = array_column($list, 'post_id');
        $items = $this->popularReadRepository->getForCacheAdditional($postIds, $userId);

        foreach ($list as &$popular) {
            $itemById = $items->where('post_id', $popular['post_id'])->first();
            
            $popular['golden'] = false;
            $popular['user_is_voted'] = $itemById->is_voted;
            $popular['user_is_owner'] = $itemById->user_is_owner;
            $popular['user_is_replaced'] = $itemById->user_replaced;
        } 

        $listWithUserAdditional = $this->listConverter->convertCacheListToArray($list, $userId);
        return $listWithUserAdditional;        
    }
}