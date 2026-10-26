import React, { useEffect, useMemo, useState } from "react";
import { context, rows, date, label, Heading, Field, Form, Badge, Pagination, Table, Empty, Modal, ModalErrors, DeleteAction, api } from "./ui";
import { PaymentMethodCards, PaymentInstructions } from "./payment-cards";
import { LessonBody } from "./education-insights";
import RichEditor from "./rich-editor-lazy";

// Product store: public catalog and product pages, checkout, the customer's licenses, and the
// admin product studio, orders and licenses.

const account = "/account/products",
    admin = "/admin/store";
const price = (minor, currency = "USD") => new Intl.NumberFormat(undefined, { style: "currency", currency, maximumFractionDigits: Number(minor) % 100 ? 2 : 0 }).format(Number(minor) / 100);
const seats = (limit) => (limit === null || limit === undefined ? "Unlimited websites" : `${limit} ${Number(limit) === 1 ? "website" : "websites"}`);
const term = (months) => ({ 1: "month", 3: "3 months", 6: "6 months", 12: "year", 24: "2 years" })[months] || `${months} months`;
const termName = (months) => ({ 1: "Monthly", 3: "Quarterly", 6: "Every 6 months", 12: "Yearly", 24: "Every 2 years" })[months] || `${months} months`;
const live = (license) => license.status === "active" && new Date(license.expires_at) > new Date();
const daysLeft = (value) => Math.ceil((new Date(value) - Date.now()) / 86400000);
const licenseState = (l) => (l.status === "revoked" ? "revoked" : !live(l) ? "expired" : daysLeft(l.expires_at) <= 14 ? "expiring" : "active");
const query = (key) => new URLSearchParams(location.search).get(key);

function Nav() {
    const links = context.page.startsWith("admin.")
        ? [
              [admin + "/products", "Products & plans"],
              [admin + "/orders", "Orders & payments"],
              [admin + "/licenses", "Licenses"],
              ["/admin/education/settings", "Payment accounts"],
          ]
        : [
              ["/products", "All products"],
              [account, "My licenses & downloads"],
          ];
    return (
        <nav className="education-nav" aria-label="Product navigation">
            {links.map(([url, title]) => (
                <a key={url} href={url} aria-current={location.pathname === url || (url !== "/products" && location.pathname.startsWith(url + "/")) ? "page" : undefined}>
                    {title}
                </a>
            ))}
        </nav>
    );
}

export function ProductIcon({ product, size = 56 }) {
    const initials = (product?.name || "?")
        .split(/\s+/)
        .filter((w) => /[A-Za-z0-9]/.test(w[0]))
        .slice(0, 2)
        .map((w) => w[0])
        .join("")
        .toUpperCase();
    return product?.icon_url ? (
        <img className="product-icon" src={product.icon_url} alt="" width={size} height={size} style={{ width: size, height: size }} />
    ) : (
        <span className="product-icon generated" style={{ width: size, height: size, fontSize: size * 0.38 }} aria-hidden="true">
            {initials}
        </span>
    );
}

function StatusChip({ status }) {
    const text = { active: "Active", expiring: "Expiring soon", expired: "Expired", revoked: "Revoked", pending: "Awaiting payment", paid: "Paid", cancelled: "Cancelled", review: "Payment in review", approved: "Approved", rejected: "Not accepted", review_required: "Needs review", resolved: "Resolved", draft: "Draft", published: "Published" }[status] || label(status);
    return <span className={`status-chip status-${status}`}>{text}</span>;
}

function CopyButton({ value, text = "Copy" }) {
    const [done, setDone] = useState(false);
    return (
        <button
            type="button"
            className="copy-button"
            onClick={() =>
                navigator.clipboard?.writeText(value).then(() => {
                    setDone(true);
                    setTimeout(() => setDone(false), 1600);
                })
            }
        >
            {done ? "Copied ✓" : text}
        </button>
    );
}

function Meter({ value, max, label: text }) {
    const unlimited = max === null || max === undefined;
    const pct = unlimited ? 0 : Math.min(100, Math.round((value * 100) / Math.max(1, max)));
    return (
        <div className={`usage-meter ${pct >= 100 ? "full" : ""}`}>
            <div className="usage-label">
                <span>{text}</span>
                <strong>{unlimited ? `${value} · unlimited` : `${value} of ${max}`}</strong>
            </div>
            {!unlimited && (
                <div className="usage-track" aria-hidden="true">
                    <span style={{ width: `${pct}%` }} />
                </div>
            )}
        </div>
    );
}

/** Lowest per-month Pro price, and the best annual saving versus monthly for the same site limit. */
function pricing(plans = []) {
    const perMonth = plans.map((p) => p.price_minor / p.term_months);
    let save = 0;
    for (const p of plans.filter((x) => x.term_months > 1)) {
        const monthly = plans.find((m) => m.term_months === 1 && m.site_limit === p.site_limit);
        if (monthly) save = Math.max(save, Math.round((1 - p.price_minor / (monthly.price_minor * p.term_months)) * 100));
    }
    return { from: perMonth.length ? Math.min(...perMonth) : null, save };
}

// --- Public ---------------------------------------------------------------------------------------

function Catalog({ products }) {
    const list = rows(products);
    return (
        <>
            <header className="store-catalog-heading">
                <span className="eyebrow">THE TOOLBOX</span>
                <h1>
                    Small tools.
                    <br />
                    <em>Big possibilities.</em>
                </h1>
                <p>Thoughtful plugins for the websites you’re proud to build. Start free, upgrade when you need more.</p>
            </header>
            <div className="catalog-grid">
                {list.map((product) => {
                    const { from } = pricing(product.plans);
                    const shot = product.screenshots?.[0];
                    return (
                        <a className="catalog-card" key={product.id} href={"/products/" + product.slug}>
                            <div className="catalog-visual">
                                {shot ? <img src={"/storage/" + shot.path} alt="" loading="lazy" /> : <ProductIcon product={product} size={84} />}
                            </div>
                            <div className="catalog-copy">
                                <div className="catalog-title">
                                    <ProductIcon product={product} size={44} />
                                    <div>
                                        <span className="eyebrow">{product.category || "WordPress plugin"}</span>
                                        <h2>{product.name}</h2>
                                    </div>
                                </div>
                                <p>{product.tagline || product.summary}</p>
                                <div className="catalog-meta">
                                    {product.latest_release && <span>v{product.latest_release.version}</span>}
                                    {product.latest_release && <span>Updated {date(product.latest_release.created_at)}</span>}
                                </div>
                                <div className="catalog-footer">
                                    <span>
                                        {product.has_free && <strong>Free</strong>}
                                        {product.has_free && from !== null && " · "}
                                        {from !== null && (
                                            <>
                                                Pro from <strong>{price(from)}</strong>/mo
                                            </>
                                        )}
                                    </span>
                                    <span className="catalog-cta">View →</span>
                                </div>
                            </div>
                        </a>
                    );
                })}
            </div>
            {!list.length && <Empty title="New tools are on their way" description="Our next product releases will appear here." />}
            <Pagination data={products} />
        </>
    );
}

function Gallery({ shots }) {
    const [open, setOpen] = useState(null);
    if (!shots?.length) return null;
    return (
        <section id="screenshots" className="product-section">
            <div className="store-section-title">
                <span className="eyebrow">SEE IT IN ACTION</span>
                <h2>Screenshots</h2>
            </div>
            <div className="gallery-grid">
                {shots.map((s, i) => (
                    <button type="button" key={s.path} className="gallery-item" onClick={() => setOpen(i)} aria-label={`Open screenshot ${i + 1}${s.caption ? ": " + s.caption : ""}`}>
                        <img src={"/storage/" + s.path} alt={s.caption || ""} loading="lazy" />
                        {s.caption && <span>{s.caption}</span>}
                    </button>
                ))}
            </div>
            <Modal open={open !== null} onClose={() => setOpen(null)} title={shots[open]?.caption || `Screenshot ${open + 1} of ${shots.length}`}>
                {open !== null && (
                    <div className="lightbox">
                        <img src={"/storage/" + shots[open].path} alt={shots[open].caption || ""} />
                        {shots.length > 1 && (
                            <div className="row-actions">
                                <button type="button" className="button secondary small" onClick={() => setOpen((open + shots.length - 1) % shots.length)}>
                                    ← Previous
                                </button>
                                <span className="muted">
                                    {open + 1} / {shots.length}
                                </span>
                                <button type="button" className="button secondary small" onClick={() => setOpen((open + 1) % shots.length)}>
                                    Next →
                                </button>
                            </div>
                        )}
                    </div>
                )}
            </Modal>
        </section>
    );
}

function Compare({ product, hasFree }) {
    const free = product.free_features || [],
        pro = product.pro_features || [];
    if (!free.length && !pro.length) return null;
    return (
        <section id="features" className="product-section">
            <div className="store-section-title">
                <span className="eyebrow">FEATURES</span>
                <h2>{hasFree ? "Free to start. Pro when you need more." : "Everything included."}</h2>
            </div>
            <div className="table-wrap compare-table">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Feature</th>
                            {hasFree && <th scope="col">Free</th>}
                            <th scope="col">Pro</th>
                        </tr>
                    </thead>
                    <tbody>
                        {free.map((f) => (
                            <tr key={"f" + f}>
                                <td>{f}</td>
                                {hasFree && (
                                    <td className="yes" aria-label="Included">
                                        ✓
                                    </td>
                                )}
                                <td className="yes" aria-label="Included">
                                    ✓
                                </td>
                            </tr>
                        ))}
                        {pro.map((f) => (
                            <tr key={"p" + f}>
                                <td>{f}</td>
                                {hasFree && (
                                    <td className="no" aria-label="Not included">
                                        —
                                    </td>
                                )}
                                <td className="yes" aria-label="Included">
                                    ✓
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </section>
    );
}

function Pricing({ product, checkoutReady, owned }) {
    const plans = product.plans || [];
    const terms = [...new Set(plans.map((p) => p.term_months))].sort((a, b) => b - a);
    const [current, setCurrent] = useState(terms.includes(12) ? 12 : terms[0]);
    const { save } = pricing(plans);
    const shown = plans.filter((p) => p.term_months === current).sort((a, b) => (a.site_limit ?? 1e9) - (b.site_limit ?? 1e9));
    const featured = shown.find((p) => p.is_featured)?.id ?? (shown.length >= 3 ? shown[1].id : null);
    if (!plans.length) return null;
    return (
        <section id="pricing" className="product-section store-pricing">
            <div className="store-section-title">
                <span className="eyebrow">PRICING</span>
                <h2>Pick the plan that fits.</h2>
                <p>Every Pro plan includes all Pro features, updates and support. Choose how many websites you need.</p>
                {terms.length > 1 && (
                    <div className="store-billing" role="group" aria-label="Billing period">
                        {terms.map((t) => (
                            <button key={t} type="button" aria-pressed={current === t} onClick={() => setCurrent(t)}>
                                {termName(t)}
                                {t === 12 && save > 0 && <span>Save {save}%</span>}
                            </button>
                        ))}
                    </div>
                )}
            </div>
            <div className={`plan-grid count-${Math.min(shown.length, 4)}`}>
                {shown.map((plan) => {
                    const monthly = plans.find((m) => m.term_months === 1 && m.site_limit === plan.site_limit);
                    const saving = monthly && plan.term_months > 1 ? Math.round((1 - plan.price_minor / (monthly.price_minor * plan.term_months)) * 100) : 0;
                    const features = plan.features?.length ? plan.features : [`Use on ${seats(plan.site_limit).toLowerCase()}`, "All Pro features", `Updates & support for ${plan.term_months === 1 ? "1 month" : term(plan.term_months)}`, "Move your license between sites anytime"];
                    return (
                        <article key={plan.id} className={`plan-card ${plan.id === featured ? "featured" : ""}`}>
                            {plan.id === featured && <span className="plan-ribbon">Most popular</span>}
                            <span className="eyebrow">{plan.name}</span>
                            <h3>{seats(plan.site_limit)}</h3>
                            <div className="plan-price">
                                {price(plan.price_minor / plan.term_months)}
                                <span>/ month</span>
                            </div>
                            <p className="plan-billed">
                                {plan.term_months === 1 ? "Billed monthly" : `${price(plan.price_minor)} billed every ${term(plan.term_months)}`}
                                {saving > 0 && <strong> · save {saving}%</strong>}
                            </p>
                            {plan.description && <p className="plan-description">{plan.description}</p>}
                            <ul className="check-list">
                                {features.map((f) => (
                                    <li key={f}>{f}</li>
                                ))}
                            </ul>
                            <a className={`button ${plan.id === featured ? "" : "secondary"}`} href={`${account}/plans/${plan.id}`}>
                                Choose {plan.name} →
                            </a>
                        </article>
                    );
                })}
            </div>
            <p className="pricing-note">
                ✓ No automatic charges — you renew only when you choose to &nbsp; ✓ Pay by bank transfer, wallet or JazzCash &nbsp; ✓ Keep using the free edition anytime
            </p>
            {owned && (
                <p className="store-notice">
                    You already have an active license. <a href={`${account}/licenses/${owned}`}>Manage it or renew →</a>
                </p>
            )}
            {!checkoutReady && <p className="store-notice">Online purchasing is being set up. You can explore plans and use the free edition meanwhile.</p>}
        </section>
    );
}

function Changelog({ releases = [] }) {
    const [all, setAll] = useState(false);
    if (!releases.length) return null;
    const list = all ? releases : releases.slice(0, 3);
    return (
        <section id="changelog" className="product-section">
            <div className="store-section-title">
                <span className="eyebrow">WHAT’S NEW</span>
                <h2>Changelog</h2>
            </div>
            <ol className="changelog">
                {list.map((r) => (
                    <li key={r.id}>
                        <div className="changelog-head">
                            <strong>Version {r.version}</strong>
                            <StatusChip status={r.edition === "pro" ? "pro" : "free"} />
                            <span className="muted">{date(r.created_at)}</span>
                        </div>
                        {r.changelog ? <LessonBody content={r.changelog} /> : <p className="muted">Maintenance release.</p>}
                    </li>
                ))}
            </ol>
            {releases.length > 3 && (
                <button type="button" className="text-link" onClick={() => setAll(!all)}>
                    {all ? "Show fewer" : `Show all ${releases.length} releases`}
                </button>
            )}
        </section>
    );
}

function Faq({ product }) {
    const generic = [
        { q: `How do I activate ${product.name} Pro?`, a: "Choose a plan and complete payment. Once it’s verified, open My licenses & downloads, copy your license key, and paste it into the plugin’s license screen in WordPress." },
        { q: "What happens when my license expires?", a: "Your license gives you Pro updates and support for the term you paid for. Renew any time — renewals extend your current expiry date, so you never lose days." },
        { q: "Can I move my license to another website?", a: "Yes. Release the old website from your account, then activate the key on the new site. Each subdomain or subdirectory installation counts as one website." },
        { q: "How do I pay?", a: "Prices are in US dollars. Checkout shows the exact PKR amount before you pay. You can pay by bank transfer or mobile wallet (verified by our team), or online through JazzCash when available. There are no automatic recurring charges." },
    ];
    const items = [...(product.faq || []), ...generic.filter((g) => !(product.faq || []).some((f) => f.q.toLowerCase() === g.q.toLowerCase()))];
    return (
        <section id="faq" className="product-section store-faq">
            <div className="store-section-title">
                <span className="eyebrow">QUESTIONS</span>
                <h2>Good to know.</h2>
            </div>
            {items.map((item) => (
                <details key={item.q}>
                    <summary>{item.q}</summary>
                    <p className="preserve">{item.a}</p>
                </details>
            ))}
            {(product.docs_url || product.support_url) && (
                <p className="faq-links">
                    {product.docs_url && (
                        <a className="text-link" href={product.docs_url} target="_blank" rel="noopener noreferrer">
                            Read the documentation ↗
                        </a>
                    )}
                    {product.support_url && (
                        <a className="text-link" href={product.support_url} target="_blank" rel="noopener noreferrer">
                            Get support ↗
                        </a>
                    )}
                </p>
            )}
        </section>
    );
}

function Product({ product, freeRelease, checkoutReady, releases = [], owned }) {
    const plans = product.plans || [];
    const { from } = pricing(plans);
    const latest = releases[0];
    const req = product.requirements || {};
    const shot = product.screenshots?.[0];
    const sections = [
        [(product.free_features?.length || product.pro_features?.length) && "features", "Features"],
        [product.screenshots?.length && "screenshots", "Screenshots"],
        [plans.length && "pricing", "Pricing"],
        [releases.length && "changelog", "Changelog"],
        ["faq", "FAQ"],
    ].filter(([id]) => id);
    return (
        <>
            <header className="product-hero">
                <div>
                    <div className="product-title">
                        <ProductIcon product={product} size={64} />
                        <span className="eyebrow">{product.category || "WordPress plugin"}</span>
                    </div>
                    <h1>{product.name}</h1>
                    {product.tagline && <p className="product-tagline">{product.tagline}</p>}
                    <p>{product.summary}</p>
                    <div className="store-hero-actions">
                        {owned ? (
                            <a className="button" href={`${account}/licenses/${owned}`}>
                                Manage your license →
                            </a>
                        ) : (
                            from !== null && (
                                <a className="button" href="#pricing">
                                    Get Pro · from {price(from)}/mo
                                </a>
                            )
                        )}
                        {freeRelease && (
                            <a className="button secondary" href={`/products/releases/${freeRelease.id}/download`}>
                                Download free v{freeRelease.version} ↓
                            </a>
                        )}
                    </div>
                    <dl className="product-meta">
                        {latest && (
                            <div>
                                <dt>Version</dt>
                                <dd>{latest.version}</dd>
                            </div>
                        )}
                        {latest && (
                            <div>
                                <dt>Updated</dt>
                                <dd>{date(latest.created_at)}</dd>
                            </div>
                        )}
                        {req.wordpress && (
                            <div>
                                <dt>WordPress</dt>
                                <dd>{req.wordpress}+</dd>
                            </div>
                        )}
                        {req.php && (
                            <div>
                                <dt>PHP</dt>
                                <dd>{req.php}+</dd>
                            </div>
                        )}
                        {req.tested && (
                            <div>
                                <dt>Tested up to</dt>
                                <dd>{req.tested}</dd>
                            </div>
                        )}
                    </dl>
                </div>
                <div className="product-visual">
                    {shot ? (
                        <figure className="browser-frame">
                            <div className="browser-bar" aria-hidden="true">
                                <i />
                                <i />
                                <i />
                            </div>
                            <img src={"/storage/" + shot.path} alt={shot.caption || `${product.name} screenshot`} />
                        </figure>
                    ) : (
                        <div className="product-art" aria-hidden="true">
                            <ProductIcon product={product} size={120} />
                        </div>
                    )}
                </div>
            </header>
            {sections.length > 1 && (
                <nav className="product-subnav" aria-label="On this page">
                    {sections.map(([id, title]) => (
                        <a key={id} href={`#${id}`}>
                            {title}
                        </a>
                    ))}
                </nav>
            )}
            <Compare product={product} hasFree={!!freeRelease || (product.free_features || []).length > 0} />
            {product.description && (
                <section className="product-section product-description">
                    <LessonBody content={product.description} />
                </section>
            )}
            <Gallery shots={product.screenshots} />
            <Pricing product={product} checkoutReady={checkoutReady} owned={owned} />
            <Changelog releases={releases} />
            <Faq product={product} />
            {from !== null && !owned && (
                <div className="mobile-buy-bar">
                    <span>
                        Pro from <strong>{price(from)}</strong>/mo
                    </span>
                    <a className="button small" href="#pricing">
                        See plans
                    </a>
                </div>
            )}
        </>
    );
}

const STEPS = ["Review plan", "Pay", "Verification", "License ready"];
function Tracker({ step }) {
    return (
        <ol className="order-tracker">
            {STEPS.map((text, i) => (
                <li key={text} className={i < step ? "done" : i === step ? "current" : ""} aria-current={i === step ? "step" : undefined}>
                    <span>{i < step ? "✓" : i + 1}</span>
                    {text}
                </li>
            ))}
        </ol>
    );
}

function CheckoutReview({ plan, rate, renewal }) {
    const product = plan.product;
    const newExpiry = renewal ? new Date(Math.max(Date.now(), new Date(renewal.expires_at))) : new Date();
    newExpiry.setMonth(newExpiry.getMonth() + plan.term_months);
    return (
        <>
            <Heading eyebrow={renewal ? "RENEW YOUR LICENSE" : "CHECKOUT"} title={renewal ? "Extend your Pro access." : "Review your plan."} description="Check the details, then create your order to see payment options." />
            <Tracker step={0} />
            <div className="checkout-layout">
                <section className="store-panel">
                    <div className="product-title">
                        <ProductIcon product={product} size={52} />
                        <div>
                            <h2>{product.name}</h2>
                            <p className="muted">
                                {plan.name} plan · {seats(plan.site_limit)}
                            </p>
                        </div>
                    </div>
                    <ul className="check-list">
                        {(plan.features?.length ? plan.features : ["All Pro features", `Use on ${seats(plan.site_limit).toLowerCase()}`, `Updates & support for ${term(plan.term_months)}`]).map((f) => (
                            <li key={f}>{f}</li>
                        ))}
                    </ul>
                    <h3>What happens next</h3>
                    <ol className="next-steps">
                        <li>
                            <strong>Create your order</strong> — we lock today’s PKR price for you.
                        </li>
                        <li>
                            <strong>Pay</strong> by bank transfer, wallet or JazzCash and upload your receipt.
                        </li>
                        <li>
                            <strong>We verify</strong> your payment — usually within a few hours.
                        </li>
                        <li>
                            <strong>Your license key</strong> appears in your account and is emailed to you.
                        </li>
                    </ol>
                </section>
                <aside className="store-panel checkout-summary">
                    <h2>Order summary</h2>
                    <dl>
                        <dt>{plan.name}</dt>
                        <dd>{price(plan.price_minor)}</dd>
                        <dt>Term</dt>
                        <dd>1 {term(plan.term_months)}</dd>
                        {renewal && (
                            <>
                                <dt>Current expiry</dt>
                                <dd>{date(renewal.expires_at)}</dd>
                            </>
                        )}
                        <dt>{renewal ? "New expiry" : "Valid until"}</dt>
                        <dd>≈ {date(newExpiry)}</dd>
                    </dl>
                    {rate > 0 ? (
                        <div className="checkout-total">
                            <span>You pay</span>
                            <strong>{price(Math.round(plan.price_minor * Number(rate)), "PKR")}</strong>
                            <small>
                                at PKR {Number(rate).toLocaleString()} per US$1 · confirmed on your order
                            </small>
                        </div>
                    ) : (
                        <p className="notice">Payments are being set up. Please check back soon or contact us.</p>
                    )}
                    {rate > 0 && (
                        <Form action={`${account}/plans/${plan.id}/checkout`} submit={renewal ? "Create renewal order →" : "Create order & pay →"}>
                            <input type="hidden" name="renewal_license_id" value={renewal?.id || ""} />
                            <label className="check-field">
                                <input type="checkbox" required /> I understand the term, website limit and that renewal is manual.
                            </label>
                        </Form>
                    )}
                    <p className="muted small">No automatic billing. The expiry date starts when payment is verified{renewal ? ", or extends your current term" : ""}.</p>
                </aside>
            </div>
        </>
    );
}

function Countdown({ until }) {
    const [, tick] = useState(0);
    useEffect(() => {
        const t = setInterval(() => tick((n) => n + 1), 30000);
        return () => clearInterval(t);
    }, []);
    const ms = new Date(until) - Date.now();
    if (ms <= 0) return <span className="status-chip status-expired">Quote expired</span>;
    const h = Math.floor(ms / 3600000),
        m = Math.floor((ms % 3600000) / 60000);
    return <span className="status-chip status-pending">Price held for {h ? `${h} h ` : ""}{m} min</span>;
}

function OrdersTable({ orders, adminMode = false }) {
    return (
        <>
            <Table
                data={orders}
                empty="No orders yet"
                columns={[
                    {
                        title: "Order",
                        render: (o) => (
                            <>
                                <strong>{o.product_name}</strong>
                                <small className="store-block">
                                    {o.plan_name} · {date(o.created_at)}
                                </small>
                            </>
                        ),
                    },
                    ...(adminMode
                        ? [
                              {
                                  title: "Customer",
                                  render: (o) => (
                                      <>
                                          {o.user?.name}
                                          <small className="store-block">{o.user?.email}</small>
                                      </>
                                  ),
                              },
                          ]
                        : []),
                    { title: "Total", render: (o) => price(o.amount_minor, o.currency) },
                    {
                        title: "Status",
                        render: (o) => {
                            const reviewing = o.status === "pending" && (o.payments || []).some((p) => ["pending", "review_required"].includes(p.status));
                            return <StatusChip status={reviewing ? "review" : o.status} />;
                        },
                    },
                    {
                        title: "",
                        render: (o) => (
                            <a className="table-action" href={`${adminMode ? admin : account}/orders/${o.reference}`}>
                                {adminMode ? "Open →" : o.status === "pending" ? "Complete payment →" : "View →"}
                            </a>
                        ),
                    },
                ]}
            />
            <Pagination data={orders} />
        </>
    );
}

function Library({ licenses, orders, latest = {} }) {
    const pending = rows(orders).filter((o) => o.status === "pending");
    return (
        <>
            <Heading eyebrow="YOUR ACCOUNT" title="Licenses & downloads" description="Your plugins, license keys, websites and orders in one place.">
                <a className="button secondary" href="/products">
                    Browse products →
                </a>
            </Heading>
            {pending.map((o) => {
                const reviewing = (o.payments || []).some((p) => ["pending", "review_required"].includes(p.status));
                return (
                    <div className={`order-banner ${reviewing ? "info" : ""}`} key={o.id}>
                        <div>
                            <strong>
                                {o.product_name} · {o.plan_name}
                            </strong>
                            <span>{reviewing ? "We’re verifying your payment. Your license appears here once it’s approved." : "Your order is waiting for payment."}</span>
                        </div>
                        <a className={reviewing ? "button secondary small" : "button small"} href={`${account}/orders/${o.reference}`}>
                            {reviewing ? "View order" : "Complete payment →"}
                        </a>
                    </div>
                );
            })}
            <div className="license-grid">
                {rows(licenses).map((l) => {
                    const state = licenseState(l);
                    const left = daysLeft(l.expires_at);
                    const download = latest[l.digital_product_id];
                    return (
                        <article className="store-panel license-card" key={l.id}>
                            <header>
                                <ProductIcon product={l.product} size={48} />
                                <div>
                                    <h2>{l.product.name}</h2>
                                    <StatusChip status={state} />
                                </div>
                            </header>
                            <Meter value={l.activations_count} max={l.site_limit} label="Websites used" />
                            <div className="usage-meter">
                                <div className="usage-label">
                                    <span>{state === "expired" ? "Expired" : "Valid until"}</span>
                                    <strong>
                                        {date(l.expires_at)}
                                        {state !== "expired" && state !== "revoked" && ` · ${left} day${left === 1 ? "" : "s"} left`}
                                    </strong>
                                </div>
                            </div>
                            <code className="key-hint">{l.key_hint}</code>
                            <div className="row-actions">
                                <a className="button small" href={`${account}/licenses/${l.id}`}>
                                    Manage & get key
                                </a>
                                {download && live(l) && (
                                    <a className="button small secondary" href={`/products/releases/${download.id}/download`}>
                                        Download v{download.version} ↓
                                    </a>
                                )}
                                {(state === "expiring" || state === "expired") && (
                                    <a className="text-link" href={`${account}/licenses/${l.id}#renew`}>
                                        Renew →
                                    </a>
                                )}
                            </div>
                        </article>
                    );
                })}
            </div>
            {!rows(licenses).length && (
                <div className="store-panel library-empty">
                    <Empty title="No licenses yet" description="Pick a plugin and complete payment — your license key and downloads will appear here." />
                    <a className="button" href="/products">
                        Explore products →
                    </a>
                </div>
            )}
            <Pagination data={licenses} />
            <section className="store-panel">
                <h2>Order history</h2>
                <OrdersTable orders={orders} />
            </section>
        </>
    );
}

function Order({ order, methods = {}, onlineReady, licenseId, adminMode = false }) {
    const [method, setMethod] = useState(Object.keys(methods)[0] || "");
    const [review, setReview] = useState(null);
    const waiting = order.payments.filter((p) => ["pending", "review_required"].includes(p.status));
    const blocked = order.payments.some((p) => ["pending", "approved", "review_required"].includes(p.status));
    const expired = new Date(order.quote_expires_at) <= new Date();
    const payable = order.status === "pending" && !blocked && !expired;
    const step = order.status === "paid" ? 4 : waiting.length ? 2 : 1;
    return (
        <>
            <Heading eyebrow={adminMode ? "ORDER REVIEW" : "YOUR ORDER"} title={order.product_name} description={`${order.plan_name} · ${seats(order.site_limit)} · 1 ${term(order.term_months)}`} />
            {order.status !== "cancelled" && <Tracker step={step} />}
            {adminMode && waiting.length > 0 && (
                <div className="order-banner">
                    <div>
                        <strong>
                            {waiting.length} payment{waiting.length === 1 ? "" : "s"} waiting for your review
                        </strong>
                        <span>Check the amount and transaction ID in your receiving account, then approve to issue the license.</span>
                    </div>
                    <button type="button" className="button small" onClick={() => setReview(waiting[0])}>
                        Review payment
                    </button>
                </div>
            )}
            <div className="store-order-layout">
                <aside className="store-panel store-order-summary">
                    <StatusChip status={order.status === "pending" && waiting.length ? "review" : order.status} />
                    <div className="order-amount">{price(order.amount_minor, order.currency)}</div>
                    <p className="muted">
                        {price(order.usd_minor)} at PKR {Number(order.usd_pkr_rate).toLocaleString()} per US$1
                    </p>
                    {order.status === "pending" && !waiting.length && <Countdown until={order.quote_expires_at} />}
                    <dl>
                        <dt>Order reference</dt>
                        <dd className="with-copy">
                            <span className="store-break">{order.reference}</span>
                            <CopyButton value={order.reference} />
                        </dd>
                        <dt>Created</dt>
                        <dd>{date(order.created_at)}</dd>
                        {order.user && (
                            <>
                                <dt>Customer</dt>
                                <dd>
                                    {order.user.name}
                                    <small className="store-block">{order.user.email}</small>
                                </dd>
                            </>
                        )}
                        {order.renewal_license_id && (
                            <>
                                <dt>Type</dt>
                                <dd>Renewal</dd>
                            </>
                        )}
                    </dl>
                    {order.status === "paid" && !adminMode && licenseId && (
                        <a className="button" href={`${account}/licenses/${licenseId}`}>
                            Open your license →
                        </a>
                    )}
                    {!adminMode && order.status === "pending" && !blocked && <Form action={`${account}/orders/${order.reference}/cancel`} submit="Cancel order" className="inline-form" confirm="Cancel this unpaid order?" />}
                </aside>
                <div className="store-stack">
                    {order.status === "paid" && !adminMode && (
                        <section className="store-panel success-panel">
                            <span aria-hidden="true">🎉</span>
                            <div>
                                <h2>You’re all set!</h2>
                                <p>Your payment is verified and your license is active. Open it to copy your key and download the plugin.</p>
                            </div>
                        </section>
                    )}
                    {payable && !adminMode && (
                        <section className="store-panel">
                            <h2>Pay {price(order.amount_minor, order.currency)}</h2>
                            {onlineReady && (
                                <div className="pay-online">
                                    <div>
                                        <strong>Pay online with JazzCash</strong>
                                        <p>Fastest — your license is issued as soon as the payment completes.</p>
                                    </div>
                                    <Form action={`${account}/orders/${order.reference}/checkout`} submit="Pay with JazzCash →" />
                                </div>
                            )}
                            {Object.keys(methods).length > 0 && (
                                <>
                                    {onlineReady && <p className="or-divider">or pay manually</p>}
                                    <ol className="pay-steps">
                                        <li>Choose an account and send exactly the amount above.</li>
                                        <li>Enter the transaction ID and upload a screenshot or PDF receipt.</li>
                                        <li>We verify it and email your license key.</li>
                                    </ol>
                                    <PaymentMethodCards methods={methods} selected={method} onSelect={setMethod} />
                                    <PaymentInstructions method={methods[method]} />
                                    <Form action={`${account}/orders/${order.reference}/payments`} submit="Submit payment for verification">
                                        <input type="hidden" name="method" value={method} />
                                        <Field name="transaction_reference" title="Transaction ID from your receipt" required maxLength={100} pattern="[A-Za-z0-9-]+" placeholder="e.g. TXN12345678" />
                                        <Field name="proof" title="Receipt (image or PDF, up to 5 MB)" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" required />
                                    </Form>
                                </>
                            )}
                            {!Object.keys(methods).length && !onlineReady && <Empty title="Payment methods are being set up" description="Please contact us before sending any payment." />}
                        </section>
                    )}
                    {order.status === "pending" && !payable && !adminMode && (
                        <section className="store-panel">
                            <h2>{blocked ? "We’re checking your payment" : "This price quote has expired"}</h2>
                            <p>{blocked ? "Your license will appear in your account as soon as the payment is verified — usually within a few hours. Please don’t send a second payment." : "Exchange rates change, so quotes are held for a limited time. Start a new order to get today’s price."}</p>
                            <a className="text-link" href={blocked ? "/chat" : "/products"}>
                                {blocked ? "Message payment support →" : "Start a new order →"}
                            </a>
                        </section>
                    )}
                    {order.payments.length > 0 && (
                    <section className="store-panel">
                        <h2>Payments</h2>
                        <Table
                            data={order.payments}
                            columns={[
                                { title: "Method", render: (p) => label(p.method) },
                                {
                                    title: "Reference",
                                    render: (p) => (
                                        <>
                                            <span className="store-break">{p.transaction_reference || p.reference}</span>
                                            {p.review_note && <small className="store-block">{p.review_note}</small>}
                                        </>
                                    ),
                                },
                                { title: "Submitted", render: (p) => date(p.created_at) },
                                { title: "Status", render: (p) => <StatusChip status={p.status} /> },
                                {
                                    title: "",
                                    render: (p) => (
                                        <div className="store-row-actions">
                                            {p.method !== "jazzcash_online" && <a href={`${account}/payments/${p.id}/proof`}>Receipt ↓</a>}
                                            {adminMode && ["pending", "review_required"].includes(p.status) && (
                                                <button className="button small" type="button" onClick={() => setReview(p)}>
                                                    Review
                                                </button>
                                            )}
                                        </div>
                                    ),
                                },
                            ]}
                        />
                    </section>
                    )}
                </div>
            </div>
            <Modal open={!!review} onClose={() => setReview(null)} title="Review payment">
                <ModalErrors />
                {review && (
                    <Form action={`${admin}/payments/${review.id}`} method="PATCH" submit="Save review">
                        <dl className="store-details">
                            <dt>Expected amount</dt>
                            <dd>
                                <strong>{price(order.amount_minor, order.currency)}</strong>
                            </dd>
                            <dt>Method</dt>
                            <dd>{label(review.method)}</dd>
                            <dt>Transaction ID</dt>
                            <dd className="with-copy">
                                <code>{review.transaction_reference || review.reference}</code>
                                <CopyButton value={review.transaction_reference || review.reference} />
                            </dd>
                        </dl>
                        {review.method !== "jazzcash_online" && (
                            <a className="text-link" href={`${account}/payments/${review.id}/proof`} target="_blank" rel="noopener noreferrer">
                                Open the customer’s receipt ↗
                            </a>
                        )}
                        <Field
                            name="status"
                            type="select"
                            title="Decision"
                            options={[
                                { value: "approved", label: "Approve — funds received (issues the license)" },
                                { value: review.method === "jazzcash_online" ? "resolved" : "rejected", label: "Not received — ask the customer to try again" },
                            ]}
                        />
                        <Field name="review_note" title="Note (shown to the customer if rejected)" type="textarea" rows={3} required minLength={8} maxLength={2000} placeholder="e.g. Verified in Meezan statement, 26 Oct" />
                    </Form>
                )}
            </Modal>
        </>
    );
}

function License({ license, plans = [], releases = [] }) {
    const [key, setKey] = useState("");
    const [error, setError] = useState("");
    const [busy, setBusy] = useState(false);
    const state = licenseState(license);
    const product = license.product;
    const latestPro = releases.find((r) => r.edition === "pro");
    async function reveal() {
        setBusy(true);
        setError("");
        try {
            setKey((await api(`${account}/licenses/${license.id}/key`, { method: "POST" })).key);
        } catch (e) {
            setError(e.message);
        } finally {
            setBusy(false);
        }
    }
    const steps = product.activation_steps?.length ? product.activation_steps : ["Download the latest Pro ZIP below and install it in WordPress → Plugins → Add New → Upload.", `Open the ${product.name} license screen in your WordPress admin.`, "Paste your license key and click Activate."];
    return (
        <>
            <header className="license-hero">
                <ProductIcon product={product} size={64} />
                <div>
                    <span className="eyebrow">LICENSE</span>
                    <h1 className="page-title">{product.name}</h1>
                    <div className="hub-chips">
                        <StatusChip status={state} />
                        <span className="soft-chip">{seats(license.site_limit)}</span>
                        <span className="soft-chip">
                            {state === "expired" ? "Expired" : "Valid until"} {date(license.expires_at)}
                        </span>
                    </div>
                </div>
            </header>
            {state === "expiring" && (
                <div className="order-banner">
                    <div>
                        <strong>Your license expires in {daysLeft(license.expires_at)} days</strong>
                        <span>Renew now — the new term is added to your current expiry date.</span>
                    </div>
                    <a className="button small" href="#renew">
                        Renew
                    </a>
                </div>
            )}
            <div className="license-layout">
                <section className="store-panel">
                    <h2>Your license key</h2>
                    <p>Keep it private — anyone with the key can activate it on their website.</p>
                    <div className="key-box">
                        <code className="store-license-key">{key || license.key_hint}</code>
                        {key ? (
                            <>
                                <CopyButton value={key} text="Copy key" />
                                <button type="button" className="copy-button" onClick={() => setKey("")}>
                                    Hide
                                </button>
                            </>
                        ) : (
                            <button type="button" className="button small" disabled={busy || license.status === "revoked"} onClick={reveal}>
                                {busy ? "Loading…" : "Show full key"}
                            </button>
                        )}
                    </div>
                    {error && (
                        <p role="alert" className="notice error">
                            {error}
                        </p>
                    )}
                    <h3>Activate in 3 steps</h3>
                    <ol className="next-steps">
                        {steps.map((s) => (
                            <li key={s}>{s}</li>
                        ))}
                    </ol>
                    {latestPro && live(license) && (
                        <a className="button" href={`/products/releases/${latestPro.id}/download`}>
                            Download Pro v{latestPro.version} ↓
                        </a>
                    )}
                </section>
                <section className="store-panel" id="renew">
                    <h2>{state === "expired" ? "Renew to get updates again" : "Renew or extend"}</h2>
                    <p>Renewals add time to your current expiry date — or start today if it has already expired.</p>
                    {license.status === "active" ? (
                        plans.length ? (
                            plans.map((p) => (
                                <a className="store-renew" key={p.id} href={`${account}/plans/${p.id}?renewal=${license.id}`}>
                                    <span>
                                        {p.name}
                                        <small>
                                            1 {term(p.term_months)} · {seats(p.site_limit)}
                                        </small>
                                    </span>
                                    <strong>{price(p.price_minor)} →</strong>
                                </a>
                            ))
                        ) : (
                            <p className="muted">No renewal plans are available for this website limit right now. Please contact support.</p>
                        )
                    ) : (
                        <p>This license was revoked. Please contact support.</p>
                    )}
                </section>
            </div>
            <section className="store-panel">
                <div className="theme-section-heading">
                    <h2>Activated websites</h2>
                    <div style={{ minWidth: 220 }}>
                        <Meter value={license.activations.length} max={license.site_limit} label="Used" />
                    </div>
                </div>
                <Table
                    data={license.activations}
                    columns={[
                        { title: "Website", render: (a) => <span className="store-break">{a.site_url}</span> },
                        { title: "Last checked", render: (a) => date(a.last_checked_at) },
                        { title: "", render: (a) => <Form action={`${account}/licenses/${license.id}/activations/${a.id}`} method="DELETE" submit="Release website" className="inline-form release-form" confirm="Release this website? It loses Pro access at its next license check, and the seat becomes free." /> },
                    ]}
                    empty="No websites yet — activate the key in WordPress and it will show up here."
                />
            </section>
            <section className="store-panel">
                <h2>Downloads</h2>
                <Table
                    data={releases}
                    columns={[
                        {
                            title: "Version",
                            render: (r) => (
                                <>
                                    <strong>{r.version}</strong> {r === releases.find((x) => x.edition === r.edition) && <span className="soft-chip">Latest</span>}
                                    {r.changelog && <small className="store-block clamp-2">{r.changelog}</small>}
                                </>
                            ),
                        },
                        { title: "Edition", render: (r) => <StatusChip status={r.edition} /> },
                        { title: "Released", render: (r) => date(r.created_at) },
                        {
                            title: "",
                            render: (r) =>
                                r.edition === "free" || live(license) ? (
                                    <a className="button small secondary" href={`/products/releases/${r.id}/download`}>
                                        ZIP ↓
                                    </a>
                                ) : (
                                    <span className="muted">Renew to download</span>
                                ),
                        },
                    ]}
                />
            </section>
        </>
    );
}

// --- Admin ----------------------------------------------------------------------------------------

function AdminProducts({ products, stats, rate }) {
    const [settings, setSettings] = useState(false);
    return (
        <>
            <Heading eyebrow="PRODUCT STORE" title="Products & plans" description="Your plugins, their pricing and releases.">
                <a className="button" href={admin + "/products/create"}>
                    New product +
                </a>
            </Heading>
            <div className="stats">
                {[
                    ["Products", stats.products],
                    ["Active licenses", stats.activeLicenses],
                    ["Paid orders", stats.paidOrders],
                ].map(([t, v]) => (
                    <div className="stat" key={t}>
                        <span>{t}</span>
                        <strong>{v}</strong>
                    </div>
                ))}
                <a className={`stat ${stats.pendingPayments ? "stat-alert" : ""}`} href={admin + "/orders?status=review"}>
                    <span>Payments to review</span>
                    <strong>{stats.pendingPayments}</strong>
                    <i aria-hidden="true">→</i>
                </a>
            </div>
            <div className={`order-banner ${rate ? "info" : ""}`}>
                <div>
                    <strong>{rate ? `Exchange rate: US$1 = PKR ${Number(rate).toLocaleString()}` : "Set an exchange rate to start selling"}</strong>
                    <span>{rate ? "New orders are quoted in PKR at this rate. Existing orders keep their quote." : "Checkout stays closed until a USD-to-PKR rate is saved."}</span>
                </div>
                <button className="button small secondary" type="button" onClick={() => setSettings(true)}>
                    {rate ? "Change rate" : "Set rate"}
                </button>
            </div>
            <section className="store-panel">
                <Form action={admin + "/products"} method="GET" submit="Search" className="search-form store-filter">
                    <Field name="search" title="Find a product" value={query("search")} />
                </Form>
                <div className="admin-product-list">
                    {rows(products).map((p) => (
                        <article key={p.id} className="admin-product-row">
                            <ProductIcon product={p} size={48} />
                            <div className="admin-product-name">
                                <a href={`${admin}/products/${p.id}`}>
                                    <strong>{p.name}</strong>
                                </a>
                                <small>{p.slug}</small>
                            </div>
                            <StatusChip status={p.published ? "published" : "draft"} />
                            <dl>
                                <div>
                                    <dt>Plans</dt>
                                    <dd>{p.plans_count}</dd>
                                </div>
                                <div>
                                    <dt>Releases</dt>
                                    <dd>{p.releases_count}</dd>
                                </div>
                                <div>
                                    <dt>Orders</dt>
                                    <dd>{p.orders_count}</dd>
                                </div>
                            </dl>
                            <div className="store-row-actions">
                                <a className="button small secondary" href={`${admin}/products/${p.id}/edit`}>
                                    Edit
                                </a>
                                {p.published && (
                                    <a className="text-link" href={`/products/${p.slug}`}>
                                        Store page ↗
                                    </a>
                                )}
                            </div>
                        </article>
                    ))}
                    {!rows(products).length && <Empty title="No products yet" description="Create your first product, add plans, then upload a plugin release." />}
                </div>
                <Pagination data={products} />
            </section>
            <Modal open={settings} onClose={() => setSettings(false)} title="Checkout exchange rate">
                <Form action={admin + "/settings"} method="PUT" submit="Save rate">
                    <p>Prices are set in US dollars; customers pay the PKR amount at this rate. It applies to new orders only.</p>
                    <Field name="usd_pkr_rate" title="PKR per US$1" type="number" min="1" max="10000" step="0.0001" value={rate} required />
                    <Field name="current_password" title="Your password (to confirm)" type="password" autoComplete="current-password" required />
                    <a className="text-link" href="/admin/education/settings">
                        Set up receiving accounts, QR codes & JazzCash →
                    </a>
                </Form>
            </Modal>
        </>
    );
}

function FaqEditor({ items = [] }) {
    const [list, setList] = useState(items.length ? items : []);
    return (
        <div className="faq-editor">
            {list.map((item, i) => (
                <div className="faq-row" key={i}>
                    <Field name={`faq[${i}][q]`} title={`Question ${i + 1}`} value={item.q} maxLength={300} />
                    <Field name={`faq[${i}][a]`} title="Answer" type="textarea" rows={3} value={item.a} maxLength={3000} />
                    <button type="button" className="text-link danger-text" onClick={() => setList(list.filter((_, j) => j !== i))}>
                        Remove
                    </button>
                </div>
            ))}
            <button type="button" className="button small secondary" onClick={() => setList([...list, { q: "", a: "" }])} disabled={list.length >= 30}>
                Add question +
            </button>
            <p className="muted small">Common questions about activation, expiry, moving sites and payment are added automatically unless you write your own.</p>
        </div>
    );
}

const EDITOR_TABS = [
    ["details", "Details"],
    ["media", "Icon & screenshots"],
    ["features", "Features & FAQ"],
    ["plans", "Plans & pricing"],
    ["releases", "Releases"],
];

function Editor({ product }) {
    const initial = query("tab");
    const fromForm = context.old._form_action || "";
    const [tab, setTabState] = useState(EDITOR_TABS.some(([k]) => k === initial) ? initial : /\/plans/.test(fromForm) ? "plans" : /\/releases/.test(fromForm) ? "releases" : "details");
    const [plan, setPlan] = useState(() => (/\/plans/.test(fromForm) ? {} : null));
    const [upload, setUpload] = useState(() => /\/releases$/.test(fromForm));
    const setTab = (t) => {
        setTabState(t);
        const url = new URL(location.href);
        url.searchParams.set("tab", t);
        history.replaceState(null, "", url);
    };
    const formTabs = ["details", "media", "features"];
    const tabs = product ? EDITOR_TABS : EDITOR_TABS.slice(0, 3);
    const latest = (edition) => product?.releases?.filter((r) => r.edition === edition).sort((a, b) => b.id - a.id)[0];
    return (
        <>
            <Heading eyebrow="PRODUCT STUDIO" title={product ? product.name : "New product"} description={product ? `Identifier: ${product.slug}` : "Use the WordPress plugin folder name as the identifier — it can’t change later."}>
                {product?.published && (
                    <a className="button secondary" href={`/products/${product.slug}`} target="_blank" rel="noopener noreferrer">
                        View store page ↗
                    </a>
                )}
                {product && (
                    <a className="button secondary" href={`${admin}/products/${product.id}`}>
                        Sales & overview
                    </a>
                )}
            </Heading>
            <nav className="hub-tabs" aria-label="Product sections">
                {tabs.map(([key, title]) => (
                    <button key={key} type="button" aria-current={tab === key ? "page" : undefined} onClick={() => setTab(key)}>
                        {title}
                        {key === "plans" && <small>{product.plans.length}</small>}
                        {key === "releases" && <small>{product.releases.length}</small>}
                    </button>
                ))}
            </nav>
            {/* One form spans the first three tabs; inactive panels are hidden, not removed, so every field submits. */}
            <Form action={product ? `${admin}/products/${product.id}` : `${admin}/products`} method={product ? "PUT" : "POST"} submit={product ? "Save product" : "Create product"} hidden={!formTabs.includes(tab)} className="product-form">
                <input type="hidden" name="_tab" value={tab} />
                <section className="store-panel" hidden={tab !== "details"}>
                    <div className="store-field-grid">
                        <Field name="name" title="Product name" value={product?.name} required maxLength={150} />
                        <Field name="slug" title="Plugin identifier (folder name)" value={product?.slug} required readOnly={!!product} placeholder="my-wordpress-plugin" pattern="[a-z0-9]+(-[a-z0-9]+)*" />
                    </div>
                    <div className="store-field-grid">
                        <Field name="tagline" title="Tagline (one short line)" value={product?.tagline} maxLength={160} placeholder="Beautiful forms in minutes." />
                        <Field name="category" title="Category" value={product?.category || "WordPress plugin"} maxLength={60} />
                    </div>
                    <Field name="summary" title="Summary (shown on cards and at the top of the page)" type="textarea" rows={2} value={product?.summary} required maxLength={500} />
                    <div className="field">
                        <span>Full description</span>
                        <RichEditor name="description" value={product?.description} minHeight={240} placeholder="Describe what the plugin does, who it’s for and why it’s great…" />
                    </div>
                    <h3>Requirements</h3>
                    <div className="store-field-grid three">
                        <Field name="requirements[wordpress]" title="Minimum WordPress" value={product?.requirements?.wordpress} placeholder="6.0" maxLength={20} />
                        <Field name="requirements[php]" title="Minimum PHP" value={product?.requirements?.php} placeholder="7.4" maxLength={20} />
                        <Field name="requirements[tested]" title="Tested up to" value={product?.requirements?.tested} placeholder="6.8" maxLength={20} />
                    </div>
                    <div className="store-field-grid">
                        <Field name="docs_url" title="Documentation link" type="url" value={product?.docs_url} placeholder="https://…" />
                        <Field name="support_url" title="Support link" type="url" value={product?.support_url} placeholder="https://…" />
                    </div>
                    <div className="store-field-grid">
                        <Field name="sort_order" title="Catalog order (lower shows first)" type="number" min={0} max={10000} value={product?.sort_order ?? 100} />
                        <Field name="published" title="Show this product in the store" type="checkbox" value={product?.published} />
                    </div>
                </section>
                <section className="store-panel" hidden={tab !== "media"}>
                    <h2>Product icon</h2>
                    <div className="theme-image-field">
                        <div className="theme-image-preview">{product ? <ProductIcon product={product} size={72} /> : <span>None</span>}</div>
                        <div>
                            <Field name="icon" title="Square PNG, JPG or WebP (256×256 or larger, up to 2 MB)" type="file" accept=".png,.jpg,.jpeg,.webp" />
                            {product?.icon_url && <Field name="remove_icon" title="Remove icon (use initials instead)" type="checkbox" />}
                        </div>
                    </div>
                    <h2>Screenshots</h2>
                    <p className="muted">Up to 8 images. The first one is used on the catalog card and the top of the product page.</p>
                    <div className="shot-editor">
                        {(product?.screenshots || []).map((s) => (
                            <div className="shot-item" key={s.path}>
                                <img src={"/storage/" + s.path} alt="" />
                                <Field name={`captions[${s.path}]`} title="Caption" value={s.caption} maxLength={160} />
                                <label className="check-field">
                                    <input type="checkbox" name="remove_screenshots[]" value={s.path} /> Remove
                                </label>
                            </div>
                        ))}
                    </div>
                    <Field name="screenshots[]" title="Add screenshots (PNG, JPG or WebP, up to 6 MB each)" type="file" accept=".png,.jpg,.jpeg,.webp" multiple />
                </section>
                <section className="store-panel" hidden={tab !== "features"}>
                    <div className="store-field-grid">
                        <Field name="free_features" title="Free features (one per line)" type="textarea" rows={8} value={product?.free_features?.join("\n")} />
                        <Field name="pro_features" title="Pro-only features (one per line)" type="textarea" rows={8} value={product?.pro_features?.join("\n")} />
                    </div>
                    <small className="field-hint">These build the Free vs Pro comparison table on the product page.</small>
                    <Field name="activation_steps" title="Activation steps shown with the license key (one per line)" type="textarea" rows={4} value={product?.activation_steps?.join("\n")} placeholder={"Install the Pro ZIP in WordPress → Plugins → Add New → Upload.\nOpen Settings → License.\nPaste your key and click Activate."} />
                    <h2>FAQ</h2>
                    <FaqEditor items={product?.faq || []} />
                </section>
            </Form>
            {product && tab === "plans" && (
                <section className="store-panel">
                    <div className="theme-section-heading">
                        <div>
                            <h2>Plans & pricing</h2>
                            <p className="muted">Prices are in USD for the whole term. Mark one plan per term as “Most popular”.</p>
                        </div>
                        <button className="button small" type="button" onClick={() => setPlan({})}>
                            Add plan +
                        </button>
                    </div>
                    <div className="admin-plan-grid">
                        {[...product.plans]
                            .sort((a, b) => a.term_months - b.term_months || a.price_minor - b.price_minor)
                            .map((p) => (
                                <article key={p.id} className={`admin-plan ${p.is_featured ? "featured" : ""}`}>
                                    <header>
                                        <strong>{p.name}</strong>
                                        <StatusChip status={p.published ? "published" : "draft"} />
                                    </header>
                                    <div className="plan-price">
                                        {price(p.price_minor)}
                                        <span>/ {term(p.term_months)}</span>
                                    </div>
                                    <p className="muted">
                                        {seats(p.site_limit)}
                                        {p.term_months > 1 && ` · ${price(p.price_minor / p.term_months)}/mo`}
                                    </p>
                                    {p.is_featured && <span className="soft-chip">★ Most popular</span>}
                                    <div className="store-row-actions">
                                        <button className="button small secondary" type="button" onClick={() => setPlan(p)}>
                                            Edit
                                        </button>
                                        <DeleteAction action={`${admin}/products/${product.id}/plans/${p.id}`} name={p.name} message="Plans that customers have ordered can’t be deleted — turn off “Available for purchase” instead." />
                                    </div>
                                </article>
                            ))}
                    </div>
                    {!product.plans.length && <Empty title="No plans yet" description="Add at least one plan so customers can buy Pro." />}
                </section>
            )}
            {product && tab === "releases" && (
                <section className="store-panel">
                    <div className="theme-section-heading">
                        <div>
                            <h2>Releases</h2>
                            <p className="muted">
                                Upload the plugin ZIP. It must contain <code>{product.slug}/{product.slug}.php</code> with a matching Version header. Pro ZIPs download only with an active license.
                            </p>
                        </div>
                        <button className="button small" type="button" onClick={() => setUpload(true)}>
                            Upload release +
                        </button>
                    </div>
                    <div className="release-latest">
                        {["free", "pro"].map((e) => (
                            <div key={e}>
                                <span>Latest {e}</span>
                                <strong>{latest(e)?.version || "—"}</strong>
                            </div>
                        ))}
                    </div>
                    <Table
                        data={[...product.releases].sort((a, b) => b.id - a.id)}
                        empty="No releases uploaded yet"
                        columns={[
                            {
                                title: "Version",
                                render: (r) => (
                                    <>
                                        <strong>{r.version}</strong>
                                        {r.changelog && <small className="store-block clamp-2">{r.changelog}</small>}
                                    </>
                                ),
                            },
                            { title: "Edition", render: (r) => <StatusChip status={r.edition} /> },
                            { title: "Uploaded", render: (r) => date(r.created_at) },
                            { title: "Status", render: (r) => <StatusChip status={r.published ? "published" : "draft"} /> },
                            {
                                title: "",
                                render: (r) => (
                                    <div className="store-row-actions">
                                        <Form action={`${admin}/releases/${r.id}`} method="PATCH" submit={r.published ? "Unpublish" : "Publish"} className="inline-form">
                                            <input type="hidden" name="published" value={r.published ? "0" : "1"} />
                                        </Form>
                                        <DeleteAction action={`${admin}/releases/${r.id}`} name={`release ${r.version}`} message="This deletes the uploaded ZIP. Customers can no longer download this version." />
                                    </div>
                                ),
                            },
                        ]}
                    />
                </section>
            )}
            {product && (
                <>
                    <Modal open={!!plan} onClose={() => setPlan(null)} title={plan?.id ? `Edit ${plan.name}` : "Add plan"}>
                        <ModalErrors />
                        {plan && (
                            <Form action={`${admin}/products/${product.id}/plans${plan.id ? "/" + plan.id : ""}`} method={plan.id ? "PUT" : "POST"} submit="Save plan">
                                <div className="store-field-grid">
                                    <Field name="name" title="Plan name" value={plan.name} required maxLength={100} placeholder="Business" />
                                    <Field name="price" title="Price in USD (whole term)" type="number" step="0.01" min="0.01" value={plan.id ? plan.price_minor / 100 : ""} required />
                                </div>
                                <div className="store-field-grid">
                                    <Field
                                        name="term_months"
                                        title="Billing term"
                                        type="select"
                                        value={plan.term_months || 12}
                                        options={[1, 3, 6, 12, 24].map((m) => ({ value: m, label: m === 1 ? "1 month" : m === 12 ? "1 year" : m === 24 ? "2 years" : `${m} months` }))}
                                    />
                                    <Field name="site_limit" title="Websites (blank = unlimited)" type="number" min="1" value={plan.site_limit ?? ""} />
                                </div>
                                <Field name="description" title="Short description (optional)" value={plan.description} maxLength={255} placeholder="For freelancers with a few client sites." />
                                <Field name="features" title="Plan highlights, one per line (optional — a sensible default list is shown otherwise)" type="textarea" rows={4} value={(plan.features || []).join("\n")} />
                                <Field name="is_featured" title="Highlight as “Most popular” for this term" type="checkbox" value={plan.is_featured} />
                                <Field name="published" title="Available for purchase" type="checkbox" value={plan.id ? plan.published : true} />
                            </Form>
                        )}
                    </Modal>
                    <Modal open={upload} onClose={() => setUpload(false)} title="Upload a release">
                        <ModalErrors />
                        <Form action={`${admin}/products/${product.id}/releases`} submit="Upload release">
                            <div className="store-field-grid">
                                <Field name="version" placeholder={latest("pro")?.version ? `Higher than ${latest("pro").version}` : "1.0.0"} required pattern="[0-9]+\.[0-9]+\.[0-9]+(-[a-zA-Z0-9.]+)?" />
                                <Field
                                    name="edition"
                                    type="select"
                                    options={[
                                        { value: "pro", label: "Pro — license required" },
                                        { value: "free", label: "Free — public download" },
                                    ]}
                                />
                            </div>
                            <Field name="package" title="Plugin ZIP (up to 20 MB)" type="file" accept=".zip" required />
                            <Field name="changelog" title="What’s new (shown on the product page)" type="textarea" rows={5} placeholder={"- Added …\n- Fixed …"} />
                            <Field name="published" title="Publish immediately" type="checkbox" value={true} />
                        </Form>
                    </Modal>
                </>
            )}
        </>
    );
}

function AdminProduct({ product, stats, orders }) {
    const pkr = (minor) => price(minor, "PKR");
    const latest = (edition) => product.releases.find((r) => r.edition === edition && r.published);
    return (
        <>
            <header className="license-hero">
                <ProductIcon product={product} size={64} />
                <div>
                    <span className="eyebrow">PRODUCT</span>
                    <h1 className="page-title">{product.name}</h1>
                    <div className="hub-chips">
                        <StatusChip status={product.published ? "published" : "draft"} />
                        <span className="soft-chip">Free {latest("free")?.version || "—"}</span>
                        <span className="soft-chip">Pro {latest("pro")?.version || "—"}</span>
                    </div>
                </div>
                <div className="store-row-actions push-right">
                    <a className="button" href={`${admin}/products/${product.id}/edit`}>
                        Edit product
                    </a>
                    {product.published && (
                        <a className="button secondary" href={`/products/${product.slug}`}>
                            Store page ↗
                        </a>
                    )}
                    <DeleteAction action={`${admin}/products/${product.id}`} name={product.name} label="Delete" disabled={product.orders_count > 0 || product.licenses_count > 0} reason="This product has orders or licenses. Unpublish it instead to hide it from the store." message="This permanently deletes the product with its plans and uploaded releases." />
                </div>
            </header>
            <div className="stats">
                {[
                    ["Revenue", pkr(stats.revenuePkr)],
                    ["Paid orders", stats.paidOrders],
                    ["Awaiting payment", stats.pendingOrders],
                    ["Active licenses", stats.activeLicenses],
                ].map(([t, v]) => (
                    <div className="stat" key={t}>
                        <span>{t}</span>
                        <strong>{v}</strong>
                    </div>
                ))}
            </div>
            <section className="store-panel">
                <div className="theme-section-heading">
                    <h2>Plans</h2>
                    <a className="text-link" href={`${admin}/products/${product.id}/edit?tab=plans`}>
                        Manage plans →
                    </a>
                </div>
                <Table
                    data={product.plans}
                    empty="No plans yet"
                    columns={[
                        { title: "Plan", render: (p) => <strong>{p.name}{p.is_featured ? " ★" : ""}</strong> },
                        { title: "Price", render: (p) => `${price(p.price_minor)} / ${term(p.term_months)}` },
                        { title: "Websites", render: (p) => seats(p.site_limit) },
                        { title: "Status", render: (p) => <StatusChip status={p.published ? "published" : "draft"} /> },
                    ]}
                />
            </section>
            <section className="store-panel">
                <div className="theme-section-heading">
                    <h2>Recent orders</h2>
                    <a className="text-link" href={`${admin}/orders`}>
                        All orders →
                    </a>
                </div>
                <OrdersTable orders={orders} adminMode />
            </section>
        </>
    );
}

function FilterTabs({ base, current, items }) {
    return (
        <nav className="hub-tabs" aria-label="Filter">
            {items.map(([key, title, count, alert]) => (
                <a key={key} href={key ? `${base}?status=${key}` : base} aria-current={(current || "") === key ? "page" : undefined}>
                    {title}
                    {count !== undefined && <small className={alert && count ? "alert" : ""}>{count}</small>}
                </a>
            ))}
        </nav>
    );
}

function AdminOrders({ orders, counts = {}, filters = {}, revenue = {} }) {
    return (
        <>
            <Heading eyebrow="PRODUCT SALES" title="Orders & payments" description="Verify payments, follow up on unpaid orders, and issue licenses.">
                {Object.entries(revenue).map(([currency, total]) => (
                    <span className="soft-chip" key={currency}>
                        Revenue {price(total, currency)}
                    </span>
                ))}
            </Heading>
            <FilterTabs
                base={admin + "/orders"}
                current={filters.status}
                items={[
                    ["", "All", counts.all],
                    ["review", "Needs review", counts.review || 0, true],
                    ["pending", "Unpaid", counts.pending || 0],
                    ["paid", "Paid", counts.paid || 0],
                    ["cancelled", "Cancelled", counts.cancelled || 0],
                ]}
            />
            <section className="store-panel">
                <Form action={admin + "/orders"} method="GET" className="search-form store-filter" submit="Search">
                    {filters.status && <input type="hidden" name="status" value={filters.status} />}
                    <Field name="search" title="Customer name, email or order reference" value={filters.search} />
                </Form>
                <OrdersTable orders={orders} adminMode />
            </section>
        </>
    );
}

function AdminLicenses({ licenses, counts = {}, filters = {} }) {
    const [editing, setEditing] = useState(null);
    return (
        <>
            <Heading eyebrow="PRODUCT ACCESS" title="Licenses" description="Watch activations and expiry, and suspend access if a payment is refunded or disputed." />
            <FilterTabs
                base={admin + "/licenses"}
                current={filters.status}
                items={[
                    ["", "All", counts.all],
                    ["active", "Active", counts.active],
                    ["expiring", "Expiring in 14 days", counts.expiring, true],
                    ["expired", "Expired", counts.expired],
                    ["revoked", "Revoked", counts.revoked],
                ]}
            />
            <section className="store-panel">
                <Form action={admin + "/licenses"} method="GET" className="search-form store-filter" submit="Search">
                    {filters.status && <input type="hidden" name="status" value={filters.status} />}
                    <Field name="search" title="Customer name, email or key ending" value={filters.search} />
                </Form>
                <Table
                    data={licenses}
                    empty="No licenses match"
                    columns={[
                        {
                            title: "Customer",
                            render: (l) => (
                                <>
                                    <strong>{l.user?.name}</strong>
                                    <small className="store-block">{l.user?.email}</small>
                                </>
                            ),
                        },
                        {
                            title: "Product",
                            render: (l) => (
                                <>
                                    {l.product?.name}
                                    <small className="store-block">
                                        <code>{l.key_hint}</code>
                                    </small>
                                </>
                            ),
                        },
                        { title: "Websites", render: (l) => <Meter value={l.activations_count} max={l.site_limit} label="" /> },
                        {
                            title: "Expiry",
                            render: (l) => (
                                <>
                                    {date(l.expires_at)}
                                    {live(l) && <small className="store-block">{daysLeft(l.expires_at)} days left</small>}
                                </>
                            ),
                        },
                        { title: "Status", render: (l) => <StatusChip status={licenseState(l)} /> },
                        {
                            title: "",
                            render: (l) => (
                                <button className="button small secondary" type="button" onClick={() => setEditing(l)}>
                                    Manage
                                </button>
                            ),
                        },
                    ]}
                />
                <Pagination data={licenses} />
            </section>
            <Modal open={!!editing} onClose={() => setEditing(null)} title="Manage license access">
                <ModalErrors />
                {editing && (
                    <Form action={`${admin}/licenses/${editing.id}`} method="PATCH" submit="Save">
                        <p>
                            {editing.user?.name} · {editing.product?.name} · <code>{editing.key_hint}</code>
                        </p>
                        <p className="muted">Revoking stops activations and Pro downloads immediately. It doesn’t change the expiry date or refund anything.</p>
                        <Field
                            name="status"
                            type="select"
                            value={editing.status}
                            options={[
                                { value: "active", label: "Active" },
                                { value: "revoked", label: "Revoked" },
                            ]}
                        />
                        <Field name="admin_note" title="Reason (kept in the record)" type="textarea" rows={3} required minLength={8} value={editing.admin_note} />
                    </Form>
                )}
            </Modal>
        </>
    );
}

export default function StorePage(props) {
    const pages = {
        "store.catalog": Catalog,
        "store.product": Product,
        "store.checkout": CheckoutReview,
        "store.library": Library,
        "store.order": Order,
        "store.license": License,
        "admin.store.products": AdminProducts,
        "admin.store.editor": Editor,
        "admin.store.product": AdminProduct,
        "admin.store.orders": AdminOrders,
        "admin.store.order": (p) => <Order {...p} adminMode />,
        "admin.store.licenses": AdminLicenses,
    };
    const Component = pages[context.page];
    return (
        <section className={"store " + (context.page.startsWith("admin.") ? "" : "section")}>
            <Nav />
            {Component ? <Component {...props} /> : <Empty />}
        </section>
    );
}
