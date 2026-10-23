/* VST Ponto — punch screen: server-synced clock, live GPS, punch buttons. */
(function () {
  'use strict';
  var C = window.PONTO;
  if (!C) return;
  var T = C.i18n;

  /* ---- Clock: server time, ticking locally from the offset ---- */
  var offset = C.serverNow - Date.now();
  var clock = document.getElementById('clock');
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function tick() {
    // Server time is Guyana (UTC-4); render it in that zone whatever the phone's zone is.
    var d = new Date(Date.now() + offset - 4 * 3600 * 1000);
    clock.textContent = pad(d.getUTCHours()) + ':' + pad(d.getUTCMinutes()) + ':' + pad(d.getUTCSeconds());
  }
  setInterval(tick, 1000);
  tick();

  /* ---- Live counters: worked / break / overtime keep running while open ---- */
  var K = C.counters, started = Date.now();
  function hm(s) { var m = Math.floor(Math.max(0, s) / 60); return Math.floor(m / 60) + ':' + pad(m % 60); }
  function counters() {
    var el = Math.floor((Date.now() - started) / 1000);
    var worked = K.worked + (K.state === 'working' ? el : 0);
    var brk = K.break + (K.state === 'break' ? el : 0);
    document.getElementById('c-worked').textContent = hm(worked);
    document.getElementById('c-break').textContent = hm(brk);
    var ot = document.getElementById('c-ot');
    if (ot && K.otAfter !== null) {
      var diff = worked - K.expected;
      ot.textContent = hm(diff > K.otAfter ? diff : 0);
    }
  }
  if (K.state === 'working' || K.state === 'break') setInterval(counters, 15000);

  /* ---- GPS ---- */
  var gpsLine = document.getElementById('gps');
  var pos = null;          // last good fix {lat, lng, accuracy}
  var gpsError = null;

  function distance(lat1, lng1, lat2, lng2) {
    var r = 6371000, rad = Math.PI / 180;
    var dLat = (lat2 - lat1) * rad, dLng = (lng2 - lng1) * rad;
    var a = Math.pow(Math.sin(dLat / 2), 2) + Math.cos(lat1 * rad) * Math.cos(lat2 * rad) * Math.pow(Math.sin(dLng / 2), 2);
    return Math.round(r * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
  }
  function fmt(m) { return m >= 1000 ? (m / 1000).toFixed(1).replace('.', ',') + ' km' : m + ' m'; }

  function showGps() {
    gpsLine.className = 'gps-line';
    if (!pos) {
      gpsLine.textContent = gpsError || T.locating;
      if (gpsError) gpsLine.classList.add('bad');
      return;
    }
    var acc = Math.round(pos.accuracy);
    if (!C.locations.length) {
      gpsLine.textContent = T.here.replace('{acc}', acc);
      gpsLine.classList.add('good');
      return;
    }
    var best = null;
    C.locations.forEach(function (l) {
      var d = distance(pos.lat, pos.lng, l.lat, l.lng);
      if (!best || d < best.d) best = { d: d, l: l };
    });
    var margin = Math.min(acc, C.maxAcc);
    var inside = best.d - margin <= best.l.radius;
    var text = T.distance.replace('{d}', fmt(best.d)).replace('{place}', best.l.name).replace('{acc}', acc);
    if (C.mode === 'office') text += ' — ' + (inside ? T.inside : T.outside);
    if (acc > C.maxAcc) text += ' ' + T.weak;
    gpsLine.textContent = text;
    gpsLine.classList.add(C.mode !== 'office' || inside ? 'good' : 'bad');
  }

  if (!('geolocation' in navigator)) {
    gpsError = T.unavailable;
  } else {
    navigator.geolocation.watchPosition(function (p) {
      pos = { lat: p.coords.latitude, lng: p.coords.longitude, accuracy: p.coords.accuracy };
      gpsError = null;
      showGps();
    }, function (err) {
      gpsError = err.code === 1 ? T.denied : T.unavailable;
      showGps();
    }, { enableHighAccuracy: true, maximumAge: 10000, timeout: 20000 });
  }
  showGps();

  /* ---- Punch ---- */
  var errBox = document.getElementById('punch-error');
  var buttons = Array.prototype.slice.call(document.querySelectorAll('.btn-punch'));
  var standalone = window.matchMedia && window.matchMedia('(display-mode: standalone)').matches;

  function busy(on, btn) {
    buttons.forEach(function (b) { b.disabled = on; });
    if (btn) {
      if (on) { if (!btn.dataset.html) btn.dataset.html = btn.innerHTML; btn.textContent = T.sending; }
      else if (btn.dataset.html) { btn.innerHTML = btn.dataset.html; }
    }
  }

  // Sent as a form so a selfie can go along; the time is set by the server.
  function send(type, btn, photo) {
    errBox.hidden = true;
    busy(true, btn);
    var body = new FormData();
    body.append('csrf', C.csrf);
    body.append('type', type);
    body.append('source', standalone ? 'app' : 'web');
    if (pos) { body.append('lat', pos.lat); body.append('lng', pos.lng); body.append('accuracy', pos.accuracy); }
    if (photo) body.append('photo', photo);
    fetch(C.api, { method: 'POST', credentials: 'same-origin', body: body }).then(function (r) {
      return r.json().catch(function () { return { ok: false }; }).then(function (j) { return { status: r.status, j: j }; });
    }).then(function (res) {
      if (res.j.ok || res.j.error === 'sequence') { location.reload(); return; }
      if (res.status === 401) { location.href = 'login.php'; return; }
      errBox.textContent = res.j.message || T.failed;
      errBox.hidden = false;
      busy(false, btn);
    }).catch(function () {
      errBox.textContent = T.failed;
      errBox.hidden = false;
      busy(false, btn);
    });
  }

  // Give the GPS a moment if it hasn't answered yet (first fix can be slow).
  function locateThenSend(type, btn, photo) {
    if (!pos && !gpsError && 'geolocation' in navigator) {
      busy(true, btn);
      navigator.geolocation.getCurrentPosition(function (p) {
        pos = { lat: p.coords.latitude, lng: p.coords.longitude, accuracy: p.coords.accuracy };
        showGps();
        send(type, btn, photo);
      }, function () { send(type, btn, photo); }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 10000 });
      return;
    }
    send(type, btn, photo);
  }

  /* ---- Selfie (when the admin turned it on): opens the front camera first ---- */
  var photoInput = document.getElementById('punch-photo');
  var photoOpt = document.getElementById('photo-opt');
  var pending = null;
  if (photoInput) {
    photoInput.addEventListener('change', function () {
      var f = photoInput.files[0];
      var p = pending; pending = null;
      photoInput.value = '';
      if (!p) return;
      if (!f && C.photo === 'required') { errBox.textContent = T.photo_needed; errBox.hidden = false; return; }
      locateThenSend(p.type, p.btn, f || null);
    });
  }

  buttons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var type = btn.dataset.type;
      if (type === 'out' && !window.confirm(T.confirm_out)) return;
      var wantPhoto = photoInput && (C.photo === 'required' || (photoOpt && photoOpt.checked));
      if (wantPhoto) { pending = { type: type, btn: btn }; photoInput.click(); return; }
      locateThenSend(type, btn, null);
    });
  });
})();
