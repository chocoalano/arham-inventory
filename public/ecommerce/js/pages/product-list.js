// ============================================
// GLOBAL UTILITIES & HELPERS
// ============================================

function changeSort(value) {
  const url = new URL(window.location.href);
  url.searchParams.set('sort-by', value);
  url.searchParams.delete('page');
  window.location.href = url.toString();
}

(function () {
  // --------- Global config (ambil dari DOM bila ada) ----------
  const modalEl = document.getElementById('quick-view-modal-container');
  const CART_FORM = document.getElementById('quick-view-add-to-cart-form');
  const ROUTES = {
    cartStore: CART_FORM?.getAttribute('action') || '/cart',
    login: CART_FORM?.getAttribute('data-login-url') || '/login-register',
    wishlistAdd: document.body?.getAttribute('data-wishlist-add') || '/wishlist',
    wishlistRemove: document.body?.getAttribute('data-wishlist-remove') || '/wishlist',
  };

  const FALLBACK_IMAGE = document.getElementsByTagName('meta')['asset']?.content
    ? (document.getElementsByTagName('meta')['asset'].content.replace(/\/+$/g, '') + '/ecommerce/images/fallback-product.png')
    : '/ecommerce/images/fallback-product.png';

  // ========= Helpers =========
  const fmtIDR = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
  const fmtNum = new Intl.NumberFormat('id-ID');
  const toTitle = s => (s ?? '').toString().replace(/[_\-]+/g, ' ').replace(/\w\S*/g, t => t[0].toUpperCase() + t.slice(1));
  const esc = str => (str || '').toString().replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m]);
  const sstr = v => (v ?? '') + '';

  const parsePrice = v => {
    if (typeof v === 'number' && !Number.isNaN(v)) return Math.round(v);
    if (typeof v === 'string') {
      const s = v.trim();
      if (/^-?\d+([.,]\d+)?$/.test(s)) return Math.round(parseFloat(s.replace(',', '.')));
      const digits = s.replace(/[^\d]/g, ''); return digits ? parseInt(digits, 10) : NaN;
    }
    return NaN;
  };
  const formatPrice = v => {
    const n = parsePrice(v);
    return Number.isFinite(n) ? fmtIDR.format(n) : (v ?? '');
  };
  const percentOff = (compare, price) => {
    const a = parsePrice(compare), b = parsePrice(price);
    if (!Number.isFinite(a) || !Number.isFinite(b) || a <= b || b <= 0) return null;
    return Math.round((1 - (b / a)) * 100);
  };

  function ensureToastContainer() {
    let wrap = document.getElementById('toast-wrap');
    if (!wrap) {
      wrap = document.createElement('div');
      wrap.id = 'toast-wrap';
      wrap.className = 'toast-container position-fixed top-0 end-0 p-3';
      wrap.style.zIndex = '9999';
      document.body.appendChild(wrap);
    }
    return wrap;
  }

  function showToast(variant, message) {
    if (!window.bootstrap?.Toast) { alert(message); return; }
    const wrap = ensureToastContainer();
    const id = 't' + Date.now();
    wrap.insertAdjacentHTML('beforeend', `
      <div id="${id}" class="toast align-items-center text-bg-${variant} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="2000">
        <div class="d-flex">
          <div class="toast-body">${esc(message)}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>`);
    const el = document.getElementById(id);
    bootstrap.Toast.getOrCreateInstance(el).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
  }
  window.showToast = showToast;
  async function safeJson(res) {
    const ct = res.headers.get('content-type') || '';
    if (ct.includes('application/json')) return res.json();
    return {};
  }

  // ============================================
  // QUICK VIEW MODAL & VARIANT SELECTION
  // ============================================
  if (!modalEl) return;

  function ensureAvailabilityEl(modal) {
    let el = modal.querySelector('.product-feature-details .product-availability');
    if (!el) {
      const details = modal.querySelector('.product-feature-details');
      if (details) {
        el = document.createElement('div');
        el.className = 'product-availability mb-20';
        details.insertBefore(el, details.querySelector('.product-variants'));
      }
    }
    return el;
  }

  function buildGallery(modal, item) {
    const largeWrap = modal.querySelector('.quickview-product-large-image-list');
    const smallWrap = modal.querySelector('.quickview-product-small-image-list .nav');
    if (!largeWrap || !smallWrap) return;

    let imgs = [];
    const arr = Array.isArray(item.gallery) ? item.gallery
      : Array.isArray(item.galeri) ? item.galeri : [];
    if (arr.length) {
      imgs = arr.map(g => {
        if (typeof g === 'string') return g;
        return g.url || g.image || g.image_url || (g.image_path ? (window.APP_STORAGE_URL ? (window.APP_STORAGE_URL + '/' + g.image_path) : g.image_path) : '');
      }).filter(Boolean);
    } else if (item.image || item.gambar) {
      imgs = [item.image || item.gambar];
    }
    if (!imgs.length) imgs = [FALLBACK_IMAGE];

    const title = item.title || item.judul || '';
    const largeHtml = imgs.map((src, idx) => {
      const i = idx + 1;
      return `
        <div class="tab-pane fade ${idx === 0 ? 'show active' : ''}" id="single-slide-quick-${i}"
             role="tabpanel" aria-labelledby="single-slide-tab-quick-${i}">
            <div class="single-product-img img-full">
                <img width="600" height="719" src="${src}" class="img-fluid" alt="${esc(title)}">
            </div>
        </div>`;
    }).join('');

    const smallHtml = imgs.map((src, idx) => {
      const i = idx + 1;
      return `
        <div class="single-small-image img-full">
            <a data-bs-toggle="tab" id="single-slide-tab-quick-${i}" href="#single-slide-quick-${i}">
                <img width="600" height="719" src="${src}" class="img-fluid" alt="${esc(title)}">
            </a>
        </div>`;
    }).join('');

    largeWrap.innerHTML = largeHtml;
    smallWrap.innerHTML = smallHtml;
  }

  // ========= Variants & Stock =========
  const allowedAttrKeys = ['color', 'colour', 'warna', 'size', 'ukuran', 'flavor', 'flavour', 'taste', 'variant', 'material', 'model', 'style', 'type', 'weight', 'capacity', 'pack', 'volume', 'length', 'width', 'height', 'grade'];
  const allowedAttrSet = new Set(allowedAttrKeys);
  const isColorKey = k => ['color', 'colour', 'warna'].includes(String(k).toLowerCase());
  const getVariantAttrs = v => {
    const out = {};
    Object.keys(v || {}).forEach(k => {
      if (!allowedAttrSet.has(k.toLowerCase())) return;
      const val = sstr(v[k]).trim();
      if (val) out[k] = val;
    });
    const extra = v?.attrs || v?.attributes || {};
    Object.keys(extra).forEach(k => {
      if (!allowedAttrSet.has(k.toLowerCase())) return;
      const val = sstr(extra[k]).trim();
      if (val) out[k] = val;
    });
    return out;
  };

  const getNumber = v => {
    const n = Number(v);
    return Number.isFinite(n) ? n : 0;
  };
  const getVariantStock = variant => {
    if (!variant) return null;
    const cands = [
      variant.total_stocks, variant.stok, variant.inventory, variant.qty, variant.quantity,
      variant.stock_qty, variant.stock_quantity
    ];
    for (const c of cands) {
      const n = getNumber(c);
      if (n || n === 0) return n;
    }
    if (variant.in_stock === true) return 999999;
    if (variant.status && String(variant.status).toLowerCase() === 'active') return 999999;
    return 0;
  };
  const getGlobalStock = item => getNumber(item.stock ?? item.stok ?? item.inventory ?? 0);

  function buildVariantUI(modal, item) {
    const variantsBox = modal.querySelector('.product-variants');
    const availEl = ensureAvailabilityEl(modal);
    const qtyInput = modal.querySelector('.pro-qty input');
    const addBtn = modal.querySelector('button.pataku-btn');

    if (addBtn) { addBtn.type = 'button'; addBtn.onclick = null; }
    if (qtyInput) { qtyInput.type = 'number'; qtyInput.min = 1; qtyInput.step = 1; }

    const hasVariants = Array.isArray(item.variants) && item.variants.length > 0;
    const stockGlobal = getGlobalStock(item);

    // Tanpa varian
    if (!hasVariants) {
      if (availEl) {
        if (stockGlobal > 0) {
          availEl.innerHTML = `Stok: <span class="badge bg-success">Tersedia</span> <span class="ms-2 text-success fw-semibold">${stockGlobal} pcs</span>`;
        } else {
          availEl.innerHTML = `Stok: <span class="badge bg-secondary">Habis</span>`;
        }
      }
      if (qtyInput) {
        qtyInput.max = stockGlobal > 0 ? stockGlobal : 1;
        if ((+qtyInput.value || 0) < 1) qtyInput.value = 1;
        if ((+qtyInput.value) > stockGlobal && stockGlobal > 0) qtyInput.value = stockGlobal;
      }
      if (addBtn) {
        addBtn.dataset.productId = item.id || '';
        addBtn.dataset.sku = item.sku || item.kode || item.code || '';
        addBtn.dataset.variantId = '';
        setAddBtnState(addBtn, stockGlobal > 0, null);
      }
      if (variantsBox) variantsBox.innerHTML = '';
      return;
    }

    // Normalisasi varian & opsi
    const variants = item.variants.map(v => ({ ...v, __attrs: getVariantAttrs(v) }));
    const attrMap = new Map();
    variants.forEach(v => Object.entries(v.__attrs).forEach(([k, val]) => {
      if (!attrMap.has(k)) attrMap.set(k, new Set());
      attrMap.get(k).add(val);
    }));

    const defaultVar = variants[0] || null;
    const currentSel = {};
    if (defaultVar) Object.assign(currentSel, defaultVar.__attrs);
    else for (const [k, set] of attrMap.entries()) currentSel[k] = [...set][0] ?? '';

    const findColorKey = () => { for (const k of attrMap.keys()) if (isColorKey(k)) return k; return null; };
    const colorKey = findColorKey();

    const pickVariantForColor = (colorValue, partialSel) => {
      const candidates = variants.filter(v => sstr(v.__attrs[colorKey]) === sstr(colorValue));
      const exact = candidates.find(v => Object.entries(partialSel).every(([k, val]) => {
        if (isColorKey(k)) return true;
        return !val || sstr(v.__attrs[k]) === sstr(val);
      }));
      return exact || candidates[0] || null;
    };

    // Render controls
    if (variantsBox) {
      const controls = [...attrMap.entries()].map(([k, set]) => {
        let optsHtml = '';
        if (colorKey && isColorKey(k)) {
          const colors = [...set];
          optsHtml = colors.map(colorVal => {
            const vPick = pickVariantForColor(colorVal, currentSel);
            const vid = vPick?.id ?? '';
            const selected = defaultVar && sstr(defaultVar.__attrs[colorKey]) === sstr(colorVal) ? 'selected' : '';
            return `<option value="${esc(vid)}" data-label="${esc(colorVal)}" ${selected}>${esc(colorVal)}</option>`;
          }).join('');
        } else {
          const values = [...set];
          const currentVal = currentSel[k] || values[0] || '';
          optsHtml = values.map(v => `<option value="${esc(v)}" ${sstr(v) === sstr(currentVal) ? 'selected' : ''}>${esc(v)}</option>`).join('');
        }
        return `
          <div class="mb-3">
            <label class="form-label fw-semibold">${toTitle(k)}</label>
            <select class="form-select variant-attr" data-attr="${esc(k)}">${optsHtml}</select>
          </div>`;
      }).join('') + `<div class="variant-summary small text-muted mb-2" aria-live="polite"></div>`;
      variantsBox.innerHTML = controls;
    }

    // Set awal selects
    if (defaultVar) {
      modal.querySelectorAll('.variant-attr').forEach(sel => {
        const key = sel.getAttribute('data-attr');
        if (!key) return;
        if (colorKey && isColorKey(key)) sel.value = defaultVar.id;
        else if (defaultVar.__attrs[key]) sel.value = defaultVar.__attrs[key];
      });
    }

    // ==== UI utils dalam scope ====
    function setAddBtnState(btn, enabled, meta) {
      if (!btn) return;
      btn.classList.toggle('disabled', !enabled);
      btn.setAttribute('aria-disabled', String(!enabled));
      btn.style.pointerEvents = enabled ? '' : 'none';
      if (meta) {
        if (meta.productId != null) btn.dataset.productId = meta.productId;
        if (meta.sku != null) btn.dataset.sku = meta.sku;
        if (meta.variantId != null) btn.dataset.variantId = meta.variantId;
      }
    }

    function updatePriceBox(modal, baseItem, variant) {
      const priceBox = modal.querySelector('.product-feature-details .product-price');
      const mainPrice = priceBox ? priceBox.querySelector('.main-price') : null;
      const discPrice = priceBox ? priceBox.querySelector('.discounted-price') : null;
      const discPctEl = priceBox ? priceBox.querySelector('.discount-percentage') : null;

      const vPrice = variant ? (variant.price ?? variant.sale_price) : null;
      const vComp = variant ? (variant.cost_price ?? variant.compare_at_price) : null;
      const iPrice = baseItem.price ?? baseItem.sale_price;
      const iComp = baseItem.cost_price ?? baseItem.compare_at_price;
      const showP = vPrice ?? iPrice;
      const showC = vComp ?? iComp;

      if (mainPrice) { if (showC) { mainPrice.textContent = formatPrice(showC); mainPrice.classList.remove('d-none'); } else { mainPrice.textContent = ''; mainPrice.classList.add('d-none'); } }
      if (discPrice) { if (showP) { discPrice.textContent = formatPrice(showP); discPrice.classList.remove('d-none'); } else { discPrice.textContent = ''; discPrice.classList.add('d-none'); } }
      if (discPctEl) {
        const pct = percentOff(showC, showP);
        const badgeStr = baseItem?.badges?.discount;
        if (Number.isFinite(pct)) { discPctEl.textContent = `Hemat ${pct}%`; discPctEl.classList.remove('d-none'); }
        else if (badgeStr) { discPctEl.textContent = `Hemat ${sstr(badgeStr).replace('-', '').trim()}`; discPctEl.classList.remove('d-none'); }
        else { discPctEl.textContent = ''; discPctEl.classList.add('d-none'); }
      }
    }

    function refreshSelectEnablement() {
      const selects = [...modal.querySelectorAll('.variant-attr')];
      const partial = {};
      selects.forEach(sel => {
        const key = sel.getAttribute('data-attr');
        if (!key || (colorKey && isColorKey(key))) return;
        partial[key] = sel.value || '';
      });

      const findById = id => variants.find(v => String(v.id) === String(id)) || null;

      selects.forEach(sel => {
        const key = sel.getAttribute('data-attr');
        [...sel.options].forEach(opt => {
          if (!opt.value) return;
          let ok = true;
          if (colorKey && isColorKey(key)) {
            const v = findById(opt.value);
            ok = !!v && Object.entries(partial).every(([k, val]) => !val || sstr(v.__attrs[k]) === sstr(val));
          } else {
            ok = variants.some(v => {
              if (sstr(v.__attrs[key]) !== sstr(opt.value)) return false;
              return Object.entries(partial).every(([k, val]) => {
                if (k === key) return true;
                return !val || sstr(v.__attrs[k]) === sstr(val);
              });
            });
          }
          opt.disabled = !ok;
          opt.classList.toggle('text-muted', !ok);
          const base = opt.getAttribute('data-base') || opt.textContent.replace(' (habis)', '');
          opt.setAttribute('data-base', base);
          opt.textContent = ok ? base : `${base} (habis)`;
        });

        if (sel.value && sel.selectedOptions[0]?.disabled) {
          const firstOk = [...sel.options].find(o => o.value && !o.disabled);
          sel.value = firstOk ? firstOk.value : '';
        }
      });
    }

    function updateVariantSummary(modal, sel, variant) {
      const holder = modal.querySelector('.variant-summary');
      if (!holder) return;
      const parts = Object.entries(sel).filter(([, v]) => v).map(([k, v]) => `${toTitle(k)}: ${v}`);
      const skuTxt = variant?.sku_variant ? ` • SKU: ${variant.sku_variant}` : '';
      holder.textContent = parts.length ? `Pilihan: ${parts.join(' / ')}${skuTxt}` : 'Silakan pilih kombinasi varian.';
    }

    function getSelection() {
      const sel = {};
      modal.querySelectorAll('.variant-attr').forEach(s => {
        const key = s.getAttribute('data-attr');
        if (colorKey && isColorKey(key)) sel[key] = s.selectedOptions[0]?.dataset.label || '';
        else sel[key] = s.value || '';
      });
      return sel;
    }

    const findById = id => variants.find(v => String(v.id) === String(id)) || null;

    function syncSelectsToVariant(variant) {
      if (!variant) return;
      modal.querySelectorAll('.variant-attr').forEach(s => {
        const key = s.getAttribute('data-attr');
        if (colorKey && isColorKey(key)) s.value = variant.id;
        else if (variant.__attrs[key]) s.value = variant.__attrs[key];
      });
    }

    function onSelectionChanged(triggerSel) {
      let matched = null;

      if (triggerSel && colorKey && isColorKey(triggerSel.getAttribute('data-attr'))) {
        matched = findById(triggerSel.value);
      }

      if (!matched) {
        const selObj = getSelection();
        matched = variants.find(v =>
          Object.entries(selObj).every(([k, val]) => !val || sstr(v.__attrs[k]) === sstr(val))
        ) || null;
      }

      if (!matched) matched = defaultVar;

      syncSelectsToVariant(matched);
      refreshSelectEnablement();

      const variantStock = getVariantStock(matched);
      const effectiveStock = (variantStock !== null) ? variantStock : stockGlobal;

      updatePriceBox(modal, item, matched);
      if (availEl) {
        if (effectiveStock > 0) {
          availEl.innerHTML = `Stok: <span class="badge bg-success">Tersedia</span> <span class="ms-2 text-success fw-semibold">${effectiveStock} pcs</span>`;
        } else {
          availEl.innerHTML = `Stok: <span class="badge bg-secondary">Habis</span>`;
        }
      }
      if (qtyInput) {
        qtyInput.max = effectiveStock > 0 ? effectiveStock : 1;
        if ((+qtyInput.value || 0) < 1) qtyInput.value = 1;
        if ((+qtyInput.value) > effectiveStock && effectiveStock > 0) qtyInput.value = effectiveStock;
      }

      if (addBtn) {
        const sku = (matched?.sku_variant ?? matched?.sku ?? item.sku ?? item.kode ?? item.code) || '';
        addBtn.dataset.productId = item.id || '';
        addBtn.dataset.sku = sku;
        addBtn.dataset.variantId = matched?.id ?? '';
        const canAdd = effectiveStock > 0 && (!!matched || !hasVariants);
        setAddBtnState(addBtn, canAdd, {
          productId: addBtn.dataset.productId,
          sku,
          variantId: addBtn.dataset.variantId
        });
      }

      updateVariantSummary(modal, getSelection(), matched);
    }

    refreshSelectEnablement();
    onSelectionChanged(null);

    modal.querySelectorAll('.variant-attr').forEach(sel =>
      sel.addEventListener('change', () => onSelectionChanged(sel))
    );
  }

  // ========= Modal lifecycle: isi konten =========
  modalEl.addEventListener('show.bs.modal', function (event) {
    const trigger = event.relatedTarget;
    if (!trigger) return;

    let item = {};
    try { item = JSON.parse(trigger.getAttribute('data-product') || '{}'); }
    catch (e) { console.warn('Invalid product json', e); }

    const titleEl = modalEl.querySelector('.product-feature-details .product-title');
    const descEl = modalEl.querySelector('.product-feature-details .product-description');
    if (titleEl) titleEl.textContent = item.title || item.judul || '';
    if (descEl) descEl.textContent = item.description || item.deskripsi || '';

    const priceBox = modalEl.querySelector('.product-feature-details .product-price');
    const mainPrice = priceBox ? priceBox.querySelector('.main-price') : null;
    const discPrice = priceBox ? priceBox.querySelector('.discounted-price') : null;
    const discPct = priceBox ? priceBox.querySelector('.discount-percentage') : null;

    if (mainPrice) {
      if (item.cost_price) { mainPrice.textContent = formatPrice(item.cost_price); mainPrice.classList.remove('d-none'); }
      else { mainPrice.textContent = ''; mainPrice.classList.add('d-none'); }
    }
    if (discPrice) {
      if (item.price) { discPrice.textContent = formatPrice(item.price); discPrice.classList.remove('d-none'); }
      else { discPrice.textContent = ''; discPrice.classList.add('d-none'); }
    }
    if (discPct) {
      const pctRaw = item?.badges?.discount || '';
      if (pctRaw) { discPct.textContent = 'Hemat ' + sstr(pctRaw).replace('-', '').trim(); discPct.classList.remove('d-none'); }
      else {
        const pct = percentOff(item.cost_price, item.price);
        if (Number.isFinite(pct)) { discPct.textContent = `Hemat ${pct}%`; discPct.classList.remove('d-none'); }
        else { discPct.textContent = ''; discPct.classList.add('d-none'); }
      }
    }

    buildGallery(modalEl, item);
    buildVariantUI(modalEl, item);
  });

  // ========= Add to Cart (POST JSON) =========
  function closeQuickViewModal() {
    try {
      if (window.bootstrap?.Modal) {
        const inst = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
        inst.hide();
      } else {
        modalEl.classList.remove('show');
        modalEl.setAttribute('aria-hidden', 'true');
        modalEl.style.display = 'none';
        document.body.classList.remove('modal-open');
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
      }
    } catch (e) {
      console.warn('Gagal menutup modal:', e);
    }
  }

  function updateCartUI(data) {
    const count = data.cart_items_count ?? data.cart_count;
    if (typeof count === 'number') {
      document.querySelectorAll('[data-cart-count], .cart-count, #cart-icon .count, #total-items')
        .forEach(el => el.textContent = fmtNum.format(count));
    }
    const subtotal = data.cart_subtotal ?? data.subtotal;
    if (typeof subtotal === 'number') {
      ['#mini-cart-subtotal', '#cart-subtotal', '#cart-grand-total']
        .forEach(sel => {
          const el = document.querySelector(sel);
          if (el) el.textContent = 'Rp' + fmtNum.format(Math.round(subtotal));
        });
    }
  }

  async function postCartJSON(payload, btn) {
    let token = $('#quick-view-add-to-cart-form').attr('attr-csrf') || '';
    if (!token) {
      token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    }
    console.log(token);

    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    };
    if (token) headers['X-CSRF-TOKEN'] = token;

    try {
      if (btn) {
        btn.classList.add('disabled');
        btn.setAttribute('aria-disabled', 'true');
        btn.style.pointerEvents = 'none';
        const originalHtml = btn.innerHTML;
        btn.dataset.originalHtml = originalHtml;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Menambahkan...';
      }

      // Penting: backend-mu memvalidasi "product_variant_id" (bukan "variant_id")
      const res = await fetch(ROUTES.cartStore, {
        method: 'POST',
        credentials: 'same-origin',
        headers,
        body: JSON.stringify({
          _token: token,
          product_id: payload.product_id || undefined,
          sku: payload.sku || undefined,
          variant_id: payload.product_variant_id ?? payload.variant_id ?? undefined,
          qty: payload.qty ?? 1
        })
      });

      if (res.status === 401) {
        showToast('warning', 'Silakan login terlebih dahulu');
        setTimeout(() => window.location.href = ROUTES.login, 1200);
        return;
      }
      if (!res.ok) {
        const text = await res.text();
        throw new Error(`Gagal (${res.status}). ${text.slice(0, 200)}`);
      }

      const data = await safeJson(res);
      showToast('success', data.message || 'Produk ditambahkan ke keranjang');

      // Livewire hooks (opsional)
      if (window.Livewire) {
        window.Livewire.dispatch('cartUpdated');
        window.Livewire.dispatch('itemAddedToCart', {
          product_id: payload.product_id,
          variant_id: payload.product_variant_id ?? payload.variant_id,
          quantity: payload.qty
        });
      }

      updateCartUI(data);
      closeQuickViewModal();

    } catch (err) {
      console.error('[Cart] Error:', err);
      showToast('danger', err.message || 'Gagal menambahkan ke keranjang');
    } finally {
      if (btn && !window.location.pathname.startsWith('/login-register')) {
        btn.classList.remove('disabled');
        btn.setAttribute('aria-disabled', 'false');
        btn.style.pointerEvents = '';
        if (btn.dataset.originalHtml) {
          btn.innerHTML = btn.dataset.originalHtml;
          delete btn.dataset.originalHtml;
        }
      }
    }
  }
  window.__postCartJSON = postCartJSON;

  // Delegasi klik tombol add-to-cart dari dalam modal
  modalEl.addEventListener('click', function (e) {
    const btn = e.target.closest('button.pataku-btn');
    if (!btn) return;
    e.preventDefault();

    const qtyInput = modalEl.querySelector('.pro-qty input');
    const qty = Math.max(1, parseInt(qtyInput?.value, 10) || 1);
    const productId = btn.dataset.productId || '';
    const sku = btn.dataset.sku || '';
    const variantId = btn.dataset.variantId || null;

    if (!sku && !productId) {
      showToast('warning', 'Silakan pilih varian terlebih dahulu.');
      return;
    }

    postCartJSON({
      product_id: productId,
      sku,
      product_variant_id: variantId,
      qty
    }, btn);
  });

})();

// ============================================
// WISHLIST MANAGER
// ============================================

const WishlistManager = {
  async add(productId, productVariantId = null, btn = null) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const url = document.body?.getAttribute('data-wishlist-add') || '/wishlist';

    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    };
    if (token) headers['X-CSRF-TOKEN'] = token;

    try {
      if (btn) {
        btn.classList.add('disabled'); btn.style.pointerEvents = 'none';
      }
      const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers,
        body: JSON.stringify({
          product_id: productId,
          product_variant_id: productVariantId
        })
      });
      if (res.status === 401) {
        showToast('warning', 'Silakan login terlebih dahulu');
        setTimeout(() => window.location.href = '/login-register', 1200);
        return null;
      }
      if (!res.ok) {
        const text = await res.text();
        throw new Error(text.slice(0, 200));
      }
      const data = await res.json().catch(() => ({}));
      showToast('success', data.message || 'Ditambahkan ke wishlist');

      // Tandai tombol aktif
      if (btn) {
        btn.classList.add('active'); // gunakan CSS untuk warna/ikon aktif
        btn.setAttribute('aria-pressed', 'true');
        if (data.id) btn.dataset.wishlistId = data.id;
      }
      return data;
    } catch (e) {
      console.error('[Wishlist] add', e);
      showToast('danger', e.message || 'Gagal menambah wishlist');
      return null;
    } finally {
      if (btn) { btn.classList.remove('disabled'); btn.style.pointerEvents = ''; }
    }
  },

  async remove(wishlistId, btn = null) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const base = document.body?.getAttribute('data-wishlist-remove') || '/wishlist';
    const url = `${base.replace(/\/+$/,'')}/${encodeURIComponent(wishlistId)}`;

    const headers = {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    };
    if (token) headers['X-CSRF-TOKEN'] = token;

    try {
      if (btn) {
        btn.classList.add('disabled'); btn.style.pointerEvents = 'none';
      }
      const res = await fetch(url, {
        method: 'DELETE',
        credentials: 'same-origin',
        headers
      });
      if (res.status === 401) {
        showToast('warning', 'Silakan login terlebih dahulu');
        setTimeout(() => window.location.href = '/login-register', 1200);
        return false;
      }
      if (!res.ok) {
        const text = await res.text();
        throw new Error(text.slice(0, 200));
      }
      const data = await res.json().catch(() => ({}));
      showToast('success', data.message || 'Dihapus dari wishlist');

      // Lepas status aktif
      if (btn) {
        btn.classList.remove('active');
        btn.setAttribute('aria-pressed', 'false');
        delete btn.dataset.wishlistId;
      }
      return true;
    } catch (e) {
      console.error('[Wishlist] remove', e);
      showToast('danger', e.message || 'Gagal hapus wishlist');
      return false;
    } finally {
      if (btn) { btn.classList.remove('disabled'); btn.style.pointerEvents = ''; }
    }
  },

  async toggle(btn) {
    const productId = btn?.dataset.productId;
    const variantId = btn?.dataset.variantId || null;
    const wid = btn?.dataset.wishlistId;

    if (!productId) {
      showToast('warning', 'Produk tidak valid');
      return;
    }
    if (wid) {
      await this.remove(wid, btn);
    } else {
      await this.add(productId, variantId, btn);
    }
  }
};

// Delegasi klik tombol wishlist
document.addEventListener('click', function (e) {
  const btn = e.target.closest('[data-action="wishlist-toggle"], .wishlist-toggle, .add-to-wishlist');
  if (!btn) return;
  e.preventDefault();
  WishlistManager.toggle(btn);
});
