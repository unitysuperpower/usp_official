import React, { useState } from "react";
import {
    context,
    rows,
    date,
    label,
    Heading,
    Field,
    Form,
    Badge,
    Pagination,
    Table,
    Stats,
    Empty,
} from "./ui";

const modes = [
    { value: "online", label: "Online" },
    { value: "in_person", label: "In person" },
    { value: "hybrid", label: "Hybrid" },
];
const levels = ["beginner", "intermediate", "advanced"];
const statuses = [
    "applied",
    "accepted",
    "active",
    "completed",
    "rejected",
    "cancelled",
];
const fee = (item) =>
    new Intl.NumberFormat(undefined, {
        style: "currency",
        currency: item.currency || "PKR",
    }).format(Number(item.fee_minor ?? item.amount_minor ?? 0) / 100);
const day = (value) => (value ? String(value).slice(0, 10) : "");
const dayLabel = (value) => date(`${day(value)}T12:00:00`);
const adminRoot = "/admin/education";
function EducationNav() {
    const admin = context.page.startsWith("admin.");
    const links = admin
        ? [
              [`${adminRoot}/courses`, "Courses & curriculum"],
              [`${adminRoot}/enrollments`, "Applications & students"],
              [`${adminRoot}/payments`, "Payments"],
          ]
        : [
              ["/courses", "Explore courses"],
              ["/learn", "My learning"],
          ];
    return (
        <nav className="education-nav" aria-label="Education navigation">
            {links.map(([href, text]) => (
                <a
                    key={href}
                    href={href}
                    aria-current={
                        location.pathname === href ? "page" : undefined
                    }
                >
                    {text}
                </a>
            ))}
        </nav>
    );
}
function Shell({ children }) {
    return (
        <section
            className={`education ${context.page.startsWith("admin.") ? "" : "section"}`}
        >
            <EducationNav />
            {children}
        </section>
    );
}
function CourseCard({ course }) {
    return (
        <a className="education-card" href={`/courses/${course.slug}`}>
            <div className="education-card-art">
                <span>{course.category}</span>
                <span aria-hidden="true">↗</span>
                <strong>{label(course.mode)}</strong>
            </div>
            <div className="education-card-body">
                <div className="education-meta">
                    <span>{label(course.level)}</span>
                    <span>{course.duration_hours} hours</span>
                </div>
                <h2>{course.title}</h2>
                <p>{course.summary}</p>
                <small>With {course.instructor}</small>
                <div className="education-card-foot">
                    <strong>
                        {course.fee_minor ? fee(course) : "Free course"}
                    </strong>
                    <span>View course →</span>
                </div>
            </div>
        </a>
    );
}
function Catalog({ courses, filters = {} }) {
    return (
        <Shell>
            <div className="education-hero">
                <span className="eyebrow">
                    USP EDUCATION · LEARN WITH PURPOSE
                </span>
                <h1>
                    Build skills.
                    <br />
                    <em>Open new doors.</em>
                </h1>
                <p>
                    Practical learning, guided by instructors. Find your next
                    course and choose an online, in-person, or hybrid
                    experience.
                </p>
                <div className="education-hero-tags">
                    <span>Guided lessons</span>
                    <span>Scheduled batches</span>
                    <span>Completion certificates</span>
                </div>
            </div>
            <Form
                action="/courses"
                method="GET"
                className="panel education-filters"
                submit="Find courses"
            >
                <Field
                    name="search"
                    title="Find your next skill"
                    type="search"
                    placeholder="Course or subject"
                    value={filters.search}
                    maxLength={200}
                />
                <Field
                    name="mode"
                    title="Study format"
                    type="select"
                    value={filters.mode}
                    options={[{ value: "", label: "All formats" }, ...modes]}
                />
                <Field
                    name="level"
                    type="select"
                    value={filters.level}
                    options={[{ value: "", label: "All levels" }, ...levels]}
                />
                <a href="/courses" className="text-link">
                    Reset
                </a>
            </Form>
            <div className="panel-heading">
                <h2>Explore courses</h2>
                <span>{courses.total} available</span>
            </div>
            {rows(courses).length ? (
                <div className="education-cards">
                    {rows(courses).map((course) => (
                        <CourseCard course={course} key={course.id} />
                    ))}
                </div>
            ) : (
                <Empty
                    title="No courses found"
                    description="Try another search or check back for new course announcements."
                />
            )}
            <Pagination data={courses} />
        </Shell>
    );
}
function CoursePage({ course, batches, lessons, applications }) {
    return (
        <Shell>
            <Heading
                eyebrow={course.category}
                title={course.title}
                description={course.summary}
            />
            <div className="education-detail">
                <div>
                    <div className="education-overview">
                        <Badge>{course.level}</Badge>
                        <Badge>{course.mode}</Badge>
                        <span>
                            {course.duration_hours} hours · {course.instructor}
                        </span>
                    </div>
                    <article className="panel">
                        <h2>About this course</h2>
                        <p className="preserve">{course.description}</p>
                        {course.outcomes && (
                            <>
                                <h3>What you’ll learn</h3>
                                <ul className="education-outcomes">
                                    {course.outcomes
                                        .split("\n")
                                        .filter(Boolean)
                                        .map((outcome, i) => (
                                            <li key={i}>{outcome}</li>
                                        ))}
                                </ul>
                            </>
                        )}
                        {course.prerequisites && (
                            <>
                                <h3>Before you start</h3>
                                <p className="preserve">
                                    {course.prerequisites}
                                </p>
                            </>
                        )}
                    </article>
                    <section className="panel">
                        <h2>Course curriculum</h2>
                        {rows(lessons).length ? (
                            <ol className="education-curriculum">
                                {rows(lessons).map((lesson) => (
                                    <li key={lesson.id}>
                                        <span>{lesson.title}</span>
                                        <small>
                                            {lesson.duration_minutes} min
                                        </small>
                                    </li>
                                ))}
                            </ol>
                        ) : (
                            <p>
                                The instructor will share the curriculum before
                                classes begin.
                            </p>
                        )}
                    </section>
                </div>
                <aside>
                    <div className="panel education-fee">
                        <span className="eyebrow">COURSE FEE</span>
                        <strong>
                            {course.fee_minor ? fee(course) : "Free"}
                        </strong>
                        <p>
                            Apply first. Pay only after your application is
                            accepted.
                        </p>
                    </div>
                    <h2>Choose your batch</h2>
                    {!rows(batches).length && (
                        <div className="panel">
                            <p>
                                No open batches right now. Please check back for
                                the next intake.
                            </p>
                        </div>
                    )}
                    {rows(batches).map((batch) => {
                        const application = rows(applications).find(
                            (a) => a.course_batch_id === batch.id,
                        );
                        const seats = Math.max(
                            0,
                            batch.capacity - batch.reserved_count,
                        );
                        return (
                            <article
                                className="panel education-batch"
                                key={batch.id}
                            >
                                <h3>{batch.name}</h3>
                                <p>
                                    {dayLabel(batch.starts_on)} –{" "}
                                    {dayLabel(batch.ends_on)}
                                </p>
                                <p>
                                    {batch.schedule}
                                    {batch.location && (
                                        <>
                                            <br />
                                            {batch.location}
                                        </>
                                    )}
                                </p>
                                <small>
                                    {seats} seats available · Apply by{" "}
                                    {dayLabel(batch.applications_close_on)}
                                </small>
                                {application ? (
                                    <a
                                        className="button"
                                        href={`/learn/${application.id}`}
                                    >
                                        View your application →
                                    </a>
                                ) : !seats ? (
                                    <p>This batch is full.</p>
                                ) : context.user?.is_admin ? (
                                    <p>
                                        Students can apply using their own
                                        account.
                                    </p>
                                ) : context.user ? (
                                    <details>
                                        <summary>
                                            Apply for this batch →
                                        </summary>
                                        <Form
                                            action={`/education/batches/${batch.id}/apply`}
                                            submit="Submit application"
                                        >
                                            <Field
                                                name="phone"
                                                title="Contact phone"
                                                type="tel"
                                                required
                                                maxLength={40}
                                            />
                                            <Field
                                                name="motivation"
                                                title="Your background and learning goals"
                                                type="textarea"
                                                required
                                                maxLength={5000}
                                            />
                                            <small>
                                                Your seat is reserved once the
                                                education team accepts your
                                                application.
                                            </small>
                                        </Form>
                                    </details>
                                ) : (
                                    <a className="button" href="/login">
                                        Sign in to apply →
                                    </a>
                                )}
                            </article>
                        );
                    })}
                </aside>
            </div>
        </Shell>
    );
}
function StudentEnrollments({ enrollments }) {
    return (
        <Shell>
            <Heading
                eyebrow="YOUR STUDENT WORKSPACE"
                title="My learning"
                description="Track applications, complete payments, and continue your courses."
            />
            {rows(enrollments).length ? (
                <div className="education-cards">
                    {rows(enrollments).map((e) => (
                        <article className="panel" key={e.id}>
                            <Badge>{e.status}</Badge>
                            <h2>{e.batch.course.title}</h2>
                            <p>
                                {e.batch.name} · {label(e.batch.course.mode)}
                            </p>
                            <p>
                                {dayLabel(e.batch.starts_on)} –{" "}
                                {dayLabel(e.batch.ends_on)}
                            </p>
                            <a className="button" href={`/learn/${e.id}`}>
                                Open workspace →
                            </a>
                        </article>
                    ))}
                </div>
            ) : (
                <Empty
                    title="Your learning journey starts here"
                    description="Explore the course catalog and apply for a batch that suits you."
                />
            )}
            <Pagination data={enrollments} />
        </Shell>
    );
}
function PaymentHistory({ enrollment, admin = false }) {
    return (
        <section className="panel">
            <h2>Payment history</h2>
            {!enrollment.payments.length && <p>No payments submitted yet.</p>}
            {enrollment.payments.map((p) => (
                <article className="education-payment" key={p.id}>
                    <div className="panel-heading">
                        <strong>{fee(p)}</strong>
                        <Badge>{p.status}</Badge>
                    </div>
                    <p>
                        {label(p.method)} · {p.reference}
                    </p>
                    <small>Submitted {date(p.created_at)}</small>
                    {p.method !== "jazzcash_online" && (
                        <p>
                            <a
                                className="text-link"
                                href={`/education/payments/${p.id}/proof`}
                            >
                                Download payment proof ↗
                            </a>
                        </p>
                    )}
                    {p.review_note && (
                        <p className="preserve">{p.review_note}</p>
                    )}
                    {p.review_history?.length > 0 && (
                        <details>
                            <summary>Previous proof reviews</summary>
                            {p.review_history.map((review, i) => (
                                <p key={i}>
                                    {date(review.reviewed_at)} · {review.note}
                                </p>
                            ))}
                        </details>
                    )}
                    {!admin &&
                        p.status === "rejected" &&
                        p.method !== "jazzcash_online" &&
                        enrollment.status === "accepted" &&
                        !enrollment.payments.some((item) =>
                            ["pending", "approved", "review_required"].includes(
                                item.status,
                            ),
                        ) && (
                            <Form
                                action={`/education/payments/${p.id}/resubmit`}
                                submit="Resubmit corrected proof"
                            >
                                <p>
                                    Keep the same transaction reference and
                                    upload a clearer or corrected receipt. Do
                                    not transfer the money again.
                                </p>
                                <Field
                                    name="proof"
                                    title="Corrected receipt"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.pdf"
                                    required
                                />
                            </Form>
                        )}
                    {admin && p.status === "review_required" && (
                        <Form
                            action={`${adminRoot}/payments/${p.id}`}
                            method="PATCH"
                            submit="Record external resolution"
                        >
                            <input
                                type="hidden"
                                name="decision"
                                value="resolve"
                            />
                            <p>
                                Resolve this payment in the JazzCash merchant
                                portal first. This form records the outcome; it
                                does not send a refund or change course access.
                            </p>
                            <Field
                                name="merchant_verified"
                                title="I confirmed the payment has been resolved in the merchant portal"
                                type="checkbox"
                                required
                            />
                            <Field
                                name="review_note"
                                title="Resolution and merchant reference"
                                type="textarea"
                                required
                                maxLength={5000}
                            />
                        </Form>
                    )}
                    {admin && p.status === "pending" && (
                        <Form
                            action={`${adminRoot}/payments/${p.id}`}
                            method="PATCH"
                            submit="Save verification"
                            confirm="Confirm that you checked this transfer against the receiving account."
                        >
                            <>
                                {p.method === "jazzcash_online" && (
                                    <>
                                        <p>
                                            Check the transaction in your
                                            JazzCash merchant portal. Only
                                            reject an expired checkout after
                                            confirming that no money was
                                            received.
                                        </p>
                                        <Field
                                            name="merchant_verified"
                                            title="I verified the final transaction result in the merchant portal"
                                            type="checkbox"
                                            required
                                        />
                                    </>
                                )}
                            </>
                            <Field
                                name="decision"
                                title="Payment decision"
                                type="select"
                                required
                                options={[
                                    { value: "", label: "Choose a decision" },
                                    {
                                        value: "approve",
                                        label: "Approve received payment",
                                    },
                                    { value: "reject", label: "Reject proof" },
                                ]}
                            />
                            <Field
                                name="review_note"
                                title="Verification note (required for rejection)"
                                type="textarea"
                                maxLength={5000}
                            />
                        </Form>
                    )}
                </article>
            ))}
        </section>
    );
}
function PaymentForm({ enrollment, paymentMethods = {}, jazzcashEnabled }) {
    const [method, setMethod] = useState(
        paymentMethods[context.old.method]
            ? context.old.method
            : Object.keys(paymentMethods)[0] || "",
    );
    const pending = enrollment.payments.some((p) =>
        ["pending", "approved", "review_required"].includes(p.status),
    );
    if (enrollment.status !== "accepted") return null;
    if (pending)
        return (
            <div className="panel">
                <h2>Payment awaiting confirmation</h2>
                <p>
                    Your seat is reserved. Your learning area unlocks when
                    payment is verified.
                </p>
                {enrollment.payments.some(
                    (p) =>
                        p.method === "jazzcash_online" &&
                        p.status === "pending",
                ) && (
                    <>
                        <p>
                            If you closed checkout, resume the same transaction
                            below. If charged without confirmation, contact the
                            education team with your reference before paying
                            again.
                        </p>
                        {jazzcashEnabled &&
                            enrollment.payments.some(
                                (p) =>
                                    p.method === "jazzcash_online" &&
                                    p.status === "pending" &&
                                    new Date(p.expires_at) > new Date(),
                            ) && (
                                <Form
                                    action={`/learn/${enrollment.id}/checkout`}
                                    submit="Resume JazzCash checkout"
                                />
                            )}
                    </>
                )}
            </div>
        );
    return (
        <section className="panel">
            <h2>Complete your enrollment</h2>
            <p>
                Amount due: <strong>{fee(enrollment)}</strong>
            </p>
            {jazzcashEnabled && (
                <Form
                    action={`/learn/${enrollment.id}/checkout`}
                    submit="Pay online with JazzCash"
                >
                    <p>Continue to JazzCash’s secure payment page.</p>
                </Form>
            )}
            {Object.keys(paymentMethods).length > 0 && (
                <>
                    <h3>Manual transfer</h3>
                    <label className="field">
                        <span>Payment instructions</span>
                        <select
                            value={method}
                            onChange={(e) => setMethod(e.target.value)}
                        >
                            {Object.entries(paymentMethods).map(
                                ([value, m]) => (
                                    <option key={value} value={value}>
                                        {m.label}
                                    </option>
                                ),
                            )}
                        </select>
                    </label>
                    <p className="education-instructions preserve">
                        {paymentMethods[method]?.instructions}
                    </p>
                    <Form
                        action={`/learn/${enrollment.id}/payments`}
                        submit="Submit payment proof"
                    >
                        <input type="hidden" name="method" value={method} />
                        <Field
                            name="reference"
                            title="Transfer transaction reference"
                            required
                            maxLength={100}
                        />
                        <Field
                            name="proof"
                            title="Receipt or transfer screenshot"
                            type="file"
                            accept=".jpg,.jpeg,.png,.pdf"
                            required
                        />
                        <small>
                            Pay the full amount shown above. Upload a JPG, PNG,
                            or PDF, up to 5 MB. Enrollment remains pending until
                            the team verifies receipt.
                        </small>
                    </Form>
                </>
            )}
            {!jazzcashEnabled && !Object.keys(paymentMethods).length && (
                <p>
                    Payment instructions are being arranged. Contact the
                    education team before sending money.
                </p>
            )}
        </section>
    );
}
function StudentWorkspace({
    enrollment: e,
    lessons,
    completed,
    paymentMethods,
    jazzcashEnabled,
}) {
    const canLearn = ["active", "completed"].includes(e.status);
    const count = rows(lessons).filter((l) => completed.includes(l.id)).length;
    const messages = {
        applied:
            "Your application is with the education team. You’ll see the decision here.",
        accepted:
            "Your application is accepted and your seat is reserved. Complete payment to start learning.",
        active: "You’re enrolled. Your classes and learning materials are ready below.",
        completed:
            "Congratulations! The education team has confirmed your course completion.",
        rejected:
            "Your application was not accepted. Read the team’s note below.",
        cancelled: "You withdrew this application.",
    };
    return (
        <Shell>
            <Heading
                eyebrow={`ENROLLMENT #${e.id}`}
                title={e.batch.course.title}
                description={e.batch.name}
            />
            <div className="education-status panel">
                <Badge>{e.status}</Badge>
                <p>{messages[e.status]}</p>
                {e.review_note && (
                    <blockquote className="preserve">
                        {e.review_note}
                    </blockquote>
                )}
                {e.certificate_code && (
                    <a className="button" href={`/learn/${e.id}/certificate`}>
                        View completion certificate ↗
                    </a>
                )}
            </div>
            <div className="education-detail">
                <div>
                    {canLearn && (
                        <section className="panel">
                            <h2>Your classroom</h2>
                            <p>
                                {dayLabel(e.batch.starts_on)} –{" "}
                                {dayLabel(e.batch.ends_on)}
                                <br />
                                {e.batch.schedule}
                            </p>
                            {e.batch.location && (
                                <p>Venue: {e.batch.location}</p>
                            )}
                            {e.batch.meeting_url && (
                                <a
                                    className="button"
                                    href={e.batch.meeting_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Join scheduled class ↗
                                </a>
                            )}
                            <div className="education-progress">
                                <strong>
                                    {count} of {rows(lessons).length} lessons
                                    complete
                                </strong>
                                <progress
                                    max={Math.max(1, rows(lessons).length)}
                                    value={count}
                                    aria-label="Course progress"
                                />
                            </div>
                            {!rows(lessons).length && (
                                <p>
                                    Your instructor will provide materials here.
                                    Follow your batch schedule for in-person
                                    classes.
                                </p>
                            )}
                            {rows(lessons).map((l, i) => (
                                <details
                                    className="education-lesson"
                                    key={l.id}
                                    open={i === 0}
                                >
                                    <summary>
                                        <span>
                                            {completed.includes(l.id)
                                                ? "✓"
                                                : "○"}{" "}
                                            {l.title}
                                        </span>
                                        <small>{l.duration_minutes} min</small>
                                    </summary>
                                    <p className="preserve">{l.content}</p>
                                    <div className="education-links">
                                        {l.video_url && (
                                            <a
                                                href={l.video_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                Watch lesson ↗
                                            </a>
                                        )}
                                        {l.resource_url && (
                                            <a
                                                href={l.resource_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                Open learning resource ↗
                                            </a>
                                        )}
                                    </div>
                                    {!completed.includes(l.id) && (
                                        <Form
                                            action={`/learn/${e.id}/lessons/${l.id}/complete`}
                                            submit="Mark lesson complete"
                                        />
                                    )}
                                </details>
                            ))}
                            <small>
                                Lesson progress is self-reported. Certificates
                                are issued after the education team confirms
                                completion.
                            </small>
                        </section>
                    )}
                    <PaymentForm
                        enrollment={e}
                        paymentMethods={paymentMethods}
                        jazzcashEnabled={jazzcashEnabled}
                    />
                    <PaymentHistory enrollment={e} />
                </div>
                <aside>
                    <section className="panel">
                        <h2>Enrollment details</h2>
                        <dl className="education-facts">
                            <dt>Student</dt>
                            <dd>{context.user.name}</dd>
                            <dt>Course fee</dt>
                            <dd>{e.fee_minor ? fee(e) : "Free"}</dd>
                            <dt>Instructor</dt>
                            <dd>{e.batch.course.instructor}</dd>
                            <dt>Format</dt>
                            <dd>{label(e.batch.course.mode)}</dd>
                            <dt>Applied</dt>
                            <dd>{date(e.created_at)}</dd>
                        </dl>
                        <a className="text-link" href="/chat">
                            Contact support →
                        </a>
                    </section>
                    {["applied", "accepted"].includes(e.status) &&
                        !e.payments.some((p) =>
                            ["pending", "approved", "review_required"].includes(
                                p.status,
                            ),
                        ) && (
                            <Form
                                action={`/learn/${e.id}/cancel`}
                                className="panel"
                                submit="Withdraw application"
                                confirm="Withdraw this application? This cannot be undone."
                            >
                                <p>
                                    You can withdraw before submitting payment.
                                </p>
                            </Form>
                        )}
                </aside>
            </div>
        </Shell>
    );
}
function AdminCourses({ courses, filters = {} }) {
    return (
        <Shell>
            <Heading
                title="Courses & curriculum"
                description="Create learning experiences, schedule batches, and publish your curriculum."
            >
                <a className="button" href={`${adminRoot}/courses/create`}>
                    Create course +
                </a>
            </Heading>
            <Form
                action={`${adminRoot}/courses`}
                method="GET"
                className="panel education-filters"
                submit="Search"
            >
                <Field name="search" type="search" value={filters.search} />
                <a href={`${adminRoot}/courses`}>Reset</a>
            </Form>
            <div className="panel">
                <Table
                    data={courses}
                    columns={[
                        {
                            title: "Course",
                            render: (c) => (
                                <a
                                    className="table-title"
                                    href={`${adminRoot}/courses/${c.id}/edit`}
                                >
                                    {c.title}
                                    <small className="table-subtitle">
                                        {c.category}
                                    </small>
                                </a>
                            ),
                        },
                        {
                            title: "Visibility",
                            render: (c) => (
                                <Badge>
                                    {c.is_published ? "published" : "draft"}
                                </Badge>
                            ),
                        },
                        { title: "Format", render: (c) => label(c.mode) },
                        { title: "Fee", render: fee },
                        { title: "Batches", key: "batches_count" },
                        { title: "Lessons", key: "lessons_count" },
                    ]}
                    empty="Create your first course"
                />
                <Pagination data={courses} />
            </div>
        </Shell>
    );
}
function BatchFields({ batch = {} }) {
    return (
        <>
            <Field
                name="name"
                title="Batch name"
                value={batch.name}
                required
                maxLength={255}
            />
            <div className="form-grid">
                <Field
                    name="starts_on"
                    title="Start date"
                    type="date"
                    value={day(batch.starts_on)}
                    required
                />
                <Field
                    name="ends_on"
                    title="End date"
                    type="date"
                    value={day(batch.ends_on)}
                    required
                />
                <Field
                    name="applications_close_on"
                    title="Application deadline"
                    type="date"
                    value={day(batch.applications_close_on)}
                    required
                />
                <Field
                    name="capacity"
                    title="Seat capacity"
                    type="number"
                    min={1}
                    max={100000}
                    value={batch.capacity || 20}
                    required
                />
            </div>
            <Field
                name="schedule"
                title="Schedule including time zone"
                value={batch.schedule}
                placeholder="Mon & Wed, 6–8 PM Pakistan time"
                required
                maxLength={255}
            />
            <Field
                name="location"
                title="Venue (required for in-person / hybrid)"
                value={batch.location}
                maxLength={255}
            />
            <Field
                name="meeting_url"
                title="Private class meeting link (HTTPS)"
                type="url"
                value={batch.meeting_url}
            />
            <Field
                name="is_open"
                title="Accept applications"
                type="checkbox"
                value={batch.is_open ?? true}
            />
        </>
    );
}
function LessonFields({ lesson = {} }) {
    return (
        <>
            <Field
                name="title"
                title="Lesson title"
                value={lesson.title}
                required
                maxLength={255}
            />
            <div className="form-grid">
                <Field
                    name="position"
                    title="Lesson order"
                    type="number"
                    min={1}
                    value={lesson.position || 1}
                    required
                />
                <Field
                    name="duration_minutes"
                    title="Duration in minutes"
                    type="number"
                    min={0}
                    value={lesson.duration_minutes || 0}
                    required
                />
            </div>
            <Field
                name="content"
                title="Lesson content (plain text)"
                type="textarea"
                value={lesson.content}
                required
                maxLength={100000}
            />
            <Field
                name="video_url"
                title="Video URL (HTTPS)"
                type="url"
                value={lesson.video_url}
            />
            <Field
                name="resource_url"
                title="Resource URL (HTTPS)"
                type="url"
                value={lesson.resource_url}
            />
            <Field
                name="is_published"
                title="Publish lesson to enrolled students"
                type="checkbox"
                value={lesson.is_published}
            />
        </>
    );
}
function CourseEditor({ course: c, currency = "PKR" }) {
    return (
        <Shell>
            <Heading
                title={c ? `Edit ${c.title}` : "Create a course"}
                description="Save the course first, then add batches and learning materials."
            />
            {c && (
                <div className="education-links">
                    <a href="#course-details">Course details</a>
                    <a href="#course-batches">Batches</a>
                    <a href="#course-lessons">Curriculum</a>
                    {c.is_published && (
                        <a href={`/courses/${c.slug}`}>View public page ↗</a>
                    )}
                </div>
            )}
            <Form
                id="course-details"
                action={
                    c ? `${adminRoot}/courses/${c.id}` : `${adminRoot}/courses`
                }
                method={c ? "PUT" : "POST"}
                className="panel"
                submit="Save course"
            >
                <h2>Course details</h2>
                <div className="form-grid">
                    <Field
                        name="title"
                        value={c?.title}
                        required
                        maxLength={255}
                    />
                    <Field
                        name="slug"
                        title="URL slug"
                        value={c?.slug}
                        placeholder="web-development"
                        required
                        pattern="[A-Za-z0-9_-]+"
                        maxLength={255}
                    />
                    <Field
                        name="category"
                        title="Subject / category"
                        value={c?.category}
                        required
                        maxLength={255}
                    />
                    <Field
                        name="instructor"
                        value={c?.instructor}
                        required
                        maxLength={255}
                    />
                    <Field
                        name="level"
                        type="select"
                        value={c?.level || "beginner"}
                        options={levels}
                    />
                    <Field
                        name="mode"
                        title="Delivery format"
                        type="select"
                        value={c?.mode || "online"}
                        options={modes}
                    />
                    <Field
                        name="duration_hours"
                        title="Teaching hours"
                        type="number"
                        min={1}
                        value={c?.duration_hours || 10}
                        required
                    />
                    <Field
                        name="fee"
                        title={`Course fee (${c?.currency || currency}) · 0 for free`}
                        type="number"
                        min={0}
                        step="0.01"
                        value={(c?.fee_minor || 0) / 100}
                        required
                    />
                </div>
                <Field
                    name="summary"
                    title="Short introduction"
                    type="textarea"
                    value={c?.summary}
                    required
                    maxLength={500}
                />
                <Field
                    name="description"
                    title="Full description"
                    type="textarea"
                    value={c?.description}
                    required
                    maxLength={30000}
                />
                <Field
                    name="outcomes"
                    title="Learning outcomes (one per line)"
                    type="textarea"
                    value={c?.outcomes}
                    maxLength={10000}
                />
                <Field
                    name="prerequisites"
                    title="Prerequisites"
                    type="textarea"
                    value={c?.prerequisites}
                    maxLength={5000}
                />
                <Field
                    name="is_published"
                    title="Publish in course catalog"
                    type="checkbox"
                    value={c?.is_published}
                />
                <small>
                    Unpublishing hides the catalog page. Existing students keep
                    their learning access. Fee changes apply only to new
                    applications.
                </small>
            </Form>
            {c && (
                <>
                    <section id="course-batches" className="panel">
                        <h2>Batches & schedules</h2>
                        {c.batches.map((b) => (
                            <details className="education-lesson" key={b.id}>
                                <summary>
                                    {b.name}
                                    <small>
                                        {b.reserved_count} / {b.capacity}{" "}
                                        reserved
                                    </small>
                                </summary>
                                <Form
                                    action={`${adminRoot}/courses/${c.id}/batches/${b.id}`}
                                    method="PUT"
                                    submit="Save batch"
                                >
                                    <BatchFields batch={b} />
                                </Form>
                            </details>
                        ))}
                        <details className="education-lesson">
                            <summary>Add a batch +</summary>
                            <Form
                                action={`${adminRoot}/courses/${c.id}/batches`}
                                submit="Create batch"
                            >
                                <BatchFields />
                            </Form>
                        </details>
                    </section>
                    <section id="course-lessons" className="panel">
                        <h2>Curriculum & learning materials</h2>
                        {c.lessons.map((l) => (
                            <details className="education-lesson" key={l.id}>
                                <summary>
                                    {l.position}. {l.title}
                                    <Badge>
                                        {l.is_published ? "published" : "draft"}
                                    </Badge>
                                </summary>
                                <Form
                                    action={`${adminRoot}/courses/${c.id}/lessons/${l.id}`}
                                    method="PUT"
                                    submit="Save lesson"
                                >
                                    <LessonFields lesson={l} />
                                </Form>
                            </details>
                        ))}
                        <details className="education-lesson">
                            <summary>Add a lesson +</summary>
                            <Form
                                action={`${adminRoot}/courses/${c.id}/lessons`}
                                submit="Create lesson"
                            >
                                <LessonFields />
                            </Form>
                        </details>
                    </section>
                </>
            )}
        </Shell>
    );
}
function AdminEnrollments({ enrollments, stats, filters = {} }) {
    return (
        <Shell>
            <Heading
                title="Applications & students"
                description="Review applications, reserve seats, and follow each student’s progress."
            />
            <Stats stats={stats} />
            <Form
                action={`${adminRoot}/enrollments`}
                method="GET"
                className="panel education-filters"
                submit="Filter"
            >
                <Field
                    name="search"
                    title="Student name or email"
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
                <a href={`${adminRoot}/enrollments`}>Reset</a>
            </Form>
            <div className="panel">
                <Table
                    data={enrollments}
                    columns={[
                        {
                            title: "Student",
                            render: (e) => (
                                <a
                                    className="table-title"
                                    href={`${adminRoot}/enrollments/${e.id}`}
                                >
                                    {e.student.name}
                                    <small className="table-subtitle">
                                        Application #{e.id}
                                    </small>
                                </a>
                            ),
                        },
                        {
                            title: "Course / batch",
                            render: (e) => (
                                <>
                                    {e.batch.course.title}
                                    <small className="table-subtitle">
                                        {e.batch.name}
                                    </small>
                                </>
                            ),
                        },
                        {
                            title: "Status",
                            render: (e) => <Badge>{e.status}</Badge>,
                        },
                        { title: "Fee", render: fee },
                        { title: "Applied", render: (e) => date(e.created_at) },
                    ]}
                    empty="No matching applications"
                />
                <Pagination data={enrollments} />
            </div>
        </Shell>
    );
}
function AdminReview({ enrollment: e, studentEmail, lessons, completed }) {
    return (
        <Shell>
            <Heading
                title={e.student.name}
                eyebrow={`APPLICATION #${e.id}`}
                description={`${e.batch.course.title} · ${e.batch.name}`}
            />
            <div className="education-detail">
                <div>
                    <article className="panel">
                        <Badge>{e.status}</Badge>
                        <h2>Student application</h2>
                        <p>
                            <a href={`mailto:${studentEmail}`}>
                                {studentEmail}
                            </a>
                            <br />
                            {e.phone}
                        </p>
                        <h3>Background & goals</h3>
                        <p className="preserve">{e.motivation}</p>
                        <p>
                            Fee at application: <strong>{fee(e)}</strong>
                        </p>
                        {e.review_note && (
                            <p className="preserve">
                                Decision note: {e.review_note}
                            </p>
                        )}
                    </article>
                    <PaymentHistory enrollment={e} admin />
                </div>
                <aside>
                    {e.status === "applied" && (
                        <Form
                            action={`${adminRoot}/enrollments/${e.id}`}
                            method="PATCH"
                            className="panel"
                            submit="Save decision"
                        >
                            <h2>Review application</h2>
                            <Field
                                name="decision"
                                title="Decision"
                                type="select"
                                required
                                options={[
                                    { value: "", label: "Choose a decision" },
                                    {
                                        value: "accept",
                                        label: "Accept and reserve a seat",
                                    },
                                    {
                                        value: "reject",
                                        label: "Reject application",
                                    },
                                ]}
                            />
                            <Field
                                name="review_note"
                                title="Student-facing note (required for rejection)"
                                type="textarea"
                                maxLength={5000}
                            />
                            <small>
                                Free courses unlock immediately after
                                acceptance. Paid courses unlock after verified
                                payment.
                            </small>
                        </Form>
                    )}
                    <section className="panel">
                        <h2>Learning progress</h2>
                        <p>
                            {
                                lessons.filter((l) => completed.includes(l.id))
                                    .length
                            }{" "}
                            / {lessons.length} published lessons complete
                        </p>
                        {lessons.map((l) => (
                            <p key={l.id}>
                                {completed.includes(l.id) ? "✓" : "○"} {l.title}
                            </p>
                        ))}
                        {e.status === "active" && (
                            <Form
                                action={`${adminRoot}/enrollments/${e.id}`}
                                method="PATCH"
                                submit="Complete & issue certificate"
                                confirm="Confirm this student has met the course completion requirements."
                            >
                                <input
                                    type="hidden"
                                    name="decision"
                                    value="complete"
                                />
                                <Field
                                    name="review_note"
                                    title="Completion note"
                                    type="textarea"
                                    maxLength={5000}
                                />
                                <small>
                                    Confirm attendance and assessment
                                    requirements. Online and hybrid courses also
                                    require all published lessons marked
                                    complete.
                                </small>
                            </Form>
                        )}
                        {e.certificate_code && (
                            <a
                                href={`/learn/${e.id}/certificate`}
                                className="button"
                            >
                                View certificate ↗
                            </a>
                        )}
                    </section>
                </aside>
            </div>
        </Shell>
    );
}
function AdminPayments({ payments, filters = {} }) {
    return (
        <Shell>
            <Heading
                title="Education payments"
                description="Verify transfers against your account before granting course access."
            />
            <Form
                action={`${adminRoot}/payments`}
                method="GET"
                className="panel education-filters"
                submit="Filter"
            >
                <Field
                    name="status"
                    type="select"
                    value={filters.status}
                    options={[
                        { value: "", label: "All payments" },
                        "pending",
                        "approved",
                        "rejected",
                        "review_required",
                        "resolved",
                    ]}
                />
                <a href={`${adminRoot}/payments`}>Reset</a>
            </Form>
            <div className="panel">
                <Table
                    data={payments}
                    columns={[
                        {
                            title: "Student",
                            render: (p) => (
                                <a
                                    className="table-title"
                                    href={`${adminRoot}/enrollments/${p.course_enrollment_id}`}
                                >
                                    {p.enrollment.student.name}
                                </a>
                            ),
                        },
                        {
                            title: "Course",
                            render: (p) => p.enrollment.batch.course.title,
                        },
                        { title: "Amount", render: fee },
                        {
                            title: "Method / reference",
                            render: (p) => (
                                <>
                                    {label(p.method)}
                                    <small className="table-subtitle">
                                        {p.reference}
                                    </small>
                                </>
                            ),
                        },
                        {
                            title: "Status",
                            render: (p) => <Badge>{p.status}</Badge>,
                        },
                        {
                            title: "Action",
                            render: (p) => (
                                <a
                                    href={`${adminRoot}/enrollments/${p.course_enrollment_id}`}
                                >
                                    Review enrollment →
                                </a>
                            ),
                        },
                    ]}
                    empty="No payments found"
                />
                <Pagination data={payments} />
            </div>
        </Shell>
    );
}
export default function EducationPage(props) {
    const pages = {
        "education.catalog": Catalog,
        "education.course": CoursePage,
        "education.enrollments": StudentEnrollments,
        "education.workspace": StudentWorkspace,
        "admin.education.courses": AdminCourses,
        "admin.education.editor": CourseEditor,
        "admin.education.enrollments": AdminEnrollments,
        "admin.education.review": AdminReview,
        "admin.education.payments": AdminPayments,
    };
    const Page = pages[context.page];
    return Page ? <Page {...props} /> : <Empty />;
}
