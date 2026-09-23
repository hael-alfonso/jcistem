(function () {
  const defaults = {
    font: { family: 'Inter, system-ui, sans-serif', size: 11 },
    color: '#738095',
  };

  if (window.Chart) {
    Chart.defaults.font.family = defaults.font.family;
    Chart.defaults.font.size = defaults.font.size;
    Chart.defaults.color = defaults.color;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 8;
    Chart.defaults.plugins.legend.position = 'bottom';
    Chart.defaults.responsive = true;
    Chart.defaults.maintainAspectRatio = false;
  }

  const palette = ['#0097D7', '#1F4789', '#57BCBC', '#EFC40F', '#130F2D', '#D66A5F'];

  function makeChart(canvas) {
    const type = canvas.dataset.chart;
    let payload = {};
    try { payload = JSON.parse(canvas.dataset.payload || '{}'); } catch (e) { payload = {}; }
    const ctx = canvas.getContext('2d');
    const colors = payload.colors || palette;

    if (type === 'doughnut') {
      return new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: payload.labels,
          datasets: [{ data: payload.values, backgroundColor: colors, borderWidth: 0, hoverOffset: 6 }],
        },
        options: {
          cutout: '62%',
          plugins: { tooltip: { callbacks: { label: (c) => ` ${c.label}: ${c.parsed}` } } },
        },
      });
    }

    if (type === 'hbar') {
      return new Chart(ctx, {
        type: 'bar',
        data: {
          labels: (payload.labels || []).map((l) => l.replace('JCI Carmona ', '')),
          datasets: [{
            data: payload.values,
            backgroundColor: '#0097D7',
            borderRadius: 8,
            barThickness: 16,
          }],
        },
        options: {
          indexAxis: 'y',
          plugins: { legend: { display: false } },
          scales: {
            x: { max: 100, grid: { color: '#eef2f5' }, ticks: { callback: (v) => v + '%' } },
            y: { grid: { display: false } },
          },
        },
      });
    }

    if (type === 'budget') {
      return new Chart(ctx, {
        type: 'bar',
        data: {
          labels: (payload.labels || []).map((l) => l.replace('JCI Carmona ', '')),
          datasets: [
            { label: 'Used', data: payload.used, backgroundColor: '#0097D7', borderRadius: 6, barPercentage: 0.7 },
            { label: 'Remaining', data: payload.remaining, backgroundColor: '#57BCBC', borderRadius: 6, barPercentage: 0.7 },
          ],
        },
        options: {
          plugins: {
            tooltip: {
              callbacks: {
                label: (c) => ` ${c.dataset.label}: ₱${Number(c.parsed.y || 0).toLocaleString()}`,
              },
            },
          },
          scales: {
            x: { stacked: true, grid: { display: false } },
            y: { stacked: true, grid: { color: '#eef2f5' }, ticks: { callback: (v) => '₱' + Number(v).toLocaleString() } },
          },
        },
      });
    }

    if (type === 'alloc') {
      return new Chart(ctx, {
        type: 'bar',
        data: {
          labels: (payload.labels || []).map((l) => l.replace('JCI Carmona ', '')),
          datasets: [
            { label: 'Allocated', data: payload.allocated, backgroundColor: '#1F4789', borderRadius: 6 },
            { label: 'Used', data: payload.used, backgroundColor: '#0097D7', borderRadius: 6 },
          ],
        },
        options: {
          plugins: {
            tooltip: { callbacks: { label: (c) => ` ${c.dataset.label}: ₱${Number(c.parsed.y || 0).toLocaleString()}` } },
          },
          scales: {
            x: { ticks: { maxRotation: 40, minRotation: 0, autoSkip: false, font: { size: 9 } }, grid: { display: false } },
            y: { grid: { color: '#eef2f5' }, ticks: { callback: (v) => '₱' + Number(v).toLocaleString() } },
          },
        },
      });
    }

    if (type === 'bar') {
      return new Chart(ctx, {
        type: 'bar',
        data: {
          labels: payload.labels,
          datasets: [{ data: payload.values, backgroundColor: colors, borderRadius: 8, barPercentage: 0.55 }],
        },
        options: {
          plugins: { legend: { display: false } },
          scales: {
            x: { grid: { display: false } },
            y: { grid: { color: '#eef2f5' }, ticks: { callback: (v) => '₱' + Number(v).toLocaleString() } },
          },
        },
      });
    }

    return null;
  }

  document.querySelectorAll('canvas[data-chart]').forEach(makeChart);

  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const toggle = document.getElementById('mobileToggle');
  const setMenu = (open) => {
    sidebar?.classList.toggle('open', open);
    overlay?.classList.toggle('show', open);
    document.body.classList.toggle('menu-open', open);
  };
  toggle?.addEventListener('click', () => setMenu(!sidebar.classList.contains('open')));
  overlay?.addEventListener('click', () => setMenu(false));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setMenu(false); });

  const search = document.getElementById('projectSearch');
  search?.addEventListener('input', () => {
    const q = search.value.toLowerCase();
    document.querySelectorAll('.project-card').forEach((c) => {
      c.style.display = c.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
  });

  document.querySelectorAll('[data-tab]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const tab = btn.dataset.tab;
      document.querySelectorAll('[data-tab]').forEach((b) => b.classList.toggle('active', b.dataset.tab === tab));
      document.querySelectorAll('[data-panel]').forEach((p) => {
        p.hidden = p.dataset.panel !== tab;
      });
    });
  });

  function toast(message, type) {
    const el = document.createElement('div');
    el.className = `toast ${type || 'success'}`;
    el.innerHTML = `<strong>${type === 'info' ? 'Notice' : 'Saved'}</strong><span>${message}</span>`;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 2800);
  }

  function modal(title, body) {
    const wrap = document.createElement('div');
    wrap.className = 'modal-backdrop';
    wrap.innerHTML = `<div class="modal" role="dialog"><div class="modal-head"><div><h3>${title}</h3></div><button class="icon-btn" data-close type="button" aria-label="Close">×</button></div><div class="modal-body">${body}</div><div class="modal-foot"><button class="btn btn-secondary" type="button" data-close>Close</button></div></div>`;
    document.body.appendChild(wrap);
    wrap.querySelectorAll('[data-close]').forEach((b) => b.addEventListener('click', () => wrap.remove()));
    wrap.addEventListener('click', (e) => { if (e.target === wrap) wrap.remove(); });
  }

  document.querySelectorAll('[data-toast]').forEach((el) => {
    el.addEventListener('click', () => toast(el.dataset.toast));
  });

  document.querySelectorAll('[data-detail]').forEach((btn) => {
    btn.addEventListener('click', () => {
      let data = {};
      try { data = JSON.parse(btn.dataset.detail); } catch (e) { data = {}; }
      const rows = Object.entries(data)
        .filter(([k]) => !['id'].includes(k))
        .map(([k, v]) => `<div><span>${k}</span><strong>${String(v ?? '—')}</strong></div>`)
        .join('');
      modal(data.title || data.ref || data.name || 'Record', `<div class="detail-grid">${rows}</div>`);
    });
  });

  document.getElementById('markAllRead')?.addEventListener('click', () => {
    document.querySelectorAll('.notification-row').forEach((n) => n.classList.add('read'));
    toast('All notifications marked as read.');
  });

  document.getElementById('createProjectForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    toast('Demo submission accepted for Admin review.');
        window.location.href = '/admin/projects?filter=pending';
  });

  document.getElementById('memberForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const name = e.target.querySelector('[name=fullName]')?.value.trim();
    if (!name) { toast('Enter the member full name before saving.', 'info'); return; }
    toast(`Account setup prepared for ${name}.`);
  });

  document.getElementById('addEvent')?.addEventListener('click', () => {
    modal('Add Calendar Activity', `<form class="form-grid"><div class="full"><label class="field"><span>Activity title</span><input placeholder="Activity title"></label></div><div><label class="field"><span>Type</span><select><option>Project</option><option>Meeting</option><option>Review</option></select></label></div><div><label class="field"><span>Date</span><input type="date"></label></div></form>`);
  });
})();
