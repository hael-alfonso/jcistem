import { boundedProgress } from './pms-helpers.js';

(() => {
  const toggle = document.querySelector('.menu-button');
  const overlay = document.querySelector('.nav-overlay');
  const sidebarClose = document.querySelector('[data-nav-close]');
  const topbarMenus = [...document.querySelectorAll('.topbar-settings-menu')];
  const sidebarNav = document.querySelector('.app-sidebar nav');
  const sidebarScrollKey = `jcistem.sidebarScroll.${document.querySelector('meta[name="workspace-id"]')?.content || 'default'}`;
  const createMenu = document.querySelector('.record-create-dropdown');
  const calendarDialog = document.querySelector('#calendar-event-dialog');
  const projectPreview = document.querySelector('#project-preview-dialog');
  const projectRemove = document.querySelector('#project-remove-dialog');
  document.querySelectorAll('.project-chair-avatar img').forEach((image) => {
    const showInitial = () => image.remove();
    image.addEventListener('error', showInitial, { once: true });
    if (image.complete && image.naturalWidth === 0) showInitial();
  });
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
    let scrollCueTimer;
    try { savedScroll = Number(sessionStorage.getItem(sidebarScrollKey)) || 0; } catch {}
    requestAnimationFrame(() => requestAnimationFrame(() => {
      sidebarNav.scrollTop = savedScroll;
      keepActiveSideLinkVisible();
    }));
    sidebarNav.addEventListener('scroll', () => {
      try { sessionStorage.setItem(sidebarScrollKey, String(sidebarNav.scrollTop)); } catch {}
      sidebarNav.classList.add('is-scrolling');
      clearTimeout(scrollCueTimer);
      scrollCueTimer = setTimeout(() => sidebarNav.classList.remove('is-scrolling'), 1100);
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
    if (mobile && open) sidebarClose?.focus({ preventScroll: true });
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
  sidebarClose?.addEventListener('click', () => { setNavigation(false); toggle?.focus(); });
  window.matchMedia('(max-width: 760px)').addEventListener('change', () => setNavigation(false));
  document.addEventListener('keydown', (event) => {
    if (!document.body.classList.contains('nav-open')) return;
    if (event.key === 'Escape') { setNavigation(false); toggle?.focus(); return; }
    if (event.key !== 'Tab') return;
    const navigationItems = [...document.querySelectorAll('.app-sidebar a, .app-sidebar button, .app-sidebar summary')].filter((item) => item.getClientRects().length);
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
  let projectRemoveTrigger = null;
  document.querySelectorAll('[data-project-remove]').forEach((link) => link.addEventListener('click', (event) => {
    if (!projectRemove?.showModal) return;
    const form = projectRemove.querySelector('[data-remove-form]');
    const name = projectRemove.querySelector('[data-remove-name]');
    if (!form || !name || !link.dataset.removeUrl) return;
    event.preventDefault();
    projectRemoveTrigger = link;
    form.action = link.dataset.removeUrl;
    form.reset();
    name.textContent = link.dataset.removeTitle || 'This project';
    projectRemove.showModal();
  }));
  projectRemove?.querySelectorAll('[data-remove-close]').forEach((button) => button.addEventListener('click', () => projectRemove.close()));
  projectRemove?.addEventListener('click', (event) => { if (event.target === projectRemove) projectRemove.close(); });
  projectRemove?.addEventListener('close', () => { projectRemoveTrigger?.focus(); projectRemoveTrigger = null; });
  const taskPreview = document.querySelector('#task-preview-dialog');
  let taskPreviewTrigger = null;
  document.querySelectorAll('[data-task-preview]').forEach((link) => link.addEventListener('click', (event) => {
    if (!taskPreview?.showModal) return;
    event.preventDefault();
    taskPreviewTrigger = link;
    const data = link.dataset;
    const write = (key, value) => { const target = taskPreview.querySelector(`[data-task-preview-${key}]`); if (target) target.textContent = value || 'Not set'; };
    for (const key of ['title', 'project', 'chair', 'status', 'priority', 'deadline', 'milestone', 'assignees', 'description', 'notes', 'evidence']) write(key, data[`task${key[0].toUpperCase()}${key.slice(1)}`]);
    const avatars = taskPreview.querySelector('[data-task-preview-avatars]');
    const rowAvatars = link.closest('tr')?.querySelector('.task-assignee-avatars');
    if (avatars) avatars.replaceChildren(...(rowAvatars ? [rowAvatars.cloneNode(true)] : []));
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
  const projectDialogTriggers = new WeakMap();
  document.querySelectorAll('[data-project-dialog-open]').forEach((button) => button.addEventListener('click', () => {
    const dialog = document.getElementById(button.dataset.projectDialogOpen);
    if (!dialog?.showModal) return;
    projectDialogTriggers.set(dialog, button);
    dialog.showModal();
  }));
  document.querySelectorAll('.project-decision-dialog').forEach((dialog) => {
    dialog.querySelectorAll('[data-project-dialog-close]').forEach((button) => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => projectDialogTriggers.get(dialog)?.focus());
  });
  const projectReopen = document.querySelector('[data-project-dialog-reopen]')?.dataset.projectDialogReopen;
  const projectReopenDialog = projectReopen && document.getElementById(projectReopen);
  if (projectReopenDialog?.showModal && document.querySelector('.alert.error')) projectReopenDialog.showModal();
  document.querySelectorAll('.ledger-row').forEach((row) => {
    const header = row.querySelector('.ledger-row-header');
    const oldTrigger = row.querySelector('.ledger-detail-trigger');
    if (!header || !oldTrigger) return;
    const dialogId = oldTrigger.dataset.financeDialogOpen;
    const reference = row.querySelector('.ledger-reference strong')?.textContent?.trim() || 'transaction';
    header.dataset.financeDialogOpen = dialogId;
    header.setAttribute('role', 'button');
    header.setAttribute('tabindex', '0');
    header.setAttribute('aria-haspopup', 'dialog');
    header.setAttribute('aria-controls', dialogId);
    header.setAttribute('aria-label', `Open transaction details for ${reference}`);
    header.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      event.preventDefault();
      header.click();
    });
    oldTrigger.remove();
  });
  const financeDialogTriggers = new WeakMap();
  document.querySelectorAll('[data-finance-dialog-open]').forEach((button) => button.addEventListener('click', () => {
    const dialog = document.getElementById(button.dataset.financeDialogOpen);
    if (!dialog?.showModal) return;
    if (dialog.id === 'dues-assessment-dialog') {
      const memberId = button.dataset.duesMemberId;
      dialog.querySelectorAll('input[name="member_id"]').forEach((input) => {
        input.checked = memberId !== 'none' && input.value === memberId;
      });
      const search = dialog.querySelector('[data-dues-choice-search]');
      if (search) { search.value = ''; search.dispatchEvent(new Event('input')); }
    }
    financeDialogTriggers.set(dialog, button);
    dialog.showModal();
  }));
  document.querySelectorAll('.finance-dialog').forEach((dialog) => {
    dialog.querySelectorAll('[data-finance-dialog-close]').forEach((button) => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => financeDialogTriggers.get(dialog)?.focus());
  });
  const ledgerTransactionForm = document.querySelector('#ledger-transaction-dialog form');
  const ledgerType = ledgerTransactionForm?.querySelector('select[name="type"]');
  const ledgerReceipt = ledgerTransactionForm?.querySelector('input[name="receipt"]');
  const updateLedgerReceiptRequirement = () => {
    if (!ledgerReceipt || !ledgerType) return;
    ledgerReceipt.required = ['Expense', 'Disbursement', 'Adjustment debit'].includes(ledgerType.value);
  };
  ledgerType?.addEventListener('change', updateLedgerReceiptRequirement);
  updateLedgerReceiptRequirement();
  const financeReopenId = document.querySelector('[data-finance-dialog-reopen]')?.dataset.financeDialogReopen;
  const financeReopenDialog = financeReopenId && document.getElementById(financeReopenId);
  if (financeReopenDialog?.closest('#budget')) history.replaceState(null, '', '#budget');
  if (financeReopenDialog?.showModal && financeReopenDialog.querySelector('.alert.error')) financeReopenDialog.showModal();
  const duesChoiceSearch = document.querySelector('[data-dues-choice-search]');
  duesChoiceSearch?.addEventListener('input', () => {
    const term = duesChoiceSearch.value.trim().toLocaleLowerCase();
    duesChoiceSearch.closest('fieldset')?.querySelectorAll('[data-dues-choice-list] .dues-target-option').forEach((label) => {
      label.hidden = !!term && !label.textContent.toLocaleLowerCase().includes(term) && !label.querySelector('input').checked;
    });
  });
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
      const target = document.getElementById(window.location.hash.slice(1));
      const selected = links.find((link, index) => link.hash === window.location.hash || (target && sections[index]?.contains(target))) || links[0];
      links.forEach((link, index) => {
        if (sections[index]) sections[index].hidden = link !== selected;
        if (link === selected) link.setAttribute('aria-current', 'page'); else link.removeAttribute('aria-current');
      });
      if (target?.matches('.project-task-card')) {
        const dialog = target.querySelector('.task-detail-modal');
        if (dialog?.showModal && !dialog.open) {
          document.querySelectorAll('.task-modal[open]').forEach((openDialog) => openDialog.close());
          taskDialogTriggers.set(dialog, target.querySelector('[data-task-dialog-open]'));
          dialog.showModal();
        }
      }
    };
    links.forEach((link) => link.addEventListener('click', (event) => {
      event.preventDefault();
      history.pushState(null, '', link.hash);
      showPage();
      projectPages.scrollIntoView({ block: 'start', behavior: 'smooth' });
    }));
    document.querySelectorAll('a[href^="#task-"]').forEach((link) => link.addEventListener('click', (event) => {
      event.preventDefault();
      history.pushState(null, '', link.hash);
      showPage();
    }));
    window.addEventListener('popstate', showPage);
    window.addEventListener('hashchange', showPage);
    showPage();
  }
  document.querySelectorAll('[data-project-submission-form]').forEach((form) => {
    const choices = [...form.querySelectorAll('input[type="radio"][name$="_submission_method"]')];
    const panels = [...form.querySelectorAll('[data-submission-panel]')];
    const updateSubmissionPanels = () => {
      const method = choices.find((choice) => choice.checked)?.value || 'online';
      panels.forEach((panel) => {
        // Keep both sections visible so the complete project record is always
        // available while only the selected submission method is editable.
        panel.hidden = false;
        panel.querySelectorAll('input, select, textarea').forEach((field) => {
          field.disabled = panel.dataset.submissionPanel !== method;
        });
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
      if (!general || !methodSection) return;
      // The project editor is a complete, single-page form. All details stay
      // visible; the selected method controls which inputs can be edited.
      general.hidden = false;
      methodSection.hidden = false;
      updateSubmissionPanels();
      if (review) review.hidden = true;
      if (previous) previous.hidden = true;
      if (next) next.hidden = true;
      if (save) save.hidden = false;
      form.querySelector('[data-project-step-hint]').textContent = 'All project details are visible. Select a submission method, then save your draft.';
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
      const budgetNote = document.createElement('p');
      budgetNote.className = 'hint';
      budgetNote.textContent = form.querySelector('[data-no-budget]')?.checked
        ? 'Project budget: no expenses planned.'
        : `Project budget: ${form.querySelector('[data-budget-total]')?.textContent?.trim() || 'PHP 0.00'}.`;
      reviewSummary.append(budgetNote);
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
    choices.forEach((choice) => choice.addEventListener('change', showProjectStep));
    showProjectStep();
    const budget = form.querySelector('[data-budget-builder]');
    if (budget) {
      const list = budget.querySelector('[data-budget-list]');
      const template = budget.querySelector('[data-budget-row-template]');
      const totalInput = budget.querySelector('[data-budget-total-input]');
      const totalOutput = budget.querySelector('[data-budget-total]');
      const lockMessage = budget.querySelector('[data-budget-lock-message]');
      const noBudget = budget.querySelector('[data-no-budget]');
      const budgetFields = budget.querySelector('[data-budget-fields]');
      const addBudget = budget.querySelector('[data-budget-add]');
      let nextBudgetIndex = Date.now();
      const currency = (cents) => `PHP ${(cents / 100).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
      const updateBudget = () => {
        const rows = noBudget?.checked ? [] : [...list.querySelectorAll('[data-budget-row]')];
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
      const toggleBudgetFields = () => {
        const empty = !!noBudget?.checked;
        if (budgetFields) {
          budgetFields.hidden = empty;
          budgetFields.querySelectorAll('input, select, textarea').forEach((field) => { field.disabled = empty; });
        }
        if (addBudget) addBudget.hidden = empty;
        updateBudget();
      };
      noBudget?.addEventListener('change', toggleBudgetFields);
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
      toggleBudgetFields();
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
  document.querySelectorAll('[data-report-submission-form]').forEach((form) => {
    const choices = [...form.querySelectorAll('input[type="radio"][name="data[submission_method]"]')];
    const digitalFields = form.querySelector('[data-report-digital-fields]');
    const attachment = form.querySelector('input[name="attachment"]');
    const updateReportMethod = () => {
      const method = choices.find((choice) => choice.checked)?.value || 'digital';
      if (digitalFields) digitalFields.hidden = method === 'upload';
      if (attachment) attachment.required = method === 'upload' && form.dataset.reportHasFile !== '1';
    };
    choices.forEach((choice) => choice.addEventListener('change', updateReportMethod));
    updateReportMethod();
  });
  const confirmDialog = document.querySelector('#confirm-dialog');
  const confirmMessage = confirmDialog?.querySelector('#confirm-dialog-message');
  const confirmTitle = confirmDialog?.querySelector('#confirm-dialog-title');
  const confirmSubmit = confirmDialog?.querySelector('[data-dialog-confirm]');
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
    const isWarning = button.matches('.danger, .caution') || /cancel|delete|void|reject|archive/i.test(button.value || '');
    confirmDialog.classList.toggle('is-warning', isWarning);
    confirmTitle.textContent = isWarning ? 'Review this action' : 'Confirm this change';
    confirmSubmit.classList.toggle('danger', isWarning);
    confirmSubmit.classList.toggle('primary', !isWarning);
      confirmSubmit.querySelector('[data-confirm-label]').textContent = isWarning ? 'Confirm action' : 'Continue';
    confirmDialog.showModal();
    if (isWarning) confirmDialog.querySelector('[data-dialog-cancel]')?.focus();
  }));
  confirmDialog?.querySelector('[data-dialog-cancel]')?.addEventListener('click', () => confirmDialog.close());
  confirmDialog?.addEventListener('click', (event) => { if (event.target === confirmDialog) confirmDialog.close(); });
  confirmDialog?.querySelector('[data-dialog-confirm]')?.addEventListener('click', () => {
    const button = pendingAction;
    confirmDialog.close();
    if (button?.form) button.form.requestSubmit(button);
  });
  confirmDialog?.addEventListener('close', () => { pendingAction?.focus(); pendingAction = null; });
  const feedbackDialog = document.querySelector('[data-feedback-dialog]');
  feedbackDialog?.querySelector('[data-feedback-close]')?.addEventListener('click', () => feedbackDialog.close());
  feedbackDialog?.addEventListener('click', (event) => { if (event.target === feedbackDialog) feedbackDialog.close(); });
  if (feedbackDialog?.showModal) feedbackDialog.showModal();
  const toast = document.querySelector('[data-toast]');
  if (toast) {
    let timer;
    const dismiss = () => {
      window.clearTimeout(timer);
      toast.classList.add('is-leaving');
      window.setTimeout(() => toast.parentElement?.remove(), window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 220);
    };
    const schedule = () => { window.clearTimeout(timer); timer = window.setTimeout(dismiss, 6500); };
    toast.querySelector('[data-toast-dismiss]')?.addEventListener('click', dismiss);
    toast.addEventListener('mouseenter', () => window.clearTimeout(timer));
    toast.addEventListener('mouseleave', schedule);
    toast.addEventListener('focusin', () => window.clearTimeout(timer));
    toast.addEventListener('focusout', (event) => { if (!toast.contains(event.relatedTarget)) schedule(); });
    schedule();
  }
  document.querySelectorAll('form').forEach((form) => form.addEventListener('submit', () => {
    if (!form.checkValidity()) return;
    form.setAttribute('aria-busy', 'true');
    // Keep submitter values in the request; do not disable action buttons.
  }));
})();

