<?php

namespace App\Infrastructure\Shared;

use App\Domain\Shared\Interfaces\LoggerInterface;
use Illuminate\Support\Facades\Log;
use Override;
use Throwable;

class Logger implements LoggerInterface
{
    protected bool $enable;

    public function __construct()
    {
        $this->enable = config('logging.logger_enable');
    }

    #[Override]
    public function info(string $message, array $data = []): void
    {
        if (!$this->enable) return;    
        Log::info($message, $data);
    }

    #[Override]
    public function error(string $message, array $data = []): void
    {
        if (!$this->enable) return;    
        Log::error($message, $data);
    }

    #[Override]
    public function errorByException(Throwable $e, $data = []): void
    {
        if (!$this->enable) return;

        Log::error($e?->getMessage(), $data);
        Log::error($e?->getCode());
        Log::error($e?->getTraceAsString());
    }

    #[Override]
    public function warning(string $message, array $data = []): void
    {
        if (!$this->enable) return;
        Log::warning($message, $data);
    }    
}
