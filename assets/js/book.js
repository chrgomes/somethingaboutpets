/* Booking page: switch between time-slot and date-range services and load live availability. */
(function () {
  'use strict';
  var form = document.getElementById('book-form');
  if (!form) { return; }
  var url = form.getAttribute('data-slots-url');
  var date = form.querySelector('#date');
  var endDate = form.querySelector('#end_date');
  var list = form.querySelector('#slot-list');
  var stayInfo = form.querySelector('#stay-info');
  var defaultStayText = stayInfo ? stayInfo.textContent : '';
  var reqId = 0;

  function current() { return form.querySelector('input[name="service"]:checked'); }
  function kind() { var c = current(); return c ? c.getAttribute('data-kind') : 'slot'; }

  function setMode() {
    var isStay = kind() === 'stay';
    form.classList.toggle('is-stay', isStay);
    endDate.required = isStay;
    if (isStay && date.value && (!endDate.value || endDate.value <= date.value)) {
      var d = new Date(date.value + 'T12:00:00');
      d.setDate(d.getDate() + 1);
      endDate.value = d.toISOString().slice(0, 10);
    }
    endDate.min = date.value || endDate.min;
  }

  function message(el, text) {
    el.innerHTML = '';
    var p = document.createElement('p');
    p.className = 'sap-muted';
    p.textContent = text;
    el.appendChild(p);
  }

  function refresh() {
    setMode();
    var svc = current();
    if (!svc || !date.value) {
      if (kind() === 'slot') { message(list, 'Choose a date to see available times.'); }
      return;
    }
    var params = new URLSearchParams({ service: svc.value, date: date.value });
    if (kind() === 'stay') {
      if (!endDate.value) { return; }
      params.set('end_date', endDate.value);
    } else {
      message(list, 'Checking availability…');
    }
    var mine = ++reqId;
    fetch(url + '?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (mine !== reqId) { return; }
        if (res.kind === 'stay') {
          stayInfo.textContent = res.message || defaultStayText;
          stayInfo.classList.toggle('sap-ok', !!res.ok);
          stayInfo.classList.toggle('sap-bad', !res.ok);
          return;
        }
        var chosen = (form.querySelector('input[name="time"]:checked') || {}).value;
        if (!res.slots || !res.slots.length) { message(list, res.message || 'No times available.'); return; }
        list.innerHTML = '';
        res.slots.forEach(function (s) {
          var label = document.createElement('label');
          label.className = 'sap-slot';
          var input = document.createElement('input');
          input.type = 'radio'; input.name = 'time'; input.value = s.value; input.required = true;
          if (s.value === chosen) { input.checked = true; }
          var span = document.createElement('span');
          span.textContent = s.label;
          label.appendChild(input); label.appendChild(span);
          list.appendChild(label);
        });
      })
      .catch(function () { message(list, 'Could not load times. Please refresh the page.'); });
  }

  form.addEventListener('change', function (ev) {
    if (ev.target.name === 'service' || ev.target === date || ev.target === endDate) { refresh(); }
  });
  form.addEventListener('submit', function (ev) {
    if (!form.querySelector('input[name="pets[]"]:checked')) {
      ev.preventDefault();
      alert('Please choose which pet(s) this booking is for.');
      return;
    }
    if (kind() === 'slot' && !form.querySelector('input[name="time"]:checked')) {
      ev.preventDefault();
      alert('Please choose a time.');
    }
  });
  setMode();
  if (date.value) { refresh(); }
})();
