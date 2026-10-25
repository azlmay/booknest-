// =====================================================
// BookNest - helpers shared by the shop and admin pages
// =====================================================

const API = 'api.php';

// Escape text before putting it into HTML (prevents XSS)
function esc(value) {
    return String(value ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}

function money(amount) {
    return 'LKR ' + Number(amount).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function orderNumber(id) {
    return '#BN' + String(id).padStart(5, '0');
}

// Call the PHP API. GET when no data, POST (JSON) when data is given.
async function api(action, data = null, query = '') {
    const options = data
        ? { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) }
        : { method: 'GET' };
    const res = await fetch(`${API}?action=${action}${query}`, options);
    if (res.status === 401 && location.pathname.endsWith('admin.php')) {
        location.reload();
    }
    return res.json();
}

let toastInstance = null;
function showToast(message, type = 'success') {
    const el = document.getElementById('toast');
    if (!toastInstance) toastInstance = new bootstrap.Toast(el, { delay: 2600 });
    el.classList.remove('success', 'error');
    el.classList.add(type);
    document.getElementById('toastMsg').textContent = message;
    toastInstance.show();
}

// ---------- Book covers ----------
// Uses the book's own cover link, or Open Library by ISBN.
// If no image loads, a designed cover with the title is shown underneath.
const COVER_COLORS = [
    ['#1f4d3a', '#2f6b52'], ['#7a2e2e', '#a14a3b'], ['#22395c', '#3b5a8a'],
    ['#5b3a6b', '#7d5390'], ['#8a5a1f', '#b07a33'], ['#2d4a4a', '#467070']
];

function coverURL(book) {
    if (book.cover_url) return book.cover_url;
    if (book.isbn) return `https://covers.openlibrary.org/b/isbn/${encodeURIComponent(book.isbn)}-L.jpg?default=false`;
    return '';
}

function coverHTML(book, extraClass = '') {
    const [c1, c2] = COVER_COLORS[Math.abs(Number(book.id) || 0) % COVER_COLORS.length];
    const url = coverURL(book);
    return `
        <div class="cover ${extraClass}" style="--c1:${c1};--c2:${c2}">
            <div class="cover-fallback">
                <span class="cover-title">${esc(book.title || 'Untitled')}</span>
                <span class="cover-author">${esc(book.author || '')}</span>
            </div>
            ${url ? `<img src="${esc(url)}" alt="Cover of ${esc(book.title)}" loading="lazy" onerror="this.remove()">` : ''}
        </div>`;
}
