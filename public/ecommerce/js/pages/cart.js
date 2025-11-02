(function ($) {
  // Ambil konfigurasi yang didefinisikan di Blade file (window.cartConfig)
  const config = window.cartConfig || {};

  const CART_UPDATE_URL = config.updateUrl;
  const CART_SYNC_URL   = config.syncUrl;
  const ITEM_DELETE_TPL = config.deleteTpl;
  const CHECKOUT_URL    = config.checkoutUrl; // Dipertahankan sebagai fallback
  const CSRF_TOKEN      = config.csrfToken;
  const nf              = new Intl.NumberFormat('id-ID');

  // MAP untuk menyimpan status lock setiap baris item (itemId: boolean)
  const itemLocks = new Map();

  // Menyimpan status kuantitas terakhir yang valid dari server, untuk rollback
  const lastValidQuantities = new Map();
  $('#cart-table tbody tr').each(function() {
      const itemId = parseInt($(this).data('item-id'), 10);
      const qty = parseInt($(this).find('[data-qty-input]').val() || '1', 10);
      lastValidQuantities.set(itemId, qty);
  });

  // jQuery AJAX default headers
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }
  });

  // ================== TOAST & UTIL ==================
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
    const wrap = ensureToastContainer();
    const id = 't' + Date.now();
    const color = variant === 'danger' ? 'bg-danger text-white' :
                  variant === 'warning' ? 'bg-warning' :
                  variant === 'success' ? 'bg-success text-white' : 'bg-secondary text-white';
    $(wrap).append(`
      <div id="${id}" class="toast align-items-center ${color} border-0"
           role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="2000">
        <div class="d-flex">
          <div class="toast-body">${$('<div/>').text(message).html()}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    `);
    const el = document.getElementById(id);
    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        bootstrap.Toast.getOrCreateInstance(el).show();
    } else {
        // Fallback for non-Bootstrap environments
        $(el).toast({ delay: 2000 }).toast('show');
    }
    el.addEventListener('hidden.bs.toast', () => el.remove());
  }

  const money = n => nf.format(Math.round(Number(n || 0)));
  const itemDeleteUrl = id => ITEM_DELETE_TPL ? ITEM_DELETE_TPL.replace('__ID__', String(id)) : null;

  function ensureCheckoutState() {
    const hasRows = $('#cart-table tbody tr').length > 0;
    const $btn = $('.checkout-btn');
    if (hasRows) { $btn.removeClass('disabled').removeAttr('aria-disabled tabindex'); }
    else { $btn.addClass('disabled').attr({ 'aria-disabled': true, tabindex: -1 }); }
  }

  function recalcOptimistic() {
    let sum = 0, items = 0;
    $('#cart-table tbody tr').each(function () {
      const $tr = $(this);
      const unit = parseFloat($tr.data('price') || 0);
      // Baca nilai dari input, pastikan >= 1
      const qty  = Math.max(1, parseInt($tr.find('[data-qty-input]').val() || '1', 10));
      sum   += unit * qty;
      items += qty;
      $tr.find('.item-subtotal').text(money(unit * qty));
    });
    $('#cart-subtotal').text(money(sum));
    $('#cart-grand-total').text(money(sum));
    $('#total-items').text(money(items));
    $('[data-cart-count], .cart-count').text(money(items));
  }

  function setRowLoading($row, state) {
    const itemId = parseInt($row.data('item-id'), 10);
    // Tambahkan atau hapus lock
    itemLocks.set(itemId, !!state);

    $row.toggleClass('row-loading', !!state);
    $row.find('[data-qty-input], .qty-btn, .btn-remove').prop('disabled', !!state);
  }

  function debounce(fn, delay){ let t; return function(){ clearTimeout(t); t=setTimeout(()=>fn.apply(this, arguments), delay); }; }

  // ================== UPDATE SATU ITEM (Fungsi Utama Sinkronisasi) ==================
  function syncRow($row) {
    if (!CART_UPDATE_URL) return;

    const itemId  = parseInt($row.data('item-id'), 10);
    const $input  = $row.find('[data-qty-input]');
    const newQty  = Math.max(1, parseInt($input.val() || '1', 10));
    const lastQty = lastValidQuantities.get(itemId) || 1;

    // CEK LOCK: Jika item sedang diupdate, batalkan request ini.
    if (itemLocks.get(itemId)) {
        return;
    }

    // Cegah request jika kuantitas tidak berubah dari status terakhir yang diketahui
    if (newQty === lastQty) {
        return;
    }

    // Optimistic Update & Set Lock
    recalcOptimistic();
    setRowLoading($row, true); // Ini akan mengunci itemLocks.set(itemId, true);

    $.ajax({
      url: CART_UPDATE_URL,
      method: 'PATCH',
      contentType: 'application/json; charset=utf-8',
      data: JSON.stringify({ item_id: itemId, quantity: newQty })
    })
    .done(function(res){
      if (res && typeof res === 'object') {
        // Update nilai-nilai di UI
        if (typeof res.line_subtotal !== 'undefined') {
          $row.find('.item-subtotal').text(money(res.line_subtotal));
        }
        if (typeof res.cart_subtotal !== 'undefined') {
          $('#cart-subtotal, #cart-grand-total').text(money(res.cart_subtotal));
        }
        if (typeof res.cart_items_count !== 'undefined') {
          $('#total-items, [data-cart-count], .cart-count').text(money(res.cart_items_count));
        }
        lastValidQuantities.set(itemId, newQty); // update qty valid

        // Dispatch event untuk update Livewire components (misal: HeaderComponents)
        if (typeof Livewire !== 'undefined') {
            Livewire.dispatch('cartUpdated');
        }

      } else {
        $input.val(lastQty); // Rollback di frontend jika response tidak valid
        recalcOptimistic();
        showToast('warning', 'Gagal sinkron. Coba lagi.');
      }
    })
    .fail(function(xhr){
      // Rollback
      $input.val(lastQty);
      recalcOptimistic();

      let msg = 'Gagal memperbarui jumlah.';
      if (xhr.status === 422) {
        try { msg = (xhr.responseJSON?.message) || 'Validasi gagal.'; } catch(e){}
      } else if (xhr.status === 400 || xhr.status === 401 || xhr.status === 403) {
        try { msg = (xhr.responseJSON?.message) || msg; } catch(e){}
      } else if (xhr.status === 419) {
        msg = 'Sesi berakhir. Muat ulang halaman untuk melanjutkan.';
      }
      showToast('danger', msg);
    })
    .always(function(){
        setRowLoading($row, false); // Melepaskan lock, terlepas dari sukses/gagal
    });
  }

  // Versi debounced untuk event 'input' (ketika user mengetik)
  const debouncedSyncRow = debounce(syncRow, 300);

  // ================== HAPUS ITEM ==================
  function deleteItem(id) {
    const url = itemDeleteUrl(id);
    if (!url) return;

    const $row = $('#cart-table tbody tr[data-item-id="' + id + '"]');
    setRowLoading($row, true);

    $.ajax({ url, method: 'DELETE' })
      .done(function(res){
        $row.remove();
        if ($('#cart-table tbody tr').length === 0) {
          $('.cart-table').html('<div class="alert alert-info text-center mb-0">Keranjang belanja Anda kosong.</div>');
        }
        lastValidQuantities.delete(id); // Hapus dari map valid
        recalcOptimistic();
        ensureCheckoutState();
        showToast('success', 'Item dihapus dari keranjang.');

        if (typeof Livewire !== 'undefined') {
            Livewire.dispatch('itemRemovedFromCart'); // Dispatch Livewire
        }
      })
      .fail(function(xhr){
        let msg = 'Gagal menghapus item.';
        if (xhr.status === 419) msg = 'Sesi berakhir. Muat ulang halaman untuk melanjutkan.';
        showToast('danger', msg);
        setRowLoading($row, false);
      });
  }

  // ================== BULK SYNC ==================
  function syncAll() {
    if (!CART_SYNC_URL) return;

    const payload = [];
    $('#cart-table tbody tr').each(function () {
      payload.push({
        item_id: parseInt($(this).data('item-id'), 10),
        quantity: Math.max(1, parseInt($(this).find('[data-qty-input]').val() || '1', 10)),
      });
    });

    // Optimistic recalculation sebelum kirim request
    recalcOptimistic();

    const $rows = $('#cart-table tbody tr');
    // Set lock untuk semua baris
    $rows.each(function(){ setRowLoading($(this), true); });

    $.ajax({
      url: CART_SYNC_URL,
      method: 'PATCH',
      contentType: 'application/json; charset=utf-8',
      data: JSON.stringify({ items: payload })
    })
    .done(function(res){
      if (res && typeof res === 'object') {
        if (res.lines) {
          Object.keys(res.lines).forEach(function (id) {
            const itemId = parseInt(id, 10);
            const $row = $('#cart-table tbody tr[data-item-id="' + itemId + '"]');
            $row.find('.item-subtotal').text(money(res.lines[id]));

            // Update kuantitas terakhir yang valid
            const currentQty = parseInt($row.find('[data-qty-input]').val() || '1', 10);
            lastValidQuantities.set(itemId, currentQty);
          });
        }
        if (typeof res.cart_subtotal !== 'undefined') {
          $('#cart-subtotal, #cart-grand-total').text(money(res.cart_subtotal));
        }
        if (typeof res.cart_items_count !== 'undefined') {
          $('#total-items, [data-cart-count], .cart-count').text(money(res.cart_items_count));
        }
        showToast('success', 'Keranjang diperbarui.');

        // Dispatch event untuk update Livewire components
        if (typeof Livewire !== 'undefined') {
            Livewire.dispatch('cartUpdated');
        }

      } else {
        showToast('warning', 'Respon tidak valid. Coba lagi.');
      }
    })
    .fail(function(xhr){
      let msg = 'Gagal menyinkronkan keranjang.';
      if (xhr.status === 422) {
        try { msg = (xhr.responseJSON?.message) || 'Validasi/Stok gagal. Periksa kuantitas.'; } catch(e){}
      } else if (xhr.status === 419) {
        msg = 'Sesi berakhir. Muat ulang halaman.';
      } else if (xhr.status === 400 || xhr.status === 401 || xhr.status === 403) {
        try { msg = (xhr.responseJSON?.message) || msg; } catch(e){}
      }
      showToast('danger', msg);

      // Rollback semua kuantitas ke nilai valid terakhir
      $('#cart-table tbody tr').each(function(){
        const $tr = $(this);
        const itemId = parseInt($tr.data('item-id'), 10);
        const last = lastValidQuantities.get(itemId) || 1;
        $tr.find('[data-qty-input]').val(last);
      });
      recalcOptimistic();
    })
    .always(function(){
      // Melepaskan lock untuk semua baris
      $rows.each(function(){ setRowLoading($(this), false); });
      ensureCheckoutState();
    });
  }

  // ================== EVENTS ==================

  // 1. Input manual quantity (Typing)
  $(document).on('input', '[data-qty-input]', function () {
    const $row = $(this).closest('tr');
    // Gunakan debounce untuk mengetik agar tidak terlalu banyak request
    debouncedSyncRow($row);
  });

  // 2. Tombol +/- quantity (Langsung panggil syncRow, MENGANDALKAN LOCK + STOP PROPAGATION)
  $(document).on('click', '.qty-btn', function (e) {
    e.stopImmediatePropagation();
    e.preventDefault();

    const $row = $(this).closest('tr');
    const itemId = parseInt($row.data('item-id'), 10);

    // CEK LOCK
    if (itemLocks.get(itemId)) {
        return;
    }

    const $input = $row.find('[data-qty-input]');
    const diff = $(this).hasClass('inc') ? 1 : -1;
    const currentVal  = parseInt($input.val() || '1', 10);
    const newVal = Math.max(1, currentVal + diff);

    if (newVal !== currentVal) {
      $input.val(newVal);
      // Panggil sinkronisasi segera (tanpa debounce)
      syncRow($row);
    }
  });

  // 3. Delete item
  $(document).on('click', '.btn-remove', function (e) {
    e.preventDefault();
    const id = $(this).data('item-id');
    if (!id) return;
    deleteItem(id);
  });

  // 4. Handle Checkout Button Click (Menggunakan attr-url)
  $(document).on('click', '.checkout-btn:not(.disabled)', function (e) {
    e.preventDefault();

    const $btn = $(this);
    // Prioritaskan attr-url dari elemen yang diklik, fallback ke konfigurasi global
    // Menggunakan .attr('attr-url') untuk mendapatkan nilai atribut
    const redirectUrl = $btn.attr('attr-url') || CHECKOUT_URL;

    if (redirectUrl) {
        // Beri feedback visual sebelum redirect
        $btn.text('Memuat...').prop('disabled', true).addClass('bg-secondary text-white');
        window.location.href = redirectUrl;
    } else {
        showToast('danger', 'URL Checkout tidak ditemukan.');
    }
  });


  // 5. Shim untuk pembaruan keseluruhan
  window.optimisticUpdate = () => syncAll();

  // State awal
  ensureCheckoutState();

})(jQuery);
