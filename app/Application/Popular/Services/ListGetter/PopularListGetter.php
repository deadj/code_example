<?php

namespace App\Application\Popular\Services\ListGetter;

use App\Domain\Shared\DTO\PageSelectorDTO;
use App\Domain\Shared\Interfaces\LoggerInterface;
use App\Domain\Shared\Interfaces\Repositories\CacheRepositoryInterface;

class PopularListGetter
{
    public const string LIST_TYPE_POPULAR = "popular";
    public const string LIST_TYPE_NEW = "new";    
    public const string POPULAR_LIST_SOURCE_DB = "database";
    public const string POPULAR_LIST_SOURCE_CACHE = "cache";

    protected string $source;

    public function __construct(
        protected DatabaseGetter $databaseGetter,
        protected CacheGetter $cacheGetter,
        protected CacheRepositoryInterface $cacheRepository,
        protected LoggerInterface $logger,
        ?string $source = null,
    ) {
        $this->source = $source ?? $this->getListSource();
    }   

    public function getListForPopular(PageSelectorDTO $pageDTO): array
    {
        $list = $this->getList(static::LIST_TYPE_POPULAR, $pageDTO);
        $pageDTO->limit = 1;
        $this->addGoldenToList($list, $pageDTO);
        return $list;
    }

    public function getListForNew(PageSelectorDTO $pageDTO): array
    {
        $list = $this->getList(static::LIST_TYPE_NEW, $pageDTO);
        return $list;
    }

    protected function getList(string $listType, PageSelectorDTO $pageDTO): array
    {
        try {
            $popular = match ($this->source) {
                static::POPULAR_LIST_SOURCE_DB => $this->databaseGetter->getItems($listType, $pageDTO),
                static::POPULAR_LIST_SOURCE_CACHE => $this->cacheGetter->getItems($listType, $pageDTO),
                default => [],
            };

            if ($this->source == static::POPULAR_LIST_SOURCE_CACHE && count($popular) === 0)
                $popular = $this->databaseGetter->getItems($listType, $pageDTO);

            if (count($popular) === 0)
                return [];

            return $popular; 
        } catch (\Throwable $e) {
            $this->logger->errorByException($e, [
                'page' => $pageDTO->page,
                'limit' => $pageDTO->limit,
                'user_id' => $pageDTO->userId,
            ]);
            throw $e;
        }
    }

    protected function getListSource(): string
    {
        $cacheConnected = $this->cacheRepository->checkConnection();
 
        return ($cacheConnected)
            ? static::POPULAR_LIST_SOURCE_CACHE        
            : static::POPULAR_LIST_SOURCE_DB;
    }

    protected function addGoldenToList(array &$list, PageSelectorDTO $pageDTO): void
    {
        $golden = $this->databaseGetter->getGolden($pageDTO);

        if (count($list) > 0 && $golden)
            array_unshift($list, $golden);
    }    
}