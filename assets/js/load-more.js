(() => {
  const btn = document.querySelector('[data-loadmore]');
  if (!btn) return;

  const grid = document.querySelector('#product-grid');
  const status = document.querySelector('#loadmore-status');
  let offset = parseInt(btn.dataset.offset || '0', 10);
  const limit = parseInt(btn.dataset.limit || '12', 10);

  const getCategory = () => document.querySelector('[data-category-active]')?.dataset.categoryActive || '';

  btn.addEventListener('click', async () => {
    btn.disabled = true;
    status.textContent = 'Loading...';

    try {
      const params = new URLSearchParams({
        offset: String(offset),
        limit: String(limit),
        category: getCategory(),
        q: document.querySelector('#search-q')?.value || '',
        sort: document.querySelector('#sort-select')?.value || 'newest'
      });

      const res = await fetch(`api/load-products.php?${params.toString()}`, { headers: { 'X-Requested-With':'fetch' }});
      const data = await res.json();

      if (data.ok) {
        grid.insertAdjacentHTML('beforeend', data.html);
        offset += data.count;
        btn.dataset.offset = String(offset);
        status.textContent = data.count ? '' : 'No more products.';
        if (!data.count) btn.style.display = 'none';
      } else {
        status.textContent = data.error || 'Failed to load.';
      }
    } catch (e) {
      status.textContent = 'Network error.';
    } finally {
      btn.disabled = false;
    }
  });
})();