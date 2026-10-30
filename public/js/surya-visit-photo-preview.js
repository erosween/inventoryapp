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
        image.style.width = `${zoom * 100}%`;
        reset.textContent = `${Math.round(zoom * 100)}%`;
    };
    document.addEventListener('click', event => {
        const link = event.target.closest('a');
        const thumbnail = link?.querySelector('img.visit-photo-thumb');
        if (!thumbnail) return;
        event.preventDefault();
        opener = link;
        zoom = 1;
        image.hidden = false;
        dialog.querySelector('[data-photo-error]').hidden = true;
        image.src = link.href;
        image.alt = title.textContent = thumbnail.alt || 'Foto Visit';
        render();
        dialog.showModal();
        stage.scrollTo(0, 0);
    });
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
