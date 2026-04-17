<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (\Throwable $e, $request) {
        
			if ($request->wantsJson()) {

				// Model not found (show/update/destroy)
				if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
					return response()->json([
						'success' => false,
						'message' => 'Resource not found'
					], 404);
				}

				// Route not found
				if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
					return response()->json([
						'success' => false,
						'message' => 'Data not found'
					], 404);
				}

				// Validation failed
				if ($e instanceof \Illuminate\Validation\ValidationException) {
					return response()->json([
						'success' => false,
						'message' => 'Validation failed',
						'errors' => $e->errors()
					], 422);
				}

				// Generic error fallback
				return response()->json([
					'success' => false,
					'message' => $e->getMessage()
				], 500);
			}
		});
    })->create();
