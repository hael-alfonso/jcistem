import { boundedProgress } from './pms-helpers.js';

(() => {
  const toggle = document.querySelector('.menu-button');
  const overlay = document.querySelector('.nav-overlay');
  const settingsMenu = document.querySelector('.topbar-settings-menu');
  const createMenu = document.querySelector('.record-create-dropdown');
  const calendarDialog = document.querySelector('#calendar-event-dialog');
  const projectPreview = document.querySelector('#project-preview-dialog');
  const groups = [...document.querySelectorAll('.nav-section')];
  const currentGroup = groups.find((group) => group.querySelector('[aria-current="page"]'));
  const primaryCurrent = document.querySelector('.sidebar-primary [aria-current="page"], .nav-direct-link[aria-current="page"]');
  let rememberedGroup = null;
  try { rememberedGroup = sessionStorage.getItem('jcistem.navGroup'); } catch {}
  const initialGroup = currentGroup || (!primaryCurrent && (groups.find((group) => group.querySelector('summary')?.textContent.trim() === rememberedGroup) || groups[0]));
  groups.forEach((group) => { group.open = group === initialGroup; });
  groups.forEach((group) => group.addEventListener('toggle', () => {
    if (!group.open) return;
    groups.forEach((other) => { if (other !== group) other.open = false; });
    try { sessionStorage.setItem('jcistem.navGroup', group.querySelector('summary')?.textContent.trim() || ''); } catch {}
  }));
  const setNavigation = (open) => {
    document.body.classList.toggle('nav-open', open);
    toggle?.setAttribute('aria-expanded', String(open));
    toggle?.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
  };
  toggle?.addEventListener('click', () => { settingsMenu && (settingsMenu.open = false); setNavigation(!document.body.classList.contains('nav-open')); });
  document.addEventListener('click', (event) => {
    if (settingsMenu && !settingsMenu.contains(event.target)) settingsMenu.open = false;
    if (createMenu && !createMenu.contains(event.target)) createMenu.open = false;
  });
  overlay?.addEventListener('click', () => setNavigation(false));
  document.querySelector('[data-calendar-dialog-open]')?.addEventListener('click', () => {
    if (calendarDialog?.showModal) calendarDialog.showModal();
    else calendarDialog?.setAttribute('open', '');
  });
  document.querySelectorAll('[data-calendar-dialog-close]').forEach((button) => button.addEventListener('click', () => calendarDialog?.close()));
  calendarDialog?.addEventListener('click', (event) => { if (event.target === calendarDialog) calendarDialog.close(); });
  let previewTrigger = null;
  document.querySelectorAll('[data-project-preview]').forEach((button) => button.addEventListener('click', () => {
    const data = button.dataset;
    if (!projectPreview?.showModal) { window.location.assign(data.href); return; }
    previewTrigger = button;
    const write = (key, value) => { const target = projectPreview.querySelector(`[data-preview-${key}]`); if (target) target.textContent = value || 'Not set'; };
    write('reference', data.reference);
    write('title', data.title);
    write('status', data.status);
    write('area', data.area);
    write('chair', data.chair);
    write('dates', data.dates);
    write('budget-label', data.budgetLabel);
    write('budget', data.budget);
    write('tasks', `${data.completed} of ${data.total} complete`);
    projectPreview.querySelector('[data-preview-progress]').value = boundedProgress(data.progress);
    projectPreview.querySelector('[data-preview-link]').href = data.href;
    projectPreview.showModal();
  }));
  projectPreview?.querySelectorAll('[data-preview-close]').forEach((button) => button.addEventListener('click', () => projectPreview.close()));
  projectPreview?.addEventListener('click', (event) => { if (event.target === projectPreview) projectPreview.close(); });
  projectPreview?.addEventListener('close', () => { previewTrigger?.focus(); previewTrigger = null; });
  const taskPreview = document.querySelector('#task-preview-dialog');
  let taskPreviewTrigger = null;
  document.querySelectorAll('[data-task-preview]').forEach((link) => link.addEventListener('click', (event) => {
    if (!taskPreview?.showModal) return;
    event.preventDefault();
    taskPreviewTrigger = link;
    const data = link.dataset;
    const write = (key, value) => { const target = taskPreview.querySelector(`[data-task-preview-${key}]`); if (target) target.textContent = value || 'Not set'; };
    for (const key of ['title', 'project', 'status', 'priority', 'deadline', 'milestone', 'assignees', 'description', 'notes', 'evidence']) write(key, data[`task${key[0].toUpperCase()}${key.slice(1)}`]);
    taskPreview.querySelector('[data-task-preview-status]').className = `badge task-status task-status-${data.taskTone}`;
    taskPreview.querySelector('[data-task-preview-priority]').className = `task-priority priority-${data.taskPriorityTone}`;
    taskPreview.querySelector('[data-task-preview-link]').href = link.href;
    taskPreview.showModal();
  }));
  taskPreview?.addEventListener('close', () => { taskPreviewTrigger?.focus(); taskPreviewTrigger = null; });
  const documentPreview = document.querySelector('#document-preview-dialog');
  const documentPreviewFrame = documentPreview?.querySelector('[data-document-preview-frame]');
  let documentPreviewTrigger = null;
  document.querySelectorAll('[data-document-preview]').forEach((button) => button.addEventListener('click', () => {
    if (!documentPreview?.showModal || !documentPreviewFrame) return;
    documentPreviewTrigger = button;
    const url = button.dataset.previewUrl;
    documentPreview.querySelector('[data-document-preview-title]').textContent = button.dataset.previewName || 'Project file';
    documentPreview.querySelector('[data-document-preview-type]').textContent = button.dataset.previewType || 'FILE';
    documentPreview.querySelector('[data-document-preview-open]').href = url;
    documentPreview.querySelector('[data-document-preview-download]').href = button.dataset.downloadUrl || url;
    documentPreviewFrame.src = url;
    documentPreview.showModal();
  }));
  documentPreview?.querySelectorAll('[data-document-preview-close]').forEach((button) => button.addEventListener('click', () => documentPreview.close()));
  documentPreview?.addEventListener('click', (event) => { if (event.target === documentPreview) documentPreview.close(); });
  documentPreview?.addEventListener('close', () => {
    if (documentPreviewFrame) documentPreviewFrame.src = 'about:blank';
    documentPreviewTrigger?.focus();
    documentPreviewTrigger = null;
  });
  const taskDialogTriggers = new WeakMap();
  document.querySelectorAll('[data-task-dialog-open]').forEach((button) => button.addEventListener('click', () => {
    const dialog = document.getElementById(button.dataset.taskDialogOpen);
    if (!dialog?.showModal) return;
    button.closest('dialog')?.close();
    taskDialogTriggers.set(dialog, button);
    dialog.showModal();
  }));
  document.querySelectorAll('.task-modal').forEach((dialog) => {
    dialog.querySelectorAll('[data-task-dialog-close]').forEach((button) => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => taskDialogTriggers.get(dialog)?.focus());
  });
  const taskReopen = document.querySelector('[data-task-reopen]')?.dataset.taskReopen;
  const taskReopenDialog = taskReopen && document.getElementById(taskReopen);
  if (taskReopenDialog?.showModal && document.querySelector('.alert.error')) taskReopenDialog.showModal();
  document.querySelectorAll('.app-sidebar .side-link').forEach((link) => link.addEventListener('click', () => setNavigation(false)));
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') { setNavigation(false); if (settingsMenu?.open) { settingsMenu.open = false; settingsMenu.querySelector('summary')?.focus(); } if (createMenu?.open) { createMenu.open = false; createMenu.querySelector('summary')?.focus(); } } });
  document.querySelectorAll('[data-print]').forEach((button) => button.addEventListener('click', () => window.print()));
  document.querySelectorAll('[data-project-submission-form]').forEach((form) => {
    const choices = [...form.querySelectorAll('input[type="radio"][name$="_submission_method"]')];
    const panels = [...form.querySelectorAll('[data-submission-panel]')];
    const updateSubmissionPanels = () => {
      const method = choices.find((choice) => choice.checked)?.value || 'online';
      panels.forEach((panel) => { panel.hidden = panel.dataset.submissionPanel !== method; });
    };
    choices.forEach((choice) => choice.addEventListener('change', updateSubmissionPanels));
    updateSubmissionPanels();
    for (const [group, output] of [['income_lines', '[data-proposal-income-total]'], ['expense_lines', '[data-proposal-expense-total]']]) {
      const amountFields = [...form.querySelectorAll(`input[name^="proposal[${group}]"]`)];
      const total = form.querySelector(output);
      if (!total) continue;
      const updateTotal = () => {
        const cents = amountFields.reduce((sum, field) => sum + Math.round((Number(field.value) || 0) * 100), 0);
        total.textContent = `PHP ${(cents / 100).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
      };
      amountFields.forEach((field) => field.addEventListener('input', updateTotal));
      updateTotal();
    }
  });
  const confirmDialog = document.querySelector('#confirm-dialog');
  const confirmMessage = confirmDialog?.querySelector('#confirm-dialog-message');
  let pendingAction = null;
  document.querySelectorAll('[data-confirm]').forEach((button) => button.addEventListener('click', (event) => {
    const form = button.form;
    if (!form) return;
    event.preventDefault();
    if (!form.reportValidity()) return;
    if (!confirmDialog?.showModal) {
      if (window.confirm(button.dataset.confirm)) form.requestSubmit(button);
      return;
    }
    pendingAction = button;
    confirmMessage.textContent = button.dataset.confirm;
    confirmDialog.showModal();
  }));
  confirmDialog?.querySelector('[data-dialog-cancel]')?.addEventListener('click', () => confirmDialog.close());
  confirmDialog?.querySelector('[data-dialog-confirm]')?.addEventListener('click', () => {
    const button = pendingAction;
    confirmDialog.close();
    if (button?.form) button.form.requestSubmit(button);
  });
  confirmDialog?.addEventListener('close', () => { pendingAction?.focus(); pendingAction = null; });
  const toast = document.querySelector('[data-toast]');
  if (toast) {
    let toastTimer;
    const dismissToast = () => {
      window.clearTimeout(toastTimer);
      toast.classList.add('is-leaving');
      window.setTimeout(() => toast.parentElement?.remove(), window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 220);
    };
    const scheduleToast = () => { window.clearTimeout(toastTimer); toastTimer = window.setTimeout(dismissToast, 8000); };
    toast.querySelector('[data-toast-dismiss]')?.addEventListener('click', dismissToast);
    toast.addEventListener('mouseenter', () => window.clearTimeout(toastTimer));
    toast.addEventListener('mouseleave', scheduleToast);
    toast.addEventListener('focusin', () => window.clearTimeout(toastTimer));
    toast.addEventListener('focusout', (event) => { if (!toast.contains(event.relatedTarget)) scheduleToast(); });
    scheduleToast();
  }
  document.querySelectorAll('form').forEach((form) => form.addEventListener('submit', () => {
    if (!form.checkValidity()) return;
    form.setAttribute('aria-busy', 'true');
    // Keep submitter values in the request; do not disable action buttons.
  }));
})();

