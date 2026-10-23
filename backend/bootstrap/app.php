<?php

use App\Exceptions\BookingException;
use App\Support\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The Next.js site proxies /admin to Laravel and sends X-Forwarded-Host, so admin
        // links and redirects point at the public site origin instead of Laravel's port.
        $middleware->trustProxies(at: array_filter(array_map('trim', explode(',', (string) (env('TRUSTED_PROXIES') ?: '127.0.0.1,::1')))));
        $middleware->redirectGuestsTo('/admin/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (BookingException $e) {
            return ApiResponse::error($e->getMessage(), $e->errors, $e->status);
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    collect($e->errors())->flatten()->first() ?? 'Please check the form and try again.',
                    $e->errors(),
                    422,
                );
            }
        });
    })->create();
