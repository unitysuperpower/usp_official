import React from "react";
import {
    context,
    rows,
    date,
    Heading,
    Stats,
    Table,
    Badge,
    Pagination,
} from "./ui";
export default function AdminOverview({
    stats,
    attention = {},
    recentRequests,
    dueProjects = [],
    leadStages = {},
}) {
    const tasks = [
        [
            "overdue_follow_ups",
            "Overdue follow-ups",
            "/admin/requests?overdue=1",
        ],
        [
            "applications_to_review",
            "Course applications",
            "/admin/education/enrollments?status=applied",
        ],
        [
            "payments_to_review",
            "Payments awaiting review",
            "/admin/education/payments",
        ],
        [
            "pending_messages",
            "New contact messages",
            "/admin/contact-messages?status=pending",
        ],
        ["unread_chat_messages", "Unread chat messages", "/admin/chat"],
    ];
    const stages = ["new", "contacted", "qualified", "won", "lost"];
    const total = Object.values(leadStages).reduce(
        (sum, value) => sum + Number(value),
        0,
    );
    return (
        <div className="admin-overview">
            <Heading
                eyebrow="YOUR WORKSPACE AT A GLANCE"
                title={"Hello, " + context.user.name.split(" ")[0] + "."}
                description="Follow up with customers, monitor delivery, and keep learning on track."
            >
                <a className="button" href="/admin/education/courses/create">
                    Create course +
                </a>
            </Heading>
            <Stats stats={stats} />
            <div className="overview-grid">
                <section className="panel">
                    <div className="panel-heading">
                        <h2>Needs your attention</h2>
                        <span className="overview-live">Current totals</span>
                    </div>
                    <div className="attention-list">
                        {tasks.map(([key, title, url]) => (
                            <a href={url} key={key}>
                                <span>{title}</span>
                                <strong
                                    className={
                                        attention[key] > 0
                                            ? "attention-count"
                                            : "attention-count clear"
                                    }
                                >
                                    {attention[key] || 0}
                                </strong>
                                <span aria-hidden="true">→</span>
                            </a>
                        ))}
                    </div>
                </section>
                <section className="panel">
                    <div className="panel-heading">
                        <h2>Lead pipeline</h2>
                        <a className="text-link" href="/admin/requests">
                            All leads →
                        </a>
                    </div>
                    <p>{total.toLocaleString()} total inquiries</p>
                    <div className="pipeline-bars">
                        {stages.map((stage) => (
                            <a
                                href={"/admin/requests?stage=" + stage}
                                key={stage}
                            >
                                <span>{stage}</span>
                                <progress
                                    aria-label={stage + " leads"}
                                    max={Math.max(total, 1)}
                                    value={leadStages[stage] || 0}
                                />
                                <strong>{leadStages[stage] || 0}</strong>
                            </a>
                        ))}
                    </div>
                </section>
            </div>
            <section className="panel">
                <div className="panel-heading">
                    <div>
                        <h2>Project deadlines</h2>
                        <p>
                            The next five open projects by due date, including
                            overdue work.
                        </p>
                    </div>
                    <a className="text-link" href="/admin/projects">
                        All projects →
                    </a>
                </div>
                <Table
                    data={dueProjects}
                    empty="No open project deadlines"
                    columns={[
                        {
                            title: "Project",
                            render: (p) => (
                                <a
                                    className="table-title"
                                    href={"/admin/projects/" + p.id}
                                >
                                    {p.title}
                                    <small className="table-subtitle">
                                        {p.customer_name}
                                    </small>
                                </a>
                            ),
                        },
                        {
                            title: "Owner",
                            render: (p) => p.assignee?.name || "Unassigned",
                        },
                        {
                            title: "Status",
                            render: (p) => <Badge>{p.status}</Badge>,
                        },
                        {
                            title: "Due",
                            render: (p) => (
                                <span
                                    className={
                                        String(p.due_on).slice(0, 10) <
                                        new Date().toLocaleDateString("en-CA")
                                            ? "due-overdue"
                                            : ""
                                    }
                                >
                                    {date(p.due_on)}
                                </span>
                            ),
                        },
                        {
                            title: "Actions",
                            render: (p) => (
                                <a
                                    className="table-action"
                                    href={"/admin/projects/" + p.id}
                                >
                                    Manage project →
                                </a>
                            ),
                        },
                    ]}
                />
            </section>
            <section className="panel">
                <div className="panel-heading">
                    <div>
                        <h2>Recent requests</h2>
                        <p>
                            Newest inquiries first. Open a request to assign it
                            or plan a follow-up.
                        </p>
                    </div>
                    <a className="text-link" href="/admin/requests">
                        Manage leads →
                    </a>
                </div>
                <Table
                    data={recentRequests}
                    empty="No service requests yet"
                    columns={[
                        {
                            title: "Customer",
                            render: (r) => (
                                <>
                                    <a
                                        className="table-title"
                                        href={"/admin/requests/" + r.id}
                                    >
                                        {r.name || r.user?.name}
                                    </a>
                                    <small className="table-subtitle">
                                        {r.company || r.email}
                                    </small>
                                </>
                            ),
                        },
                        {
                            title: "Service",
                            render: (r) => r.service?.title || "Unavailable",
                        },
                        {
                            title: "Stage",
                            render: (r) => <Badge>{r.lead_stage}</Badge>,
                        },
                        {
                            title: "Owner",
                            render: (r) => r.assignee?.name || "Unassigned",
                        },
                        {
                            title: "Received",
                            render: (r) => date(r.created_at),
                        },
                        {
                            title: "Actions",
                            render: (r) => (
                                <a
                                    className="table-action"
                                    href={"/admin/requests/" + r.id}
                                >
                                    Review →
                                </a>
                            ),
                        },
                    ]}
                />
                <Pagination data={recentRequests} />
            </section>
            <div className="overview-shortcuts">
                {[
                    [
                        "/admin/services/create",
                        "Add service",
                        "Publish what your team offers.",
                    ],
                    [
                        "/admin/blogs/create",
                        "Write an article",
                        "Share news and useful ideas.",
                    ],
                    [
                        "/admin/education/enrollments",
                        "Review students",
                        "Manage applications and enrollment.",
                    ],
                    [
                        "/admin/education/settings",
                        "Payment settings",
                        "Maintain receiving and gateway details.",
                    ],
                ].map(([url, title, description]) => (
                    <a className="panel" href={url} key={url}>
                        <strong>{title} ↗</strong>
                        <p>{description}</p>
                    </a>
                ))}
            </div>
        </div>
    );
}
