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

    /* Compact title row */
    .export-card > .flex.items-start.justify-between {
        align-items: center;
    }

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

    /* Metadata becomes one quiet app-like line instead of 3 dashboard boxes. */
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

    /* Delivery history: show recent delivery as supporting information, not another dashboard card. */
    .export-card .border-emerald-100.bg-emerald-50\/60 {
        padding: .7rem .8rem !important;
        border-radius: .9rem !important;
        background: rgb(248 250 252 / .9) !important;
        border-color: rgb(226 232 240) !important;
    }

    .export-card .border-emerald-100.bg-emerald-50\/60 > p:first-child {
        color: rgb(100 116 139) !important;
    }

    /* Main action rail */
    .export-card > .mt-4.flex.flex-wrap.items-center.gap-2 {
        display: grid !important;
        grid-template-columns: 2.75rem 2.75rem 2.75rem minmax(0, 1fr) 2.75rem;
        gap: .5rem !important;
        align-items: center;
    }

    /* Excel / PDF / Share become compact icon actions. Text stays in DOM for accessibility. */
    .export-card a[href*="/download"],
    .export-card a[href*="/pdf/download"],
    .export-card .share-export,
    .export-card form[action*="/pdf"] > button {
        position: relative;
        display: inline-flex !important;
        width: 2.75rem !important;
        min-width: 2.75rem !important;
        height: 2.75rem !important;
        min-height: 2.75rem !important;
        align-items: center !important;
        justify-content: center !important;
        overflow: hidden;
        border-radius: .9rem !important;
        padding: 0 !important;
        font-size: 0 !important;
    }

    .export-card a[href*="/download"]::before {
        content: 'X';
        display: grid;
        place-items: center;
        width: 1.45rem;
        height: 1.45rem;
        border-radius: .35rem;
        background: #16a34a;
        color: white;
        font: 800 .72rem/1 system-ui, sans-serif;
    }

    .export-card a[href*="/pdf/download"]::before,
    .export-card form[action*="/pdf"] > button::before {
        content: 'PDF';
        color: inherit;
        font: 800 .58rem/1 system-ui, sans-serif;
        letter-spacing: -.02em;
    }

    .export-card .share-export::before {
        content: '↗';
        color: rgb(15 23 42);
        font: 800 1.15rem/1 system-ui, sans-serif;
    }

    /* Email stays the main business CTA. */
    .export-card [data-email-open] {
        grid-column: 4;
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

    .export-card details.ml-auto {
        grid-column: 5;
        margin-left: 0 !important;
    }

    .export-card details.ml-auto > summary {
        width: 2.75rem !important;
        height: 2.75rem !important;
        border-radius: .9rem !important;
        background: white;
    }

    /* A pending PDF form still occupies the PDF action slot cleanly. */
    .export-card > .mt-4.flex.flex-wrap.items-center.gap-2 > form[action*="/pdf"] {
        width: 2.75rem;
    }

    /* Filters remain usable at 430px without overflowing. */
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
    document.querySelectorAll('.export-card').forEach((card) => {
        const excel = card.querySelector('a[href*="/download"]:not([href*="/pdf/"])');
        const pdf = card.querySelector('a[href*="/pdf/download"], form[action*="/pdf"] > button');
        const share = card.querySelector('.share-export');
        if (excel) excel.setAttribute('title', 'Tải Excel');
        if (pdf) pdf.setAttribute('title', pdf.closest('form') ? 'Tạo PDF' : 'Tải PDF');
        if (share) share.setAttribute('title', 'Chia sẻ');

        const history = card.querySelector('.border-emerald-100.bg-emerald-50\/60 > p:first-child');
        if (history) history.textContent = 'Đã gửi gần nhất';
    });
});
</script>
