import { read, utils } from '@e965/xlsx';
import { init as createSlides } from 'pptx-preview';
import DOMPurify from 'dompurify';

const body = document.body;
const status = document.querySelector('#viewer-status');
const pages = document.querySelector('#document-pages');

async function renderSpreadsheet(bytes) {
  const workbook = read(bytes, { type: 'array', cellStyles: true, sheetRows: 2000 });
  const tabs = document.createElement('div'); tabs.className = 'office-sheet-tabs';
  const content = document.createElement('div'); content.className = 'office-sheet-content';
  pages.append(tabs, content);
  workbook.SheetNames.forEach((name, index) => {
    const button = document.createElement('button'); button.type = 'button'; button.textContent = name;
    button.addEventListener('click', () => {
      tabs.querySelectorAll('button').forEach((item) => item.setAttribute('aria-selected', String(item === button)));
      content.innerHTML = DOMPurify.sanitize(utils.sheet_to_html(workbook.Sheets[name]));
    });
    tabs.append(button);
    if (index === 0) button.click();
  });
}

async function renderSlides(bytes) {
  const width = Math.min(Math.max(pages.clientWidth - 32, 320), 1000);
  const slides = createSlides(pages, { width, height: Math.round(width * 9 / 16), mode: 'list' });
  await slides.preview(bytes.buffer);
}

async function openOfficeFile() {
  const response = await fetch(body.dataset.documentUrl, { credentials: 'same-origin', cache: 'no-store' });
  if (!response.ok) throw new Error('This file could not be opened. Check your access or download the original.');
  const bytes = new Uint8Array(await response.arrayBuffer());
  if (/\.pptx$/i.test(body.dataset.documentName)) await renderSlides(bytes);
  else await renderSpreadsheet(bytes);
  status.hidden = true;
}

openOfficeFile().catch((error) => {
  status.textContent = error.message || 'The preview could not be rendered. Download the original file instead.';
  status.classList.add('is-error');
});
