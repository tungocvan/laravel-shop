module.exports = (socket, io) => {
    const getRoom = (id) => `session-${id}`;

    let typingTimeout = null;

    /**
     * =========================================
     * JOIN SESSION
     * =========================================
     */
    socket.on("join-session", (sessionId) => {
        if (!sessionId) return;

        if (socket.data.sessionId === sessionId) return;

        // leave old
        if (socket.data.sessionId) {
            socket.leave(getRoom(socket.data.sessionId));
        }

        socket.join(getRoom(sessionId));
        socket.data.sessionId = sessionId;

        socket.emit("session-joined", { session_id: sessionId });
    });

    /**
     * =========================================
     * LEAVE
     * =========================================
     */
    socket.on("leave-session", (sessionId) => {
        if (!sessionId) return;

        socket.leave(getRoom(sessionId));

        if (socket.data.sessionId === sessionId) {
            socket.data.sessionId = null;
        }
    });

    /**
     * =========================================
     * TYPING (THROTTLE)
     * =========================================
     */
    socket.on("typing", (data = {}) => {
        const sessionId = data.session_id || socket.data.sessionId;
        if (!sessionId) return;

        const room = getRoom(sessionId);

        socket.broadcast.to(room).emit("display-typing", {
            user_id: socket.user?.id || null,
        });

        clearTimeout(typingTimeout);

        typingTimeout = setTimeout(() => {
            socket.broadcast.to(room).emit("hide-typing", {});
        }, 2000);
    });

    /**
     * =========================================
     * MESSAGE DELIVERED
     * =========================================
     */
    socket.on("message-delivered", (data = {}) => {
        const sessionId = data.session_id || socket.data.sessionId;
        if (!sessionId) return;

        socket.broadcast
            .to(getRoom(sessionId))
            .emit("message-delivered", data);
    });

    /**
     * =========================================
     * DISCONNECT
     * =========================================
     */
    socket.on("disconnect", () => {
        socket.data.sessionId = null;
    });
};