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
      if (on) { if (!btn.dataset.label) btn.dataset.label = btn.textContent; btn.textContent = T.sending; }
      else if (btn.dataset.label) { btn.textContent = btn.dataset.label; }
    }
  }

  function send(type, btn) {
    errBox.hidden = true;
    busy(true, btn);
    var body = { csrf: C.csrf, type: type, source: standalone ? 'app' : 'web' };
    if (pos) { body.lat = pos.lat; body.lng = pos.lng; body.accuracy = pos.accuracy; }
    fetch(C.api, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    }).then(function (r) {
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

  buttons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var type = btn.dataset.type;
      if (type === 'out' && !window.confirm(T.confirm_out)) return;
      // Give the GPS a moment if it hasn't answered yet (first fix can be slow).
      if (!pos && !gpsError && 'geolocation' in navigator) {
        busy(true, btn);
        navigator.geolocation.getCurrentPosition(function (p) {
          pos = { lat: p.coords.latitude, lng: p.coords.longitude, accuracy: p.coords.accuracy };
          showGps();
          send(type, btn);
        }, function () { send(type, btn); }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 10000 });
        return;
      }
      send(type, btn);
    });
  });
})();
