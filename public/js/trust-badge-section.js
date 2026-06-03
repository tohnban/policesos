(function () {
    'use strict';

    if (!document.querySelector('[data-trust-success-invalid]')) {
        return;
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.alert-banner-success, .sub-feedback.success').forEach(function (el) {
            if (/selo/i.test(el.textContent || '')) {
                el.remove();
            }
        });
    });
})();
