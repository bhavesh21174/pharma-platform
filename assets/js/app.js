// Sidebar toggle on mobile
document.addEventListener('click', e => {
  const target = e.target instanceof Element ? e.target : null;
  if (!target) return;

  const toggle = target.closest('[data-sidebar-toggle]');
  const sidebar = document.querySelector('.app-sidebar');
  if (toggle && sidebar) {
    const open = sidebar.classList.toggle('open');
    document.body.classList.toggle('sidebar-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
    return;
  }

  if (sidebar?.classList.contains('open') && (!sidebar.contains(target) || target.closest('.app-sidebar a'))) {
    sidebar.classList.remove('open');
    document.body.classList.remove('sidebar-open');
    document.querySelector('[data-sidebar-toggle]')?.setAttribute('aria-expanded', 'false');
  }
});

document.addEventListener('keydown', e => {
  if (e.key !== 'Escape') return;
  document.querySelector('.app-sidebar.open')?.classList.remove('open');
  document.body.classList.remove('sidebar-open');
  document.querySelector('[data-sidebar-toggle]')?.setAttribute('aria-expanded', 'false');
});

// Auto-dismiss toasts
setTimeout(() => document.querySelectorAll('.toast.show').forEach(t => t.classList.remove('show')), 4000);