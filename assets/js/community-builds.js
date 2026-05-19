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
        currentPage = page;
        const grid = document.getElementById('communityBuildsGrid');
        const pagination = document.getElementById('communityBuildsPagination');
        if (!grid) return;

        grid.innerHTML = '<div class="cb-loading"><i class="fas fa-spinner fa-spin"></i> Loading community builds...</div>';

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
        const gallery = showcase.image_gallery || [];
        const thumb = gallery[0] || 'logo.png';
        const date = new Date(showcase.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

        const upvoteActive = showcase.user_upvoted ? 'active' : '';
        const favoriteActive = showcase.user_favorited ? 'active' : '';

        return `
            <div class="cb-card">
                <div class="cb-card-thumb">
                    <img src="${thumb}" alt="${showcase.title}" onerror="this.src='logo.png'">
                    <span class="cb-card-views"><i class="fas fa-eye"></i> ${showcase.view_count || 0}</span>
                </div>
                <div class="cb-card-body">
                    <h3 class="cb-card-title">${escapeHTML(showcase.title)}</h3>
                    <p class="cb-card-desc">${escapeHTML(showcase.description || 'No description provided.')}</p>
                    <div class="cb-card-meta">
                        <span class="cb-card-author"><i class="fas fa-user"></i> ${escapeHTML(showcase.author_name || 'Anonymous')}</span>
                        <span class="cb-card-date"><i class="fas fa-calendar"></i> ${date}</span>
                    </div>
                    <div class="cb-card-actions">
                        <button class="cb-action-btn ${upvoteActive}" data-cb-upvote="${showcase.id}" title="Upvote">
                            <i class="fas fa-arrow-up"></i> <span>${showcase.upvotes || 0}</span>
                        </button>
                        <button class="cb-action-btn ${favoriteActive}" data-cb-favorite="${showcase.id}" title="Favorite">
                            <i class="fas fa-heart"></i> <span>${showcase.favorites || 0}</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    async function interact(showcaseId, type, btn) {
        try {
            const res = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ action: 'interact', showcase_id: showcaseId, type })
            });

            if (res.status === 401) {
                if (typeof showToast === 'function') {
                    showToast('Please log in to upvote or favorite community builds.', 'error');
                } else {
                    alert('Please log in to upvote or favorite community builds.');
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
                    showToast(data.status === 'added' ? `${type === 'upvote' ? 'Upvote' : 'Favorite'} added!` : `${type === 'upvote' ? 'Upvote' : 'Favorite'} removed.`, 'success');
                }
            }
        } catch (e) {
            console.error('Interaction failed:', e);
        }
    }

    function openPublishModal() {
        const modal = document.getElementById('cbPublishModal');
        if (!modal) return;

        // Pre-fill title from build name
        const buildName = document.getElementById('buildNameInput')?.value || 'My Build';
        const titleInput = document.getElementById('cbPublishTitle');
        if (titleInput) titleInput.value = buildName;

        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('show'), 10);
    }

    function closePublishModal() {
        const modal = document.getElementById('cbPublishModal');
        if (!modal) return;
        modal.classList.remove('show');
        setTimeout(() => modal.style.display = 'none', 300);
    }

    async function publish() {
        const title = document.getElementById('cbPublishTitle')?.value?.trim();
        const description = document.getElementById('cbPublishDesc')?.value?.trim() || '';

        if (!title) {
            if (typeof showToast === 'function') showToast('Please give your build a name.', 'error');
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
            if (typeof showToast === 'function') showToast('Select at least one component before publishing.', 'error');
            return;
        }

        try {
            const res = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ action: 'publish', title, description, config })
            });
            const data = await res.json();

            if (data.error) {
                if (typeof showToast === 'function') showToast(data.error, 'error');
                return;
            }

            if (data.success) {
                closePublishModal();
                if (typeof showToast === 'function') showToast('Your build has been published! 🎉', 'success');
                loaded = false; // Force reload
                loadShowcases(1);
            }
        } catch (e) {
            console.error('Publish failed:', e);
            if (typeof showToast === 'function') showToast('Failed to publish build.', 'error');
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

    return { loadShowcases, openPublishModal, closePublishModal, publish };
})();
