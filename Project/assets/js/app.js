(function () {
  const clearStaleAuthValues = () => {
    const stale = new Set(['kevin.m@example.com', 'Admin@123']);
    document.querySelectorAll('form input').forEach((input) => {
      const value = (input.value || '').trim();
      if (stale.has(value)) {
        input.value = '';
      }
    });
  };

  window.addEventListener('DOMContentLoaded', clearStaleAuthValues);

  const spinner = document.getElementById('spinner');
  const hideSpinner = () => {
    if (spinner) spinner.hidden = true;
  };
  hideSpinner();
  window.addEventListener('load', hideSpinner, { once: true });
  window.addEventListener('error', hideSpinner);
  window.addEventListener('unhandledrejection', hideSpinner);

  const menuBtn = document.getElementById('menuBtn');
  const sidebar = document.getElementById('sidebar');
  if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', () => sidebar.classList.toggle('open'));
  }

  document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(btn.getAttribute('data-toggle-password'));
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.textContent = show ? 'Hide' : 'Show';
    });
  });

  document.querySelectorAll('[data-open-modal]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const modal = document.getElementById(btn.getAttribute('data-open-modal'));
      if (modal) modal.classList.add('open');
    });
  });

  document.querySelectorAll('[data-close-modal]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const modal = btn.closest('.modal');
      if (modal) modal.classList.remove('open');
    });
  });

  document.querySelectorAll('.modal').forEach((modal) => {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) modal.classList.remove('open');
    });
  });

  if (new URLSearchParams(location.search).get('open') === '1') {
    const first = document.querySelector('.modal');
    if (first) first.classList.add('open');
  }

  document.querySelectorAll('form[data-loading]').forEach((form) => {
    form.addEventListener('submit', () => {
      if (spinner) spinner.hidden = false;
    });
  });

  document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (e) => {
      if (!confirm(form.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  document.querySelectorAll('[data-count]').forEach((el) => {
    const target = Number(el.getAttribute('data-count') || 0);
    const start = performance.now();
    const dur = 600;
    const tick = (now) => {
      const p = Math.min(1, (now - start) / dur);
      el.textContent = String(Math.round(target * p));
      if (p < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  });

  const statusNode = document.getElementById('statusData');
  const canvas = document.getElementById('statusChart');
  if (statusNode && canvas && window.Chart) {
    const rows = JSON.parse(statusNode.textContent || '[]');
    new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: rows.map((r) => r.status),
        datasets: [{
          data: rows.map((r) => Number(r.total)),
          backgroundColor: ['#fb923c', '#a78bfa', '#34d399'],
          borderWidth: 0,
        }],
      },
      options: {
        plugins: { legend: { labels: { color: '#e8eefc' } } },
      },
    });
  }
})();
