/* Service Worker de Ahorro Eco (RNF-06).
 * - Guarda en caché el "cascarón" estático (estilos, logo, íconos) para que la app abra rápido.
 * - Las páginas PHP y la API NO se guardan: tienen datos personales y sesión.
 * - Si no hay conexión al abrir una página, muestra offline.html. */
var VERSION = 'ahorro-eco-v1';
var ESTATICOS = ['offline.html', 'nc_assets/css/app.css', 'nc_assets/css/style.css', 'image_2f3ead.png',
                 'icons/icon-192.png', 'icons/icon-512.png'];

self.addEventListener('install', function (e) {
    e.waitUntil(caches.open(VERSION).then(function (c) { return c.addAll(ESTATICOS); }).then(function () { return self.skipWaiting(); }));
});

self.addEventListener('activate', function (e) {
    e.waitUntil(caches.keys().then(function (claves) {
        return Promise.all(claves.filter(function (k) { return k !== VERSION; }).map(function (k) { return caches.delete(k); }));
    }).then(function () { return self.clients.claim(); }));
});

self.addEventListener('fetch', function (e) {
    var req = e.request;
    if (req.method !== 'GET') { return; }
    var url = new URL(req.url);
    if (url.origin !== location.origin) { return; }

    // Navegación: siempre a la red; sin conexión, página de aviso.
    if (req.mode === 'navigate') {
        e.respondWith(fetch(req).catch(function () { return caches.match('offline.html'); }));
        return;
    }
    // Estáticos del cascarón: primero caché, luego red.
    var estatico = /\.(css|png|jpg|jpeg|webp|svg|woff2?|ttf)$/i.test(url.pathname) && url.pathname.indexOf('/uploads/') === -1;
    if (estatico) {
        e.respondWith(caches.match(req).then(function (hit) {
            return hit || fetch(req).then(function (resp) {
                var copia = resp.clone();
                caches.open(VERSION).then(function (c) { c.put(req, copia); });
                return resp;
            });
        }));
    }
});
