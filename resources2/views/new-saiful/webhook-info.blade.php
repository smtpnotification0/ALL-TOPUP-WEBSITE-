<!DOCTYPE html>
<html>
<head>
    <title>NEW SAIFUL Webhook Info</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 40px; }
        .box { background: #fff; padding: 20px; border-radius: 8px; width: 500px; }
        .label { font-weight: bold; margin-top: 15px; }
        .value {
            background: #eee;
            padding: 10px;
            border-radius: 4px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="box">
        <h2>NEW SAIFUL Webhook Configuration</h2>

        <div class="label">Webhook URL</div>
        <div class="value">
            {{ url('/api/new-saiful/order/receive') }}
        </div>

        <div class="label">Secret Key</div>
        <div class="value">
            {{ config('services.new_saiful.secret') }}
        </div>
    </div>
</body>
</html>
