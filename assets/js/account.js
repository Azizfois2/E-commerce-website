(() => {
    'use strict';

    // ── Helpers ──────────────────────────────────────────────
    function formatMAD(value) {
        return Number(value).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' MAD';
    }

    function showAlert(id, msg, isError) {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerHTML = `<div class="alert-inline ${isError ? 'error' : 'success'}">${msg}</div>`;
        setTimeout(() => { if (el) el.innerHTML = ''; }, 5000);
    }

    function showToast(message, type = 'info') {
        const toast = document.getElementById('toast');
        const toastMsg = document.getElementById('toastMessage');
        const toastIcon = toast?.querySelector('i');
        if (!toast || !toastMsg) return;

        toast.className = `toast show ${type}`;
        toastMsg.style.whiteSpace = 'pre-line';
        toastMsg.textContent = message;

        if (toastIcon) {
            if (type === 'success') toastIcon.className = 'fas fa-check-circle';
            else if (type === 'error') toastIcon.className = 'fas fa-exclamation-triangle';
            else toastIcon.className = 'fas fa-info-circle';
        }

        clearTimeout(window._accountToastTimer);
        window._accountToastTimer = setTimeout(() => {
            toast.classList.remove('show');
        }, 5000);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatStatusText(status) {
        return String(status || 'pending')
            .replace(/_/g, ' ')
            .replace(/\b\w/g, ch => ch.toUpperCase());
    }

    async function apiPost(url, data) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify(data)
        });
        return res.json().catch(() => ({}));
    }

    // â”€â”€ Two-factor authentication â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    const profilePictureInput = document.getElementById('profilePictureInput');
    const profilePicturePreview = document.getElementById('profilePicturePreview');
    const profileAvatarImg = document.getElementById('profileAvatarImg');
    const uploadProfilePictureBtn = document.getElementById('uploadProfilePictureBtn');
    let selectedProfilePicture = null;

    if (profilePictureInput) {
        profilePictureInput.addEventListener('change', () => {
            const file = profilePictureInput.files && profilePictureInput.files[0];
            selectedProfilePicture = file || null;
            if (!file) return;
            if (file.size > 3 * 1024 * 1024) {
                selectedProfilePicture = null;
                profilePictureInput.value = '';
                showAlert('profileAlert', 'Profile picture must be 3 MB or smaller.', true);
                return;
            }
            const previewUrl = URL.createObjectURL(file);
            if (profilePicturePreview) profilePicturePreview.src = previewUrl;
            if (profileAvatarImg) profileAvatarImg.src = previewUrl;
        });
    }

    if (uploadProfilePictureBtn) {
        uploadProfilePictureBtn.addEventListener('click', async () => {
            if (!selectedProfilePicture) {
                showAlert('profileAlert', 'Choose an image before uploading.', true);
                profilePictureInput?.focus();
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const formData = new FormData();
            formData.append('profile_picture', selectedProfilePicture);

            uploadProfilePictureBtn.disabled = true;
            uploadProfilePictureBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

            try {
                const res = await fetch('api/upload-profile-picture.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    credentials: 'same-origin',
                    body: formData
                });
                const r = await res.json().catch(() => ({}));
                if (r.success) {
                    const imageUrl = `${r.image}?v=${Date.now()}`;
                    if (profilePicturePreview) profilePicturePreview.src = imageUrl;
                    if (profileAvatarImg) profileAvatarImg.src = imageUrl;
                    selectedProfilePicture = null;
                    if (profilePictureInput) profilePictureInput.value = '';
                    showAlert('profileAlert', '<i class="fas fa-check-circle"></i> Profile picture updated.', false);
                } else {
                    showAlert('profileAlert', r.error || 'Could not upload profile picture.', true);
                }
            } catch (e) {
                showAlert('profileAlert', 'Could not upload profile picture.', true);
            }

            uploadProfilePictureBtn.disabled = false;
            uploadProfilePictureBtn.innerHTML = '<i class="fas fa-upload"></i> Upload';
        });
    }

    const twoFactorToggle = document.getElementById('twoFactorToggle');
    const twoFactorConfirm = document.getElementById('twoFactorConfirm');
    const twoFactorPassword = document.getElementById('twoFactorPassword');
    const twoFactorConfirmBtn = document.getElementById('twoFactorConfirmBtn');
    const twoFactorCancelBtn = document.getElementById('twoFactorCancelBtn');
    const twoFactorStatus = document.getElementById('twoFactorStatus');
    const twoFactorMethod = document.getElementById('twoFactorMethod');
    const setupAuthenticatorBtn = document.getElementById('setupAuthenticatorBtn');
    const authenticatorSetup = document.getElementById('authenticatorSetup');
    const authenticatorQr = document.getElementById('authenticatorQr');
    const authenticatorSecret = document.getElementById('authenticatorSecret');
    const authenticatorCode = document.getElementById('authenticatorCode');
    const confirmAuthenticatorBtn = document.getElementById('confirmAuthenticatorBtn');
    let twoFactorInitialState = Boolean(twoFactorToggle?.checked);
    let twoFactorTargetState = twoFactorInitialState;
    let lastAuthenticatorPassword = '';

    function syncTwoFactorStatus(enabled) {
        if (!twoFactorStatus) return;
        twoFactorStatus.textContent = enabled ? 'Enabled' : 'Disabled';
        twoFactorStatus.classList.toggle('enabled', enabled);
    }

    function closeTwoFactorConfirm() {
        twoFactorConfirm?.classList.remove('is-open');
        if (twoFactorPassword) twoFactorPassword.value = '';
        if (twoFactorToggle) twoFactorToggle.checked = twoFactorInitialState;
    }

    if (twoFactorToggle) {
        twoFactorToggle.addEventListener('change', () => {
            twoFactorTargetState = twoFactorToggle.checked;
            twoFactorConfirm?.classList.add('is-open');
            twoFactorPassword?.focus();
        });
    }

    if (twoFactorCancelBtn) {
        twoFactorCancelBtn.addEventListener('click', closeTwoFactorConfirm);
    }

    if (twoFactorPassword) {
        twoFactorPassword.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') twoFactorConfirmBtn?.click();
        });
    }

    let currentBackupCodes = [];

    function displayBackupCodes(codes) {
        const section = document.getElementById('backupCodesSection');
        const display = document.getElementById('backupCodesDisplay');
        const grid = document.getElementById('backupCodesGrid');
        if (!section || !display || !grid) return;

        currentBackupCodes = codes;
        grid.innerHTML = codes.map(c => `
            <div style="font-family:'JetBrains Mono',monospace; font-size:0.95rem; font-weight:700; color:var(--text); text-align:center; padding:10px; background:rgba(255,255,255,0.03); border:1px solid var(--border); border-radius:8px;">${c}</div>
        `).join('');

        section.style.display = 'block';
        display.style.display = 'block';
    }

    if (twoFactorConfirmBtn) {
        twoFactorConfirmBtn.addEventListener('click', async () => {
            const password = twoFactorPassword?.value || '';
            if (!password) {
                showAlert('twoFactorAlert', 'Please enter your current password.', true);
                twoFactorPassword?.focus();
                return;
            }

            twoFactorConfirmBtn.disabled = true;
            twoFactorConfirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            const r = await apiPost('api/two-factor.php', {
                action: twoFactorTargetState ? 'enable' : 'disable',
                method: twoFactorMethod?.value || 'email',
                password
            });

            if (r.success) {
                twoFactorInitialState = Boolean(r.enabled);
                if (twoFactorToggle) twoFactorToggle.checked = twoFactorInitialState;
                syncTwoFactorStatus(twoFactorInitialState);
                twoFactorConfirm?.classList.remove('is-open');
                if (twoFactorPassword) twoFactorPassword.value = '';
                showAlert('twoFactorAlert', `<i class="fas fa-check-circle"></i> ${r.message}`, false);
                
                if (r.backup_codes && r.backup_codes.length > 0) {
                    displayBackupCodes(r.backup_codes);
                } else if (!twoFactorInitialState) {
                    const section = document.getElementById('backupCodesSection');
                    if (section) section.style.display = 'none';
                }
            } else {
                if (twoFactorToggle) twoFactorToggle.checked = twoFactorInitialState;
                showAlert('twoFactorAlert', r.error || 'Failed to update two-factor authentication.', true);
            }

            twoFactorConfirmBtn.disabled = false;
            twoFactorConfirmBtn.innerHTML = '<i class="fas fa-shield-halved"></i> Confirm';
        });
    }

    if (setupAuthenticatorBtn) {
        setupAuthenticatorBtn.addEventListener('click', async () => {
            const password = prompt('Enter your current password to set up an authenticator app:');
            if (!password) return;
            setupAuthenticatorBtn.disabled = true;
            setupAuthenticatorBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            const r = await apiPost('api/two-factor.php', { action: 'setup_totp', password });
            setupAuthenticatorBtn.disabled = false;
            setupAuthenticatorBtn.innerHTML = '<i class="fas fa-qrcode"></i> Setup Authenticator';
            if (!r.success) {
                showAlert('twoFactorAlert', r.error || 'Failed to start authenticator setup.', true);
                return;
            }
            lastAuthenticatorPassword = password;
            if (authenticatorQr) authenticatorQr.src = r.qr_url;
            if (authenticatorSecret) authenticatorSecret.textContent = r.secret;
            authenticatorSetup?.classList.add('is-open');
            authenticatorCode?.focus();
        });
    }

    if (confirmAuthenticatorBtn) {
        confirmAuthenticatorBtn.addEventListener('click', async () => {
            const code = authenticatorCode?.value.trim() || '';
            if (!/^\d{6}$/.test(code)) {
                showAlert('twoFactorAlert', 'Enter the 6-digit code from your authenticator app.', true);
                return;
            }
            confirmAuthenticatorBtn.disabled = true;
            confirmAuthenticatorBtn.textContent = 'Confirming...';
            const r = await apiPost('api/two-factor.php', {
                action: 'confirm_totp',
                password: lastAuthenticatorPassword,
                code
            });
            confirmAuthenticatorBtn.disabled = false;
            confirmAuthenticatorBtn.textContent = 'Confirm Authenticator';
            if (r.success) {
                twoFactorInitialState = true;
                if (twoFactorToggle) twoFactorToggle.checked = true;
                if (twoFactorMethod) twoFactorMethod.value = 'authenticator';
                syncTwoFactorStatus(true);
                authenticatorSetup?.classList.remove('is-open');
                showAlert('twoFactorAlert', `<i class="fas fa-check-circle"></i> ${r.message}`, false);
                
                if (r.backup_codes && r.backup_codes.length > 0) {
                    displayBackupCodes(r.backup_codes);
                }
            } else {
                showAlert('twoFactorAlert', r.error || 'Authenticator setup failed.', true);
            }
        });
    }

    const copyBtn = document.getElementById('copyBackupCodesBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', async () => {
            if (currentBackupCodes.length === 0) return;
            const text = currentBackupCodes.join('\n');
            try {
                await navigator.clipboard.writeText(text);
                showToast('Backup codes copied to clipboard!', 'success');
            } catch (err) {
                const tempTextarea = document.createElement('textarea');
                tempTextarea.value = text;
                document.body.appendChild(tempTextarea);
                tempTextarea.select();
                document.execCommand('copy');
                document.body.removeChild(tempTextarea);
                showToast('Backup codes copied to clipboard!', 'success');
            }
        });
    }

    const downloadBtn = document.getElementById('downloadBackupCodesBtn');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', () => {
            if (currentBackupCodes.length === 0) return;
            const text = "MAROC PC TWO-FACTOR BACKUP CODES\n\nSave these codes securely. Each code can be used once.\n\n" + currentBackupCodes.join('\n');
            const blob = new Blob([text], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'marocpc-2fa-backup-codes.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showToast('Backup codes downloaded!', 'success');
        });
    }

    const regenerateBtn = document.getElementById('regenerateBackupCodesBtn');
    if (regenerateBtn) {
        regenerateBtn.addEventListener('click', async () => {
            const password = prompt('Please enter your current password to regenerate backup codes:');
            if (!password) return;

            regenerateBtn.disabled = true;
            regenerateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

            const r = await apiPost('api/two-factor.php', {
                action: 'regenerate_backup_codes',
                password
            });

            regenerateBtn.disabled = false;
            regenerateBtn.innerHTML = '<i class="fas fa-arrows-rotate"></i> Regenerate Codes';

            if (r.success && r.backup_codes) {
                displayBackupCodes(r.backup_codes);
                showAlert('twoFactorAlert', `<i class="fas fa-check-circle"></i> ${r.message}`, false);
            } else {
                showAlert('twoFactorAlert', r.error || 'Failed to regenerate backup codes.', true);
            }
        });
    }

    // ── Profile save ─────────────────────────────────────────
    const saveBtn = document.getElementById('saveProfileBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', async () => {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            const data = {
                nom: document.getElementById('accName')?.value.trim(),
                email: document.getElementById('accEmail')?.value.trim(),
                adresse: document.getElementById('accAddress')?.value.trim(),
                telephone: document.getElementById('accPhone')?.value.trim(),
                date_naissance: document.getElementById('accDob')?.value || ''
            };
            const r = await apiPost('api/update-profile.php', data);

            if (r.success) {
                showAlert('profileAlert', '<i class="fas fa-check-circle"></i> Profile updated successfully!', false);
            } else if (r.errors) {
                showAlert('profileAlert', Object.values(r.errors).join(' '), true);
            } else {
                showAlert('profileAlert', r.error || 'Failed to update.', true);
            }

            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-check"></i> Save Changes';
        });
    }

    // ── Password change ──────────────────────────────────────
    const passBtn = document.getElementById('changePassBtn');
    if (passBtn) {
        passBtn.addEventListener('click', async () => {
            const cur = document.getElementById('accCurrentPass')?.value;
            const neu = document.getElementById('accNewPass')?.value;
            if (!cur || !neu) { showAlert('passAlert', 'Please fill both password fields.', true); return; }

            passBtn.disabled = true;
            passBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

            const data = {
                nom: document.getElementById('accName')?.value.trim() || '',
                email: document.getElementById('accEmail')?.value.trim() || '',
                adresse: document.getElementById('accAddress')?.value.trim() || '',
                telephone: document.getElementById('accPhone')?.value.trim() || '',
                date_naissance: document.getElementById('accDob')?.value || '',
                current_password: cur,
                new_password: neu
            };
            const r = await apiPost('api/update-profile.php', data);

            if (r.success) {
                showAlert('passAlert', '<i class="fas fa-check-circle"></i> Password updated!', false);
                document.getElementById('accCurrentPass').value = '';
                document.getElementById('accNewPass').value = '';
            } else if (r.errors) {
                showAlert('passAlert', Object.values(r.errors).join(' '), true);
            } else {
                showAlert('passAlert', r.error || 'Failed to update.', true);
            }

            passBtn.disabled = false;
            passBtn.innerHTML = '<i class="fas fa-key"></i> Update Password';
        });
    }

    // ── Orders ───────────────────────────────────────────────
    function statusClass(s) {
        if (s === 'delivered' || s === 'shipped') return 'status-good';
        if (s === 'cancelled') return 'status-danger';
        return 'status-warn';
    }

    async function cancelOrder(orderId, cardEl) {
        if (!confirm(`Cancel order #${orderId}? This cannot be undone.`)) return;

        const btn = cardEl.querySelector('.btn-cancel-order');
        if (btn) btn.disabled = true;

        const r = await apiPost('api/cancel-order.php', { order_id: orderId });

        if (r.success) {
            const badge = cardEl.querySelector('.order-status');
            if (badge) {
                badge.textContent = 'Cancelled';
                badge.className = 'order-status status-danger';
            }
            if (btn) btn.remove();
            showToast('Order cancelled.', 'success');
        } else {
            if (btn) btn.disabled = false;
            showToast(r.error || 'Could not cancel order.', 'error');
        }
    }

    window.viewOrder = async function (id) {
        try {
            const res = await fetch(`api/order-detail.php?id=${id}`, { credentials: 'same-origin' });
            const data = await res.json();

            if (!data.order) {
                showToast('Order not found.', 'error');
                return;
            }

            const o = data.order;
            const items = (data.items || []);
            const history = (data.history || []);

            // Elements
            const modal = document.getElementById('trackingModalBackdrop');
            if (!modal) return;
            
            document.getElementById('trackingOrderId').innerText = `Order #${o.id} - ${o.status.toUpperCase()}`;
            document.getElementById('trackingEstimatedDelivery').innerText = o.estimated_delivery ? new Date(o.estimated_delivery).toLocaleDateString() : 'N/A';
            document.getElementById('trackingTotalCost').innerText = formatMAD(parseFloat(o.total));

            // Items List
            document.getElementById('trackingItemsList').innerHTML = items.map(i => 
                `<div class="tracking-item">
                    <span class="tracking-item-name">${i.quantity}x ${i.name_at_time || 'Product'}</span>
                    <span class="tracking-item-price">${formatMAD(parseFloat(i.price_at_time))}</span>
                </div>`
            ).join('');

            // Progress Bar
            const statuses = ['pending', 'processing', 'shipped', 'out_for_delivery', 'delivered'];
            let currentIdx = statuses.indexOf(o.status);
            if (o.status === 'cancelled') currentIdx = -1;

            statuses.forEach((st, idx) => {
                const el = document.getElementById(`step-${st}`);
                if (!el) return;
                el.classList.remove('active', 'completed');
                if (o.status === 'cancelled') {
                    el.style.borderColor = 'var(--red)';
                    el.style.color = 'var(--red)';
                } else {
                    el.style.borderColor = ''; el.style.color = '';
                    if (idx < currentIdx) el.classList.add('completed');
                    else if (idx === currentIdx) el.classList.add('active');
                }
            });

            // Assembly Tracker Logic
            const assemblyContainer = document.getElementById('trackingAssemblyContainer');
            const assemblyFill = document.getElementById('trackingAssemblyFill');
            const assemblyGuideLink = document.getElementById('assemblyGuideLink');

            if (o.assembly_status && o.assembly_status !== 'not_applicable') {
                if (assemblyContainer) assemblyContainer.style.display = 'block';
                if (assemblyGuideLink) assemblyGuideLink.href = `assembly-guide.php?id=${o.id}`;

                const aStatuses = ['gathering_parts', 'building', 'testing', 'qc_passed', 'ready'];
                let aIdx = aStatuses.indexOf(o.assembly_status);

                aStatuses.forEach((st, idx) => {
                    const el = document.getElementById(`step-assembly-${st}`);
                    if (!el) return;
                    el.classList.remove('active', 'completed');
                    if (idx < aIdx) el.classList.add('completed');
                    else if (idx === aIdx) el.classList.add('active');
                });

                if (assemblyFill) {
                    assemblyFill.style.width = aIdx > 0 ? `${(aIdx / (aStatuses.length - 1)) * 100}%` : '0%';
                }
            } else {
                if (assemblyContainer) assemblyContainer.style.display = 'none';
            }

            const fill = document.getElementById('trackingProgressFill');
            const mapProgress = document.getElementById('mapRouteProgress');
            const cities = ['city-casa', 'city-rabat', 'city-tanger', 'city-dest'];
            
            // Reset map
            cities.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.remove('active', 'reached');
            });

            if (o.status === 'cancelled') {
                fill.style.width = '100%';
                fill.style.background = 'var(--red)';
                if (mapProgress) mapProgress.style.width = '0%';
            } else {
                fill.style.background = 'var(--cyan)';
                fill.style.width = currentIdx > 0 ? `${(currentIdx / (statuses.length - 1)) * 100}%` : '0%';
                
                // Map Animation Logic
                if (mapProgress) {
                    let mapPct = 0;
                    if (currentIdx === 1) { // processing -> Casa
                        mapPct = 0; 
                        document.getElementById('city-casa').classList.add('active');
                    } else if (currentIdx === 2) { // shipped -> Rabat
                        mapPct = 33;
                        document.getElementById('city-casa').classList.add('reached');
                        document.getElementById('city-rabat').classList.add('active');
                    } else if (currentIdx === 3) { // out_for_delivery -> Tanger
                        mapPct = 66;
                        document.getElementById('city-casa').classList.add('reached');
                        document.getElementById('city-rabat').classList.add('reached');
                        document.getElementById('city-tanger').classList.add('active');
                    } else if (currentIdx === 4) { // delivered -> Destination
                        mapPct = 100;
                        cities.forEach(id => document.getElementById(id).classList.add('reached'));
                        document.getElementById('city-dest').classList.add('active');
                    }
                    mapProgress.style.width = `${mapPct}%`;
                }
            }

            // Timeline
            const timelineContainer = document.getElementById('trackingTimeline');
            if (history.length > 0) {
                timelineContainer.innerHTML = history.map(h => {
                    const date = new Date(h.changed_at).toLocaleString();
                    const stName = h.new_status.replace(/_/g, ' ').toUpperCase();
                    return `
                        <div class="timeline-event">
                            <div class="timeline-date">${date}</div>
                            <div class="timeline-status">${stName}</div>
                            ${h.notes ? `<div class="timeline-notes">${h.notes}</div>` : ''}
                        </div>`;
                }).join('');
            } else {
                timelineContainer.innerHTML = `
                    <div class="timeline-event">
                        <div class="timeline-date">${new Date(o.created_at).toLocaleString()}</div>
                        <div class="timeline-status">${o.status.replace(/_/g, ' ').toUpperCase()}</div>
                    </div>`;
            }

            modal.classList.add('is-open');

        } catch (e) {
            showToast('Failed to load order details.', 'error');
        }
    };

    // Modal Close logic
    const trackModal = document.getElementById('trackingModalBackdrop');
    if (trackModal) {
        document.getElementById('trackingModalClose').addEventListener('click', () => {
            trackModal.classList.remove('is-open');
        });
        trackModal.addEventListener('click', (e) => {
            if (e.target === trackModal) trackModal.classList.remove('is-open');
        });
    }

    async function loadOrders() {
        const c = document.getElementById('ordersContainer');
        if (!c) return;

        try {
            const res = await fetch('api/orders.php', { credentials: 'same-origin' });
            const data = await res.json();

            if (!data.orders || !data.orders.length) {
                c.innerHTML = `
                    <div style="text-align:center;padding:48px 20px;">
                        <i class="fas fa-box-open" style="font-size:3rem;color:var(--muted);margin-bottom:16px;display:block;"></i>
                        <p class="no-orders" style="font-size:1.1rem;">No orders yet</p>
                        <p style="color:var(--muted);font-size:0.88rem;margin-top:6px;">Your order history will appear here once you make a purchase.</p>
                        <a href="products.html" style="display:inline-block;margin-top:20px;padding:12px 28px;background:var(--cyan);color:#000;border-radius:10px;font-weight:700;text-decoration:none;transition:all 0.2s;">
                            <i class="fas fa-shopping-bag"></i> Start Shopping
                        </a>
                    </div>`;
                return;
            }

            const steps = ['pending', 'processing', 'shipped', 'out_for_delivery', 'delivered'];
            c.innerHTML = '<div class="orders-list modern-orders-list">' + data.orders.map(o => {
                const status = o.status || 'pending';
                const cancellable = ['pending', 'processing'].includes(status);
                const currentIndex = steps.indexOf(status);
                const items = String(o.items_preview || '')
                    .split('||')
                    .filter(Boolean)
                    .slice(0, 4)
                    .map(raw => {
                        const [name, image, quantity] = raw.split('@@');
                        return {
                            name: name || 'Product',
                            image: image || 'Images/products/placeholder-storage.svg',
                            quantity: quantity || '1'
                        };
                    });
                const overflowCount = Math.max(0, Number(o.item_count || 0) - items.length);
                const itemHtml = items.length
                    ? items.map(item => `
                        <div class="order-product-chip" title="${escapeHtml(item.name)}">
                            <img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" onerror="this.src='Images/products/placeholder-storage.svg'">
                            <span>${escapeHtml(item.quantity)}x</span>
                        </div>`).join('') + (overflowCount ? `<div class="order-product-more">+${overflowCount}</div>` : '')
                    : '<div class="order-product-empty">No item preview</div>';
                const progressHtml = status === 'cancelled'
                    ? '<div class="order-progress cancelled"><span>Cancelled</span></div>'
                    : `<div class="order-progress">${steps.map((step, idx) => `
                        <span class="${idx < currentIndex ? 'done' : (idx === currentIndex ? 'active' : '')}" title="${formatStatusText(step)}"></span>
                    `).join('')}</div>`;
                const deliveryText = o.estimated_delivery
                    ? new Date(o.estimated_delivery).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
                    : 'Not set';

                return `
                    <div class="order-card modern-order-card" data-order-id="${o.id}">
                        <div class="order-card-main">
                            <div>
                                <div class="order-id">Order #${o.id}</div>
                                <div class="order-date">${new Date(o.created_at).toLocaleDateString('en-US', {
                                    year: 'numeric', month: 'short', day: 'numeric'
                                })} · ${Number(o.item_count || 0)} item${Number(o.item_count || 0) === 1 ? '' : 's'}</div>
                            </div>
                            <div class="order-status ${statusClass(status)}">${formatStatusText(status)}</div>
                        </div>
                        <div class="order-products-strip">${itemHtml}</div>
                        ${progressHtml}
                        <div class="order-meta-grid">
                            <div><span>Total</span><strong>${formatMAD(parseFloat(o.total))}</strong></div>
                            <div><span>Payment</span><strong>${formatStatusText(o.payment_status || 'pending')}</strong></div>
                            <div><span>ETA</span><strong>${deliveryText}</strong></div>
                        </div>
                        <div class="order-card-actions">
                            <button class="btn-view" onclick="viewOrder(${o.id})"><i class="fas fa-location-dot"></i> Track</button>
                            ${cancellable ? `<button class="btn-cancel-order" data-order-id="${o.id}"><i class="fas fa-times"></i> Cancel</button>` : ''}
                        </div>
                    </div>`;
            }).join('') + '</div>';

            c.querySelectorAll('.btn-cancel-order').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = parseInt(btn.dataset.orderId, 10);
                    const card = btn.closest('.order-card');
                    cancelOrder(id, card);
                });
            });

        } catch (e) {
            c.innerHTML = '<p class="no-orders">Failed to load orders.</p>';
        }
    }

    if (document.getElementById('ordersContainer')) loadOrders();

    async function loadWishlist() {
        const c = document.getElementById('wishlistContainer');
        if (!c) return;

        try {
            const res = await fetch('api/wishlist.php?details=true', { credentials: 'same-origin' });
            const data = await res.json();

            if (!data.products || !data.products.length) {
                c.innerHTML = `
                    <div style="grid-column: 1/-1; text-align:center; padding:48px 20px;">
                        <i class="far fa-heart" style="font-size:3rem; color:var(--muted); margin-bottom:16px; display:block;"></i>
                        <p class="no-orders" style="font-size:1.1rem;">Your wishlist is empty</p>
                        <p style="color:var(--muted); font-size:0.88rem; margin-top:6px;">Save items you like to view them later.</p>
                        <a href="products.html" style="display:inline-block; margin-top:20px; padding:12px 28px; background:var(--cyan); color:#000; border-radius:10px; font-weight:700; text-decoration:none; transition:all 0.2s;">
                            <i class="fas fa-shopping-bag"></i> Browse Products
                        </a>
                    </div>`;
                return;
            }

            c.innerHTML = data.products.map(p => {
                const discount = p.oldPrice || p.old_price 
                    ? Math.round(((parseFloat(p.oldPrice || p.old_price) - parseFloat(p.price)) / parseFloat(p.oldPrice || p.old_price)) * 100)
                    : 0;
                
                const current = formatMAD(p.price);
                const priceHtml = p.old_price 
                    ? `<span class="product-price">${current}</span><span class="product-old-price">${formatMAD(p.old_price)}</span><span class="product-discount">−${discount}%</span>`
                    : `<span class="product-price">${current}</span>`;

                return `
                    <article class="product-card">
                        <div class="product-img-wrap">
                            <img src="${p.image}" alt="${p.name}" class="product-img" loading="lazy">
                            <button class="product-wishlist active" data-id="${p.id}">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                        <div class="product-card-body">
                            <p class="product-category">${(p.category || '').toUpperCase()}</p>
                            <h3 class="product-card-name">${p.name}</h3>
                            <div class="product-price-row">
                                ${priceHtml}
                            </div>
                            <div class="product-card-actions">
                                <a href="products.html" class="btn btn-primary" style="text-align: center; display: block; text-decoration: none; width: 100%;"><i class="fas fa-eye"></i> View</a>
                            </div>
                        </div>
                    </article>`;
            }).join('');

            // Bind remove button
            c.querySelectorAll('.product-wishlist').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const id = parseInt(btn.dataset.id);
                    try {
                        const r = await fetch('api/wishlist.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: 'toggle', product_id: id })
                        });
                        const d = await r.json();
                        if (d.success) {
                            if (typeof Wishlist !== 'undefined') Wishlist.init(); // sync global
                            loadWishlist(); // reload list
                        }
                    } catch (err) {}
                });
            });

        } catch (e) {
            c.innerHTML = '<p class="no-orders" style="grid-column: 1/-1;">Failed to load wishlist.</p>';
        }
    }

    if (document.getElementById('wishlistContainer')) loadWishlist();

    async function loadSavedBuilds() {
        const c = document.getElementById('savedBuildsContainer');
        if (!c) return;

        try {
            const res = await fetch('api/builder-save.php?my=1', { credentials: 'same-origin' });
            const data = await res.json();

            if (!data.success || !data.builds || !data.builds.length) {
                c.innerHTML = `
                    <div style="text-align:center;padding:48px 20px;">
                        <i class="fas fa-computer" style="font-size:3rem;color:var(--muted);margin-bottom:16px;display:block;"></i>
                        <p class="no-orders" style="font-size:1.1rem;">No saved builds yet</p>
                        <p style="color:var(--muted);font-size:0.88rem;margin-top:6px;">Use the Builder to save, share, and price full setups.</p>
                        <a href="builder.php" style="display:inline-block;margin-top:20px;padding:12px 28px;background:var(--cyan);color:#000;border-radius:10px;font-weight:700;text-decoration:none;">
                            <i class="fas fa-screwdriver-wrench"></i> Open Builder
                        </a>
                    </div>`;
                return;
            }

            c.innerHTML = `<div class="orders-list">${data.builds.map(build => {
                const shareUrl = `${window.location.origin}${window.location.pathname.replace(/account\.php$/, 'builder.php')}?build=${build.share_code}`;
                return `
                    <div class="order-card saved-build-card" data-build-id="${build.id}" style="grid-template-columns:1fr auto auto;">
                        <div class="order-card-left">
                            <div class="order-id">${build.build_name || 'Saved Build'}</div>
                            <div class="order-date">${(build.use_case || 'general').toUpperCase()} - ${new Date(build.created_at).toLocaleDateString()} - ${build.total_wattage || 0}W</div>
                        </div>
                        <div style="font-family:'JetBrains Mono',monospace;color:var(--cyan);font-weight:800;">${formatMAD(build.total_price || 0)}</div>
                        <div class="order-card-actions">
                            <a class="btn-view" href="builder.php?build=${encodeURIComponent(build.share_code)}"><i class="fas fa-eye"></i> Open</a>
                            <button class="btn-view share-build-btn" data-url="${shareUrl}"><i class="fas fa-share-alt"></i> Share</button>
                            <button class="btn-cancel-order delete-build-btn" data-id="${build.id}"><i class="fas fa-trash"></i> Delete</button>
                        </div>
                    </div>`;
            }).join('')}</div>`;

            c.querySelectorAll('.share-build-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    try {
                        await navigator.clipboard.writeText(btn.dataset.url);
                        showToast('Build link copied.', 'success');
                    } catch {
                        prompt('Copy this build link:', btn.dataset.url);
                    }
                });
            });

            c.querySelectorAll('.delete-build-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    if (!confirm('Delete this saved build?')) return;
                    const r = await apiPost('api/builder-save.php', { action: 'delete', build_id: parseInt(btn.dataset.id, 10) });
                    if (r.success) {
                        showToast('Build deleted.', 'success');
                        loadSavedBuilds();
                    } else {
                        showToast(r.message || 'Failed to delete build.', 'error');
                    }
                });
            });
        } catch (e) {
            c.innerHTML = '<p class="no-orders">Failed to load saved builds.</p>';
        }
    }

    if (document.getElementById('savedBuildsContainer')) loadSavedBuilds();

    // ── Loyalty ──────────────────────────────────────────────
    async function loadLoyalty() {
        const historyEl = document.getElementById('loyaltyHistory');
        if (!historyEl) return;

        try {
            // Fetch Balance & Progress
            const balanceRes = await fetch('api/loyalty.php?action=balance');
            const b = await balanceRes.json();
            
            let currentPoints = 0;

            if (b.success) {
                currentPoints = b.balance;
                const progBar = document.getElementById('loyaltyProgressBar');
                const progLabel = document.getElementById('loyaltyProgressLabel');
                const benefitsEl = document.getElementById('loyaltyBenefits');

                if (progBar) progBar.style.width = `${b.tier_progress}%`;
                if (progLabel) progLabel.textContent = b.tier === 'platinum' ? 'MAX TIER REACHED' : `${b.lifetime_earned} / ${b.next_tier_points} PTS TO ${b.next_tier.toUpperCase()}`;
                
                if (benefitsEl && b.tier_benefits) {
                    benefitsEl.innerHTML = b.tier_benefits.map(ben => 
                        `<div style="padding:10px 14px;background:var(--page-bg);border:1px solid var(--border);border-radius:10px;font-size:0.82rem;color:var(--text);"><i class="fas fa-check" style="color:#00f5d4;margin-right:6px;"></i> ${ben}</div>`
                    ).join('');
                }
            }

            // Fetch Catalog (Batch 2 wire-up)
            try {
                // Remove existing catalog if any to prevent duplicates on reload
                const existingCatalog = document.getElementById('loyaltyCatalogUI');
                if (existingCatalog) existingCatalog.remove();

                const catalogRes = await fetch('api/loyalty.php?action=catalog');
                const c = await catalogRes.json();
                if (c.success && c.catalog && c.catalog.length > 0) {
                    let catalogHtml = `<div id="loyaltyCatalogUI"><h3 style="margin-top:20px;margin-bottom:15px;font-size:1.1rem;"><i class="fas fa-gift" style="color:var(--cyan); margin-right:8px;"></i> Rewards Store</h3><div class="orders-list" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; margin-bottom: 30px;">`;
                    c.catalog.forEach(reward => {
                        const affordable = currentPoints >= reward.points_required;
                        catalogHtml += `
                            <div class="order-card" style="display:flex; flex-direction:column; gap:10px; padding: 15px; border: 1px solid ${affordable ? 'var(--cyan)' : 'var(--border)'};">
                                <h4 style="margin:0;color:var(--text);">${escapeHtml(reward.title)}</h4>
                                <p style="margin:0;font-size:0.85rem;color:var(--muted);flex-grow:1;">${escapeHtml(reward.description)}</p>
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
                                    <span style="font-family:'JetBrains Mono',monospace; font-weight:700; color:var(--cyan);">${reward.points_required} PTS</span>
                                    <button class="btn-view redeem-reward-btn" data-id="${reward.id}" ${affordable ? '' : 'disabled'} style="background: ${affordable ? 'var(--cyan)' : 'var(--border)'}; color: ${affordable ? '#000' : 'var(--muted)'};">
                                        Redeem
                                    </button>
                                </div>
                                <span style="font-size:0.7rem; color:var(--muted); text-align:right;">${reward.stock_remaining} left</span>
                            </div>
                        `;
                    });
                    catalogHtml += `</div></div>`;
                    
                    // Insert catalog before history
                    historyEl.insertAdjacentHTML('beforebegin', catalogHtml);

                    document.querySelectorAll('.redeem-reward-btn').forEach(btn => {
                        btn.addEventListener('click', async () => {
                            if (!confirm('Are you sure you want to redeem this reward?')) return;
                            btn.disabled = true;
                            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                            const rewardId = btn.dataset.id;
                            const r = await apiPost('api/loyalty.php', { action: 'redeem', reward_id: parseInt(rewardId, 10) });
                            if (r.success) {
                                showToast('Reward redeemed successfully!', 'success');
                                setTimeout(() => window.location.reload(), 1500);
                            } else {
                                showToast(r.message || 'Failed to redeem reward.', 'error');
                                btn.disabled = false;
                                btn.innerHTML = 'Redeem';
                            }
                        });
                    });
                }
            } catch (ce) {
                console.error("Failed to load catalog", ce);
            }

            // Fetch History
            const historyRes = await fetch('api/loyalty.php?action=history');
            const h = await historyRes.json();

            if (!h.history || !h.history.length) {
                historyEl.innerHTML = '<p class="no-orders">No points transactions yet.</p>';
                return;
            }

            historyEl.innerHTML = `
                <h3 style="margin-top:20px;margin-bottom:15px;font-size:1.1rem;"><i class="fas fa-history" style="color:var(--cyan); margin-right:8px;"></i> Points History</h3>
                <div class="orders-list">
                    ${h.history.map(t => {
                        const isGain = t.points > 0;
                        return `
                            <div class="order-card" style="grid-template-columns: 1fr 120px 120px;">
                                <div class="order-card-left">
                                    <div class="order-id" style="font-size:0.9rem;">${t.description || t.source}</div>
                                    <div class="order-date">${new Date(t.created_at).toLocaleDateString()}</div>
                                </div>
                                <div style="font-family:'JetBrains Mono',monospace; font-weight:700; color:${isGain ? '#00e676' : '#ff3d5a'}; text-align:right;">
                                    ${isGain ? '+' : ''}${t.points} PTS
                                </div>
                                <div class="order-status status-neutral" style="text-align:center; font-size:0.7rem;">${t.source.toUpperCase()}</div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;

        } catch (e) {
            historyEl.innerHTML = '<p class="no-orders">Failed to load loyalty data.</p>';
        }
    }

    if (document.getElementById('loyaltyHistory')) loadLoyalty();

    // ── Account deletion ─────────────────────────────────────
    const deleteBtn = document.getElementById('deleteAccountBtn');
    const deleteBackdrop = document.getElementById('deleteModalBackdrop');
    const deleteCancel = document.getElementById('deleteModalCancel');
    const deleteConfirm = document.getElementById('deleteModalConfirm');
    const deletePassword = document.getElementById('deleteConfirmPassword');

    function openDeleteModal() {
        if (deleteBackdrop) {
            deleteBackdrop.classList.add('is-open');
            setTimeout(() => deletePassword?.focus(), 200);
        }
    }

    function closeDeleteModal() {
        if (deleteBackdrop) {
            deleteBackdrop.classList.remove('is-open');
            if (deletePassword) deletePassword.value = '';
        }
    }

    if (deleteBtn) deleteBtn.addEventListener('click', openDeleteModal);
    if (deleteCancel) deleteCancel.addEventListener('click', closeDeleteModal);

    if (deleteBackdrop) {
        deleteBackdrop.addEventListener('click', (e) => {
            if (e.target === deleteBackdrop) closeDeleteModal();
        });
    }

    if (deleteConfirm) {
        deleteConfirm.addEventListener('click', async () => {
            const password = deletePassword?.value || '';
            if (!password) {
                showToast('Please enter your password.', 'error');
                deletePassword?.focus();
                return;
            }

            deleteConfirm.disabled = true;
            deleteConfirm.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

            const r = await apiPost('api/delete-account.php', { action: 'delete', password });

            if (r.success) {
                closeDeleteModal();
                showToast(r.message || 'Account scheduled for deletion.', 'success');
                // Reload page to show the restore banner
                setTimeout(() => window.location.reload(), 2000);
            } else {
                showToast(r.error || 'Failed to delete account.', 'error');
                deleteConfirm.disabled = false;
                deleteConfirm.innerHTML = '<i class="fas fa-trash-alt"></i> Delete Account';
            }
        });
    }

    // Enter key in password field triggers delete
    if (deletePassword) {
        deletePassword.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') deleteConfirm?.click();
        });
    }

    // ── Account restoration ──────────────────────────────────
    const restoreBtn = document.getElementById('restoreAccountBtn');
    if (restoreBtn) {
        restoreBtn.addEventListener('click', async () => {
            restoreBtn.disabled = true;
            restoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Restoring...';

            const r = await apiPost('api/delete-account.php', { action: 'restore' });

            if (r.success) {
                showToast(r.message || 'Account restored!', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast(r.error || 'Failed to restore.', 'error');
                restoreBtn.disabled = false;
                restoreBtn.innerHTML = '<i class="fas fa-undo"></i> Restore';
            }
        });
    }
})();
