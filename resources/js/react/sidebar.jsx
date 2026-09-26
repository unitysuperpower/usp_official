import React, { useEffect, useRef } from "react";
import { context } from "./ui";

const groups = [
    [
        "Workspace",
        [
            ["dashboard", "Overview"],
            ["analytics", "Analytics"],
        ],
    ],
    [
        "Manage content",
        [
            ["services", "Services"],
            ["categories", "Service categories"],
            ["blogs", "Journal"],
            ["blog-categories", "Blog categories"],
        ],
    ],
    [
        "Customer care",
        [
            ["requests", "Leads & requests"],
            ["projects", "Projects"],
            ["contact-messages", "Contact inbox"],
            ["chat", "Conversations"],
        ],
    ],
];
const paths = {
    dashboard: "M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z",
    analytics: "M4 3v18h17 M8 16v-5 M13 16V7 M18 16V4",
    services: "M4 7h16v14H4z M8 7V3h8v4 M4 12h16 M10 12v3h4v-3",
    categories: "M3 6h7l2 3h9v11H3z M3 6V4h7l2 2h7v3",
    blogs: "M5 3h10l4 4v14H5z M14 3v5h5 M8 12h8 M8 16h6",
    "blog-categories": "M3 3h9l9 9-9 9-9-9z M7 7h.01",
    projects: "M3 5h7l2 3h9v13H3z M7 13h10 M7 17h6",
    requests: "M8 5H5v16h14V5h-3 M8 3h8v4H8z M8 12h8 M8 16h5",
    "contact-messages": "M3 5h18v14H3z M3 6l9 7 9-7",
    chat: "M21 11a8 8 0 0 1-8 8H7l-4 3V11a9 9 0 0 1 18 0z M7 11h.01 M12 11h.01 M17 11h.01",
    external: "M14 3h7v7 M21 3l-11 11 M10 3H3v18h18v-7",
    collapse: "M3 3h18v18H3z M9 3v18 M16 9l-3 3 3 3",
    close: "M6 6l12 12 M6 18L18 6",
};
export function NavIcon({ name }) {
    return (
        <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
        >
            <path d={paths[name]} />
        </svg>
    );
}
const active = (path) =>
    context.page === `admin.${path}` ||
    context.page.startsWith(`admin.${path}.`);
export const workspaceTitle =
    groups.flatMap(([, links]) => links).find(([path]) => active(path))?.[1] ||
    (active("profile") ? "My profile" : "Workspace");

export default function Sidebar({
    open,
    onClose,
    collapsed,
    onCollapse,
    logo,
}) {
    const panel = useRef(null);
    const closeButton = useRef(null);
    useEffect(() => {
        if (!open) return;
        const previousFocus = document.activeElement;
        const main = document.querySelector(".main-shell");
        const previousOverflow = document.body.style.overflow;
        const wasInert = main.inert;
        main.inert = true;
        document.body.style.overflow = "hidden";
        closeButton.current.focus();
        const onKey = (event) => {
            if (event.key === "Escape") {
                event.preventDefault();
                onClose();
            }
            if (event.key !== "Tab") return;
            const items = [
                ...panel.current.querySelectorAll(
                    "a[href], button:not([disabled])",
                ),
            ].filter((el) => el.getClientRects().length);
            const first = items[0],
                last = items.at(-1);
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        };
        const media = window.matchMedia("(max-width: 800px)");
        const resize = () => {
            if (!media.matches) onClose();
        };
        document.addEventListener("keydown", onKey);
        media.addEventListener("change", resize);
        return () => {
            main.inert = wasInert;
            document.body.style.overflow = previousOverflow;
            document.removeEventListener("keydown", onKey);
            media.removeEventListener("change", resize);
            previousFocus?.focus();
        };
    }, [open, onClose]);
    return (
        <>
            {open && (
                <div
                    className="sidebar-backdrop"
                    onClick={onClose}
                    aria-hidden="true"
                />
            )}
            <aside
                ref={panel}
                id="admin-navigation"
                className={`sidebar ${open ? "is-open" : ""}`}
                role={open ? "dialog" : undefined}
                aria-modal={open ? true : undefined}
                aria-label="Admin workspace"
            >
                <div className="sidebar-brand">
                    {logo}
                    <button
                        ref={closeButton}
                        className="sidebar-close"
                        onClick={onClose}
                        aria-label="Close navigation"
                    >
                        <NavIcon name="close" />
                    </button>
                </div>
                <div className="sidebar-workspace">
                    <span className="workspace-dot" />
                    <span>Admin workspace</span>
                    <span className="workspace-tag">USP</span>
                </div>
                <nav aria-label="Admin navigation">
                    {groups.map(([title, links]) => (
                        <div className="nav-group" key={title}>
                            <span className="nav-caption">{title}</span>
                            {links.map(([path, title]) => (
                                <a
                                    key={path}
                                    href={`/admin/${path}`}
                                    className={active(path) ? "active" : ""}
                                    aria-current={
                                        active(path) ? "page" : undefined
                                    }
                                    aria-label={title}
                                    title={collapsed ? title : undefined}
                                >
                                    <NavIcon name={path} />
                                    <span className="nav-label">{title}</span>
                                    {active(path) && (
                                        <i
                                            className="nav-active-dot"
                                            aria-hidden="true"
                                        />
                                    )}
                                </a>
                            ))}
                        </div>
                    ))}
                </nav>
                <div className="sidebar-footer">
                    <a
                        className="site-link"
                        href="/"
                        title={collapsed ? "View website" : undefined}
                        aria-label="View website"
                    >
                        <NavIcon name="external" />
                        <span className="nav-label">View website</span>
                    </a>
                    <button
                        className="sidebar-collapse"
                        onClick={onCollapse}
                        aria-label={
                            collapsed ? "Expand sidebar" : "Collapse sidebar"
                        }
                        aria-expanded={!collapsed}
                        title={collapsed ? "Expand sidebar" : undefined}
                    >
                        <NavIcon name="collapse" />
                        <span className="nav-label">Collapse sidebar</span>
                    </button>
                </div>
            </aside>
        </>
    );
}
