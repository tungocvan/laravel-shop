require("dotenv").config();

const express = require("express");
const { createServer } = require("http");
const { Server } = require("socket.io");

if (!process.env.BRIDGE_SECRET_KEY) {
    throw new Error("BRIDGE_SECRET_KEY is required");
}

/**
 * =========================================
 * APP INIT
 * =========================================
 */
const app = express();
app.use(express.json());

const httpServer = createServer(app);

/**
 * =========================================
 * SOCKET.IO INIT
 * =========================================
 */
const io = new Server(httpServer, {
    cors: {
        origin: process.env.APP_URL,
        methods: ["GET", "POST"],
    },
    transports: ["polling", "websocket"],
});

console.log("🚀 Node Realtime Server starting...");

/**
 * =========================================
 * AUTH MIDDLEWARE (Laravel -> Node)
 * =========================================
 */
const bridgeAuth = (req, res, next) => {
    const secret = req.headers["x-bridge-secret"];

    if (secret !== process.env.BRIDGE_SECRET_KEY) {
        console.warn("❌ Unauthorized bridge request");
        return res.status(401).json({
            success: false,
            error: "Unauthorized",
        });
    }

    next();
};

/**
 * =========================================
 * LOAD MODULES (NO connection here)
 * =========================================
 */
const chatModule = require("./events/chat");
const internalChatModule = require("./events/internal-chat");

/**
 * =========================================
 * SOCKET CONNECTION (ONLY ONE PLACE)
 * =========================================
 */
io.on("connection", (socket) => {
    console.log("🔵 SERVER SOCKET:", socket.id);
    console.log("👉 Total clients:", io.engine.clientsCount);

    /**
     * Attach modules
     */
    chatModule(socket, io, bridgeAuth, app);
    internalChatModule(socket, io);

    /**
     * DEBUG: LIST ALL ROOMS
     */
    socket.on("debug-socket", () => {
        console.log("📦 SOCKET INFO:", {
            id: socket.id,
            rooms: [...socket.rooms],
        });
    });

    /**
     * DISCONNECT
     */
    socket.on("disconnect", (reason) => {
        console.log(
            "❌ [DISCONNECTED]:",
            socket.id,
            "| Reason:",
            reason
        );

        console.log("👉 Total clients:", io.engine.clientsCount);
    });
});

/**
 * =========================================
 * HEALTH CHECK
 * =========================================
 */
app.get("/health", (req, res) => {
    res.json({
        status: "ok",
        service: "realtime-node",
        clients: io.engine.clientsCount,
        timestamp: new Date().toISOString(),
    });
});

app.post("/broadcast", bridgeAuth, (req, res) => {
    try {
        const { event, data = {}, channel = null } = req.body;

        if (!event) {
            return res.status(400).json({
                success: false,
                error: "Missing event",
            });
        }

        const sessionId =
            data.chat_session_id ||
            data.session_id ||
            null;

        const roomName =
            channel || (sessionId ? `session-${sessionId}` : null);

        if (roomName) {
            io.to(roomName).emit(event, data);
        } else {
            io.emit(event, data);
        }

        console.log("📥 [BRIDGE EVENT]:", event, roomName);

        res.json({ success: true });

    } catch (err) {
        console.error("❌ [BROADCAST ERROR]:", err);

        res.status(500).json({
            success: false,
            error: err.message,
        });
    }
});
/**
 * =========================================
 * START SERVER
 * =========================================
 */
const PORT = process.env.NODEJS_SERVER_PORT || 6001;

httpServer.listen(PORT, "0.0.0.0", () => {
    console.log(`🚀 Realtime Server running on port ${PORT}`);
});
