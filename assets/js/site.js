/* Something About Pets – small site helpers */
(function () {
  'use strict';

  // Contact form: send without leaving the page (falls back to a normal post without JS).
  document.querySelectorAll('.sap-ajax-form').forEach(function (form) {
    var status = form.querySelector('.sap-form-status');
    var button = form.querySelector('[type="submit"]');
    form.addEventListener('submit', function (ev) {
      if (!window.fetch || !window.FormData) { return; }
      if (!form.checkValidity()) {
        ev.preventDefault();
        form.reportValidity();
        return;
      }
      ev.preventDefault();
      button.disabled = true;
      var label = button.textContent;
      button.textContent = 'Sending…';
      fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          status.innerHTML = '';
          var div = document.createElement('div');
          div.className = 'sap-alert ' + (res.ok ? 'sap-alert-success' : 'sap-alert-error');
          div.textContent = res.message || (res.ok ? 'Thanks!' : 'Something went wrong.');
          status.appendChild(div);
          if (res.ok) { form.reset(); }
        })
        .catch(function () {
          status.innerHTML = '<div class="sap-alert sap-alert-error">Could not send right now. Please email us instead.</div>';
        })
        .finally(function () {
          button.disabled = false;
          button.textContent = label;
        });
    });
  });

  // Confirm dangerous actions (cancel booking, delete pet…)
  document.addEventListener('submit', function (ev) {
    var msg = ev.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { ev.preventDefault(); }
  });
})();
