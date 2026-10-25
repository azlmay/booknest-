// =====================================================
// BookNest - Admin panel
// CRUD for books + order management
// =====================================================

let books = [];
let deleteId = null;

const $ = id => document.getElementById(id);
const bookModal   = new bootstrap.Modal($('bookModal'));
const deleteModal = new bootstrap.Modal($('deleteModal'));

/* =============== STATS =============== */

async function loadStats() {
    const r = await api('stats');
    if (!r.success) return;
    const s = r.data;
    $('sBooks').textContent   = s.books;
    $('sStock').textContent   = s.stock;
    $('sLow').textContent     = s.low_stock;
    $('sOrders').textContent  = s.orders;
    $('sPending').textContent = s.pending;
    $('sRevenue').textContent = money(s.revenue).replace('.00', '');
}

/* =============== READ BOOKS =============== */

async function loadBooks() {
    try {
        const r = await api('books');
        if (!r.success) throw new Error(r.message);
        books = r.data.map(b => ({ ...b, id: Number(b.id), price: Number(b.price), stock: Number(b.stock) }));
        renderBooks();
    } catch (err) {
        $('bookTable').innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">${esc(err.message || 'Could not load books.')}</td></tr>`;
    }
}

function renderBooks() {
    const q = $('bookSearch').value.trim().toLowerCase();
    const list = books.filter(b => !q || `${b.title} ${b.author} ${b.category} ${b.isbn || ''}`.toLowerCase().includes(q));
    if (list.length === 0) {
        $('bookTable').innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No books found.</td></tr>';
        return;
    }
    $('bookTable').innerHTML = list.map(b => `
        <tr>
            <td>
                <div class="d-flex align-items-center gap-3">
                    ${coverHTML(b, 'cover-xs')}
                    <div class="min-w-0">
                        <div class="fw-semibold">${esc(b.title)}</div>
                        <div class="small text-muted">${esc(b.author)}</div>
                    </div>
                </div>
            </td>
            <td><span class="badge badge-cat">${esc(b.category)}</span></td>
            <td class="text-end text-nowrap">${money(b.price)}</td>
            <td class="text-center"><span class="${b.stock === 0 ? 'stock-out' : b.stock < 5 ? 'stock-low' : ''}">${b.stock}</span></td>
            <td class="text-center text-nowrap">
                <button class="btn btn-sm btn-outline-primary" data-edit="${b.id}" title="Edit"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-danger" data-delete="${b.id}" title="Delete"><i class="bi bi-trash"></i></button>
            </td>
        </tr>`).join('');
}

/* =============== CREATE / UPDATE =============== */

const fields = ['title', 'author', 'category', 'price', 'stock', 'isbn', 'cover_url', 'description'];

function updatePreview() {
    $('coverPreview').innerHTML = coverHTML({
        id: Number($('bookId').value) || books.length + 1,
        title: $('title').value || 'Book title',
        author: $('author').value || 'Author',
        isbn: $('isbn').value.replace(/[\s-]/g, ''),
        cover_url: $('cover_url').value.trim(),
    });
}

function openForm(book = null) {
    const form = $('bookForm');
    form.reset();
    form.classList.remove('was-validated');
    $('bookId').value = book ? book.id : '';
    $('modalTitle').textContent = book ? 'Edit Book' : 'Add New Book';
    if (book) {
        fields.forEach(f => { $(f).value = book[f] ?? ''; });
        // Keep custom categories that are not in the list
        if (![...$('category').options].some(o => o.value === book.category)) {
            $('category').add(new Option(book.category, book.category));
            $('category').value = book.category;
        }
    }
    updatePreview();
    bookModal.show();
}

['title', 'author', 'isbn', 'cover_url'].forEach(f => $(f).addEventListener('input', updatePreview));

$('addBtn').addEventListener('click', () => openForm());

$('bookForm').addEventListener('submit', async e => {
    e.preventDefault();
    const form = e.target;
    if (!form.checkValidity()) { form.classList.add('was-validated'); return; }

    const data = {};
    fields.forEach(f => { data[f] = $(f).value.trim(); });
    const id = $('bookId').value;
    if (id) data.id = Number(id);

    const btn = $('saveBtn');
    btn.disabled = true;
    try {
        const r = await api(id ? 'book_update' : 'book_create', data);
        showToast(r.message, r.success ? 'success' : 'error');
        if (r.success) {
            bookModal.hide();
            loadBooks();
            loadStats();
        }
    } catch {
        showToast('Something went wrong. Please try again.', 'error');
    } finally {
        btn.disabled = false;
    }
});

/* =============== DELETE =============== */

$('bookTable').addEventListener('click', e => {
    const edit = e.target.closest('[data-edit]');
    const del  = e.target.closest('[data-delete]');
    if (edit) openForm(books.find(b => b.id === Number(edit.dataset.edit)));
    if (del) {
        const b = books.find(x => x.id === Number(del.dataset.delete));
        if (!b) return;
        deleteId = b.id;
        $('deleteName').textContent = b.title;
        deleteModal.show();
    }
});

$('confirmDelete').addEventListener('click', async () => {
    try {
        const r = await api('book_delete', { id: deleteId });
        showToast(r.message, r.success ? 'success' : 'error');
        if (r.success) {
            deleteModal.hide();
            loadBooks();
            loadStats();
        }
    } catch {
        showToast('Something went wrong. Please try again.', 'error');
    }
});

$('bookSearch').addEventListener('input', renderBooks);

/* =============== ORDERS =============== */

async function loadOrders() {
    try {
        const r = await api('orders');
        if (!r.success) throw new Error(r.message);
        renderOrders(r.data);
    } catch (err) {
        $('orderTable').innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">${esc(err.message || 'Could not load orders.')}</td></tr>`;
    }
}

function renderOrders(orders) {
    if (orders.length === 0) {
        $('orderTable').innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No orders yet. Place one from the shop to see it here.</td></tr>';
        return;
    }
    const statuses = ['Pending', 'Shipped', 'Delivered', 'Cancelled'];
    $('orderTable').innerHTML = orders.map(o => `
        <tr>
            <td class="text-nowrap">
                <div class="fw-semibold">${orderNumber(o.id)}</div>
                <div class="small text-muted">${esc(new Date(o.created_at.replace(' ', 'T')).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' }))}</div>
            </td>
            <td>
                <div class="fw-semibold">${esc(o.customer_name)}</div>
                <div class="small text-muted">${esc(o.phone)}</div>
                <div class="small text-muted">${esc(o.address)}, ${esc(o.city)}</div>
            </td>
            <td class="small">${o.items.map(i => `${esc(i.title)} &times; ${Number(i.quantity)}`).join('<br>')}</td>
            <td class="text-end text-nowrap fw-semibold">${money(o.total)}</td>
            <td>
                <select class="form-select form-select-sm status-select status-${esc(o.status)}" data-order="${o.id}" ${o.status === 'Cancelled' ? 'disabled' : ''} aria-label="Order status">
                    ${statuses.map(s => `<option ${s === o.status ? 'selected' : ''}>${s}</option>`).join('')}
                </select>
            </td>
        </tr>`).join('');
}

$('orderTable').addEventListener('change', async e => {
    const select = e.target.closest('[data-order]');
    if (!select) return;
    const r = await api('order_status', { id: Number(select.dataset.order), status: select.value });
    showToast(r.message, r.success ? 'success' : 'error');
    loadOrders();
    loadStats();
    loadBooks();
});

/* =============== START =============== */

loadStats();
loadBooks();
loadOrders();
