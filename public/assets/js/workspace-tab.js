(() => {
  const root = document.documentElement;
  const workspace = document.querySelector('meta[name="workspace-id"]')?.content;
  const newAccountUrl = document.querySelector('meta[name="new-account-url"]')?.content;
  const release = () => root.classList.remove('workspace-tab-pending');

  if (!workspace || !newAccountUrl || !('BroadcastChannel' in window)) {
    release();
    return;
  }

  const channel = new BroadcastChannel('jcistem-workspace-tabs');
  const instance = crypto.randomUUID?.() ?? `${Date.now()}-${Math.random()}`;
  const started = Date.now();
  let leaving = false;

  const isOlder = (other) => other.started < started ||
    (other.started === started && other.instance < instance);
  const startNewLogin = () => {
    if (leaving) return;
    leaving = true;
    channel.close();
    window.location.replace(newAccountUrl);
  };

  channel.onmessage = ({ data }) => {
    if (leaving || data?.workspace !== workspace || data.instance === instance) return;
    if (data.type === 'probe') {
      channel.postMessage({ type: 'present', workspace, instance, started, target: data.instance });
      if (isOlder(data)) startNewLogin();
    } else if (data.type === 'present' && data.target === instance && isOlder(data)) {
      startNewLogin();
    }
  };

  channel.postMessage({ type: 'probe', workspace, instance, started });
  setTimeout(release, 180);
  window.addEventListener('pagehide', () => { leaving = true; channel.close(); }, { once: true });
})();
