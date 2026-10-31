(() => {
  const container = document.getElementById('document-pages');
  const status = document.getElementById('viewer-status');
  const url = document.body.dataset.documentUrl;
  if (!container || !status || !url) return;

  const open = async () => {
    try {
      if (!window.docx?.renderAsync) throw new Error('The document viewer is unavailable.');
      const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
      if (!response.ok) throw new Error('The document could not be loaded.');
      const file = await response.blob();
      await window.docx.renderAsync(file, container, null, {
        breakPages: true,
        renderHeaders: true,
        renderFooters: true,
        renderFootnotes: true,
        renderEndnotes: true,
        renderAltChunks: false,
        useBase64URL: true,
      });
      status.hidden = true;
    } catch (error) {
      status.textContent = 'This DOCX could not be shown in the browser. Use the PDF rendering or download the original file.';
      status.classList.add('is-error');
    }
  };

  open();
})();
