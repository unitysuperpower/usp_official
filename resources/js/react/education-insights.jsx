import React, { useMemo, useState } from "react";
import { context, sanitize, date, Heading, Form, Field, Modal, ModalErrors, Empty } from "./ui";

// Smart-learning building blocks shared by the student course hub and the instructor classroom.

export const levels = {
    on_track: ["On track", "Keeping pace with the class"],
    watch: ["Needs attention", "Falling a little behind"],
    at_risk: ["At risk", "Likely to need help to finish"],
    completed: ["Completed", "Course completed"],
};

export function LevelChip({ level, student = false }) {
    const text = student ? { on_track: "You're on track", watch: "A little behind", at_risk: "Needs your attention", completed: "Completed" }[level] : levels[level]?.[0];
    return <span className={`level-chip level-${level}`}>{text}</span>;
}

export function ProgressRing({ value = 0, size = 76, label = "complete" }) {
    const r = (size - 10) / 2,
        c = 2 * Math.PI * r,
        v = Math.max(0, Math.min(100, Number(value) || 0));
    return (
        <div className="progress-ring" style={{ width: size, height: size }} role="img" aria-label={`${v}% ${label}`}>
            <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`} aria-hidden="true">
                <circle cx={size / 2} cy={size / 2} r={r} className="ring-track" />
                <circle cx={size / 2} cy={size / 2} r={r} className="ring-value" strokeDasharray={c} strokeDashoffset={c - (v / 100) * c} />
            </svg>
            <strong>{v}%</strong>
        </div>
    );
}

/** Progress bar with a marker where the class schedule says the student should be. */
export function PaceBar({ progress = 0, expected = 0, compact = false }) {
    return (
        <div className={`pace-bar ${compact ? "compact" : ""}`}>
            <div className="pace-track" role="img" aria-label={`${progress ?? 0}% done; the class is ${expected}% through the schedule`}>
                <span className="pace-fill" style={{ width: `${progress ?? 0}%` }} />
                <i className="pace-marker" style={{ left: `${expected}%` }} title={`Class schedule: ${expected}%`} />
            </div>
            {!compact && (
                <div className="pace-legend">
                    <span>
                        <b className="dot fill" /> You: {progress ?? 0}%
                    </span>
                    <span>
                        <b className="dot marker" /> Class schedule: {expected}%
                    </span>
                </div>
            )}
        </div>
    );
}

export const ago = (value) => {
    if (!value) return "Never";
    const days = Math.floor((Date.now() - new Date(value)) / 86400000);
    if (days <= 0) return "Today";
    if (days === 1) return "Yesterday";
    if (days < 30) return `${days} days ago`;
    return date(value);
};
export const minutes = (total) => (total >= 60 ? `${Math.floor(total / 60)} h ${total % 60 ? `${total % 60} min` : ""}`.trim() : `${total || 0} min`);

// --- Lesson content ---------------------------------------------------------------------------

const escape = (s) => s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
const inline = (s) =>
    escape(s)
        .replace(/\*\*(.+?)\*\*/g, "<strong>$1</strong>")
        .replace(/(^|[^*])\*(?!\s)(.+?)\*(?!\*)/g, "$1<em>$2</em>")
        .replace(/`([^`]+)`/g, "<code>$1</code>")
        .replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>')
        .replace(/(^|\s)(https?:\/\/[^\s<]+)/g, '$1<a href="$2" target="_blank" rel="noopener noreferrer">$2</a>');

/**
 * Lesson text written as plain text or light Markdown (# headings, - bullets, 1. lists, **bold**,
 * [links](https://…)) becomes readable HTML. Content that is already HTML is sanitised as-is.
 */
export function formatLesson(text = "") {
    if (/<\s*(p|ul|ol|h[1-6]|div|br|strong|img|table|blockquote|pre|figure)\b/i.test(text)) return text;
    const out = [];
    let list = null,
        para = [];
    const flushPara = () => {
        if (para.length) out.push(`<p>${para.map(inline).join("<br>")}</p>`);
        para = [];
    };
    const flushList = () => {
        if (list) out.push(`<${list.type}>${list.items.map((i) => `<li>${inline(i)}</li>`).join("")}</${list.type}>`);
        list = null;
    };
    const lines = String(text).replace(/\r/g, "").split("\n").map((l) => l.trim());
    const numbered = (l) => /^\d+[.)]\s+/.test(l || "");
    lines.forEach((line, index) => {
        let m;
        const next = lines.slice(index + 1).find((l) => l);
        if (!line) {
            flushPara();
            flushList();
        } else if ((m = line.match(/^(#{1,3})\s+(.*)$/))) {
            flushPara();
            flushList();
            out.push(`<h${m[1].length + 2}>${inline(m[2])}</h${m[1].length + 2}>`);
        } else if ((m = line.match(/^[-–•*]\s+(.*)$/))) {
            flushPara();
            if (list?.type !== "ul") flushList();
            list ??= { type: "ul", items: [] };
            list.items.push(m[1]);
        } else if ((m = line.match(/^\d+[.)]\s+(.*)$/))) {
            flushPara();
            // A lone numbered line followed by normal text ("1. What is social media?") is a sub-heading.
            if (list?.type !== "ol" && !numbered(next)) {
                flushList();
                out.push(`<h4>${inline(line)}</h4>`);
            } else {
                if (list?.type !== "ol") flushList();
                list ??= { type: "ol", items: [] };
                list.items.push(m[1]);
            }
        } else if (/^[A-Z][^.!?]{1,60}:$/.test(line)) {
            flushPara();
            flushList();
            out.push(`<h4>${inline(line.slice(0, -1))}</h4>`);
        } else {
            flushList();
            para.push(line);
        }
    });
    flushPara();
    flushList();
    return out.join("");
}

export function LessonBody({ content }) {
    const html = useMemo(() => sanitize(formatLesson(content || "")), [content]);
    return <div className="prose lesson-prose" dangerouslySetInnerHTML={{ __html: html }} />;
}

/** Embeddable player URL for common video hosts, or null to fall back to a link. */
export function embedUrl(url) {
    try {
        const u = new URL(url);
        const host = u.hostname.replace(/^www\.|^m\./, "");
        if (host === "youtu.be") return `https://www.youtube-nocookie.com/embed/${u.pathname.slice(1)}`;
        if (host.endsWith("youtube.com")) {
            const id = u.searchParams.get("v") || u.pathname.match(/\/(embed|shorts|live)\/([\w-]+)/)?.[2];
            return id ? `https://www.youtube-nocookie.com/embed/${id}` : null;
        }
        if (host === "vimeo.com") return /^\/\d+/.test(u.pathname) ? `https://player.vimeo.com/video${u.pathname.match(/^\/\d+/)[0]}` : null;
        if (host === "player.vimeo.com") return url;
        if (host === "loom.com") return u.pathname.startsWith("/share/") ? `https://www.loom.com/embed/${u.pathname.split("/")[2]}` : null;
        if (host === "drive.google.com") {
            const id = u.pathname.match(/\/file\/d\/([\w-]+)/)?.[1];
            return id ? `https://drive.google.com/file/d/${id}/preview` : null;
        }
    } catch {}
    return null;
}

export function LessonVideo({ url, title }) {
    if (!url) return null;
    if (/\.(mp4|webm|ogg)(\?|$)/i.test(url))
        return (
            <div className="lesson-video">
                <video src={url} controls preload="metadata" />
            </div>
        );
    const embed = embedUrl(url);
    return embed ? (
        <div className="lesson-video">
            <iframe src={embed} title={`Video: ${title}`} loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowFullScreen referrerPolicy="strict-origin-when-cross-origin" />
        </div>
    ) : (
        <a href={url} target="_blank" rel="noopener noreferrer" className="lesson-video-link">
            <span aria-hidden="true">▶</span>
            <div>
                <strong>Watch this lesson</strong>
                <small>Opens the instructor’s video in a new tab ↗</small>
            </div>
        </a>
    );
}

// --- Instructor views ---------------------------------------------------------------------------

function NudgeButton({ base, row, sentAt }) {
    const [open, setOpen] = useState(false);
    const today = sentAt && new Date(sentAt).toDateString() === new Date().toDateString();
    return (
        <>
            <button type="button" className="table-action" disabled={today} title={today ? "Already reminded today" : undefined} onClick={() => setOpen(true)}>
                {today ? "Reminded today" : "Send reminder"}
            </button>
            <Modal open={open} onClose={() => setOpen(false)} title={`Remind ${row.student?.name}`}>
                <ModalErrors />
                <Form action={`${base}/students/${row.enrollment_id}/nudge`} submit="Email reminder">
                    <p>We’ll email {row.student?.name} a friendly note with a link to the course{row.reasons.length ? ", including:" : "."}</p>
                    {row.reasons.length > 0 && (
                        <ul className="insight-reasons">
                            {row.reasons.map((r) => (
                                <li key={r}>{r}</li>
                            ))}
                        </ul>
                    )}
                    <Field name="message" title="Personal message (optional)" type="textarea" rows={3} maxLength={1000} placeholder="Hi! I noticed you missed the last class — let me know if you need help catching up." />
                </Form>
            </Modal>
        </>
    );
}

export function ClassroomInsights({ insights = [], nudged = {}, base }) {
    const [filter, setFilter] = useState("all");
    const counts = insights.reduce((acc, r) => ({ ...acc, [r.level]: (acc[r.level] || 0) + 1 }), {});
    const shown = insights.filter((r) => filter === "all" || r.level === filter);
    if (!insights.length) return <Empty title="No enrolled students yet" description="Insights appear when students are active in this batch." />;
    return (
        <section className="panel">
            <div className="theme-section-heading">
                <div>
                    <h2>Student insights</h2>
                    <p className="muted">Ranked by who needs help most. Based on pace, attendance, deadlines, quizzes and activity.</p>
                </div>
                <div className="trend-range" role="group" aria-label="Filter students">
                    {[
                        ["all", `All ${insights.length}`],
                        ["at_risk", `At risk ${counts.at_risk || 0}`],
                        ["watch", `Attention ${counts.watch || 0}`],
                        ["on_track", `On track ${counts.on_track || 0}`],
                    ].map(([key, text]) => (
                        <button key={key} type="button" aria-pressed={filter === key} onClick={() => setFilter(key)}>
                            {text}
                        </button>
                    ))}
                </div>
            </div>
            <div className="insight-list">
                {shown.map((row) => (
                    <article className={`insight-row level-border-${row.level}`} key={row.enrollment_id}>
                        <div className="insight-person">
                            <span className="user-avatar" aria-hidden="true">
                                {(row.student?.name || "?")
                                    .split(" ")
                                    .slice(0, 2)
                                    .map((w) => w[0])
                                    .join("")}
                            </span>
                            <div>
                                <strong>{row.student?.name}</strong>
                                <LevelChip level={row.level} />
                            </div>
                        </div>
                        <div className="insight-pace">
                            <small>
                                {row.lessons_done}/{row.lessons_total} lessons
                            </small>
                            <PaceBar progress={row.progress ?? 0} expected={row.expected} compact />
                        </div>
                        <dl className="insight-facts">
                            <div>
                                <dt>Attendance</dt>
                                <dd>{row.attendance === null ? "—" : `${row.attendance}%`}</dd>
                            </div>
                            <div>
                                <dt>Avg score</dt>
                                <dd>{row.average_score === null ? "—" : `${row.average_score}%`}</dd>
                            </div>
                            <div>
                                <dt>Missing</dt>
                                <dd>{row.missing}</dd>
                            </div>
                            <div>
                                <dt>Last active</dt>
                                <dd>{ago(row.last_active_at)}</dd>
                            </div>
                        </dl>
                        <div className="insight-actions">
                            {row.reasons.length > 0 ? (
                                <ul className="insight-reasons">
                                    {row.reasons.slice(0, 3).map((r) => (
                                        <li key={r}>{r}</li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="muted">{row.level === "completed" ? "Finished the course." : "No concerns right now."}</p>
                            )}
                            {row.status === "active" && <NudgeButton base={base} row={row} sentAt={nudged[row.enrollment_id]} />}
                        </div>
                    </article>
                ))}
                {!shown.length && <p className="muted">No students in this group.</p>}
            </div>
        </section>
    );
}

const when = (value) => new Date(value).toLocaleString(undefined, { weekday: "short", day: "numeric", month: "short", hour: "numeric", minute: "2-digit" });

export function TeachingDashboard({ batches = [], managesAll }) {
    const totals = batches.reduce((a, b) => ({ students: a.students + b.active_count, risk: a.risk + b.at_risk, grade: a.grade + b.to_grade, unmarked: a.unmarked + b.unmarked_sessions }), { students: 0, risk: 0, grade: 0, unmarked: 0 });
    return (
        <section className="education">
            <Heading eyebrow="TEACHING" title={`Good ${new Date().getHours() < 12 ? "morning" : new Date().getHours() < 17 ? "afternoon" : "evening"}, ${context.user.name.split(" ")[0]}`} description={managesAll ? "Every running and upcoming class. Instructors see only the courses they are assigned to." : "Your classes, students who need help, and work waiting for you."} />
            <div className="stats">
                {[
                    ["Active students", totals.students],
                    ["Students at risk", totals.risk],
                    ["Work to grade", totals.grade],
                    ["Sessions to close", totals.unmarked],
                ].map(([title, value]) => (
                    <div className="stat" key={title}>
                        <span>{title}</span>
                        <strong>{value}</strong>
                    </div>
                ))}
            </div>
            {!batches.length && <Empty title="No classes assigned yet" description="When a course manager adds you as an instructor, your batches appear here." />}
            <div className="teaching-grid">
                {batches.map((b) => (
                    <article className="panel teaching-card" key={b.id}>
                        <header>
                            <span className="eyebrow">{b.course.title}</span>
                            <h2>{b.name}</h2>
                            <p className="muted">
                                {date(b.starts_on)} – {date(b.ends_on)} · {b.schedule}
                            </p>
                        </header>
                        <div className="teaching-numbers">
                            <div>
                                <strong>{b.active_count}</strong>
                                <span>students</span>
                            </div>
                            <div>
                                <strong>{b.average_progress ?? "—"}
                                    {b.average_progress !== null && "%"}</strong>
                                <span>avg progress</span>
                            </div>
                            <div className={b.at_risk ? "alert" : ""}>
                                <strong>{b.at_risk}</strong>
                                <span>at risk</span>
                            </div>
                            <div className={b.to_grade ? "alert" : ""}>
                                <strong>{b.to_grade}</strong>
                                <span>to grade</span>
                            </div>
                        </div>
                        {b.next_session && (
                            <p className="teaching-next">
                                <span aria-hidden="true">◷</span> Next class: <strong>{b.next_session.title}</strong> · {when(b.next_session.starts_at)}
                            </p>
                        )}
                        {b.unmarked_sessions > 0 && <p className="teaching-warning">{b.unmarked_sessions} past session{b.unmarked_sessions === 1 ? "" : "s"} still marked “scheduled” — close them and take attendance.</p>}
                        {b.needs_attention.length > 0 && (
                            <ul className="teaching-attention">
                                {b.needs_attention.map((s) => (
                                    <li key={s.name + s.reason}>
                                        <LevelChip level={s.level} /> <strong>{s.name}</strong> {s.reason && <span className="muted">— {s.reason}</span>}
                                    </li>
                                ))}
                            </ul>
                        )}
                        <div className="row-actions">
                            <a className="button small" href={`/admin/education/batches/${b.id}/classroom`}>
                                Open classroom →
                            </a>
                            {b.to_grade > 0 && (
                                <a className="text-link" href={`/admin/education/batches/${b.id}/classroom?tab=assignments`}>
                                    Grade work
                                </a>
                            )}
                        </div>
                    </article>
                ))}
            </div>
        </section>
    );
}
