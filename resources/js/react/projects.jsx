import React from "react";
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
} from "./ui";

const admin = context.page.startsWith("admin.");
const stages = ["new", "contacted", "qualified", "won", "lost"];
const statuses = ["planning", "active", "on_hold", "completed", "cancelled"];
const owners = (assignees = [], title = "Unassigned") => [
    { value: "", label: title },
    ...assignees.map((user) => ({ value: user.id, label: user.name })),
];
const day = (value) => (value ? String(value).slice(0, 10) : "");
function Due({ value, closed = false }) {
    const overdue =
        value && !closed && day(value) < new Date().toLocaleDateString("en-CA");
    return (
        <span className={overdue ? "due-overdue" : ""}>
            {value ? date(`${day(value)}T12:00:00`) : "—"}
            {overdue && " · Overdue"}
        </span>
    );
}
function Progress({ project }) {
    return (
        <div className="project-progress">
            <progress
                max="100"
                value={project.progress}
                aria-label={`${project.title}: ${project.progress}% complete`}
            />
            <small>
                {project.completed_milestones_count} /{" "}
                {project.milestones_count} milestones · {project.progress}%
            </small>
        </div>
    );
}
function LeadInbox({ requests, assignees, stats = {}, filters }) {
    const filtered = Boolean(filters.search || filters.stage || filters.assigned_to || Number(filters.overdue));
    const metrics = [
        ["open_leads", "Open leads", "New, contacted & qualified", "◎"],
        ["qualified", "Qualified", "Ready for the next step", "✓"],
        ["overdue_follow_ups", "Overdue follow-ups", "Open leads needing attention", "◷"],
        ["won", "Won leads", "Closed as won", "↗"],
    ];
    return (
        <div className="lead-inbox">
            <div className="lead-metrics" aria-label="Overall pipeline summary">
                {metrics.map(([key, title, hint, icon]) => (
                    <article className={`lead-metric ${key === "overdue_follow_ups" && stats[key] > 0 ? "needs-attention" : ""}`} key={key}>
                        <div><span>{title}</span><span className="lead-metric-icon" aria-hidden="true">{icon}</span></div>
                        <strong>{Number(stats[key] || 0).toLocaleString()}</strong>
                        <small>{hint}</small>
                    </article>
                ))}
            </div>
            <section className="panel lead-list" aria-labelledby="requests-heading">
                <div className="lead-list-heading">
                    <div><h2 id="requests-heading">Request inbox <span className="lead-count">{requests?.total || 0}</span></h2><p>Review inquiries, assign an owner, and plan the next follow-up.</p></div>
                    <span className="lead-sort">Newest first</span>
                </div>
                <Form action="/admin/requests" method="GET" className="lead-filters" submit={null}>
                    <div className="lead-filter-fields">
                        <Field name="search" type="search" title="Search requests" placeholder="Name, email or company" value={filters.search} maxLength={200} />
                        <Field name="stage" title="Lead stage" type="select" value={filters.stage} options={[{ value: "", label: "All stages" }, ...stages]} />
                        <Field name="assigned_to" title="Assigned owner" type="select" value={filters.assigned_to} options={owners(assignees, "All owners")} />
                    </div>
                    <div className="lead-filter-actions">
                        <Field name="overdue" title="Overdue follow-ups only" type="checkbox" value={filters.overdue} />
                        <div>{filtered && <a className="text-link" href="/admin/requests">Clear filters</a>}<button className="button" type="submit">Apply filters <span aria-hidden="true">→</span></button></div>
                    </div>
                </Form>
                {rows(requests).length ? (
                    <div className="lead-table-wrap">
                        <table className="lead-table">
                            <caption className="lead-sr-only">Customer requests, newest first</caption>
                            <thead><tr>{["Customer / service", "Stage / priority", "Owner", "Follow-up", ""].map((title, i) => <th scope="col" key={i}>{title || <span className="lead-sr-only">Actions</span>}</th>)}</tr></thead>
                            <tbody>{rows(requests).map((r) => (
                                <tr key={r.id}>
                                    <td className="lead-customer-cell">
                                        <div className="lead-customer">
                                            <span className="lead-avatar" aria-hidden="true">{r.name.trim().split(/\s+/).slice(0, 2).map(part => part[0]).join("")}</span>
                                            <div><a className="table-title" href={`/admin/requests/${r.id}`}>{r.name}</a><span className="table-subtitle">{r.company || r.email}</span><span className="lead-service">{r.service?.title || "Service unavailable"}</span></div>
                                        </div>
                                    </td>
                                    <td data-label="Stage / priority"><div className="lead-status"><Badge>{r.lead_stage}</Badge><span className={`lead-priority ${r.priority}`}><span aria-hidden="true">●</span> {r.priority} priority</span></div></td>
                                    <td data-label="Owner"><span className={!r.assignee ? "lead-muted" : ""}>{r.assignee?.name || "Unassigned"}</span></td>
                                    <td data-label="Follow-up">{r.follow_up_on ? <Due value={r.follow_up_on} closed={["won", "lost"].includes(r.lead_stage)} /> : <span className="lead-muted">Not scheduled</span>}</td>
                                    <td className="lead-row-actions"><a className="lead-review" href={`/admin/requests/${r.id}`} aria-label={`Review request from ${r.name}`}>Review <span aria-hidden="true">→</span></a>{r.project && <a className="lead-project" href={`/admin/projects/${r.project.id}`}>View project ↗</a>}</td>
                                </tr>
                            ))}</tbody>
                        </table>
                    </div>
                ) : (
                    <div className="lead-empty"><span aria-hidden="true">◎</span><h3>{filtered ? "No requests match your filters" : "Your inbox is clear"}</h3><p>{filtered ? "Try a different search, stage, or owner to find the request you need." : "New service inquiries will appear here, ready for your team to follow up."}</p>{filtered && <a className="button" href="/admin/requests">Clear filters</a>}</div>
                )}
                <footer className="lead-list-footer"><span>{requests?.total ? `Showing ${requests.from}–${requests.to} of ${requests.total} requests` : "0 requests"}{filtered && " · Filtered results"}</span><Pagination data={requests} /></footer>
            </section>
        </div>
    );
}
export function Leads({ requests, request, assignees, stats, filters = {} }) {
    return (
        <>
            <Heading
                eyebrow="CUSTOMER PIPELINE"
                title={request ? request.name : "Leads & requests"}
                description={
                    request
                        ? "Coordinate follow-ups and turn an approved request into a project."
                        : "Assign every inquiry, follow up on time, and track your next opportunity."
                }
            >
                {request && (
                    <a className="text-link" href="/admin/requests">
                        ← All leads
                    </a>
                )}
            </Heading>
            {!request ? (
                <LeadInbox requests={requests} assignees={assignees} stats={stats} filters={filters} />
            ) : (
                <>
                    <div className="split-panels">
                        <article className="panel">
                            <Badge>{request.lead_stage}</Badge>
                            <h2>
                                {request.service?.title ||
                                    "Service unavailable"}
                            </h2>
                            <p>
                                <a href={`mailto:${request.email}`}>
                                    {request.email}
                                </a>
                                <br />
                                {request.phone}
                                <br />
                                {request.company}
                            </p>
                            <p className="preserve">{request.message}</p>
                            <small>Received {date(request.created_at)}</small>
                            {request.project && (
                                <p>
                                    <a
                                        className="button"
                                        href={`/admin/projects/${request.project.id}`}
                                    >
                                        Open {request.project.title} ↗
                                    </a>
                                </p>
                            )}
                        </article>
                        <Form
                            action={`/admin/requests/${request.id}/lead`}
                            method="PATCH"
                            className="panel"
                            submit="Save lead"
                        >
                            <h2>Follow-up plan</h2>
                            <Field
                                name="lead_stage"
                                title="Lead stage"
                                type="select"
                                value={request.lead_stage}
                                options={request.project ? ["won"] : stages}
                            />
                            <Field
                                name="assigned_to"
                                title="Assigned to"
                                type="select"
                                value={request.assigned_to}
                                options={owners(assignees)}
                            />
                            <div className="form-grid">
                                <Field
                                    name="priority"
                                    type="select"
                                    value={request.priority}
                                    options={["low", "normal", "high"]}
                                />
                                <Field
                                    name="follow_up_on"
                                    title="Follow-up date"
                                    type="date"
                                    value={day(request.follow_up_on)}
                                />
                            </div>
                            <Field
                                name="admin_notes"
                                title="Internal notes (team only)"
                                type="textarea"
                                value={request.admin_notes}
                                maxLength={20000}
                            />
                        </Form>
                    </div>
                    {!request.project && (
                        <Form
                            action={`/admin/requests/${request.id}/project`}
                            className="panel"
                            submit="Create project"
                            confirm="Convert this lead into a project?"
                        >
                            <h2>Start delivery</h2>
                            <p>
                                Creates one project and marks the lead as won.{" "}
                                {request.user_id
                                    ? "The linked customer can access their project workspace."
                                    : "This is a guest inquiry. Link a registered customer account in project settings when ready."}
                            </p>
                            <Field
                                name="title"
                                title="Project title"
                                value={`${request.service?.title || "Project"} — ${request.company || request.name}`}
                                required
                                maxLength={255}
                            />
                            <Field
                                name="description"
                                title="Project scope (shared with customer)"
                                type="textarea"
                                required={false}
                                maxLength={20000}
                            />
                            <Field
                                name="due_on"
                                title="Target delivery date"
                                type="date"
                            />
                        </Form>
                    )}
                </>
            )}
        </>
    );
}
export function Projects({ projects, assignees, stats, filters = {} }) {
    const prefix = admin ? "/admin/projects" : "/projects";
    return (
        <section className={admin ? "" : "section"}>
            <Heading
                eyebrow="PROJECT WORKSPACE"
                title={admin ? "Projects" : "Your projects"}
                description="Keep scope, milestones, decisions, and deliverables together."
            >
                <a
                    className="text-link"
                    href={admin ? "/admin/requests" : "/dashboard"}
                >
                    {admin ? "Create from a lead ↗" : "← Your dashboard"}
                </a>
            </Heading>
            {stats && <Stats stats={stats} />}
            {admin && (
                <Form
                    action={prefix}
                    method="GET"
                    className="panel workflow-filters"
                    submit="Apply filters"
                >
                    <Field
                        name="search"
                        title="Search projects or customers"
                        value={filters.search}
                    />
                    <Field
                        name="status"
                        type="select"
                        value={filters.status}
                        options={[
                            { value: "", label: "All statuses" },
                            ...statuses,
                        ]}
                    />
                    <Field
                        name="assigned_to"
                        title="Owner"
                        type="select"
                        value={filters.assigned_to}
                        options={owners(assignees, "All owners")}
                    />
                    <Field
                        name="overdue"
                        title="Overdue only"
                        type="checkbox"
                        value={filters.overdue}
                    />
                    <a className="text-link" href={prefix}>
                        Reset
                    </a>
                </Form>
            )}
            <div className="panel">
                <Table
                    data={projects}
                    empty="No projects yet"
                    columns={[
                        {
                            title: "Project",
                            render: (p) => (
                                <a
                                    className="table-title"
                                    href={`${prefix}/${p.id}`}
                                >
                                    {p.title}
                                    {admin && (
                                        <small className="table-subtitle">
                                            {p.customer_name}
                                        </small>
                                    )}
                                </a>
                            ),
                        },
                        {
                            title: "Status",
                            render: (p) => <Badge>{p.status}</Badge>,
                        },
                        {
                            title: "Progress",
                            render: (p) => <Progress project={p} />,
                        },
                        {
                            title: "Owner",
                            render: (p) => p.assignee?.name || "Unassigned",
                        },
                        {
                            title: "Due",
                            render: (p) => (
                                <Due
                                    value={p.due_on}
                                    closed={["completed", "cancelled"].includes(
                                        p.status,
                                    )}
                                />
                            ),
                        },
                    ]}
                />
                <Pagination data={projects} />
            </div>
        </section>
    );
}
function MilestoneFields({ milestone = {} }) {
    return (
        <>
            <Field
                name="title"
                title="Milestone title"
                value={milestone.title}
                required
                maxLength={255}
            />
            <Field
                name="description"
                title="Deliverable / acceptance criteria"
                value={milestone.description}
                type="textarea"
                maxLength={10000}
            />
            <div className="form-grid">
                <Field
                    name="due_on"
                    title="Due date"
                    type="date"
                    value={day(milestone.due_on)}
                />
                <Field
                    name="status"
                    title="Milestone status"
                    type="select"
                    value={
                        milestone.status === "changes_requested"
                            ? "in_progress"
                            : milestone.status || "pending"
                    }
                    options={[
                        "pending",
                        "in_progress",
                        "awaiting_approval",
                        "completed",
                    ]}
                />
            </div>
            <Field
                name="requires_approval"
                title="Customer approval required"
                type="checkbox"
                value={milestone.requires_approval}
            />
            <small>
                For approval milestones, select “awaiting approval” when the
                deliverable is ready. Editing approved work requires a new
                review.
            </small>
        </>
    );
}
export function ProjectDetail({
    project,
    milestones,
    updates,
    files,
    assignees,
    linkedCustomerEmail,
}) {
    const base = `/projects/${project.id}`;
    const closed = ["completed", "cancelled"].includes(project.status);
    return (
        <section className={admin ? "" : "section"}>
            <Heading
                eyebrow="PROJECT WORKSPACE"
                title={project.title}
                description={`Project #${project.id} · ${project.customer_name}`}
            >
                <a
                    className="text-link"
                    href={admin ? "/admin/projects" : "/projects"}
                >
                    ← All projects
                </a>
            </Heading>
            <div className="panel project-overview">
                <div>
                    <Badge>{project.status}</Badge>
                    <p className="preserve">
                        {project.description ||
                            "Project scope will appear here."}
                    </p>
                </div>
                <div>
                    <Progress project={project} />
                    <p>
                        Owner: {project.assignee?.name || "Unassigned"}
                        <br />
                        Due: <Due value={project.due_on} closed={closed} />
                    </p>
                </div>
            </div>
            {admin && (
                <details className="panel workflow-settings">
                    <summary>Project settings & internal notes</summary>
                    <Form
                        action={`/admin/projects/${project.id}`}
                        method="PATCH"
                        submit="Save project"
                    >
                        <Field
                            name="title"
                            title="Project title"
                            value={project.title}
                            required
                            maxLength={255}
                        />
                        <Field
                            name="description"
                            title="Scope (shared with customer)"
                            type="textarea"
                            value={project.description}
                            maxLength={20000}
                        />
                        <div className="form-grid">
                            <Field
                                name="status"
                                title="Project status"
                                type="select"
                                value={project.status}
                                options={statuses}
                            />
                            <Field
                                name="due_on"
                                title="Target delivery date"
                                type="date"
                                value={day(project.due_on)}
                            />
                        </div>
                        <Field
                            name="assigned_to"
                            title="Project owner"
                            type="select"
                            value={project.assigned_to}
                            options={owners(assignees)}
                        />
                        <Field
                            name="customer_account_email"
                            title="Linked customer account email"
                            type="email"
                            value={linkedCustomerEmail}
                        />
                        <p>
                            Only the registered account linked here can see this
                            project, its updates, and its files. Leave blank to
                            keep it team-only. Changing this grants access to
                            the new account.
                        </p>
                        <Field
                            name="internal_notes"
                            title="Internal notes (team only)"
                            type="textarea"
                            value={project.internal_notes}
                            maxLength={20000}
                        />
                        {project.service_request_id && (
                            <a
                                className="text-link"
                                href={`/admin/requests/${project.service_request_id}`}
                            >
                                View original lead ↗
                            </a>
                        )}
                    </Form>
                </details>
            )}
            <section className="panel">
                <div className="panel-heading">
                    <h2>Milestones</h2>
                    <span>
                        {project.completed_milestones_count} of{" "}
                        {project.milestones_count} complete
                    </span>
                </div>
                {!rows(milestones).length && (
                    <p>No milestones have been added yet.</p>
                )}
                <ol className="milestone-list">
                    {rows(milestones).map((m) => (
                        <li key={m.id}>
                            <div className="milestone-heading">
                                <h3>{m.title}</h3>
                                <Badge>{m.status}</Badge>
                            </div>
                            <p className="preserve">{m.description}</p>
                            <p>
                                <Due
                                    value={m.due_on}
                                    closed={closed || m.status === "completed"}
                                />
                                {m.requires_approval &&
                                    " · Customer approval required"}
                            </p>
                            {m.approval_note && (
                                <blockquote className="approval-note">
                                    Customer feedback: {m.approval_note}
                                </blockquote>
                            )}
                            {m.approved_at && (
                                <small>Approved {date(m.approved_at)}</small>
                            )}
                            {admin && !closed && (
                                <details className="milestone-editor">
                                    <summary>Edit milestone</summary>
                                    <Form
                                        action={`/admin/projects/${project.id}/milestones/${m.id}`}
                                        method="PATCH"
                                        submit="Update milestone"
                                    >
                                        <MilestoneFields milestone={m} />
                                    </Form>
                                </details>
                            )}
                            {!admin &&
                                !closed &&
                                m.status === "awaiting_approval" && (
                                    <Form
                                        action={`${base}/milestones/${m.id}/approval`}
                                        className="approval-form"
                                        submit="Submit review"
                                    >
                                        <Field
                                            name="decision"
                                            title="Your decision"
                                            type="select"
                                            required
                                            options={[
                                                {
                                                    value: "",
                                                    label: "Choose a decision",
                                                },
                                                {
                                                    value: "approve",
                                                    label: "Approve deliverable",
                                                },
                                                {
                                                    value: "request_changes",
                                                    label: "Request changes",
                                                },
                                            ]}
                                        />
                                        <Field
                                            name="approval_note"
                                            title="Feedback (required when requesting changes)"
                                            type="textarea"
                                            maxLength={5000}
                                        />
                                    </Form>
                                )}
                        </li>
                    ))}
                </ol>
                {admin && !closed && (
                    <details className="milestone-editor">
                        <summary>Add milestone</summary>
                        <Form
                            action={`/admin/projects/${project.id}/milestones`}
                            submit="Add milestone"
                        >
                            <MilestoneFields />
                        </Form>
                    </details>
                )}
            </section>
            <div className="split-panels project-collaboration">
                <section className="panel">
                    <h2>Updates</h2>
                    <Form action={`${base}/updates`} submit="Post update">
                        <Field
                            name="body"
                            title="New update"
                            type="textarea"
                            required
                            maxLength={10000}
                        />
                        {admin && (
                            <Field
                                name="is_internal"
                                title="Team only (hidden from customer)"
                                type="checkbox"
                            />
                        )}
                    </Form>
                    <div className="project-feed">
                        {rows(updates).map((update) => (
                            <article key={update.id}>
                                <strong>
                                    {update.author?.name ||
                                        "Former team member"}
                                </strong>{" "}
                                <small>
                                    {date(update.created_at)}
                                    {update.is_internal
                                        ? " · Team only"
                                        : " · Shared"}
                                </small>
                                <p className="preserve">{update.body}</p>
                            </article>
                        ))}
                    </div>
                    <Pagination data={updates} />
                </section>
                <section className="panel">
                    <h2>Shared files</h2>
                    <p>
                        Visible to the customer and project team. Up to 10 MB
                        per file: PDF, text, CSV, images, Office documents, or
                        ZIP.
                    </p>
                    <Form action={`${base}/files`} submit="Upload file">
                        <Field
                            name="file"
                            title="Choose a file"
                            type="file"
                            required
                            accept=".pdf,.txt,.csv,.jpg,.jpeg,.png,.webp,.docx,.xlsx,.zip"
                        />
                    </Form>
                    <ul className="project-files">
                        {rows(files).map((file) => (
                            <li key={file.id}>
                                <a href={`${base}/files/${file.id}`}>
                                    {file.original_name}
                                </a>
                                <small>
                                    {(file.size / 1024).toFixed(1)} KB ·{" "}
                                    {date(file.created_at)}
                                </small>
                                {(admin ||
                                    file.uploaded_by === context.user.id) && (
                                    <Form
                                        action={`${base}/files/${file.id}`}
                                        method="DELETE"
                                        submit="Remove"
                                        className="inline-form danger"
                                        confirm={`Remove ${file.original_name}?`}
                                    />
                                )}
                            </li>
                        ))}
                    </ul>
                    <Pagination data={files} />
                </section>
            </div>
        </section>
    );
}
