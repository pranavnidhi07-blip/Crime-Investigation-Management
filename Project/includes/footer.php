            </div>
        </main>
    </div>
</div>
<div class="spinner-overlay" id="spinner" hidden>
    <div class="spinner"></div>
</div>
<script>
(function () {
    function bindModalControls() {
        document.querySelectorAll('[data-open-modal]').forEach(function (button) {
            if (button.dataset.cimsBound === '1') return;
            button.dataset.cimsBound = '1';
            button.addEventListener('click', function () {
                var id = this.getAttribute('data-open-modal');
                var modal = id ? document.getElementById(id) : null;
                if (modal) modal.classList.add('open');
            });
        });

        document.querySelectorAll('[data-close-modal]').forEach(function (button) {
            if (button.dataset.cimsBound === '1') return;
            button.dataset.cimsBound = '1';
            button.addEventListener('click', function () {
                var modal = this.closest('.modal');
                if (modal) modal.classList.remove('open');
            });
        });

        document.querySelectorAll('.modal').forEach(function (modal) {
            if (modal.dataset.cimsBound === '1') return;
            modal.dataset.cimsBound = '1';
            modal.addEventListener('click', function (event) {
                if (event.target === this) this.classList.remove('open');
            });
        });

        if (new URLSearchParams(window.location.search).get('open') === '1') {
            var first = document.querySelector('.modal');
            if (first) first.classList.add('open');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindModalControls, { once: true });
    } else {
        bindModalControls();
    }
})();
</script>
<script src="<?= h(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
