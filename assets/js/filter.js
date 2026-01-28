(() => {
  const pills = document.querySelectorAll('[data-category]');
  if (!pills.length) {
    console.log('[Filter] No category pills found');
    return;
  }

  console.log('[Filter] Initialized with', pills.length, 'category pills');

  const grid = document.querySelector('#product-grid');
  const btn = document.querySelector('[data-loadmore]');
  const status = document.querySelector('#loadmore-status');
  const sortSelect = document.querySelector('#sort-select');

  const setActive = (cat) => {
    pills.forEach(p => p.classList.toggle('active', p.dataset.category === cat));
    document.body.dataset.categoryActive = cat;
    document.body.setAttribute('data-category-active', cat);
    console.log('[Filter] Set active category:', cat);
  };

  const loadFirstPage = async (cat) => {
    if (!grid) {
      console.error('[Filter] Product grid not found');
      return;
    }
    
    if (status) status.textContent = 'Loading...';
    if (btn) btn.style.display = '';
    
    try {
      const searchQ = document.querySelector('#search-q')?.value || '';
      const sortVal = sortSelect?.value || 'newest';
      
      const params = new URLSearchParams({
        category: cat,
        q: searchQ,
        sort: sortVal
      });
      
      console.log('[Filter] Fetching:', `/LifeX/api/filter-products.php?${params.toString()}`);
      
      const res = await fetch(`/LifeX/api/filter-products.php?${params.toString()}`, { 
        headers:{'X-Requested-With':'fetch'} 
      });
      
      const data = await res.json();
      console.log('[Filter] Response:', data);
      
      if (data.ok) {
        grid.innerHTML = data.html;
        if (btn) {
          btn.dataset.offset = String(data.offset);
          btn.dataset.limit = String(data.limit);
          btn.style.display = data.hasMore ? '' : 'none';
        }
        if (status) status.textContent = '';
      } else {
        if (status) status.textContent = data.error || 'Failed.';
        console.error('[Filter] API error:', data.error);
      }
    } catch (e) {
      console.error('[Filter] Network error:', e);
      if (status) status.textContent = 'Network error.';
    }
  };

  pills.forEach(p => {
    p.addEventListener('click', () => {
      const cat = p.dataset.category || '';
      setActive(cat);
      loadFirstPage(cat);
    });
  });

  // Sort handler
  if (sortSelect) {
    sortSelect.addEventListener('change', () => {
      const cat = document.body.dataset.categoryActive || '';
      console.log('[Filter] Sort changed, reloading with category:', cat);
      loadFirstPage(cat);
    });
  } else {
    console.warn('[Filter] Sort select not found');
  }

  // Search
  const form = document.querySelector('#search-form');
  const searchInput = document.querySelector('#search-q');
  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const cat = document.body.dataset.categoryActive || '';
      console.log('[Filter] Form submitted, loading with category:', cat);
      loadFirstPage(cat);
    });
  } else {
    console.warn('[Filter] Search form not found');
  }
  
  // Also support search on input change with debounce
  if (searchInput) {
    let searchTimeout;
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        const cat = document.body.dataset.categoryActive || '';
        console.log('[Filter] Search input changed, reloading');
        loadFirstPage(cat);
      }, 300);
    });
  }

  // default active
  setActive('');
})();