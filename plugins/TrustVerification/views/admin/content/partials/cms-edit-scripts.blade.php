<script>
    (function () {
        var form = document.getElementById('tv-cms-edit-form');
        var blockType = document.getElementById('tv-cms-live-preview')?.getAttribute('data-block-type') || 'text';
        var flowPreview = document.getElementById('tv-flow-preview');
        var modalBody = document.getElementById('tv-cms-modal-body');
        var rawPre = document.getElementById('tv-cms-raw-preview');

        function val(name) {
            var el = form && form.querySelector('[name="' + name + '"]');
            return el ? el.value : '';
        }

        function updateLivePreview() {
            if (blockType === 'faq') {
                var q = document.getElementById('pv-question');
                var a = document.getElementById('pv-answer');
                if (q) q.textContent = val('question') || '—';
                if (a) a.textContent = val('answer') || '—';
            } else if (blockType === 'email') {
                var sub = document.getElementById('pv-email_subject');
                var html = document.getElementById('pv-body_html');
                if (sub) sub.textContent = val('email_subject') || '—';
                if (html) html.innerHTML = val('body_html') || '—';
            } else if (blockType === 'testimonial') {
                var c = document.getElementById('pv-content');
                if (c) {
                    c.textContent = (val('customer_name') || 'Customer') + ' — ' + (val('review') || '');
                }
            } else {
                var c = document.getElementById('pv-content');
                if (c) {
                    c.textContent = val('content') || val('title') || '—';
                }
            }

            if (rawPre && blockType === 'legal' || blockType === 'json') {
                try {
                    rawPre.textContent = JSON.stringify(JSON.parse(val('content_json') || '{}'), null, 2);
                } catch (e) {
                    rawPre.textContent = val('content_json');
                }
            } else if (rawPre) {
                rawPre.textContent = val('content') || val('question') || val('email_subject') || '—';
            }
        }

        function syncModalPreview() {
            if (!modalBody) return;
            var clone = document.getElementById('tv-cms-live-preview');
            if (clone) modalBody.innerHTML = clone.innerHTML;
        }

        if (form) {
            form.querySelectorAll('.tv-cms-live').forEach(function (el) {
                el.addEventListener('input', updateLivePreview);
            });
            updateLivePreview();
        }

        if (flowPreview) {
            flowPreview.addEventListener('click', function () {
                var modal = document.getElementById('tvCmsPreviewModal');
                if (modal && typeof bootstrap !== 'undefined') {
                    syncModalPreview();
                    new bootstrap.Modal(modal).show();
                } else {
                    document.querySelector('.tv-cms-preview-panel')?.scrollIntoView({ behavior: 'smooth' });
                }
            });
            flowPreview.style.cursor = 'pointer';
            flowPreview.classList.add('is-current');
        }

        document.getElementById('tvCmsPreviewModal')?.addEventListener('show.bs.modal', syncModalPreview);
    })();
</script>
