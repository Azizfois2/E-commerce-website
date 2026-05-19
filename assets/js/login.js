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
        toggleBtn.textContent = isPass ? '🙈' : '👁';
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
        return valid;
    }

    ['email', 'pass'].forEach(field => {
        const el = form.elements[field];
        if (!el) return;
        el.addEventListener('blur', () => validateField(field));
        el.addEventListener('input', () => {
            // clear error as soon as user starts typing
            el.closest('.form-group')?.classList.remove('invalid');
        });
    });

    // ── Toast helper ──────────────────────────────────────────
    function showToast(message, isError = false) {
        toastMsg.textContent = message;
        toast.style.borderColor = isError ? 'var(--red)' : 'var(--cyan)';
        toast.querySelector('i').textContent = isError ? '✕' : '⚡';
        toast.querySelector('i').style.color = isError ? 'var(--red)' : 'var(--cyan)';
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
        // #region agent log
        fetch('http://127.0.0.1:7242/ingest/3ef74137-7336-41af-9a23-1526acbc2e88',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({runId:'post-fix',hypothesisId:'H7',location:'js/login.js:submit',message:'Login validation passed, submitting form',data:{action:form.action,remember:!!form.elements.remember?.checked},timestamp:Date.now()})}).catch(()=>{});
        // #endregion
        form.submit();
    });
});