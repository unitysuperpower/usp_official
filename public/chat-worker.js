/* No pages or authenticated responses are cached by this service worker. */
self.addEventListener("push", (event) => {
    let data = {};
    try {
        data = event.data?.json() || {};
    } catch {}
    event.waitUntil(
        self.registration.showNotification(data.title || "USP Tech Solution", {
            body: data.body || "You have a new chat message.",
            icon: "/favicon.png",
            badge: "/favicon.png",
            tag: data.tag || "usp-chat",
            data: { url: data.url || "/chat" },
        }),
    );
});
self.addEventListener("notificationclick", (event) => {
    event.notification.close();
    let url = new URL("/chat", self.location.origin);
    try {
        const target = new URL(
            event.notification.data?.url || "/chat",
            self.location.origin,
        );
        if (
            target.origin === self.location.origin &&
            (target.pathname === "/chat" ||
                /^\/admin\/chat\/\d+$/.test(target.pathname))
        )
            url = target;
    } catch {}
    event.waitUntil(
        self.clients
            .matchAll({ type: "window", includeUncontrolled: true })
            .then(async (clients) => {
                for (const client of clients) {
                    if (
                        new URL(client.url).origin === self.location.origin &&
                        "focus" in client
                    ) {
                        await client.navigate(url.href);
                        return client.focus();
                    }
                }
                return self.clients.openWindow(url.href);
            }),
    );
});
