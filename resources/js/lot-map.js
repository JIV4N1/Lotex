document.querySelectorAll('[data-lot-map]').forEach((map) => {
    const planImage = map.querySelector('[data-plan-image]');
    let zoom = 1;
    map.querySelectorAll('[data-plan-zoom]').forEach((button) => {
        button.addEventListener('click', () => {
            const direction = Number(button.dataset.planZoom);
            zoom = direction === 0 ? 1 : Math.min(4, Math.max(1, zoom + direction * .5));
            planImage.style.width = `${zoom * 100}%`;
        });
    });
    const picker = map.querySelector('[data-map-picker]');
    const summary = map.querySelector('[data-map-summary]');
    const detailLink = map.querySelector('[data-map-link]');
    const visitLink = map.querySelector('[data-map-visit]');
    const plots = [...map.querySelectorAll('[data-map-lot]')];

    const choose = (plot, scroll = false, reveal = false) => {
        plots.forEach((item) => {
            item.classList.toggle('is-selected', item === plot);
            item.setAttribute('aria-pressed', String(item === plot));
        });
        map.querySelectorAll('[data-shape-key]').forEach(shape => {
            const selected = shape.dataset.shapeKey === plot.dataset.mapLot;
            shape.classList.toggle('is-selected', selected);
            shape.setAttribute('aria-pressed', String(selected));
        });
        picker.value = plot.dataset.mapLot;
        const heading = document.createElement('h3');
        heading.className = 'h5 mb-2';
        heading.textContent = plot.dataset.mapTitle;
        const details = document.createElement('p');
        details.className = 'mb-0';
        details.textContent = `${plot.dataset.mapArea} · ${plot.dataset.mapPrice} · ${plot.dataset.mapStatus}`;
        const measures = document.createElement('p');
        measures.className = 'small mt-3 mb-0';
        measures.textContent = `Frente: ${plot.dataset.mapFront} � Fondo: ${plot.dataset.mapDepth}`;
        summary.replaceChildren(heading, details, measures);
        visitLink.href = `${plot.dataset.mapUrl}#solicitar-visita`;
        visitLink.hidden = plot.dataset.mapStatus !== 'Disponible';
        detailLink.href = plot.dataset.mapUrl;
        detailLink.hidden = false;
        if (scroll && !planImage) plot.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        if (reveal && window.matchMedia('(max-width: 1199.98px)').matches) {
            map.querySelector('.lot-map-info').scrollIntoView({ block: 'nearest' });
        }
    };

    plots.forEach((plot) => {
        plot.addEventListener('click', () => choose(plot, false, true));
        plot.addEventListener('focus', () => choose(plot));
    });
    map.querySelectorAll('[data-shape-key]').forEach((shape) => {
        const activate = () => {
            const plot = plots.find(item => item.dataset.mapLot === shape.dataset.shapeKey);
            if (plot) choose(plot, false, true);
        };
        shape.addEventListener('click', activate);
        shape.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); activate(); }
        });
    });
    picker.addEventListener('change', () => {
        const plot = plots.find((item) => item.dataset.mapLot === picker.value);
        if (plot) choose(plot, false, true);
        else {
            detailLink.hidden = true; visitLink.hidden = true;
            summary.textContent = 'Selecciona manzana y lote para consultar sus datos actuales.';
            map.querySelectorAll('.is-selected').forEach(item => { item.classList.remove('is-selected'); item.setAttribute('aria-pressed', 'false'); });
        }
    });
});
