<?php

use App\Http\Middleware\EnsureActiveStaff;
use App\Http\Middleware\EnsureStaffRole;
use App\Http\Middleware\PreventStaffPageCaching;
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
        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO,
        );
        $middleware->redirectGuestsTo(fn (Request $request): string => route('staff.login'));
        $middleware->redirectUsersTo(fn (Request $request): string => route('staff.home'));
        $middleware->alias([
            'staff.active' => EnsureActiveStaff::class,
            'staff.role' => EnsureStaffRole::class,
            'staff.no-cache' => PreventStaffPageCaching::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*', 'bookings') || $request->routeIs('booking.key', 'booking.availability', 'booking.month') || $request->expectsJson(),
        );
    })->create();
