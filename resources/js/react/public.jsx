import React, { useState } from "react";
import {
    context,
    rows,
    date,
    money,
    Heading,
    Empty,
    Pagination,
    Field,
    Form,
    Html,
    Badge,
    Stats,
    Table,
    api,
} from "./ui";
export function Cards({ items, blog = false }) {
    return rows(items).length ? (
        <div className="cards">
            {rows(items).map((item, i) => (
                <a
                    className="content-card"
                    href={`/${blog ? "blogs" : "services"}/${item.slug}`}
                    key={item.id}
                >
                    <div className={`card-art tone-${i % 3}`}>
                        {item.featured_image || item.image ? (
                            <img
                                loading="lazy"
                                src={`/storage/${item.featured_image || item.image}`}
                                alt=""
                            />
                        ) : (
                            <span aria-hidden="true">
                                {blog ? "✳" : ["⌘", "◈", "↗"][i % 3]}
                            </span>
                        )}
                        <span className="card-arrow">↗</span>
                    </div>
                    <div className="card-content">
                        <span className="eyebrow">
                            {item.category?.name ||
                                (blog ? "INSIGHTS" : "OUR EXPERTISE")}
                        </span>
                        <h3>{item.title}</h3>
                        <p>
                            {String(
                                item.excerpt ||
                                    item.short_description ||
                                    item.description ||
                                    "",
                            )
                                .replace(/<[^>]*>/g, "")
                                .slice(0, 150)}
                        </p>
                        <div className="card-meta">
                            {blog
                                ? date(item.published_at)
                                : `${money(item.price)} / ${item.price_unit || "project"}`}
                            <span>
                                {blog ? "Read story" : "Explore service"} ↗
                            </span>
                        </div>
                    </div>
                </a>
            ))}
        </div>
    ) : (
        <Empty
            title={
                blog
                    ? "Fresh perspectives, coming soon."
                    : "Let’s find the right solution."
            }
            description={
                blog
                    ? "Our latest articles will appear here."
                    : "Tell us what you have in mind through our contact page."
            }
        />
    );
}
export function Home({ featuredServices, latestBlogs }) {
    return (
        <>
            <section className="hero">
                <div className="hero-copy">
                    <span className="eyebrow">
                        <b className="dot" /> YOUR NEXT CHAPTER STARTS HERE
                    </span>
                    <h1>
                        Big ideas.
                        <br />
                        Beautifully
                        <br />
                        <em>built.</em>
                    </h1>
                    <p>
                        We bring design and technology together to build digital
                        experiences that move your business forward.
                    </p>
                    <div className="actions">
                        <a className="button" href="/contact">
                            Let’s build something <span>↗</span>
                        </a>
                        <a className="text-link" href="/services">
                            Explore our services →
                        </a>
                    </div>
                    <div className="hero-note">
                        <span className="mini-mark">u.</span>
                        <div>
                            Thoughtful design. Purposeful technology.
                            <br />
                            <strong>Your ambition, our expertise.</strong>
                        </div>
                    </div>
                </div>
                <div
                    className="hero-visual"
                    aria-label="Design and technology working together"
                >
                    <div className="visual-grid" />
                    <div className="orbit orbit-one" />
                    <div className="orbit orbit-two" />
                    <div className="visual-core">
                        u<span>.</span>
                    </div>
                    <div className="floating-label label-top">
                        <span>✳</span> Designed to make a difference
                    </div>
                    <div className="floating-card">
                        <span className="eyebrow">FROM CONCEPT TO LAUNCH</span>
                        <h3>Ideas into impact.</h3>
                        <div className="visual-bars">
                            <i />
                            <i />
                            <i />
                            <i />
                            <i />
                            <i />
                            <i />
                        </div>
                        <div className="visual-caption">
                            <span>Strategy</span>
                            <span>Design</span>
                            <span>Development</span>
                        </div>
                    </div>
                    <span className="visual-caption-bottom">
                        BUILT WITH PURPOSE. MADE FOR PEOPLE.
                    </span>
                </div>
            </section>
            <div className="expertise-strip">
                <span>IDEAS MEET EXECUTION</span>
                <strong>Design</strong>
                <i>✳</i>
                <strong>Development</strong>
                <i>✳</i>
                <strong>Digital strategy</strong>
                <i>✳</i>
                <strong>Support</strong>
            </div>
            <section className="section">
                <Heading
                    eyebrow="WHAT WE DO"
                    title="Good technology. Great possibilities."
                    description="The expertise to take your next idea further."
                >
                    <a className="text-link" href="/services">
                        All services ↗
                    </a>
                </Heading>
                <Cards items={featuredServices} />
            </section>
            <section className="approach section" id="approach">
                <div>
                    <span className="eyebrow">HOW WE WORK</span>
                    <h2>
                        A thoughtful process.
                        <br />
                        <em>A better outcome.</em>
                    </h2>
                    <p>
                        From the first conversation to the final detail, we work
                        with you to create something that matters.
                    </p>
                    <a className="button" href="/contact">
                        Meet your next tech partner ↗
                    </a>
                </div>
                <div className="steps">
                    {[
                        [
                            "01",
                            "Discover the possibilities",
                            "We listen, ask the right questions, and understand what success means to you.",
                        ],
                        [
                            "02",
                            "Design with intention",
                            "We turn your goals into clear, useful experiences that feel right.",
                        ],
                        [
                            "03",
                            "Build for what’s next",
                            "We develop, refine, and help you launch with confidence.",
                        ],
                    ].map(([n, t, d]) => (
                        <article key={n}>
                            <span>{n}</span>
                            <div>
                                <h3>{t}</h3>
                                <p>{d}</p>
                            </div>
                        </article>
                    ))}
                </div>
            </section>
            <section className="section">
                <Heading
                    eyebrow="OUR JOURNAL"
                    title="A little perspective goes a long way."
                >
                    <a className="text-link" href="/blogs">
                        All insights ↗
                    </a>
                </Heading>
                <Cards items={rows(latestBlogs).slice(0, 3)} blog />
            </section>
            <section className="cta">
                <span className="eyebrow">GOT SOMETHING IN MIND?</span>
                <h2>
                    Let’s make
                    <br />
                    <em>what’s next.</em>
                </h2>
                <a className="button light" href="/contact">
                    Start a conversation ↗
                </a>
                <span className="cta-star" aria-hidden="true">
                    ✳
                </span>
            </section>
        </>
    );
}
export function Listing({ blog = false, ...props }) {
    const items = blog ? props.blogs : props.services;
    const cats = props.categories || props.allCategories || [];
    return (
        <section className="section">
            <Heading
                eyebrow={blog ? "THE JOURNAL" : "OUR EXPERTISE"}
                title={
                    props.category?.name ||
                    (blog
                        ? "Ideas worth exploring."
                        : "Solutions built around you.")
                }
                description={
                    props.category?.description ||
                    (blog
                        ? "Perspectives on technology, design, and what comes next."
                        : "Discover how we can bring your next project to life.")
                }
            />
            {!blog && (
                <Form
                    action="/services"
                    method="GET"
                    className="search-form"
                    submit="Search"
                >
                    <Field
                        name="search"
                        title="Find a service"
                        value={new URLSearchParams(location.search).get(
                            "search",
                        )}
                        placeholder="What do you want to build?"
                    />
                </Form>
            )}
            <nav className="filters" aria-label="Categories">
                <a
                    className={!props.category ? "active" : ""}
                    href={blog ? "/blogs" : "/services"}
                >
                    All {blog ? "insights" : "services"}
                </a>
                {cats.map((c) => (
                    <a
                        className={props.category?.id === c.id ? "active" : ""}
                        key={c.id}
                        href={`/${blog ? "blog" : "service"}-category/${c.slug}`}
                    >
                        {c.name}
                    </a>
                ))}
            </nav>
            <Cards items={items} blog={blog} />
            <Pagination data={items} />
        </section>
    );
}
export function Contact({ service }) {
    return (
        <section className="section contact-layout">
            <div>
                <span className="eyebrow">LET’S TALK</span>
                <h1>
                    {service
                        ? "Your next step starts here."
                        : "Great things start with a conversation."}
                </h1>
                <p>
                    Tell us about your goals, your challenges, or that idea you
                    can’t stop thinking about.
                </p>
                <div className="contact-note">
                    <span>↗</span>
                    <h3>
                        {service?.title || "A partner for your next chapter."}
                    </h3>
                    <p>
                        Share a few details and our team will get back to you.
                    </p>
                </div>
            </div>
            <Form
                action="/contact"
                className="panel"
                submit={service ? "Send service request" : "Send your message"}
            >
                {service && (
                    <input type="hidden" name="service_id" value={service.id} />
                )}
                <div className="form-grid">
                    <Field
                        name="name"
                        title="Your name"
                        value={context.user?.name}
                        required
                        autoComplete="name"
                    />
                    <Field
                        name="email"
                        title="Email address"
                        type="email"
                        value={context.user?.email}
                        required
                        autoComplete="email"
                    />
                    <Field
                        name="phone"
                        title="Phone (optional)"
                        type="tel"
                        autoComplete="tel"
                    />
                    {service ? (
                        <Field name="company" title="Company (optional)" />
                    ) : (
                        <Field
                            name="subject"
                            title="What’s on your mind?"
                            required
                        />
                    )}
                </div>
                <Field
                    name="message"
                    title="Tell us about your project"
                    type="textarea"
                    required
                    minLength={service ? 1 : 10}
                />
                <p className="muted">
                    Your details will be used to respond to your inquiry.
                </p>
            </Form>
        </section>
    );
}
export function Detail({
    blog,
    service,
    relatedBlogs,
    relatedServices,
    userLiked,
}) {
    const item = blog || service;
    const [liked, setLiked] = useState(userLiked);
    const [count, setCount] = useState(blog?.likes_count || 0);
    const [error, setError] = useState("");
    const [busy, setBusy] = useState(false);
    async function like() {
        setBusy(true);
        setError("");
        try {
            const result = await api(`/blogs/${blog.id}/like`, {
                method: "POST",
            });
            setLiked(result.liked);
            setCount(result.likes_count);
        } catch (e) {
            setError(e.message);
        } finally {
            setBusy(false);
        }
    }
    return (
        <>
            <article className="section detail">
                <a className="text-link" href={blog ? "/blogs" : "/services"}>
                    ← All {blog ? "insights" : "services"}
                </a>
                <Heading
                    eyebrow={item.category?.name}
                    title={item.title}
                    description={item.excerpt || item.short_description}
                />
                {(item.featured_image || item.image) && (
                    <img
                        className="detail-image"
                        src={`/storage/${item.featured_image || item.image}`}
                        alt={item.title}
                    />
                )}
                <div className="detail-body">
                    <Html value={item.content || item.description} />
                    {service && (
                        <aside className="panel">
                            <span className="eyebrow">
                                BUILT FOR YOUR BUSINESS
                            </span>
                            <h2>{money(service.price)}</h2>
                            <p>Per {service.price_unit}</p>
                            {service.delivery_days && (
                                <p>
                                    Estimated delivery: {service.delivery_days}{" "}
                                    days
                                </p>
                            )}
                            <ul>
                                {Object.values(service.features || {}).map(
                                    (f, i) => (
                                        <li key={i}>{f}</li>
                                    ),
                                )}
                            </ul>
                            <a className="button" href="#inquiry">
                                Discuss this service ↗
                            </a>
                        </aside>
                    )}
                </div>
                {blog && (
                    <section className="comments">
                        <p className="muted">
                            {blog.user?.name} · {date(blog.published_at)}
                        </p>
                        <button
                            className="button secondary"
                            onClick={like}
                            disabled={busy}
                        >
                            {liked ? "♥ Liked" : "♡ Like"} · {count}
                        </button>
                        {error && (
                            <p role="alert" className="error">
                                {error}
                            </p>
                        )}
                        <h2>Join the conversation</h2>
                        {context.user ? (
                            <Form
                                action={`/blogs/${blog.id}/comment`}
                                submit="Post comment"
                            >
                                <Field
                                    name="comment"
                                    title="Your comment"
                                    type="textarea"
                                    maxLength={1000}
                                    required
                                />
                            </Form>
                        ) : (
                            <a href="/login" className="text-link">
                                Sign in to comment →
                            </a>
                        )}
                        {(blog.comments || []).map((c) => (
                            <article className="comment" key={c.id}>
                                <strong>{c.user?.name}</strong>
                                <p>{c.comment}</p>
                                {c.replies?.map((r) => (
                                    <blockquote key={r.id}>
                                        <strong>{r.user?.name}</strong>
                                        <p>{r.comment}</p>
                                    </blockquote>
                                ))}
                            </article>
                        ))}
                    </section>
                )}
            </article>
            {service && (
                <div id="inquiry">
                    <Contact service={service} />
                </div>
            )}
            <section className="section">
                <h2>Keep exploring.</h2>
                <Cards
                    items={relatedBlogs || relatedServices}
                    blog={Boolean(blog)}
                />
            </section>
        </>
    );
}
export function Dashboard({ requests, stats }) {
    return (
        <section className="section">
            <Heading
                eyebrow="YOUR WORKSPACE"
                title={`Welcome back, ${context.user.name.split(" ")[0]}.`}
                description="Your projects, conversations, and next steps, all in one place."
            >
                <a className="button" href="/services">
                    Explore services ↗
                </a>
            </Heading>
            <Stats stats={stats} />
            <div className="panel">
                <h2>Your service requests</h2>
                <Table
                    data={requests}
                    empty="Your next project starts here."
                    columns={[
                        {
                            title: "Service",
                            render: (r) => (
                                <strong>
                                    {r.service?.title || "Service unavailable"}
                                </strong>
                            ),
                        },
                        {
                            title: "Status",
                            render: (r) => <Badge>{r.status}</Badge>,
                        },
                        { title: "Date", render: (r) => date(r.created_at) },
                        { title: "Message", key: "message" },
                    ]}
                />
                <Pagination data={requests} />
            </div>
            <div className="quick-links">
                <a href="/chat">
                    <h3>Let’s keep talking ↗</h3>
                    <p>Open your support conversation.</p>
                </a>
                <a href="/profile">
                    <h3>Make yourself at home ↗</h3>
                    <p>Manage your account and password.</p>
                </a>
            </div>
        </section>
    );
}
