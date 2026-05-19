/**
 * installment.js — Taksit (تقسيط) Installment Payment Calculator
 * 
 * Pure client-side. No backend needed.
 * Adds "Pay X MAD/month" badges to product cards and a slider in cart/checkout.
 */
(() => {
    'use strict';

    // ── Configuration ────────────────────────────────────────
    const INSTALLMENT_CONFIG = {
        interestRate: 0.05,   // 5% annual interest
        plans: [3, 6, 12, 24],
        defaultPlan: 6,
        minPrice: 500,        // Only show for products ≥ 500 MAD
        currency: 'MAD'
    };

    /**
     * Calculate monthly installment amount.
     * @param {number} price     — Product price in MAD
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

    function formatMAD(value) {
        return Number(value).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' MAD';
    }

    /**
     * Generate the small installment badge HTML for product cards.
     */
    function installmentBadge(price, months = INSTALLMENT_CONFIG.defaultPlan) {
        if (price < INSTALLMENT_CONFIG.minPrice) return '';

        const calc = calculateInstallment(price, months);
        return `
            <div class="installment-badge" title="Pay in ${months} monthly installments">
                <i class="fas fa-credit-card"></i>
                <span>or <strong>${formatMAD(calc.monthly)}</strong>/mo × ${months}</span>
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
            <div class="installment-widget" id="${containerId}">
                <div class="installment-header">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Installment Payments</span>
                </div>
                <div class="installment-plans">
                    ${INSTALLMENT_CONFIG.plans.map(m => {
                        const c = calculateInstallment(price, m);
                        const isDefault = m === INSTALLMENT_CONFIG.defaultPlan;
                        return `
                            <button class="installment-plan-btn ${isDefault ? 'active' : ''}" data-months="${m}">
                                <span class="plan-months">${m} Months</span>
                                <span class="plan-amount">${formatMAD(c.monthly)}/mo</span>
                            </button>
                        `;
                    }).join('')}
                </div>
                <div class="installment-detail">
                    <div class="installment-row">
                        <span>Cash Price</span>
                        <span>${formatMAD(price)}</span>
                    </div>
                    <div class="installment-row">
                        <span>Interest Fee (${(INSTALLMENT_CONFIG.interestRate * 100).toFixed(0)}%/yr)</span>
                        <span class="installment-interest">${formatMAD(defaultCalc.interest)}</span>
                    </div>
                    <div class="installment-row installment-total">
                        <span>Total Cost</span>
                        <span class="installment-total-value">${formatMAD(defaultCalc.total)}</span>
                    </div>
                    <div class="installment-monthly-highlight">
                        <span class="installment-monthly-value">${formatMAD(defaultCalc.monthly)}</span>
                        <span class="installment-monthly-label">/ month × <span class="installment-months-label">${INSTALLMENT_CONFIG.defaultPlan}</span> months</span>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Bind plan buttons in an installment widget.
     */
    function bindInstallmentWidget(containerId, price) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.querySelectorAll('.installment-plan-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                container.querySelectorAll('.installment-plan-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const months = parseInt(btn.dataset.months);
                const calc = calculateInstallment(price, months);

                container.querySelector('.installment-interest').textContent = formatMAD(calc.interest);
                container.querySelector('.installment-total-value').textContent = formatMAD(calc.total);
                container.querySelector('.installment-monthly-value').textContent = formatMAD(calc.monthly);
                container.querySelector('.installment-months-label').textContent = months;
            });
        });
    }

    // ── Expose globally ──────────────────────────────────────
    window.Installment = {
        config: INSTALLMENT_CONFIG,
        calculate: calculateInstallment,
        badge: installmentBadge,
        widget: installmentWidget,
        bind: bindInstallmentWidget,
        formatMAD
    };
})();
