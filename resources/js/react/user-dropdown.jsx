import React, { useEffect, useRef } from "react";
import { context, Form } from "./ui";

function AccountIcon({ type }) {
    const paths = {
        profile: "M20 21v-2a7 7 0 0 0-14 0v2M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z",
        dashboard: "M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z",
        appearance: "M12 3a9 9 0 1 0 9 9h-9V3Z",
        security: "m12 3 8 4v5c0 5-8 9-8 9s-8-4-8-9V7l8-4Zm-4 9 3 3 5-6",
    };
    return (
        <svg
            width="19"
            height="19"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
        >
            <path d={paths[type]} />
        </svg>
    );
}

export default function UserDropdown({ open, onToggle, onClose }) {
    const container = useRef(null);
    const trigger = useRef(null);
    const user = context.user;
    const initials =
        user.name
            .trim()
            .split(/\s+/)
            .slice(0, 2)
            .map((part) => part[0])
            .join("")
            .toUpperCase() || "U";
    const profile = user.is_admin ? "/admin/profile" : "/profile";
    const dashboard = user.is_admin ? "/admin/dashboard" : "/dashboard";

    useEffect(() => {
        if (!open) return;
        function outside(event) {
            if (!container.current?.contains(event.target)) onClose();
        }
        function escape(event) {
            if (event.key === "Escape") {
                onClose();
                trigger.current?.focus();
            }
        }
        document.addEventListener("pointerdown", outside);
        document.addEventListener("keydown", escape);
        return () => {
            document.removeEventListener("pointerdown", outside);
            document.removeEventListener("keydown", escape);
        };
    }, [open, onClose]);

    return (
        <div
            className="user-dropdown"
            ref={container}
            onBlur={(event) => {
                if (!event.currentTarget.contains(event.relatedTarget))
                    onClose();
            }}
        >
            <button
                ref={trigger}
                type="button"
                className={`user-trigger ${open ? "is-open" : ""}`}
                aria-label={`Account options for ${user.name}`}
                aria-expanded={open}
                aria-controls="header-account-panel"
                onClick={onToggle}
            >
                <span className="user-avatar" aria-hidden="true">
                    {initials}
                    <i />
                </span>
                <span className="user-trigger-copy">
                    <strong>{user.name.split(" ")[0]}</strong>
                    <small>
                        {user.is_admin ? "Administrator" : "My workspace"}
                    </small>
                </span>
                <svg
                    className="user-chevron"
                    width="15"
                    height="15"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="1.8"
                    aria-hidden="true"
                >
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </button>
            {open && (
                <div className="user-panel" id="header-account-panel">
                    <div className="user-panel-heading">
                        <span className="user-avatar large" aria-hidden="true">
                            {initials}
                        </span>
                        <div>
                            <strong>{user.name}</strong>
                            <span>{user.email}</span>
                            <small>
                                {user.is_admin
                                    ? "Admin workspace"
                                    : "Personal account"}
                            </small>
                        </div>
                    </div>
                    <nav
                        aria-label="Account options"
                        className="user-panel-links"
                    >
                        {[
                            [
                                profile,
                                "My profile",
                                "Personal details & password",
                                "profile",
                            ],
                            [
                                dashboard,
                                "Dashboard",
                                "Your workspace at a glance",
                                "dashboard",
                            ],
                            [
                                "/settings/appearance",
                                "Appearance",
                                "Make this space yours",
                                "appearance",
                            ],
                            [
                                "/settings/two-factor",
                                "Account security",
                                "Two-factor authentication",
                                "security",
                            ],
                        ].map(([href, title, description, icon]) => (
                            <a
                                key={href}
                                href={href}
                                aria-current={
                                    location.pathname === href
                                        ? "page"
                                        : undefined
                                }
                            >
                                <span className="user-link-icon">
                                    <AccountIcon type={icon} />
                                </span>
                                <span>
                                    <strong>{title}</strong>
                                    <small>{description}</small>
                                </span>
                                <span
                                    className="user-link-arrow"
                                    aria-hidden="true"
                                >
                                    ↗
                                </span>
                            </a>
                        ))}
                    </nav>
                    <div className="user-panel-footer">
                        <span>See you again soon.</span>
                        <Form
                            action="/logout"
                            submit="Sign out"
                            className="user-signout"
                        />
                    </div>
                </div>
            )}
        </div>
    );
}
