(() => {
  'use strict';
  const box = document.getElementById('oh-import-progress');
  if (!box || typeof ohImportQueue === 'undefined') return;
  const status = document.getElementById('oh-import-status');
  const meter = document.getElementById('oh-import-meter');
  const resume = document.getElementById('oh-import-resume');
  const pause = document.getElementById('oh-import-pause');
  let running = false;
  const batch = async () => {
    if (!running) return;
    resume.disabled = true;
    try {
      const body = new URLSearchParams({action: 'oh_import_batch', nonce: ohImportQueue.nonce});
      const response = await fetch(ohImportQueue.url, {method: 'POST', credentials: 'same-origin', body});
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.data?.message || 'Import paused; refresh the page and resume.');
      const data = result.data;
      meter.max = Math.max(1, data.total || 0);
      meter.value = data.processed || 0;
      status.textContent = `${data.stage}: ${data.processed || 0} / ${data.total || 0} records; imported ${data.imported || 0}, existing skipped ${data.skipped || 0}.`;
      if (data.stage === 'complete') {
        running = false; resume.hidden = true; pause.hidden = true;
        status.textContent += ' Import and search index completed successfully. Refresh the dashboard to see the final totals.';
      } else if (data.stage === 'error') {
        throw new Error(data.error || 'Import paused; retry to resume.');
      } else if (running) setTimeout(batch, data.busy ? 1500 : 150);
    } catch (error) {
      running = false; status.textContent = error.message;
    } finally {
      resume.disabled = running;
    }
  };
  resume.addEventListener('click', () => { if (!running) {running = true; batch();} });
  pause.addEventListener('click', () => {running = false; status.textContent += ' Paused; the current batch will finish safely.';});
  if (box.dataset.active === '1') { running = true; batch(); }
  else { resume.hidden = true; pause.hidden = true; }
})();
