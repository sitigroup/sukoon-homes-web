<!DOCTYPE html>
<html>
<body style="font-family:Arial,sans-serif;color:#181818;line-height:1.5;">
    <h2 style="margin:0 0 12px;">Verification request received</h2>
    <p>Hello,</p>
    @if(!empty($cmsBodyHtml))
        {!! $cmsBodyHtml !!}
    @else
    <p>We received your Sukoon Homes verification request.</p>
    @endif
    <ul>
        <li><strong>Order:</strong> {{ $order->order_number }}</li>
        <li><strong>Package:</strong> {{ $order->package?->name }}</li>
        <li><strong>Subject:</strong> {{ $order->subject?->full_name }}</li>
        <li><strong>Amount:</strong> ₹{{ number_format($order->amount) }}</li>
        <li><strong>Payment:</strong> {{ ucfirst($order->payment_status) }}</li>
    </ul>
  @if($order->payment_status !== 'paid')
    <p>If you have not paid online yet, our team may contact you to complete payment before we start checks.</p>
  @else
    <p>Payment is confirmed. Our Barmer team will begin checks and email your PDF report within {{ $order->package?->delivery_hours ?? 72 }} hours.</p>
  @endif
    <p>Track status: <a href="https://homes.sukoon.group/my-verification-orders/">My verification orders</a></p>
    <p style="color:#666;font-size:12px;">Sukoon Homes — Barmer</p>
</body>
</html>
