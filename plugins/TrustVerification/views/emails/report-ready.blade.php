<!DOCTYPE html>
<html>
<body style="font-family:Arial,sans-serif;color:#181818;line-height:1.5;">
    <h2 style="margin:0 0 12px;">Your verification report is ready</h2>
    <p>Hello,</p>
    @if(!empty($cmsBodyHtml))
        {!! $cmsBodyHtml !!}
    @else
    <p>Your Sukoon Homes verification report for order <strong>{{ $order->order_number }}</strong> is ready.</p>
    @endif
    <ul>
        <li><strong>Package:</strong> {{ $order->package?->name }}</li>
        <li><strong>Subject:</strong> {{ $order->subject?->full_name }}</li>
        @if($order->report?->risk_level)
            <li><strong>Risk level:</strong> {{ ucfirst($order->report->risk_level) }}</li>
        @endif
    </ul>
    @if($reportUrl)
        <p><a href="{{ $reportUrl }}" style="display:inline-block;background:#181818;color:#fff;padding:10px 16px;text-decoration:none;border-radius:6px;">Download report</a></p>
    @else
        <p>Log in to <a href="https://homes.sukoon.group/my-verification-orders/">My verification orders</a> to download your PDF.</p>
    @endif
    <p style="color:#666;font-size:12px;">Sukoon Homes — Barmer</p>
</body>
</html>
