// Bedrock Lapidary: catalog renderer
// Fetches PRODUCTS / CATEGORIES from the live PHP/MySQL backend
// (api/products.php) instead of a bundled static file, so admin panel
// edits appear for every visitor immediately, not just one browser.

const IMG_BASE = '/assets/img/products/';
const API_BASE = '/api/';

let PRODUCTS = [];
let CATEGORIES = {};
let VARIANT_INDEX = {};
let CATALOG_LOADED = false;
let catalogPromise = null;

/**
 * Fetches the catalog from the backend and populates the globals above.
 * Every page's trailing render call should be wrapped in this, e.g.:
 *   loadCatalog().then(renderProductDetail);
 * Falls back to an empty catalog with a visible error rather than a silent
 * blank page if the API can't be reached (e.g. config.php not filled in yet).
 *
 * Safe to call more than once per page (cart.js kicks it off early for
 * search, and each page's own script also calls it): the in-flight promise
 * is cached so a second call before the first resolves reuses the same
 * request instead of hitting the API again.
 */
function loadCatalog() {
  if (CATALOG_LOADED) return Promise.resolve();
  if (catalogPromise) return catalogPromise;
  catalogPromise = fetch(API_BASE + 'products.php')
    .then(res => {
      if (!res.ok) throw new Error('API returned ' + res.status);
      return res.json();
    })
    .then(data => {
      PRODUCTS = data.products || [];
      CATEGORIES = data.categories || {};
      VARIANT_INDEX = {};
      PRODUCTS.forEach(p => variantsOf(p).forEach(v => {
        VARIANT_INDEX[v.id] = {
          id: v.id, productId: p.id,
          name: p.name + (v.label ? ' - ' + v.label : ''),
          vendor: p.vendor, category: p.category,
          price: v.price, sale: v.sale, sku: v.sku, stock: v.stock, label: v.label,
        };
      }));
      CATALOG_LOADED = true;
    })
    .catch(err => {
      console.error('Could not load catalog from API:', err);
      const banner = document.createElement('div');
      banner.style.cssText = 'background:#A23B3B;color:#fff;padding:14px 20px;text-align:center;font-size:18.2px;';
      banner.textContent = 'Could not load the product catalog. Check that api/config.php has the correct database credentials.';
      document.body.prepend(banner);
      catalogPromise = null; // allow a retry on the next call
    });
  return catalogPromise;
}

let SETTINGS_CACHE = null;
let settingsPromise = null;

/**
 * Fetches sitewide settings (phone, email, banner text, popup config, etc.)
 * from the backend, same in-flight-promise caching as loadCatalog() above.
 * cart.js and main.js both need settings on every page load; without this
 * shared cache each one fired its own independent fetch to api/settings.php,
 * so the endpoint was hit twice per page for no reason.
 */
function loadSettings() {
  if (SETTINGS_CACHE) return Promise.resolve(SETTINGS_CACHE);
  if (settingsPromise) return settingsPromise;
  settingsPromise = fetch(API_BASE + 'settings.php')
    .then(res => res.json())
    .then(data => {
      SETTINGS_CACHE = data;
      return data;
    })
    .catch(err => {
      settingsPromise = null; // allow a retry on the next call
      throw err;
    });
  return settingsPromise;
}

function escHTML(str){
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function money(n){
  return '$' + Number(n).toFixed(2).replace(/\.00$/, '.00');
}

// Placeholder SVG (data URI) shown until a real photo is dropped in assets/img/products/
function placeholderDataURI(){
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">
    <rect width="200" height="200" fill="#EDF6EF"/>
    <g fill="none" stroke="#5C625E" stroke-width="2">
      <circle cx="100" cy="90" r="34"/>
      <path d="M60 150h80M70 150v-14M130 150v-14"/>
    </g>
    <text x="100" y="180" text-anchor="middle" font-family="sans-serif" font-size="11" fill="#5C625E">photo pending</text>
  </svg>`;
  return 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);
}

function productImgTag(product, cssClass, eager){
  // Responsive WebP (400w/800w) with the original JPG as fallback. Mirrors bl_img() in includes/seo.php.
  const id = encodeURIComponent(product.productId || product.id);
  const w400 = `${IMG_BASE}w400/${id}.webp`;
  const w800 = `${IMG_BASE}w800/${id}.webp`;
  const jpg = `${IMG_BASE}${id}.jpg`;
  const sizes = cssClass === 'pdp-photo' ? '(max-width: 900px) 92vw, 560px' : '(max-width: 600px) 46vw, 300px';
  return `<img class="${cssClass||''}" src="${w400}" srcset="${w400} 400w, ${w800} 800w" sizes="${sizes}" width="400" height="400" alt="${escHTML(product.name)}" ${eager ? 'fetchpriority="high"' : 'loading="lazy"'} decoding="async" onerror="blImgFallback(this,'${jpg}')">`;
}

// webp -> jpg -> placeholder
function blImgFallback(img, jpg){
  if (!img.dataset.f){
    img.dataset.f = '1';
    img.removeAttribute('srcset');
    img.src = jpg;
  } else {
    img.onerror = null;
    img.src = placeholderDataURI();
    img.classList.add('img-placeholder');
  }
}

function getProduct(id){
  return PRODUCTS.find(p => p.id === id);
}

/**
 * Purchasable options of a product. Every product has at least one variant
 * (for a plain product the variant id equals the product id); if the API
 * sent none, fall back to the product itself.
 */
function variantsOf(p){
  if (p.variants && p.variants.length) return p.variants;
  return [{ id: p.id, sku: p.sku, price: p.price, sale: p.sale, stock: p.stock, label: '' }];
}

/**
 * Looks up a cart line by its id: a variant id, or (for carts saved before
 * variants existed) a plain product id. Returns { id, productId, name,
 * vendor, category, price, sale, sku, stock, label } or undefined.
 */
function getVariant(id){
  if (VARIANT_INDEX[id]) return VARIANT_INDEX[id];
  const p = getProduct(id);
  if (!p) return undefined;
  const v = variantsOf(p)[0];
  return { id: p.id, productId: p.id, name: p.name, vendor: p.vendor, category: p.category,
           price: v.price, sale: v.sale, sku: v.sku, stock: v.stock, label: v.label };
}

function productsByCategory(cat){
  return PRODUCTS.filter(p => p.category === cat);
}

// ---------------------------------------------------------------------------
// Product card (used on category listing + homepage + recommendations)
// ---------------------------------------------------------------------------
function productCardHTML(p){
  const tagClass = p.tag === 'Sale' ? 'tag tag-sale' : 'tag';
  const tagHTML = p.tag ? `<span class="${tagClass}">${p.tag}</span>` : '';
  const bestSellerHTML = p.bestSeller ? `<span class="tag tag-best-seller">Best Seller</span>` : '';
  const priceHTML = p.sale
    ? `<span class="price">${money(p.sale)}</span><span class="price-was">${money(p.price)}</span>`
    : `<span class="price">${money(p.price)}</span>`;
  return `
  <a href="/product/${p.id}" class="product-card">
    <div class="thumb">
      <div class="tag-stack">${bestSellerHTML}${tagHTML}</div>
      ${productImgTag(p, '')}
    </div>
    <div class="body">
      <div class="vendor">${escHTML(p.vendor)}</div>
      <h3>${escHTML(p.name)}</h3>
      <div class="price-row">${priceHTML}</div>
    </div>
  </a>`;
}

// ---------------------------------------------------------------------------
// Render a category grid into a container element
// ---------------------------------------------------------------------------
function renderCategoryGrid(containerId, catSlug, opts){
  opts = opts || {};
  const el = document.getElementById(containerId);
  if (!el) return;
  let items = productsByCategory(catSlug);
  if (opts.limit) items = items.slice(0, opts.limit);
  el.innerHTML = items.map(productCardHTML).join('');
}

// Render an arbitrary list of product ids (used for "best sellers", recommendations)
function renderProductList(containerId, ids){
  const el = document.getElementById(containerId);
  if (!el) return;
  const items = ids.map(getProduct).filter(Boolean);
  el.innerHTML = items.map(productCardHTML).join('');
}

// ---------------------------------------------------------------------------
// Product detail page render
// ---------------------------------------------------------------------------
/**
 * Fetches the active promo (if any) for a product and renders the on-page
 * banner. Runs after the main product render so it doesn't block or delay
 * the rest of the page, the banner just pops in a moment later if one exists.
 */
function fetchProductPromoBanner(productId){
  const el = document.getElementById('pdp-promo-banner');
  if (!el) return;
  fetch(API_BASE + 'product-promo.php?id=' + encodeURIComponent(productId))
    .then(res => res.json())
    .then(data => {
      if (!data.active) { el.innerHTML = ''; el.style.display = 'none'; return; }
      const pct = String(data.discountPct).replace(/\.0+$/, '');
      const expiry = new Date(data.endsAt.replace(' ', 'T'));
      const dayName = expiry.toLocaleDateString(undefined, { weekday: 'long' });
      const time = expiry.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
      el.innerHTML = `Save ${pct}% on This Product! Use Discount Code <strong>${escHTML(data.code)}</strong> at Checkout. Offer Expires ${dayName} at ${time}.`;
      el.style.display = 'block';
    })
    .catch(() => { el.innerHTML = ''; el.style.display = 'none'; });
}

/**
 * Route params for clean URLs: /product/<id>, /category/<cat>[/<type>].
 * Falls back to the query string so legacy URLs and ?q= search still work.
 */
function routeParams(){
  const params = new URLSearchParams(window.location.search);
  const parts = window.location.pathname.split('/').filter(Boolean).map(decodeURIComponent);
  if (parts[0] === 'product' && parts[1]) params.set('id', parts[1]);
  if (parts[0] === 'category' && parts[1]) {
    params.set('cat', parts[1]);
    if (parts[2]) params.set('type', parts[2]);
  }
  return params;
}

function renderProductDetail(){
  const params = routeParams();
  const id = params.get('id');
  const p = id ? getProduct(id) : null;
  if (!p || !document.getElementById('pdp-title')) return; // server already rendered a not-found page

  document.getElementById('pdp-breadcrumb-cat').textContent = CATEGORIES[p.category];
  document.getElementById('pdp-breadcrumb-cat').href = `/category/${p.category}`;
  document.getElementById('pdp-breadcrumb-name').textContent = p.name;

  document.getElementById('pdp-gallery-main').innerHTML = productImgTag(p, 'pdp-photo', true);
  document.getElementById('pdp-vendor').textContent = p.vendor;
  document.getElementById('pdp-title').textContent = p.name; // textContent is safe even with literal quote marks
  const bestSellerBadge = document.getElementById('pdp-best-seller-badge');
  if (bestSellerBadge) bestSellerBadge.style.display = p.bestSeller ? 'inline-block' : 'none';

  // Product-specific promo banner (fetched separately since it's time-window dependent)
  fetchProductPromoBanner(p.id);

  document.getElementById('pdp-meta-list').innerHTML = Object.entries(p.specs).slice(0,5).map(
    ([k,v]) => `<li><span>${escHTML(k)}</span><span>${escHTML(v)}</span></li>`
  ).join('') + `<li><span>SKU</span><span>${escHTML(p.sku)}</span></li>`;

  // Variants (grit / size / motor ...): price, SKU, stock and the cart line follow the chosen option.
  const variants = variantsOf(p);
  const wanted = params.get('variant');
  let current = variants.find(x => x.id === wanted) || variants.find(x => x.stock !== 'out') || variants[0];
  const addBtn = document.getElementById('add-to-cart-btn');

  function applyVariant(v){
    current = v;
    document.getElementById('pdp-sku').innerHTML = `SKU: <span>${escHTML(v.sku)}</span>`;
    const priceBlock = document.getElementById('pdp-price-block');
    if (v.sale){
      const pct = Math.round((1 - v.sale/v.price)*100);
      priceBlock.innerHTML = `<span class="price">${money(v.sale)}</span><span class="was">${money(v.price)}</span><span class="save">Save ${pct}%</span>`;
    } else {
      priceBlock.innerHTML = `<span class="price">${money(v.price)}</span>`;
    }
    const stockLabel = {in:'In stock, ships in 1–2 days', low:'Low stock, order soon', out:'Currently out of stock'}[v.stock] || '';
    const stockEl = document.getElementById('pdp-stock');
    stockEl.textContent = stockLabel;
    stockEl.style.color = v.stock === 'out' ? 'var(--oxblood)' : 'var(--green-d)';
    if (addBtn){
      const out = v.stock === 'out';
      addBtn.disabled = out;
      addBtn.textContent = out ? 'Out of Stock' : 'Add to Cart';
      addBtn.style.opacity = out ? .5 : '';
      addBtn.style.cursor = out ? 'not-allowed' : '';
    }
    const skuRow = document.querySelector('#pdp-meta-list li:last-child span:last-child');
    if (skuRow) skuRow.textContent = v.sku;
  }

  const picker = document.getElementById('pdp-variant-picker');
  if (picker && variants.length > 1){
    picker.innerHTML = `
      <label for="pdp-variant-select" style="display:block; font-weight:600; margin:14px 0 6px;">Choose an option</label>
      <select id="pdp-variant-select" style="width:100%; padding:12px 14px; font-size:17px; border:1px solid var(--line); border-radius:8px; background:#fff; color:inherit;">
        ${variants.map(x => `<option value="${escHTML(x.id)}"${x.id === current.id ? ' selected' : ''}>${escHTML(x.label || x.sku)} - ${money(x.sale || x.price)}${x.stock === 'out' ? ' (out of stock)' : ''}</option>`).join('')}
      </select>`;
    picker.style.display = 'block';
    document.getElementById('pdp-variant-select').addEventListener('change', (e) => {
      const v = variants.find(x => x.id === e.target.value);
      if (!v) return;
      applyVariant(v);
      try { history.replaceState(null, '', window.location.pathname + (v.id === variants[0].id ? '' : '?variant=' + encodeURIComponent(v.id))); } catch(err) {}
    });
  }
  applyVariant(current);

  // Description tab: blurb may contain multiple paragraphs separated by a blank line
  const blurbParagraphs = p.blurb.split(/\n\s*\n/).map(para => `<p>${escHTML(para.trim())}</p>`);
  const descParts = [...blurbParagraphs];
  if (p.features && p.features.length){
    descParts.push(`<p><strong>Key features:</strong></p><ul>${p.features.map(f=>`<li>${escHTML(f)}</li>`).join('')}</ul>`);
  }
  if (p.included && p.included.length){
    descParts.push(`<p><strong>What's included:</strong></p><ul>${p.included.map(f=>`<li>${escHTML(f)}</li>`).join('')}</ul>`);
  }
  if (p.noise){
    descParts.push(`<div class="callout"><strong>Sound &amp; operation</strong>${escHTML(p.noise)}</div>`);
  }
  if (p.purchasing_note){
    descParts.push(`<div class="callout"><strong>Before you buy</strong>${escHTML(p.purchasing_note)}</div>`);
  }
  document.getElementById('tab-desc').innerHTML = descParts.join('');

  // Specs tab
  const specRows = Object.entries(p.specs).map(([k,v]) => `<tr><td>${escHTML(k)}</td><td>${escHTML(v)}</td></tr>`).join('');
  document.getElementById('tab-specs').innerHTML = `
    <table class="spec-table">
      ${specRows}
      <tr><td>Warranty</td><td>${escHTML(p.warranty)}</td></tr>
    </table>
    <p style="margin-top:18px; font-size:17.6px;"><a href="/returns">See our shipping &amp; returns policy →</a></p>
  `;

  // Recommendations
  if (p.recommends && p.recommends.length) renderProductList('pdp-recommends', p.recommends); // otherwise keep the server-rendered related products

  // Add to cart
  if (addBtn) {
    addBtn.addEventListener('click', () => {
      if (current.stock === 'out') return;
      const qty = parseInt(document.querySelector('.qty-stepper input').value, 10) || 1;
      cartAdd(current.id, qty);
      const original = addBtn.textContent;
      addBtn.textContent = 'Added to Cart';
      setTimeout(() => { addBtn.textContent = original; }, 1600);
    });
  }
}

// ---------------------------------------------------------------------------
// Category page render
// ---------------------------------------------------------------------------
function renderCategoryPage(){
  const params = routeParams();
  const q = (params.get('q') || '').trim();
  const cat = params.get('cat') || 'saws';

  let items, pageLabel;
  if (q) {
    const needle = q.toLowerCase();
    items = PRODUCTS.filter(p =>
      p.name.toLowerCase().includes(needle) || p.vendor.toLowerCase().includes(needle)
    );
    pageLabel = `Search results for \u201c${q}\u201d`;
  } else {
    items = window.BL_TYPE_IDS ? PRODUCTS.filter(p => window.BL_TYPE_IDS.includes(p.id)) : productsByCategory(cat);
    pageLabel = CATEGORIES[cat];
  }

  // Title, H1, breadcrumb and count are rendered by category.php for real categories/types.
  if (q) {
    document.title = `${pageLabel} - Bedrock Lapidary`;
    document.getElementById('cat-breadcrumb').textContent = pageLabel;
    document.getElementById('cat-title').textContent = pageLabel;
    document.getElementById('cat-count').textContent = `${items.length} product${items.length===1?'':'s'}`;
  }

  const introEl = document.getElementById('cat-intro-text');
  if (introEl && !q && introEl.dataset.ssr !== '1') {
    fetch('/api/content.php').then(r => r.json()).then(data => {
      const text = data.categoryContent && data.categoryContent[cat];
      if (text) {
        introEl.textContent = text;
        introEl.style.display = '';
      }
    }).catch(() => {});
  }

  const vendors = [...new Set(items.map(p=>p.vendor))].sort();
  document.getElementById('cat-filter-vendors').innerHTML = vendors.map(v =>
    `<label><input type="checkbox" data-vendor="${escHTML(v)}"> ${escHTML(v)} <span style="margin-left:auto; color:var(--line);">(${items.filter(p=>p.vendor===v).length})</span></label>`
  ).join('');

  function draw(list){
    document.getElementById('cat-grid').innerHTML = list.length
      ? list.map(productCardHTML).join('')
      : `<p style="color:var(--ink-soft);">No products matched. Try a different search term, or <a href="/category/saws">browse all categories</a>.</p>`;
    document.getElementById('cat-count').textContent = `${list.length} product${list.length===1?'':'s'}`;
  }
  // The server already rendered this exact grid; only redraw for search results or an empty grid.
  if (q || !document.getElementById('cat-grid').children.length) draw(items);

  document.getElementById('cat-filter-vendors').addEventListener('change', () => {
    const checked = [...document.querySelectorAll('#cat-filter-vendors input:checked')].map(c => c.dataset.vendor);
    draw(checked.length ? items.filter(p => checked.includes(p.vendor)) : items);
  });

  const sortSelect = document.getElementById('cat-sort');
  sortSelect.addEventListener('change', () => {
    const val = sortSelect.value;
    let list = [...items];
    if (val === 'price-asc') list.sort((a,b)=>(a.sale||a.price)-(b.sale||b.price));
    if (val === 'price-desc') list.sort((a,b)=>(b.sale||b.price)-(a.sale||a.price));
    draw(list);
  });
}
