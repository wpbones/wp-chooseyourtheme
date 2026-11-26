/*
 * WP Choose Your Theme: submit the dropdown when it changes. The form works without this script
 * (it has a button); with it, the button is not needed.
 */
(function () {
  'use strict';

  document.querySelectorAll('form.wpcyt_form').forEach(function (form) {
    var select = form.querySelector('select.wpcyt_combo');
    var button = form.querySelector('.wpcyt_submit');

    if (!select) {
      return;
    }

    select.addEventListener('change', function () {
      form.submit();
    });

    if (button) {
      button.hidden = true;
    }
  });
})();
