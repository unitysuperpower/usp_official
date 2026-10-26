import React, { useEffect, useRef } from "react";
import { context } from "./ui";

const groups = [
    ["Product store", [["store/products", "Products & plans"], ["store/orders", "Orders & payments"], ["store/licenses", "Licenses"]]],
    [
        "Education",
        [
            ["education/overview", "Education overview"],
            ["education/teaching", "My classes"],
            ["education/courses", "Courses & curriculum"],
            ["education/enrollments", "Students & applications"],
            ["education/payments", "Course payments"],
            ["education/settings", "Payment settings"],
        ],
    ],
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
    [
        "Administration",
        [
            ["users", "Users"],
            ["roles", "Roles & permissions"],
            ["settings/theme", "Appearance"],
            ["settings/email", "Email delivery"],
        ],
    ],
];
// Permission each link needs (see config/permissions.php).
const permissionFor = {
    "store/products": "store.products",
    "store/orders": "store.orders",
    "store/licenses": "store.licenses",
    "education/overview": ["education.courses", "education.enrollments", "education.payments"],
    "education/teaching": "education.teach",
    "education/courses": "education.courses",
    "education/enrollments": "education.enrollments",
    "education/payments": "education.payments",
    "education/settings": "education.settings",
    dashboard: "dashboard.view",
    analytics: "analytics.view",
    services: "services.manage",
    categories: "services.manage",
    blogs: "blogs.manage",
    "blog-categories": "blogs.manage",
    requests: "requests.manage",
    projects: "projects.manage",
    "contact-messages": "contact.manage",
    chat: "chat.manage",
    users: "users.manage",
    roles: "roles.manage",
    "settings/email": "settings.email",
    "settings/theme": "settings.theme",
};
const allowed = (path) => {
    const granted = context.user?.permissions || [];
    return [permissionFor[path]].flat().some((p) => granted.includes(p));
};
const visibleGroups = groups
    .map(([title, links]) => [title, links.filter(([path]) => allowed(path))])
    .filter(([, links]) => links.length);
const paths = {
    "store/products": "M3 5h18v16H3z M3 10h18 M8 5V3h8v2",
    "store/orders": "M3 5h18v14H3z M3 10h18 M7 15h3",
    "store/licenses": "M15 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0 M11 11v10 M11 16h4 M11 20h3",
    "education/overview": "M4 3v18h17 M8 16v-5 M13 16V7 M18 16V4",
    "education/courses": "M3 4h7l2 2 2-2h7v16h-7l-2 2-2-2H3z M12 6v16",
    "education/teaching": "M2 9l10-5 10 5-10 5z M6 11v5c3 2 9 2 12 0v-5 M22 9v6",
    "education/enrollments":
        "M16 21v-2a6 6 0 0 0-12 0v2 M14 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0 M19 8v6 M16 11h6",
    "education/payments": "M3 5h18v14H3z M3 10h18 M7 15h3",
    "education/settings": "M4 7h16 M4 17h16 M9 4v6 M15 14v6",
    dashboard: "M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z",
    analytics: "M4 3v18h17 M8 16v-5 M13 16V7 M18 16V4",
    services: "M4 7h16v14H4z M8 7V3h8v4 M4 12h16 M10 12v3h4v-3",
    categories: "M3 6h7l2 3h9v11H3z M3 6V4h7l2 2h7v3",
    blogs: "M5 3h10l4 4v14H5z M14 3v5h5 M8 12h8 M8 16h6",
    "blog-categories": "M3 3h9l9 9-9 9-9-9z M7 7h.01",
    projects: "M3 5h7l2 3h9v13H3z M7 13h10 M7 17h6",
    requests: "M8 5H5v16h14V5h-3 M8 3h8v4H8z M8 12h8 M8 16h5",
    "contact-messages": "M3 5h18v14H3z M3 6l9 7 9-7",
    "settings/email": "M3 5h18v14H3z M3 6l9 7 9-7 M16 15l2 2 4-4",
    "settings/theme": "M12 3a9 9 0 1 0 0 18c1.1 0 2-.9 2-2 0-.5-.2-1-.5-1.3-.3-.4-.5-.8-.5-1.3 0-1.1.9-2 2-2h2.4A4.6 4.6 0 0 0 21 9.8C21 6 17 3 12 3z M7.5 11h.01 M10 7.5h.01 M14.5 7.5h.01",
    users: "M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0 M22 21v-2a4 4 0 0 0-3-3.9 M16 3.1a4 4 0 0 1 0 7.8",
    roles: "M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z M9 12l2 2 4-4",
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
const active = (path) => {
    const page = context.page
        .replace("admin.store.editor", "admin.store.products")
        .replace(/^admin\.store\.order$/, "admin.store.orders")
        .replace("admin.education.classroom", context.user?.permissions?.includes("education.courses") ? "admin.education.courses" : "admin.education.teaching")
        .replace("admin.education.editor", "admin.education.courses")
        .replace("admin.education.review", "admin.education.enrollments");
    const key = path.replaceAll("/", ".");
    return page === `admin.${key}` || page.startsWith(`admin.${key}.`);
};
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
                    <span className="workspace-tag">{context.theme.brand.name}</span>
                </div>
                <nav aria-label="Admin navigation">
                    {visibleGroups.map(([title, links]) => (
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
