/* VST Ponto — app shell: service worker, "install the app" card, push notifications. */
(function () {
  'use strict';
  var me = document.currentScript;
  var base = new URL('..', me.src).pathname;            // "/sistema/"
  var csrfMeta = document.querySelector('meta[name="csrf"]');
  var csrf = csrfMeta ? csrfMeta.content : '';
  var ua = navigator.userAgent;
  var isIOS = /iphone|ipad|ipod/i.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);
  var standalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || navigator.standalone === true;
  function $(id) { return document.getElementById(id); }
  function store(k, v) { try { if (v === undefined) return localStorage.getItem(k); localStorage.setItem(k, v); } catch (e) { return null; } }

  if (!('serviceWorker' in navigator)) return;
  navigator.serviceWorker.register(base + 'sw.js', { scope: base }).catch(function () {});

  /* ---- Install card ---- */
  var installCard = $('install-card');
  var deferred = null;
  function showInstall(ios) {
    if (!installCard || standalone || store('pt_install_dismissed')) return;
    installCard.hidden = false;
    $('install-ios').hidden = !ios;
    $('install-btn').hidden = ios;
  }
  window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); deferred = e; showInstall(false); });
  if (isIOS && !standalone) showInstall(true);
  if (installCard) {
    $('install-btn').addEventListener('click', function () {
      if (!deferred) return;
      deferred.prompt();
      deferred.userChoice.then(function () { deferred = null; installCard.hidden = true; });
    });
    $('install-close').addEventListener('click', function () { store('pt_install_dismissed', '1'); installCard.hidden = true; });
  }
  window.addEventListener('appinstalled', function () { if (installCard) installCard.hidden = true; });

  /* ---- Push notifications ---- */
  var pushCard = $('push-card');
  if (!pushCard) return;
  var supported = 'PushManager' in window && 'Notification' in window;
  if (!supported || (isIOS && !standalone)) return;     // iPhone: only once installed on the home screen

  function api(action, body) {
    return fetch(base + 'api/notificacoes.php?action=' + action, {
      method: body ? 'POST' : 'GET', credentials: 'same-origin',
      headers: body ? { 'Content-Type': 'application/json' } : {},
      body: body ? JSON.stringify(Object.assign({ csrf: csrf }, body)) : undefined
    }).then(function (r) { return r.json(); });
  }
  function keyBytes(b64) {
    var s = atob((b64 + '='.repeat((4 - b64.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/'));
    var a = new Uint8Array(s.length);
    for (var i = 0; i < s.length; i++) a[i] = s.charCodeAt(i);
    return a;
  }
  function show(state) {
    pushCard.hidden = false;
    ['push-off', 'push-on', 'push-denied'].forEach(function (id) { $(id).hidden = id !== 'push-' + state; });
  }
  function save(sub) { var j = sub.toJSON(); return api('assinar', { endpoint: j.endpoint, keys: j.keys }); }
  function msg(text) { $('push-msg').textContent = text || ''; }

  navigator.serviceWorker.ready.then(function (reg) {
    return reg.pushManager.getSubscription().then(function (sub) {
      if (sub) { save(sub); show('on'); return; }      // re-save: the phone may now belong to someone else
      show(Notification.permission === 'denied' ? 'denied' : 'off');
    });
  });

  $('push-enable').addEventListener('click', function (ev) {
    var btn = ev.target; btn.disabled = true; msg('');
    Notification.requestPermission().then(function (perm) {
      if (perm !== 'granted') { show(perm === 'denied' ? 'denied' : 'off'); return; }
      return Promise.all([navigator.serviceWorker.ready, api('chave')]).then(function (r) {
        if (!r[1].ok) throw new Error(r[1].message);
        return r[0].pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyBytes(r[1].publicKey) });
      }).then(save).then(function () { show('on'); });
    }).catch(function (e) { msg(e && e.message ? e.message : ''); }).then(function () { btn.disabled = false; });
  });
  $('push-test').addEventListener('click', function () {
    api('teste', {}).then(function (j) { msg(j.message || ''); });
  });
  $('push-disable').addEventListener('click', function () {
    navigator.serviceWorker.ready.then(function (reg) { return reg.pushManager.getSubscription(); }).then(function (sub) {
      if (!sub) return show('off');
      return api('cancelar', { endpoint: sub.endpoint }).then(function () { return sub.unsubscribe(); }).then(function () { show('off'); });
    });
  });
})();
