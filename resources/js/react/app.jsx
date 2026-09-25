import React, { useState } from "react";
import { createRoot } from "react-dom/client";
import UserDropdown from "./user-dropdown";
import FloatingChat from "./floating-chat";
import { context, Form, Empty } from "./ui";
import { Home, Listing, Detail, Contact, Dashboard } from "./public";
import {
    AdminDashboard,
    ResourceList,
    ResourceEditor,
    Requests,
    Contacts,
    Analytics,
} from "./admin";
import { Auth, Profile, Appearance, Security, Chat, ChatList } from "./account";
import "../../css/react.css";
try {
    document.documentElement.dataset.theme =
        localStorage.getItem("usp-theme") || "light";
} catch {}
const admin = context.page.startsWith("admin.");
function Logo() {
    return (
        <a className="brand" href="/">
            <span className="brand-icon">
                u<span>.</span>
            </span>
            <span>
                USP<span className="brand-subtitle">TECH SOLUTION</span>
            </span>
        </a>
    );
}
const adminLinks = [
    ["dashboard", "Overview", "◈"],
    ["services", "Services", "⌘"],
    ["categories", "Service categories", "▦"],
    ["requests", "Service requests", "↗"],
    ["blogs", "Journal", "▤"],
    ["blog-categories", "Blog categories", "▦"],
    ["contact-messages", "Contact inbox", "✉"],
    ["chat", "Conversations", "◌"],
    ["analytics", "Analytics", "▥"],
    ["profile", "My profile", "○"],
];
function Layout({ children }) {
    const [open, setOpen] = useState(false);
    const [accountOpen, setAccountOpen] = useState(false);
    const accountDropdown = context.user && (
        <UserDropdown
            open={accountOpen}
            onToggle={() => {
                setAccountOpen(!accountOpen);
                setOpen(false);
            }}
            onClose={() => setAccountOpen(false)}
        />
    );
    return (
        <div className={admin ? "admin-shell" : "site-shell"}>
            <a className="skip-link" href="#main">
                Skip to content
            </a>
            {admin ? (
                <aside className={`sidebar ${open ? "is-open" : ""}`}>
                    <Logo />
                    <span className="nav-caption">WORKSPACE</span>
                    <nav aria-label="Admin navigation">
                        {adminLinks.map(([path, title, icon]) => (
                            <a
                                key={path}
                                href={`/admin/${path}`}
                                className={
                                    context.page.startsWith(`admin.${path}.`) ||
                                    context.page === `admin.${path}`
                                        ? "active"
                                        : ""
                                }
                            >
                                <span aria-hidden="true">{icon}</span>
                                {title}
                            </a>
                        ))}
                    </nav>
                    <a className="site-link" href="/">
                        View website ↗
                    </a>
                    <div className="sidebar-user">
                        <span className="avatar">
                            {context.user.name.slice(0, 1)}
                        </span>
                        <div>
                            <strong>{context.user.name}</strong>
                            <small>Administrator</small>
                        </div>
                    </div>
                    <Form
                        action="/logout"
                        submit="Sign out"
                        className="inline-form"
                    />
                </aside>
            ) : null}
            <FloatingChat />
            <div className="main-shell">
                <header className="topbar">
                    {admin ? (
                        <>
                            <span className="workspace-label">
                                USP workspace{" "}
                                <span>
                                    /{" "}
                                    {context.page
                                        .split(".")[1]
                                        .replaceAll("-", " ")}
                                </span>
                            </span>
                            {accountDropdown}
                        </>
                    ) : (
                        <>
                            <Logo />
                            <nav
                                className={
                                    open ? "public-nav is-open" : "public-nav"
                                }
                                id="public-nav"
                                aria-label="Main navigation"
                            >
                                {[
                                    ["/", "Home"],
                                    ["/services", "Services"],
                                    ["/#approach", "Our approach"],
                                    ["/blogs", "Insights"],
                                ].map(([href, title]) => (
                                    <a
                                        key={title}
                                        aria-current={
                                            location.pathname === href
                                                ? "page"
                                                : undefined
                                        }
                                        href={href}
                                    >
                                        {title}
                                    </a>
                                ))}
                                {context.user && (
                                    <a
                                        href={
                                            context.user.is_admin
                                                ? "/admin/dashboard"
                                                : "/dashboard"
                                        }
                                    >
                                        Dashboard
                                    </a>
                                )}
                                <a
                                    className="mobile-account"
                                    href={context.user ? "/profile" : "/login"}
                                >
                                    {context.user ? "My account" : "Sign in"}
                                </a>
                            </nav>
                            <div className="header-actions">
                                {context.user ? (
                                    accountDropdown
                                ) : (
                                    <a href="/login" className="sign-in">
                                        Sign in
                                    </a>
                                )}
                                <a className="button small" href="/contact">
                                    Let’s talk <span>↗</span>
                                </a>
                            </div>
                        </>
                    )}
                    <button
                        className="menu-toggle"
                        aria-label={
                            open ? "Close navigation" : "Open navigation"
                        }
                        aria-expanded={open}
                        onClick={() => {
                            setOpen(!open);
                            setAccountOpen(false);
                        }}
                    >
                        {open ? "✕" : "☰"}
                    </button>
                </header>
                <main id="main" className={admin ? "admin-main" : ""}>
                    {Object.entries(context.flash).map(([key, value]) => (
                        <div
                            role="status"
                            className={`notice ${key === "error" ? "error" : ""}`}
                            key={key}
                        >
                            {value}
                        </div>
                    ))}
                    {Object.keys(context.errors).length > 0 && (
                        <div className="notice error" role="alert">
                            <strong>Please check the following:</strong>
                            <ul>
                                {Object.entries(context.errors).map(
                                    ([key, errors]) => (
                                        <li key={key}>{errors.join(" ")}</li>
                                    ),
                                )}
                            </ul>
                        </div>
                    )}
                    {children}
                </main>
                {!admin && (
                    <footer>
                        <div className="footer-top">
                            <div>
                                <Logo />
                                <p>
                                    Thoughtfully designed.
                                    <br />
                                    Purposefully built.
                                </p>
                            </div>
                            <div>
                                <span className="eyebrow">EXPLORE</span>
                                <a href="/services">Our services</a>
                                <a href="/blogs">Journal</a>
                                <a href="/contact">Get in touch</a>
                            </div>
                            <div>
                                <span className="eyebrow">YOUR WORKSPACE</span>
                                <a
                                    href={
                                        context.user ? "/dashboard" : "/login"
                                    }
                                >
                                    {context.user ? "Dashboard" : "Sign in"}
                                </a>
                                <a href={context.user ? "/chat" : "/register"}>
                                    {context.user
                                        ? "Support chat"
                                        : "Create an account"}
                                </a>
                                {context.user && (
                                    <Form
                                        action="/logout"
                                        submit="Sign out"
                                        className="inline-form"
                                    />
                                )}
                            </div>
                            <div className="footer-statement">
                                Your next big idea.
                                <br />
                                <em>Let’s make it happen.</em>
                                <a href="/contact">Start a conversation ↗</a>
                            </div>
                        </div>
                        <div className="footer-bottom">
                            <span>
                                © {new Date().getFullYear()} USP Tech Solution
                            </span>
                            <span>Ideas. Craft. Impact.</span>
                        </div>
                    </footer>
                )}
            </div>
        </div>
    );
}
function Page() {
    const page = context.page,
        p = context.props;
    if (page === "home") return <Home {...p} />;
    if (page.startsWith("auth.")) return <Auth {...p} />;
    if (page === "dashboard") return <Dashboard {...p} />;
    if (page === "contact") return <Contact />;
    if (page === "services.index" || page === "services.category")
        return <Listing {...p} />;
    if (page === "blogs.index" || page === "blogs.category")
        return <Listing blog {...p} />;
    if (page === "services.show" || page === "blogs.show")
        return <Detail {...p} />;
    if (page.endsWith("profile.edit")) return <Profile />;
    if (page === "settings.appearance") return <Appearance />;
    if (page === "settings.two-factor") return <Security {...p} />;
    if (page === "chat.index" || page === "admin.chat.show")
        return <Chat {...p} />;
    if (page === "admin.chat.index") return <ChatList {...p} />;
    if (page === "admin.dashboard") return <AdminDashboard {...p} />;
    if (page.startsWith("admin.requests.")) return <Requests {...p} />;
    if (page.startsWith("admin.contact-messages.")) return <Contacts {...p} />;
    if (page.startsWith("admin.analytics.")) return <Analytics {...p} />;
    const [, kind, action] = page.split(".");
    if (["services", "blogs", "categories", "blog-categories"].includes(kind))
        return action === "index" ? (
            <ResourceList kind={kind} {...p} />
        ) : (
            <ResourceEditor kind={kind} {...p} />
        );
    return (
        <Empty
            title="Page unavailable"
            description="Return home or contact our support team."
        />
    );
}
class ErrorBoundary extends React.Component {
    state = { failed: false };
    static getDerivedStateFromError() {
        return { failed: true };
    }
    render() {
        return this.state.failed ? (
            <section className="section">
                <h1>Something didn’t load.</h1>
                <p>Please refresh the page and try again.</p>
                <a href="/" className="button">
                    Return home
                </a>
            </section>
        ) : (
            this.props.children
        );
    }
}
createRoot(document.getElementById("app")).render(
    <ErrorBoundary>
        <Layout>
            <Page />
        </Layout>
    </ErrorBoundary>,
);
