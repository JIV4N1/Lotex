import 'bootstrap/js/dist/collapse';

document.querySelectorAll('[data-gallery]').forEach((gallery) => {
    const main = gallery.querySelector('.gallery-main');
    const buttons = gallery.querySelectorAll('[data-gallery-src]');
    buttons.forEach((button) => button.addEventListener('click', () => {
        main.src = button.dataset.gallerySrc;
        main.alt = button.dataset.galleryAlt;
        buttons.forEach((item) => item.setAttribute('aria-pressed', String(item === button)));
    }));
});
