@php
    $webBase = rtrim((string) env('WEB_URL', 'https://homes.sukoon.group'), '/');
    $tvReturn = $webBase . '/payment/trust-verification-complete?lang=en';
@endphp
<script>
    (function () {
        const urlParams = new URLSearchParams(window.location.search);
        const trxref = urlParams.get('trxref');
        const reference = urlParams.get('reference') || urlParams.get('referenceId') || urlParams.get('link_id');
        const txStatus = (urlParams.get('txStatus') || urlParams.get('link_status') || '').toUpperCase();
        const gateway = @json($gateway ?? 'unknown');
        let status = 'success';
        if (gateway === 'cashfree') {
            const paid = ['SUCCESS', 'PAID', 'PARTIALLY_PAID'];
            const failed = ['FAILED', 'CANCELLED', 'CANCELED', 'EXPIRED'];
            if (txStatus && failed.includes(txStatus)) status = 'failed';
            else if (txStatus && !paid.includes(txStatus) && txStatus !== '') status = 'failed';
        }
        const payload = { status, reference: reference || 'payment', trxref, gateway };

        const webReturn = @json($tvReturn);
        const openerTargets = [@json(config('app.url')), @json($webBase)];

        if (window.opener && !window.opener.closed) {
            openerTargets.forEach(function (origin) {
                try { window.opener.postMessage(payload, origin); } catch (e) {}
            });
            window.close();
            return;
        }

        if (gateway === 'cashfree' && urlParams.get('link_id')) {
            window.location.replace(webReturn + '&' + urlParams.toString());
            return;
        }

        window.location.replace(@json($webUrl ?? $webBase));
    })();
</script>