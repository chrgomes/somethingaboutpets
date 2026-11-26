/* Pet profile pages: shrink large photos in the browser before upload (so big phone photos upload
   quickly and stay under the server's upload limit), preview them, and tidy the emergency form. */
(function () {
  'use strict';
  var MAX = 1600;

  function shrink(file) {
    return new Promise(function (resolve) {
      if (!/^image\/(jpeg|png|webp)$/.test(file.type) || !window.createImageBitmap) { resolve(file); return; }
      createImageBitmap(file, { imageOrientation: 'from-image' }).then(function (bmp) {
        var scale = Math.min(1, MAX / Math.max(bmp.width, bmp.height));
        if (scale === 1 && file.size < 2.5 * 1024 * 1024) { resolve(file); return; }
        var c = document.createElement('canvas');
        c.width = Math.round(bmp.width * scale); c.height = Math.round(bmp.height * scale);
        var ctx = c.getContext('2d');
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
        ctx.drawImage(bmp, 0, 0, c.width, c.height);
        c.toBlob(function (blob) {
          resolve(blob ? new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }) : file);
        }, 'image/jpeg', 0.86);
      }).catch(function () { resolve(file); });
    });
  }

  document.querySelectorAll('input[type=file][data-resize]').forEach(function (input) {
    var preview = document.createElement('div');
    preview.className = 'pet-previews';
    input.insertAdjacentElement('afterend', preview);
    input.addEventListener('change', function () {
      var files = Array.prototype.slice.call(input.files || []);
      preview.innerHTML = '';
      if (!files.length || !window.DataTransfer) { return; }
      var form = input.form, btn = form && form.querySelector('[type=submit]');
      if (btn) { btn.disabled = true; }
      Promise.all(files.map(shrink)).then(function (out) {
        var dt = new DataTransfer();
        out.forEach(function (f) {
          dt.items.add(f);
          var tile = document.createElement('span');
          if (/^image\//.test(f.type)) {
            var img = document.createElement('img');
            img.src = URL.createObjectURL(f); img.alt = '';
            tile.appendChild(img);
          } else {
            tile.textContent = f.name;
            tile.className = 'is-doc';
          }
          preview.appendChild(tile);
        });
        input.files = dt.files;
      }).finally(function () { if (btn) { btn.disabled = false; } });
    });
  });

  // Only ask for the limit and signature once the authorisation box is ticked.
  var auth = document.getElementById('emergency_authorised');
  if (auth) {
    var fields = document.querySelector('.pet-auth-fields');
    var sync = function () {
      fields.hidden = !auth.checked;
      document.getElementById('emergency_signed_name').required = auth.checked;
    };
    auth.addEventListener('change', sync);
    sync();
  }
})();
