/**
 * community-builds.js — Community Build Showcases
 * Wires api/community-builds.php to the builder page UI
 */
const CommunityBuilds = (() => {
    const API = 'api/community-builds.php';
    let currentPage = 1;
    let currentSort = 'newest';
    let loaded = false;

    function init() {
        const sortSelect = document.getElementById('cbSortSelect');
        if (sortSelect) {
            sortSelect.addEventListener('change', () => {
                currentSort = sortSelect.value;
                currentPage = 1;
                loadShowcases();
            });
        }

        // Robust Event Delegation for Upvote & Favorite Actions
        const grid = document.getElementById('communityBuildsGrid');
        if (grid) {
            grid.addEventListener('click', (e) => {
                const upvoteBtn = e.target.closest('[data-cb-upvote]');
                const favoriteBtn = e.target.closest('[data-cb-favorite]');
                
                if (upvoteBtn) {
                    e.preventDefault();
                    const id = parseInt(upvoteBtn.getAttribute('data-cb-upvote'), 10);
                    if (id) interact(id, 'upvote', upvoteBtn);
                } else if (favoriteBtn) {
                    e.preventDefault();
                    const id = parseInt(favoriteBtn.getAttribute('data-cb-favorite'), 10);
                    if (id) interact(id, 'favorite', favoriteBtn);
                }
            });
        }

        // Observe tab visibility to lazy-load
        const tab = document.getElementById('tab-community-builds');
        if (tab) {
            const observer = new MutationObserver(() => {
                if (tab.classList.contains('active') && !loaded) {
                    loaded = true;
                    loadShowcases();
                }
            });
            observer.observe(tab, { attributes: true, attributeFilter: ['class'] });

            // Also check immediately
            if (tab.classList.contains('active')) {
                loaded = true;
                loadShowcases();
            }
        }
    }

    async function loadShowcases(page = 1) {
        const t = (key, fallback) => window.__marocPcI18n?.[key] || fallback;
        currentPage = page;
        const grid = document.getElementById('communityBuildsGrid');
        const pagination = document.getElementById('communityBuildsPagination');
        if (!grid) return;

        grid.innerHTML = `<div class="cb-loading"><i class="fas fa-spinner fa-spin"></i> ${t('cbLoading', 'Loading community builds...')}</div>`;

        try {
            const res = await fetch(`${API}?action=list&sort=${currentSort}&page=${page}`, { credentials: 'same-origin' });
            const data = await res.json();

            if (data.error) {
                grid.innerHTML = `
                    <div class="cb-empty">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Unable to load builds</h3>
                        <p>${escapeHTML(data.error)}</p>
                    </div>
                `;
                if (pagination) pagination.innerHTML = '';
                return;
            }

            if (!data.success || !data.showcases || data.showcases.length === 0) {
                grid.innerHTML = `
                    <div class="cb-empty">
                        <i class="fas fa-users"></i>
                        <h3>No community builds yet</h3>
                        <p>Be the first! Configure a build in the PC Builder tab, then publish it here.</p>
                    </div>
                `;
                if (pagination) pagination.innerHTML = '';
                return;
            }

            grid.innerHTML = data.showcases.map(s => renderShowcaseCard(s)).join('');

            // Pagination
            if (pagination && data.pages > 1) {
                let paginationHTML = '';
                for (let i = 1; i <= data.pages; i++) {
                    paginationHTML += `<button class="cb-page-btn ${i === currentPage ? 'active' : ''}" onclick="CommunityBuilds.loadShowcases(${i})">${i}</button>`;
                }
                pagination.innerHTML = paginationHTML;
            } else if (pagination) {
                pagination.innerHTML = '';
            }
        } catch (e) {
            console.error('Failed to load community builds:', e);
            grid.innerHTML = `
                <div class="cb-empty">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Unable to load builds</h3>
                    <p>Community builds service is temporarily unavailable.</p>
                </div>
            `;
        }
    }

    function renderShowcaseCard(showcase) {
        const t = (key, fallback) => window.__marocPcI18n?.[key] || fallback;
        const gallery = showcase.image_gallery || [];
        const thumb = gallery[0] || 'logo.png';
        const locale = document.documentElement.lang || 'en';
        const date = new Date(showcase.created_at).toLocaleDateString(locale === 'ar' ? 'ar-MA' : locale === 'fr' ? 'fr-FR' : 'en-US', { month: 'short', day: 'numeric', year: 'numeric' });

        const upvoteActive = showcase.user_upvoted ? 'active' : '';
        const favoriteActive = showcase.user_favorited ? 'active' : '';

        return `
            <div class="cb-card" onclick="CommunityBuilds.showDetail(${showcase.id})">
                <div class="cb-card-thumb">
                    <img src="${thumb}" alt="${escapeHTML(showcase.title)}" onerror="this.src='logo.png'">
                    <span class="cb-card-views"><i class="fas fa-eye"></i> ${showcase.view_count || 0}</span>
                </div>
                <div class="cb-card-body">
                    <h3 class="cb-card-title notranslate" translate="no">${escapeHTML(showcase.title)}</h3>
                    <p class="cb-card-desc notranslate" translate="no">${escapeHTML(showcase.description || t('cbNoDescription', 'No description provided.'))}</p>
                    <div class="cb-card-meta">
                        <span class="cb-card-author notranslate" translate="no"><i class="fas fa-user"></i> ${escapeHTML(showcase.author_name || t('cbAnonymous', 'Anonymous'))}</span>
                        <span class="cb-card-date notranslate" translate="no"><i class="fas fa-calendar"></i> ${date}</span>
                    </div>
                    <div class="cb-card-actions">
                        <button class="cb-action-btn ${upvoteActive}" onclick="event.stopPropagation(); CommunityBuilds.handleInteract(${showcase.id}, 'upvote', this)" title="${t('cbUpvote', 'Upvote')}">
                            <i class="fas fa-arrow-up"></i> <span>${showcase.upvotes || 0}</span>
                        </button>
                        <button class="cb-action-btn ${favoriteActive}" onclick="event.stopPropagation(); CommunityBuilds.handleInteract(${showcase.id}, 'favorite', this)" title="${t('cbFavorite', 'Favorite')}">
                            <i class="fas fa-heart"></i> <span>${showcase.favorites || 0}</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    async function showDetail(showcaseId) {
        const t = (key, fallback) => window.__marocPcI18n?.[key] || fallback;
        const formatPrice = (price) => {
            const locale = document.documentElement.lang || 'en';
            const currency = locale === 'ar' ? 'د.م.' : locale === 'fr' ? 'DH' : 'DH';
            return price ? price.toLocaleString() + ' ' + currency : '';
        };
        const translateCategory = (cat) => {
            const categoryMap = {
                'cpu': t('catCpu', 'CPU'),
                'motherboard': t('catMotherboard', 'MOTHERBOARD'),
                'gpu': t('catGpu', 'GPU'),
                'ram': t('catRam', 'RAM'),
                'storage': t('catStorage', 'STORAGE'),
                'psu': t('catPsu', 'PSU'),
                'case': t('catCase', 'CASE'),
                'cooling': t('catCooling', 'COOLING'),
                'monitor': t('catMonitor', 'MONITOR'),
                'accessories': t('catAccessories', 'ACCESSORIES')
            };
            return categoryMap[cat.toLowerCase()] || cat.toUpperCase();
        };
        
        try {
            const res = await fetch(`${API}?action=view&id=${showcaseId}`, { credentials: 'same-origin' });
            const data = await res.json();

            if (data.error || !data.success || !data.showcase) {
                if (typeof showToast === 'function') showToast(data.error || t('cbFailedLoad', 'Failed to load build details.'), 'error');
                return;
            }

            const showcase = data.showcase;
            const config = showcase.config_json || showcase.config || {};
            
            // Check if config is empty
            if (Object.keys(config).length === 0) {
                if (typeof showToast === 'function') showToast(t('cbNoComponents', 'This build has no components.'), 'error');
                return;
            }
            
            // Create modal HTML
            const modalHTML = `
                <div class="cb-detail-modal" id="cbDetailModal" onclick="CommunityBuilds.closeDetail(event)">
                    <div class="cb-detail-content" onclick="event.stopPropagation()">
                        <button class="cb-detail-close" onclick="CommunityBuilds.closeDetail()"><i class="fas fa-times"></i></button>
                        <div class="cb-detail-header">
                            <h2 class="notranslate" translate="no">${escapeHTML(showcase.title)}</h2>
                            <p class="notranslate" translate="no">${escapeHTML(showcase.description || '')}</p>
                            <div class="cb-detail-meta">
                                <span class="notranslate" translate="no"><i class="fas fa-user"></i> ${escapeHTML(showcase.author_name || 'Anonymous')}</span>
                                <span><i class="fas fa-eye"></i> ${showcase.view_count || 0} ${t('cbViews', 'views')}</span>
                                <span><i class="fas fa-arrow-up"></i> ${showcase.upvotes || 0} ${t('cbUpvotes', 'upvotes')}</span>
                                <span><i class="fas fa-heart"></i> ${showcase.favorites || 0} ${t('cbFavorites', 'favorites')}</span>
                            </div>
                        </div>
                        <div class="cb-detail-body">
                            <h3><i class="fas fa-list"></i> ${t('cbBuildComponents', 'Build Components')}</h3>
                            <div class="cb-components-list">
                                ${Object.entries(config).map(([category, component]) => `
                                    <div class="cb-component-item">
                                        <div class="cb-component-category">${translateCategory(category)}</div>
                                        <div class="cb-component-details">
                                            <strong class="notranslate" translate="no">${escapeHTML(component.name)}</strong>
                                            <span class="notranslate" translate="no">${component.brand || ''}</span>
                                        </div>
                                        <div class="cb-component-price notranslate" translate="no">${formatPrice(component.price)}</div>
                                    </div>
                                `).join('')}
                            </div>
                            <div class="cb-detail-total">
                                <strong>${t('cbTotalPrice', 'Total Price:')}</strong>
                                <span class="notranslate" translate="no">${formatPrice(Object.values(config).reduce((sum, c) => sum + (c.price || 0), 0))}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Remove existing modal if any
            const existingModal = document.getElementById('cbDetailModal');
            if (existingModal) existingModal.remove();

            // Add new modal to body
            document.body.insertAdjacentHTML('beforeend', modalHTML);

            // Show modal with animation
            setTimeout(() => {
                const modal = document.getElementById('cbDetailModal');
                if (modal) modal.classList.add('show');
            }, 10);

        } catch (e) {
            console.error('Failed to load build detail:', e);
            if (typeof showToast === 'function') showToast(t('cbFailedLoad', 'Failed to load build details.'), 'error');
        }
    }

    function closeDetail(event) {
        const modal = document.getElementById('cbDetailModal');
        if (!modal) return;
        
        // Only close if clicking the overlay or close button
        if (!event || event.target === modal || event.target.closest('.cb-detail-close')) {
            modal.classList.remove('show');
            setTimeout(() => modal.remove(), 300);
        }
    }

    async function interact(showcaseId, type, btn) {
        const t = (key, fallback) => window.__marocPcI18n?.[key] || fallback;
        
        try {
            const res = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ action: 'interact', showcase_id: showcaseId, type })
            });

            if (res.status === 401) {
                if (typeof showToast === 'function') {
                    showToast(t('cbPleaseLoginInteract', 'Please log in to upvote or favorite community builds.'), 'error');
                } else {
                    alert(t('cbPleaseLoginInteract', 'Please log in to upvote or favorite community builds.'));
                }
                const modal = document.getElementById('roleModal');
                if (modal) modal.style.display = 'flex';
                return;
            }

            const data = await res.json();
            if (data.error) {
                if (typeof showToast === 'function') showToast(data.error, 'error');
                return;
            }
            if (data.success) {
                const countSpan = btn.querySelector('span');
                if (countSpan) countSpan.textContent = data.count;
                btn.classList.toggle('active', data.status === 'added');
                if (typeof showToast === 'function') {
                    const msg = data.status === 'added' 
                        ? (type === 'upvote' ? t('cbUpvoteAdded', 'Upvote added!') : t('cbFavoriteAdded', 'Favorite added!'))
                        : (type === 'upvote' ? t('cbUpvoteRemoved', 'Upvote removed.') : t('cbFavoriteRemoved', 'Favorite removed.'));
                    showToast(msg, 'success');
                }
            }
        } catch (e) {
            console.error('Interaction failed:', e);
        }
    }

    async function openPublishModal() {
        const t = (key, fallback) => window.__marocPcI18n?.[key] || fallback;
        
        // Check if user is logged in first
        try {
            const checkRes = await fetch('auth-status.php', { credentials: 'same-origin' });
            const authData = await checkRes.json();
            console.log('Auth check response:', authData);
            
            if (!authData.loggedIn) {
                console.log('User not logged in, showing login modal');
                if (typeof showToast === 'function') {
                    showToast(t('cbPleaseLogin', 'Please log in to publish your build.'), 'error');
                } else {
                    alert(t('cbPleaseLogin', 'Please log in to publish your build.'));
                }
                const modal = document.getElementById('roleModal');
                if (modal) modal.style.display = 'flex';
                return;
            }
            console.log('User is logged in, opening publish modal');
        } catch (e) {
            console.error('Auth check failed:', e);
            // If auth check fails, allow modal to open anyway
        }
        
        const modal = document.getElementById('cbPublishModal');
        if (!modal) return;

        // Pre-fill title from build name
        const buildName = document.getElementById('buildNameInput')?.value || 'My Build';
        const titleInput = document.getElementById('cbPublishTitle');
        if (titleInput) titleInput.value = buildName;

        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('is-open'), 10);
    }

    function closePublishModal() {
        const modal = document.getElementById('cbPublishModal');
        if (!modal) return;
        modal.classList.remove('is-open');
        setTimeout(() => modal.style.display = 'none', 300);
    }

    async function publish() {
        const t = (key, fallback) => window.__marocPcI18n?.[key] || fallback;
        const title = document.getElementById('cbPublishTitle')?.value?.trim();
        const description = document.getElementById('cbPublishDesc')?.value?.trim() || '';

        if (!title) {
            if (typeof showToast === 'function') showToast(t('cbPleaseGiveName', 'Please give your build a name.'), 'error');
            return;
        }

        // Get current build config from PCBuilder
        const selected = typeof PCBuilder !== 'undefined' && PCBuilder.getSelected ? PCBuilder.getSelected() : {};
        const config = {};
        Object.entries(selected).forEach(([key, product]) => {
            if (product) {
                config[key] = { id: product.id, name: product.name, price: product.price, brand: product.brand };
            }
        });

        if (Object.keys(config).length === 0) {
            if (typeof showToast === 'function') showToast(t('cbSelectComponent', 'Select at least one component before publishing.'), 'error');
            return;
        }

        try {
            const res = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ action: 'publish', title, description, config })
            });

            if (res.status === 401) {
                if (typeof showToast === 'function') {
                    showToast(t('cbPleaseLogin', 'Please log in to publish your build.'), 'error');
                } else {
                    alert(t('cbPleaseLogin', 'Please log in to publish your build.'));
                }
                closePublishModal();
                const modal = document.getElementById('roleModal');
                if (modal) modal.style.display = 'flex';
                return;
            }

            const data = await res.json();

            if (data.error) {
                if (typeof showToast === 'function') showToast(data.error, 'error');
                return;
            }

            if (data.success) {
                closePublishModal();
                if (typeof showToast === 'function') showToast(t('cbPublished', 'Your build has been published! 🎉'), 'success');
                loaded = false; // Force reload
                loadShowcases(1);
            }
        } catch (e) {
            console.error('Publish failed:', e);
            if (typeof showToast === 'function') showToast(t('cbPublishFailed', 'Failed to publish build.'), 'error');
        }
    }

    function escapeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    return { loadShowcases, openPublishModal, closePublishModal, publish, showDetail, closeDetail, handleInteract: interact };
})();
