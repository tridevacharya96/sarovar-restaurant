// js/reviews.js
'use strict';

let reviewsPage        = 1;
let reviewsSource      = 'all';
let reviewsHasMore     = false;
let selectedRating     = 0;

/* ── Init ──────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    loadReviewSummary();
    loadReviews(true);
    loadGoogleReviews();
    initStarPicker();
    initReviewForm();
});

/* ── Summary ────────────────────────────────────────────── */
function loadReviewSummary() {
    fetch(BASE_PATH + 'api/reviews.php?action=summary', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            const avg   = parseFloat(data.avg_rating || 0);
            const total = parseInt(data.total || 0);

            document.getElementById('reviewsAvgScore').textContent = avg > 0 ? avg.toFixed(1) : '—';
            document.getElementById('reviewsAvgStars').innerHTML   = buildStars(avg);
            document.getElementById('reviewsTotalCount').textContent =
                total > 0 ? `Based on ${total} review${total !== 1 ? 's' : ''}` : 'No reviews yet';

            // Breakdown bars
            const breakdown = document.getElementById('reviewsBreakdown');
            if (breakdown && total > 0) {
                const bars = [5,4,3,2,1].map(n => {
                    const count = parseInt(data[`${numberToWord(n)}_star`] || 0);
                    const pct   = total > 0 ? (count / total * 100).toFixed(0) : 0;
                    return `<div class="rating-bar-row">
                        <div class="rating-bar-label">${n} ★</div>
                        <div class="rating-bar-track">
                            <div class="rating-bar-fill" style="width:0%" data-pct="${pct}"></div>
                        </div>
                        <div class="rating-bar-count">${count}</div>
                    </div>`;
                }).join('');
                breakdown.innerHTML = bars;
                setTimeout(() => {
                    breakdown.querySelectorAll('.rating-bar-fill')
                        .forEach(b => b.style.width = b.dataset.pct + '%');
                }, 100);
            }

            // Inject JSON-LD schema for Google SEO
            injectReviewSchema(avg, total);
        })
        .catch(() => {});
}

/* ── Load Reviews ─────────────────────────────────────── */
function loadReviews(reset = false) {
    if (reset) {
        reviewsPage = 1;
        document.getElementById('reviewsGrid').innerHTML =
            '<div class="reviews-loading"><i class="fas fa-spinner fa-spin"></i> Loading reviews...</div>';
    }

    fetch(`${BASE_PATH}api/reviews.php?action=get&page=${reviewsPage}&source=${reviewsSource}`, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            const grid = document.getElementById('reviewsGrid');

            if (reset) grid.innerHTML = '';

            if (!data.reviews || data.reviews.length === 0) {
                if (reset) grid.innerHTML = `
                    <div class="reviews-loading" style="grid-column:1/-1">
                        <i class="fas fa-comment-slash" style="font-size:36px;margin-bottom:12px;display:block;opacity:0.3"></i>
                        <p>No reviews yet. Be the first to review!</p>
                    </div>`;
                document.getElementById('loadMoreReviews').style.display = 'none';
                return;
            }

            data.reviews.forEach(r => grid.appendChild(buildReviewCard(r)));

            reviewsHasMore = data.has_more;
            const btn = document.getElementById('loadMoreReviews');
            btn.style.display = reviewsHasMore ? 'inline-flex' : 'none';
        })
        .catch(() => {
            document.getElementById('reviewsGrid').innerHTML =
                '<div class="reviews-loading" style="grid-column:1/-1">Failed to load reviews. Please refresh.</div>';
        });
}

function loadMoreReviews() {
    reviewsPage++;
    loadReviews(false);
}

/* ── Google Reviews ────────────────────────────────────── */
function loadGoogleReviews() {
    fetch(`${BASE_PATH}api/reviews.php?action=google`, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (!data.configured) {
                document.getElementById('googleTab').style.display = 'none';
                return;
            }
            // Show Google review link
            if (data.google_review_url) {
                const link = document.getElementById('googleReviewLink');
                if (link) {
                    link.href = data.google_review_url;
                    link.style.display = 'inline-flex';
                }
            }
            // Show Google overall rating if available
            if (data.place_rating && data.total_ratings) {
                const scoreEl = document.getElementById('reviewsAvgScore');
                const countEl = document.getElementById('reviewsTotalCount');
                if (scoreEl) scoreEl.textContent = parseFloat(data.place_rating).toFixed(1);
                if (countEl) countEl.textContent  = `Based on ${data.total_ratings}+ Google reviews`;
                document.getElementById('reviewsAvgStars').innerHTML = buildStars(data.place_rating);
            }
        })
        .catch(() => {
            document.getElementById('googleTab').style.display = 'none';
        });
}

/* ── Tab Switching ─────────────────────────────────────── */
function switchReviewTab(source, el) {
    reviewsSource = source;
    document.querySelectorAll('.reviews-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');

    if (source === 'google') {
        loadGoogleReviewsInGrid();
    } else {
        loadReviews(true);
    }
}

function loadGoogleReviewsInGrid() {
    const grid = document.getElementById('reviewsGrid');
    grid.innerHTML = '<div class="reviews-loading"><i class="fas fa-spinner fa-spin"></i> Loading Google reviews...</div>';
    document.getElementById('loadMoreReviews').style.display = 'none';

    fetch(`${BASE_PATH}api/reviews.php?action=google`, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            grid.innerHTML = '';
            if (!data.reviews || data.reviews.length === 0) {
                grid.innerHTML = `<div class="reviews-loading" style="grid-column:1/-1">
                    ${!data.configured
                        ? '<p>Google reviews not configured yet.<br><small>Add your Google Place ID in Admin → Site Settings → Reviews</small></p>'
                        : '<p>No Google reviews found.</p>'
                    }
                </div>`;
                return;
            }
            data.reviews.forEach(r => grid.appendChild(buildReviewCard(r)));
        })
        .catch(() => {
            grid.innerHTML = '<div class="reviews-loading" style="grid-column:1/-1">Failed to load Google reviews.</div>';
        });
}

/* ── Build Review Card ─────────────────────────────────── */
function buildReviewCard(r) {
    const div      = document.createElement('div');
    div.className  = 'review-card';
    const isGoogle = r.source === 'google';
    const initials = (r.name || 'U').split(' ').map(w => w[0]).join('').substring(0,2).toUpperCase();
    const date     = r.created_at ? new Date(r.created_at).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' }) : '';

    const avatarHtml = (isGoogle && r.photo_url)
        ? `<div class="review-avatar review-avatar-google"><img src="${escHtml(r.photo_url)}" alt="${escHtml(r.name)}" loading="lazy"/></div>`
        : `<div class="review-avatar">${initials}</div>`;

    const sourceBadge = isGoogle
        ? `<span class="review-source-badge source-google"><img src="https://www.gstatic.com/images/branding/product/1x/googleg_48dp.png" width="12" alt=""/> Google</span>`
        : `<span class="review-source-badge source-website"><i class="fas fa-globe"></i> Website</span>`;

    const replyHtml = r.admin_reply
        ? `<div class="review-reply"><strong>The Sarovar Court replied:</strong> ${escHtml(r.admin_reply)}</div>` : '';

    div.innerHTML = `
    <div class="review-card-header">
        <div class="review-card-stars">${buildStars(r.rating)}</div>
        ${sourceBadge}
    </div>
    <p class="review-text">${escHtml(r.review_text || '')}</p>
    ${replyHtml}
    <div class="review-author">
        ${avatarHtml}
        <div>
            <div class="review-author-name">${escHtml(r.name)}</div>
            <div class="review-author-date">${date}</div>
        </div>
    </div>`;
    return div;
}

/* ── Star Picker ────────────────────────────────────────── */
function initStarPicker() {
    const picker = document.getElementById('starPicker');
    if (!picker) return;

    picker.querySelectorAll('i').forEach(star => {
        star.addEventListener('mouseenter', () => highlightStars(parseInt(star.dataset.val)));
        star.addEventListener('mouseleave', () => highlightStars(selectedRating));
        star.addEventListener('click', () => {
            selectedRating = parseInt(star.dataset.val);
            document.getElementById('ratingInput').value = selectedRating;
            highlightStars(selectedRating);
        });
    });
}

function highlightStars(val) {
    document.querySelectorAll('#starPicker i').forEach(s => {
        s.classList.toggle('active', parseInt(s.dataset.val) <= val);
    });
}

/* ── Submit Review Form ─────────────────────────────────── */
function initReviewForm() {
    const form = document.getElementById('reviewForm');
    if (!form) return;
    form.addEventListener('submit', handleReviewSubmit);
}

function handleReviewSubmit(e) {
    e.preventDefault();
    const form    = e.target;
    const btn     = form.querySelector('button[type="submit"]');
    const errorEl = document.getElementById('reviewError');
    const data    = new FormData(form);
    data.append('action', 'submit');

    if (parseInt(data.get('rating')) < 1) {
        errorEl.textContent   = 'Please select a star rating.';
        errorEl.style.display = 'block';
        return;
    }

    errorEl.style.display = 'none';
    setButtonLoading(btn, true);

    fetch(BASE_PATH + 'api/reviews.php', { method: 'POST', body: data, credentials: 'include' })
        .then(r => r.json())
        .then(result => {
            setButtonLoading(btn, false);
            if (result.success) {
                closeModal('reviewModal');
                form.reset();
                selectedRating = 0;
                highlightStars(0);
                document.getElementById('ratingInput').value = 0;

                if (result.show_google_prompt && result.google_url) {
                    document.getElementById('googlePromptLink').href = result.google_url;
                    openModal('googlePromptModal');
                } else {
                    showToast(result.message, 'success');
                }
            } else {
                errorEl.textContent   = result.error || 'Failed to submit review.';
                errorEl.style.display = 'block';
            }
        })
        .catch(() => {
            setButtonLoading(btn, false);
            errorEl.textContent   = 'Network error. Please try again.';
            errorEl.style.display = 'block';
        });
}

/* ── Build Stars HTML ───────────────────────────────────── */
function buildStars(rating) {
    const full  = Math.floor(rating);
    const half  = (rating - full) >= 0.5;
    const empty = 5 - full - (half ? 1 : 0);
    return ''.concat(
        '<i class="fas fa-star"></i>'.repeat(full),
        half  ? '<i class="fas fa-star-half-alt"></i>' : '',
        '<i class="fas fa-star empty"></i>'.repeat(Math.max(0, empty))
    );
}

/* ── JSON-LD Schema for Google SEO ─────────────────────── */
function injectReviewSchema(avgRating, reviewCount) {
    if (!avgRating || !reviewCount) return;
    const script = document.createElement('script');
    script.type  = 'application/ld+json';
    script.textContent = JSON.stringify({
        "@context": "https://schema.org",
        "@type":    "Restaurant",
        "name":     "The Sarovar Court",
        "address": {
            "@type":           "PostalAddress",
            "streetAddress":   "Ispat Market, Ambagan Circle, Bank Street, Sector 19",
            "addressLocality": "Rourkela",
            "addressRegion":   "Odisha",
            "postalCode":      "769005",
            "addressCountry":  "IN"
        },
        "aggregateRating": {
            "@type":       "AggregateRating",
            "ratingValue": avgRating.toFixed(1),
            "reviewCount": reviewCount,
            "bestRating":  "5",
            "worstRating": "1"
        }
    });
    document.head.appendChild(script);
}

function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function numberToWord(n) {
    return ['','one','two','three','four','five'][n] || n;
}
