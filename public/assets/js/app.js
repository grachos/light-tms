/* Light TMS - JS de UI: autocompletado de municipio y mapa lat/long */

(function () {
    'use strict';

    /* ---------- Autocompletado de municipio (DIVIPOLA) ---------- */
    function initMunicipios() {
        document.querySelectorAll('[data-municipio]').forEach(function (caja) {
            const texto  = caja.querySelector('.mun-texto');
            const codigo = caja.querySelector('.mun-codigo');
            const lista  = caja.querySelector('.mun-lista');
            let timer = null;

            texto.addEventListener('input', function () {
                codigo.value = ''; // se invalida hasta elegir de la lista
                const q = texto.value.trim();
                clearTimeout(timer);
                if (q.length < 2) { lista.innerHTML = ''; return; }
                timer = setTimeout(function () { buscar(q); }, 220);
            });

            texto.addEventListener('blur', function () {
                // pequeño retardo para permitir el click en la opción
                setTimeout(function () { lista.innerHTML = ''; }, 180);
            });

            function buscar(q) {
                fetch('?r=municipios.buscar&q=' + encodeURIComponent(q))
                    .then(function (r) { return r.json(); })
                    .then(function (items) { pintar(items); })
                    .catch(function () { lista.innerHTML = ''; });
            }

            function pintar(items) {
                lista.innerHTML = '';
                items.forEach(function (m) {
                    const li = document.createElement('li');
                    li.textContent = m.nombre_completo + '  (' + m.codigo_rndc + ')';
                    li.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        texto.value = m.nombre_completo;
                        codigo.value = m.codigo_rndc;
                        lista.innerHTML = '';
                    });
                    lista.appendChild(li);
                });
            }
        });
    }

    /* ---------- Mapa para lat/long (Leaflet + OpenStreetMap) ---------- */
    function initMapa() {
        const cont = document.getElementById('mapa');
        if (!cont || typeof L === 'undefined') { return; }

        const inLat = document.getElementById('latitud');
        const inLon = document.getElementById('longitud');
        const lat0 = parseFloat(inLat.value) || 4.6097;   // Bogotá por defecto
        const lon0 = parseFloat(inLon.value) || -74.0817;

        const map = L.map('mapa').setView([lat0, lon0], inLat.value ? 15 : 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        let marker = inLat.value
            ? L.marker([lat0, lon0], { draggable: true }).addTo(map)
            : null;

        function fijar(lat, lon) {
            inLat.value = lat.toFixed(8);
            inLon.value = lon.toFixed(8);
            if (marker) { marker.setLatLng([lat, lon]); }
            else {
                marker = L.marker([lat, lon], { draggable: true }).addTo(map);
                marker.on('dragend', function () {
                    const p = marker.getLatLng();
                    fijar(p.lat, p.lng);
                });
            }
        }

        map.on('click', function (e) { fijar(e.latlng.lat, e.latlng.lng); });
        if (marker) {
            marker.on('dragend', function () {
                const p = marker.getLatLng();
                fijar(p.lat, p.lng);
            });
        }

        // Buscar dirección (Nominatim, gratis)
        const btn = document.getElementById('mapa-buscar-btn');
        const inp = document.getElementById('mapa-buscar');
        if (btn && inp) {
            btn.addEventListener('click', function () {
                const q = inp.value.trim();
                if (!q) { return; }
                fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=co&q=' + encodeURIComponent(q))
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res && res.length) {
                            const lat = parseFloat(res[0].lat), lon = parseFloat(res[0].lon);
                            map.setView([lat, lon], 16);
                            fijar(lat, lon);
                        } else {
                            alert('No se encontró la dirección.');
                        }
                    });
            });
        }

        // Enlace "abrir en Google Maps"
        const gmap = document.getElementById('abrir-google-maps');
        if (gmap) {
            gmap.addEventListener('click', function (e) {
                e.preventDefault();
                const lat = inLat.value || lat0, lon = inLon.value || lon0;
                window.open('https://www.google.com/maps?q=' + lat + ',' + lon, '_blank');
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initMunicipios();
        initMapa();
    });
})();
