import React from "react";
import { context, Form, Field, Heading, date, label } from "./ui";
import { CourseArtwork } from "./course-visuals";
const money = (e) =>
    new Intl.NumberFormat(undefined, {
        style: "currency",
        currency: e.currency || "PKR",
    }).format(Number(e.fee_minor) / 100);
export function EnrollmentReview({ batch, seats, available }) {
    const course = batch.course;
    return (
        <section className="section education">
            <a className="text-link" href={"/courses/" + course.slug}>
                ← Back to course
            </a>
            <Heading
                eyebrow="STEP 1 · ENROLLMENT"
                title="Review your course. Start your journey."
                description="Confirm your batch and learning goals. Payment is requested only after your application is accepted."
            />
            <div className="enrollment-review-grid">
                <section className="panel enrollment-order">
                    <div className="enrollment-order-art">
                        <CourseArtwork course={course} />
                    </div>
                    <span className="eyebrow">{course.category}</span>
                    <h2>{course.title}</h2>
                    <dl className="education-facts">
                        <dt>Batch</dt>
                        <dd>{batch.name}</dd>
                        <dt>Dates</dt>
                        <dd>
                            {date(batch.starts_on)} – {date(batch.ends_on)}
                        </dd>
                        <dt>Schedule</dt>
                        <dd>{batch.schedule}</dd>
                        <dt>Format</dt>
                        <dd>{label(course.mode)}</dd>
                        {batch.location && (
                            <>
                                <dt>Venue</dt>
                                <dd>{batch.location}</dd>
                            </>
                        )}
                        <dt>Instructor</dt>
                        <dd>{course.instructor}</dd>
                        <dt>Course fee</dt>
                        <dd>
                            <strong>
                                {course.fee_minor
                                    ? money(course)
                                    : "Free course"}
                            </strong>
                        </dd>
                        <dt>Due now</dt>
                        <dd>No payment required to apply</dd>
                    </dl>
                    <small>
                        {seats} seats available · A seat is reserved after
                        acceptance.
                    </small>
                </section>
                <div>
                    {available ? (
                        <Form
                            action={"/education/batches/" + batch.id + "/apply"}
                            className="panel"
                            submit="Submit enrollment application"
                        >
                            <h2>Your enrollment details</h2>
                            <p>
                                Applying as{" "}
                                <strong>{context.user?.name}</strong>
                            </p>
                            <input
                                type="hidden"
                                name="quoted_fee_minor"
                                value={course.fee_minor}
                            />
                            <input
                                type="hidden"
                                name="quoted_currency"
                                value={course.currency}
                            />
                            <Field
                                name="phone"
                                title="Contact phone"
                                type="tel"
                                autoComplete="tel"
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
                            <div className="enrollment-explainer">
                                <h3>What happens next?</h3>
                                <ol>
                                    <li>
                                        The education team reviews your
                                        application.
                                    </li>
                                    <li>
                                        {course.fee_minor
                                            ? "After acceptance, choose a payment method and complete payment."
                                            : "Once accepted, your free course becomes available."}
                                    </li>
                                    <li>
                                        {course.fee_minor
                                            ? "Verified payment unlocks your classroom, lessons, and activities."
                                            : "Open your classroom and start learning."}
                                    </li>
                                </ol>
                            </div>
                            <p className="muted">
                                Your fee is saved with your application. Later
                                course price changes won’t change it.
                            </p>
                        </Form>
                    ) : (
                        <section className="panel">
                            <h2>This batch is not accepting applications</h2>
                            <p>
                                The deadline has passed, the batch is closed, or
                                all seats are reserved.
                            </p>
                            <a
                                className="button"
                                href={
                                    "/courses/" +
                                    course.slug +
                                    "#course-batches"
                                }
                            >
                                Choose another batch →
                            </a>
                        </section>
                    )}
                </div>
            </div>
        </section>
    );
}
export function EnrollmentJourney({
    enrollment: e,
    lessonCount = 0,
    completedCount = 0,
}) {
    const closed = ["rejected", "cancelled"].includes(e.status),
        unlocked = ["active", "completed"].includes(e.status);
    const pending = e.payments?.find((p) =>
        ["pending", "review_required"].includes(p.status),
    );
    const current =
        e.status === "applied"
            ? 0
            : e.status === "accepted"
              ? 1
              : e.status === "active"
                ? 2
                : 3;
    const steps = [
        ["Application", e.created_at ? date(e.created_at) : "Submitted"],
        [
            e.fee_minor ? "Payment" : "Approval",
            e.fee_minor
                ? pending
                    ? "Under review"
                    : unlocked
                      ? "Verified"
                      : "After acceptance"
                : "No payment needed",
        ],
        [
            "Learn",
            unlocked
                ? completedCount + " / " + lessonCount + " lessons"
                : "Unlocks after confirmation",
        ],
        [
            "Complete",
            e.status === "completed"
                ? "Certificate issued"
                : "Meet course requirements",
        ],
    ];
    let title = "Your application is being reviewed",
        text =
            "The education team will confirm your place. There is nothing to pay yet.",
        href = null,
        cta = null;
    if (e.status === "accepted") {
        title = pending
            ? "Your payment is being checked"
            : "Your place is reserved";
        text = pending
            ? "Your classroom unlocks after verification. Keep your transaction reference and avoid paying again."
            : "Complete your course payment to unlock lessons, class links, and assessments.";
        href = pending ? "#payment-history" : "#course-payment";
        cta = pending ? "View payment status" : "Continue to payment";
    }
    if (unlocked) {
        title =
            e.status === "completed"
                ? "You completed this course"
                : "Your classroom is ready";
        text =
            e.status === "completed"
                ? "Your learning materials and completion certificate remain available."
                : "Continue your lessons, attend scheduled classes, and complete your assessments.";
        href =
            e.status === "completed" && e.certificate_code
                ? "/learn/" + e.id + "/certificate"
                : lessonCount
                  ? "#lesson-player"
                  : "#classroom";
        cta =
            e.status === "completed" ? "View certificate" : "Continue learning";
    }
    if (closed) {
        title =
            e.status === "rejected"
                ? "Application not accepted"
                : "Application withdrawn";
        text =
            "Review the education team’s note below or explore another available batch.";
        href = "/courses";
        cta = "Explore courses";
    }
    return (
        <section className="lms-journey">
            <ol className="lms-steps" aria-label="Enrollment progress">
                {steps.map(([name, detail], i) => (
                    <li
                        key={name}
                        className={
                            !closed && i < current
                                ? "is-done"
                                : !closed && i === current
                                  ? "is-current"
                                  : ""
                        }
                        aria-current={
                            !closed && i === current ? "step" : undefined
                        }
                    >
                        <span className="lms-step-number">
                            {!closed && i < current ? "✓" : i + 1}
                        </span>
                        <div>
                            <strong>{name}</strong>
                            <small>{detail}</small>
                        </div>
                    </li>
                ))}
            </ol>
            <div className="lms-next">
                <div>
                    <span className="eyebrow">YOUR NEXT STEP</span>
                    <h2>{title}</h2>
                    <p>{text}</p>
                </div>
                {href && (
                    <a className="button" href={href}>
                        {cta} →
                    </a>
                )}
                {pending && (
                    <button
                        type="button"
                        className="button secondary"
                        onClick={() => location.reload()}
                    >
                        Check for updates
                    </button>
                )}
            </div>
        </section>
    );
}
export function CompletionChecklist({
    enrollment: e,
    lessons = [],
    completed = [],
    learning = {},
}) {
    if (!["active", "completed"].includes(e.status)) return null;
    const total = lessons.length,
        done = lessons.filter((l) => completed.includes(l.id)).length;
    const assignments = (learning.assignments || []).filter(
            (a) => a.is_required,
        ),
        quizzes = (learning.quizzes || []).filter((q) => q.is_required),
        attendance = learning.attendanceSummary;
    const passedAssignments = assignments.filter((a) =>
        (learning.submissions || []).some(
            (s) =>
                s.education_assignment_id === a.id &&
                s.status === "graded" &&
                Number(s.score) >= Number(a.pass_percent),
        ),
    ).length;
    const passedQuizzes = quizzes.filter((q) =>
        (learning.attempts || []).some(
            (a) => a.education_quiz_id === q.id && a.passed,
        ),
    ).length;
    const items = [
        {
            title: "Published lessons",
            detail: done + " of " + total + " completed",
            ready:
                e.batch.course.mode === "in_person" ||
                (total > 0 && done === total),
            href: "#lesson-player",
        },
        {
            title: "Required assignments",
            detail: passedAssignments + " of " + assignments.length + " passed",
            ready: passedAssignments === assignments.length,
            href: "#learning-activities",
        },
        {
            title: "Required quizzes",
            detail: passedQuizzes + " of " + quizzes.length + " passed",
            ready: passedQuizzes === quizzes.length,
            href: "#learning-activities",
        },
        {
            title: "Attendance",
            detail: attendance?.required
                ? String(attendance.percent ?? 0) +
                  "% recorded · " +
                  attendance.required +
                  "% required"
                : "No minimum attendance set",
            ready:
                !attendance?.required ||
                (attendance.percent !== null &&
                    attendance.percent >= attendance.required),
            href: "#learning-activities",
        },
    ];
    return (
        <section className="panel completion-checklist">
            <span className="eyebrow">YOUR PATH TO COMPLETION</span>
            <h2>Course milestones</h2>
            {items.map((item) => (
                <a href={item.href} key={item.title}>
                    <span
                        className={
                            item.ready ? "milestone-ready" : "milestone-pending"
                        }
                        aria-label={
                            item.ready ? "Requirement met" : "In progress"
                        }
                    >
                        {item.ready ? "✓" : "○"}
                    </span>
                    <div>
                        <strong>{item.title}</strong>
                        <small>{item.detail}</small>
                    </div>
                </a>
            ))}
            <p>
                {e.status === "completed"
                    ? "Your completion has been confirmed."
                    : items.every((i) => i.ready)
                      ? "Your recorded requirements are met. Contact the education team for final review and certification."
                      : "Complete the course requirements before the education team confirms your certificate."}
            </p>
        </section>
    );
}
