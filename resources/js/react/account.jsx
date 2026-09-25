import React, { useEffect, useState } from "react";
import {
    context,
    rows,
    date,
    Heading,
    Field,
    Form,
    Badge,
    Stats,
    Table,
    Pagination,
    Empty,
    api,
    Html,
} from "./ui";
export function Auth(p) {
    const mode = context.page.split(".").pop();
    const [recovery, setRecovery] = useState(false);
    const configs = {
        login: [
            "Welcome back.",
            "Sign in to your USP workspace.",
            "/login",
            "Sign in",
        ],
        register: [
            "Your next chapter.",
            "Create an account and let’s build something great.",
            "/register",
            "Create account",
        ],
        "forgot-password": [
            "A fresh start.",
            "We’ll send you a link to reset your password.",
            "/forgot-password",
            "Send reset link",
        ],
        "reset-password": [
            "Reset your password.",
            "Choose a new password for your account.",
            "/reset-password",
            "Reset password",
        ],
        "confirm-password": [
            "One quick check.",
            "Confirm your password to continue.",
            "/user/confirm-password",
            "Confirm password",
        ],
        "two-factor-challenge": [
            "A little extra security.",
            "Use your authenticator app or a recovery code.",
            "/two-factor-challenge",
            "Verify",
        ],
        "verify-email": [
            "Check your inbox.",
            "Verify your email address using the link we sent you.",
            "/email/verification-notification",
            "Resend verification email",
        ],
    };
    const [title, description, action, submit] = configs[mode];
    return (
        <section className="auth-layout">
            <div className="auth-art">
                <span className="eyebrow">USP TECH SOLUTION</span>
                <h2>
                    Good things
                    <br />
                    start with
                    <br />
                    <em>connection.</em>
                </h2>
                <span className="auth-star" aria-hidden="true">
                    ✳
                </span>
                <p>A space for your ideas, projects, and possibilities.</p>
            </div>
            <div className="auth-form">
                <Heading title={title} description={description} />
                <Form action={action} submit={submit}>
                    {mode === "register" && (
                        <Field
                            name="name"
                            title="Full name"
                            required
                            autoComplete="name"
                        />
                    )}
                    {[
                        "login",
                        "register",
                        "forgot-password",
                        "reset-password",
                    ].includes(mode) && (
                        <Field
                            name="email"
                            type="email"
                            title="Email address"
                            value={p.email}
                            required
                            autoComplete="email"
                        />
                    )}
                    {[
                        "login",
                        "register",
                        "reset-password",
                        "confirm-password",
                    ].includes(mode) && (
                        <Field
                            name="password"
                            type="password"
                            title="Password"
                            required
                            autoComplete={
                                ["register", "reset-password"].includes(mode)
                                    ? "new-password"
                                    : "current-password"
                            }
                        />
                    )}{" "}
                    {["register", "reset-password"].includes(mode) && (
                        <Field
                            name="password_confirmation"
                            type="password"
                            title="Confirm password"
                            required
                            autoComplete="new-password"
                        />
                    )}
                    {mode === "reset-password" && (
                        <input type="hidden" name="token" value={p.token} />
                    )}{" "}
                    {mode === "login" && (
                        <div className="panel-heading">
                            <Field
                                name="remember"
                                type="checkbox"
                                title="Remember me"
                            />
                            <a className="text-link" href="/forgot-password">
                                Forgot password?
                            </a>
                        </div>
                    )}
                    {mode === "two-factor-challenge" && (
                        <>
                            <Field
                                key={String(recovery)}
                                name={recovery ? "recovery_code" : "code"}
                                title={
                                    recovery
                                        ? "Recovery code"
                                        : "Authentication code"
                                }
                                required
                                autoComplete="one-time-code"
                            />
                            <button
                                className="text-link"
                                type="button"
                                onClick={() => setRecovery(!recovery)}
                            >
                                Use{" "}
                                {recovery ? "authenticator" : "recovery code"}
                            </button>
                        </>
                    )}
                </Form>
                {mode === "login" && (
                    <p>
                        New here?{" "}
                        <a className="text-link" href="/register">
                            Create an account ↗
                        </a>
                    </p>
                )}
                {mode === "register" && (
                    <p>
                        Already have an account?{" "}
                        <a className="text-link" href="/login">
                            Sign in ↗
                        </a>
                    </p>
                )}
            </div>
        </section>
    );
}
export function Profile() {
    const prefix = context.user.is_admin ? "/admin/profile" : "/profile";
    return (
        <section className="section">
            <Heading
                eyebrow="YOUR ACCOUNT"
                title="Make yourself at home."
                description="Keep your details up to date and your account secure."
            />
            <div className="split-panels">
                <Form action={prefix} method="PATCH" className="panel">
                    <h2>Personal details</h2>
                    <Field
                        name="name"
                        value={context.user.name}
                        required
                        autoComplete="name"
                    />
                    <Field
                        name="email"
                        type="email"
                        value={context.user.email}
                        required
                        autoComplete="email"
                    />
                </Form>
                <Form
                    action={`${prefix}/password`}
                    method="PATCH"
                    className="panel"
                    submit="Update password"
                >
                    <h2>Password</h2>
                    <Field
                        name="current_password"
                        title="Current password"
                        type="password"
                        required
                        autoComplete="current-password"
                    />
                    <Field
                        name="password"
                        title="New password"
                        type="password"
                        required
                        minLength={8}
                        autoComplete="new-password"
                    />
                    <Field
                        name="password_confirmation"
                        title="Confirm new password"
                        type="password"
                        required
                        autoComplete="new-password"
                    />
                </Form>
            </div>
            <div className="quick-links">
                <a href="/settings/two-factor">
                    <h3>Two-factor authentication ↗</h3>
                    <p>Add an extra layer of security.</p>
                </a>
                <a href="/settings/appearance">
                    <h3>Your appearance preferences ↗</h3>
                    <p>Choose a light or dark workspace.</p>
                </a>
            </div>
            <Form
                action="/profile"
                method="DELETE"
                className="panel danger"
                submit="Delete my account"
                confirm="Permanently delete your account? This cannot be undone."
            >
                <h2>Delete account</h2>
                <p>Enter your password to permanently delete your account.</p>
                <Field
                    name="password"
                    title="Confirm your password"
                    type="password"
                    required
                    autoComplete="current-password"
                />
            </Form>
        </section>
    );
}
export function Appearance() {
    const [theme, setTheme] = useState(
        localStorage.getItem("usp-theme") || "light",
    );
    function update(value) {
        setTheme(value);
        localStorage.setItem("usp-theme", value);
        document.documentElement.dataset.theme = value;
    }
    return (
        <section className="section">
            <Heading title="Your space, your style." />
            <div className="panel">
                <fieldset>
                    <legend>Color theme</legend>
                    {["light", "dark"].map((t) => (
                        <label className="check-field" key={t}>
                            <input
                                type="radio"
                                name="theme"
                                checked={theme === t}
                                onChange={() => update(t)}
                            />
                            {t === "light" ? "Light" : "Dark"}
                        </label>
                    ))}
                </fieldset>
            </div>
        </section>
    );
}
export function Security({ enabled, requiresConfirmation }) {
    const [active, setActive] = useState(enabled);
    const [setup, setSetup] = useState(null);
    const [codes, setCodes] = useState([]);
    const [error, setError] = useState("");
    const [busy, setBusy] = useState(false);
    async function run(fn) {
        setBusy(true);
        setError("");
        try {
            await fn();
        } catch (e) {
            setError(e.message);
        } finally {
            setBusy(false);
        }
    }
    async function enable() {
        await api("/user/two-factor-authentication", { method: "POST" });
        const [qr, key] = await Promise.all([
            api("/user/two-factor-qr-code"),
            api("/user/two-factor-secret-key"),
        ]);
        setSetup({ svg: qr.svg, key: key.secretKey });
        if (!requiresConfirmation) setActive(true);
    }
    return (
        <section className="section">
            <Heading
                title="A little extra security."
                description="Protect your account with your authenticator app."
            />
            <div className="panel security">
                <Badge>{active ? "enabled" : "disabled"}</Badge>
                {error && (
                    <p role="alert" className="error">
                        {error}
                    </p>
                )}
                {!active && !setup && (
                    <button
                        className="button"
                        disabled={busy}
                        onClick={() => run(enable)}
                    >
                        Enable two-factor authentication
                    </button>
                )}
                {setup && (
                    <>
                        <div className="qr">
                            <Html value={setup.svg} />
                        </div>
                        <p>
                            Scan this QR code, or enter this setup key in your
                            authenticator:
                        </p>
                        <code>{setup.key}</code>
                        {requiresConfirmation && !active && (
                            <form
                                className="form"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    const code = new FormData(
                                        e.currentTarget,
                                    ).get("code");
                                    run(async () => {
                                        await api(
                                            "/user/confirmed-two-factor-authentication",
                                            {
                                                method: "POST",
                                                body: JSON.stringify({ code }),
                                            },
                                        );
                                        setActive(true);
                                        setSetup(null);
                                    });
                                }}
                            >
                                <Field
                                    name="code"
                                    title="Authentication code"
                                    required
                                    pattern="[0-9]{6}"
                                    autoComplete="one-time-code"
                                />
                                <button className="button" disabled={busy}>
                                    Confirm
                                </button>
                            </form>
                        )}
                    </>
                )}
                {active && (
                    <>
                        <p>
                            Keep your recovery codes in a safe place. Each code
                            can be used once.
                        </p>
                        <div className="actions">
                            <button
                                className="button secondary"
                                disabled={busy}
                                onClick={() =>
                                    run(async () =>
                                        setCodes(
                                            await api(
                                                "/user/two-factor-recovery-codes",
                                            ),
                                        ),
                                    )
                                }
                            >
                                Show recovery codes
                            </button>
                            <button
                                className="button secondary"
                                disabled={busy}
                                onClick={() =>
                                    run(async () => {
                                        await api(
                                            "/user/two-factor-recovery-codes",
                                            { method: "POST" },
                                        );
                                        setCodes(
                                            await api(
                                                "/user/two-factor-recovery-codes",
                                            ),
                                        );
                                    })
                                }
                            >
                                Regenerate codes
                            </button>
                        </div>
                        {codes.length > 0 && <pre>{codes.join("\n")}</pre>}
                        <button
                            className="button secondary"
                            disabled={busy}
                            onClick={() =>
                                run(async () => {
                                    await api(
                                        "/user/two-factor-authentication",
                                        { method: "DELETE" },
                                    );
                                    setActive(false);
                                    setSetup(null);
                                    setCodes([]);
                                })
                            }
                        >
                            Disable two-factor authentication
                        </button>
                    </>
                )}
            </div>
        </section>
    );
}
export function ChatList({ conversations, totalUnread, activeConversations }) {
    return (
        <>
            <Heading
                title="Conversations"
                description="Be there when your customers need you."
            />
            <Stats
                stats={{
                    unread_messages: totalUnread,
                    active_conversations: activeConversations,
                }}
            />
            <div className="panel">
                <Table
                    data={conversations}
                    columns={[
                        {
                            title: "Customer",
                            render: (r) => (
                                <a
                                    className="table-title"
                                    href={`/admin/chat/${r.id}`}
                                >
                                    {r.user?.name || "Customer"}
                                </a>
                            ),
                        },
                        {
                            title: "Last message",
                            render: (r) =>
                                r.latest_message?.message || "No messages yet",
                        },
                        {
                            title: "Status",
                            render: (r) => <Badge>{r.status}</Badge>,
                        },
                        { title: "Unread", key: "unread_count" },
                        {
                            title: "Updated",
                            render: (r) => date(r.last_message_at),
                        },
                    ]}
                />
                <Pagination data={conversations} />
            </div>
        </>
    );
}
export function Chat({ conversation }) {
    const admin = context.page.startsWith("admin.");
    const base = admin ? "/admin/chat" : "/chat";
    const [messages, setMessages] = useState(conversation.messages || []);
    const [error, setError] = useState("");
    const [text, setText] = useState("");
    const [busy, setBusy] = useState(false);
    useEffect(() => {
        let alive = true;
        let timer;
        const controller = new AbortController();
        async function refresh() {
            try {
                const data = await api(`${base}/${conversation.id}/messages`, {
                    signal: controller.signal,
                });
                if (alive) {
                    setMessages(data.messages);
                    setError("");
                }
            } catch (e) {
                if (alive)
                    setError("Unable to refresh messages. Retrying shortly.");
            } finally {
                if (alive) timer = setTimeout(refresh, 5000);
            }
        }
        refresh();
        return () => {
            alive = false;
            controller.abort();
            clearTimeout(timer);
        };
    }, [conversation.id, base]);
    async function send(e) {
        e.preventDefault();
        if (!text.trim()) return;
        setBusy(true);
        setError("");
        try {
            const result = await api(`${base}/send`, {
                method: "POST",
                body: JSON.stringify({
                    conversation_id: conversation.id,
                    message: text,
                }),
            });
            setMessages((prev) =>
                prev.some((m) => m.id === result.message.id)
                    ? prev
                    : [...prev, result.message],
            );
            setText("");
        } catch (e) {
            setError(e.message);
        } finally {
            setBusy(false);
        }
    }
    return (
        <section className={admin ? "" : "section"}>
            <Heading
                title={
                    admin
                        ? `Chat with ${conversation.user?.name || "customer"}`
                        : "Let’s keep talking."
                }
                description="Your conversation with USP Tech Solution."
            />
            {admin && (
                <Form
                    action={`${base}/${conversation.id}/status`}
                    method="PATCH"
                    className="search-form"
                    submit="Update status"
                >
                    <Field
                        name="status"
                        type="select"
                        value={conversation.status}
                        options={["active", "closed", "archived"]}
                    />
                </Form>
            )}
            <div className="panel chat">
                <div
                    className="messages"
                    role="log"
                    aria-label="Chat messages"
                    aria-live="polite"
                >
                    {messages.length ? (
                        messages.map((m) => (
                            <article
                                className={`bubble ${m.user_id === context.user.id ? "own" : ""}`}
                                key={m.id}
                            >
                                <strong>
                                    {m.user?.name ||
                                        (m.is_admin ? "USP Support" : "You")}
                                </strong>
                                <p>{m.message}</p>
                                <small>{date(m.created_at)}</small>
                            </article>
                        ))
                    ) : (
                        <Empty
                            title="Say hello."
                            description="Share a question or tell us how we can help."
                        />
                    )}
                </div>
                {error && (
                    <p role="alert" className="error">
                        {error}
                    </p>
                )}
                <form className="chat-compose" onSubmit={send}>
                    <label className="field">
                        <span>Your message</span>
                        <textarea
                            value={text}
                            onChange={(e) => setText(e.target.value)}
                            rows={2}
                            required
                            maxLength={1000}
                            placeholder="Write a message…"
                        />
                    </label>
                    <button className="button" disabled={busy || !text.trim()}>
                        {busy ? "Sending…" : "Send ↗"}
                    </button>
                </form>
            </div>
        </section>
    );
}
