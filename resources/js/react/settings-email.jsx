import React from "react";
import { Heading, Form, Field, Badge, date } from "./ui";

// Admin → Settings → Email: SMTP delivery, notification switches and a test send.
export default function EmailSettings({ settings, types, mailer }) {
    const s = settings || {};
    const on = s.enabled && s.host;
    return (
        <>
            <Heading
                eyebrow="SETTINGS"
                title="Email delivery"
                description="Send order confirmations, license keys, enrollment confirmations and request alerts from your own mailbox."
            />
            <section className="store-panel">
                <div className="store-card-footer">
                    <div>
                        <strong>Status</strong>
                        <p>
                            {on
                                ? `Sending through ${s.host}:${s.port}.`
                                : `Sending is off. Emails are currently handled by the "${mailer}" mailer${mailer === "log" ? " and written to the log, not delivered" : ""}.`}
                            {s.last_tested_at ? ` Last successful test: ${date(s.last_tested_at)}.` : ""}
                        </p>
                    </div>
                    <Badge>{on ? "active" : "off"}</Badge>
                </div>
            </section>

            <section className="store-panel">
                <h2>SMTP server</h2>
                <p>
                    Use the details from your email provider. For a Hostinger mailbox: server <code>smtp.hostinger.com</code>, port <code>465</code>,
                    security SSL, username and password of the mailbox. For Gmail, create an app password and use <code>smtp.gmail.com</code>, port <code>587</code>, TLS.
                </p>
                <Form action="/admin/settings/email" method="PUT" submit="Save email settings">
                    <Field name="enabled" title="Send emails through this SMTP server" type="checkbox" value={s.enabled} />
                    <div className="store-field-grid">
                        <Field name="host" title="SMTP server" value={s.host} placeholder="smtp.hostinger.com" maxLength={255} />
                        <Field name="port" title="Port" type="number" min="1" max="65535" value={s.port || 587} required />
                    </div>
                    <div className="store-field-grid">
                        <Field
                            name="encryption"
                            title="Security"
                            type="select"
                            value={s.encryption || "tls"}
                            options={[
                                { value: "tls", label: "TLS / STARTTLS (usually port 587)" },
                                { value: "ssl", label: "SSL (usually port 465)" },
                                { value: "none", label: "None (not recommended)" },
                            ]}
                        />
                        <Field name="username" title="Username" value={s.username} autoComplete="off" maxLength={255} />
                    </div>
                    <div className="store-field-grid">
                        <Field
                            name="password"
                            title={s.has_password ? "Password (leave blank to keep the saved password)" : "Password"}
                            type="password"
                            autoComplete="new-password"
                            maxLength={500}
                        />
                        {s.has_password ? <Field name="clear_password" title="Remove the saved password" type="checkbox" /> : <span />}
                    </div>
                    <div className="store-field-grid">
                        <Field name="from_address" title="Send from (email address)" type="email" value={s.from_address} placeholder="info@usp.com.pk" />
                        <Field name="from_name" title="Sender name" value={s.from_name} placeholder="USP Tech Solution" maxLength={120} />
                    </div>
                    <Field
                        name="admin_address"
                        title="Admin alerts go to (leave blank to use the sender address)"
                        type="email"
                        value={s.admin_address}
                    />
                    <h3>Emails to send</h3>
                    <div className="store-field-grid">
                        {Object.entries(types || {}).map(([key, label]) => (
                            <Field key={key} name={`notifications[${key}]`} title={label} type="checkbox" value={s.notifications?.[key]} />
                        ))}
                    </div>
                    <Field name="current_password" title="Your admin password (to confirm)" type="password" autoComplete="current-password" required />
                </Form>
            </section>

            <section className="store-panel">
                <h2>Send a test email</h2>
                <p>Save your settings first, then send a test to check that emails arrive.</p>
                <Form action="/admin/settings/email/test" method="POST" submit="Send test email">
                    <Field name="test_to" title="Send the test to" type="email" value={s.admin_address || s.from_address} required />
                </Form>
            </section>
        </>
    );
}
