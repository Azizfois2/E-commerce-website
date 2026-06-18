document.addEventListener('DOMContentLoaded', () => {

    const form = document.forms.login;
    const passInput = document.getElementById('login-pass');
    const toggleBtn = document.getElementById('loginTogglePass');
    const toast = document.getElementById('loginToast');
    const toastMsg = document.getElementById('loginToastMsg');
    const submitBtn = document.getElementById('loginBtn');

    // ── Password toggle ───────────────────────────────────────
    toggleBtn.addEventListener('click', () => {
        const isPass = passInput.type === 'password';
        passInput.type = isPass ? 'text' : 'password';
        toggleBtn.innerHTML = isPass ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
        toggleBtn.setAttribute('aria-label', isPass ? 'Hide password' : 'Show password');
    });

    // ── Validation ────────────────────────────────────────────
    const validators = {
        email: (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v),
        pass: (v) => v.length >= 1
    };

    function validateField(name) {
        const input = form.elements[name];
        const group = input?.closest('.form-group');
        if (!group) return true;

        const valid = validators[name](input.value);
        group.classList.toggle('invalid', !valid);
        group.classList.toggle('valid', valid && input.value.length > 0);
        return valid;
    }

    ['email', 'pass'].forEach(field => {
        const el = form.elements[field];
        if (!el) return;
        el.addEventListener('blur', () => validateField(field));
        el.addEventListener('input', () => {
            // clear error as soon as user starts typing
            const group = el.closest('.form-group');
            if (group) {
                group.classList.remove('invalid');
            }
        });
    });

    // ── Toast helper ──────────────────────────────────────────
    function showToast(message, isError = false) {
        toastMsg.textContent = message;
        toast.classList.remove('success', 'error');
        toast.classList.add(isError ? 'error' : 'success');
        toast.querySelector('i').textContent = isError ? '✕' : '⚡';
        toast.classList.add('show');

        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    // ── Submit ────────────────────────────────────────────────
    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const emailValid = validateField('email');
        const passValid  = validateField('pass');

        if (!emailValid || !passValid) {
            form.classList.add('shake');
            setTimeout(() => form.classList.remove('shake'), 400);

            const firstErr = form.querySelector('.invalid');
            if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        submitBtn.textContent = 'Verifying...';
        submitBtn.disabled = true;
        form.submit();
    });
});
