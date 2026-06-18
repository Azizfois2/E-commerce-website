/**
 * Admin Performance Optimization Script
 * - Lazy loading for tables
 * - Virtual scrolling for large datasets
 * - Debounced search
 * - Progressive enhancement
 */

(function() {
    'use strict';

    // ============================================
    // UTILITY FUNCTIONS
    // ============================================

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    function onIdle(callback) {
        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(callback, { timeout: 1200 });
            return;
        }
        window.setTimeout(callback, 160);
    }

    // ============================================
    // LAZY TABLE LOADING
    // ============================================

    class LazyTable {
        constructor(tableElement, options = {}) {
            this.table = tableElement;
            this.tbody = tableElement.querySelector('tbody');
            this.rows = Array.from(this.tbody?.querySelectorAll('tr') || []);
            this.options = {
                batchSize: options.batchSize || 20,
                threshold: options.threshold || 500,
                ...options
            };
            this.currentIndex = 0;
            this.hiddenRows = [];
            this.init();
        }

        init() {
            if (this.rows.length <= this.options.batchSize) {
                return; // No need to lazy load
            }

            // Hide rows beyond batch size
            this.hiddenRows = this.rows.slice(this.options.batchSize);
            this.hiddenRows.forEach(row => row.style.display = 'none');

            // Add load more button or infinite scroll
            this.addLoadMoreTrigger();
        }

        addLoadMoreTrigger() {
            const loadMoreBtn = document.createElement('div');
            loadMoreBtn.className = 'load-more-trigger';
            loadMoreBtn.innerHTML = `
                <button class="button button-light" id="loadMoreRows">
                    <i class="fas fa-chevron-down"></i> Load More (${this.hiddenRows.length} remaining)
                </button>
            `;
            this.table.parentElement.appendChild(loadMoreBtn);

            loadMoreBtn.querySelector('#loadMoreRows').addEventListener('click', () => {
                this.loadMore();
            });

            // Also support infinite scroll
            this.setupInfiniteScroll(loadMoreBtn);
        }

        setupInfiniteScroll(trigger) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && this.hiddenRows.length > 0) {
                        this.loadMore();
                    }
                });
            }, { rootMargin: '200px' });

            observer.observe(trigger);
        }

        loadMore() {
            const nextBatch = this.hiddenRows.splice(0, this.options.batchSize);
            
            // Use requestAnimationFrame for smooth rendering
            requestAnimationFrame(() => {
                nextBatch.forEach(row => {
                    row.style.display = '';
                });
            });

            // Update button text
            const btn = document.querySelector('#loadMoreRows');
            if (btn) {
                if (this.hiddenRows.length === 0) {
                    btn.parentElement.style.display = 'none';
                } else {
                    btn.innerHTML = `<i class="fas fa-chevron-down"></i> Load More (${this.hiddenRows.length} remaining)`;
                }
            }
        }
    }

    // ============================================
    // OPTIMIZED SEARCH WITH DEBOUNCING
    // ============================================

    function setupOptimizedSearch() {
        const searchInputs = document.querySelectorAll('input[type="text"][name="search"], input[type="text"][name="customer"]');
        
        searchInputs.forEach(input => {
            const form = input.closest('form');
            if (!form) return;

            // Store original submit handler
            const originalSubmit = form.onsubmit;

            // Debounced auto-submit
            const debouncedSubmit = debounce(() => {
                form.submit();
            }, 500);

            input.addEventListener('input', (e) => {
                // Show loading indicator
                input.classList.add('searching');
                debouncedSubmit();
            });

            // Remove loading class on form submit
            form.addEventListener('submit', () => {
                input.classList.remove('searching');
            });
        });
    }

    // ============================================
    // TABLE ROW VIRTUALIZATION (For very large datasets)
    // ============================================

    class VirtualTable {
        constructor(container, data, rowHeight = 60) {
            this.container = container;
            this.data = data;
            this.rowHeight = rowHeight;
            this.visibleStart = 0;
            this.visibleEnd = 0;
            this.init();
        }

        init() {
            const viewportHeight = this.container.clientHeight;
            const totalHeight = this.data.length * this.rowHeight;
            
            // Create spacers
            this.topSpacer = document.createElement('div');
            this.bottomSpacer = document.createElement('div');
            
            this.container.prepend(this.topSpacer);
            this.container.append(this.bottomSpacer);

            // Throttled scroll handler
            const handleScroll = throttle(() => this.updateVisible(), 16);
            this.container.addEventListener('scroll', handleScroll);

            this.updateVisible();
        }

        updateVisible() {
            const scrollTop = this.container.scrollTop;
            const viewportHeight = this.container.clientHeight;

            this.visibleStart = Math.floor(scrollTop / this.rowHeight);
            this.visibleEnd = Math.ceil((scrollTop + viewportHeight) / this.rowHeight);

            // Render visible rows
            this.render();
        }

        render() {
            // Remove existing rows
            const existingRows = this.container.querySelectorAll('tr:not(.spacer)');
            existingRows.forEach(row => row.remove());

            // Update spacers
            this.topSpacer.style.height = `${this.visibleStart * this.rowHeight}px`;
            this.bottomSpacer.style.height = `${(this.data.length - this.visibleEnd) * this.rowHeight}px`;

            // Render visible rows
            for (let i = this.visibleStart; i < this.visibleEnd && i < this.data.length; i++) {
                const row = this.renderRow(this.data[i]);
                this.bottomSpacer.before(row);
            }
        }

        renderRow(data) {
            const row = document.createElement('tr');
            // Implement your row rendering logic here
            row.innerHTML = `<td>${data}</td>`;
            return row;
        }
    }

    // ============================================
    // IMAGE LAZY LOADING
    // ============================================

    function setupLazyImages() {
        if ('loading' in HTMLImageElement.prototype) {
            // Native lazy loading
            const images = document.querySelectorAll('img[data-src]');
            images.forEach(img => {
                img.src = img.dataset.src;
                img.loading = 'lazy';
            });
        } else {
            // Fallback to Intersection Observer
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    // ============================================
    // SKELETON LOADING STATES
    // ============================================

    function showSkeletonLoader(container) {
        const skeleton = document.createElement('div');
        skeleton.className = 'skeleton-card';
        skeleton.innerHTML = `
            <div class="loading-skeleton" style="width: 60%; height: 24px;"></div>
            <div class="loading-skeleton" style="width: 100%; height: 16px; margin-top: 12px;"></div>
            <div class="loading-skeleton" style="width: 80%; height: 16px;"></div>
            <div class="loading-skeleton" style="width: 90%; height: 16px;"></div>
        `;
        container.appendChild(skeleton);
        return skeleton;
    }

    // ============================================
    // PERFORMANCE MONITORING
    // ============================================

    function measurePerformance() {
        if (window.performance && window.performance.timing) {
            window.addEventListener('load', () => {
                setTimeout(() => {
                    const timing = performance.timing;
                    const loadTime = timing.loadEventEnd - timing.navigationStart;
                    const domReady = timing.domContentLoadedEventEnd - timing.navigationStart;
                    
                    console.log(`Page Load Time: ${loadTime}ms`);
                    console.log(`DOM Ready: ${domReady}ms`);
                    
                    // Send to analytics if needed
                    if (loadTime > 3000) {
                        console.warn('Page load is slow. Consider optimization.');
                    }
                }, 0);
            });
        }
    }

    // ============================================
    // AUTO-SAVE WITH DEBOUNCING
    // ============================================

    function setupAutoSave(formElements) {
        formElements.forEach(form => {
            const inputs = form.querySelectorAll('input, select, textarea');
            
            const debouncedSave = debounce(async () => {
                const formData = new FormData(form);
                const data = Object.fromEntries(formData);
                
                try {
                    // Show saving indicator
                    form.classList.add('saving');
                    
                    // Implement your save logic here
                    // await fetch('/api/auto-save', { method: 'POST', body: formData });
                    
                    form.classList.remove('saving');
                    form.classList.add('saved');
                    setTimeout(() => form.classList.remove('saved'), 2000);
                } catch (error) {
                    console.error('Auto-save failed:', error);
                    form.classList.remove('saving');
                }
            }, 1000);

            inputs.forEach(input => {
                input.addEventListener('input', debouncedSave);
            });
        });
    }

    // ============================================
    // INITIALIZATION
    // ============================================

    function init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }

        // Initialize lazy tables
        const tables = document.querySelectorAll('table[data-admin-lazy="true"]');
        tables.forEach(table => {
            if (table.querySelectorAll('tbody tr').length > 20) {
                new LazyTable(table, { batchSize: 20 });
            }
        });

        // Setup optimized search
        onIdle(setupOptimizedSearch);

        // Setup lazy images
        onIdle(setupLazyImages);

        // Measure performance
        onIdle(measurePerformance);
    }

    // Auto-initialize
    init();

    // Export for external use
    window.AdminPerformance = {
        LazyTable,
        VirtualTable,
        debounce,
        throttle,
        setupAutoSave,
        showSkeletonLoader
    };

})();
