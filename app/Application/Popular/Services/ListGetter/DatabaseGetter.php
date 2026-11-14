<?php

namespace App\Application\Popular\Services\ListGetter;

use App\Domain\Popular\Interfaces\Repositories\PopularReadRepositoryInterface;
use App\Domain\Popular\Interfaces\SourceGetterInterface;
use App\Domain\Item\Entities\ItemListEntity;
use App\Domain\Item\Enums\ConverterCollectionType;
use App\Domain\Item\Services\ItemListConverter;
use App\Domain\Shared\DTO\PageSelectorDTO;

class DatabaseGetter implements SourceGetterInterface
{
    public function __construct(
        protected PopularReadRepositoryInterface $popularReadRepository,
        protected ItemListConverter $listConverter,
    ) {}

    public function getItems(string $listType, PageSelectorDTO $pageDTO): array
    {
        $list = match ($listType) {
            PopularListGetter::LIST_TYPE_POPULAR => $this->popularReadRepository->getPopular($pageDTO),
            PopularListGetter::LIST_TYPE_NEW => $this->popularReadRepository->getNew($pageDTO),
            default => [],
        };

        $list = $this->listConverter->convertCollectionToArray(
            $list, 
            ConverterCollectionType::Popular,
            $pageDTO->userId
        );
        
        return $list;
    }

    public function getGolden(PageSelectorDTO $pageDTO):  ?ItemListEntity
    {
        $golden = $this->popularReadRepository->getGolden($pageDTO)->first();
        
        if (!$golden)
            return null;
        
        $convertedGolden = $this->listConverter->convertItemToEntity(
            $golden->post,
            $golden,
            $pageDTO->userId,
        );

        return $convertedGolden;
    }
}