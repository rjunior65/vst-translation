/* VST Ponto — conversation page: send without reloading, poll for new messages. */
(function () {
  'use strict';
  var C = window.CHAT;
  if (!C) return;
  var T = C.i18n;
  var box = document.getElementById('chat');
  var form = document.getElementById('composer');
  var body = document.getElementById('body');
  var files = document.getElementById('files');
  var picked = document.getElementById('picked');
  var errBox = document.getElementById('chat-error');
  var sendBtn = document.getElementById('send');
  var end = document.getElementById('end');
  var last = C.last;
  var busy = false;

  function lastDay() {
    var els = box.querySelectorAll('[data-day]');
    return els.length ? els[els.length - 1].getAttribute('data-day') : '';
  }
  function nearBottom() { return window.innerHeight + window.scrollY >= document.body.scrollHeight - 160; }
  function toBottom() { end.scrollIntoView({ block: 'end' }); }
  function append(html, scroll) {
    if (!html) return;
    var empty = document.getElementById('chat-empty');
    if (empty) empty.remove();
    // A poll and a send can both return the same new message: skip what is already shown.
    var tpl = document.createElement('template');
    tpl.innerHTML = html;
    Array.prototype.slice.call(tpl.content.children).forEach(function (el) {
      var id = el.getAttribute('data-id');
      var day = el.classList.contains('day-sep') ? el.getAttribute('data-day') : null;
      if (id && box.querySelector('.msg[data-id="' + id + '"]')) return;
      if (day && box.querySelector('.day-sep[data-day="' + day + '"]')) return;
      end.parentNode.insertBefore(el, end);
    });
    if (scroll) toBottom();
  }
  function showError(msg) { errBox.textContent = msg; errBox.hidden = !msg; }
  function url(action) {
    return C.api + '?action=' + action + '&c=' + C.id + '&after=' + last + '&day=' + encodeURIComponent(lastDay());
  }

  toBottom();

  /* ---- composer: auto-grow, Enter sends (Shift+Enter = new line) on desktop ---- */
  function grow() { body.style.height = 'auto'; body.style.height = Math.min(body.scrollHeight, 160) + 'px'; }
  body.addEventListener('input', grow);
  body.addEventListener('keydown', function (e) {
    var touch = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
    if (e.key === 'Enter' && !e.shiftKey && !touch) { e.preventDefault(); form.requestSubmit ? form.requestSubmit() : form.submit(); }
  });

  files.addEventListener('change', function () {
    showError('');
    var list = Array.prototype.slice.call(files.files);
    if (list.length > C.maxFiles) { showError(T.count); files.value = ''; list = []; }
    if (list.some(function (f) { return f.size > C.maxBytes; })) { showError(T.size); files.value = ''; list = []; }
    picked.innerHTML = '';
    list.forEach(function (f) {
      var s = document.createElement('span');
      s.className = 'tag';
      s.textContent = '📎 ' + f.name;
      picked.appendChild(s);
    });
    picked.hidden = !list.length;
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (busy) return;
    if (!body.value.trim() && !files.files.length) return;
    busy = true;
    showError('');
    sendBtn.disabled = true;
    sendBtn.textContent = T.sending;
    fetch(url('send'), { method: 'POST', credentials: 'same-origin', body: new FormData(form) })
      .then(function (r) { return r.json().catch(function () { return { ok: false }; }); })
      .then(function (j) {
        if (j.ok) {
          append(j.html, true);
          last = Math.max(last, j.last || 0);
          form.reset();
          picked.hidden = true;
          picked.innerHTML = '';
          grow();
        } else {
          showError(j.message || T.failed);
        }
      })
      .catch(function () { showError(T.failed); })
      .then(function () { busy = false; sendBtn.disabled = false; sendBtn.textContent = T.send; body.focus(); });
  });

  /* ---- polling: every 5 s while visible, 30 s in the background ---- */
  var timer = null;
  function poll() {
    if (busy) return schedule();
    fetch(url('poll'), { credentials: 'same-origin' })
      .then(function (r) { if (r.status === 401) { location.href = 'login.php'; } return r.json(); })
      .then(function (j) {
        if (j && j.ok && j.html) {
          var stick = nearBottom();
          append(j.html, stick);
          last = Math.max(last, j.last);
        }
        if (j && j.ok && window.PT_setUnread) window.PT_setUnread(j.unread);
      })
      .catch(function () {})
      .then(schedule);
  }
  function schedule() { clearTimeout(timer); timer = setTimeout(poll, document.hidden ? 30000 : 5000); }
  document.addEventListener('visibilitychange', function () { if (!document.hidden) { clearTimeout(timer); poll(); } });
  schedule();
})();
