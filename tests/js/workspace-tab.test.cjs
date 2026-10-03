const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const script = fs.readFileSync(path.join(__dirname, '../../public/assets/js/workspace-tab.js'), 'utf8');

test('a second tab on the same workspace gets a fresh login while other workspaces stay open', async () => {
  const channels = new Set();
  let nextId = 0;

  class Channel {
    constructor(name) { this.name = name; channels.add(this); }
    postMessage(data) {
      for (const peer of channels) {
        if (peer !== this && peer.name === this.name) queueMicrotask(() => peer.onmessage?.({ data }));
      }
    }
    close() { channels.delete(this); }
  }

  function openTab(workspace) {
    const tab = { redirect: null, hidden: true };
    const document = {
      documentElement: { classList: { remove: () => { tab.hidden = false; } } },
      querySelector: (selector) => ({
        'meta[name="workspace-id"]': { content: workspace },
        'meta[name="new-account-url"]': { content: '/login' },
      })[selector],
    };
    const window = {
      BroadcastChannel: Channel,
      location: { replace: (url) => { tab.redirect = url; } },
      addEventListener: () => {},
    };
    vm.runInNewContext(script, {
      document, window, BroadcastChannel: Channel,
      crypto: { randomUUID: () => String(++nextId).padStart(4, '0') },
      setTimeout: (callback) => callback(),
      Date: { now: () => 1000 },
      Math,
    });
    return tab;
  }

  const admin = openTab('admin-workspace');
  const duplicate = openTab('admin-workspace');
  const member = openTab('member-workspace');
  await new Promise(setImmediate);

  assert.equal(admin.redirect, null);
  assert.equal(duplicate.redirect, '/login');
  assert.equal(member.redirect, null);
  assert.equal(admin.hidden, false);
  assert.equal(member.hidden, false);
});
