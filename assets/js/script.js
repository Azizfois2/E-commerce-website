// effet nadi v2
const observerOptions = {
    root: null,
    rootMargin: '0px 0px -50px 0px',
    threshold: 0.1
};

const observer = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });


    // Aji n3mrou lik lemail blhdra 5awya hhh
    const newsletterForm = document.getElementById('newsletterForm');
    if (newsletterForm) {
        const input = newsletterForm.querySelector('input');
        const btn = newsletterForm.querySelector('button');

        const submitNewsletter = async () => {
            const email = input.value.trim();
            if (!email) {
                if (typeof showToast === 'function') showToast('Please enter an email address', 'error');
                else alert('Please enter an email address');
                return;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                if (typeof showToast === 'function') showToast('Please enter a valid email', 'error');
                else alert('Please enter a valid email');
                return;
            }

            const originalText = btn.textContent;
            btn.textContent = 'Wait...';
            btn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('email', email);

                const res = await fetch('api/subscribe.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await res.json();

                if (data.success) {
                    if (typeof showToast === 'function') showToast(data.message, 'success');
                    else alert(data.message);
                    input.value = '';
                } else {
                    if (typeof showToast === 'function') showToast(data.message, 'error');
                    else alert(data.message);
                }
            } catch (err) {
                if (typeof showToast === 'function') showToast('Network error. Please try again.', 'error');
                else alert('Network error. Please try again.');
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        };

        btn.addEventListener('click', submitNewsletter);

        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitNewsletter();
            }
        });
    }
});
