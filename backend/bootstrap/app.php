<?php

use App\Http\Middleware\PrivateApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('api', PrivateApiResponse::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['current_password', 'password', 'password_confirmation', 'refresh_token']);
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            if ($request->is('api/*')) {
                $response->headers->set('Cache-Control', 'private, no-store');
                $response->headers->set('Pragma', 'no-cache');
                $response->headers->set('Referrer-Policy', 'no-referrer');
            }

            return $response;
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
