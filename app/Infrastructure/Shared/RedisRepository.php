<?php

namespace App\Infrastructure\Shared;

use App\Domain\Shared\Enums\ListDirection;
use App\Domain\Shared\Interfaces\Repositories\CacheRepositoryInterface;
use Illuminate\Support\Facades\Redis;
use Override;

class RedisRepository implements CacheRepositoryInterface
{
    public const string LIST_DIRECTION_LEFT = "left";
    public const string LIST_DIRECTION_RIGHT = "right";

    protected ?string $key;

    public function __construct(
        ?string $key = null,
    ) {
        $this->key = $key;      
    }

    public function setKey(string $key): static
    {
        $this->key = $key;
        return $this;
    }

    #[Override]
    public function checkConnection(): bool
    {
        $response = Redis::ping();
        return (string) $response === 'PONG';
    }

    #[Override]
    public function setString(string $string): void
    {
        Redis::set($this->key, $string);
    }

    #[Override]
    public function updateList(array $items): void
    {
        Redis::transaction(function ($redis) use ($items) {
            $redis->del($this->key);
            $redis->rpush($this->key, ...$items);
        });        
    }

    #[Override]
    public function addItemsToList(array $items, ?ListDirection $direction = null): void
    {
        $direction = $direction ?? ListDirection::Right;

        Redis::transaction(function ($redis) use ($items, $direction) {
            if ($direction === ListDirection::Left)
                $redis->lpush($this->key, ...$items);
            if ($direction === ListDirection::Right)
                $redis->rpush($this->key, ...$items);
        });      
    }

    #[Override]
    public function changeListItemByIndex(int $index, string $item)
    {
        Redis::transaction(function ($redis) use ($index, $item) {
            $redis->lset($this->key, $index, $item);
        });
    }

    #[Override]
    public function getListLength(): int
    {
        return Redis::llen($this->key);
    }

    #[Override]
    public function getList(): array
    {
        return $this->getRangeFromList(0, -1);
    }    

    #[Override]
    public function getRangeFromList(int $start, int $end): array
    {
        $items = Redis::lrange($this->key, $start, $end);
        $items = array_map(fn($item) => json_decode($item, true), $items);
        return $items;  
    }

    #[Override]
    public function getString(): ?string
    {
        return Redis::get($this->key);
    }

    #[Override]
    public function getArrayFromJson(): ?array
    {
        $string = $this->getString();
        return is_null($string) ? null : json_decode($string, true);
    }

    #[Override]
    public function removeItemFromList(string $item): void
    {
        Redis::transaction(function ($redis) use ($item) {
            $redis->lrem($this->key, 1, $item);
        });
    }
}