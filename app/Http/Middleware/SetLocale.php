<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request by applying the preferred locale.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession()) {
            return $next($request);
        }

        $supportedLocales = ['en', 'ar'];

        $sessionLocale = $request->session()->get('locale');
        $queryLocale = $request->query('lang');
        $cookieLocale = Cookie::get('locale');
        $preferredLocale = $request->getPreferredLanguage($supportedLocales);

        $locale = $sessionLocale
            ?: ($queryLocale && in_array($queryLocale, $supportedLocales, true) ? $queryLocale : null)
            ?: ($cookieLocale && in_array($cookieLocale, $supportedLocales, true) ? $cookieLocale : null)
            ?: $preferredLocale
            ?: config('app.locale');

        if ($locale && in_array($locale, $supportedLocales, true)) {
            App::setLocale($locale);
            $request->session()->put('locale', $locale);
            Cookie::queue(Cookie::make('locale', $locale, 60 * 24 * 365));
        }

        return $next($request);
    }
}
