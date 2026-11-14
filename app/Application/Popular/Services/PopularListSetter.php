<?php

namespace App\Application\Popular\Services;

use App\Domain\Popular\DTO\CacheChangerActionDTO;
use App\Domain\Popular\DTO\CacheChangerHandlerDTO;
use App\Domain\Popular\Interfaces\Repositories\PopularReadRepositoryInterface;
use App\Application\Shared\Enums\CacheKey;
use App\Application\Popular\Services\Cache\PopularCacheService;
use App\Domain\Shared\Interfaces\Repositories\CacheRepositoryInterface;

class PopularListSetter
{
    protected array $listKeys;

    public function __construct(
        protected CacheRepositoryInterface $cacheRepository,
        protected PopularCacheService $popularCacheService,
        protected PopularReadRepositoryInterface $popularReadRepository,
        ?array $listKeys = null,
    ) {
        $this->listKeys = $listKeys ?? CacheKey::getPublicScreensKeys();
    }

    public function changeScore(int $postId, bool $status): void
    {
        $action = function (CacheChangerActionDTO $dto) {
            $dto->currentItem['score'] += $dto->status ? 1 : -1;
            $this->cacheRepository->changeListItemByIndex($dto->index, json_encode($dto->currentItem));                
        };
        
        $this->handleList(new CacheChangerHandlerDTO(
            postId: $postId,
            status: $status,
            action: $action,
        ));
    }

    public function changeReplaced(int $postId, bool $status): void
    {
        $action = function (CacheChangerActionDTO $dto) {
            $dto->currentItem['replacement_count'] += $dto->status ? 1 : -1;
            $this->cacheRepository->changeListItemByIndex($dto->index, json_encode($dto->currentItem));                
        };
        
        $this->handleList(new CacheChangerHandlerDTO(
            postId: $postId,
            status: $status,
            action: $action,
        ));
    }    

    public function changePublic(int $postId, bool $status): void
    {
        if ($status)
            return;

        $action = function (CacheChangerActionDTO $dto) {
            $this->cacheRepository->removeItemFromList(json_encode($dto->currentItem));
        };

        $this->handleList(new CacheChangerHandlerDTO(
            postId: $postId,
            status: $status,
            action: $action,
        ));            
    }

    protected function handleList(CacheChangerHandlerDTO $handlerDTO): void
    {
        foreach ($this->listKeys as $key) {
            $items = $this->cacheRepository->setKey($key)->getList();
    
            foreach ($items as $index => $item) {

                if ($item['post_id'] == $handlerDTO->postId) {
                    $actionDTO = new CacheChangerActionDTO(
                        allItems: $items,
                        currentItem: $item,
                        index: $index,
                        status: $handlerDTO->status,
                        cacheKey: $key,
                    );

                    ($handlerDTO->action)($actionDTO);
                    
                    break;
                }
            }   
        }
    }
}