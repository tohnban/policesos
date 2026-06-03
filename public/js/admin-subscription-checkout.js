(function () {
    'use strict';

    var monthsEl = document.getElementById('billing_cycle_months');
    var priceEl = document.getElementById('negotiated_price_aoa');
    var summaryEl = document.getElementById('admin-enterprise-summary');
    if (!monthsEl || !priceEl || !summaryEl) {
        return;
    }

    function formatKz(n) {
        return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' Kz';
    }

    function syncSummary() {
        var months = parseInt(monthsEl.value, 10) || 1;
        var total = parseFloat(priceEl.value) || 0;
        if (total <= 0) {
            summaryEl.textContent = 'Preencha o valor negociado.';
            return;
        }
        summaryEl.textContent = formatKz(total) + ' por ' + months + ' mês(es) (' + formatKz(total / months) + ' / mês equivalente)';
    }

    monthsEl.addEventListener('change', syncSummary);
    priceEl.addEventListener('input', syncSummary);
    syncSummary();
})();
