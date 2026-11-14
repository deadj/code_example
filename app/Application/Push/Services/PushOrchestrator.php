<?php

namespace App\Application\Push\Services;

use App\Application\Push\Services\Handlers\DailyTelegramHandler;
use App\Domain\Push\Enums\PushType;
use App\Application\Push\Services\Handlers\MorningHandler;
use App\Application\Push\Services\Handlers\TopUpdateHandler;
use App\Application\Push\Services\Handlers\WeeklyHandler;

class PushOrchestrator
{
    public function handle(PushType $pushType): void
    {
        $handlerClass = match ($pushType) {
            PushType::Morning => MorningHandler::class,
            PushType::Weekly => WeeklyHandler::class,
            PushType::TopUpdate => TopUpdateHandler::class,
            PushType::DailyTelegram => DailyTelegramHandler::class,
            default => null,
        };

        if (!$handlerClass)
            return;

        $handler = app()->make($handlerClass);
        $handler->handle();
    }
}