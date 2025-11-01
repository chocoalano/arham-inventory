function changeSort(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort-by', value);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// === Quick View: Varian & Stok sesuai struktur data yang kamu kirim ===
(function () {
    const modalEl = document.getElementById('quick-view-modal-container');
    if (!modalEl) return;

    const FALLBACK_IMAGE = document.getElementsByTagName('meta')['asset']?.content
        ? (document.getElementsByTagName('meta')['asset'].content.replace(/\/+$/g, '') + '/ecommerce/images/fallback-product.png')
        : '/ecommerce/images/fallback-product.png';

    // ========= Helpers =========
    const fmtIDR = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
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
            document.body.appendChild(wrap);
        }
        return wrap;
    }
    function showToast(variant, message) {
        if (!window.bootstrap?.Toast) { alert(message); return; }
        const wrap = ensureToastContainer();
        const id = 't' + Date.now();
        wrap.insertAdjacentHTML('beforeend', `
          <div id="${id}" class="toast align-items-center text-bg-${variant} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="2200">
            <div class="d-flex">
              <div class="toast-body">${message}</div>
              <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
          </div>`);
        const el = document.getElementById(id);
        bootstrap.Toast.getOrCreateInstance(el).show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    }

    // ========= Availability element (dibuat jika belum ada) =========
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

    // ========= Gallery =========
    function buildGallery(modal, item) {
        const largeWrap = modal.querySelector('.quickview-product-large-image-list');
        const smallWrap = modal.querySelector('.quickview-product-small-image-list .nav');
        if (!largeWrap || !smallWrap) return;

        // dukung kunci: gallery/galeri (string|obj), image/gambar
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
        // ambil dari root
        Object.keys(v || {}).forEach(k => {
            if (!allowedAttrSet.has(k.toLowerCase())) return;
            const val = sstr(v[k]).trim();
            if (val) out[k] = val;
        });
        // ambil dari attrs/attributes (jika ada)
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

        if (addBtn) { addBtn.type = 'button'; addBtn.onclick = null; } // hilangkan onclick inline
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

            // jika yang berubah adalah color → ambil langsung by id
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

            // sinkronisasi & enablement
            syncSelectsToVariant(matched);
            refreshSelectEnablement();

            // stok efektif (varian > global)
            const variantStock = getVariantStock(matched);
            const effectiveStock = (variantStock !== null) ? variantStock : stockGlobal;

            // update harga & availability
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

            // tombol
            if (addBtn) {
                const sku = (matched?.sku_variant ?? matched?.sku ?? item.sku ?? item.kode ?? item.code) || '';
                addBtn.dataset.sku = sku;
                addBtn.dataset.variantId = matched?.id ?? '';
                const canAdd = effectiveStock > 0 && (!!matched || !hasVariants);
                setAddBtnState(addBtn, canAdd, { sku, variantId: addBtn.dataset.variantId });
            }

            updateVariantSummary(modal, getSelection(), matched);
            console.log('[QV]',
                { matched, variantStock: getVariantStock(matched), globalStock: (item.stock ?? item.stok ?? item.inventory ?? 0) }
            );
        }

        // Init pertama
        refreshSelectEnablement();
        onSelectionChanged(null);

        // Events
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

        // Title & Desc
        const titleEl = modalEl.querySelector('.product-feature-details .product-title');
        const descEl = modalEl.querySelector('.product-feature-details .product-description');
        if (titleEl) titleEl.textContent = item.title || item.judul || '';
        if (descEl) descEl.textContent = item.description || item.deskripsi || '';

        // Harga default (sebelum varian override)
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
    async function postCartJSON(payload, btn) {
        const form = document.getElementById('quick-view-add-to-cart-form');
        const token = form?.getAttribute('attr-csrf') || document.querySelector('meta[name="csrf-token"]')?.content || '';
        const url = form?.getAttribute('action') || '/cart';

        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
        if (token) { headers['X-CSRF-TOKEN'] = token; headers['X-XSRF-TOKEN'] = token; }

        try {
            if (btn) {
                btn.classList.add('disabled');
                btn.setAttribute('aria-disabled', 'true');
                btn.style.pointerEvents = 'none';
            }

            const res = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers,
                body: JSON.stringify(payload)
            });

            // 1. Periksa 401 UNTUK REDIRECT
            if (res.status === 401) {
                // Ganti '/login' dengan URL halaman login customer Anda yang sebenarnya
                window.location.href = '/login-register';
                // Menghentikan eksekusi lebih lanjut
                return;
            }

            if (!res.ok) {
                // Melempar error untuk status non-2xx lainnya (misalnya 403, 404, 500)
                const text = await res.text();
                throw new Error(`Gagal (${res.status}). ${text.slice(0, 200)}`);
            }

            // Sukses
            const data = await res.json().catch(() => ({}));
            showToast('success', 'Produk ditambahkan ke keranjang');

            if (typeof data?.cart_count === 'number') {
                const badge = document.querySelector('[data-cart-count]');
                if (badge) badge.textContent = data.cart_count;
            }

            const redirectTo = form?.getAttribute('redirect-to') || null;
            if (redirectTo) {
                window.location.href = redirectTo;
            }
        } catch (err) {
            // 2. Menangani error lain yang dilempar dari throw new Error
            console.error(err); // Gunakan console.error untuk pesan yang lebih jelas di konsol

            // Pastikan err.message ditampilkan atau gunakan pesan default
            showToast('danger', err.message || 'Gagal menambahkan ke keranjang');

        } finally {
            // 3. Pastikan tombol kembali normal HANYA jika tidak ada redirect (misalnya, setelah success atau error non-401)
            if (btn && window.location.pathname.startsWith('/login-customer') === false) {
                btn.classList.remove('disabled');
                btn.setAttribute('aria-disabled', 'false');
                btn.style.pointerEvents = '';
            }
        }
    }

    // Delegasi klik tombol add-to-cart
    modalEl.addEventListener('click', function (e) {
        const btn = e.target.closest('button.pataku-btn');
        if (!btn) return;
        e.preventDefault();

        const qtyInput = modalEl.querySelector('.pro-qty input');
        const qty = Math.max(1, parseInt(qtyInput?.value, 10) || 1);
        const sku = btn.dataset.sku || '';
        const variantId = btn.dataset.variantId || null;

        if (!sku) { showToast('warning', 'Silakan pilih varian terlebih dahulu.'); return; }

        postCartJSON({ sku, variant_id: variantId, qty }, btn);
    });

})();
