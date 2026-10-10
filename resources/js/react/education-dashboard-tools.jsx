import React, { useState } from "react";
import { Form, Field } from "./ui";
import { CourseArtwork } from "./course-visuals";
export function LearningSpotlight({ course }) {
    if (!course) return null;
    return (
        <section className="learning-spotlight">
            <div>
                <span className="eyebrow">MAKE YOUR NEXT STEP COUNT</span>
                <h2>Pick up where you left off.</h2>
                <p>{course.title}</p>
                <div className="spotlight-lesson">
                    <span aria-hidden="true">▶</span>
                    <div>
                        <strong>{course.lesson_title}</strong>
                        <small>
                            {course.duration_minutes} minutes · Next unfinished
                            lesson
                        </small>
                    </div>
                </div>
                <a
                    className="button"
                    href={
                        "/learn/" +
                        course.enrollment_id +
                        "?lesson=" +
                        course.lesson_id +
                        "#lesson-player"
                    }
                >
                    Continue learning →
                </a>
            </div>
            <div className="spotlight-art">
                <CourseArtwork course={course} />
            </div>
        </section>
    );
}
export function LearningFilters({ filters = {}, stats = {} }) {
    const total = Object.values(stats).reduce((sum, n) => sum + Number(n), 0);
    function href(status) {
        const p = new URLSearchParams();
        if (status) p.set("status", status);
        if (filters.search) p.set("search", filters.search);
        return "/learn" + (p.size ? "?" + p : "") + "#your-courses";
    }
    return (
        <div className="learning-controls" id="your-courses">
            <nav
                className="learning-status-tabs"
                aria-label="Filter your courses"
            >
                {[
                    ["", "All courses", total],
                    ["active", "Learning", stats.active || 0],
                    ["accepted", "Payment", stats.accepted || 0],
                    ["applied", "Applications", stats.applied || 0],
                    ["completed", "Completed", stats.completed || 0],
                    ["cancelled", "Withdrawn", stats.cancelled || 0],
                    ["rejected", "Not accepted", stats.rejected || 0],
                ].map(([value, title, count]) => (
                    <a
                        key={value}
                        href={href(value)}
                        aria-current={
                            (filters.status || "") === value
                                ? "page"
                                : undefined
                        }
                    >
                        {title}
                        <span>{count}</span>
                    </a>
                ))}
            </nav>
            <Form
                action="/learn#your-courses"
                method="GET"
                className="learning-search"
                submit="Search courses"
            >
                <input
                    type="hidden"
                    name="status"
                    value={filters.status || ""}
                />
                <Field
                    name="search"
                    title="Search your courses"
                    type="search"
                    value={filters.search}
                    placeholder="Course name"
                    maxLength={200}
                />
                {filters.search && (
                    <a
                        className="text-link"
                        href={
                            filters.status
                                ? "/learn?status=" + filters.status
                                : "/learn"
                        }
                    >
                        Clear search
                    </a>
                )}
            </Form>
        </div>
    );
}
export function ApplicationTrend({ trend = {} }) {
    const [range, setRange] = useState(30);
    const entries = Object.entries(trend)
        .sort(([a], [b]) => a.localeCompare(b))
        .slice(-range);
    const max = Math.max(1, ...entries.map(([, v]) => Number(v))),
        total = entries.reduce((sum, [, v]) => sum + Number(v), 0);
    return (
        <section className="panel application-trend">
            <div className="panel-heading">
                <div>
                    <span className="eyebrow">ENROLLMENT ACTIVITY</span>
                    <h2>Applications over time</h2>
                    <p>
                        {total} applications in the last {range} days · Pakistan
                        time
                    </p>
                </div>
                <div
                    className="trend-range"
                    role="group"
                    aria-label="Application chart period"
                >
                    {[7, 30].map((n) => (
                        <button
                            key={n}
                            type="button"
                            aria-pressed={range === n}
                            onClick={() => setRange(n)}
                        >
                            {n} days
                        </button>
                    ))}
                </div>
            </div>
            <div
                className="application-bars"
                role="img"
                aria-label={
                    total +
                    " applications over " +
                    range +
                    " days. Daily values are available in the table below."
                }
            >
                {entries.map(([day, count]) => (
                    <div
                        className="application-bar-column"
                        key={day}
                        title={day + ": " + count + " applications"}
                    >
                        <span
                            style={{
                                height:
                                    Math.max(2, (Number(count) / max) * 100) +
                                    "%",
                                opacity: count ? 1 : 0.2,
                            }}
                        />
                    </div>
                ))}
            </div>
            <div className="trend-axis">
                <span>{entries[0]?.[0]}</span>
                <span>{entries.at(-1)?.[0]}</span>
            </div>
            {total === 0 && (
                <p className="trend-empty">
                    New applications will appear here as students apply.
                </p>
            )}
            <details className="trend-data">
                <summary>View daily counts</summary>
                <div className="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date (PKT)</th>
                                <th>Applications</th>
                            </tr>
                        </thead>
                        <tbody>
                            {entries.map(([day, count]) => (
                                <tr key={day}>
                                    <td>{day}</td>
                                    <td>{count}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </details>
        </section>
    );
}
export function EducationActions({ stats = {}, pendingPayments = 0 }) {
    return (
        <nav
            className="education-action-grid"
            aria-label="Education quick actions"
        >
            {[
                [
                    "/admin/education/enrollments",
                    "Applications to review",
                    stats["Applications to review"] || 0,
                    "Review student applications",
                ],
                [
                    "/admin/education/payments",
                    "Payments to verify",
                    pendingPayments,
                    "Open payment review",
                ],
                [
                    "/admin/education/courses",
                    "Assignments to grade",
                    stats["Assignments to grade"] || 0,
                    "Open courses & classrooms",
                ],
            ].map(([href, title, count, action]) => (
                <a href={href} key={href}>
                    <span className="action-count">{count}</span>
                    <div>
                        <strong>{title}</strong>
                        <small>{action} →</small>
                    </div>
                </a>
            ))}
        </nav>
    );
}
