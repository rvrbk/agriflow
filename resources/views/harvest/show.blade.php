<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Harvest {{ $harvest['batch_uuid'] }}</title>
    <style>
        :root {
            --ink: #1f2a1d;
            --muted: #4e5f4f;
            --line: #ccd8c7;
            --paper: #ffffff;
            --bg: #f6f8f2;
            --accent: #2f6e4a;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: var(--ink);
            background: radial-gradient(circle at top right, #eef5e2 0%, var(--bg) 42%);
        }

        .container {
            max-width: 900px;
            margin: 24px auto;
            padding: 0 16px;
        }

        .card {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 20px;
        }

        h1 {
            margin: 0;
            font-size: 24px;
        }

        .subtitle {
            margin: 8px 0 20px;
            color: var(--muted);
            font-size: 14px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }

        .item {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px 12px;
            background: #fcfffa;
        }

        .label {
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
        }

        .value {
            word-break: break-word;
            font-size: 14px;
        }

        .chip {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            background: #d6e9d6;
            color: var(--accent);
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
<div class="container">
    <article class="card">
        <h1>Harvest Details</h1>
        <p class="subtitle">Batch {{ $harvest['batch_uuid'] }}</p>

        <div class="grid">
            <div class="item">
                <div class="label">Product</div>
                <div class="value">{{ $harvest['product_name'] ?: '-' }}</div>
            </div>
            <div class="item">
                <div class="label">Product UUID</div>
                <div class="value">{{ $harvest['product_uuid'] ?: '-' }}</div>
            </div>
            <div class="item">
                <div class="label">Warehouse</div>
                <div class="value">{{ $harvest['warehouse_name'] ?: '-' }}</div>
            </div>
            <div class="item">
                <div class="label">Warehouse UUID</div>
                <div class="value">{{ $harvest['warehouse_uuid'] ?: '-' }}</div>
            </div>
            <div class="item">
                <div class="label">Corporation</div>
                <div class="value">{{ $harvest['corporation_name'] ?: '-' }}</div>
            </div>
            <div class="item">
                <div class="label">Corporation UUID</div>
                <div class="value">{{ $harvest['corporation_uuid'] ?: '-' }}</div>
            </div>
            <div class="item">
                <div class="label">Quantity</div>
                <div class="value">{{ $harvest['quantity'] }}</div>
            </div>
            <div class="item">
                <div class="label">Location</div>
                <div class="value">{{ $harvest['location'] ?: '-' }}</div>
            </div>
            <div class="item">
                <div class="label">Available On</div>
                <div class="value">{{ $harvest['available_on'] ?: '-' }}</div>
            </div>
            <div class="item">
                <div class="label">Harvested On</div>
                <div class="value">{{ $harvest['harvested_on'] ?: '-' }}</div>
            </div>
            <div class="item">
                <div class="label">Expires On</div>
                <div class="value">{{ $harvest['expires_on'] ?: '-' }}</div>
            </div>
            <div class="item">
                <div class="label">Quality</div>
                <div class="value">
                    @if($harvest['quality'])
                        <span class="chip">{{ $harvest['quality'] }}</span>
                    @else
                        -
                    @endif
                </div>
            </div>
            <div class="item">
                <div class="label">QR Code</div>
                <div class="value">{{ $harvest['qr_code'] ?: '-' }}</div>
            </div>
            <div class="item">
                <div class="label">QR Payload</div>
                <div class="value">{{ $harvest['qr_payload'] ?: '-' }}</div>
            </div>
        </div>
    </article>
</div>
</body>
</html>
