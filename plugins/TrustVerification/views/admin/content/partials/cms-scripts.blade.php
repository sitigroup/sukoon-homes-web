<script>
    document.querySelectorAll('.tv-cms-toggle-preview').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.getAttribute('data-target'));
            if (!target) return;
            var open = target.classList.toggle('d-none');
            btn.setAttribute('aria-expanded', open ? 'false' : 'true');
            btn.textContent = open ? 'Preview' : 'Hide';
        });
    });
</script>
