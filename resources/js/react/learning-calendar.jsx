import React, { useState } from "react";
import { Heading, Empty } from "./ui";
export default function LearningCalendar({ events = [] }) {
    const [kind, setKind] = useState("all");
    const visible = events.filter((e) => kind === "all" || e.kind === kind);
    const groups = visible.reduce((acc, event) => {
        const day = new Date(event.at).toLocaleDateString(undefined, {
            weekday: "long",
            month: "long",
            day: "numeric",
            year: "numeric",
        });
        (acc[day] ??= []).push(event);
        return acc;
    }, {});
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    return (
        <section className="education section">
            <a className="text-link" href="/learn">
                ← My learning
            </a>
            <Heading
                eyebrow="PLAN YOUR LEARNING"
                title="Your learning calendar"
                description="Upcoming classes and outstanding assignment deadlines across your active courses."
            >
                <a className="button" href="/learn/calendar/download">
                    Download calendar ↗
                </a>
            </Heading>
            <div className="calendar-summary">
                <div>
                    <strong>
                        {events.filter((e) => e.kind === "Class").length}
                    </strong>
                    <span>Scheduled classes</span>
                </div>
                <div>
                    <strong>
                        {events.filter((e) => e.kind !== "Class").length}
                    </strong>
                    <span>Assignment deadlines</span>
                </div>
                <p>
                    Next 90 days · Times shown in {timezone}. Up to 300 classes
                    and 300 deadlines.
                </p>
            </div>
            <nav className="calendar-filters" aria-label="Calendar event type">
                {[
                    ["all", "All events"],
                    ["Class", "Classes"],
                    ["Assignment due", "Assignments"],
                ].map(([value, title]) => (
                    <button
                        type="button"
                        key={value}
                        aria-pressed={kind === value}
                        onClick={() => setKind(value)}
                    >
                        {title}
                    </button>
                ))}
            </nav>
            {visible.length ? (
                Object.entries(groups).map(([day, items]) => (
                    <section className="calendar-day" key={day}>
                        <h2>{day}</h2>
                        <div>
                            {items.map((event) => (
                                <article
                                    className={
                                        "calendar-event " +
                                        (event.kind === "Class"
                                            ? "is-class"
                                            : "is-deadline")
                                    }
                                    key={event.id}
                                >
                                    <time dateTime={event.at}>
                                        {new Date(event.at).toLocaleTimeString(
                                            undefined,
                                            {
                                                hour: "numeric",
                                                minute: "2-digit",
                                            },
                                        )}
                                    </time>
                                    <div>
                                        <span className="eyebrow">
                                            {event.kind}
                                        </span>
                                        <h3>{event.title}</h3>
                                        <p>{event.course}</p>
                                    </div>
                                    <a
                                        className="text-link"
                                        href={
                                            "/learn/" +
                                            event.enrollment_id +
                                            (event.kind === "Class"
                                                ? "#classroom"
                                                : "#learning-activities")
                                        }
                                    >
                                        Open classroom →
                                    </a>
                                </article>
                            ))}
                        </div>
                    </section>
                ))
            ) : (
                <Empty
                    title="No upcoming events"
                    description="Your instructor’s scheduled classes and outstanding assignment deadlines will appear here."
                />
            )}
            <aside className="panel calendar-help">
                <h2>Take your schedule with you</h2>
                <p>
                    Import the downloaded .ics file into your calendar app. It
                    is a snapshot, not a live subscription: check this page for
                    schedule changes. Classes mark their start time; assignments
                    mark their deadline. Private meeting links stay in your
                    classroom.
                </p>
            </aside>
        </section>
    );
}
