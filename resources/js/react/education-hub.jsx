import React, { useEffect, useRef, useState } from "react";
import { context, rows, date, label, Form, Empty, api } from "./ui";
import { LearningActivities } from "./education-learning";
import { Announcements } from "./education-experience";
import { CompletionChecklist } from "./lms-journey";
import { LevelChip, ProgressRing, PaceBar, LessonBody, LessonVideo, ago, minutes } from "./education-insights";

// Student course hub for active and completed enrollments: one focused screen per task instead of
// one long page. The selected tab and lesson live in the URL (?tab=lessons&lesson=12).

const TABS = [
    ["overview", "Overview"],
    ["lessons", "Lessons"],
    ["classes", "Classes"],
    ["assignments", "Assignments"],
    ["quizzes", "Quizzes"],
    ["details", "Details"],
];
const when = (value) => new Date(value).toLocaleString(undefined, { weekday: "short", day: "numeric", month: "short", hour: "numeric", minute: "2-digit" });

function setQuery(params) {
    const url = new URL(location.href);
    Object.entries(params).forEach(([k, v]) => (v === null ? url.searchParams.delete(k) : url.searchParams.set(k, v)));
    history.replaceState(null, "", url);
}

function TodoList({ items = [], go, meetingUrl }) {
    if (!items.length)
        return (
            <div className="todo-empty">
                <span aria-hidden="true">✓</span>
                <p>You’re all caught up. New lessons, classes and deadlines will show up here.</p>
            </div>
        );
    const icon = { class: "◷", assignment: "✎", lesson: "▶", quiz: "?" };
    return (
        <ol className="todo-list">
            {items.map((item, i) => {
                const action =
                    item.type === "class" && item.live && meetingUrl ? (
                        <a className="button small" href={meetingUrl} target="_blank" rel="noopener noreferrer">
                            Join now ↗
                        </a>
                    ) : (
                        <button
                            type="button"
                            className={i === 0 ? "button small" : "button small secondary"}
                            onClick={() =>
                                item.type === "lesson"
                                    ? go("lessons", item.id)
                                    : go({ class: "classes", assignment: "assignments", quiz: "quizzes" }[item.type], null, item.type === "class" ? null : `${item.type}-${item.id}`)
                            }
                        >
                            {{ lesson: "Start", assignment: "Open", quiz: "Take quiz", class: "Details" }[item.type]}
                        </button>
                    );
                return (
                    <li key={item.type + (item.id || item.at)} className={`todo-item todo-${item.type} ${item.overdue || item.live ? "urgent" : ""}`}>
                        <span className="todo-icon" aria-hidden="true">
                            {icon[item.type]}
                        </span>
                        <div>
                            <strong>{item.title}</strong>
                            <small>
                                {item.text}
                                {item.at && ` · ${when(item.at)}`}
                                {item.minutes ? ` · ${item.minutes} min` : ""}
                            </small>
                        </div>
                        {action}
                    </li>
                );
            })}
        </ol>
    );
}

function PaceCard({ insight }) {
    const message = {
        on_track: "Nice work — you’re keeping pace with your class.",
        watch: "You’re slipping a little behind. A short session today will catch you up.",
        at_risk: "You’ve fallen behind. Start with the first item in your plan, or message your instructor for help.",
        completed: "You’ve completed this course.",
    }[insight.level];
    return (
        <section className="panel pace-card">
            <div className="theme-section-heading">
                <h2>Your pace</h2>
                <LevelChip level={insight.level} student />
            </div>
            <p>{message}</p>
            <PaceBar progress={insight.progress ?? 0} expected={insight.expected} />
            {insight.reasons.length > 0 && insight.level !== "completed" && (
                <ul className="insight-reasons">
                    {insight.reasons.map((r) => (
                        <li key={r}>{r.replace(/^(\d+) lessons behind/, "You are $1 lessons behind")}</li>
                    ))}
                </ul>
            )}
            <dl className="hub-facts">
                <div>
                    <dt>Attendance</dt>
                    <dd>{insight.attendance === null ? "—" : `${insight.attendance}%`}</dd>
                </div>
                <div>
                    <dt>Average score</dt>
                    <dd>{insight.average_score === null ? "—" : `${insight.average_score}%`}</dd>
                </div>
                <div>
                    <dt>Quizzes passed</dt>
                    <dd>
                        {insight.quizzes_passed}/{insight.quizzes_total}
                    </dd>
                </div>
                <div>
                    <dt>Time left</dt>
                    <dd>{minutes(insight.remaining_minutes)}</dd>
                </div>
            </dl>
        </section>
    );
}

function NotesBox({ enrollment, lesson, initial }) {
    const [value, setValue] = useState(initial || "");
    const [state, setState] = useState("");
    const timer = useRef(null);
    useEffect(() => () => clearTimeout(timer.current), []);
    const save = (body) =>
        api(`/learn/${enrollment.id}/lessons/${lesson.id}/note`, { method: "PUT", body: JSON.stringify({ body }) })
            .then(() => setState("Saved"))
            .catch((e) => setState(e.message));
    return (
        <div className="lesson-notes">
            <div className="theme-section-heading">
                <h3>Your private notes</h3>
                <small className="muted" aria-live="polite">
                    {state}
                </small>
            </div>
            <textarea
                value={value}
                rows={5}
                maxLength={20000}
                aria-label="Private notes for this lesson"
                placeholder="Jot down key ideas, questions for class, or links. Notes save automatically and only you can see them."
                onChange={(e) => {
                    setValue(e.target.value);
                    setState("Saving…");
                    clearTimeout(timer.current);
                    timer.current = setTimeout(() => save(e.target.value), 900);
                }}
            />
        </div>
    );
}

function Lessons({ enrollment: e, lessons, completed, notes, selected, onSelect }) {
    const list = rows(lessons);
    const lesson = list.find((l) => l.id === selected) || list.find((l) => !completed.includes(l.id)) || list[0];
    useEffect(() => {
        if (lesson && e.status === "active") api(`/learn/${e.id}/lessons/${lesson.id}/open`, { method: "POST", body: "{}" }).catch(() => {});
    }, [lesson?.id]);
    if (!lesson) return <Empty title="No lessons yet" description="Your instructor will publish lessons here. Follow your class schedule meanwhile." />;
    const index = list.indexOf(lesson);
    const done = completed.includes(lesson.id);
    let section = null;
    return (
        <section className="lesson-studio">
            <aside className="lesson-outline">
                <div className="lesson-outline-heading">
                    <span className="eyebrow">CURRICULUM</span>
                    <h2>
                        {completed.filter((id) => list.some((l) => l.id === id)).length} / {list.length} complete
                    </h2>
                </div>
                <nav aria-label="Course lessons">
                    {list.map((l, i) => {
                        const heading = l.section && l.section !== section ? (section = l.section) : null;
                        return (
                            <React.Fragment key={l.id}>
                                {heading && <span className="lesson-section-title">{heading}</span>}
                                <button type="button" aria-current={l.id === lesson.id ? "step" : undefined} onClick={() => onSelect(l.id)} className={completed.includes(l.id) ? "is-done" : ""}>
                                    <span>{completed.includes(l.id) ? "✓" : String(i + 1).padStart(2, "0")}</span>
                                    <span>
                                        {l.title}
                                        <small>
                                            {l.duration_minutes} min{l.video_url ? " · video" : ""}
                                        </small>
                                    </span>
                                </button>
                            </React.Fragment>
                        );
                    })}
                </nav>
            </aside>
            <article className="lesson-content" key={lesson.id}>
                <span className="eyebrow">
                    {lesson.section ? `${lesson.section} · ` : ""}LESSON {index + 1} OF {list.length} · {lesson.duration_minutes} MIN
                </span>
                <h2>{lesson.title}</h2>
                <LessonVideo url={lesson.video_url} title={lesson.title} />
                <LessonBody content={lesson.content} />
                {lesson.resource_url && (
                    <a className="button secondary" href={lesson.resource_url} target="_blank" rel="noopener noreferrer">
                        Open lesson resources ↗
                    </a>
                )}
                <div className="lesson-footer">
                    {index > 0 ? (
                        <button type="button" className="button secondary" onClick={() => onSelect(list[index - 1].id)}>
                            ← Previous
                        </button>
                    ) : (
                        <span />
                    )}
                    {e.status === "active" && !done ? (
                        <Form action={`/learn/${e.id}/lessons/${lesson.id}/complete`} submit={index < list.length - 1 ? "Complete & continue →" : "Complete course lessons ✓"} className="inline-complete">
                            <input type="hidden" name="continue" value="1" />
                        </Form>
                    ) : index < list.length - 1 ? (
                        <button type="button" className="button" onClick={() => onSelect(list[index + 1].id)}>
                            {done ? "✓ Done · Next lesson →" : "Next lesson →"}
                        </button>
                    ) : (
                        done && <p className="lesson-completed">✓ You’ve completed this lesson</p>
                    )}
                </div>
                {e.status === "active" && <NotesBox enrollment={e} lesson={lesson} initial={notes[lesson.id]} />}
            </article>
        </section>
    );
}

export default function StudentHub({ enrollment: e, lessons, completed, learning, notes = {}, announcements = [], plan = {}, payments, details }) {
    const params = new URLSearchParams(location.search);
    const [tab, setTab] = useState(TABS.some(([k]) => k === params.get("tab")) ? params.get("tab") : params.get("lesson") ? "lessons" : "overview");
    const [lesson, setLesson] = useState(Number(params.get("lesson")) || plan.insight?.next_lesson?.id || null);
    const insight = plan.insight;
    const tabsRef = useRef(null);
    const go = (next, lessonId = null, anchor = null) => {
        setTab(next);
        if (lessonId) setLesson(lessonId);
        setQuery({ tab: next, lesson: next === "lessons" ? lessonId || lesson : null });
        requestAnimationFrame(() => {
            const target = anchor && document.getElementById(anchor);
            (target || tabsRef.current)?.scrollIntoView({ behavior: "smooth", block: "start" });
        });
    };
    const live = plan.todo?.find((t) => t.type === "class" && t.live);
    const progress = insight?.progress ?? (rows(lessons).length ? Math.floor((completed.length * 100) / rows(lessons).length) : 0);
    const dueCount = (plan.todo || []).filter((t) => t.type === "assignment").length;
    const next = insight?.next_lesson;
    return (
        <>
            <header className="course-hub-header">
                <div>
                    <span className="eyebrow">
                        {e.batch.name} · {label(e.batch.course.mode)}
                    </span>
                    <h1 className="page-title">{e.batch.course.title}</h1>
                    <p className="muted">
                        with {e.batch.course.instructor} · {e.batch.schedule}
                    </p>
                    <div className="hub-chips">
                        {insight && <LevelChip level={insight.level} student />}
                        {insight?.streak > 0 && (
                            <span className="streak-chip" title="Days in a row with learning activity">
                                🔥 {insight.streak}-day streak
                            </span>
                        )}
                        {insight && insight.level !== "completed" && insight.remaining_minutes > 0 && <span className="soft-chip">{minutes(insight.remaining_minutes)} of lessons left</span>}
                        {insight?.last_active_at && <span className="soft-chip">Last active {ago(insight.last_active_at).toLowerCase()}</span>}
                    </div>
                </div>
                <div className="hub-cta">
                    <ProgressRing value={progress} />
                    {e.status === "completed" && e.certificate_code ? (
                        <a className="button" href={`/learn/${e.id}/certificate`}>
                            View certificate ↗
                        </a>
                    ) : next ? (
                        <button type="button" className="button" onClick={() => go("lessons", next.id)}>
                            {completed.length ? "Continue" : "Start"}: {next.title.length > 34 ? next.title.slice(0, 32) + "…" : next.title} →
                        </button>
                    ) : null}
                </div>
            </header>
            {live && (
                <div className="live-banner" role="status">
                    <span className="live-dot" aria-hidden="true" />
                    <strong>{live.title}</strong> is starting {new Date(live.at) <= new Date() ? "now" : `at ${new Date(live.at).toLocaleTimeString(undefined, { hour: "numeric", minute: "2-digit" })}`}.
                    {e.batch.meeting_url ? (
                        <a className="button small" href={e.batch.meeting_url} target="_blank" rel="noopener noreferrer">
                            Join class ↗
                        </a>
                    ) : (
                        e.batch.location && <span> Venue: {e.batch.location}</span>
                    )}
                </div>
            )}
            <nav className="hub-tabs" aria-label="Course sections" ref={tabsRef}>
                {TABS.map(([key, title]) => (
                    <button key={key} type="button" aria-current={tab === key ? "page" : undefined} onClick={() => go(key)}>
                        {title}
                        {key === "lessons" && <small>{rows(lessons).length}</small>}
                        {key === "assignments" && dueCount > 0 && <small className="alert">{dueCount}</small>}
                    </button>
                ))}
            </nav>

            {tab === "overview" && (
                <div className="hub-overview">
                    <div>
                        <section className="panel">
                            <h2>Your plan</h2>
                            <p className="muted">The most useful things to do next, in order.</p>
                            <TodoList items={plan.todo} go={go} meetingUrl={e.batch.meeting_url} />
                        </section>
                        {insight && <PaceCard insight={insight} />}
                        <Announcements items={announcements} />
                    </div>
                    <aside>
                        <CompletionChecklist enrollment={e} lessons={rows(lessons)} completed={completed} learning={learning} />
                        <section className="panel">
                            <h2>Class details</h2>
                            <dl className="education-facts">
                                <dt>Dates</dt>
                                <dd>
                                    {date(e.batch.starts_on)} – {date(e.batch.ends_on)}
                                </dd>
                                <dt>Schedule</dt>
                                <dd>{e.batch.schedule}</dd>
                                {e.batch.location && (
                                    <>
                                        <dt>Venue</dt>
                                        <dd>{e.batch.location}</dd>
                                    </>
                                )}
                            </dl>
                            {e.batch.meeting_url && (
                                <a className="button secondary small" href={e.batch.meeting_url} target="_blank" rel="noopener noreferrer">
                                    Class meeting link ↗
                                </a>
                            )}
                        </section>
                    </aside>
                </div>
            )}
            {tab === "lessons" && (
                <Lessons
                    enrollment={e}
                    lessons={lessons}
                    completed={completed}
                    notes={notes}
                    selected={lesson}
                    onSelect={(id) => {
                        setLesson(id);
                        setQuery({ tab: "lessons", lesson: id });
                        tabsRef.current?.scrollIntoView({ behavior: "smooth", block: "start" });
                    }}
                />
            )}
            {tab === "classes" && <LearningActivities enrollment={e} learning={learning} only="schedule" />}
            {tab === "assignments" && <LearningActivities enrollment={e} learning={learning} only="assignments" />}
            {tab === "quizzes" && <LearningActivities enrollment={e} learning={learning} only="quizzes" />}
            {tab === "details" && (
                <div className="education-detail">
                    <div>{payments}</div>
                    <aside>{details}</aside>
                </div>
            )}
        </>
    );
}
