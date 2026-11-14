<?php

namespace App\Application\Push\Services\Handlers;

use App\Domain\Push\Interfaces\PushHandlerInterface;
use App\Domain\Shared\Interfaces\LoggerInterface;
use App\Domain\User\Interfaces\Repositories\UserReadRepositoryInterface;

abstract class AbstractPushHandler implements PushHandlerInterface
{
    public function __construct(
        protected LoggerInterface $logger,
        protected UserReadRepositoryInterface $userReadRepository,
    ) {}

    protected function getRandomMessage(array $messages): string
    {
        $messageKey = array_rand($messages);
        return $messages[$messageKey];
    }
}