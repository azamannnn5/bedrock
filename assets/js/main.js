// Bedrock Lapidary: shared interactions
document.addEventListener('DOMContentLoaded', () => {

  // Dropdown nav. Desktop: click toggles the submenu (hover-style mega menu).
  // Mobile: the category link navigates directly, since there's no hover
  // state to reveal a submenu with, and a separate chevron expands it instead.
  const isMobileNav = () => window.matchMedia('(max-width: 980px)').matches;

  document.querySelectorAll('.has-drop').forEach(li => {
    const link = li.querySelector(':scope > a');
    if (!link) return;

    if (isMobileNav()) {
      // Give each has-drop item its own expand chevron; tapping the link
      // itself navigates, tapping the chevron reveals the submenu.
      const chevron = document.createElement('button');
      chevron.type = 'button';
      chevron.className = 'has-drop-chevron';
      chevron.setAttribute('aria-label', 'Show ' + link.textContent.trim() + ' subcategories');
      chevron.textContent = '▾';
      li.insertBefore(chevron, li.querySelector('.drop'));
      chevron.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = li.classList.contains('open');
        document.querySelectorAll('.has-drop.open').forEach(el => el.classList.remove('open'));
        if (!isOpen) li.classList.add('open');
      });
    } else {
      link.addEventListener('click', (e) => {
        const isOpen = li.classList.contains('open');
        document.querySelectorAll('.has-drop.open').forEach(el => el.classList.remove('open'));
        if (!isOpen) {
          e.preventDefault();
          li.classList.add('open');
        }
      });
    }
  });
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.has-drop')) {
      document.querySelectorAll('.has-drop.open').forEach(el => el.classList.remove('open'));
    }
  });

  // Mobile hamburger menu
  const hamburger = document.getElementById('hamburger-toggle');
  const overlay = document.getElementById('nav-overlay');
  if (hamburger) {
    hamburger.addEventListener('click', () => {
      document.body.classList.toggle('nav-open');
    });
  }
  if (overlay) {
    overlay.addEventListener('click', () => {
      document.body.classList.remove('nav-open');
    });
  }
  const mobileNavClose = document.getElementById('mobile-nav-close');
  if (mobileNavClose) {
    mobileNavClose.addEventListener('click', () => {
      document.body.classList.remove('nav-open');
    });
  }
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.body.classList.remove('nav-open');
    }
  });

  // Quantity steppers
  document.querySelectorAll('.qty-stepper').forEach(stepper => {
    const input = stepper.querySelector('input');
    stepper.querySelector('.minus')?.addEventListener('click', () => {
      input.value = Math.max(1, (parseInt(input.value, 10) || 1) - 1);
    });
    stepper.querySelector('.plus')?.addEventListener('click', () => {
      input.value = (parseInt(input.value, 10) || 1) + 1;
    });
  });

  // Product detail tabs
  document.querySelectorAll('.tab-heads button').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.tab;
      const wrap = btn.closest('.pdp-tabs');
      wrap.querySelectorAll('.tab-heads button').forEach(b => b.classList.remove('active'));
      wrap.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      wrap.querySelector(`.tab-panel[data-tab="${target}"]`).classList.add('active');
    });
  });

  // Gallery thumb swap (product detail)
  document.querySelectorAll('.pdp-thumbs .t').forEach(thumb => {
    thumb.addEventListener('click', () => {
      const gallery = thumb.closest('.pdp-gallery');
      gallery.querySelectorAll('.pdp-thumbs .t').forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
      const mainSlot = gallery.querySelector('.pdp-gallery-main');
      mainSlot.innerHTML = thumb.innerHTML;
    });
  });

  initDiscountPopup();
});

// ---------------------------------------------------------------------------
// First-order discount popup: appears once per browser session, a few
// seconds after page load, on any page. Submits to the real backend.
// ---------------------------------------------------------------------------
function initDiscountPopup(){
  // Frequency/delay/storage strategy come from admin-configurable settings,
  // and the headline percentage comes from whichever promo is actually
  // active, rather than a hardcoded number that could drift out of sync.
  loadSettings().then(settings => {
    const frequency = settings.popupFrequency || 'once_per_session';
    const delayMs = (settings.popupDelaySeconds ?? 4) * 1000;
    const storage = frequency === 'once_ever' ? localStorage : sessionStorage;
    const storageKey = 'bedrock_popup_shown';

    if (frequency !== 'every_visit' && storage.getItem(storageKey)) return;

    fetch('api/active-popup-promo.php').then(r => r.json()).then(promo => {
      showDiscountPopup(promo.active ? promo.discountPct : null, delayMs, () => {
        if (frequency !== 'every_visit') storage.setItem(storageKey, '1');
      });
    }).catch(() => {}); // no active promo endpoint reachable, just skip the popup silently
  }).catch(() => {});
}

function showDiscountPopup(discountPct, delayMs, markShown){
  const pctText = discountPct ? String(discountPct).replace(/\.0+$/, '') : '10';
  const headline = `Save ${pctText}% on Your First Order of Most Products!`;

  const backdrop = document.createElement('div');
  backdrop.className = 'promo-popup-backdrop';
  backdrop.innerHTML = `
    <div class="promo-popup">
      <button type="button" class="promo-popup-close" aria-label="Close">&times;</button>
      <h2>${escHTML(headline)}</h2>
      <p>Enter your name and email below to receive a special discount.</p>
      <form id="promo-popup-form">
        <div class="form-field">
          <input type="text" id="promo-popup-name" placeholder="First name" required>
        </div>
        <div class="form-field">
          <input type="email" id="promo-popup-email" placeholder="Email" required>
        </div>
        <button type="submit" class="btn btn-block">Send Me the Discount!</button>
      </form>
      <p class="promo-popup-fine">By signing up, you agree to receive marketing emails. Discount applies to your first order only.</p>
    </div>
  `;
  document.body.appendChild(backdrop);

  const close = () => {
    backdrop.classList.remove('open');
    setTimeout(() => backdrop.remove(), 200);
    markShown();
  };

  backdrop.querySelector('.promo-popup-close').addEventListener('click', close);
  backdrop.addEventListener('click', (e) => { if (e.target === backdrop) close(); });

  backdrop.querySelector('#promo-popup-form').addEventListener('submit', (e) => {
    e.preventDefault();
    const firstName = document.getElementById('promo-popup-name').value.trim();
    const email = document.getElementById('promo-popup-email').value.trim();
    const form = e.target;
    const btn = form.querySelector('button');
    btn.disabled = true;
    btn.textContent = 'Sending...';

    fetch('api/popup-signup.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ firstName, email })
    })
      .then(res => res.json())
      .then(data => {
        form.innerHTML = `<p style="font-size:18.2px; color:var(--green-d); margin:0;">${data.message || 'Check your inbox for your code!'}</p>`;
        markShown();
      })
      .catch(() => {
        btn.disabled = false;
        btn.textContent = 'Send Me the Discount!';
        alert('Could not reach the server. Please try again.');
      });
  });

  setTimeout(() => {
    backdrop.classList.add('open');
  }, delayMs);
}
