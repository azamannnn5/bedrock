// Bedrock Lapidary: cart storage + header wiring
// Cart persists in localStorage so it carries across pages.

const CART_KEY = 'bedrock_cart_v1';

function cartGet(){
  try {
    const raw = localStorage.getItem(CART_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch(e){
    return [];
  }
}

function cartSave(items){
  localStorage.setItem(CART_KEY, JSON.stringify(items));
  updateCartCount();
}

function cartAdd(productId, qty){
  qty = qty || 1;
  const items = cartGet();
  const existing = items.find(i => i.id === productId);
  if (existing) {
    existing.qty += qty;
  } else {
    items.push({ id: productId, qty: qty });
  }
  cartSave(items);
}

function cartSetQty(productId, qty){
  let items = cartGet();
  if (qty <= 0) {
    items = items.filter(i => i.id !== productId);
  } else {
    const existing = items.find(i => i.id === productId);
    if (existing) existing.qty = qty;
  }
  cartSave(items);
}

function cartRemove(productId){
  const items = cartGet().filter(i => i.id !== productId);
  cartSave(items);
}

function cartClear(){
  cartSave([]);
}

function cartCount(){
  return cartGet().reduce((sum, i) => sum + i.qty, 0);
}

function updateCartCount(){
  const el = document.getElementById('cart-count-badge');
  if (!el) return;
  const count = cartCount();
  if (count > 0) {
    el.textContent = count;
    el.style.display = 'flex';
  } else {
    el.style.display = 'none';
  }
}

// ---------------------------------------------------------------------------
// Header wiring: search flyout toggle + cart badge on load
// ---------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
  updateCartCount();
  loadCatalog(); // kick off early so search results are ready by the time anyone types
  applySiteSettings();

  const searchToggle = document.getElementById('search-toggle');
  const searchFlyout = document.getElementById('search-flyout');
  const searchInput = searchFlyout ? searchFlyout.querySelector('input') : null;
  const searchResults = document.getElementById('search-live-results');

  if (searchToggle && searchFlyout) {
    searchToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      searchFlyout.classList.toggle('open');
      if (searchFlyout.classList.contains('open')) {
        searchInput?.focus();
      } else if (searchResults) {
        searchResults.innerHTML = '';
        searchResults.classList.remove('open');
      }
    });
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.search-flyout') && !e.target.closest('#search-toggle')) {
        searchFlyout.classList.remove('open');
        if (searchResults) { searchResults.innerHTML = ''; searchResults.classList.remove('open'); }
      }
    });
  }

  // Live search-as-you-type results, shown while typing
  if (searchInput && searchResults && typeof PRODUCTS !== 'undefined') {
    searchInput.addEventListener('input', () => {
      const q = searchInput.value.trim().toLowerCase();
      if (!q) {
        searchResults.innerHTML = '';
        searchResults.classList.remove('open');
        return;
      }
      const matches = PRODUCTS.filter(p =>
        p.name.toLowerCase().includes(q) || p.vendor.toLowerCase().includes(q)
      ).slice(0, 6);

      if (matches.length === 0) {
        searchResults.innerHTML = `<div class="search-live-empty">No products match "${escHTML(q)}"</div>`;
        searchResults.classList.add('open');
        return;
      }

      searchResults.innerHTML = matches.map(p => `
        <a href="product.html?id=${p.id}" class="search-live-row">
          <span class="search-live-thumb">${productImgTag(p, '')}</span>
          <span class="search-live-info">
            <span class="search-live-name">${escHTML(p.name)}</span>
            <span class="search-live-price">${money(p.sale || p.price)}</span>
          </span>
        </a>
      `).join('');
      searchResults.classList.add('open');
    });
  }

  const searchForm = document.getElementById('search-flyout');
  if (searchForm) {
    searchForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const q = searchForm.querySelector('input').value.trim();
      if (q) {
        window.location.href = 'category.html?q=' + encodeURIComponent(q);
      }
    });
  }

  const cartButton = document.getElementById('cart-button');
  if (cartButton) {
    cartButton.addEventListener('click', () => {
      window.location.href = 'cart.html';
    });
  }
});

/**
 * Fetches sitewide settings (phone, email, shipping banner text) from the
 * backend and applies them to every matching element on the page, so
 * changing them in admin/settings.php updates the whole site with no
 * redeploy. Silently leaves the existing placeholder text in place if the
 * API can't be reached, rather than showing a broken/blank value.
 */
function applySiteSettings(){
  loadSettings()
    .then(settings => {
      const phone = settings.phoneNumber;
      const email = settings.contactEmail;
      const phoneDigits = phone ? phone.replace(/[^0-9]/g, '') : '';

      if (phone) {
        const contactPhone = document.getElementById('contact-phone-text');
        if (contactPhone) {
          const hours = settings.businessHours || '';
          contactPhone.textContent = hours ? `${phone}, ${hours}` : phone;
        }
        const callBtn = document.getElementById('contact-call-btn');
        if (callBtn) {
          callBtn.textContent = `Call ${phone}`;
          callBtn.href = `tel:${phoneDigits}`;
          callBtn.style.display = '';
        }
        document.querySelectorAll('#legal-phone-text').forEach(el => el.textContent = phone);
      }

      if (email) {
        const contactEmail = document.getElementById('contact-email-text');
        if (contactEmail) contactEmail.textContent = `${email}, we reply within one business day`;
        document.querySelectorAll('#legal-email-text').forEach(el => el.textContent = email);
      }

      if (Array.isArray(settings.bannerMessages) && settings.bannerMessages.length > 0) {
        startBannerRotation(settings.bannerMessages);
      }

      const socialEl = document.getElementById('footer-social-icons');
      if (socialEl && settings.social) {
        const icons = {
          facebook: '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M22 12a10 10 0 1 0-11.5 9.9v-7H7.9V12h2.6V9.8c0-2.6 1.5-4 3.9-4 1.1 0 2.3.2 2.3.2v2.5h-1.3c-1.3 0-1.7.8-1.7 1.6V12h2.9l-.5 2.9h-2.4v7A10 10 0 0 0 22 12"/></svg>',
          instagram: '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 2c2.7 0 3.1 0 4.1.1 1 .1 1.7.2 2.3.5.6.2 1.1.6 1.6 1.1.5.5.8.9 1.1 1.6.2.6.4 1.3.5 2.3.1 1 .1 1.4.1 4.1s0 3.1-.1 4.1c-.1 1-.2 1.7-.5 2.3-.2.6-.6 1.1-1.1 1.6-.5.5-.9.8-1.6 1.1-.6.2-1.3.4-2.3.5-1 .1-1.4.1-4.1.1s-3.1 0-4.1-.1c-1-.1-1.7-.2-2.3-.5-.6-.2-1.1-.6-1.6-1.1-.5-.5-.8-.9-1.1-1.6-.2-.6-.4-1.3-.5-2.3C2 15.1 2 14.7 2 12s0-3.1.1-4.1c.1-1 .2-1.7.5-2.3.2-.6.6-1.1 1.1-1.6.5-.5.9-.8 1.6-1.1.6-.2 1.3-.4 2.3-.5C8.9 2 9.3 2 12 2m0 1.8c-2.6 0-3 0-4 .1-.9 0-1.4.2-1.7.3-.4.2-.7.3-1 .6-.3.3-.5.6-.6 1-.1.3-.3.8-.3 1.7-.1 1-.1 1.4-.1 4s0 3 .1 4c0 .9.2 1.4.3 1.7.2.4.3.7.6 1 .3.3.6.5 1 .6.3.1.8.3 1.7.3 1 .1 1.4.1 4 .1s3 0 4-.1c.9 0 1.4-.2 1.7-.3.4-.2.7-.3 1-.6.3-.3.5-.6.6-1 .1-.3.3-.8.3-1.7.1-1 .1-1.4.1-4s0-3-.1-4c0-.9-.2-1.4-.3-1.7-.2-.4-.3-.7-.6-1-.3-.3-.6-.5-1-.6-.3-.1-.8-.3-1.7-.3-1-.1-1.4-.1-4-.1M12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10m0 1.8a3.2 3.2 0 1 0 0 6.4 3.2 3.2 0 0 0 0-6.4m5.2-.4a1.2 1.2 0 1 1 0-2.4 1.2 1.2 0 0 1 0 2.4"/></svg>',
          youtube: '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M23 12s0-3.5-.4-5.2c-.3-.9-1-1.7-1.9-1.9C18.9 4.5 12 4.5 12 4.5s-6.9 0-8.7.4c-.9.2-1.6 1-1.9 1.9C1 8.5 1 12 1 12s0 3.5.4 5.2c.3.9 1 1.6 1.9 1.9 1.8.4 8.7.4 8.7.4s6.9 0 8.7-.4c.9-.3 1.6-1 1.9-1.9.4-1.7.4-5.2.4-5.2M9.8 15.5V8.5l6 3.5-6 3.5Z"/></svg>',
        };
        Object.entries(settings.social).forEach(([key, url]) => {
          if (url) {
            const a = document.createElement('a');
            a.href = url;
            a.target = '_blank';
            a.rel = 'noopener';
            a.style.color = 'rgba(255,255,255,.8)';
            a.setAttribute('aria-label', key);
            a.innerHTML = icons[key] || '';
            socialEl.appendChild(a);
          }
        });
      }
    })
    .catch(() => {}); // keep existing placeholder text if the API isn't reachable yet
}

/**
 * Rotates the top-bar message(s) admin has set up, each with its own
 * display duration. A single message just displays with no rotation.
 */
function startBannerRotation(messages){
  const elements = document.querySelectorAll('.ship-msg');
  if (!elements.length) return;
  let i = 0;

  const show = () => {
    elements.forEach(el => el.textContent = messages[i].message);
  };
  show();

  if (messages.length > 1) {
    const advance = () => {
      i = (i + 1) % messages.length;
      show();
      setTimeout(advance, (messages[i].seconds || 5) * 1000);
    };
    setTimeout(advance, (messages[i].seconds || 5) * 1000);
  }
}
