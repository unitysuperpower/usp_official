import React, { createContext, useContext, useState } from "react";
import DOMPurify from "dompurify";
export const context = window.__USP__;
const FormScope = createContext(null);
export const rows = (value) =>
    Array.isArray(value) ? value : value?.data || [];
export const label = (value) =>
    String(value ?? "")
        .replaceAll("_", " ")
        .replaceAll("-", " ");
export const date = (value) =>
    value
        ? new Date(value).toLocaleDateString(undefined, {
              year: "numeric",
              month: "short",
              day: "numeric",
          })
        : "—";
export const money = (value) =>
    Number(value || 0).toLocaleString(undefined, {
        style: "currency",
        currency: "USD",
    });
export function Html({ value }) {
    return (
        <div
            className="prose"
            dangerouslySetInnerHTML={{
                __html: DOMPurify.sanitize(value || ""),
            }}
        />
    );
}
export function Badge({ children }) {
    return <span className={`badge ${children}`}>{label(children)}</span>;
}
export function Heading({
    eyebrow,
    title,
    description,
    children,
    as: Title = "h1",
}) {
    return (
        <header className="page-heading">
            <div>
                <span className="eyebrow">
                    {eyebrow || "USP TECH SOLUTION"}
                </span>
                <Title className="page-title">{title}</Title>
                {description && <p>{description}</p>}
            </div>
            {children}
        </header>
    );
}
export function Empty({
    title = "Nothing here yet",
    description = "New items will appear here when they are available.",
}) {
    return (
        <div className="empty">
            <span className="empty-icon">◇</span>
            <h3>{title}</h3>
            <p>{description}</p>
        </div>
    );
}
export function Pagination({ data }) {
    return (
        data?.last_page > 1 && (
            <nav className="pagination" aria-label="Pagination">
                {data.links.map((link, i) =>
                    link.url ? (
                        <a
                            key={i}
                            className={link.active ? "active" : ""}
                            href={link.url}
                            aria-current={link.active ? "page" : undefined}
                        >
                            {label(link.label)
                                .replace("&laquo;", "←")
                                .replace("&raquo;", "→")}
                        </a>
                    ) : (
                        <span key={i}>
                            {label(link.label)
                                .replace("&laquo;", "←")
                                .replace("&raquo;", "→")}
                        </span>
                    ),
                )}
            </nav>
        )
    );
}
export function Field({
    name,
    title,
    type = "text",
    value,
    options,
    required = false,
    ...rest
}) {
    const action = useContext(FormScope);
    const useOld =
        !context.old._form_action || context.old._form_action === action;
    const initial = (useOld ? context.old[name] : undefined) ?? value ?? "";
    return (
        <label className={type === "checkbox" ? "check-field" : "field"}>
            <span>
                {title || label(name)}
                {required && " *"}
            </span>
            {type === "textarea" ? (
                <textarea
                    name={name}
                    defaultValue={initial}
                    required={required}
                    rows={5}
                    {...rest}
                />
            ) : type === "select" ? (
                <select
                    name={name}
                    defaultValue={initial}
                    required={required}
                    {...rest}
                >
                    {options?.map((o) => (
                        <option key={o.value ?? o} value={o.value ?? o}>
                            {o.label ?? label(o)}
                        </option>
                    ))}
                </select>
            ) : type === "checkbox" ? (
                <>
                    <input type="hidden" name={name} value="0" />
                    <input
                        type="checkbox"
                        name={name}
                        value="1"
                        defaultChecked={
                            initial === true || initial === 1 || initial === "1"
                        }
                        {...rest}
                    />
                </>
            ) : (
                <input
                    name={name}
                    type={type}
                    {...(type === "file" ? {} : { defaultValue: initial })}
                    required={required}
                    {...rest}
                />
            )}{" "}
            {useOld && context.errors[name] && (
                <small className="error">
                    {context.errors[name].join(" ")}
                </small>
            )}
        </label>
    );
}
export function Form({
    action,
    method = "POST",
    children,
    submit = "Save changes",
    className = "",
    confirm,
    ...rest
}) {
    const [busy, setBusy] = useState(false);
    return (
        <FormScope.Provider value={action}>
            <form
                action={action}
                method={method === "GET" ? "GET" : "POST"}
                encType={method === "GET" ? undefined : "multipart/form-data"}
                className={`form ${className}`}
                onSubmit={(e) => {
                    if (confirm && !window.confirm(confirm)) {
                        e.preventDefault();
                        return;
                    }
                    setBusy(true);
                }}
                {...rest}
            >
                {method !== "GET" && (
                    <>
                        <input
                            type="hidden"
                            name="_form_action"
                            value={action}
                        />
                        <input
                            type="hidden"
                            name="_token"
                            value={context.csrf}
                        />
                        <input type="hidden" name="_method" value={method} />
                    </>
                )}
                {children}
                {submit && (
                    <button className="button" disabled={busy}>
                        {busy ? "Please wait…" : submit}
                        <span aria-hidden="true"> ↗</span>
                    </button>
                )}
            </form>
        </FormScope.Provider>
    );
}
export async function api(url, options = {}) {
    const response = await fetch(url, {
        credentials: "same-origin",
        ...options,
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": context.csrf,
            ...options.headers,
        },
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok)
        throw new Error(
            Object.values(data.errors || {})
                .flat()
                .join(" ") ||
                data.message ||
                data.error ||
                `Request failed (${response.status}). Please try again.`,
        );
    return data;
}
export function Stats({ stats }) {
    return (
        <div className="stats">
            {Object.entries(stats || {}).map(([key, value]) => (
                <div className="stat" key={key}>
                    <span>{label(key)}</span>
                    <strong>
                        {typeof value === "number"
                            ? value.toLocaleString()
                            : value}
                    </strong>
                    <i aria-hidden="true">↗</i>
                </div>
            ))}
        </div>
    );
}
export function Table({ columns, data, empty }) {
    return rows(data).length ? (
        <div className="table-wrap">
            <table>
                <thead>
                    <tr>
                        {columns.map((c) => (
                            <th key={c.title}>{c.title}</th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows(data).map((item, i) => (
                        <tr key={item.id ?? i}>
                            {columns.map((c) => (
                                <td key={c.title}>
                                    {c.render
                                        ? c.render(item)
                                        : (item[c.key] ?? "—")}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    ) : (
        <Empty title={empty} />
    );
}
