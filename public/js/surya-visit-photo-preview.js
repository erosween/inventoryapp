(() => {
    const dialog = document.getElementById('surya-visit-photo-preview');
    if (!dialog) return;
    const image = dialog.querySelector('img');
    const stage = dialog.querySelector('[data-photo-stage]');
    const title = dialog.querySelector('[data-photo-caption]');
    const reset = dialog.querySelector('[data-photo-reset]');
    let zoom = 1;
    let opener;
    const render = () => {
        if (image.naturalWidth && image.naturalHeight && stage.clientHeight) {
            const fit = Math.min(1, Math.max(1,stage.clientWidth - 24) / image.naturalWidth, Math.max(1,stage.clientHeight - 24) / image.naturalHeight);
            image.style.width = `${image.naturalWidth * fit * zoom}px`;
        }
        reset.textContent = zoom === 1 ? 'Pas layar' : `${Math.round(zoom * 100)}%`;
    };
    document.addEventListener('click', event => {
        const link = event.target.closest('a');
        const button = event.target.closest('.photo-open[data-photo-src]');
        const thumbnail = link?.querySelector('img.visit-photo-thumb')
            || link?.matches('.outlet-branding-photo,.map-detail-branding a') && link.querySelector('img')
            || button?.querySelector('img');
        if (!thumbnail) return;
        const source = button?.dataset.photoSrc || link?.href;
        if (!source) return;
        event.preventDefault();
        opener = button || link;
        zoom = 1;
        image.hidden = false;
        dialog.querySelector('[data-photo-error]').hidden = true;
        image.src = source;
        image.alt = title.textContent = button?.dataset.photoTitle || thumbnail.alt || 'Foto Visit';
        render();
        dialog.showModal();
        render();
        stage.scrollTo(0, 0);
    });
    image.addEventListener('load', render);
    new ResizeObserver(() => { if (dialog.open) render(); }).observe(stage);
    image.addEventListener('error', () => {
        image.hidden = true;
        dialog.querySelector('[data-photo-error]').hidden = false;
    });
    dialog.querySelector('[data-photo-in]').onclick = () => { zoom = Math.min(4, zoom + .25); render(); };
    dialog.querySelector('[data-photo-out]').onclick = () => { zoom = Math.max(1, zoom - .25); render(); };
    reset.onclick = () => { zoom = 1; render(); stage.scrollTo(0, 0); };
    dialog.querySelector('[data-photo-close]').onclick = () => dialog.close();
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => {
        image.removeAttribute('src');
        opener?.focus({preventScroll: true});
    });
})();
