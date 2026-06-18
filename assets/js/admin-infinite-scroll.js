/**
 * Admin Infinite Scroll - Load products/orders as you scroll
 * Fully internationalized - no hardcoded text
 */

(function() {
    'use strict';

    // Get translations from global object (set by PHP)
    const i18n = window.adminI18n || {};
    const phrases = {
        loadingMore: i18n.infiniteScroll?.loading || 'Loading more...',
        allLoaded: i18n.infiniteScroll?.allLoaded || 'All items loaded',
        loadFailed: i18n.infiniteScroll?.loadFailed || 'Failed to load more.',
        refreshPage: i18n.infiniteScroll?.refreshPage || 'Refresh page'
    };

    class InfiniteScroll {
        constructor(tableSelector, options = {}) {
            this.table = document.querySelector(tableSelector);
            if (!this.table) return;

            this.tbody = this.table.querySelector('tbody');
            this.currentPage = 1;
            this.isLoading = false;
            this.hasMore = true;
            
            this.options = {
                perPage: options.perPage || 30,
                threshold: options.threshold || 300,
                endpoint: options.endpoint || window.location.pathname,
                ...options
            };

            this.init();
        }

        init() {
            // Get current filters from URL
            this.filters = this.getFiltersFromURL();
            
            // Hide existing pagination if present
            const pagination = document.querySelector('.pagination');
            if (pagination) {
                pagination.style.display = 'none';
            }

            // Add scroll listener
            window.addEventListener('scroll', this.handleScroll.bind(this));
            
            // Add loading indicator
            this.createLoadingIndicator();
        }

        getFiltersFromURL() {
            const params = new URLSearchParams(window.location.search);
            const filters = {};
            
            for (const [key, value] of params.entries()) {
                if (key !== 'page') {
                    filters[key] = value;
                }
            }
            
            return filters;
        }

        handleScroll() {
            if (this.isLoading || !this.hasMore) return;

            const scrollPosition = window.innerHeight + window.scrollY;
            const threshold = document.documentElement.scrollHeight - this.options.threshold;

            if (scrollPosition >= threshold) {
                this.loadMore();
            }
        }

        createLoadingIndicator() {
            this.loadingIndicator = document.createElement('div');
            this.loadingIndicator.className = 'infinite-scroll-loader';
            this.loadingIndicator.innerHTML = `
                <div class="loader-spinner"></div>
                <span class="loader-text">${phrases.loadingMore}</span>
            `;
            this.loadingIndicator.style.display = 'none';
            
            // Insert after table
            this.table.parentElement.appendChild(this.loadingIndicator);
        }

        showLoading() {
            this.loadingIndicator.style.display = 'flex';
        }

        hideLoading() {
            this.loadingIndicator.style.display = 'none';
        }

        async loadMore() {
            this.isLoading = true;
            this.showLoading();
            this.currentPage++;

            try {
                const params = new URLSearchParams({
                    ...this.filters,
                    page: this.currentPage,
                    ajax: '1'
                });

                const response = await fetch(`${this.options.endpoint}?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load data');
                }

                const data = await response.json();

                if (data.html && data.html.trim() !== '') {
                    this.appendRows(data.html);
                    this.hasMore = data.hasMore || false;
                } else {
                    this.hasMore = false;
                    this.showEndMessage();
                }

            } catch (error) {
                console.error('Infinite scroll error:', error);
                this.showError();
                this.hasMore = false;
            } finally {
                this.isLoading = false;
                this.hideLoading();
            }
        }

        appendRows(html) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            const rows = tempDiv.querySelectorAll('tr');
            rows.forEach(row => {
                // Add fade-in animation
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                this.tbody.appendChild(row);
                
                // Trigger animation
                requestAnimationFrame(() => {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                });
            });
        }

        showEndMessage() {
            const endMsg = document.createElement('div');
            endMsg.className = 'infinite-scroll-end';
            endMsg.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>${phrases.allLoaded}</span>
            `;
            this.loadingIndicator.replaceWith(endMsg);
        }

        showError() {
            const errorMsg = document.createElement('div');
            errorMsg.className = 'infinite-scroll-error';
            errorMsg.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                <span>${phrases.loadFailed} <button onclick="location.reload()">${phrases.refreshPage}</button></span>
            `;
            this.loadingIndicator.after(errorMsg);
        }
    }

    // Auto-initialize on admin pages with tables
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = window.location.pathname;

        // Admin Products Page
        if (currentPage.includes('admin-products.php')) {
            const table = document.querySelector('.table-card table');
            if (table && table.querySelectorAll('tbody tr').length >= 20) {
                new InfiniteScroll('.table-card table', {
                    perPage: 30,
                    endpoint: 'admin-products.php'
                });
            }
        }

        // Admin Orders Page
        if (currentPage.includes('admin-orders.php')) {
            const table = document.querySelector('.table-card table');
            if (table && table.querySelectorAll('tbody tr').length >= 20) {
                new InfiniteScroll('.table-card table', {
                    perPage: 25,
                    endpoint: 'admin-orders.php'
                });
            }
        }

        // Admin Customers Page
        if (currentPage.includes('admin-customers.php')) {
            const table = document.querySelector('.table-card table');
            if (table && table.querySelectorAll('tbody tr').length >= 20) {
                new InfiniteScroll('.table-card table', {
                    perPage: 30,
                    endpoint: 'admin-customers.php'
                });
            }
        }
    });

    // Export for manual initialization
    window.AdminInfiniteScroll = InfiniteScroll;

})();
