<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::get('/', function () {
    $locale = App::getLocale();

    return view('landing', ['locale' => $locale]);
})->name('home')->withoutMiddleware([
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
]);

Route::get('/login', [LoginController::class, 'redirect'])
    ->name('login')
    ->withoutMiddleware([
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
    ]);

Route::get('/logout', [LoginController::class, 'logout'])
    ->name('logout')
    ->withoutMiddleware([
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
    ]);

Route::post('/locale/{locale}', LocaleController::class)
    ->name('locale.switch')
    ->withoutMiddleware([
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
    ]);

Route::view('/offline', 'offline')
    ->name('offline')
    ->withoutMiddleware([
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
    ]);

Route::get('/manifest.webmanifest', function () {
    $manifestPath = public_path('manifest.webmanifest');

    if (! file_exists($manifestPath)) {
        abort(404);
    }

    $contents = file_get_contents($manifestPath);
    $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

    $locale = App::getLocale();
    $direction = $locale === 'ar' ? 'rtl' : 'ltr';

    $data['lang'] = $locale;
    $data['dir'] = $direction;
    $data['name'] = trans('pwa.name');
    $data['short_name'] = trans('pwa.short_name');
    $data['description'] = trans('pwa.description');
    $data['start_url'] = '/?source=pwa&lang='.$locale;
    $data['display_override'] = ['standalone', 'fullscreen'];

    return response()->json($data, 200, ['Content-Type' => 'application/manifest+json']);
})->name('pwa.manifest')->withoutMiddleware([
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
]);

Route::get('/service-worker.js', function () {
    $serviceWorkerPath = public_path('service-worker.js');

    if (! file_exists($serviceWorkerPath)) {
        abort(404);
    }

    return response(
        file_get_contents($serviceWorkerPath),
        200,
        ['Content-Type' => 'application/javascript']
    );
})->name('pwa.worker')->withoutMiddleware([
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
]);

Route::get('/health', fn () => response()->json(['status' => 'ok']))
    ->withoutMiddleware([
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
    ]);
