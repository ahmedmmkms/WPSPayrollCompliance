<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('landing.title') }}</title>
    <style>
        body {
            font-family: "Inter", "Cairo", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: #0f172a;
            color: #e2e8f0;
            text-align: center;
            padding: 2rem;
        }
        main {
            max-width: 32rem;
        }
        h1 {
            font-size: clamp(2rem, 6vw, 3rem);
            margin-bottom: 1rem;
        }
        p {
            font-size: 1.1rem;
            line-height: 1.7;
        }
    </style>
</head>
<body>
<main>
    <h1>{{ __('landing.headline') }}</h1>
    <p>{{ app()->getLocale() === 'ar' ? 'أنت حالياً غير متصل بالإنترنت. سنعيد المحاولة تلقائياً عند عودة الاتصال.' : 'You are currently offline. We will automatically retry once a connection is restored.' }}</p>
</main>
</body>
</html>
