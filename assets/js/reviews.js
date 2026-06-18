const Reviews = {
    state: {
        productId: null,
        container: null,
        reviews: [],
        isLoading: false,
    },

    t(key, fallback) {
        return window.__marocPcI18n?.[key] || fallback;
    },

    async loadForProduct(productId, mount = null) {
        this.state.productId = productId;
        if (mount) this.state.container = mount;
        this.state.isLoading = true;
        this.renderContainer();
        
        try {
            const res = await fetch(`api/reviews.php?product_id=${productId}`);
            const data = await res.json();
            if (data.success) {
                this.state.reviews = data.reviews;
            }
        } catch (e) {
            console.error('Failed to load reviews:', e);
        } finally {
            this.state.isLoading = false;
            this.renderList();
        }
    },

    renderContainer() {
        // Prefer the new product detail page mount, then fall back to the legacy quick-view modal.
        const target = this.state.container || document.querySelector('[data-reviews-mount]') || document.querySelector('.modal-details');
        if (!target) return;

        const useTargetAsContainer = target.matches?.('[data-reviews-mount]');
        let container = useTargetAsContainer ? target : target.querySelector('#reviewsContainer');
        if (!container && !useTargetAsContainer) {
            container = document.createElement('div');
            
            // Insert it at the end of the modal details
            target.appendChild(container);
        }

        container.id = 'reviewsContainer';
        container.classList.add('product-reviews-section');
        this.state.container = container;

        container.innerHTML = `
            <div class="reviews-header">
                <h3>${this.t('reviewCustomerReviews', 'Customer Reviews')}</h3>
                <button class="btn btn-outline btn-small" id="writeReviewBtn">${this.t('reviewWriteReview', 'Write a Review')}</button>
            </div>
            <div id="reviewFormContainer" style="display:none; margin-bottom: 20px;">
                <form id="reviewForm" class="review-form">
                    <div class="form-group">
                        <label>${this.t('reviewRating', 'Rating')}</label>
                        <div class="star-rating-input">
                            ${[5,4,3,2,1].map(num => `
                                <input type="radio" id="star${num}" name="rating" value="${num}" required>
                                <label for="star${num}" title="${this.t('reviewStarsTemplate', '{count} stars').replace('{count}', num)}"><i class="fas fa-star"></i></label>
                            `).join('')}
                        </div>
                    </div>
                    <div class="form-group">
                        <label>${this.t('reviewLabel', 'Review')}</label>
                        <div class="review-guidance">
                            <span>${this.t('reviewGuidance', 'Useful pattern: Pros / Cons / Used with / Would you buy again?')}</span>
                        </div>
                        <textarea name="review_text" required placeholder="${this.t('reviewPlaceholder', 'Pros:\\nCons:\\nUsed with: CPU, GPU, case or build type\\nWould you recommend it?')}" rows="5" style="width:100%; padding: 10px; background: var(--input-bg); border: 1px solid var(--border); color: var(--text); border-radius: 4px;"></textarea>
                    </div>
                    <div class="review-qna-prompt">
                        <i class="fas fa-circle-question"></i>
                        ${this.t('reviewQnaPrompt', 'Questions about compatibility? Mention your CPU, GPU, motherboard, case, and PSU so Maroc PC can answer clearly.')}
                    </div>
                    <div style="text-align: right; margin-top: 10px;">
                        <button type="button" class="btn btn-outline btn-small" id="cancelReviewBtn" style="margin-right: 8px;">${this.t('reviewCancel', 'Cancel')}</button>
                        <button type="submit" class="btn btn-primary btn-small">${this.t('reviewSubmitReview', 'Submit Review')}</button>
                    </div>
                    <div id="reviewFormMsg" style="margin-top: 10px; font-size: 0.85rem;"></div>
                </form>
            </div>
            <div id="reviewsList" class="reviews-list">
                ${this.state.isLoading ? `<div class="text-center" style="padding: 20px;"><i class="fas fa-spinner fa-spin"></i> ${this.t('reviewLoadingReviews', 'Loading reviews...')}</div>` : ''}
            </div>
        `;

        this.bindEvents();
    },

    renderList() {
        const list = this.state.container?.querySelector('#reviewsList') || document.getElementById('reviewsList');
        if (!list) return;

        if (this.state.isLoading) return;

        if (this.state.reviews.length === 0) {
            list.innerHTML = `<p style="color: var(--muted); text-align: center; padding: 20px 0;">${this.t('reviewNoReviews', 'No reviews yet. Be the first to review this product!')}</p>`;
            return;
        }

        list.innerHTML = this.state.reviews.map(rev => `
            <div class="review-item" data-id="${rev.id}">
                <div class="review-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                    <div>
                        <strong style="color: var(--white);">${rev.client_name}</strong>
                        ${rev.is_verified ? `<span class="verified-badge"><i class="fas fa-check-circle"></i> ${this.t('reviewVerifiedPurchase', 'Verified Purchase')}</span>` : ''}
                    </div>
                    <div class="review-date" style="color: var(--muted); font-size: 0.8rem;">
                        ${new Date(rev.created_at).toLocaleDateString(window.__marocPcLocale || document.documentElement.lang || undefined)}
                    </div>
                </div>
                <div class="review-stars" style="color: #ffb400; font-size: 0.85rem; margin-bottom: 8px;">
                    ${this.renderStars(rev.rating)}
                </div>
                <div class="review-text" style="color: var(--text); font-size: 0.9rem; line-height: 1.5; margin-bottom: 12px;">
                    ${this.escapeHTML(rev.review_text)}
                </div>
                <div class="review-actions" style="display: flex; gap: 12px; font-size: 0.8rem;">
                    <span style="color: var(--muted);">${this.t('reviewWasHelpful', 'Was this helpful?')}</span>
                    <button class="vote-btn ${rev.user_vote === 'helpful' ? 'active' : ''}" data-vote="helpful">
                        <i class="fas fa-thumbs-up"></i> ${this.t('reviewHelpfulYes', 'Yes')} (${rev.helpful_count})
                    </button>
                    <button class="vote-btn ${rev.user_vote === 'unhelpful' ? 'active' : ''}" data-vote="unhelpful">
                        <i class="fas fa-thumbs-down"></i> ${this.t('reviewHelpfulNo', 'No')} (${rev.unhelpful_count})
                    </button>
                </div>
            </div>
        `).join('');

        this.bindVoteEvents();
    },

    renderStars(rating) {
        let html = '';
        for (let i = 1; i <= 5; i++) {
            html += i <= rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
        }
        return html;
    },

    escapeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    bindEvents() {
        const root = this.state.container || document;
        const writeBtn = root.querySelector('#writeReviewBtn');
        const formContainer = root.querySelector('#reviewFormContainer');
        const cancelBtn = root.querySelector('#cancelReviewBtn');
        const form = root.querySelector('#reviewForm');

        if (writeBtn) {
            writeBtn.addEventListener('click', () => {
                formContainer.style.display = 'block';
                writeBtn.style.display = 'none';
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                formContainer.style.display = 'none';
                writeBtn.style.display = 'inline-flex';
                form.reset();
                const msg = root.querySelector('#reviewFormMsg');
                if (msg) msg.textContent = '';
            });
        }

        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const msgEl = root.querySelector('#reviewFormMsg');
                if (!msgEl) return;
                msgEl.textContent = this.t('reviewSubmitting', 'Submitting...');
                msgEl.style.color = 'var(--text)';

                const formData = new FormData(form);
                const body = {
                    action: 'create',
                    product_id: this.state.productId,
                    rating: parseInt(formData.get('rating')),
                    review_text: formData.get('review_text')
                };

                try {
                    const res = await fetch('api/reviews.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(body)
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        msgEl.style.color = 'var(--green)';
                        msgEl.textContent = data.message;
                        form.reset();
                        setTimeout(() => {
                            formContainer.style.display = 'none';
                            writeBtn.style.display = 'inline-flex';
                            this.loadForProduct(this.state.productId);
                        }, 1500);
                    } else {
                        msgEl.style.color = 'var(--red)';
                        msgEl.textContent = data.error || this.t('reviewSubmitFailed', 'Failed to submit review. You must be logged in.');
                    }
                } catch (err) {
                    msgEl.style.color = 'var(--red)';
                    msgEl.textContent = this.t('networkError', 'Network error. Please try again.');
                }
            });
        }
    },

    bindVoteEvents() {
        const root = this.state.container || document;
        root.querySelectorAll('.vote-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const btnEl = e.currentTarget;
                const reviewItem = btnEl.closest('.review-item');
                const reviewId = parseInt(reviewItem.dataset.id);
                const vote = btnEl.dataset.vote;

                try {
                    const res = await fetch('api/reviews.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'vote', review_id: reviewId, vote })
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        // Reload reviews to get updated counts and user vote state
                        this.loadForProduct(this.state.productId);
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(data.error || this.t('reviewVoteFailed', 'Failed to vote'), 'error');
                        } else {
                            alert(data.error || this.t('reviewVoteLogin', 'Failed to vote. Please log in.'));
                        }
                    }
                } catch (err) {
                    console.error('Vote error:', err);
                }
            });
        });
    }
};
