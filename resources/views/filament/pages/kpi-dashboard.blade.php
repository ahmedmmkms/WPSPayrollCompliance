@php
    $locale = app()->getLocale();
    $direction = in_array($locale, ['ar']) ? 'rtl' : 'ltr';
    $usesArabicDigits = $direction === 'rtl' ? '1' : '0';
@endphp

<x-filament-panels::page>
    <div
        data-kpi-dashboard
        data-throughput-endpoint="{{ route('tenant.kpi.throughput') }}"
        data-exceptions-endpoint="{{ route('tenant.kpi.exceptions') }}"
        data-locale="{{ $locale }}"
        data-direction="{{ $direction }}"
        data-uses-arabic-digits="{{ $usesArabicDigits }}"
        data-updated-template="{{ __('dashboard.kpi.updated_at_label', ['time' => '{time}']) }}"
        data-empty-state="{{ __('dashboard.kpi.empty_state') }}"
    >
        <div class="grid gap-6">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('dashboard.kpi.cards.throughput.title') }}
                            </h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('dashboard.kpi.cards.throughput.subtitle') }}
                            </p>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400" data-updated-throughput></div>
                    </div>
                    <div class="mt-4">
                        <canvas data-chart="throughput" class="w-full" height="200"></canvas>
                    </div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('dashboard.kpi.cards.exceptions.title') }}
                            </h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('dashboard.kpi.cards.exceptions.subtitle') }}
                            </p>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400" data-updated-exceptions></div>
                    </div>
                    <div class="mt-4">
                        <canvas data-chart="exceptions" class="w-full" height="200"></canvas>
                    </div>
                    <dl class="mt-6 space-y-3" data-snapshot></dl>
                    <div class="mt-4 flex items-center justify-between rounded-lg bg-rose-50 px-3 py-2 text-sm font-medium text-rose-600 dark:bg-rose-500/10 dark:text-rose-300" data-sla>
                        <span>{{ __('dashboard.kpi.sla_heading') }}</span>
                        <span data-sla-count>0</span>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-dashed border-gray-300 p-6 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400 hidden" data-empty-wrapper>
                {{ __('dashboard.kpi.empty_state') }}
            </div>
        </div>
    </div>

    @vite('resources/js/filament/kpi-dashboard.js')
</x-filament-panels::page>
