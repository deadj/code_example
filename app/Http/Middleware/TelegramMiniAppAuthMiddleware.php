<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SergiX44\Nutgram\Nutgram;
use Symfony\Component\HttpFoundation\Response;

class TelegramMiniAppAuthMiddleware
{
    public function __construct(
        protected Nutgram $bot,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $initData = $request->header('X-Telegram-Init-Data');
        if (!$initData)
            return response()->json(['error' => 'Missing X-Telegram-Init-Data header'], 401);

        $webAppData = $this->bot->validateWebAppData($initData);
        if (!$webAppData) 
            return response()->json(['error' => 'Invalid Telegram signature'], 403);

        $request->attributes->set('telegram_user', $webAppData->user);

        return $next($request);
    }
}
