@php($landing = trans('landing'))
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0ea5e9">
    <meta name="description" content="{{ $landing['meta']['description'] }}">
    <title>{{ $landing['title'] }}</title>
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            color-scheme: light dark;
            --bg-surface: #020617;
            --bg-gradient-start: #0f172a;
            --bg-gradient-end: #020617;
            --fg-primary: #f8fafc;
            --fg-muted: rgba(226, 232, 240, 0.85);
            --accent: #38bdf8;
            --accent-strong: #0ea5e9;
            --card-bg: rgba(15, 23, 42, 0.7);
            --card-border: rgba(148, 163, 184, 0.18);
            --focus-ring: rgba(56, 189, 248, 0.7);
            --shadow-lg: 0 20px 45px rgba(8, 47, 73, 0.45);
            --max-content-width: min(1120px, 92vw);
        }

        [dir='rtl'] {
            font-family: "Cairo", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            background: radial-gradient(circle at top, var(--bg-gradient-start), var(--bg-gradient-end) 65%);
            font-family: "Inter", "Cairo", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--fg-primary);
        }

        body {
            display: flex;
            flex-direction: column;
        }

        .layout {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
        }

        .site-header {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1.25rem 0;
        }

        .site-header__content {
            width: var(--max-content-width);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .brand {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            font-size: 1.05rem;
            letter-spacing: 0.02em;
            color: var(--fg-primary);
            text-decoration: none;
        }

        .brand__mark {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.75rem;
            background: linear-gradient(145deg, rgba(56, 189, 248, 0.28), rgba(14, 165, 233, 0.8));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--fg-primary);
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.25);
        }

        nav.site-nav {
            margin-inline-start: auto;
            display: none;
            gap: 1.5rem;
        }

        nav.site-nav a {
            color: var(--fg-muted);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
        }

        nav.site-nav a:hover,
        nav.site-nav a:focus-visible {
            color: var(--fg-primary);
        }

        .locale-switcher {
            display: inline-flex;
            align-items: center;
            margin-inline-start: auto;
        }

        .locale-switcher button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            padding: 0.45rem 0.95rem;
            border-radius: 999px;
            background: rgba(56, 189, 248, 0.12);
            border: 1px solid rgba(56, 189, 248, 0.25);
            color: var(--fg-primary);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
        }

        main {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            gap: 4rem;
            padding: 2.5rem 1.25rem 3rem;
        }

        .hero {
            width: var(--max-content-width);
            margin: 0 auto;
            display: grid;
            gap: 2.5rem;
        }

        .hero__content {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .hero__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.4rem 0.9rem;
            border-radius: 999px;
            background: rgba(56, 189, 248, 0.16);
            color: var(--accent);
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .hero__headline {
            font-size: clamp(2.2rem, 4.8vw, 3.5rem);
            line-height: 1.1;
            margin: 0;
        }

        .hero__subheadline {
            font-size: clamp(1.05rem, 2.2vw, 1.35rem);
            line-height: 1.6;
            color: var(--fg-muted);
            margin: 0;
            max-width: 48ch;
        }

        .hero__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.9rem;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            padding: 0.85rem 1.75rem;
            border-radius: 999px;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-decoration: none;
            cursor: pointer;
            transition: transform 150ms ease, background 150ms ease, border-color 150ms ease;
            border: 1px solid transparent;
        }

        .button--primary {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.92), rgba(56, 189, 248, 0.8));
            color: var(--fg-primary);
            box-shadow: var(--shadow-lg);
        }

        .button--ghost {
            background: rgba(56, 189, 248, 0.14);
            border-color: rgba(56, 189, 248, 0.35);
            color: var(--accent);
        }

        .button:hover {
            transform: translateY(-1px);
            background: rgba(56, 189, 248, 0.18);
        }

        .button--primary:hover {
            background: linear-gradient(135deg, rgba(14, 165, 233, 1), rgba(56, 189, 248, 0.95));
        }

        .button:focus-visible,
        .locale-switcher button:focus-visible {
            outline: 3px solid var(--focus-ring);
            outline-offset: 2px;
        }

        .hero__stats {
            display: grid;
            gap: 1rem;
        }

        .stat-card {
            border-radius: 1.1rem;
            padding: 1.5rem;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(18px);
        }

        .stat-card__value {
            font-size: clamp(1.8rem, 3vw, 2.4rem);
            font-weight: 700;
            margin: 0;
        }

        .stat-card__label {
            margin: 0.25rem 0 0;
            font-size: 0.95rem;
            letter-spacing: 0.02em;
            color: var(--fg-primary);
        }

        .stat-card__caption {
            margin: 0.75rem 0 0;
            font-size: 0.85rem;
            color: var(--fg-muted);
        }

        .features {
            width: var(--max-content-width);
            margin: 0 auto;
            display: grid;
            gap: 1.5rem;
        }

        .features__list {
            display: grid;
            gap: 1.25rem;
        }

        .feature-card {
            padding: 1.75rem;
            border-radius: 1rem;
            background: rgba(15, 23, 42, 0.65);
            border: 1px solid rgba(148, 163, 184, 0.18);
            backdrop-filter: blur(16px);
        }

        .feature-card h3 {
            margin: 0 0 0.6rem;
            font-size: 1.2rem;
        }

        .feature-card p {
            margin: 0;
            color: var(--fg-muted);
            line-height: 1.6;
        }

        .cta-panel {
            width: var(--max-content-width);
            margin: 0 auto;
            padding: 2rem;
            border-radius: 1.4rem;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.18), rgba(56, 189, 248, 0.08));
            border: 1px solid rgba(56, 189, 248, 0.25);
            display: grid;
            gap: 1.1rem;
            text-align: center;
        }

        .cta-panel h2 {
            margin: 0;
            font-size: clamp(1.8rem, 3.4vw, 2.4rem);
        }

        .cta-panel p {
            margin: 0 auto;
            max-width: 56ch;
            color: var(--fg-muted);
            line-height: 1.6;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.55rem 1.25rem;
            border-radius: 999px;
            background: rgba(56, 189, 248, 0.16);
            color: var(--accent);
            font-weight: 600;
            letter-spacing: 0.06em;
            font-size: 0.85rem;
        }

        .badge span {
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.8rem;
            color: var(--fg-primary);
        }

        footer {
            padding: 2rem 1.25rem 2.5rem;
            display: flex;
            justify-content: center;
            color: var(--fg-muted);
            font-size: 0.85rem;
        }

        .footer__content {
            width: var(--max-content-width);
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .made-by {
            margin: 0;
        }

        .footer-links {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: var(--fg-muted);
            text-decoration: none;
        }

        .footer-links a:hover,
        .footer-links a:focus-visible {
            color: var(--fg-primary);
        }

        @media (min-width: 768px) {
            nav.site-nav {
                display: flex;
            }

            .hero {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                align-items: center;
            }

            .hero__stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .features__list {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .hero__stats {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 767px) {
            .site-header__content {
                flex-wrap: wrap;
                justify-content: space-between;
            }

            .locale-switcher {
                margin-inline-start: 0;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 1ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0ms !important;
                scroll-behavior: auto !important;
            }
        }

        [dir='rtl'] nav.site-nav {
            margin-inline-start: 0;
            margin-inline-end: auto;
        }

        [dir='rtl'] .hero__actions {
            flex-direction: row-reverse;
        }

        [dir='rtl'] .features__list,
        [dir='rtl'] .hero__stats {
            direction: rtl;
        }
    </style>
</head>
<body>
<div class="layout">
    <header class="site-header">
        <div class="site-header__content">
            <a class="brand" href="{{ route('home') }}">
                <span class="brand__mark">W</span>
                <span>{{ $landing['title'] }}</span>
            </a>
            <nav class="site-nav" aria-label="Primary">
                <a href="https://wpspayrollcompliance.alwaysdata.net/health" target="_blank" rel="noopener">
                    {{ $landing['nav']['status'] }}
                </a>
                <a href="https://github.com/ammk/WPSPayrollCompliance/blob/main/docs/runbook.md" target="_blank" rel="noopener">
                    {{ $landing['nav']['docs'] }}
                </a>
            </nav>
            <form method="POST" action="{{ route('locale.switch', $locale === 'ar' ? 'en' : 'ar') }}" class="locale-switcher">
                @csrf
                <button type="submit" aria-label="{{ $landing['locale_switcher']['label'] }}">
                    {{ $locale === 'ar' ? $landing['locale_switcher']['english'] : $landing['locale_switcher']['arabic'] }}
                </button>
            </form>
        </div>
    </header>

    <main>
        <section class="hero" id="hero">
            <div class="hero__content">
                <span class="hero__eyebrow">{{ $landing['hero']['eyebrow'] }}</span>
                <h1 class="hero__headline">{{ $landing['hero']['headline'] }}</h1>
                <p class="hero__subheadline">{{ $landing['hero']['subheadline'] }}</p>
                <div class="hero__actions">
                    <a class="button button--primary" href="{{ route('login') }}">{{ $landing['hero']['primary_cta'] }}</a>
                    <a class="button button--ghost" href="https://wpspayrollcompliance.alwaysdata.net/health" target="_blank" rel="noopener">{{ $landing['hero']['secondary_cta'] }}</a>
                </div>
                <div class="badge">
                    {{ $landing['badge']['online'] }}
                    <span>{{ $landing['badge']['timestamp_prefix'] }} {{ now('UTC')->format('Y-m-d H:i') }} UTC</span>
                </div>
            </div>
            <div class="hero__stats" role="list">
                @foreach ($landing['stats'] as $stat)
                    <article class="stat-card" role="listitem">
                        <p class="stat-card__value">{{ $stat['value'] }}</p>
                        <p class="stat-card__label">{{ $stat['label'] }}</p>
                        <p class="stat-card__caption">{{ $stat['caption'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="features" id="features">
            <h2>{{ $landing['features']['title'] }}</h2>
            <div class="features__list">
                @foreach ($landing['features']['items'] as $feature)
                    <article class="feature-card">
                        <h3>{{ $feature['title'] }}</h3>
                        <p>{{ $feature['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="cta-panel" id="cta">
            <h2>{{ $landing['cta']['title'] }}</h2>
            <p>{{ $landing['cta']['description'] }}</p>
            <div class="hero__actions" style="justify-content: center;">
                <a class="button button--primary" href="{{ route('login') }}">{{ $landing['cta']['primary'] }}</a>
                <a class="button button--ghost" href="https://github.com/ammk/WPSPayrollCompliance/blob/main/docs/runbook.md" target="_blank" rel="noopener">{{ $landing['cta']['secondary'] }}</a>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer__content">
            <p class="made-by">&copy; {{ now()->year }} WPS Payroll Compliance</p>
            <div class="footer-links">
                <a href="mailto:support@wpspayroll.com">support@wpspayroll.com</a>
                <a href="https://github.com/ammk/WPSPayrollCompliance" target="_blank" rel="noopener">GitHub</a>
            </div>
        </div>
    </footer>
</div>
</body>
</html>
