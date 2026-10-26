import React, { useState } from "react";
import { context, date, Heading, Field, Form, Badge, Table, Pagination, Modal, ModalErrors, DeleteAction } from "./ui";

// Admin → Users & roles. Roles bundle permissions from config/permissions.php.

function Checks({ name, options, checked = [], disabled = false }) {
    return (
        <div className="permission-checks">
            {options.map(([value, title, hint]) => (
                <label className="check-field" key={value}>
                    <input type="checkbox" name={`${name}[]`} value={value} defaultChecked={checked.includes(value)} disabled={disabled} />
                    <span>
                        {title}
                        {hint && <small>{hint}</small>}
                    </span>
                </label>
            ))}
        </div>
    );
}

export function Users({ users, roles, filters = {}, canAssignSuper }) {
    const [editing, setEditing] = useState(null);
    const [creating, setCreating] = useState(false);
    const roleOptions = (roles || []).filter((r) => canAssignSuper || !r.is_system).map((r) => [r.id, r.name]);
    return (
        <>
            <Heading eyebrow="ADMINISTRATION" title="Users" description="Give people access by assigning one or more roles. Staff roles open the admin workspace; learners only see their own account.">
                <button className="button" type="button" onClick={() => setCreating(true)}>
                    Add user +
                </button>
                {context.user.permissions.includes("roles.manage") && (
                    <a className="button secondary" href="/admin/roles">
                        Edit roles ↗
                    </a>
                )}
            </Heading>
            <div className="panel">
                <Form action="/admin/users" method="GET" className="search-form" submit="Search">
                    <Field name="q" type="search" title="Search users" value={filters.q} maxLength={100} placeholder="Name or email…" />
                    <Field name="role" type="select" title="Role" value={filters.role} options={[{ value: "", label: "All roles" }, ...(roles || []).map((r) => ({ value: r.slug, label: r.name }))]} />
                    <a className="text-link" href="/admin/users">
                        Reset
                    </a>
                </Form>
                <Table
                    data={users}
                    empty="No users match these filters"
                    columns={[
                        { title: "Name", render: (u) => <strong>{u.name}</strong> },
                        { title: "Email", key: "email" },
                        { title: "Roles", render: (u) => u.roles.map((r) => <Badge key={r}>{r}</Badge>) },
                        { title: "Access", render: (u) => (u.is_admin ? "Admin workspace" : "Account only") },
                        { title: "Joined", render: (u) => date(u.created_at) },
                        {
                            title: "Actions",
                            render: (u) => (
                                <button className="table-action" type="button" onClick={() => setEditing(u)}>
                                    Change roles
                                </button>
                            ),
                        },
                    ]}
                />
                <Pagination data={users} />
            </div>
            <Modal open={!!editing} onClose={() => setEditing(null)} title={`Roles for ${editing?.name || ""}`}>
                <ModalErrors />
                {editing && (
                    <Form action={`/admin/users/${editing.id}/roles`} method="PUT" submit="Save roles">
                        <p>{editing.email}</p>
                        <Checks name="roles" options={roleOptions} checked={editing.role_ids} />
                    </Form>
                )}
            </Modal>
            <Modal open={creating} onClose={() => setCreating(false)} title="Add user">
                <ModalErrors />
                <Form action="/admin/users" submit="Create account">
                    <Field name="name" title="Full name" required maxLength={255} />
                    <Field name="email" title="Email" type="email" required maxLength={255} />
                    <Field name="password" title="Temporary password" type="password" autoComplete="new-password" required />
                    <span className="field-title">Roles</span>
                    <Checks name="roles" options={roleOptions} checked={(roles || []).filter((r) => r.slug === "learner").map((r) => r.id)} />
                </Form>
            </Modal>
        </>
    );
}

export function Roles({ roles, groups, defaultRole }) {
    const [editing, setEditing] = useState(null);
    const permissionGroups = Object.entries(groups || {});
    return (
        <>
            <Heading eyebrow="ADMINISTRATION" title="Roles & permissions" description="Each role is a set of permissions. A person with several roles gets all of their permissions combined.">
                <button className="button" type="button" onClick={() => setEditing({ permissions: [] })}>
                    Create role +
                </button>
                <a className="button secondary" href="/admin/users">
                    Manage users ↗
                </a>
            </Heading>
            <div className="role-grid">
                {(roles || []).map((role) => (
                    <section className="panel role-card" key={role.id}>
                        <header>
                            <h2>{role.name}</h2>
                            <span>
                                {role.users_count} {role.users_count === 1 ? "person" : "people"}
                            </span>
                        </header>
                        {role.description && <p>{role.description}</p>}
                        <p className="role-summary">
                            {role.is_system ? "Every permission, always" : role.permissions?.length ? `${role.permissions.length} permissions` : "No admin access"}
                        </p>
                        <div className="row-actions">
                            {role.is_system ? (
                                <span className="muted">Locked</span>
                            ) : (
                                <>
                                    <button className="table-action" type="button" onClick={() => setEditing(role)}>
                                        Edit permissions
                                    </button>
                                    {role.slug !== defaultRole && (
                                        <DeleteAction action={`/admin/roles/${role.id}`} name={role.name} message="People with this role keep their other roles. Anyone left without a role becomes a learner." />
                                    )}
                                </>
                            )}
                        </div>
                    </section>
                ))}
            </div>
            <Modal open={!!editing} onClose={() => setEditing(null)} title={editing?.id ? `Edit ${editing.name}` : "Create role"}>
                <ModalErrors />
                {editing && (
                    <Form action={editing.id ? `/admin/roles/${editing.id}` : "/admin/roles"} method={editing.id ? "PUT" : "POST"} submit="Save role">
                        <Field name="name" title="Role name" value={editing.name} required maxLength={100} />
                        <Field name="description" title="Description" value={editing.description} maxLength={255} />
                        {permissionGroups.map(([group, items]) => (
                            <fieldset className="permission-group" key={group}>
                                <legend>{group}</legend>
                                <Checks name="permissions" options={Object.entries(items).map(([key, text]) => [key, text, key])} checked={editing.permissions || []} />
                            </fieldset>
                        ))}
                    </Form>
                )}
            </Modal>
        </>
    );
}
