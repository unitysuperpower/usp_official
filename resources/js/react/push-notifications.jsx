import React, { useEffect, useState } from "react";
import { api } from "./ui";

export default function PushNotifications() {
    const supported =
        window.isSecureContext &&
        "serviceWorker" in navigator &&
        "PushManager" in window &&
        "Notification" in window;
    const [enabled, setEnabled] = useState(false);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState("");
    useEffect(() => {
        if (!supported) return;
        let active = true;
        navigator.serviceWorker
            .getRegistration("/chat-worker.js")
            .then(async (registration) => {
                const subscription =
                    await registration?.pushManager.getSubscription();
                if (subscription && Notification.permission === "granted") {
                    // Reassociate only this authenticated account after its session changes.
                    await api("/notifications/push", {
                        method: "POST",
                        body: JSON.stringify(subscription.toJSON()),
                    });
                    if (active) setEnabled(true);
                }
            })
            .catch(() => {});
        return () => {
            active = false;
        };
    }, [supported]);
    async function toggle() {
        setBusy(true);
        setError("");
        try {
            if (enabled) {
                const registration =
                    await navigator.serviceWorker.getRegistration(
                        "/chat-worker.js",
                    );
                const subscription =
                    await registration?.pushManager.getSubscription();
                if (subscription) {
                    await api("/notifications/push", {
                        method: "DELETE",
                        body: JSON.stringify({
                            endpoint: subscription.endpoint,
                        }),
                    });
                    await subscription.unsubscribe();
                }
                setEnabled(false);
                return;
            }
            const permission = await Notification.requestPermission();
            if (permission !== "granted")
                throw new Error(
                    permission === "denied"
                        ? "Notifications are blocked. Allow them in your browser’s site settings."
                        : "Notifications were not enabled. You can try again anytime.",
                );
            const config = await api("/notifications/push");
            if (!config.public_key)
                throw new Error(
                    "Push notifications are not available yet. You can still check messages here.",
                );
            await navigator.serviceWorker.register("/chat-worker.js", {
                scope: "/",
            });
            const registration = await navigator.serviceWorker.ready;
            const encoded = config.public_key
                .replace(/-/g, "+")
                .replace(/_/g, "/");
            const key = Uint8Array.from(atob(encoded), (character) =>
                character.charCodeAt(0),
            );
            const subscription =
                (await registration.pushManager.getSubscription()) ||
                (await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: key,
                }));
            await api("/notifications/push", {
                method: "POST",
                body: JSON.stringify(subscription.toJSON()),
            });
            setEnabled(true);
        } catch (e) {
            setError(e.message);
        } finally {
            setBusy(false);
        }
    }
    return (
        <div className="chat-notifications">
            <span aria-hidden="true">♧</span>
            <div>
                <strong>
                    {enabled
                        ? "Push notifications are on"
                        : "Don’t miss a reply"}
                </strong>
                {supported ? (
                    <button type="button" onClick={toggle} disabled={busy}>
                        {busy
                            ? "Updating…"
                            : enabled
                              ? "Turn off on this browser"
                              : "Enable push notifications"}
                    </button>
                ) : (
                    <small>
                        Browser notifications aren’t available here. Messages
                        still appear in chat.
                    </small>
                )}
                {error && <p role="alert">{error}</p>}
            </div>
        </div>
    );
}
