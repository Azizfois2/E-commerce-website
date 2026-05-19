document.addEventListener('DOMContentLoaded', () => {
    const form = document.forms.signup;
    const passInput = document.getElementById('pass');
    const toggleBtn = document.getElementById('togglePass');

    const paymentLabels = {
        master_card: 'Mastercard',
        carte_visa: 'Visa',
        vmt_elec: 'Bank Transfer'
    };

    // ── Password visibility toggle ────────────────────────────
    toggleBtn.addEventListener('click', () => {
        const isPass = passInput.type === 'password';
        passInput.type = isPass ? 'text' : 'password';
        toggleBtn.textContent = isPass ? '🙈' : '👁';
        toggleBtn.setAttribute('aria-label', isPass ? 'Hide password' : 'Show password');
    });

    // ── Validators ────────────────────────────────────────────
    const validators = {
        fullname: (v) => v.trim().length >= 2,
        email:    (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v),
        pass: (v) => /^(?=.*[0-9])(?=.*[^a-zA-Z0-9]).{8,}$/.test(v),
        // FIX: compare date strings directly to avoid UTC/local timezone offset bug
        dob:      (v) => v !== '' && v < new Date().toISOString().slice(0, 10),
        adresse:  (v) => v.trim().length >= 5,
        telephone: (v) => v.trim().length >= 8
        // FIX: mp validator now explicitly queries the form rather than relying on closure
    };

    // ── Real-time validation ──────────────────────────────────
    Object.keys(validators).forEach(field => {
        const elements = form.elements[field];
        if (!elements) return;

        // FIX: was only attaching to el[0]; now all radio buttons get listeners
        const targets = elements.length ? [...elements] : [elements];
        targets.forEach(el => {
            el.addEventListener('blur',   () => validateField(field));
            el.addEventListener('change', () => validateField(field));
        });
    });

    function validateField(name) {
        const input = form.elements[name];
        if (!input) return true;

        const group = input.length
            ? input[0].closest('.form-group')
            : input.closest('.form-group');
        if (!group) return true;

        const val = input.value ?? '';
        const valid = validators[name](val);
        group.classList.toggle('invalid', !valid);
        return valid;
    }

    // ── Modal helpers ─────────────────────────────────────────
    const confirmOverlay = document.getElementById('confirmOverlay');
    const confirmModal   = document.getElementById('confirmModal');
    const confirmClose   = document.getElementById('confirmClose');
    const confirmEdit    = document.getElementById('confirmEdit');
    const confirmSubmit  = document.getElementById('confirmSubmit');

    // Keep a reference to the element focused before the modal opened
    let previouslyFocused = null;

    function openConfirm() {
        const fullname = form.fullname.value.trim();
        const email    = form.email.value.trim();
        const pass     = form.pass.value;
        const dob      = form.dob.value;
        const adresse  = form.adresse.value.trim();
        const telephone = form.telephone.value.trim();

        document.getElementById('cf-name').textContent  = fullname || '—';
        document.getElementById('cf-email').textContent = email    || '—';
        document.getElementById('cf-pass').textContent  =
            pass.length ? '•'.repeat(Math.min(pass.length, 12)) : '—';
        document.getElementById('cf-dob').textContent   = dob
            ? new Date(dob).toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' })
            : '—';
            
        const cfTelephone = document.getElementById('cf-telephone');
        if (cfTelephone) cfTelephone.textContent = telephone || '—';
        
        const cfAdresse = document.getElementById('cf-adresse');
        if (cfAdresse) cfAdresse.textContent = adresse || '—';
        const paymentPreview = document.getElementById('cf-mp');
        const mpEl = form.querySelector('input[name="mp"]:checked');
        if (paymentPreview) {
            const mp = mpEl ? (paymentLabels[mpEl.value] ?? mpEl.value) : '—';
            paymentPreview.textContent = mp;
        }

        // FIX: reset button state on every open (was never reset after prior submission)
        confirmSubmit.textContent = 'Confirm & create';
        confirmSubmit.disabled    = false;

        previouslyFocused = document.activeElement;
        confirmOverlay.classList.add('active');
        confirmModal.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Move focus into the modal for accessibility
        confirmClose.focus();
    }

    function closeConfirm() {
        confirmOverlay.classList.remove('active');
        confirmModal.classList.remove('active');
        document.body.style.overflow = '';
        // Return focus to wherever it was before the modal opened
        previouslyFocused?.focus();
    }

    confirmClose.addEventListener('click', closeConfirm);
    confirmEdit.addEventListener('click',  closeConfirm);

    // FIX: only close when clicking the dark overlay itself, not the modal inside it
    confirmOverlay.addEventListener('click', (e) => {
        if (e.target === confirmOverlay) closeConfirm();
    });

    // FIX: new — Escape key support
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && confirmOverlay.classList.contains('active')) {
            closeConfirm();
        }
    });

    confirmSubmit.addEventListener('click', () => {
        confirmSubmit.textContent = 'Creating…';
        confirmSubmit.disabled    = true;
        // #region agent log
        fetch('http://127.0.0.1:7242/ingest/3ef74137-7336-41af-9a23-1526acbc2e88',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({runId:'post-fix',hypothesisId:'H6',location:'js/form.js:confirmSubmit',message:'Signup form confirmed and submitting',data:{action:form.action},timestamp:Date.now()})}).catch(()=>{});
        // #endregion
        form.submit();
    });

    // ── Form submit ───────────────────────────────────────────
    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const allValid = Object.keys(validators).reduce(
            (acc, field) => validateField(field) && acc,
            true
        );

        if (!allValid) {
            form.classList.add('shake');
            setTimeout(() => form.classList.remove('shake'), 400);
            form.querySelector('.invalid')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        openConfirm();
    });
});