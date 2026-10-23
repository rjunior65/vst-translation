/* ============================================================
   VST Ponto — service worker (scope /sistema/)
   - pages always come from the network (punch times, hours and messages
     must be live); when offline, a friendly "no connection" page
   - styles, scripts and icons: served from cache, refreshed in background
   - push: the push carries no content; the worker asks the server for the
     pending notifications of the signed-in person and shows them
   ============================================================ */
var VERSION = 'v1';
var STATIC = 'vst-ponto-static-' + VERSION;
var PRECACHE = ['offline.html', 'assets/app.css', 'assets/app.js', 'assets/ponto.js', 'assets/chat.js',
                '../images/icon-192.png', '../images/logo/vst-logo.svg', '../images/favicon.svg'];

self.addEventListener('install', function (e) {
  e.waitUntil(caches.open(STATIC).then(function (c) { return c.addAll(PRECACHE); }).then(function () { return self.skipWaiting(); }));
});

self.addEventListener('activate', function (e) {
  e.waitUntil(caches.keys().then(function (keys) {
    return Promise.all(keys.filter(function (k) { return k.indexOf('vst-ponto-') === 0 && k !== STATIC; })
                           .map(function (k) { return caches.delete(k); }));
  }).then(function () { return self.clients.claim(); }));
});

self.addEventListener('fetch', function (e) {
  var req = e.request;
  if (req.method !== 'GET') return;
  var url = new URL(req.url);
  if (url.origin !== location.origin) return;

  if (req.mode === 'navigate') {
    e.respondWith(fetch(req).catch(function () { return caches.match('offline.html'); }));
    return;
  }
  if (/\/sistema\/assets\/|\/images\//.test(url.pathname)) {
    e.respondWith(caches.open(STATIC).then(function (cache) {
      return cache.match(req).then(function (hit) {
        var net = fetch(req).then(function (res) { if (res.ok) cache.put(req, res.clone()); return res; })
                            .catch(function () { return hit; });
        return hit || net;
      });
    }));
  }
  // everything else (API, attachments): straight to the network, never cached
});

/* ---- Push ---- */
var ICON = '../images/icon-192.png';
self.addEventListener('push', function (e) {
  e.waitUntil(
    fetch('api/notificacoes.php?action=pendentes', { credentials: 'include', cache: 'no-store' })
      .then(function (r) { return r.ok ? r.json() : { items: [] }; })
      .catch(function () { return { items: [] }; })
      .then(function (j) {
        var items = (j && j.items) || [];
        if (!items.length) {
          // Signed out on this phone, or already seen: still a notification is required.
          return self.registration.showNotification('VST Ponto', {
            body: (j && j.generic) || 'Você tem uma nova notificação.', icon: ICON, badge: ICON, tag: 'vst-generic', data: { url: './' }
          });
        }
        return Promise.all(items.map(function (n) {
          return self.registration.showNotification(n.title, {
            body: n.body, icon: ICON, badge: ICON, tag: n.tag || ('vst-' + n.id), renotify: true, data: { url: n.url || './' }
          });
        }));
      })
  );
});

self.addEventListener('notificationclick', function (e) {
  e.notification.close();
  var target = new URL((e.notification.data && e.notification.data.url) || './', self.registration.scope).href;
  e.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (list) {
    for (var i = 0; i < list.length; i++) {
      if (list[i].url.indexOf(self.registration.scope) === 0 && 'focus' in list[i]) {
        return list[i].navigate(target).then(function (c) { return c && c.focus(); });
      }
    }
    return self.clients.openWindow(target);
  }));
});
