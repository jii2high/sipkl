<?php

use App\Exceptions\DocumentIssuanceHeld;
use App\Exceptions\InvalidTransition;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Transisi status yang ditolak tabel transisi: 409 (bentrok status) atau
        // 422 (pasangan tidak dikenal), selalu berpesan Bahasa Indonesia (T0.3).
        $exceptions->render(function (InvalidTransition $exception, Request $request) {
            $status = $exception->httpStatus();
            $message = $exception->getMessage();

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], $status);
            }

            return response()->view('errors.domain', [
                'status' => $status,
                'message' => $message,
            ], $status);
        });

        // Penerbitan surat ditahan karena TTD Kepsek belum ada (BR-F12-2).
        $exceptions->render(function (DocumentIssuanceHeld $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 409);
            }

            return response()->view('errors.domain', [
                'status' => 409,
                'message' => $exception->getMessage(),
            ], 409);
        });
    })
    ->registered(function (Application $app): void {
        // Berkas bahasa Bahasa Indonesia ada di resources/lang, bukan base /lang (TRD.md §3.2).
        $app->useLangPath($app->basePath('resources/lang'));
    })
    ->create();
