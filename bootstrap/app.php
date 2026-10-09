<?php

use App\Http\Middleware\MustChangePassword;
use App\Http\Middleware\RequireMenuPermission;
use App\Http\Middleware\SetLanguage;
use App\Http\Middleware\ValidateUserSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

/*
 | = Program.cs / UseMesRequestPipeline: khai báo pipeline middleware.
 | Thứ tự trong nhóm "web" của Laravel: cookie -> session -> CSRF -> (SetLanguage) -> route.
 */
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [SetLanguage::class]);

        // Tên ngắn dùng trong routes/web.php
        $middleware->alias([
            'mes.session' => ValidateUserSession::class,
            'mes.must-change-password' => MustChangePassword::class,
            'mes.permission' => RequireMenuPermission::class,
        ]);

        // Chưa đăng nhập -> trang đăng nhập (= CookieAuthenticationOptions.LoginPath).
        $middleware->redirectGuestsTo(fn () => route('account.login'));
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
