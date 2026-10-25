// =====================================================
// BookNest - Shop page
// 1. Animated text
// 2. Load and display books (READ)
// 3. Shopping bag + checkout (creates orders)
// =====================================================

let books = [];
let activeCategory = 'All';
let cart = loadCart();
let detailId = null;

const $ = id => document.getElementById(id);
const bookModal     = new bootstrap.Modal($('bookModal'));
const checkoutModal = new bootstrap.Modal($('checkoutModal'));
const successModal  = new bootstrap.Modal($('successModal'));
const cartPanel     = new bootstrap.Offcanvas($('cartPanel'));

/* =============== ANIMATED TEXT =============== */

// 1. Wave title: every letter drops in, then gently waves
function initWaveTitle() {
    const el = $('waveTitle');
    const words = el.textContent.trim().split(/\s+/);
    el.textContent = '';
    let i = 0;
    words.forEach((word, w) => {
        const wordSpan = document.createElement('span');
        wordSpan.className = 'word';
        [...word].forEach(ch => {
            const span = document.createElement('span');
            span.className = 'ch';
            span.textContent = ch;
            span.style.animationDelay = `${i * 0.045}s, ${1.6 + i * 0.07}s`;
            wordSpan.appendChild(span);
            i++;
        });
        el.appendChild(wordSpan);
        if (w < words.length - 1) el.appendChild(document.createTextNode(' '));
    });
}

// 2. Typewriter: types and deletes phrases in a loop
function initTypewriter() {
    const phrases = ['favourite novel.', 'great read.', 'life-changing book.', 'bedtime story.', 'coding guide.'];
    const el = $('typed');
    let p = 0, c = 0, deleting = false;
    (function tick() {
        const word = phrases[p];
        el.textContent = word.substring(0, c);
        if (!deleting && c < word.length) { c++; setTimeout(tick, 85); }
        else if (!deleting) { deleting = true; setTimeout(tick, 1600); }
        else if (c > 0) { c--; setTimeout(tick, 40); }
        else { deleting = false; p = (p + 1) % phrases.length; setTimeout(tick, 300); }
    })();
}

// 3. Section titles reveal when scrolled into view
function initReveal() {
    const observer = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
    }, { threshold: 0.4 });
    document.querySelectorAll('.reveal-text').forEach(el => observer.observe(el));
}

// 4. Numbers count up
function animateCounter(el, target) {
    const start = performance.now();
    (function step(now) {
        const progress = Math.min((now - start) / 1200, 1);
        el.textContent = Math.floor(progress * target);
        if (progress < 1) requestAnimationFrame(step);
    })(start);
}

function updateStats() {
    animateCounter($('statBooks'), books.length);
    animateCounter($('statAuthors'), new Set(books.map(b => b.author)).size);
    animateCounter($('statCats'), new Set(books.map(b => b.category)).size);
}

/* =============== LOAD BOOKS (READ) =============== */

async function loadBooks() {
    try {
        const result = await api('books');
        if (!result.success) throw new Error(result.message);
        books = result.data.map(b => ({ ...b, id: Number(b.id), price: Number(b.price), stock: Number(b.stock) }));
        cleanCart();
        renderChips();
        renderGrid();
        renderHeroStack();
        renderCart();
        updateStats();
    } catch (err) {
        $('bookGrid').innerHTML =
            `<div class="col-12 text-center text-danger py-5">${esc(err.message || 'Could not load books.')}</div>`;
    }
}

function renderHeroStack() {
    const picks = [...books].sort((a, b) => (b.isbn ? 1 : 0) - (a.isbn ? 1 : 0)).slice(0, 3);
    $('heroStack').innerHTML = picks.map(b => coverHTML(b)).join('');
}

function renderChips() {
    const categories = ['All', ...[...new Set(books.map(b => b.category))].sort()];
    if (!categories.includes(activeCategory)) activeCategory = 'All';
    $('categoryChips').innerHTML = categories.map(c =>
        `<button type="button" class="chip ${c === activeCategory ? 'active' : ''}" data-cat="${esc(c)}">${esc(c)}</button>`
    ).join('');
}

function filteredBooks() {
    const q = $('searchInput').value.trim().toLowerCase();
    const list = books.filter(b =>
        (activeCategory === 'All' || b.category === activeCategory) &&
        (!q || `${b.title} ${b.author} ${b.category}`.toLowerCase().includes(q))
    );
    const sorters = {
        newest:     (a, b) => b.id - a.id,
        title:      (a, b) => a.title.localeCompare(b.title),
        price_asc:  (a, b) => a.price - b.price,
        price_desc: (a, b) => b.price - a.price,
    };
    return list.sort(sorters[$('sortSelect').value] || sorters.newest);
}

function renderGrid() {
    const list = filteredBooks();
    $('resultCount').textContent = `${list.length} book${list.length === 1 ? '' : 's'}`;

    if (list.length === 0) {
        $('bookGrid').innerHTML = `
            <div class="col-12 empty-state">
                <i class="bi bi-search"></i>
                <p>No books match your search.</p>
            </div>`;
        return;
    }

    $('bookGrid').innerHTML = list.map((b, i) => {
        const out = b.stock <= 0;
        return `
        <div class="col-6 col-md-4 col-lg-3">
            <article class="book-card ${out ? 'out' : ''}" style="animation-delay:${Math.min(i, 12) * 0.04}s">
                <button type="button" class="cover-btn" data-open="${b.id}" aria-label="View ${esc(b.title)}">
                    ${coverHTML(b)}
                </button>
                <div class="book-body">
                    <span class="book-cat">${esc(b.category)}</span>
                    <h3 class="book-title" data-open="${b.id}">${esc(b.title)}</h3>
                    <p class="book-author">${esc(b.author)}</p>
                    ${!out && b.stock < 5 ? `<div class="stock-note">Only ${b.stock} left</div>` : ''}
                    <div class="book-foot">
                        <span class="book-price">${money(b.price)}</span>
                        ${out
                            ? '<span class="sold-out">Sold out</span>'
                            : `<button type="button" class="add-btn" data-add="${b.id}" aria-label="Add ${esc(b.title)} to bag"><i class="bi bi-plus-lg"></i></button>`}
                    </div>
                </div>
            </article>
        </div>`;
    }).join('');
}

/* =============== BOOK DETAILS =============== */

function openBook(id) {
    const b = books.find(x => x.id === id);
    if (!b) return;
    detailId = id;
    const inBag = cart[id] || 0;
    const canAdd = b.stock - inBag;
    $('bookDetail').innerHTML = `
        <div class="row g-4">
            <div class="col-sm-5">${coverHTML(b, 'cover-detail')}</div>
            <div class="col-sm-7 d-flex flex-column">
                <span class="book-cat">${esc(b.category)}</span>
                <h3 class="detail-title">${esc(b.title)}</h3>
                <p class="book-author mb-2">by ${esc(b.author)}</p>
                <div class="detail-price">${money(b.price)}</div>
                <div class="mb-3">${b.stock <= 0
                    ? '<span class="badge text-bg-secondary">Sold out</span>'
                    : `<span class="badge stock-badge"><i class="bi bi-check-circle"></i> In stock (${b.stock})</span>`}</div>
                <p class="detail-desc">${esc(b.description || 'No description yet.')}</p>
                ${b.isbn ? `<p class="small text-muted mb-3">ISBN: ${esc(b.isbn)}</p>` : ''}
                ${canAdd > 0 ? `
                <div class="d-flex gap-2 mt-auto align-items-center">
                    <div class="qty">
                        <button type="button" data-step="-1" aria-label="Decrease">−</button>
                        <input type="number" id="detailQty" value="1" min="1" max="${canAdd}" aria-label="Quantity">
                        <button type="button" data-step="1" aria-label="Increase">+</button>
                    </div>
                    <button type="button" class="btn btn-main flex-grow-1" id="detailAdd"><i class="bi bi-bag-plus"></i> Add to Bag</button>
                </div>` : (b.stock > 0 ? '<p class="text-muted small mt-auto">All available copies are already in your bag.</p>' : '')}
            </div>
        </div>`;
    bookModal.show();
}

$('bookDetail').addEventListener('click', e => {
    const step = e.target.closest('[data-step]');
    const input = $('detailQty');
    if (step && input) {
        const max = Number(input.max);
        input.value = Math.min(max, Math.max(1, (Number(input.value) || 1) + Number(step.dataset.step)));
    }
    if (e.target.closest('#detailAdd')) {
        const qty = Math.max(1, Math.floor(Number(input.value) || 1));
        if (addToCart(detailId, qty)) bookModal.hide();
    }
});

/* =============== SHOPPING BAG =============== */

function loadCart() {
    try {
        const saved = JSON.parse(localStorage.getItem('booknest_cart') || '{}');
        return saved && typeof saved === 'object' && !Array.isArray(saved) ? saved : {};
    } catch {
        return {};
    }
}

function saveCart() {
    try { localStorage.setItem('booknest_cart', JSON.stringify(cart)); } catch { /* storage unavailable */ }
}

// Remove books that no longer exist and cap quantities at the stock level
function cleanCart() {
    for (const id of Object.keys(cart)) {
        const b = books.find(x => x.id === Number(id));
        const q = Math.floor(Number(cart[id]) || 0);
        if (!b || b.stock <= 0 || q <= 0) delete cart[id];
        else cart[id] = Math.min(q, b.stock);
    }
    saveCart();
}

function addToCart(id, qty = 1) {
    const b = books.find(x => x.id === id);
    if (!b) return false;
    const current = cart[id] || 0;
    if (current + qty > b.stock) {
        const left = b.stock - current;
        showToast(left > 0 ? `Only ${left} more ${left === 1 ? 'copy' : 'copies'} available.` : 'No more copies available.', 'error');
        return false;
    }
    cart[id] = current + qty;
    saveCart();
    renderCart();
    const badge = $('cartCount');
    badge.classList.remove('bump');
    void badge.offsetWidth;
    badge.classList.add('bump');
    showToast(`"${b.title}" added to your bag.`);
    return true;
}

function cartTotals() {
    let subtotal = 0, count = 0;
    for (const [id, q] of Object.entries(cart)) {
        const b = books.find(x => x.id === Number(id));
        if (b) { subtotal += b.price * q; count += q; }
    }
    const delivery = subtotal === 0 || subtotal >= SHOP.freeOver ? 0 : SHOP.deliveryFee;
    return { subtotal, delivery, total: subtotal + delivery, count };
}

function renderCart() {
    const { subtotal, delivery, total, count } = cartTotals();
    $('cartCount').textContent = count;
    $('cartCount').style.display = count ? 'grid' : 'none';

    const entries = Object.entries(cart);
    if (entries.length === 0) {
        $('cartItems').innerHTML = `
            <div class="empty-state">
                <i class="bi bi-bag"></i>
                <p>Your bag is empty.</p>
                <button class="btn btn-ghost btn-sm" data-bs-dismiss="offcanvas">Browse books</button>
            </div>`;
        $('cartFooter').style.display = 'none';
        return;
    }

    $('cartFooter').style.display = '';
    $('cartItems').innerHTML = entries.map(([id, q]) => {
        const b = books.find(x => x.id === Number(id));
        if (!b) return '';
        return `
        <div class="cart-item">
            ${coverHTML(b, 'cover-sm')}
            <div class="flex-grow-1 min-w-0">
                <div class="cart-title">${esc(b.title)}</div>
                <div class="small text-muted">${money(b.price)}</div>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <div class="qty qty-sm">
                        <button type="button" data-dec="${b.id}" aria-label="Decrease">−</button>
                        <span>${q}</span>
                        <button type="button" data-inc="${b.id}" aria-label="Increase" ${q >= b.stock ? 'disabled' : ''}>+</button>
                    </div>
                    <button type="button" class="btn btn-link btn-sm text-danger p-0" data-remove="${b.id}">Remove</button>
                </div>
            </div>
        </div>`;
    }).join('');

    $('cartSubtotal').textContent = money(subtotal);
    $('cartDelivery').textContent = delivery === 0 ? 'FREE' : money(delivery);
    $('cartTotal').textContent = money(total);
    $('freeNote').innerHTML = subtotal >= SHOP.freeOver
        ? '<i class="bi bi-gift"></i> You get free delivery!'
        : `Add <strong>${money(SHOP.freeOver - subtotal)}</strong> more for free delivery.`;
}

$('cartItems').addEventListener('click', e => {
    const inc = e.target.closest('[data-inc]');
    const dec = e.target.closest('[data-dec]');
    const rem = e.target.closest('[data-remove]');
    if (inc) {
        const id = Number(inc.dataset.inc);
        const b = books.find(x => x.id === id);
        if (b && cart[id] < b.stock) cart[id]++;
    } else if (dec) {
        const id = Number(dec.dataset.dec);
        if (--cart[id] <= 0) delete cart[id];
    } else if (rem) {
        delete cart[Number(rem.dataset.remove)];
    } else {
        return;
    }
    saveCart();
    renderCart();
});

/* =============== CHECKOUT (CREATE ORDER) =============== */

$('checkoutBtn').addEventListener('click', () => {
    $('checkoutTotal').textContent = money(cartTotals().total);
    $('checkoutForm').classList.remove('was-validated');
    cartPanel.hide();
    checkoutModal.show();
});

$('checkoutForm').addEventListener('submit', async e => {
    e.preventDefault();
    const form = e.target;
    if (!form.checkValidity()) { form.classList.add('was-validated'); return; }

    const btn = $('placeOrderBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Placing...';
    try {
        const result = await api('order_create', {
            name:    $('cName').value.trim(),
            phone:   $('cPhone').value.trim(),
            address: $('cAddress').value.trim(),
            city:    $('cCity').value.trim(),
            items:   Object.entries(cart).map(([id, q]) => ({ book_id: Number(id), quantity: q })),
        });
        if (!result.success) {
            showToast(result.message, 'error');
            loadBooks();
            return;
        }
        cart = {};
        saveCart();
        form.reset();
        checkoutModal.hide();
        $('orderNo').textContent = orderNumber(result.data.order_id);
        $('orderTotal').textContent = money(result.data.total);
        successModal.show();
        loadBooks();
    } catch {
        showToast('Something went wrong. Please try again.', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Place Order';
    }
});

/* =============== EVENTS =============== */

$('bookGrid').addEventListener('click', e => {
    const add = e.target.closest('[data-add]');
    if (add) { addToCart(Number(add.dataset.add)); return; }
    const open = e.target.closest('[data-open]');
    if (open) openBook(Number(open.dataset.open));
});

$('categoryChips').addEventListener('click', e => {
    const chip = e.target.closest('[data-cat]');
    if (!chip) return;
    activeCategory = chip.dataset.cat;
    renderChips();
    renderGrid();
});

$('searchInput').addEventListener('input', renderGrid);
$('sortSelect').addEventListener('change', renderGrid);

/* =============== START =============== */

initWaveTitle();
initTypewriter();
initReveal();
renderCart();
loadBooks();
