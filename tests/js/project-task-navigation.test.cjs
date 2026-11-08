const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const script = fs.readFileSync(path.join(__dirname, '../../public/assets/js/chapter.js'), 'utf8')
  .replace(/^import .*;\r?\n/, '');

function workspace(hash) {
  const element = (properties = {}) => ({
    listeners: {}, attributes: {}, hidden: false,
    addEventListener(name, callback) { (this.listeners[name] ??= []).push(callback); },
    dispatch(name) { for (const callback of this.listeners[name] ?? []) callback({ preventDefault() {} }); },
    setAttribute(name, value) { this.attributes[name] = value; },
    removeAttribute(name) { delete this.attributes[name]; },
    matches() { return false; }, contains() { return false; },
    querySelector() { return null; }, querySelectorAll() { return []; },
    scrollIntoView() {}, focus() {},
    ...properties,
  });
  const overview = element();
  const dialogs = [1, 2].map(() => element({
    open: false, showModal() { this.open = true; }, close() { this.open = false; this.dispatch('close'); },
  }));
  const cards = dialogs.map((dialog) => element({
    matches(selector) { return selector === '.project-task-card'; },
    querySelector(selector) { return selector === '.task-detail-modal' ? dialog : element(); },
  }));
  const delivery = element({ contains(target) { return cards.includes(target); } });
  const links = ['#overview', '#delivery'].map((hash) => element({ hash }));
  const taskLinks = ['#task-1', '#task-2'].map((hash) => element({ hash }));
  const nav = element({ querySelectorAll() { return links; } });
  const document = element({
    body: { dataset: {}, classList: { toggle() {}, contains() { return false; } } },
    querySelector(selector) { return selector === '[data-project-pages]' ? nav : null; },
    querySelectorAll(selector) {
      if (selector === '.task-modal') return dialogs;
      if (selector === '.task-modal[open]') return dialogs.filter((dialog) => dialog.open);
      if (selector === 'a[href^="#task-"]') return taskLinks;
      return [];
    },
    getElementById(id) { return { overview, delivery, 'task-1': cards[0], 'task-2': cards[1] }[id] ?? null; },
  });
  const window = element({ location: { hash }, matchMedia() { return element({ matches: false }); } });
  vm.runInNewContext(script, { document, window, history: { pushState(_state, _title, hash) { window.location.hash = hash; } } });
  return { overview, delivery, dialogs, links, taskLinks, window };
}

test('a direct task URL selects the task section and opens its details', () => {
  const page = workspace('#task-1');
  assert.equal(page.overview.hidden, true);
  assert.equal(page.delivery.hidden, false);
  assert.equal(page.links[1].attributes['aria-current'], 'page');
  assert.equal(page.dialogs[0].open, true);
});

test('task links switch details and can reopen the same task after closing', () => {
  const page = workspace('#overview');
  page.taskLinks[0].dispatch('click');
  assert.equal(page.dialogs[0].open, true);
  page.taskLinks[1].dispatch('click');
  assert.equal(page.dialogs[0].open, false);
  assert.equal(page.dialogs[1].open, true);
  page.dialogs[1].close();
  page.taskLinks[1].dispatch('click');
  assert.equal(page.dialogs[1].open, true);
});

test('hash changes reveal tasks and an unknown anchor falls back to overview', () => {
  const page = workspace('#unknown');
  assert.equal(page.overview.hidden, false);
  assert.equal(page.delivery.hidden, true);
  page.window.location.hash = '#task-2';
  page.window.dispatch('hashchange');
  assert.equal(page.delivery.hidden, false);
  assert.equal(page.dialogs[1].open, true);
});
