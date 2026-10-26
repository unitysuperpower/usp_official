import React, { useEffect, useState } from "react";
import { Heading, Form, Field } from "./ui";

// Admin → Settings → Appearance. Changes preview live on this page; saving applies them site-wide.
const colorLabels = {
    bg: ["Page background", "Behind everything"],
    surface: ["Cards & panels", "Forms, tables, cards"],
    ink: ["Main text", "Headings, body text, dark areas"],
    muted: ["Secondary text", "Descriptions and hints"],
    line: ["Borders", "Dividers and outlines"],
    soft: ["Soft fill", "Hover states, subtle backgrounds"],
    accent: ["Accent", "Links, active items, hover buttons"],
    lime: ["Highlight", "Badges, light buttons, logo dot"],
    "button-bg": ["Button", "Main button background"],
    "button-ink": ["Button text", "Text on main buttons"],
};
const slug = (font) => font.toLowerCase().replaceAll(" ", "-");
const systemFonts = ["Georgia", "System UI"];

function useFontPreview(fonts) {
    useEffect(() => {
        const families = [...new Set(Object.values(fonts))].filter((f) => !systemFonts.includes(f));
        if (!families.length) return;
        const link = document.createElement("link");
        link.rel = "stylesheet";
        link.href = "https://fonts.bunny.net/css?family=" + families.map((f) => slug(f) + ":400,500,600,700,800").join("|") + "&display=swap";
        document.head.appendChild(link);
        return () => link.remove();
    }, [fonts.body, fonts.heading, fonts.accent]);
}

function ColorField({ name, value, onChange }) {
    const [title, hint] = colorLabels[name] || [name, ""];
    return (
        <label className="color-field">
            <input type="color" value={value} onChange={(e) => onChange(e.target.value)} aria-label={title} />
            <span>
                <strong>{title}</strong>
                <small>{hint}</small>
            </span>
            <input
                className="color-hex"
                value={value}
                maxLength={7}
                spellCheck={false}
                aria-label={`${title} hex value`}
                onChange={(e) => {
                    const v = e.target.value.trim();
                    if (/^#[0-9a-fA-F]{6}$/.test(v)) onChange(v.toLowerCase());
                }}
            />
        </label>
    );
}

function ImageField({ name, title, hint, current }) {
    return (
        <div className="theme-image-field">
            <div className="theme-image-preview">{current ? <img src={current} alt="" /> : <span>None</span>}</div>
            <div>
                <Field name={name} title={title} type="file" accept={name === "favicon" ? ".png,.ico,.webp" : ".png,.jpg,.jpeg,.webp"} />
                <small>{hint}</small>
                {current && <Field name={`remove_${name}`} title="Remove current image" type="checkbox" />}
            </div>
        </div>
    );
}

export default function ThemeSettings({ theme, defaults, images, fonts: fontChoices, presets }) {
    const [colors, setColors] = useState(theme.colors);
    const [fonts, setFonts] = useState(theme.fonts);
    const [radius, setRadius] = useState(Number(theme.radius));
    const [editing, setEditing] = useState("light");
    useFontPreview(fonts);

    useEffect(() => {
        const root = document.documentElement;
        root.dataset.theme = editing;
        Object.entries(colors[editing]).forEach(([key, value]) => root.style.setProperty(`--${key}`, value));
        const stack = (font) => (font === "System UI" ? "system-ui, sans-serif" : `"${font}", ${font === "Georgia" ? "serif" : "sans-serif"}`);
        Object.entries(fonts).forEach(([role, font]) => root.style.setProperty(`--font-${role}`, stack(font)));
        root.style.setProperty("--radius-scale", radius);
    }, [colors, fonts, radius, editing]);

    const setColor = (key, value) => setColors((c) => ({ ...c, [editing]: { ...c[editing], [key]: value } }));
    const applyPreset = (preset) => {
        setEditing("light");
        setColors((c) => ({ ...c, light: { ...c.light, ...preset } }));
    };

    return (
        <>
            <Heading eyebrow="SETTINGS" title="Appearance" description="Brand colors, fonts, logo and footer text for the whole site. Changes preview on this page right away; save to publish them." />
            <Form action="/admin/settings/theme" method="PUT" submit="Save appearance">
                {["light", "dark"].map((mode) => Object.entries(colors[mode]).map(([key, value]) => <input key={mode + key} type="hidden" name={`colors[${mode}][${key}]`} value={value} />))}

                <section className="store-panel">
                    <h2>Color presets</h2>
                    <p>Start from a preset, then fine-tune any color below. Presets change light mode only.</p>
                    <div className="theme-presets">
                        {Object.entries(presets).map(([name, preset]) => (
                            <button type="button" className="theme-preset" key={name} onClick={() => applyPreset(preset)}>
                                <span className="theme-swatches" aria-hidden="true">
                                    {["ink", "accent", "lime", "bg"].map((k) => (
                                        <i key={k} style={{ background: preset[k] }} />
                                    ))}
                                </span>
                                {name}
                            </button>
                        ))}
                    </div>
                </section>

                <section className="store-panel">
                    <div className="theme-section-heading">
                        <h2>Colors</h2>
                        <div className="trend-range" role="group" aria-label="Color mode to edit">
                            {["light", "dark"].map((mode) => (
                                <button type="button" key={mode} aria-pressed={editing === mode} onClick={() => setEditing(mode)}>
                                    {mode === "light" ? "Light mode" : "Dark mode"}
                                </button>
                            ))}
                        </div>
                    </div>
                    <div className="color-grid">
                        {Object.keys(colorLabels).map((key) => (
                            <ColorField key={key} name={key} value={colors[editing][key]} onChange={(v) => setColor(key, v)} />
                        ))}
                    </div>
                    <div className="theme-sample" aria-hidden="true">
                        <span className="eyebrow">PREVIEW</span>
                        <h3>Thoughtfully designed.</h3>
                        <p>
                            Body text in your chosen font, with a <a href="#">link in the accent color</a>.
                        </p>
                        <div className="row-actions">
                            <span className="button small">Primary button</span>
                            <span className="button small secondary">Secondary</span>
                            <span className="button small light">Highlight</span>
                            <span className="badge active">badge</span>
                        </div>
                    </div>
                    <button type="button" className="text-link" onClick={() => setColors((c) => ({ ...c, [editing]: defaults.colors[editing] }))}>
                        Restore default {editing} colors
                    </button>
                </section>

                <section className="store-panel">
                    <h2>Typography & shape</h2>
                    <div className="store-field-grid">
                        {[
                            ["body", "Body text"],
                            ["heading", "Headings"],
                            ["accent", "Accent (quotes, italics)"],
                        ].map(([role, title]) => (
                            <label className="field" key={role}>
                                <span>{title}</span>
                                <select name={`fonts[${role}]`} value={fonts[role]} onChange={(e) => setFonts((f) => ({ ...f, [role]: e.target.value }))}>
                                    {fontChoices.map((font) => (
                                        <option key={font}>{font}</option>
                                    ))}
                                </select>
                            </label>
                        ))}
                    </div>
                    <label className="field">
                        <span>Corner rounding: {radius === 0 ? "square" : radius < 0.8 ? "subtle" : radius <= 1.2 ? "default" : "round"}</span>
                        <input type="range" name="radius" min="0" max="2" step="0.1" value={radius} onChange={(e) => setRadius(Number(e.target.value))} />
                    </label>
                </section>

                <section className="store-panel">
                    <h2>Light & dark mode</h2>
                    <div className="store-field-grid">
                        <Field
                            name="mode"
                            title="Default for visitors"
                            type="select"
                            value={theme.mode}
                            options={[
                                { value: "light", label: "Light" },
                                { value: "dark", label: "Dark" },
                                { value: "system", label: "Match each visitor's device" },
                            ]}
                        />
                        <Field name="allow_toggle" title="Let signed-in users choose light or dark in their settings" type="checkbox" value={theme.allow_toggle} />
                    </div>
                </section>

                <section className="store-panel">
                    <h2>Brand</h2>
                    <p>Without a logo image, the site shows the letter mark and name as text.</p>
                    <div className="store-field-grid">
                        <Field name="brand[name]" title="Site name" value={theme.brand.name} required maxLength={40} />
                        <Field name="brand[subtitle]" title="Subtitle under the name" value={theme.brand.subtitle} maxLength={40} />
                    </div>
                    <Field name="brand[mark]" title="Letter mark in the logo square (1–3 characters, blank to hide)" value={theme.brand.mark} maxLength={3} />
                    <ImageField name="logo" title="Logo image" hint="PNG, JPG or WebP, up to 2 MB. Shown 45 px tall; a wide transparent PNG works best." current={images.logo} />
                    <ImageField name="logo_dark" title="Logo for dark mode (optional)" hint="Used in dark mode instead of the main logo." current={images.logo_dark} />
                    <ImageField name="favicon" title="Browser tab icon (favicon)" hint="Square PNG, ICO or WebP, up to 512 KB. 512×512 px is ideal." current={images.favicon} />
                </section>

                <section className="store-panel">
                    <h2>Footer text</h2>
                    <Field name="footer[tagline]" title="Tagline under the logo" type="textarea" rows={2} value={theme.footer.tagline} maxLength={200} />
                    <div className="store-field-grid">
                        <Field name="footer[statement]" title="Closing statement" value={theme.footer.statement} maxLength={120} />
                        <Field name="footer[statement_emphasis]" title="Statement, emphasised line" value={theme.footer.statement_emphasis} maxLength={120} />
                    </div>
                    <div className="store-field-grid">
                        <Field name="footer[copyright]" title="Copyright name" value={theme.footer.copyright} maxLength={120} />
                        <Field name="footer[motto]" title="Motto (bottom right)" value={theme.footer.motto} maxLength={120} />
                    </div>
                </section>
            </Form>
            <section className="store-panel">
                <h2>Start over</h2>
                <p>Restore the original colors, fonts, rounding and text. Uploaded logo images are kept.</p>
                <Form action="/admin/settings/theme/reset" submit="Reset to defaults" className="danger" confirm="Reset the theme to its defaults?" />
            </section>
        </>
    );
}
