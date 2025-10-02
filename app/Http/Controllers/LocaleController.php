<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    /**
     * Update the preferred locale for the current session.
     */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $supported = ['en', 'ar'];

        abort_unless(in_array($locale, $supported, true), 404);

        $request->session()->put('locale', $locale);
        Cookie::queue(Cookie::make('locale', $locale, 60 * 24 * 365));

        return back(fallback: route('home'));
    }
}
