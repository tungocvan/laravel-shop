document.addEventListener('alpine:init', () => {
    Alpine.store('toast', {
        items: [],
        limit: 3,
        duration: 0,
        dedupe: true,
        lastMessage: null,

        push(detail) {
            const message = detail.content || 'Success';
            const type = detail.type || 'success';
            console.log('detail:',detail);
            
            // DEDUPE
            if (this.dedupe && this.lastMessage === message) return;
            this.lastMessage = message;

            const toast = {
                id: Date.now() + Math.random(),
                message,
                type,
                action: detail.action ?? null,
                url: detail.url ?? null,
                confirm: detail.confirm ?? false,
                duration: detail.duration ?? this.duration,
                show: true
            };

            // LIMIT QUEUE
            if (this.items.length >= this.limit) {
                this.items.shift();
            }

            this.items.push(toast);

            if (!toast.confirm) {
                setTimeout(() => {
                    this.handleAction(toast);
                    this.remove(toast.id);
                }, toast.duration);
            }
        },

        remove(id) {
            const t = this.items.find(t => t.id === id);
            if (!t) return;

            t.show = false;

            setTimeout(() => {
                this.items = this.items.filter(i => i.id !== id);
            }, 200);
        },

        confirm(toast) {
            this.handleAction(toast);
            this.remove(toast.id);
        },

        handleAction(toast) {
            if (!toast.action) return;
            
            switch (toast.action) {
                case 'reload':
                    window.location.reload();
                    break;

                case 'refresh':
                    window.Livewire?.dispatch('refresh');
                    break;

                case 'redirect':
                    if (toast.url) window.location.href = toast.url;
                    break;
            }
        }
    });
});

/**
 * 🔥 QUAN TRỌNG: Bridge Livewire → Alpine
 */
window.addEventListener('notify', (e) => {
    if (!window.Alpine) return

    Alpine.store('toast').push(e.detail || {})
})