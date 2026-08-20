<style>
/* P4.4.1E — Price List Workspace UI Polish
 * Route-scoped presentation only. Keep existing forms/routes/queue behavior untouched.
 */
@media (max-width: 639px) {
    .export-card {
        border-radius: 1.25rem !important;
        padding: 1rem !important;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .06) !important;
    }

    .export-card > .flex.items-start.justify-between { align-items: center; }
    .export-card > .flex.items-start.justify-between .h-9.w-9 {
        width: 2.5rem !important;
        height: 2.5rem !important;
        border-radius: .9rem !important;
    }
    .export-card .export-status {
        white-space: nowrap;
        padding: .3rem .55rem !important;
        font-size: .68rem !important;
    }

    .export-card > .mt-4.grid.grid-cols-3 {
        display: flex !important;
        flex-wrap: wrap;
        gap: .35rem .65rem !important;
        margin-top: .8rem !important;
        padding: .65rem .8rem;
        border-radius: .9rem;
        background: rgb(248 250 252);
    }
    .export-card > .mt-4.grid.grid-cols-3 > div {
        display: inline-flex;
        align-items: center;
        gap: .28rem;
        padding: 0 !important;
        background: transparent !important;
    }
    .export-card > .mt-4.grid.grid-cols-3 > div:not(:last-child)::after {
        content: '·';
        margin-left: .3rem;
        color: rgb(148 163 184);
        font-weight: 700;
    }
    .export-card > .mt-4.grid.grid-cols-3 small {
        font-size: .65rem !important;
        letter-spacing: .02em;
    }
    .export-card > .mt-4.grid.grid-cols-3 p {
        margin: 0 !important;
        font-size: .76rem !important;
    }

    .export-card .border-emerald-100.bg-emerald-50\/60 {
        padding: .7rem .8rem !important;
        border-radius: .9rem !important;
        background: rgb(248 250 252 / .9) !important;
        border-color: rgb(226 232 240) !important;
    }
    .export-card .border-emerald-100.bg-emerald-50\/60 > p:first-child {
        color: rgb(100 116 139) !important;
    }

    .export-card > .mt-4.flex.flex-wrap.items-center.gap-2 {
        display: grid !important;
        grid-template-columns: 2.75rem 2.75rem 2.75rem minmax(0, 1fr) 2.75rem;
        gap: .5rem !important;
        align-items: center;
    }

    .export-card .price-list-icon-action {
        display: inline-flex !important;
        width: 2.75rem !important;
        min-width: 2.75rem !important;
        height: 2.75rem !important;
        min-height: 2.75rem !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: .9rem !important;
        padding: 0 !important;
        font-size: 0 !important;
        background: #fff !important;
        border: 1px solid rgb(226 232 240) !important;
        box-shadow: 0 1px 2px rgb(15 23 42 / .04);
    }
    .export-card .price-list-icon-action svg {
        width: 1.22rem;
        height: 1.22rem;
        display: block;
    }
    .export-card .price-list-icon-action[data-action-icon="excel"] { color: rgb(22 163 74) !important; }
    .export-card .price-list-icon-action[data-action-icon="pdf"] { color: rgb(225 29 72) !important; }
    .export-card .price-list-icon-action[data-action-icon="share"] { color: rgb(30 41 59) !important; }
    .export-card .price-list-icon-action:active { transform: scale(.96); }

    .export-card [data-email-open] {
        grid-column: 4;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        width: 100%;
        min-height: 2.75rem !important;
        border-radius: .9rem !important;
        background: rgb(15 23 42) !important;
        border-color: rgb(15 23 42) !important;
        color: white !important;
        font-size: .76rem !important;
        font-weight: 800 !important;
        padding-inline: .8rem !important;
    }
    .export-card [data-email-open] svg { width: 1rem; height: 1rem; }

    .export-card details.ml-auto {
        grid-column: 5;
        margin-left: 0 !important;
    }
    .export-card details.ml-auto > summary {
        width: 2.75rem !important;
        height: 2.75rem !important;
        border-radius: .9rem !important;
        background: white;
        font-size: 0;
    }
    .export-card details.ml-auto > summary svg { width: 1.2rem; height: 1.2rem; }

    .export-card > .mt-4.flex.flex-wrap.items-center.gap-2 > form[action*="/pdf"] {
        width: 2.75rem;
    }

    section form.flex.gap-2:has(input[name="q"]) {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) minmax(8.5rem, .9fr);
        width: 100%;
    }
    section form.flex.gap-2:has(input[name="q"]) input,
    section form.flex.gap-2:has(input[name="q"]) select {
        width: 100%;
        min-width: 0;
    }
}

@media (min-width: 640px) {
    .export-card {
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .export-card:hover {
        transform: translateY(-1px);
        border-color: rgb(203 213 225);
        box-shadow: 0 12px 28px rgba(15, 23, 42, .07);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const icons = {
        excel: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 3h7l4 4v14H7z"/><path d="M14 3v5h5"/><path d="m9.5 12 4 5"/><path d="m13.5 12-4 5"/></svg>',
        pdf: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 3h7l4 4v14H7z"/><path d="M14 3v5h5"/><path d="M9.5 16v-4h1.2a1.4 1.4 0 1 1 0 2.8H9.5"/><path d="M13.5 12v4h1a2 2 0 0 0 0-4z"/></svg>',
        share: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="m8.2 10.8 7.6-4.5"/><path d="m8.2 13.2 7.6 4.5"/></svg>',
        mail: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>',
        more: '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="19" cy="12" r="1.7"/></svg>',
        document: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8.5 8h7"/><path d="M8.5 12h7"/><path d="M8.5 16h5"/></svg>'
    };

    document.querySelectorAll('.export-card').forEach((card) => {
        const excel = card.querySelector('a[href*="/download"]:not([href*="/pdf/"])');
        const pdf = card.querySelector('a[href*="/pdf/download"], form[action*="/pdf"] > button');
        const share = card.querySelector('.share-export');
        const email = card.querySelector('[data-email-open]');
        const more = card.querySelector('details.ml-auto > summary');
        const documentIcon = card.querySelector(':scope > .flex.items-start.justify-between .h-9.w-9');

        const setIconAction = (element, type, label) => {
            if (!element) return;
            element.classList.add('price-list-icon-action');
            element.dataset.actionIcon = type;
            element.setAttribute('title', label);
            element.setAttribute('aria-label', label);
            element.innerHTML = icons[type];
        };

        setIconAction(excel, 'excel', 'Tải Excel');
        setIconAction(pdf, 'pdf', pdf?.closest('form') ? 'Tạo PDF' : 'Tải PDF');
        setIconAction(share, 'share', 'Chia sẻ');

        if (email) {
            email.innerHTML = `${icons.mail}<span>Gửi bảng giá</span>`;
            email.setAttribute('aria-label', 'Gửi bảng giá');
        }
        if (more) {
            more.innerHTML = icons.more;
            more.setAttribute('aria-label', 'Thao tác khác');
            more.setAttribute('title', 'Thao tác khác');
        }
        if (documentIcon) documentIcon.innerHTML = icons.document;

        const history = card.querySelector('.border-emerald-100.bg-emerald-50\\/60 > p:first-child');
        if (history) history.textContent = 'Đã gửi gần nhất';
    });
});
</script>
