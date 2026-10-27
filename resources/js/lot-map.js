document.querySelectorAll('[data-lot-map]').forEach((map) => {
    const picker = map.querySelector('[data-map-picker]');
    const summary = map.querySelector('[data-map-summary]');
    const detailLink = map.querySelector('[data-map-link]');
    const plots = [...map.querySelectorAll('[data-map-lot]')];

    const choose = (plot, scroll = false, reveal = false) => {
        plots.forEach((item) => {
            item.classList.toggle('is-selected', item === plot);
            item.setAttribute('aria-pressed', String(item === plot));
        });
        picker.value = plot.dataset.mapLot;
        const heading = document.createElement('h3');
        heading.className = 'h5 mb-2';
        heading.textContent = plot.dataset.mapTitle;
        const details = document.createElement('p');
        details.className = 'mb-0';
        details.textContent = `${plot.dataset.mapArea} · ${plot.dataset.mapPrice} · ${plot.dataset.mapStatus}`;
        summary.replaceChildren(heading, details);
        detailLink.href = plot.dataset.mapUrl;
        detailLink.hidden = false;
        if (scroll) plot.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        if (reveal && window.matchMedia('(max-width: 1199.98px)').matches) {
            summary.scrollIntoView({ block: 'nearest' });
        }
    };

    plots.forEach((plot) => {
        plot.addEventListener('click', () => choose(plot, false, true));
        plot.addEventListener('focus', () => choose(plot));
    });
    picker.addEventListener('change', () => {
        const plot = plots.find((item) => item.dataset.mapLot === picker.value);
        if (plot) choose(plot, true);
    });
});
