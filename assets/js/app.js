(function () {
    'use strict';

    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const store = {
        get(key) {
            try { return JSON.parse(localStorage.getItem(key)) || []; } catch (e) { return []; }
        },
        set(key, value) {
            try { localStorage.setItem(key, JSON.stringify(value)); } catch (e) { /* storage unavailable */ }
        },
    };

    /* Mobile menu */
    const nav = $('#main-nav');
    $('[data-toggle-menu]')?.addEventListener('click', () => nav.classList.toggle('open'));
    document.addEventListener('click', (e) => {
        if (nav?.classList.contains('open') && !e.target.closest('#main-nav, [data-toggle-menu]')) nav.classList.remove('open');
    });

    /* Hero slider */
    const slider = $('[data-slider]');
    if (slider) {
        const slides = $$('.hero-slide', slider);
        const dots = $$('.hero-dots button', slider);
        let index = 0, timer;
        const show = (i) => {
            index = (i + slides.length) % slides.length;
            slides.forEach((s, n) => s.classList.toggle('active', n === index));
            dots.forEach((d, n) => d.classList.toggle('active', n === index));
        };
        const start = () => { clearInterval(timer); if (slides.length > 1) timer = setInterval(() => show(index + 1), 6000); };
        dots.forEach((d) => d.addEventListener('click', () => { show(+d.dataset.slide); start(); }));
        slider.addEventListener('mouseenter', () => clearInterval(timer));
        slider.addEventListener('mouseleave', start);
        start();
    }

    /* Live search */
    const input = $('[data-live-search]');
    if (input) {
        const box = $('.live-results', input.form);
        let t, ctrl;
        input.addEventListener('input', () => {
            clearTimeout(t);
            const q = input.value.trim();
            if (q.length < 2) { box.hidden = true; return; }
            t = setTimeout(async () => {
                ctrl?.abort();
                ctrl = new AbortController();
                try {
                    const api = document.body.dataset.api;
                    const res = await fetch(api + (api.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(q), { signal: ctrl.signal });
                    const items = await res.json();
                    box.innerHTML = items.length
                        ? items.map((i) => `<a href="${esc(i.url)}"><img src="${esc(i.poster)}" alt=""><span>${esc(i.title)}<small>${esc(i.meta)}</small></span></a>`).join('')
                          + `<a class="all" href="#" data-submit>View all results for “${esc(q)}”</a>`
                        : '<a class="all">No results</a>';
                    box.hidden = false;
                } catch (e) { /* aborted or offline */ }
            }, 250);
        });
        box.addEventListener('click', (e) => {
            if (e.target.closest('[data-submit]')) { e.preventDefault(); input.form.submit(); }
        });
        document.addEventListener('click', (e) => { if (!e.target.closest('.search-form')) box.hidden = true; });
        input.addEventListener('keydown', (e) => { if (e.key === 'Escape') box.hidden = true; });
    }

    /* Server switching on the watch page */
    const player = $('[data-player]');
    $$('[data-server-url]').forEach((btn) => btn.addEventListener('click', () => {
        $$('[data-server-url]').forEach((b) => b.classList.toggle('active', b === btn));
        const url = btn.dataset.serverUrl;
        if (btn.dataset.serverKind === 'video') {
            const v = document.createElement('video');
            v.controls = true; v.playsInline = true; v.preload = 'metadata'; v.src = url;
            player.replaceChildren(v);
        } else {
            const f = document.createElement('iframe');
            f.src = url; f.allowFullscreen = true; f.title = 'Video player';
            f.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
            f.referrerPolicy = 'no-referrer-when-downgrade';
            player.replaceChildren(f);
        }
    }));

    /* Episode number filter */
    $$('[data-ep-filter]').forEach((field) => {
        const list = field.closest('.section, .widget').querySelector('[data-ep-list]');
        field.addEventListener('input', () => {
            const q = field.value.trim();
            $$('[data-ep]', list).forEach((a) => { a.hidden = q !== '' && !a.dataset.ep.startsWith(q); });
        });
    });
    const current = $('.ep-list .current');
    if (current) current.parentElement.scrollTop = current.offsetTop - current.parentElement.clientHeight / 2;

    /* Tabs (schedule) */
    $$('[data-tabs]').forEach((tabs) => {
        $$('[data-tab]', tabs).forEach((tab) => tab.addEventListener('click', () => {
            $$('[data-tab]', tabs).forEach((t) => t.classList.toggle('active', t === tab));
            $$('[data-panel]').forEach((p) => { p.hidden = p.dataset.panel !== tab.dataset.tab; });
        }));
    });

    /* Bookmarks */
    const BM = 'ls_bookmarks', HIST = 'ls_history';
    $$('[data-bookmark]').forEach((btn) => {
        const item = JSON.parse(btn.dataset.bookmark);
        const sync = () => {
            const on = store.get(BM).some((b) => b.slug === item.slug);
            btn.textContent = on ? '✓ In My List' : '+ Add to List';
            btn.classList.toggle('active', on);
        };
        btn.addEventListener('click', () => {
            let list = store.get(BM);
            list = list.some((b) => b.slug === item.slug) ? list.filter((b) => b.slug !== item.slug) : [item, ...list];
            store.set(BM, list);
            sync();
        });
        sync();
    });

    /* Watch history */
    const watch = $('[data-history]');
    if (watch) {
        const item = { ...JSON.parse(watch.dataset.history), time: Date.now() };
        store.set(HIST, [item, ...store.get(HIST).filter((h) => h.slug !== item.slug)].slice(0, 30));
    }

    const card = (i, badge) => `<article class="card"><a class="card-link" href="${esc(i.url)}">
        <div class="card-thumb"><img src="${esc(i.poster)}" alt="" loading="lazy">${badge ? `<span class="badge badge-ep">${esc(badge)}</span>` : ''}<span class="card-play">▶</span></div>
        <div class="card-body"><h3 class="card-title">${esc(i.title)}</h3></div></a></article>`;

    const renderHistory = () => {
        const hist = store.get(HIST);
        $$('[data-history-list]').forEach((el) => { el.innerHTML = hist.map((h) => card(h, 'EP ' + h.ep)).join(''); });
        const cw = $('#continue-watching');
        if (cw) cw.hidden = hist.length === 0;
        const empty = $('[data-history-empty]');
        if (empty) empty.hidden = hist.length > 0;
    };
    renderHistory();
    $$('[data-clear-history]').forEach((b) => b.addEventListener('click', () => { store.set(HIST, []); renderHistory(); }));

    const bmList = $('[data-bookmark-list]');
    if (bmList) {
        const bms = store.get(BM);
        bmList.innerHTML = bms.map((b) => card(b, b.type)).join('');
        $('[data-bookmark-empty]').hidden = bms.length > 0;
    }

    /* Back to top */
    const top = $('[data-back-to-top]');
    if (top) {
        window.addEventListener('scroll', () => top.classList.toggle('show', window.scrollY > 600), { passive: true });
        top.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    }
})();
