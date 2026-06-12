const searchInput = document.querySelector('#searchInput');
const productGrid = document.querySelector('#productGrid');
const statusBar = document.querySelector('#statusBar');
const emptyState = document.querySelector('#emptyState');
const template = document.querySelector('#productTemplate');
const overlay = document.querySelector('#detailOverlay');
const detailContent = document.querySelector('#detailContent');
const closeDetail = document.querySelector('#closeDetail');

let debounceTimer;
let lastController;

const escapeHtml = (value = '') => String(value)
  .replaceAll('&', '&amp;')
  .replaceAll('<', '&lt;')
  .replaceAll('>', '&gt;')
  .replaceAll('"', '&quot;')
  .replaceAll("'", '&#039;');

function setLoading(query) {
  statusBar.innerHTML = `<span class="loader"></span>Searching for <strong>${escapeHtml(query)}</strong>...`;
}

async function loadProducts(query = 'REMOTE') {
  const trimmed = query.trim() || 'REMOTE';
  if (lastController) lastController.abort();
  lastController = new AbortController();
  setLoading(trimmed);

  try {
    const res = await fetch(`api/products.php?q=${encodeURIComponent(trimmed)}&limit=18`, { signal: lastController.signal });
    const data = await res.json();
    renderProducts(data);
  } catch (error) {
    if (error.name === 'AbortError') return;
    statusBar.textContent = 'Unable to load products. Please check your local server and API connection.';
    productGrid.innerHTML = '';
    emptyState.classList.remove('hidden');
  }
}

function renderProducts(data) {
  const items = data.items || [];
  productGrid.innerHTML = '';
  emptyState.classList.toggle('hidden', items.length > 0);

  const sourceLabel = data.source === 'eed' ? 'EED API' : 'local sample data';
  statusBar.innerHTML = `Showing <strong>${items.length}</strong> result(s) for <strong>${escapeHtml(data.query || '')}</strong> from ${sourceLabel}. ${data.notice ? `<span class="text-amber-700">${escapeHtml(data.notice)}</span>` : ''}`;

  items.forEach((item) => {
    const node = template.content.cloneNode(true);
    const card = node.querySelector('.product-card');
    node.querySelector('.product-image').src = item.image || 'assets/img/product-placeholder.svg';
    node.querySelector('.product-image').alt = item.name;
    node.querySelector('.product-name').textContent = item.name;
    node.querySelector('.product-price').textContent = item.price;
    node.querySelector('.product-meta').textContent = `${item.manufacturer || 'Unknown manufacturer'} • Article #${item.id}`;
    const stock = node.querySelector('.product-stock');
    stock.textContent = item.orderable ? 'Orderable' : 'Check availability';
    stock.classList.add(item.orderable ? 'badge-ok' : 'badge-no');
    card.addEventListener('click', () => openDetails(item.id));
    productGrid.appendChild(node);
  });
}

async function openDetails(id) {
  overlay.classList.remove('hidden');
  overlay.classList.add('flex');
  detailContent.innerHTML = '<div class="p-10"><span class="loader"></span>Loading product details...</div>';

  try {
    const res = await fetch(`api/product.php?id=${encodeURIComponent(id)}`);
    const data = await res.json();
    if (!data.ok) throw new Error(data.error || 'Unable to load details.');
    renderDetails(data.item, data.notice);
  } catch (error) {
    detailContent.innerHTML = `<div class="p-10"><h2 class="text-2xl font-bold text-slate-950">Details unavailable</h2><p class="mt-2 text-slate-500">${escapeHtml(error.message)}</p></div>`;
  }
}

function renderDetails(item, notice = '') {
  const features = (item.features || []).map(feature => `<li class="rounded-xl bg-slate-50 px-4 py-3 text-slate-700">${escapeHtml(feature)}</li>`).join('');
  detailContent.innerHTML = `
    <div class="grid md:grid-cols-2 gap-0">
      <div class="bg-slate-50 p-6 md:p-8">
        <img src="${escapeHtml(item.image || 'assets/img/product-placeholder.svg')}" alt="${escapeHtml(item.name)}" class="h-full max-h-[460px] min-h-[300px] w-full rounded-3xl object-cover">
      </div>
      <div class="p-6 md:p-8">
        <p class="text-sm font-semibold uppercase tracking-[.25em] text-sky-700">Article #${escapeHtml(item.id)}</p>
        <h2 class="mt-3 text-3xl md:text-4xl font-black leading-tight">${escapeHtml(item.name)}</h2>
        <div class="mt-5 inline-flex rounded-full bg-slate-950 px-4 py-2 text-xl font-black text-white">${escapeHtml(item.price)}</div>
        <p class="mt-5 text-slate-600">${escapeHtml(item.description)}</p>
        ${notice ? `<p class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">${escapeHtml(notice)}</p>` : ''}
        <dl class="mt-6 grid grid-cols-2 gap-3 text-sm">
          <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-slate-500">Manufacturer</dt><dd class="mt-1 font-bold">${escapeHtml(item.manufacturer)}</dd></div>
          <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-slate-500">Delivery</dt><dd class="mt-1 font-bold">${escapeHtml(item.delivery)}</dd></div>
          <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-slate-500">EAN</dt><dd class="mt-1 font-bold">${escapeHtml(item.ean)}</dd></div>
          <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-slate-500">Status</dt><dd class="mt-1 font-bold">${item.orderable ? 'Orderable' : 'Check availability'}</dd></div>
        </dl>
        <h3 class="mt-7 text-lg font-bold">Key information</h3>
        <ul class="mt-3 grid gap-2">${features || '<li class="text-slate-500">No extra features returned.</li>'}</ul>
      </div>
    </div>`;
}

searchInput.addEventListener('input', (event) => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => loadProducts(event.target.value), 350);
});

document.querySelectorAll('.quick-search').forEach(button => {
  button.addEventListener('click', () => {
    searchInput.value = button.dataset.query;
    loadProducts(button.dataset.query);
  });
});

closeDetail.addEventListener('click', () => overlay.classList.add('hidden'));
overlay.addEventListener('click', (event) => {
  if (event.target === overlay) overlay.classList.add('hidden');
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') overlay.classList.add('hidden');
});

loadProducts(searchInput.value);
