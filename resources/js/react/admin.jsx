import React, { useState } from "react";
import RichEditor from "./rich-editor-lazy";
import {
    context,
    rows,
    label,
    date,
    money,
    Heading,
    Empty,
    Pagination,
    Field,
    Form,
    Badge,
    Stats,
    Table,
    Modal,
    ModalErrors,
    DeleteAction,
} from "./ui";
const resourceNames = {
    services: "Services",
    blogs: "Blog articles",
    categories: "Service categories",
    "blog-categories": "Blog categories",
};
export { default as AdminDashboard } from "./admin-overview";
export function ResourceList({ kind, ...props }) {
    const data = props[kind === "blog-categories" ? "categories" : kind] || [];
    const categories = kind.includes("categories");
    const [editing, setEditing] = useState(() => {
        if (!categories || context.old._modal !== "category") return null;
        const id = Number(context.old._form_action?.split("/").pop());
        return id ? rows(data).find((item) => item.id === id) || null : {};
    });
    return (
        <>
            <Heading
                title={resourceNames[kind]}
                description="Create, refine, and manage the content your customers see."
            >
                {categories ? (
                    <button
                        className="button"
                        type="button"
                        onClick={() => setEditing({})}
                    >
                        Create category +
                    </button>
                ) : (
                    <a className="button" href={`/admin/${kind}/create`}>
                        Create{" "}
                        {categories
                            ? "category"
                            : kind === "blogs"
                              ? "article"
                              : "service"}{" "}
                        ↗
                    </a>
                )}
            </Heading>
            <div className="panel">
                <Form
                    action={`/admin/${kind}`}
                    method="GET"
                    className="search-form"
                    submit="Search"
                >
                    <Field
                        name="search"
                        type="search"
                        title={`Search ${resourceNames[kind].toLowerCase()}`}
                        value={props.filters?.search}
                        maxLength={200}
                        placeholder="Search by name or category…"
                    />
                    <a className="text-link" href={`/admin/${kind}`}>
                        Reset
                    </a>
                </Form>
                <p>{data.total || 0} results</p>
                <Table
                    data={data}
                    columns={[
                        {
                            title: "Name",
                            render: (r) => (
                                <a
                                    className="table-title"
                                    href={`/admin/${kind}/${r.id}/edit`}
                                >
                                    {r.title || r.name}
                                </a>
                            ),
                        },
                        ...(!categories
                            ? [
                                  {
                                      title: "Category",
                                      render: (r) => r.category?.name || "—",
                                  },
                              ]
                            : []),
                        ...(categories
                            ? [
                                  {
                                      title: "Items",
                                      render: (r) =>
                                          r.services_count ??
                                          r.blogs_count ??
                                          0,
                                  },
                              ]
                            : []),
                        { title: "Created", render: (r) => date(r.created_at) },
                        {
                            title: "Status",
                            render: (r) => (
                                <Badge>
                                    {(
                                        kind === "blogs"
                                            ? r.is_published
                                            : r.is_active
                                    )
                                        ? "active"
                                        : "draft"}
                                </Badge>
                            ),
                        },
                        ...(kind === "services"
                            ? [
                                  {
                                      title: "Price",
                                      render: (r) => money(r.price),
                                  },
                              ]
                            : []),
                        {
                            title: "Actions",
                            render: (r) => (
                                <div className="table-actions">
                                    {categories ? (
                                        <button
                                            type="button"
                                            className="table-action"
                                            onClick={() => setEditing(r)}
                                            aria-label={"Edit " + r.name}
                                        >
                                            Edit
                                        </button>
                                    ) : (
                                        <a
                                            href={
                                                "/admin/" +
                                                kind +
                                                "/" +
                                                r.id +
                                                "/edit"
                                            }
                                        >
                                            Edit ↗
                                        </a>
                                    )}
                                    {!categories &&
                                        (kind === "blogs"
                                            ? r.is_published
                                            : r.is_active) && (
                                            <a href={"/" + kind + "/" + r.slug}>
                                                View ↗
                                            </a>
                                        )}
                                    <DeleteAction
                                        action={"/admin/" + kind + "/" + r.id}
                                        name={r.title || r.name}
                                        disabled={
                                            categories &&
                                            (r.services_count > 0 ||
                                                r.blogs_count > 0)
                                        }
                                    />
                                </div>
                            ),
                        },
                    ]}
                    empty={
                        props.filters?.search
                            ? "No matching results"
                            : "Ready for something new?"
                    }
                />
                <Pagination data={data} />
            </div>
            {editing && (
                <Modal
                    open={true}
                    onClose={() => setEditing(null)}
                    title={(editing.id ? "Edit" : "Create") + " category"}
                >
                    <ModalErrors />
                    <ResourceEditor
                        key={editing.id || "new"}
                        kind={kind}
                        category={editing}
                        embedded
                        onCancel={() => setEditing(null)}
                    />
                </Modal>
            )}
        </>
    );
}
export function ResourceEditor({ kind, embedded = false, onCancel, ...props }) {
    const category = kind.includes("categories");
    const blog = kind === "blogs";
    const item =
        props.service ||
        props.blog ||
        props.category ||
        props.blogCategory ||
        {};
    const edit = Boolean(item.id);
    const [features, setFeatures] = useState(
        Object.values(
            (context.old._form_action ===
            "/admin/" + kind + (item.id ? "/" + item.id : "")
                ? context.old.features
                : null) ||
                item.features || [""],
        ),
    );
    return (
        <>
            {!embedded && (
                <Heading
                    title={`${edit ? "Edit" : "Create"} ${category ? "category" : blog ? "article" : "service"}`}
                    description="Make every detail count."
                />
            )}
            <Form
                action={`/admin/${kind}${edit ? `/${item.id}` : ""}`}
                method={edit ? "PUT" : "POST"}
                className={embedded ? "editor" : "panel editor"}
                submit={edit ? "Save changes" : "Create"}
            >
                {embedded && (
                    <input type="hidden" name="_modal" value="category" />
                )}
                <div className="form-grid">
                    <Field
                        name={category ? "name" : "title"}
                        title={category ? "Category name" : "Title"}
                        value={item.name || item.title}
                        required
                        maxLength={255}
                    />
                    {!category && (
                        <Field
                            name="category_id"
                            title="Category"
                            type="select"
                            value={item.category_id}
                            required
                            options={[
                                { value: "", label: "Select a category" },
                                ...(props.categories || []).map((c) => ({
                                    value: c.id,
                                    label: c.name,
                                })),
                            ]}
                        />
                    )}
                </div>
                {!category && (
                    <Field
                        name={blog ? "excerpt" : "short_description"}
                        title="Short introduction"
                        type="textarea"
                        value={blog ? item.excerpt : item.short_description}
                    />
                )}
                {category ? (
                    <Field name="description" title="Description" type="textarea" rows={4} value={item.description} />
                ) : (
                    <div className="field">
                        <span>{blog ? "Article" : "Service description"} *</span>
                        <RichEditor name={blog ? "content" : "description"} value={item.content || item.description} placeholder={blog ? "Write your article… Type ## for a heading, - for a list, or drop images in." : "Describe the service…"} />
                    </div>
                )}
                {kind === "services" && (
                    <>
                        <div className="form-grid">
                            <Field
                                name="price"
                                type="number"
                                value={item.price}
                                required
                                min="0"
                                step="0.01"
                            />
                            <Field
                                name="price_unit"
                                title="Price unit"
                                value={item.price_unit || "project"}
                                required
                                maxLength={50}
                            />
                            <Field
                                name="delivery_days"
                                title="Delivery days"
                                type="number"
                                value={item.delivery_days}
                                min="1"
                            />
                        </div>
                        <Field
                            name="features_text"
                            title="Features (one per line)"
                            type="textarea"
                            value={Object.values(item.features || {}).join(
                                "\n",
                            )}
                        />
                    </>
                )}
                {!category && (
                    <>
                        <Field
                            name={blog ? "featured_image" : "image"}
                            title="Cover image (JPEG, PNG, GIF or WebP; up to 10 MB)"
                            type="file"
                            accept="image/jpeg,image/png,image/gif,image/webp"
                        />
                        {(item.image || item.featured_image) && (
                            <img
                                className="image-preview"
                                src={`/storage/${item.image || item.featured_image}`}
                                alt="Current cover"
                            />
                        )}
                        <Field
                            name="is_featured"
                            title="Feature on the website"
                            type="checkbox"
                            value={item.is_featured}
                        />
                    </>
                )}
                {kind === "categories" && (
                    <>
                        <Field name="icon" title="Icon" value={item.icon} />
                        <fieldset>
                            <legend>Category features</legend>
                            {features.map((f, i) => (
                                <label className="field" key={i}>
                                    <span>Feature {i + 1}</span>
                                    <input
                                        name="features[]"
                                        value={f}
                                        maxLength={500}
                                        onChange={(e) =>
                                            setFeatures(
                                                features.map((v, n) =>
                                                    n === i
                                                        ? e.target.value
                                                        : v,
                                                ),
                                            )
                                        }
                                    />
                                </label>
                            ))}
                            <button
                                className="button secondary"
                                type="button"
                                onClick={() => setFeatures([...features, ""])}
                            >
                                Add feature +
                            </button>
                        </fieldset>
                    </>
                )}
                {kind === "blog-categories" && (
                    <Field
                        name="color"
                        type="color"
                        value={item.color || "#167b6d"}
                    />
                )}
                <Field
                    name={blog ? "is_published" : "is_active"}
                    title={blog ? "Published" : "Active"}
                    type="checkbox"
                    value={
                        edit
                            ? blog
                                ? item.is_published
                                : item.is_active
                            : true
                    }
                />
                {blog && (
                    <>
                        <Field
                            name="tags"
                            title="Tags (comma separated)"
                            value={Object.values(item.tags || {}).join(", ")}
                        />
                        <Field
                            name="meta_title"
                            title="SEO title"
                            value={item.meta_title}
                        />
                        <Field
                            name="meta_description"
                            title="SEO description"
                            type="textarea"
                            value={item.meta_description}
                        />
                    </>
                )}
                {embedded ? (
                    <button
                        className="button secondary"
                        type="button"
                        onClick={onCancel}
                    >
                        Cancel
                    </button>
                ) : (
                    <a className="text-link" href={`/admin/${kind}`}>
                        Cancel
                    </a>
                )}
            </Form>
        </>
    );
}
function RequestsTable({ data }) {
    return (
        <Table
            data={data}
            columns={[
                {
                    title: "Customer",
                    render: (r) => (
                        <a
                            className="table-title"
                            href={`/admin/requests/${r.id}`}
                        >
                            {r.name || r.user?.name}
                        </a>
                    ),
                },
                {
                    title: "Service",
                    render: (r) => r.service?.title || "Unavailable",
                },
                { title: "Status", render: (r) => <Badge>{r.status}</Badge> },
                { title: "Received", render: (r) => date(r.created_at) },
                {
                    title: "",
                    render: (r) => (
                        <a href={`/admin/requests/${r.id}`}>Open ↗</a>
                    ),
                },
            ]}
        />
    );
}
export function Contacts({
    message,
    messages,
    pendingCount,
    unreadCount,
    repliedCount,
}) {
    const q = new URLSearchParams(location.search);
    const [editing, setEditing] = useState(() =>
        context.old._modal === "contact"
            ? rows(messages).find(
                  (item) =>
                      String(item.id) ===
                      context.old._form_action?.split("/").pop(),
              ) || null
            : null,
    );
    return (
        <>
            <Heading
                title={message ? message.subject : "Contact inbox"}
                description="Every conversation is a new possibility."
            />
            {message ? (
                <>
                    <div className="split-panels">
                        <article className="panel">
                            <Badge>{message.status}</Badge>
                            <h2>{message.name}</h2>
                            <p>
                                {message.email} · {message.phone}
                            </p>
                            <p className="preserve">{message.message}</p>
                            <small>{date(message.created_at)}</small>
                        </article>
                        <Form
                            action={`/admin/contact-messages/${message.id}`}
                            method="PUT"
                            className="panel"
                        >
                            <Field
                                name="status"
                                type="select"
                                value={message.status}
                                options={[
                                    "pending",
                                    "read",
                                    "replied",
                                    "archived",
                                ]}
                            />
                            <Field
                                name="admin_notes"
                                title="Internal notes"
                                type="textarea"
                                value={message.admin_notes}
                            />
                        </Form>
                    </div>
                    <Form
                        action={`/admin/contact-messages/${message.id}/reply`}
                        className="panel"
                        submit="Send email reply"
                    >
                        <h2>Reply to {message.name}</h2>
                        <Field
                            name="subject"
                            value={`Re: ${message.subject}`}
                            required
                        />
                        <Field
                            name="reply_message"
                            title="Your reply"
                            type="textarea"
                            required
                            minLength={10}
                        />
                        <Field
                            name="mark_as_replied"
                            title="Mark as replied"
                            type="checkbox"
                            value={true}
                        />
                    </Form>
                    <Form
                        action={`/admin/contact-messages/${message.id}`}
                        method="DELETE"
                        className="inline-form danger"
                        submit="Delete message"
                        confirm="Permanently delete this message?"
                    />
                </>
            ) : (
                <>
                    <Stats
                        stats={{
                            pending: pendingCount,
                            unread: unreadCount,
                            replied: repliedCount,
                        }}
                    />
                    <Form
                        method="GET"
                        action="/admin/contact-messages"
                        className="search-form"
                        submit="Filter"
                    >
                        <Field name="search" value={q.get("search")} />
                        <Field
                            name="status"
                            type="select"
                            value={q.get("status")}
                            options={[
                                { value: "", label: "All statuses" },
                                "pending",
                                "read",
                                "replied",
                                "archived",
                            ]}
                        />
                    </Form>
                    {editing && (
                        <Modal
                            open={true}
                            onClose={() => setEditing(null)}
                            title={"Update message from " + editing.name}
                        >
                            <ModalErrors />
                            <Form
                                action={"/admin/contact-messages/" + editing.id}
                                method="PUT"
                                submit="Save message"
                            >
                                <input
                                    type="hidden"
                                    name="_modal"
                                    value="contact"
                                />
                                <Field
                                    name="status"
                                    type="select"
                                    value={editing.status}
                                    options={[
                                        "pending",
                                        "read",
                                        "replied",
                                        "archived",
                                    ]}
                                />
                                <Field
                                    name="admin_notes"
                                    title="Internal notes"
                                    type="textarea"
                                    value={editing.admin_notes}
                                />
                                <button
                                    type="button"
                                    className="button secondary"
                                    onClick={() => setEditing(null)}
                                >
                                    Cancel
                                </button>
                            </Form>
                        </Modal>
                    )}
                    <div className="panel">
                        <Table
                            data={messages}
                            columns={[
                                { title: "From", key: "name" },
                                {
                                    title: "Subject",
                                    render: (r) => (
                                        <a
                                            className="table-title"
                                            href={`/admin/contact-messages/${r.id}`}
                                        >
                                            {r.subject}
                                        </a>
                                    ),
                                },
                                {
                                    title: "Status",
                                    render: (r) => <Badge>{r.status}</Badge>,
                                },
                                {
                                    title: "Actions",
                                    render: (r) => (
                                        <div className="table-actions">
                                            <a
                                                href={
                                                    "/admin/contact-messages/" +
                                                    r.id
                                                }
                                            >
                                                Open
                                            </a>
                                            <button
                                                type="button"
                                                className="table-action"
                                                onClick={() => setEditing(r)}
                                            >
                                                Update
                                            </button>
                                            <DeleteAction
                                                action={
                                                    "/admin/contact-messages/" +
                                                    r.id
                                                }
                                                name={r.subject}
                                            />
                                        </div>
                                    ),
                                },
                                {
                                    title: "Received",
                                    render: (r) => date(r.created_at),
                                },
                            ]}
                        />
                        <Pagination data={messages} />
                    </div>
                </>
            )}
        </>
    );
}
export function Analytics(p) {
    const seo = context.page.endsWith(".seo");
    return (
        <>
            <Heading
                title={seo ? "Search & engagement" : "Website analytics"}
                description={
                    seo
                        ? "Content reach and estimated engagement."
                        : "Understand how people discover your work."
                }
            >
                <a
                    className="text-link"
                    href={seo ? "/admin/analytics" : "/admin/analytics/seo"}
                >
                    {seo ? "Website analytics" : "SEO overview"} ↗
                </a>
            </Heading>
            {!seo && (
                <Form
                    action="/admin/analytics"
                    method="GET"
                    className="search-form"
                    submit="Apply"
                >
                    <Field
                        name="period"
                        title="Time period"
                        type="select"
                        value={p.period}
                        options={[
                            { value: 7, label: "Last 7 days" },
                            { value: 30, label: "Last 30 days" },
                            { value: 90, label: "Last 90 days" },
                        ]}
                    />
                </Form>
            )}
            <Stats
                stats={
                    seo
                        ? {
                              total_pages: p.totalPages,
                              average_views: Number(
                                  p.avgPageViews || 0,
                              ).toFixed(1),
                              estimated_bounce_rate: `${Number(p.bounceRate).toFixed(1)}%`,
                              pages_per_session: Number(
                                  p.avgPagesPerSession,
                              ).toFixed(1),
                          }
                        : {
                              page_views: p.totalViews,
                              unique_visitors: p.uniqueVisitors,
                              change: `${Number(p.viewsChange).toFixed(1)}%`,
                          }
                }
            />
            {!seo && (
                <div className="panel">
                    <h2>Daily page views</h2>
                    {rows(p.viewsByDay).length ? (
                        <div className="bar-chart">
                            {rows(p.viewsByDay).map((d) => (
                                <div className="bar-row" key={d.date}>
                                    <span>{date(d.date)}</span>
                                    <div>
                                        <i
                                            style={{
                                                width: `${(Number(d.views) / Math.max(...rows(p.viewsByDay).map((v) => Number(v.views)), 1)) * 100}%`,
                                            }}
                                        />
                                    </div>
                                    <strong>{d.views}</strong>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <Empty />
                    )}
                </div>
            )}
            <div className="split-panels">
                {(seo
                    ? [
                          [
                              "Most viewed articles",
                              p.mostViewedBlogs,
                              "title",
                              "views",
                          ],
                          [
                              "Most viewed services",
                              p.mostViewedServices,
                              "title",
                              "views",
                          ],
                      ]
                    : [
                          ["Top pages", p.topPages, "url", "views"],
                          ["Devices", p.deviceStats, "device_type", "count"],
                          ["Browsers", p.browserStats, "browser", "count"],
                          ["Page types", p.pageTypeStats, "page_type", "count"],
                          ["Top articles", p.topBlogs, "title", "views"],
                          ["Top services", p.topServices, "title", "views"],
                          ["Referrers", p.topReferrers, "referrer", "count"],
                      ]
                ).map(([title, data, key, count]) => (
                    <div className="panel" key={title}>
                        <h2>{title}</h2>
                        <Table
                            data={data}
                            columns={[
                                { title: "Name", key },
                                { title: "Views", key: count },
                            ]}
                        />
                    </div>
                ))}
            </div>
        </>
    );
}
