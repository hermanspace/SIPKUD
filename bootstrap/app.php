<?php

use App\Http\Middleware\TenantMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Proxy tepercaya di depan aplikasi (Nginx Proxy Manager/Cloudflare).
        // Default '*' karena app hanya menerima trafik dari jaringan Docker internal.
        // Batasi via TRUSTED_PROXIES (daftar IP/CIDR dipisah koma) bila diperlukan.
        $trustedProxies = env('TRUSTED_PROXIES', '*');
        $middleware->trustProxies(
            at: $trustedProxies === '*' ? '*' : array_map('trim', explode(',', $trustedProxies))
        );

        $middleware->alias([
            'tenant' => TenantMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
