import { boundedProgress } from './pms-helpers.js';

(() => {
  const toggle = document.querySelector('.menu-button');
  const overlay = document.querySelector('.nav-overlay');
  const topbarMenus = [...document.querySelectorAll('.topbar-settings-menu')];
  const sidebarNav = document.querySelector('.app-sidebar nav');
  const sidebarScrollKey = `jcistem.sidebarScroll.${document.querySelector('meta[name="workspace-id"]')?.content || 'default'}`;
  const createMenu = document.querySelector('.record-create-dropdown');
  const calendarDialog = document.querySelector('#calendar-event-dialog');
  const projectPreview = document.querySelector('#project-preview-dialog');
  const groups = [...document.querySelectorAll('.nav-section')];
  const currentGroup = groups.find((group) => group.querySelector('[aria-current="page"]'));
  const primaryCurrent = document.querySelector('.sidebar-primary [aria-current="page"], .nav-direct-link[aria-current="page"]');
  let rememberedGroup = null;
  try { rememberedGroup = sessionStorage.getItem('jcistem.navGroup'); } catch {}
  const initialGroup = currentGroup || (!primaryCurrent && (groups.find((group) => group.querySelector('summary')?.textContent.trim() === rememberedGroup) || groups[0]));
  const expandedNavigation = document.body.dataset.navigationMode === 'expanded';
  groups.forEach((group) => {
    if (expandedNavigation) group.removeAttribute('name');
    group.open = expandedNavigation || group === initialGroup;
  });
  groups.forEach((group) => group.addEventListener('toggle', () => {
    if (!group.open) return;
    if (document.body.dataset.navigationMode !== 'expanded') groups.forEach((other) => { if (other !== group) other.open = false; });
    try { sessionStorage.setItem('jcistem.navGroup', group.querySelector('summary')?.textContent.trim() || ''); } catch {}
  }));
  const activeSideLink = document.querySelector('.app-sidebar [aria-current="page"]');
  const keepActiveSideLinkVisible = () => {
    if (!sidebarNav || !activeSideLink) return;
    const navBox = sidebarNav.getBoundingClientRect();
    const linkBox = activeSideLink.getBoundingClientRect();
    if (linkBox.top < navBox.top) sidebarNav.scrollTop -= navBox.top - linkBox.top + 8;
    else if (linkBox.bottom > navBox.bottom) sidebarNav.scrollTop += linkBox.bottom - navBox.bottom + 8;
  };
  if (sidebarNav) {
    let savedScroll = 0;
    try { savedScroll = Number(sessionStorage.getItem(sidebarScrollKey)) || 0; } catch {}
    requestAnimationFrame(() => requestAnimationFrame(() => {
      sidebarNav.scrollTop = savedScroll;
      keepActiveSideLinkVisible();
    }));
    sidebarNav.addEventListener('scroll', () => {
      try { sessionStorage.setItem(sidebarScrollKey, String(sidebarNav.scrollTop)); } catch {}
    }, { passive: true });
    sidebarNav.addEventListener('click', (event) => {
      if (!event.target.closest('a[href]')) return;
      try { sessionStorage.setItem(sidebarScrollKey, String(sidebarNav.scrollTop)); } catch {}
    });
  }
  const setNavigation = (open) => {
    document.body.classList.toggle('nav-open', open);
    toggle?.setAttribute('aria-expanded', String(open));
    toggle?.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
    const mobile = window.matchMedia('(max-width: 760px)').matches;
    document.querySelector('.app-sidebar')?.toggleAttribute('inert', mobile && !open);
    document.querySelector('.app-main')?.toggleAttribute('inert', mobile && open);
    if (mobile && open) (activeSideLink || document.querySelector('.app-sidebar a'))?.focus({ preventScroll: true });
  };
  setNavigation(false);
  topbarMenus.forEach((menu) => {
    menu.addEventListener('toggle', () => {
      menu.querySelector('summary')?.setAttribute('aria-expanded', String(menu.open));
      if (menu.open) topbarMenus.forEach((other) => { if (other !== menu) other.open = false; });
    });
    menu.addEventListener('focusout', (event) => {
      if (event.relatedTarget && !menu.contains(event.relatedTarget)) menu.open = false;
    });
  });
  toggle?.addEventListener('click', () => { topbarMenus.forEach((menu) => { menu.open = false; }); setNavigation(!document.body.classList.contains('nav-open')); });
  document.addEventListener('click', (event) => {
    topbarMenus.forEach((menu) => { if (!menu.contains(event.target)) menu.open = false; });
    if (createMenu && !createMenu.contains(event.target)) createMenu.open = false;
  });
  overlay?.addEventListener('click', () => { setNavigation(false); toggle?.focus(); });
  window.matchMedia('(max-width: 760px)').addEventListener('change', () => setNavigation(false));
  document.addEventListener('keydown', (event) => {
    if (!document.body.classList.contains('nav-open')) return;
    if (event.key === 'Escape') { setNavigation(false); toggle?.focus(); }
    if (event.key !== 'Tab') return;
    const navigationItems = [...document.querySelectorAll('.app-sidebar a, .app-sidebar button, .app-sidebar summary, .nav-overlay')].filter((item) => item.getClientRects().length);
    const first = navigationItems[0];
    const last = navigationItems.at(-1);
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
  });
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
    const objective = projectPreview.querySelector('[data-preview-objective]');
    if (objective) { objective.textContent = data.objective || ''; objective.hidden = !data.objective; }
    write('pace', data.elapsed === '' ? data.pace : `${data.pace} · ${data.elapsed}% of schedule elapsed`);
    const timeMarker = projectPreview.querySelector('[data-preview-time-marker]');
    if (timeMarker) {
      const elapsed = Number(data.elapsed);
      timeMarker.hidden = data.elapsed === '' || !Number.isFinite(elapsed) || elapsed <= 0;
      timeMarker.style.left = `${Math.min(100, Math.max(0, elapsed))}%`;
    }
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
  const documentPreviewVideo = documentPreview?.querySelector('[data-document-preview-video]');
  let documentPreviewTrigger = null;
  document.querySelectorAll('[data-document-preview]').forEach((button) => button.addEventListener('click', () => {
    if (!documentPreview?.showModal || !documentPreviewFrame) return;
    documentPreviewTrigger = button;
    const url = button.dataset.previewUrl;
    documentPreview.querySelector('[data-document-preview-title]').textContent = button.dataset.previewName || 'Project file';
    documentPreview.querySelector('[data-document-preview-type]').textContent = button.dataset.previewType || 'FILE';
    documentPreview.querySelector('[data-document-preview-open]').href = url;
    documentPreview.querySelector('[data-document-preview-download]').href = button.dataset.downloadUrl || url;
    if (button.dataset.previewType === 'MP4' && documentPreviewVideo) {
      documentPreviewFrame.hidden = true;
      documentPreviewFrame.src = 'about:blank';
      documentPreviewVideo.hidden = false;
      documentPreviewVideo.src = url;
    } else if (['DOCX', 'XLS', 'XLSX', 'PPTX'].includes(button.dataset.previewType)) {
      if (documentPreviewVideo) documentPreviewVideo.hidden = true;
      documentPreviewFrame.hidden = false;
      const frameUrl = new URL(url, window.location.href);
      frameUrl.searchParams.set('embedded', '1');
      documentPreviewFrame.src = frameUrl.toString();
    } else {
      if (documentPreviewVideo) documentPreviewVideo.hidden = true;
      documentPreviewFrame.hidden = false;
      documentPreviewFrame.src = url;
    }
    documentPreview.showModal();
  }));
  documentPreview?.querySelectorAll('[data-document-preview-close]').forEach((button) => button.addEventListener('click', () => documentPreview.close()));
  documentPreview?.addEventListener('click', (event) => { if (event.target === documentPreview) documentPreview.close(); });
  documentPreview?.addEventListener('close', () => {
    if (documentPreviewFrame) documentPreviewFrame.src = 'about:blank';
    if (documentPreviewVideo) { documentPreviewVideo.pause(); documentPreviewVideo.removeAttribute('src'); documentPreviewVideo.load(); }
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
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') { setNavigation(false); topbarMenus.forEach((menu) => { if (menu.open) { menu.open = false; menu.querySelector('summary')?.focus(); } }); if (createMenu?.open) { createMenu.open = false; createMenu.querySelector('summary')?.focus(); } } });
  document.querySelectorAll('[data-print]').forEach((button) => button.addEventListener('click', () => window.print()));
  document.querySelectorAll('[data-ledger-budget-line]').forEach((select) => {
    const project = select.form?.querySelector('select[name="project_id"]');
    if (!project) return;
    const updateLines = () => {
      [...select.options].forEach((option) => { if (option.value) option.disabled = option.dataset.projectId !== project.value; });
      if (select.selectedOptions[0]?.disabled) select.value = '';
    };
    project.addEventListener('change', updateLines);
    updateLines();
  });
  const projectPages = document.querySelector('[data-project-pages]');
  if (projectPages) {
    const links = [...projectPages.querySelectorAll('a[href^="#"]')];
    const sections = links.map((link) => document.getElementById(link.hash.slice(1)));
    const showPage = () => {
      const selected = links.find((link) => link.hash === window.location.hash) || links[0];
      links.forEach((link, index) => {
        sections[index].hidden = link !== selected;
        if (link === selected) link.setAttribute('aria-current', 'page'); else link.removeAttribute('aria-current');
      });
    };
    links.forEach((link) => link.addEventListener('click', (event) => {
      event.preventDefault();
      history.pushState(null, '', link.hash);
      showPage();
      projectPages.scrollIntoView({ block: 'start', behavior: 'smooth' });
    }));
    window.addEventListener('popstate', showPage);
    showPage();
  }
  document.querySelectorAll('[data-project-submission-form]').forEach((form) => {
    const choices = [...form.querySelectorAll('input[type="radio"][name$="_submission_method"]')];
    const panels = [...form.querySelectorAll('[data-submission-panel]')];
    const updateSubmissionPanels = () => {
      const method = choices.find((choice) => choice.checked)?.value || 'online';
      panels.forEach((panel) => {
        panel.hidden = panel.dataset.submissionPanel !== method;
        // Hidden required fields must not block the other submission method.
        panel.querySelectorAll('input, select, textarea').forEach((field) => { field.disabled = panel.hidden; });
      });
    };
    choices.forEach((choice) => choice.addEventListener('change', updateSubmissionPanels));
    updateSubmissionPanels();
    const general = form.querySelector('#proposal-general');
    const methodSection = form.querySelector('.submission-method-section');
    const review = form.querySelector('[data-project-review]');
    const reviewSummary = form.querySelector('[data-project-review-summary]');
    const confirmReview = form.querySelector('[data-project-review-confirm]');
    const previous = form.querySelector('[data-project-step-prev]');
    const next = form.querySelector('[data-project-step-next]');
    const save = form.querySelector('[data-project-save]');
    let projectStep = 0;
    const showProjectStep = () => {
      if (!review) return;
      general.hidden = projectStep !== 0;
      methodSection.hidden = projectStep !== 1;
      updateSubmissionPanels();
      panels.forEach((panel) => { panel.hidden = projectStep !== 2 || panel.dataset.submissionPanel !== (choices.find((choice) => choice.checked)?.value || 'online'); });
      review.hidden = projectStep !== 3;
      previous.hidden = projectStep === 0;
      next.hidden = projectStep === 3;
      save.hidden = projectStep !== 3;
      document.querySelectorAll('[data-project-step-label]').forEach((label, index) => {
        label.classList.toggle('is-current', index === projectStep);
        label.classList.toggle('is-complete', index < projectStep);
        if (index === projectStep) label.setAttribute('aria-current', 'step'); else label.removeAttribute('aria-current');
      });
      form.querySelector('[data-project-step-hint]').textContent = projectStep === 3 ? 'Confirm the preview, then save the draft.' : 'Complete this step to continue.';
    };
    const activeStepFields = () => {
      const group = projectStep === 0 ? general : projectStep === 1 ? methodSection : panels.find((panel) => !panel.hidden);
      return [...(group?.querySelectorAll('input, select, textarea') || [])].filter((field) => !field.disabled);
    };
    const previewProjectDetails = () => {
      reviewSummary.replaceChildren();
      const heading = document.createElement('h3'); heading.textContent = 'Project details and selected method'; reviewSummary.append(heading);
      const list = document.createElement('dl'); list.className = 'project-review-list';
      const fields = [general, panels.find((panel) => panel.dataset.submissionPanel === (choices.find((choice) => choice.checked)?.value || 'online'))];
      fields.forEach((group) => group?.querySelectorAll('input, select, textarea').forEach((field) => {
        if (field.disabled || !field.name || field.type === 'hidden' || field.type === 'radio' || field.type === 'checkbox' || field.name === '_token') return;
        const value = field.type === 'file' ? [...field.files].map((file) => file.name).join(', ') : field.tagName === 'SELECT' ? field.selectedOptions[0]?.textContent : field.value.trim();
        if (!value || value.startsWith('Select ')) return;
        const row = document.createElement('div'); const term = document.createElement('dt'); const detail = document.createElement('dd');
        term.textContent = field.closest('label')?.querySelector('span')?.textContent?.trim() || field.name.replaceAll(/[\[\]_]/g, ' ');
        detail.textContent = value; row.append(term, detail); list.append(row);
      }));
      reviewSummary.append(list);
      const method = document.createElement('p'); method.className = 'hint'; method.textContent = `Submission method: ${choices.find((choice) => choice.checked)?.value === 'upload' ? 'Completed official template upload' : 'Online form'}.`; reviewSummary.append(method);
    };
    next?.addEventListener('click', () => {
      const invalid = activeStepFields().find((field) => !field.checkValidity());
      if (invalid) { invalid.reportValidity(); return; }
      if (projectStep === 2) previewProjectDetails();
      projectStep = Math.min(3, projectStep + 1); showProjectStep();
      document.querySelector('.project-entry-steps')?.scrollIntoView({ block: 'start', behavior: 'smooth' });
    });
    previous?.addEventListener('click', () => { projectStep = Math.max(0, projectStep - 1); showProjectStep(); document.querySelector('.project-entry-steps')?.scrollIntoView({ block: 'start', behavior: 'smooth' }); });
    form.addEventListener('submit', (event) => {
      if (projectStep !== 3 || !confirmReview.checked) { event.preventDefault(); projectStep = 3; previewProjectDetails(); showProjectStep(); confirmReview.focus(); }
    });
    choices.forEach((choice) => choice.addEventListener('change', showProjectStep));
    showProjectStep();
    const budget = form.querySelector('[data-budget-builder]');
    if (budget) {
      const list = budget.querySelector('[data-budget-list]');
      const template = budget.querySelector('[data-budget-row-template]');
      const totalInput = budget.querySelector('[data-budget-total-input]');
      const totalOutput = budget.querySelector('[data-budget-total]');
      const lockMessage = budget.querySelector('[data-budget-lock-message]');
      let nextBudgetIndex = Date.now();
      const currency = (cents) => `PHP ${(cents / 100).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
      const updateBudget = () => {
        const rows = [...list.querySelectorAll('[data-budget-row]')];
        const totalCents = rows.reduce((sum, row) => {
          const quantity = Number(row.querySelector('[data-budget-quantity]')?.value) || 0;
          const unitCents = Math.round((Number(row.querySelector('[data-budget-unit-cost]')?.value) || 0) * 100);
          const lineCents = Math.round(quantity * unitCents);
          row.querySelector('[data-budget-line-total]').textContent = currency(lineCents);
          return sum + lineCents;
        }, 0);
        totalInput.value = (totalCents / 100).toFixed(2);
        totalOutput.textContent = currency(totalCents);
        form.querySelectorAll('[data-budget-letter-total]').forEach((target) => { target.textContent = currency(totalCents); });
        rows.forEach((row) => { row.querySelector('[data-budget-remove]').disabled = rows.length === 1; });
        if (lockMessage) {
          const lockedCents = Math.round((Number(budget.dataset.lockedTotal) || 0) * 100);
          const matches = lockedCents === totalCents;
          lockMessage.classList.toggle('is-mismatch', !matches);
          lockMessage.textContent = matches
            ? `Itemized total matches the BOD-approved budget of ${currency(lockedCents)}.`
            : `Adjust the list to match the BOD-approved budget of ${currency(lockedCents)} (${currency(Math.abs(lockedCents - totalCents))} ${totalCents < lockedCents ? 'still unitemized' : 'over budget'}).`;
        }
      };
      budget.addEventListener('input', updateBudget);
      budget.addEventListener('click', (event) => {
        const add = event.target.closest('[data-budget-add]');
        const remove = event.target.closest('[data-budget-remove]');
        if (add) {
          const wrapper = document.createElement('div');
          wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(nextBudgetIndex++));
          const row = wrapper.firstElementChild;
          list.append(row);
          row.querySelector('select, input')?.focus();
          updateBudget();
        }
        if (remove && list.querySelectorAll('[data-budget-row]').length > 1) {
          remove.closest('[data-budget-row]').remove();
          updateBudget();
        }
      });
      updateBudget();
    }
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
    if (!button.formNoValidate && !form.noValidate && !form.reportValidity()) return;
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

