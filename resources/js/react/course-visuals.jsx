import React, { useEffect, useState } from "react";
import { Field, context, label } from "./ui";

export function CourseArtwork({ course, eager = false }) {
    const src =
        course.preview_url ||
        (course.cover_image ? "/storage/" + course.cover_image : null);
    return (
        <div
            className={
                "course-artwork theme-" + (course.visual_theme || "mint")
            }
        >
            {src ? (
                <img
                    src={src}
                    alt={course.cover_alt || ""}
                    loading={eager ? "eager" : "lazy"}
                    decoding="async"
                    width="1200"
                    height="800"
                />
            ) : (
                <div className="course-art-pattern" aria-hidden="true">
                    <span className="course-art-orbit" />
                    <div className="course-art-window">
                        <span>USP / EDUCATION</span>
                        <strong>
                            Learn.
                            <br />
                            Build.
                            <br />
                            Grow.
                        </strong>
                        <span>✦ &nbsp; Your next chapter</span>
                    </div>
                    <span className="course-art-spark">✳</span>
                </div>
            )}
        </div>
    );
}

export function CourseCarousel({ courses = [] }) {
    const [index, setIndex] = useState(0);
    if (!courses.length) return null;
    const course = courses[index % courses.length];
    return (
        <section
            className="course-carousel"
            aria-roledescription="carousel"
            aria-label="Featured courses"
        >
            <div
                className="course-carousel-slide"
                role="group"
                aria-roledescription="slide"
                aria-label={index + 1 + " of " + courses.length}
            >
                <div className="course-carousel-copy">
                    <span className="eyebrow">
                        FEATURED COURSE · {course.category}
                    </span>
                    <h2>{course.title}</h2>
                    <p>{course.summary}</p>
                    <div className="education-meta">
                        <span>{course.duration_hours} hours</span>
                        <span>{label(course.level)}</span>
                        <span>{label(course.mode)}</span>
                    </div>
                    <a className="button" href={"/courses/" + course.slug}>
                        Explore course ↗
                    </a>
                </div>
                <CourseArtwork course={course} eager />
            </div>
            {courses.length > 1 && (
                <div className="course-carousel-controls">
                    <span aria-live="polite" aria-atomic="true">
                        Course {index + 1} of {courses.length}
                    </span>
                    <div className="course-carousel-dots">
                        {courses.map((item, i) => (
                            <button
                                key={item.id}
                                type="button"
                                aria-label={"Show " + item.title}
                                aria-current={i === index ? "true" : undefined}
                                onClick={() => setIndex(i)}
                            />
                        ))}
                    </div>
                    <div>
                        <button
                            type="button"
                            aria-label="Previous featured course"
                            onClick={() =>
                                setIndex(
                                    (index + courses.length - 1) %
                                        courses.length,
                                )
                            }
                        >
                            ←
                        </button>
                        <button
                            type="button"
                            aria-label="Next featured course"
                            onClick={() =>
                                setIndex((index + 1) % courses.length)
                            }
                        >
                            →
                        </button>
                    </div>
                </div>
            )}
        </section>
    );
}

export function CoursePresentationFields({ course = {} }) {
    const [theme, setTheme] = useState(
        context.old?.visual_theme || course.visual_theme || "mint",
    );
    const [file, setFile] = useState(null);
    const [preview, setPreview] = useState(null);
    const [remove, setRemove] = useState(context.old?.remove_cover === "1");
    useEffect(() => {
        if (!file) {
            setPreview(null);
            return;
        }
        const url = URL.createObjectURL(file);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [file]);
    return (
        <fieldset className="course-presentation">
            <legend>Course graphics & featured carousel</legend>
            <div className="course-editor-preview">
                <CourseArtwork
                    course={{
                        ...course,
                        visual_theme: theme,
                        cover_image: remove ? null : course.cover_image,
                        preview_url: preview,
                    }}
                />
            </div>
            <div className="form-grid">
                <Field
                    name="cover"
                    title="Course cover image"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    onChange={(event) =>
                        setFile(event.target.files?.[0] || null)
                    }
                />
                <Field
                    name="cover_alt"
                    title="Image description"
                    value={course.cover_alt}
                    maxLength={255}
                />
                <Field
                    name="visual_theme"
                    title="Graphic theme"
                    type="select"
                    value={theme}
                    options={["mint", "violet", "amber", "blue"]}
                    onChange={(event) => setTheme(event.target.value)}
                />
                <Field
                    name="carousel_position"
                    title="Carousel position"
                    type="number"
                    value={course.carousel_position || 1}
                    min={1}
                    max={1000}
                    required
                />
            </div>
            <p className="muted">
                JPEG, PNG or WebP, up to 8 MB and 4096 × 4096 pixels.
                Recommended: 1600 × 1000. A theme graphic appears when no image
                is uploaded.
            </p>
            {course.cover_image && (
                <Field
                    name="remove_cover"
                    title="Remove current cover image"
                    type="checkbox"
                    value={remove}
                    onChange={(event) => setRemove(event.target.checked)}
                />
            )}
            <Field
                name="is_featured"
                title="Feature this course in the courses page carousel"
                type="checkbox"
                value={course.is_featured || false}
            />
            <p className="muted">
                Only published, featured courses appear. Lower positions appear
                first, up to 12 courses. Feature at least two courses to show
                navigation.
            </p>
        </fieldset>
    );
}
