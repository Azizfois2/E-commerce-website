/**
 * installment.js — Taksit (تقسيط) Installment Payment Calculator
 *
 * Frontend calculator + optional cart sync metadata.
 * Adds "Pay X DH/month" badges to product cards and interactive plan selection.
 */
(() => {
    'use strict';

    // ── Configuration ────────────────────────────────────────
    const INSTALLMENT_CONFIG = {
        interestRate: 0.05,   // 5% annual interest
        plans: [3, 6, 12, 24],
        defaultPlan: 6,
        minPrice: 500,        // Only show for products >= 500 DH
        currency: 'MAD'
    };

    /**
     * Calculate monthly installment amount.
     * @param {number} price     — Product price in DH
     * @param {number} months    — Number of months
     * @param {number} [rate]    — Annual interest rate (default from config)
     * @returns {{ monthly: number, total: number, interest: number }}
     */
    function calculateInstallment(price, months, rate = INSTALLMENT_CONFIG.interestRate) {
        if (months <= 0 || price <= 0) return { monthly: 0, total: 0, interest: 0 };

        // Simple interest for transparency
        const totalInterest = price * rate * (months / 12);
        const total = price + totalInterest;
        const monthly = total / months;

        return {
            monthly: Math.ceil(monthly * 100) / 100,
            total: Math.ceil(total * 100) / 100,
            interest: Math.ceil(totalInterest * 100) / 100
        };
    }

    function installmentText(key, fallback) {
        return window.__marocPcI18n?.[key] || window.__i18n?.[key] || fallback;
    }

    function installmentTemplate(key, fallback, params = {}) {
        let value = installmentText(key, fallback);
        Object.entries(params).forEach(([name, replacement]) => {
            value = value.replaceAll(`{${name}}`, replacement);
        });
        return value;
    }

    function monthLabel(months) {
        return installmentTemplate(
            months === 1 ? 'installmentMonth' : 'installmentMonths',
            months === 1 ? '{count} Month' : '{count} Months',
            { count: months }
        );
    }

    function formatMAD(value, options = {}) {
        if (typeof window.formatMAD === 'function') {
            return window.formatMAD(value, options);
        }
        return Number(value).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' DH';
    }

    /**
     * Generate the small installment badge HTML for product cards.
     */
    function installmentBadge(price, months = INSTALLMENT_CONFIG.defaultPlan) {
        if (price < INSTALLMENT_CONFIG.minPrice) return '';

        const calc = calculateInstallment(price, months);
        return `
            <div class="installment-badge" title="${installmentTemplate('payInInstallments', 'Pay in {count} monthly installments', { count: months })}">
                <i class="fas fa-credit-card"></i>
                <span>${installmentText('installmentOr', 'or')} <strong>${formatMAD(calc.monthly)}</strong>/${installmentText('monthShort', 'mo')} x ${months}</span>
            </div>
        `;
    }

    /**
     * Generate the interactive installment widget for cart/checkout/modal.
     */
    function installmentWidget(price, containerId) {
        if (price < INSTALLMENT_CONFIG.minPrice) return '';

        const defaultCalc = calculateInstallment(price, INSTALLMENT_CONFIG.defaultPlan);

        return `
            <div class="installment-widget" id="${containerId}" data-selected-months="${INSTALLMENT_CONFIG.defaultPlan}" data-selected-monthly="${defaultCalc.monthly}" data-selected-total="${defaultCalc.total}" data-selected-interest="${defaultCalc.interest}">
                <div class="installment-header">
                    <i class="fas fa-calendar-alt"></i>
                    <span>${installmentText('installmentPayments', 'Installment Payments')}</span>
                </div>
                <div class="installment-plans">
                    ${INSTALLMENT_CONFIG.plans.map(m => {
                        const c = calculateInstallment(price, m);
                        const isDefault = m === INSTALLMENT_CONFIG.defaultPlan;
                        return `
                            <button class="installment-plan-btn ${isDefault ? 'active' : ''}" data-months="${m}">
                                <span class="plan-months">${monthLabel(m)}</span>
                                <span class="plan-amount">${formatMAD(c.monthly)}/${installmentText('monthShort', 'mo')}</span>
                            </button>
                        `;
                    }).join('')}
                </div>
                <div class="installment-detail">
                    <div class="installment-row">
                        <span>${installmentText('cashPrice', 'Cash Price')}</span>
                        <span>${formatMAD(price)}</span>
                    </div>
                    <div class="installment-row">
                        <span>${installmentTemplate('interestFee', 'Interest Fee ({rate}%/yr)', { rate: (INSTALLMENT_CONFIG.interestRate * 100).toFixed(0) })}</span>
                        <span class="installment-interest">${formatMAD(defaultCalc.interest)}</span>
                    </div>
                    <div class="installment-row installment-total">
                        <span>${installmentText('totalCost', 'Total Cost')}</span>
                        <span class="installment-total-value">${formatMAD(defaultCalc.total)}</span>
                    </div>
                    <div class="installment-monthly-highlight">
                        <span class="installment-monthly-value">${formatMAD(defaultCalc.monthly)}</span>
                        <span class="installment-monthly-label">/ ${installmentText('month', 'month')} x <span class="installment-months-label">${INSTALLMENT_CONFIG.defaultPlan}</span> ${installmentText('months', 'months')}</span>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Bind plan buttons in an installment widget.
     */
    function writeSelectedPlan(container, calc, months) {
        container.dataset.selectedMonths = String(months);
        container.dataset.selectedMonthly = String(calc.monthly);
        container.dataset.selectedTotal = String(calc.total);
        container.dataset.selectedInterest = String(calc.interest);
    }

    function bindInstallmentWidget(containerId, price) {
        const container = document.getElementById(containerId);
        if (!container) return;

        writeSelectedPlan(container, calculateInstallment(price, INSTALLMENT_CONFIG.defaultPlan), INSTALLMENT_CONFIG.defaultPlan);

        container.querySelectorAll('.installment-plan-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                container.querySelectorAll('.installment-plan-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const months = parseInt(btn.dataset.months, 10);
                const calc = calculateInstallment(price, months);
                writeSelectedPlan(container, calc, months);

                container.querySelector('.installment-interest').textContent = formatMAD(calc.interest);
                container.querySelector('.installment-total-value').textContent = formatMAD(calc.total);
                container.querySelector('.installment-monthly-value').textContent = formatMAD(calc.monthly);
                container.querySelector('.installment-months-label').textContent = months;
            });
        });
    }

    // ── Expose globally ──────────────────────────────────────
    function getSelection(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return null;
        return {
            months: Number.parseInt(container.dataset.selectedMonths || String(INSTALLMENT_CONFIG.defaultPlan), 10),
            monthly: Number.parseFloat(container.dataset.selectedMonthly || '0'),
            total: Number.parseFloat(container.dataset.selectedTotal || '0'),
            interest: Number.parseFloat(container.dataset.selectedInterest || '0')
        };
    }

    window.Installment = {
        config: INSTALLMENT_CONFIG,
        calculate: calculateInstallment,
        badge: installmentBadge,
        widget: installmentWidget,
        bind: bindInstallmentWidget,
        getSelection,
        formatMAD
    };
})();
