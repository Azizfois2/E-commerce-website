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
        toggleBtn.innerHTML = isPass ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
        toggleBtn.setAttribute('aria-label', isPass ? 'Hide password' : 'Show password');
    });

    // ── Password Strength Meter ───────────────────────────────
    const strengthMeter = document.getElementById('passwordStrength');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    const requirementsBox = document.getElementById('passwordRequirements');
    
    // Requirement elements
    const reqLength = document.getElementById('req-length');
    const reqNumber = document.getElementById('req-number');
    const reqSymbol = document.getElementById('req-symbol');
    const reqUpper = document.getElementById('req-upper');

    function checkPasswordRequirements(password) {
        const requirements = {
            length: password.length >= 8,
            number: /[0-9]/.test(password),
            symbol: /[^a-zA-Z0-9]/.test(password),
            upper: /[A-Z]/.test(password)
        };
        
        // Update visual indicators
        reqLength.classList.toggle('met', requirements.length);
        reqNumber.classList.toggle('met', requirements.number);
        reqSymbol.classList.toggle('met', requirements.symbol);
        if (reqUpper) reqUpper.classList.toggle('met', requirements.upper);
        
        return requirements;
    }

    function calculatePasswordStrength(password) {
        if (!password) return { strength: 0, text: 'Enter password', class: '' };
        
        let strength = 0;
        
        // Length check
        if (password.length >= 8) strength += 25;
        if (password.length >= 12) strength += 15;
        
        // Character variety
        if (/[a-z]/.test(password)) strength += 15;
        if (/[A-Z]/.test(password)) strength += 15;
        if (/[0-9]/.test(password)) strength += 15;
        if (/[^a-zA-Z0-9]/.test(password)) strength += 15;
        
        // Determine strength level
        if (strength < 40) return { strength: 33, text: 'Weak', class: 'weak' };
        if (strength < 70) return { strength: 66, text: 'Medium', class: 'medium' };
        return { strength: 100, text: 'Strong', class: 'strong' };
    }

    passInput.addEventListener('input', (e) => {
        const password = e.target.value;
        
        // Always show requirements when there's focus or content
        requirementsBox.classList.add('active');
            
        if (password.length > 0) {
            strengthMeter.classList.add('active');
            
            // Check requirements
            checkPasswordRequirements(password);
            
            // Update strength meter
            const result = calculatePasswordStrength(password);
            strengthFill.className = 'strength-fill ' + result.class;
            strengthText.className = 'strength-text ' + result.class;
            strengthText.textContent = result.text;
        } else {
            strengthMeter.classList.remove('active');
            // Reset all requirements to unmet
            checkPasswordRequirements('');
        }
    });

    // Show requirements immediately on focus — before the user types anything
    passInput.addEventListener('focus', () => {
        requirementsBox.classList.add('active');
        if (passInput.value.length > 0) {
            strengthMeter.classList.add('active');
        }
    });

    // Hide requirements when user leaves the field AND it's empty
    passInput.addEventListener('blur', () => {
        if (passInput.value.length === 0) {
            requirementsBox.classList.remove('active');
            strengthMeter.classList.remove('active');
        }
    });

    // ── Validators ────────────────────────────────────────────
    const validators = {
        fullname: (v) => v.trim().length >= 2,
        email:    (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v),
        pass: (v) => /^(?=.*[0-9])(?=.*[^a-zA-Z0-9]).{8,}$/.test(v),
        // FIX: compare date strings directly to avoid UTC/local timezone offset bug
        dob:      (v) => v !== '' && v < new Date().toISOString().slice(0, 10),
        adresse:  (v) => v.trim().length >= 5,
        telephone: (v) => {
            const selectedMethod = form.querySelector('input[name="verify_method"]:checked')?.value || 'email';
            return selectedMethod === 'email' || v.trim().length >= 8;
        },
        terms_agree: () => form.elements.terms_agree?.checked === true
        // FIX: mp validator now explicitly queries the form rather than relying on closure
    };

    const phoneGroup = document.querySelector('.phone-group');
    const phoneInput = document.getElementById('telephone');
    const verificationChoices = form.querySelectorAll('input[name="verify_method"]');

    function syncPhoneRequirement() {
        const selectedMethod = form.querySelector('input[name="verify_method"]:checked')?.value || 'email';
        const needsPhone = selectedMethod !== 'email';

        if (phoneInput) {
            phoneInput.required = needsPhone;
            phoneInput.setAttribute('aria-required', needsPhone ? 'true' : 'false');
        }

        if (phoneGroup) {
            phoneGroup.classList.toggle('phone-required', needsPhone);
            phoneGroup.classList.toggle('phone-optional', !needsPhone);
            if (!needsPhone) {
                phoneGroup.classList.remove('invalid');
            }
        }
    }

    verificationChoices.forEach(choice => {
        choice.addEventListener('change', () => {
            syncPhoneRequirement();
            validateField('telephone');
        });
    });

    syncPhoneRequirement();

    // ── Real-time validation ──────────────────────────────────
    Object.keys(validators).forEach(field => {
        const elements = form.elements[field];
        if (!elements) return;

        // FIX: was only attaching to el[0]; now all radio buttons get listeners
        const targets = elements.length ? [...elements] : [elements];
        targets.forEach(el => {
            el.addEventListener('blur',   () => validateField(field));
            el.addEventListener('change', () => validateField(field));
            el.addEventListener('input',  () => {
                // Clear invalid state on input
                const group = el.closest('.form-group');
                if (group && group.classList.contains('invalid')) {
                    group.classList.remove('invalid');
                }
            });
        });
    });

    function validateField(name) {
        const input = form.elements[name];
        if (!input) return true;

        const group = input.length
            ? input[0].closest('.form-group')
            : input.closest('.form-group');
        if (!group) return true;

        const val = input.type === 'checkbox' ? (input.checked ? '1' : '') : (input.value ?? '');
        const valid = validators[name](val);
        group.classList.toggle('invalid', !valid);
        group.classList.toggle('valid', valid && val.length > 0);
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

        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.textContent = 'Creating…';
            submitButton.disabled = true;
        }
        form.submit();
    });
});
