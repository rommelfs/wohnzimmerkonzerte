(() => {
  'use strict';
  // Original image links remain usable when JavaScript or modal dialogs are unavailable.
  if (!window.HTMLDialogElement || !HTMLDialogElement.prototype.showModal) return;
  document.querySelectorAll('[data-rl-gallery] a[href]').forEach(link => link.setAttribute('aria-haspopup', 'dialog'));
  let dialog, image, caption, counter, status, previous, next, original, stage;
  let links = [], index = 0, opener, gallery, touch;
  const element = (tag, className, text) => {
    const node = document.createElement(tag);
    node.className = className;
    if (text) node.textContent = text;
    return node;
  };
  function show(newIndex) {
    index = (newIndex + links.length) % links.length;
    const link = links[index];
    const source = link.querySelector('img');
    image.hidden = true;
    stage.setAttribute('aria-busy', 'true');
    status.textContent = '';
    caption.textContent = link.closest('figure')?.querySelector('figcaption')?.textContent.trim() || '';
    caption.hidden = !caption.textContent;
    counter.textContent = gallery.dataset.count.replace('{current}', String(index + 1)).replace('{total}', String(links.length));
    image.alt = source?.alt || '';
    original.href = link.href;
    image.src = link.href;
    previous.hidden = next.hidden = links.length < 2;
  }
  function createDialog() {
    dialog = element('dialog', 'rl-lightbox');
    const toolbar = element('div', 'rl-lightbox-toolbar');
    counter = element('p', 'rl-lightbox-count');
    counter.setAttribute('role', 'status');
    counter.setAttribute('aria-live', 'polite');
    const close = element('button', 'rl-lightbox-close', '×');
    close.type = 'button';
    close.autofocus = true;
    toolbar.append(counter, close);
    stage = element('div', 'rl-lightbox-stage');
    image = element('img', 'rl-lightbox-image');
    image.decoding = 'async';
    status = element('p', 'rl-lightbox-status');
    status.setAttribute('role', 'status');
    image.addEventListener('load', () => { image.hidden = false; stage.setAttribute('aria-busy', 'false'); });
    image.addEventListener('error', () => { image.hidden = true; stage.setAttribute('aria-busy', 'false'); status.textContent = gallery.dataset.error; });
    stage.append(image, status);
    caption = element('p', 'rl-lightbox-caption');
    caption.id = 'rl-lightbox-caption';
    dialog.setAttribute('aria-describedby', caption.id);
    const controls = element('div', 'rl-lightbox-controls');
    previous = element('button', 'rl-lightbox-previous');
    next = element('button', 'rl-lightbox-next');
    previous.type = next.type = 'button';
    original = element('a', 'rl-lightbox-original');
    original.target = '_blank';
    original.rel = 'noopener';
    controls.append(previous, next);
    dialog.append(toolbar, stage, caption, controls, original);
    document.body.append(dialog);
    close.addEventListener('click', () => dialog.close());
    previous.addEventListener('click', () => show(index - 1));
    next.addEventListener('click', () => show(index + 1));
    dialog.addEventListener('keydown', event => {
      if (event.key === 'Tab') {
        const controls = [...dialog.querySelectorAll('button, a[href]')].filter(node => node.getClientRects().length && !node.disabled);
        const first = controls[0], last = controls[controls.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        return;
      }
      if (event.altKey || event.ctrlKey || event.metaKey) return;
      if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
        event.preventDefault();
        show(index + (event.key === 'ArrowLeft' ? -1 : 1));
      }
    });
    dialog.addEventListener('click', event => {
      if (event.target !== dialog) return;
      const rect = dialog.getBoundingClientRect();
      if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
    });
    dialog.addEventListener('close', () => {
      document.documentElement.classList.remove('rl-gallery-open');
      image.removeAttribute('src');
      touch = null;
      if (opener?.isConnected) opener.focus({preventScroll: true});
    });
    stage.addEventListener('pointerdown', event => {
      touch = event.pointerType === 'touch' && event.isPrimary ? {x: event.clientX, y: event.clientY, id: event.pointerId} : null;
    });
    stage.addEventListener('pointercancel', () => { touch = null; });
    stage.addEventListener('pointerup', event => {
      if (!touch || touch.id !== event.pointerId) return;
      const dx = event.clientX - touch.x, dy = event.clientY - touch.y;
      touch = null;
      if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy) * 1.4) show(index + (dx < 0 ? 1 : -1));
    });
  }
  document.addEventListener('click', event => {
    const link = event.target.closest?.('[data-rl-gallery] a[href]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    gallery = link.closest('[data-rl-gallery]');
    links = [...gallery.querySelectorAll('a[href]')];
    opener = link;
    if (!dialog) createDialog();
    dialog.setAttribute('aria-label', gallery.dataset.label);
    dialog.querySelector('.rl-lightbox-close').setAttribute('aria-label', gallery.dataset.close);
    previous.textContent = '← ' + gallery.dataset.previous;
    next.textContent = gallery.dataset.next + ' →';
    original.textContent = gallery.dataset.original;
    show(links.indexOf(link));
    document.documentElement.classList.add('rl-gallery-open');
    dialog.showModal();
  });
})();
