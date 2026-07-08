<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} — {{ $site_name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: linear-gradient(160deg, #f0f4f8 0%, #e2e8f0 100%);
            color: #1e293b;
        }
        .ag-maintenance {
            max-width: 28rem;
            width: 100%;
            text-align: center;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            padding: 2rem 1.5rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }
        .ag-maintenance__badge {
            display: inline-block;
            margin-bottom: 1rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: #fef3c7;
            border: 1px solid #fcd34d;
            color: #92400e;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.04em;
        }
        .ag-maintenance__title {
            margin: 0 0 0.75rem;
            font-size: 1.35rem;
            font-weight: 800;
        }
        .ag-maintenance__message {
            margin: 0;
            font-size: 0.9375rem;
            line-height: 1.55;
            color: #475569;
        }
        .ag-maintenance__site {
            margin-top: 1.25rem;
            font-size: 0.8125rem;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="ag-maintenance">
        <span class="ag-maintenance__badge">{{ __('sales_channels.maintenance.badge') }}</span>
        <h1 class="ag-maintenance__title">{{ $title }}</h1>
        <p class="ag-maintenance__message">{{ $message }}</p>
        <p class="ag-maintenance__site">{{ $site_name }}</p>
    </div>
</body>
</html>
