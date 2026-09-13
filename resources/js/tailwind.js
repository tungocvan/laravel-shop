import './realtime/socket-client';
import Sortable from 'sortablejs';

// Admin menu tree owns SortableJS through the Vite bundle. Use the fallback
// drag engine so handles rendered as interactive controls behave consistently
// across Chromium/Livewire layouts instead of relying on native HTML5 drag.
class AdminSortable extends Sortable {
    constructor(element, options = {}) {
        super(element, {
            forceFallback: true,
            fallbackOnBody: true,
            fallbackTolerance: 3,
            ...options,
        });
    }
}

window.Sortable = AdminSortable;
