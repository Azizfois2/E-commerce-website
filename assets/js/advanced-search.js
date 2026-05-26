/* ============================================
   ADVANCED SEARCH WITH AUTOCOMPLETE (PREMIUM)
   ============================================ */

(function() {
  'use strict';

  function initAdvancedSearch() {
    const searchInput = document.querySelector('.search-input');
    const searchForm = searchInput ? searchInput.closest('form') : null;
    if (!searchInput) return;

    // Create search dropdown
    const searchDropdown = document.createElement('div');
    searchDropdown.className = 'search-dropdown';
    searchDropdown.style.cssText = `
      position: absolute;
      top: calc(100% + 8px);
      right: 0;
      width: 450px;
      max-width: 90vw;
      background: var(--page-bg-2, #0a0b0e);
      border: 1px solid var(--border-cyan, rgba(0, 245, 212, 0.2));
      border-radius: 8px;
      max-height: 400px;
      overflow-y: auto;
      display: none;
      z-index: 1000;
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.45);
    `;
    
    // Ensure the wrapper has relative positioning
    const wrapper = searchInput.parentElement;
    if (getComputedStyle(wrapper).position === 'static') {
        wrapper.style.position = 'relative';
    }
    wrapper.appendChild(searchDropdown);

    let searchTimeout;
    let currentFocus = -1;

    // Simple fuzzy search function
    function fuzzySearch(query, items) {
      if (!query) return [];
      
      const lowerQuery = query.toLowerCase();
      return items.filter(item => {
        const name = item.name.toLowerCase();
        const category = item.category.toLowerCase();
        const brand = item.brand ? item.brand.toLowerCase() : '';
        
        return name.includes(lowerQuery) || 
               category.includes(lowerQuery) || 
               brand.includes(lowerQuery);
      }).slice(0, 6); // Max 6 results
    }

    // Highlight matching text
    function highlightMatch(text, query) {
      if (!query) return text;
      // Escape regex chars
      const safeQuery = query.replace(/[-\\^$*+?.()|[\]{}]/g, '\\$&');
      const regex = new RegExp(`(${safeQuery})`, 'gi');
      return text.replace(regex, '<mark style="background: var(--cyan-glow, rgba(0, 245, 212, 0.15)); color: var(--cyan, #00f5d4); border-radius: 2px; padding: 0 2px;">$1</mark>');
    }

    // Display search results
    function displaySearchResults(results, query) {
      if (results.length === 0) {
        searchDropdown.innerHTML = `
          <div style="padding: 20px; text-align: center; color: var(--muted, #5a6170);">
            <i class="fas fa-search" style="font-size: 1.5rem; margin-bottom: 10px; opacity: 0.5;"></i>
            <p style="font-size: 0.9rem;">No products found for "${query}"</p>
          </div>
        `;
        searchDropdown.style.display = 'block';
        return;
      }

      searchDropdown.innerHTML = results.map((product, index) => {
        // Safe image path with fallback
        const imgSrc = product.image ? product.image : 'cpu-product.png';
        const fallback = 'this.onerror=null;this.src=\'cpu-product.png\';';
        
        return `
        <a href="products.html?id=${product.id}" class="search-result-item" data-index="${index}" style="
          display: flex;
          align-items: center;
          gap: 15px;
          padding: 12px 16px;
          text-decoration: none;
          color: var(--white, #eef0f4);
          transition: background 0.2s;
          border-bottom: 1px solid var(--border, rgba(255, 255, 255, 0.07));
        ">
          <img src="${imgSrc}" onerror="${fallback}" alt="${product.name}" style="
            width: 44px;
            height: 44px;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 6px;
            padding: 4px;
          ">
          <div style="flex: 1; min-width: 0;">
            <strong style="display: block; margin-bottom: 4px; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
              ${highlightMatch(product.name, query)}
            </strong>
            <small style="color: var(--muted, #5a6170); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">${product.category}</small>
          </div>
          <span style="
            font-family: 'Orbitron', monospace;
            color: var(--cyan, #00f5d4);
            font-weight: 700;
            font-size: 0.85rem;
            white-space: nowrap;
          ">${product.price.toLocaleString()} MAD</span>
        </a>
      `}).join('');

      searchDropdown.style.display = 'block';
    }

    // Input Handler
    searchInput.addEventListener('input', (e) => {
      clearTimeout(searchTimeout);
      const query = e.target.value.trim();
      currentFocus = -1; // Reset focus

      if (query.length < 2) {
        searchDropdown.style.display = 'none';
        return;
      }

      searchDropdown.innerHTML = '<div style="padding: 15px; text-align: center; color: var(--cyan);"><i class="fas fa-circle-notch fa-spin"></i> Searching...</div>';
      searchDropdown.style.display = 'block';

      searchTimeout = setTimeout(() => {
        // Wait for data.js to be available
        if (typeof products !== 'undefined') {
          const results = fuzzySearch(query, products);
          displaySearchResults(results, query);
        } else {
           // Fallback if data.js failed to load
           searchDropdown.style.display = 'none';
        }
      }, 250);
    });

    // Keyboard Navigation & Form Submit
    searchInput.addEventListener('keydown', (e) => {
      let items = searchDropdown.querySelectorAll('.search-result-item');
      
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        currentFocus++;
        addActive(items);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        currentFocus--;
        addActive(items);
      } else if (e.key === 'Enter') {
        // If dropdown is open and an item is focused, navigate to it instead of submitting form
        if (searchDropdown.style.display === 'block' && currentFocus > -1) {
          e.preventDefault();
          if (items[currentFocus]) {
            items[currentFocus].click();
          }
        }
        // Otherwise, allow standard form submission to proceed natively
      } else if (e.key === 'Escape') {
        searchDropdown.style.display = 'none';
        currentFocus = -1;
      }
    });

    function addActive(items) {
      if (!items || items.length === 0) return;
      
      removeActive(items);
      if (currentFocus >= items.length) currentFocus = 0;
      if (currentFocus < 0) currentFocus = (items.length - 1);
      
      items[currentFocus].style.background = 'var(--card-bg-hover, rgba(0, 245, 212, 0.08))';
      items[currentFocus].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function removeActive(items) {
      for (let i = 0; i < items.length; i++) {
        items[i].style.background = 'transparent';
      }
    }

    // Visual hover state for mouse users
    searchDropdown.addEventListener('mouseover', (e) => {
      const item = e.target.closest('.search-result-item');
      if (item) {
        let items = searchDropdown.querySelectorAll('.search-result-item');
        removeActive(items);
        item.style.background = 'var(--card-bg-hover, rgba(0, 245, 212, 0.08))';
        currentFocus = parseInt(item.getAttribute('data-index'), 10);
      }
    });

    searchDropdown.addEventListener('mouseout', (e) => {
        const item = e.target.closest('.search-result-item');
        if(item) {
            item.style.background = 'transparent';
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
      if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
        searchDropdown.style.display = 'none';
        currentFocus = -1;
      }
    });

  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdvancedSearch);
  } else {
    initAdvancedSearch();
  }

})();
