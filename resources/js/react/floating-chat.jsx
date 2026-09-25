import React, { useEffect, useRef, useState } from "react";
import { api, context } from "./ui";
import PushNotifications from "./push-notifications";

function ChatIcon() {
    return (
        <svg
            width="23"
            height="23"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.7"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
        >
            <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z" />
            <path d="M8 11h8M8 14h5" />
        </svg>
    );
}

export default function FloatingChat() {
    const user = context.user;
    const admin = Boolean(user?.is_admin);
    const [open, setOpen] = useState(false);
    const [selected, setSelected] = useState(null);
    const [data, setData] = useState({
        messages: [],
        conversations: [],
        conversation: null,
        unread_count: 0,
    });
    const [draft, setDraft] = useState("");
    const [busy, setBusy] = useState(false);
    const [loading, setLoading] = useState(Boolean(user));
    const [error, setError] = useState("");
    const [toast, setToast] = useState(false);
    const [revision, setRevision] = useState(0);
    const latest = useRef(null);
    const lastRead = useRef("");
    const trigger = useRef(null);
    const panel = useRef(null);
    const messageList = useRef(null);

    useEffect(() => {
        if (!user) return;
        let alive = true;
        let timer;
        const controller = new AbortController();
        async function refresh() {
            try {
                const snapshot = await api(
                    `/chat/widget${selected ? `?conversation_id=${selected}` : ""}`,
                    {
                        signal: controller.signal,
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    },
                );
                if (!alive) return;
                const key = `usp-chat-latest-${user.id}`;
                if (latest.current === null) {
                    try {
                        latest.current =
                            Number(sessionStorage.getItem(key)) ||
                            snapshot.latest_incoming_id;
                    } catch {
                        latest.current = snapshot.latest_incoming_id;
                    }
                }
                if (
                    snapshot.latest_incoming_id > latest.current &&
                    snapshot.unread_count > 0 &&
                    !open
                )
                    setToast(true);
                latest.current = snapshot.latest_incoming_id;
                try {
                    sessionStorage.setItem(key, String(latest.current));
                } catch {}
                setData(snapshot);
                setError("");
                setLoading(false);
                const last = snapshot.messages.at(-1);
                const marker = `${snapshot.conversation?.id}:${last?.id}`;
                if (
                    open &&
                    document.visibilityState === "visible" &&
                    last &&
                    marker !== lastRead.current
                ) {
                    await api("/chat/widget/read", {
                        method: "POST",
                        body: JSON.stringify({
                            conversation_id: snapshot.conversation.id,
                            through_id: last.id,
                        }),
                        signal: controller.signal,
                    });
                    lastRead.current = marker;
                }
            } catch (e) {
                if (alive) {
                    setError("We couldn’t refresh chat. Retrying shortly.");
                    setLoading(false);
                }
            } finally {
                if (alive) timer = setTimeout(refresh, open ? 4000 : 12000);
            }
        }
        refresh();
        return () => {
            alive = false;
            controller.abort();
            clearTimeout(timer);
        };
    }, [user?.id, open, selected, revision]);

    useEffect(() => {
        if (!open) return;
        const frame = requestAnimationFrame(() => panel.current?.focus());
        function escape(e) {
            if (e.key === "Escape") {
                setOpen(false);
                trigger.current?.focus();
            }
        }
        document.addEventListener("keydown", escape);
        return () => {
            cancelAnimationFrame(frame);
            document.removeEventListener("keydown", escape);
        };
    }, [open]);
    useEffect(() => {
        if (messageList.current)
            messageList.current.scrollTop = messageList.current.scrollHeight;
    }, [open, data.messages.at(-1)?.id]);
    useEffect(() => {
        if (!toast) return;
        const timer = setTimeout(() => setToast(false), 8000);
        return () => clearTimeout(timer);
    }, [toast]);

    async function send(e) {
        e.preventDefault();
        if (!draft.trim() || busy) return;
        setBusy(true);
        setError("");
        try {
            let id = selected || data.conversation?.id;
            if (!id && !admin) {
                const started = await api("/chat/widget/start", {
                    method: "POST",
                    body: "{}",
                });
                id = started.conversation_id;
                setSelected(id);
            }
            if (!id) throw new Error("Choose a conversation first.");
            const result = await api(
                admin ? "/admin/chat/send" : "/chat/send",
                {
                    method: "POST",
                    body: JSON.stringify({
                        conversation_id: id,
                        message: draft.trim(),
                    }),
                },
            );
            setData((previous) => ({
                ...previous,
                messages: previous.messages.some(
                    (m) => m.id === result.message.id,
                )
                    ? previous.messages
                    : [...previous.messages, result.message],
            }));
            setDraft("");
            setRevision((value) => value + 1);
        } catch (e) {
            setError(e.message);
        } finally {
            setBusy(false);
        }
    }
    function show() {
        setOpen(!open);
        setToast(false);
    }
    const inbox = admin && !selected;
    return (
        <div className="floating-chat">
            {toast && !open && (
                <div className="chat-toast" role="status">
                    <button
                        type="button"
                        onClick={() => {
                            setOpen(true);
                            setToast(false);
                        }}
                    >
                        <ChatIcon />
                        <span>
                            <strong>New chat message</strong>
                            <small>
                                {admin
                                    ? "A customer is waiting for a reply."
                                    : "Your support team has replied."}
                            </small>
                        </span>
                    </button>
                    <button
                        type="button"
                        aria-label="Dismiss notification"
                        onClick={() => setToast(false)}
                    >
                        ×
                    </button>
                </div>
            )}
            {open && (
                <section
                    className="chat-widget"
                    ref={panel}
                    tabIndex={-1}
                    role="dialog"
                    aria-modal="false"
                    aria-labelledby="chat-widget-title"
                >
                    <header className="chat-widget-header">
                        <div className="chat-widget-brand">
                            <ChatIcon />
                        </div>
                        <div>
                            <h2 id="chat-widget-title">
                                {admin ? "Your conversations" : "USP Support"}
                            </h2>
                            <p>
                                {admin
                                    ? "Every conversation matters"
                                    : "A little help for your next big idea"}
                            </p>
                        </div>
                        <button
                            type="button"
                            aria-label="Minimize chat"
                            onClick={() => {
                                setOpen(false);
                                trigger.current?.focus();
                            }}
                        >
                            −
                        </button>
                    </header>
                    {!user ? (
                        <div className="chat-widget-welcome">
                            <span className="chat-welcome-icon">✳</span>
                            <h3>
                                Let’s talk about
                                <br />
                                what’s next.
                            </h3>
                            <p>
                                Have a question or a project in mind? Sign in to
                                chat with our team and keep your conversation in
                                one place.
                            </p>
                            <a className="button" href="/login">
                                Sign in to chat ↗
                            </a>
                            <a className="text-link" href="/contact">
                                Prefer to leave a message? →
                            </a>
                        </div>
                    ) : (
                        <>
                            <PushNotifications />
                            {admin && selected && (
                                <div className="chat-widget-back">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setSelected(null);
                                            setDraft("");
                                        }}
                                    >
                                        ← All conversations
                                    </button>
                                    <a href={`/admin/chat/${selected}`}>
                                        Open full chat ↗
                                    </a>
                                </div>
                            )}
                            {error && (
                                <p className="chat-widget-error" role="alert">
                                    {error}
                                </p>
                            )}
                            {inbox ? (
                                <div className="chat-widget-inbox">
                                    {loading ? (
                                        <p className="chat-widget-empty">
                                            Loading conversations…
                                        </p>
                                    ) : data.conversations.length ? (
                                        data.conversations.map((c) => (
                                            <button
                                                key={c.id}
                                                type="button"
                                                onClick={() => {
                                                    setData((previous) => ({
                                                        ...previous,
                                                        messages: [],
                                                    }));
                                                    setSelected(c.id);
                                                }}
                                            >
                                                <span className="chat-contact-avatar">
                                                    {c.user?.name?.slice(
                                                        0,
                                                        1,
                                                    ) || "C"}
                                                </span>
                                                <span>
                                                    <strong>
                                                        {c.user?.name ||
                                                            "Customer"}
                                                    </strong>
                                                    <small>
                                                        {c.subject ||
                                                            "Support chat"}
                                                    </small>
                                                </span>
                                                {c.unread_count > 0 && (
                                                    <b>{c.unread_count}</b>
                                                )}
                                                <span aria-hidden="true">
                                                    →
                                                </span>
                                            </button>
                                        ))
                                    ) : (
                                        <p className="chat-widget-empty">
                                            New customer conversations will
                                            appear here.
                                        </p>
                                    )}
                                    <a
                                        className="chat-widget-full"
                                        href="/admin/chat"
                                    >
                                        View all conversations ↗
                                    </a>
                                </div>
                            ) : (
                                <>
                                    <div
                                        className="chat-widget-messages"
                                        ref={messageList}
                                        role="log"
                                        aria-live="polite"
                                        aria-label="Support messages"
                                    >
                                        {loading ? (
                                            <p className="chat-widget-empty">
                                                Loading your conversation…
                                            </p>
                                        ) : data.messages.length ? (
                                            data.messages.map((m) => (
                                                <article
                                                    className={`chat-widget-bubble ${m.user_id === user.id ? "own" : ""}`}
                                                    key={m.id}
                                                >
                                                    <strong>
                                                        {m.user?.name ||
                                                            (m.is_admin
                                                                ? "USP Support"
                                                                : "Customer")}
                                                    </strong>
                                                    <p>{m.message}</p>
                                                    <time
                                                        dateTime={m.created_at}
                                                    >
                                                        {new Date(
                                                            m.created_at,
                                                        ).toLocaleTimeString(
                                                            [],
                                                            {
                                                                hour: "2-digit",
                                                                minute: "2-digit",
                                                            },
                                                        )}
                                                    </time>
                                                </article>
                                            ))
                                        ) : (
                                            <div className="chat-widget-empty">
                                                <span>✳</span>
                                                <h3>
                                                    A conversation starts with
                                                    hello.
                                                </h3>
                                                <p>
                                                    Tell us how we can help. Our
                                                    team will reply here.
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                    <form
                                        className="chat-widget-compose"
                                        onSubmit={send}
                                    >
                                        <label>
                                            <span className="sr-only">
                                                Chat message
                                            </span>
                                            <textarea
                                                value={draft}
                                                onChange={(e) =>
                                                    setDraft(e.target.value)
                                                }
                                                required
                                                maxLength={1000}
                                                rows={2}
                                                placeholder="Write your message…"
                                            />
                                        </label>
                                        <button
                                            type="submit"
                                            disabled={busy || !draft.trim()}
                                            aria-label={
                                                busy
                                                    ? "Sending message"
                                                    : "Send chat message"
                                            }
                                        >
                                            {busy ? "…" : "↗"}
                                        </button>
                                    </form>
                                    <div className="chat-widget-footnote">
                                        <span>
                                            Your conversation, all in one place.
                                        </span>
                                        <a
                                            href={
                                                admin
                                                    ? `/admin/chat/${selected}`
                                                    : "/chat"
                                            }
                                        >
                                            Full chat ↗
                                        </a>
                                    </div>
                                </>
                            )}
                        </>
                    )}
                </section>
            )}
            <button
                ref={trigger}
                className={`chat-launcher ${open ? "is-open" : ""}`}
                type="button"
                aria-expanded={open}
                aria-label={`${open ? "Close" : "Open"} support chat${data.unread_count ? `, ${data.unread_count} unread messages` : ""}`}
                onClick={show}
            >
                {open ? (
                    <span className="chat-launcher-close">×</span>
                ) : (
                    <ChatIcon />
                )}
                <span>
                    {open
                        ? "Close chat"
                        : admin
                          ? "Conversations"
                          : "Let’s chat"}
                </span>
                {data.unread_count > 0 && !open && (
                    <b className="chat-unread">
                        {data.unread_count > 99 ? "99+" : data.unread_count}
                    </b>
                )}
            </button>
        </div>
    );
}
