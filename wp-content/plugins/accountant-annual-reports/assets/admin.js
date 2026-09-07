(function () {
  'use strict';
  const choose = document.getElementById('tax14-select-pdf');
  if (!choose) return;
  const field = document.getElementById('tax14-pdf');
  const label = document.getElementById('tax14-pdf-name');
  let frame;
  choose.addEventListener('click', function () {
    if (!frame) {
      frame = wp.media({title: tax14Media.title, button: {text: tax14Media.button}, library: {type: 'application/pdf'}, multiple: false});
      frame.on('select', function () {
        const file = frame.state().get('selection').first().toJSON();
        if (file.mime !== 'application/pdf') { label.textContent = tax14Media.invalid; return; }
        field.value = file.id;
        label.textContent = file.filename;
      });
    }
    frame.open();
  });
  document.getElementById('tax14-remove-pdf').addEventListener('click', function () {
    field.value = '0'; label.textContent = tax14Media.empty; choose.focus();
  });
}());
