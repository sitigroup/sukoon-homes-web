<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $badge->badge_number }} — {{ $badge->displayTitle() }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; margin: 40px; }
        .card { border: 2px solid #111827; border-radius: 12px; padding: 32px; max-width: 640px; }
        .brand { font-size: 14px; letter-spacing: .12em; text-transform: uppercase; color: #6b7280; }
        h1 { font-size: 26px; margin: 8px 0 4px; }
        .number { font-size: 18px; font-weight: 700; margin-bottom: 24px; }
        .meta { font-size: 13px; line-height: 1.6; }
        .seal { margin-top: 28px; font-size: 12px; color: #374151; }
        .disclaimer { margin-top: 32px; font-size: 11px; color: #6b7280; max-width: 640px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">Sukoon Homes · Trust Verification</div>
        <h1>{{ $badge->displayTitle() }}</h1>
        <div class="number">{{ $badge->badge_number }}</div>
        <div class="meta">
            <div><strong>Order:</strong> {{ $orderNumber }}</div>
            <div><strong>Issued:</strong> {{ $issuedLabel }}</div>
            <div><strong>Valid until:</strong> {{ $expiresLabel }}</div>
        </div>
        <div class="seal">This certificate confirms completion of Sukoon verification for the linked order. It does not guarantee future conduct or legal status.</div>
    </div>
    <p class="disclaimer">Verification reports are informational only. Revoked or expired badges are void. No private documents are included in this certificate.</p>
</body>
</html>
