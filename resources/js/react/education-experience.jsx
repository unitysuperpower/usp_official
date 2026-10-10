import {
    ApplicationTrend,
    EducationActions,
} from "./education-dashboard-tools";
import React, { useState } from "react";
import {
    rows,
    date,
    Form,
    Field,
    Heading,
    Stats,
    Table,
    Pagination,
} from "./ui";
export function CourseExtras({ course }) {
    return (
        <>
            {course.preview_video_url && (
                <section className="panel">
                    <h2>Meet your next skill</h2>
                    <p>Get a first look before you apply.</p>
                    <a
                        className="button secondary"
                        href={course.preview_video_url}
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Watch course preview ↗
                    </a>
                </section>
            )}
            {course.instructor_bio && (
                <section className="panel">
                    <span className="eyebrow">YOUR INSTRUCTOR</span>
                    <h2>{course.instructor}</h2>
                    <p className="preserve">{course.instructor_bio}</p>
                </section>
            )}
            {course.faq && (
                <section className="panel">
                    <h2>Questions before you start?</h2>
                    <p className="preserve">{course.faq}</p>
                </section>
            )}
        </>
    );
}
export function ExperienceFields({ course = {} }) {
    return (
        <details className="experience-editor" open>
            <summary>Preview, instructor & frequently asked questions</summary>
            <Field
                name="preview_video_url"
                title="Public course preview video (HTTPS)"
                type="url"
                value={course.preview_video_url}
                maxLength={2000}
            />
            <Field
                name="instructor_bio"
                title="About the instructor"
                type="textarea"
                value={course.instructor_bio}
                maxLength={5000}
            />
            <Field
                name="faq"
                title="Frequently asked questions and answers"
                type="textarea"
                value={course.faq}
                maxLength={10000}
            />
            <p>Leave unused sections blank.</p>
        </details>
    );
}
export function CourseReadiness({ course }) {
    if (!course) return null;
    const checks = [
        ["Course details", !!course.summary && !!course.description],
        ["Course artwork", !!course.cover_image],
        ["Instructor profile", !!course.instructor_bio],
        ["Published curriculum", course.lessons?.some((l) => l.is_published)],
        [
            "Open batch",
            course.batches?.some(
                (b) =>
                    b.is_open &&
                    b.applications_close_on?.slice(0, 10) >=
                        new Date().toISOString().slice(0, 10),
            ),
        ],
    ];
    return (
        <section className="panel">
            <div className="panel-heading">
                <h2>Launch checklist</h2>
                <span>
                    {checks.filter((c) => c[1]).length} / {checks.length} ready
                </span>
            </div>
            <div className="readiness-grid">
                {checks.map(([title, ready]) => (
                    <span key={title} className={ready ? "ready" : ""}>
                        {ready ? "✓" : "○"} {title}
                    </span>
                ))}
            </div>
            <p>A theme graphic is available when no cover is uploaded.</p>
        </section>
    );
}
export function Announcements({ items = [], batchId }) {
    return (
        <>
            {batchId && (
                <Form
                    className="panel"
                    action={
                        "/admin/education/batches/" + batchId + "/announcements"
                    }
                    submit="Publish announcement"
                >
                    <h2>Batch announcements</h2>
                    <p>
                        Visible to active and completed students in this batch.
                    </p>
                    <Field
                        name="title"
                        title="Announcement title"
                        required
                        maxLength={255}
                    />
                    <Field
                        name="body"
                        title="Message"
                        type="textarea"
                        required
                        maxLength={10000}
                    />
                </Form>
            )}
            {items.length > 0 && (
                <section className="panel">
                    <h2>Class announcements</h2>
                    {items.map((item) => (
                        <article className="announcement" key={item.id}>
                            <small>{date(item.created_at)}</small>
                            <h3>{item.title}</h3>
                            <p className="preserve">{item.body}</p>
                            {batchId && (
                                <Form
                                    action={
                                        "/admin/education/batches/" +
                                        batchId +
                                        "/announcements/" +
                                        item.id
                                    }
                                    method="DELETE"
                                    confirm="Remove this announcement?"
                                    submit="Remove announcement"
                                />
                            )}
                        </article>
                    ))}
                </section>
            )}
        </>
    );
}
export function Deadlines({ items = [] }) {
    if (!items.length) return null;
    return (
        <section className="panel">
            <h2>Your next assignments</h2>
            <div className="learning-session-list">
                {items.map((item) => (
                    <article key={item.id}>
                        <div>
                            <h3>{item.title}</h3>
                            <p>
                                {item.course_title} ·{" "}
                                {new Date(item.due_at) < new Date()
                                    ? "Deadline passed"
                                    : "Due"}{" "}
                                {new Date(item.due_at).toLocaleString()}
                            </p>
                        </div>
                        <a
                            href={
                                "/learn/" +
                                item.enrollment_id +
                                "#learning-activities"
                            }
                            className="text-link"
                        >
                            View assignment →
                        </a>
                    </article>
                ))}
            </div>
        </section>
    );
}
export function LessonPlayer({ enrollment, lessons, completed, notes = {} }) {
    const list = rows(lessons),
        query = Number(new URLSearchParams(location.search).get("lesson"));
    const [selected, setSelected] = useState(
        list.find((l) => l.id === query)?.id ||
            list.find((l) => !completed.includes(l.id))?.id ||
            list[0]?.id,
    );
    const [dirty, setDirty] = useState(false);
    const lesson = list.find((l) => l.id === selected);
    if (!lesson) return null;
    const index = list.indexOf(lesson);
    function select(item) {
        if (dirty && !window.confirm("Leave without saving your notes?"))
            return;
        setDirty(false);
        setSelected(item.id);
        const url = new URL(location.href);
        url.searchParams.set("lesson", item.id);
        history.replaceState(null, "", url);
    }
    return (
        <section className="lesson-studio" id="lesson-player">
            <aside className="lesson-outline">
                <div className="lesson-outline-heading">
                    <span className="eyebrow">YOUR CURRICULUM</span>
                    <h2>
                        {
                            completed.filter((id) =>
                                list.some((l) => l.id === id),
                            ).length
                        }{" "}
                        / {list.length} complete
                    </h2>
                </div>
                <nav aria-label="Course lessons">
                    {list.map((l, i) => (
                        <button
                            type="button"
                            key={l.id}
                            aria-current={
                                l.id === selected ? "step" : undefined
                            }
                            onClick={() => select(l)}
                        >
                            <span>
                                {completed.includes(l.id)
                                    ? "✓"
                                    : String(i + 1).padStart(2, "0")}
                            </span>
                            <span>
                                {l.title}
                                <small>{l.duration_minutes} min</small>
                            </span>
                        </button>
                    ))}
                </nav>
            </aside>
            <article className="lesson-content" key={lesson.id}>
                <span className="eyebrow">
                    LESSON {index + 1} OF {list.length} ·{" "}
                    {lesson.duration_minutes} MIN
                </span>
                <h2>{lesson.title}</h2>
                {lesson.video_url && (
                    <a
                        href={lesson.video_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="lesson-video-link"
                    >
                        <span aria-hidden="true">▶</span>
                        <div>
                            <strong>Watch this lesson</strong>
                            <small>
                                Opens the instructor’s video in a new tab ↗
                            </small>
                        </div>
                    </a>
                )}
                <p className="preserve">{lesson.content}</p>
                {lesson.resource_url && (
                    <a
                        className="button secondary"
                        href={lesson.resource_url}
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Open lesson resources ↗
                    </a>
                )}
                <div className="lesson-navigation">
                    {index > 0 && (
                        <button
                            className="button secondary"
                            onClick={() => select(list[index - 1])}
                        >
                            ← Previous
                        </button>
                    )}
                    {index < list.length - 1 && (
                        <button
                            className="button secondary"
                            onClick={() => select(list[index + 1])}
                        >
                            Next lesson →
                        </button>
                    )}
                </div>
                {!completed.includes(lesson.id) &&
                    enrollment.status === "active" && (
                        <Form
                            action={
                                "/learn/" +
                                enrollment.id +
                                "/lessons/" +
                                lesson.id +
                                "/complete"
                            }
                            submit="Mark lesson complete"
                        />
                    )}
                {completed.includes(lesson.id) && (
                    <p className="lesson-completed">
                        ✓ You’ve completed this lesson
                    </p>
                )}
                <Form
                    action={
                        "/learn/" +
                        enrollment.id +
                        "/lessons/" +
                        lesson.id +
                        "/note"
                    }
                    method="PUT"
                    submit="Save private notes"
                    className="lesson-notes"
                >
                    <h3>Your private notes</h3>
                    <p>
                        Only you can access these notes. Save before moving to
                        another lesson.
                    </p>
                    <Field
                        name="body"
                        title="Lesson notes"
                        type="textarea"
                        value={notes[lesson.id] || ""}
                        maxLength={20000}
                        onChange={() => setDirty(true)}
                    />
                </Form>
            </article>
        </section>
    );
}
const amount = (v, c) =>
    new Intl.NumberFormat(undefined, { style: "currency", currency: c }).format(
        Number(v) / 100,
    );
export function EducationOverview({
    stats,
    payments = [],
    outstanding = [],
    pendingPayments,
    courses,
    trend = {},
    followups = [],
}) {
    const total = Object.values(trend).reduce((a, b) => a + Number(b), 0);
    return (
        <section className="education">
            <Heading
                eyebrow="EDUCATION OPERATIONS"
                title="Learning overview"
                description="Enrollment, payments, and teaching activity in one place."
            >
                <a className="button" href="/admin/education/courses/create">
                    Create a course +
                </a>
            </Heading>
            <Stats stats={stats} />
            <EducationActions stats={stats} pendingPayments={pendingPayments} />
            <ApplicationTrend trend={trend} />
            <div className="experience-metrics">
                <section className="panel">
                    <h2>Verified payments</h2>
                    {payments.length ? (
                        payments.map((p) => (
                            <p className="metric-value" key={p.currency}>
                                {amount(p.collected, p.currency)}
                            </p>
                        ))
                    ) : (
                        <p>No approved payments yet.</p>
                    )}
                    <a href="/admin/education/payments" className="text-link">
                        {pendingPayments} payments awaiting review →
                    </a>
                </section>
                <section className="panel">
                    <h2>Outstanding fees</h2>
                    {outstanding.length ? (
                        outstanding.map((p) => (
                            <p className="metric-value" key={p.currency}>
                                {amount(p.outstanding, p.currency)}
                            </p>
                        ))
                    ) : (
                        <p>No outstanding accepted enrollments.</p>
                    )}
                    <small>
                        Accepted paid enrollments without an approved payment.
                    </small>
                </section>
                <section className="panel">
                    <h2>Last 30 days</h2>
                    <p className="metric-value">{total}</p>
                    <p>Course applications</p>
                    <a
                        className="text-link"
                        href="/admin/education/enrollments"
                    >
                        Review students & applications →
                    </a>
                </section>
            </div>
            <section className="panel">
                <h2>Course performance</h2>
                <Table
                    data={courses}
                    columns={[
                        {
                            title: "Course",
                            render: (c) => (
                                <a
                                    href={
                                        "/admin/education/courses/" +
                                        c.id +
                                        "/edit"
                                    }
                                    className="table-title"
                                >
                                    {c.title}
                                </a>
                            ),
                        },
                        {
                            title: "Published lessons",
                            render: (c) => c.lessons_count,
                        },
                        {
                            title: "Enrollments",
                            render: (c) =>
                                c.batches.reduce(
                                    (n, b) => n + b.enrollments_count,
                                    0,
                                ),
                        },
                        {
                            title: "Active",
                            render: (c) =>
                                c.batches.reduce(
                                    (n, b) => n + b.active_count,
                                    0,
                                ),
                        },
                        {
                            title: "Completed",
                            render: (c) =>
                                c.batches.reduce(
                                    (n, b) => n + b.completed_count,
                                    0,
                                ),
                        },
                        {
                            title: "Classrooms",
                            render: (c) => (
                                <div className="classroom-links">
                                    {c.batches.map((b) => (
                                        <a
                                            key={b.id}
                                            href={
                                                "/admin/education/batches/" +
                                                b.id +
                                                "/classroom"
                                            }
                                        >
                                            {b.name} →
                                        </a>
                                    ))}
                                </div>
                            ),
                        },
                    ]}
                    empty="Create your first course"
                />
                <Pagination data={courses} />
            </section>
            <section className="panel">
                <h2>Students to follow up with</h2>
                <p>
                    Active for over seven days with no recorded lesson
                    completions. In-person students may be progressing through
                    attendance instead.
                </p>
                {followups.length ? (
                    <Table
                        data={followups}
                        columns={[
                            { title: "Student", render: (e) => e.student.name },
                            {
                                title: "Course",
                                render: (e) => e.batch.course.title,
                            },
                            {
                                title: "Action",
                                render: (e) => (
                                    <a
                                        href={
                                            "/admin/education/enrollments/" +
                                            e.id
                                        }
                                    >
                                        Review learning record →
                                    </a>
                                ),
                            },
                        ]}
                    />
                ) : (
                    <p>No students match this follow-up rule.</p>
                )}
            </section>
        </section>
    );
}
