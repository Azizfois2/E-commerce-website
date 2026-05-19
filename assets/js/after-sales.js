(() => {
    'use strict';

    const form = document.getElementById('afterSalesForm');
    const result = document.getElementById('afterSalesResult');
    if (!form || !result) return;

    const params = new URLSearchParams(window.location.search);
    const orderId = params.get('order');
    if (orderId && form.elements.order_id) {
        form.elements.order_id.value = orderId;
    }

    function showResult(message, isError = false) {
        result.className = `after-form-result ${isError ? 'is-error' : 'is-success'}`;
        result.innerHTML = message;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const submit = form.querySelector('.after-submit');
        const original = submit.innerHTML;
        submit.disabled = true;
        submit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());
        payload.package_opened = formData.has('package_opened');

        try {
            const response = await fetch('api/after-sales-request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                showResult(data.error || 'Could not submit your request. Please try again.', true);
                return;
            }

            showResult(`
                <strong>${data.message}</strong><br>
                Ticket: <strong>${data.ticket}</strong><br>
                Priority: ${data.priority}<br>
                Next step: ${data.next_action}
            `);
            form.reset();
        } catch (error) {
            showResult('Network error. Please try again or email support@marocpc.com.', true);
        } finally {
            submit.disabled = false;
            submit.innerHTML = original;
        }
    });
})();
