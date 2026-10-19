(function () {
    'use strict';

    // Confirm destructive actions.
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => { if (!confirm(form.dataset.confirm)) e.preventDefault(); });
    });

    // Live slug preview while typing a new title.
    const source = document.querySelector('[data-slug-source]');
    const target = document.querySelector('[data-slug-target]');
    if (source && target) {
        let touched = target.value !== '';
        target.addEventListener('input', () => { touched = target.value !== ''; });
        source.addEventListener('input', () => {
            if (!touched) target.placeholder = source.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        });
    }

    // Add/remove video server rows.
    document.querySelectorAll('[data-add-row]').forEach((btn) => {
        const list = btn.previousElementSibling;
        btn.addEventListener('click', () => {
            const row = list.querySelector('.server-row').cloneNode(true);
            row.querySelectorAll('input').forEach((i) => { i.value = ''; });
            row.querySelector('input').placeholder = 'Server ' + (list.children.length + 1);
            list.appendChild(row);
        });
        list.addEventListener('click', (e) => {
            if (!e.target.closest('[data-remove-row]')) return;
            const row = e.target.closest('.server-row');
            if (list.children.length > 1) row.remove();
            else row.querySelectorAll('input').forEach((i) => { i.value = ''; });
        });
    });
})();
