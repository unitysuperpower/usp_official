import React, { useState } from "react";
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
} from "./ui";
const resourceNames = {
    services: "Services",
    blogs: "Blog articles",
    categories: "Service categories",
    "blog-categories": "Blog categories",
};
export function AdminDashboard({ stats, recentRequests }) {
    return (
        <>
            <Heading
                eyebrow="BUSINESS OVERVIEW"
                title={`Hello, ${context.user.name.split(" ")[0]}.`}
                description="A clear view of what’s happening at USP Tech Solution."
            >
                <a className="button" href="/admin/services/create">
                    Add a service ↗
                </a>
            </Heading>
            <Stats stats={stats} />
            <div className="panel">
                <div className="panel-heading">
                    <h2>Recent service requests</h2>
                    <a href="/admin/requests" className="text-link">
                        View all →
                    </a>
                </div>
                <RequestsTable data={recentRequests} />
            </div>
            <div className="quick-links">
                <a href="/admin/blogs/create">
                    <h3>Share a fresh perspective ↗</h3>
                    <p>Write your next journal article.</p>
                </a>
                <a href="/admin/chat">
                    <h3>Keep conversations moving ↗</h3>
                    <p>Help your customers take their next step.</p>
                </a>
            </div>
        </>
    );
}
export function ResourceList({ kind, ...props }) {
    const [search, setSearch] = useState("");
    const data = props[kind === "blog-categories" ? "categories" : kind] || [];
    const categories = kind.includes("categories");
    const filtered = rows(data).filter((x) =>
        `${x.title || x.name} ${x.category?.name || ""}`
            .toLowerCase()
            .includes(search.toLowerCase()),
    );
    return (
        <>
            <Heading
                title={resourceNames[kind]}
                description="Create, refine, and manage the content your customers see."
            >
                <a className="button" href={`/admin/${kind}/create`}>
                    Create{" "}
                    {categories
                        ? "category"
                        : kind === "blogs"
                          ? "article"
                          : "service"}{" "}
                    ↗
                </a>
            </Heading>
            <div className="panel">
                <label className="field">
                    <span>Search {resourceNames[kind].toLowerCase()}</span>
                    <input
                        type="search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search by name…"
                    />
                </label>
                <Table
                    data={filtered}
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
                                    <a href={`/admin/${kind}/${r.id}/edit`}>
                                        Edit ↗
                                    </a>
                                    <Form
                                        action={`/admin/${kind}/${r.id}`}
                                        method="DELETE"
                                        submit="Delete"
                                        className="inline-form danger"
                                        confirm={`Delete “${r.title || r.name}”? This cannot be undone.`}
                                    />
                                </div>
                            ),
                        },
                    ]}
                    empty="Ready for something new?"
                />
            </div>
        </>
    );
}
export function ResourceEditor({ kind, ...props }) {
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
        Object.values(context.old.features || item.features || [""]),
    );
    return (
        <>
            <Heading
                title={`${edit ? "Edit" : "Create"} ${category ? "category" : blog ? "article" : "service"}`}
                description="Make every detail count."
            />
            <Form
                action={`/admin/${kind}${edit ? `/${item.id}` : ""}`}
                method={edit ? "PUT" : "POST"}
                className="panel editor"
                submit={edit ? "Save changes" : "Create"}
            >
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
                <Field
                    name={blog ? "content" : "description"}
                    title={
                        blog
                            ? "Article content (HTML supported)"
                            : "Description (HTML supported)"
                    }
                    type="textarea"
                    rows={10}
                    value={item.content || item.description}
                    required={!category}
                />
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
                        <input type="hidden" name="is_active" value="0" />
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
                <a className="text-link" href={`/admin/${kind}`}>
                    Cancel
                </a>
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
export function Requests({ requests, request }) {
    return (
        <>
            <Heading
                title={request ? "Service request" : "Service requests"}
                description="Turn new inquiries into meaningful partnerships."
            />
            {request ? (
                <div className="split-panels">
                    <div className="panel">
                        <Badge>{request.status}</Badge>
                        <h2>
                            {request.service?.title || "Service unavailable"}
                        </h2>
                        <h3>{request.name}</h3>
                        <p>
                            <a href={`mailto:${request.email}`}>
                                {request.email}
                            </a>
                        </p>
                        <p>
                            {request.phone} {request.company}
                        </p>
                        <p className="preserve">{request.message}</p>
                        <small>{date(request.created_at)}</small>
                    </div>
                    <Form
                        action={`/admin/requests/${request.id}/status`}
                        method="PATCH"
                        className="panel"
                    >
                        <Field
                            name="status"
                            type="select"
                            value={request.status}
                            options={[
                                "pending",
                                "in_progress",
                                "completed",
                                "cancelled",
                            ]}
                        />
                        <Field
                            name="admin_notes"
                            title="Internal notes"
                            type="textarea"
                            value={request.admin_notes}
                        />
                    </Form>
                </div>
            ) : (
                <div className="panel">
                    <RequestsTable data={requests} />
                    <Pagination data={requests} />
                </div>
            )}
        </>
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
