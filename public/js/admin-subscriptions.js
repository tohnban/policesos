(function () {
    'use strict';

    var hiddenClass = 'admin-plan-action-hidden';

    function toggleEl(el, show) {
        if (!el) {
            return;
        }
        if (show) {
            el.classList.remove(hiddenClass);
        } else {
            el.classList.add(hiddenClass);
        }
    }

    function buildCheckoutUrl(baseUrl, targetId, planCode) {
        if (!baseUrl || !targetId || !planCode) {
            return '';
        }
        var sep = baseUrl.indexOf('?') >= 0 ? '&' : '?';
        return baseUrl + sep + 'target_user_id=' + encodeURIComponent(targetId)
            + '&plan_code=' + encodeURIComponent(planCode);
    }

    function syncPlanActions(form) {
        var select = form.querySelector('[data-plan-select]');
        var saveBtn = form.querySelector('[data-action-save]');
        var configBtn = form.querySelector('[data-action-configure]');
        var standardOnly = form.querySelector('[data-standard-only]');
        if (!select || !saveBtn || !configBtn) {
            return;
        }

        var opt = select.options[select.selectedIndex];
        var isCustom = !!(opt && opt.getAttribute('data-custom') === '1');
        var planCode = opt ? String(opt.value || '').trim() : '';
        var targetId = form.getAttribute('data-target-user-id') || '';
        var baseUrl = form.getAttribute('data-checkout-url') || '';
        var checkoutUrl = isCustom ? buildCheckoutUrl(baseUrl, targetId, planCode) : '';

        toggleEl(saveBtn, !isCustom);
        toggleEl(configBtn, isCustom);
        toggleEl(standardOnly, !isCustom);

        configBtn.setAttribute('data-navigate-url', checkoutUrl);
        configBtn.setAttribute('href', checkoutUrl || '#');
        configBtn.setAttribute('aria-hidden', isCustom ? 'false' : 'true');
        if (!isCustom) {
            configBtn.setAttribute('tabindex', '-1');
        } else {
            configBtn.removeAttribute('tabindex');
        }
    }

    document.querySelectorAll('.admin-subscription-set-form').forEach(function (form) {
        syncPlanActions(form);

        var select = form.querySelector('[data-plan-select]');
        if (select) {
            select.addEventListener('change', function () {
                syncPlanActions(form);
            });
        }

        var configBtn = form.querySelector('[data-action-configure]');
        if (configBtn) {
            configBtn.addEventListener('click', function (e) {
                var url = configBtn.getAttribute('data-navigate-url') || '';
                if (!url) {
                    e.preventDefault();
                    return;
                }
                e.preventDefault();
                window.location.assign(url);
            });
        }

        form.addEventListener('submit', function (e) {
            var opt = select && select.options[select.selectedIndex];
            if (opt && opt.getAttribute('data-custom') === '1') {
                e.preventDefault();
                var url = configBtn ? (configBtn.getAttribute('data-navigate-url') || '') : '';
                if (url) {
                    window.location.assign(url);
                }
            }
        });
    });
})();
