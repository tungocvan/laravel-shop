import { io } from 'socket.io-client';

if (window.APP_CONFIG?.realtime?.enabled) {
const debug = import.meta.env.DEV || import.meta.env.VITE_SOCKET_DEBUG === 'true';
const socketHost = window.APP_CONFIG?.realtime?.url
    || window.CHAT_CONFIG_HOST
    || import.meta.env.VITE_SOCKET_PUBLIC_URL
    || window.location.origin;

window.socket ??= io(socketHost, {
    path: '/socket.io',
    transports: ['polling'],
    upgrade: false,
    reconnection: true,
    reconnectionAttempts: 10,
    reconnectionDelay: 1000,
});

const socket = window.socket;
window.dispatchEvent(new CustomEvent('realtime:ready', { detail: { socket } }));
window.currentSessionId ??= null;

socket.on('connect', () => {
    if (debug) console.info('Socket connected', socket.id);
    if (window.currentSessionId) socket.emit('join-session', window.currentSessionId);
});

socket.on('connect_error', (error) => {
    if (debug) console.warn('Socket connection failed', error.message);
});

window.joinSession = (id) => {
    if (!id) return;
    window.currentSessionId = id;
    if (socket.connected) socket.emit('join-session', id);
};

window.leaveSession = (id) => {
    if (!id) return;
    socket.emit('leave-session', id);
    if (window.currentSessionId === id) window.currentSessionId = null;
};
}
