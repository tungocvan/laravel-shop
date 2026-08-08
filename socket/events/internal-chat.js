/**
 * =========================================
 * INTERNAL CHAT MODULE (ADMIN / DM)
 * =========================================
 * ⚠️ KHÔNG dùng io.on("connection") ở đây
 * 👉 socket được inject từ server.js
 */

const onlineAdmins = new Map();

module.exports = (socket, io) => {

    console.log("🧩 [INTERNAL] ATTACHED:", socket.id);

    /**
     * =========================================
     * ADMIN ONLINE
     * =========================================
     */
    socket.on("admin-online", (data = {}) => {
        try {
            const userId = data.user_id;

            if (!userId) {
                console.warn("⚠️ [ADMIN ONLINE] Missing user_id");
                return;
            }

            onlineAdmins.set(userId, socket.id);

            console.log("🟢 ADMIN ONLINE:", userId, "| Socket:", socket.id);

            io.emit("online-admins", [...onlineAdmins.keys()]);
        } catch (err) {
            console.error("❌ [ADMIN ONLINE ERROR]:", err);
        }
    });

    /**
     * =========================================
     * JOIN DM ROOM
     * =========================================
     */
    socket.on("join-dm-room", (data = {}) => {
        try {
            const roomRaw = data.room;

            if (!roomRaw) {
                console.warn("⚠️ [JOIN DM] Missing room");
                return;
            }

            const room = String(roomRaw).trim();

            /**
             * LEAVE OLD ROOM
             */
            if (socket.data.room) {
                socket.leave(socket.data.room);

                console.log(
                    `🚪 [LEAVE] ${socket.id} <- ${socket.data.room}`
                );
            }

            /**
             * JOIN NEW ROOM
             */
            socket.join(room);
            socket.data.room = room;

            console.log(
                `🚪 [JOINED] ${socket.id} -> ${room}`
            );

        } catch (err) {
            console.error("❌ [JOIN DM ERROR]:", err);
        }
    });

    /**
     * =========================================
     * DEBUG: LIST ROOMS
     * =========================================
     */
    socket.on("debug-rooms", () => {
        console.log("📦 SOCKET ROOMS:", socket.id, [...socket.rooms]);
    });

    /**
     * =========================================
     * DISCONNECT
     * =========================================
     */
    socket.on("disconnect", (reason) => {
        try {
            console.log(
                `❌ [INTERNAL DISCONNECT] ${socket.id} | Reason: ${reason}`
            );

            /**
             * REMOVE ADMIN ONLINE
             */
            for (const [userId, socketId] of onlineAdmins.entries()) {
                if (socketId === socket.id) {
                    onlineAdmins.delete(userId);

                    console.log("🔴 ADMIN OFFLINE:", userId);
                }
            }

            io.emit("online-admins", [...onlineAdmins.keys()]);

        } catch (err) {
            console.error("❌ [DISCONNECT ERROR]:", err);
        }
    });
};