<?php

namespace App\Domain\Item\Services;

use App\Domain\Item\Entities\ItemListEntity;
use App\Infrastructure\Domain\Popular\Models\Popular;
use App\Domain\Item\DTO\ItemTitleDTO;
use App\Domain\Item\Enums\ConverterCollectionType;
use App\Infrastructure\Domain\Item\Models\Post;
use Illuminate\Database\Eloquent\Collection;

class ItemListConverter 
{
    public function __construct(
        protected ItemTitleGenerator $titleGenerator,
    ) {}

    public function convertCollectionToArray(
        Collection $collection, 
        ConverterCollectionType $collectionType,
        ?int $userId = null,
    ): array
    {
        $array = [];

        foreach ($collection as $item) {
            $post = $collectionType == ConverterCollectionType::Post ? $item : $item->post;
            $popular = $collectionType == ConverterCollectionType::Post ? $item->popular : $item;

            $array[] = $this->convertItemToEntity($post, $popular, $userId);
        }

        return $array;
    }

    public function convertCacheListToArray(
        array $list,
        ?int $userId = null,
    ): array
    {
        $array = [];

        foreach ($list as $item) {
            $array[] = $this->convertCacheItemToEntity($item, $userId);
        }

        return $array;
    }


    public function convertItemToEntity(
        Post $post,
        ?Popular $popular,
        ?int $userId = null,
    ): ItemListEntity
    {
        $item = new ItemListEntity;
        $item->setPost(
            postId: $post->id,
            post: $post->original_text,
            answer: $post->answer?->text ?? "",
            replacedCount: $post->replacement_count,
            userIsOwner: $post->owner_id === $userId,
            createdAt: $post->created_at,            
        );

        if ($popular) 
            $item->setPopular(
                score: $popular->score,
                publicAvailable: true,
                public: $popular->public,
                golden: $popular->golden,
                userIsVoted: $popular->is_voted,
                userIsReplaced: $popular->user_replaced,
            );

        $item->setTitle($this->titleGenerator->getForListItem(new ItemTitleDTO(
            public: $popular?->public ?? false,
            golden: $popular?->golden ?? false,
            authUserId: $userId,
            postUserId: $post->owner_id,
        )));

        return $item;
    }


    public function convertCacheItemToEntity(
        array $item,
        ?int $userId = null,
    ): ItemListEntity
    {
        $entity = new ItemListEntity;
        $entity->setPost(
            postId: $item['post_id'],
            post: $item['post'],
            answer: $item['answer'],
            replacedCount: $item['replacement_count'],
            userIsOwner: $item['user_is_owner'],
        )->setPopular(
            score: $item['score'],
            publicAvailable: true,
            public: true,
            golden: $item['golden'],
            userIsVoted: !$item['golden'] && $item['user_is_voted'],
            userIsReplaced: $item['user_is_replaced']
        )->setTitle(
             $this->titleGenerator->getForListItem(new itemTitleDTO(
                public: true,
                golden: $item['golden'],
                authUserId: $userId,
                postUserId: $item['user_id'],
            ))              
        );

        return $entity;
    }
}