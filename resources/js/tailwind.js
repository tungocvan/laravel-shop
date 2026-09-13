import './realtime/socket-client';
import Sortable from 'sortablejs';

// Admin menu tree owns SortableJS through the Vite bundle. The Blade component
// intentionally consumes this explicit bridge instead of depending on a CDN or
// an undeclared browser global.
window.Sortable = Sortable;
