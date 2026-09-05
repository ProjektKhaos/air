'use strict';

(() => {
    const A = window.CMAW;
    document.querySelectorAll('.home-station-button').forEach(button => button.addEventListener('click', () => {
        A.setHome(button.dataset.code);
        document.querySelectorAll('.station-card').forEach(card => card.classList.remove('is-home'));
        button.closest('.station-card')?.classList.add('is-home');
    }));

    let map;
    let group;
    const markers = [];
    let activeFilter = 'all';
    const matches = value => activeFilter === 'all' || value === activeFilter;
    const applyFilter = () => {
        document.querySelectorAll('[data-provider-card]').forEach(card => card.hidden = !matches(card.dataset.providerCard));
        if (!group) return;
        group.clearLayers();
        markers.filter(item => matches(item.classification)).forEach(item => group.addLayer(item.marker));
        if (group.getLayers().length) map.fitBounds(group.getBounds(), {padding: [24, 24], maxZoom: 14});
    };
    const init = () => {
        if (map) {
            setTimeout(() => map.invalidateSize(), 0);
            return;
        }
        const element = document.getElementById('station-map');
        if (!element || !window.L) return;
        map = L.map(element).setView([18.81, 98.98], 11);
        L.tileLayer(element.dataset.tileUrl, {maxZoom: 18, attribution: '© OpenStreetMap contributors', crossOrigin: true}).addTo(map);
        group = L.markerClusterGroup();
        document.querySelectorAll('[data-map-station]').forEach(node => {
            const shape = node.dataset.classification === 'official' ? 'official' : 'local';
            const icon = L.divIcon({
                className: 'provider-marker-wrap',
                html: `<span class="provider-marker ${shape} status-${node.dataset.status} freshness-${node.dataset.freshness}" aria-hidden="true"></span>`,
                iconSize: [34, 34],
                iconAnchor: [17, 17],
            });
            const marker = L.marker([Number(node.dataset.latitude), Number(node.dataset.longitude)], {
                icon,
                title: node.dataset.name,
                keyboard: true,
                alt: node.dataset.name,
            });
            const popup = document.createElement('div');
            popup.className = 'station-map-popup';
            const title = document.createElement('strong');
            title.textContent = node.dataset.name;
            const source = document.createElement('span');
            source.textContent = `${shape === 'official' ? element.dataset.officialLabel : element.dataset.localLabel} · ${node.dataset.source}`;
            const value = document.createElement('span');
            value.textContent = shape === 'official' ? `TH AQI ${node.dataset.aqi || '—'}` : `PM2.5 ${node.dataset.pm25 || '—'} µg/m³`;
            const second = document.createElement('span');
            second.textContent = `PM10 ${node.dataset.pm10 || '—'} µg/m³`;
            const updated = document.createElement('span');
            updated.textContent = element.dataset.updatedLabel.replace('{time}', A.time(node.dataset.time));
            const link = document.createElement('a');
            link.href = node.dataset.url;
            link.textContent = element.dataset.detailsLabel;
            popup.append(title, source, value, second, updated, link);
            marker.bindPopup(popup);
            markers.push({marker, classification: node.dataset.classification});
        });
        element.dataset.markerCount = String(markers.length);
        group.addTo(map);
        applyFilter();
    };

    document.querySelectorAll('[data-provider-filter]').forEach(button => button.addEventListener('click', () => {
        activeFilter = button.dataset.providerFilter;
        document.querySelectorAll('[data-provider-filter]').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
        applyFilter();
    }));
    const activate = value => {
        if (!['list', 'map'].includes(value)) value = 'list';
        document.querySelectorAll('[data-view-panel]').forEach(panel => panel.hidden = panel.dataset.viewPanel !== value);
        document.querySelectorAll('[data-station-view]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.stationView === value)));
        try { localStorage.setItem('cmaw-map-view', value); } catch {}
        if (value === 'map') init();
    };
    document.querySelectorAll('[data-station-view]').forEach(button => button.addEventListener('click', () => activate(button.dataset.stationView)));
    let initial = 'list';
    try { initial = localStorage.getItem('cmaw-map-view') || 'list'; } catch {}
    activate(initial);
})();
