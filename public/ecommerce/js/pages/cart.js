function deleteItem(id) {
    // Hapus item dari keranjang belanja
    $.ajax({
        url: '/ecommerce/cart/' + id,
        type: 'DELETE',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            // Tampilkan pesan sukses
            alert('Item berhasil dihapus dari keranjang.');
            // Perbarui tampilan keranjang
            updateCartView(response.cart);
        },
        error: function(xhr) {
            // Tampilkan pesan kesalahan
            alert('Terjadi kesalahan saat menghapus item dari keranjang.');
        }
    });
}
