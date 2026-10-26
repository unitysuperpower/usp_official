import React, { useEffect, useRef, useState } from "react";
import { useEditor, useEditorState, EditorContent } from "@tiptap/react";
import { Node, mergeAttributes } from "@tiptap/core";
import StarterKit from "@tiptap/starter-kit";
import Image from "@tiptap/extension-image";
import TextAlign from "@tiptap/extension-text-align";
import Highlight from "@tiptap/extension-highlight";
import { TextStyle, Color } from "@tiptap/extension-text-style";
import { Table, TableRow, TableCell, TableHeader } from "@tiptap/extension-table";
import { Placeholder, CharacterCount } from "@tiptap/extensions";
import Youtube from "@tiptap/extension-youtube";
import { TaskList, TaskItem } from "@tiptap/extension-list";
import Subscript from "@tiptap/extension-subscript";
import Superscript from "@tiptap/extension-superscript";
import Typography from "@tiptap/extension-typography";
import { context } from "./ui";

// Rich text editor for articles, services, lessons and product pages. It writes clean HTML into a
// hidden input named `name`, so it drops into any existing <Form>. Rendering stays sanitised by the
// Html/LessonBody components; the editor itself never trusts pasted markup beyond its schema.

const UPLOAD_URL = "/admin/editor/images";

// Images carry an optional width (25–100%) and alignment.
const SizedImage = Image.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            width: {
                default: null,
                parseHTML: (el) => el.style.width || el.getAttribute("width") || null,
                renderHTML: (attrs) => (attrs.width ? { style: `width:${attrs.width}` } : {}),
            },
            align: {
                default: null,
                parseHTML: (el) => el.getAttribute("data-align"),
                renderHTML: (attrs) => (attrs.align ? { "data-align": attrs.align } : {}),
            },
        };
    },
});

// Highlighted box for tips, notes and warnings: <div class="callout" data-callout="tip">…</div>
const Callout = Node.create({
    name: "callout",
    group: "block",
    content: "block+",
    defining: true,
    addAttributes() {
        return { type: { default: "info", parseHTML: (el) => el.getAttribute("data-callout") || "info", renderHTML: (attrs) => ({ "data-callout": attrs.type }) } };
    },
    parseHTML() {
        return [{ tag: "div[data-callout]" }];
    },
    renderHTML({ HTMLAttributes }) {
        return ["div", mergeAttributes(HTMLAttributes, { class: "callout" }), 0];
    },
    addCommands() {
        return {
            toggleCallout:
                (type) =>
                ({ editor, commands }) =>
                    editor.isActive(this.name, { type }) ? commands.lift(this.name) : editor.isActive(this.name) ? commands.updateAttributes(this.name, { type }) : commands.wrapIn(this.name, { type }),
        };
    },
});

const COLORS = ["#111827", "#6b7280", "#dc2626", "#ea580c", "#ca8a04", "#16a34a", "#0d9488", "#2563eb", "#7c3aed", "#db2777"];
const HIGHLIGHTS = ["#fef08a", "#bbf7d0", "#bfdbfe", "#fbcfe8", "#fed7aa", "#e9d5ff"];

async function upload(file) {
    const data = new FormData();
    data.append("image", file);
    const response = await fetch(UPLOAD_URL, { method: "POST", body: data, credentials: "same-origin", headers: { Accept: "application/json", "X-CSRF-TOKEN": context.csrf } });
    const json = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(Object.values(json.errors || {}).flat()[0] || json.message || "Upload failed");
    return json.url;
}

function Btn({ on, active, disabled, title, children }) {
    return (
        <button
            type="button"
            className={`rte-btn ${active ? "is-active" : ""}`}
            onMouseDown={(e) => e.preventDefault()}
            onClick={on}
            disabled={disabled}
            title={title}
            aria-label={title}
            aria-pressed={active ?? undefined}
        >
            {children}
        </button>
    );
}

function Swatches({ colors, onPick, onClear, clearLabel }) {
    return (
        <div className="rte-swatches">
            {colors.map((c) => (
                <button type="button" key={c} style={{ background: c }} onMouseDown={(e) => e.preventDefault()} onClick={() => onPick(c)} aria-label={c} title={c} />
            ))}
            <button type="button" className="rte-swatch-clear" onMouseDown={(e) => e.preventDefault()} onClick={onClear}>
                {clearLabel}
            </button>
        </div>
    );
}

export default function RichEditor({ name, value = "", placeholder = "Start writing… Type ## for a heading, - for a list.", minHeight = 360, uploads = true }) {
    const initial = context.old && Object.prototype.hasOwnProperty.call(context.old, name) ? context.old[name] : value;
    const [html, setHtml] = useState(initial || "");
    const [panel, setPanel] = useState(null); // link | image | video | color | highlight
    const [source, setSource] = useState(false);
    const [full, setFull] = useState(false);
    const [busy, setBusy] = useState("");
    const [error, setError] = useState("");
    const [draft, setDraft] = useState(null);
    const fileRef = useRef(null);
    const wrapRef = useRef(null);
    const draftKey = `rte-draft:${location.pathname}:${name}`;
    const timer = useRef(null);

    const insertFiles = async (files, editor, pos) => {
        const images = [...files].filter((f) => /^image\/(png|jpe?g|webp|gif)$/.test(f.type));
        if (!images.length || !uploads) return false;
        setError("");
        for (const file of images) {
            setBusy(`Uploading ${file.name}…`);
            try {
                const url = await upload(file);
                const chain = editor.chain().focus();
                (pos !== undefined ? chain.insertContentAt(pos, { type: "image", attrs: { src: url, alt: "" } }) : chain.setImage({ src: url, alt: "" })).run();
            } catch (e) {
                setError(e.message);
            }
        }
        setBusy("");
        return true;
    };

    const editor = useEditor({
        immediatelyRender: true,
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3, 4] },
                link: { openOnClick: false, autolink: true, defaultProtocol: "https", HTMLAttributes: { rel: "noopener noreferrer", target: null } },
            }),
            SizedImage.configure({ allowBase64: false }),
            TextAlign.configure({ types: ["heading", "paragraph"] }),
            Highlight.configure({ multicolor: true }),
            TextStyle,
            Color,
            Table.configure({ resizable: false }),
            TableRow,
            TableHeader,
            TableCell,
            Placeholder.configure({ placeholder }),
            CharacterCount,
            Youtube.configure({ nocookie: true, modestBranding: true, HTMLAttributes: { class: "embed-video" } }),
            TaskList,
            TaskItem.configure({ nested: true }),
            Subscript,
            Superscript,
            Typography,
            Callout,
        ],
        content: initial || "",
        editorProps: {
            attributes: { class: "prose rte-content", spellcheck: "true" },
            handleDrop: (view, event, slice, moved) => {
                if (moved || !event.dataTransfer?.files?.length) return false;
                const pos = view.posAtCoords({ left: event.clientX, top: event.clientY })?.pos;
                event.preventDefault();
                insertFiles(event.dataTransfer.files, editor, pos);
                return true;
            },
            handlePaste: (view, event) => {
                if (!event.clipboardData?.files?.length) return false;
                event.preventDefault();
                insertFiles(event.clipboardData.files, editor);
                return true;
            },
        },
        onUpdate: ({ editor }) => {
            const next = editor.isEmpty ? "" : editor.getHTML();
            setHtml(next);
            clearTimeout(timer.current);
            timer.current = setTimeout(() => {
                try {
                    localStorage.setItem(draftKey, JSON.stringify({ html: next, at: Date.now() }));
                } catch {}
            }, 800);
        },
    });

    const state = useEditorState({
        editor,
        // The selector can run before the editor has built its schema; report "not ready" until then.
        selector: ({ editor: e }) => !e?.schema || e.isDestroyed ? null : ({
            block: e.isActive("heading", { level: 2 }) ? "h2" : e.isActive("heading", { level: 3 }) ? "h3" : e.isActive("heading", { level: 4 }) ? "h4" : e.isActive("codeBlock") ? "code" : "p",
            bold: e.isActive("bold"),
            italic: e.isActive("italic"),
            underline: e.isActive("underline"),
            strike: e.isActive("strike"),
            code: e.isActive("code"),
            sub: e.isActive("subscript"),
            sup: e.isActive("superscript"),
            link: e.isActive("link"),
            href: e.getAttributes("link").href || "",
            bullet: e.isActive("bulletList"),
            ordered: e.isActive("orderedList"),
            task: e.isActive("taskList"),
            quote: e.isActive("blockquote"),
            callout: e.isActive("callout") ? e.getAttributes("callout").type : null,
            align: ["center", "right", "justify"].find((a) => e.isActive({ textAlign: a })) || "left",
            table: e.isActive("table"),
            image: e.isActive("image"),
            imageAttrs: e.isActive("image") ? e.getAttributes("image") : {},
            canUndo: e.can().undo(),
            canRedo: e.can().redo(),
            words: e.storage.characterCount?.words?.() ?? 0,
            chars: e.storage.characterCount?.characters?.() ?? 0,
        }),
    });

    // Offer to restore an unsaved draft that differs from what the server sent.
    useEffect(() => {
        try {
            const saved = JSON.parse(localStorage.getItem(draftKey) || "null");
            if (saved?.html && saved.html !== (initial || "") && Date.now() - saved.at < 14 * 86400000) setDraft(saved);
        } catch {}
        const form = wrapRef.current?.closest("form");
        const clear = () => {
            try {
                localStorage.removeItem(draftKey);
            } catch {}
        };
        form?.addEventListener("submit", clear);
        return () => form?.removeEventListener("submit", clear);
    }, []);

    useEffect(() => {
        document.body.style.overflow = full ? "hidden" : "";
        const onKey = (e) => e.key === "Escape" && setFull(false);
        if (full) document.addEventListener("keydown", onKey);
        return () => {
            document.body.style.overflow = "";
            document.removeEventListener("keydown", onKey);
        };
    }, [full]);

    if (!editor) return null;
    const s = state || { block: "p", align: "left", imageAttrs: {}, href: "", words: 0, chars: 0 };
    const c = () => editor.chain().focus();
    const toggleSource = () => {
        if (source) editor.commands.setContent(html, { emitUpdate: true });
        setSource(!source);
        setPanel(null);
    };

    return (
        <div
            ref={wrapRef}
            className={`rte ${full ? "is-fullscreen" : ""}`}
            onKeyDown={(e) => {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "k" && !source) {
                    e.preventDefault();
                    setPanel("link");
                }
            }}
        >
            <input type="hidden" name={name} value={html} />
            {draft && (
                <div className="rte-draft" role="status">
                    You have unsaved changes from {new Date(draft.at).toLocaleString()}.
                    <button
                        type="button"
                        className="text-link"
                        onClick={() => {
                            editor.commands.setContent(draft.html, { emitUpdate: true });
                            setDraft(null);
                        }}
                    >
                        Restore
                    </button>
                    <button
                        type="button"
                        className="text-link"
                        onClick={() => {
                            try {
                                localStorage.removeItem(draftKey);
                            } catch {}
                            setDraft(null);
                        }}
                    >
                        Discard
                    </button>
                </div>
            )}
            <div className="rte-toolbar" role="toolbar" aria-label="Formatting">
                <div className="rte-group">
                    <Btn on={() => c().undo().run()} disabled={source || !s.canUndo} title="Undo (Ctrl+Z)">
                        ↶
                    </Btn>
                    <Btn on={() => c().redo().run()} disabled={source || !s.canRedo} title="Redo (Ctrl+Shift+Z)">
                        ↷
                    </Btn>
                </div>
                <div className="rte-group">
                    <select
                        className="rte-select"
                        aria-label="Text style"
                        disabled={source}
                        value={s.block}
                        onChange={(e) => {
                            const v = e.target.value;
                            v === "p" ? c().setParagraph().run() : v === "code" ? c().toggleCodeBlock().run() : c().toggleHeading({ level: Number(v[1]) }).run();
                        }}
                    >
                        <option value="p">Paragraph</option>
                        <option value="h2">Heading 2</option>
                        <option value="h3">Heading 3</option>
                        <option value="h4">Heading 4</option>
                        <option value="code">Code block</option>
                    </select>
                </div>
                <div className="rte-group">
                    <Btn on={() => c().toggleBold().run()} active={s.bold} disabled={source} title="Bold (Ctrl+B)">
                        <b>B</b>
                    </Btn>
                    <Btn on={() => c().toggleItalic().run()} active={s.italic} disabled={source} title="Italic (Ctrl+I)">
                        <i>I</i>
                    </Btn>
                    <Btn on={() => c().toggleUnderline().run()} active={s.underline} disabled={source} title="Underline (Ctrl+U)">
                        <u>U</u>
                    </Btn>
                    <Btn on={() => c().toggleStrike().run()} active={s.strike} disabled={source} title="Strikethrough">
                        <s>S</s>
                    </Btn>
                    <Btn on={() => c().toggleCode().run()} active={s.code} disabled={source} title="Inline code">
                        {"</>"}
                    </Btn>
                    <Btn on={() => c().toggleSuperscript().run()} active={s.sup} disabled={source} title="Superscript">
                        x²
                    </Btn>
                    <Btn on={() => c().toggleSubscript().run()} active={s.sub} disabled={source} title="Subscript">
                        x₂
                    </Btn>
                    <Btn on={() => setPanel(panel === "color" ? null : "color")} active={panel === "color"} disabled={source} title="Text color">
                        <span className="rte-a">A</span>
                    </Btn>
                    <Btn on={() => setPanel(panel === "highlight" ? null : "highlight")} active={panel === "highlight"} disabled={source} title="Highlight">
                        <span className="rte-mark">H</span>
                    </Btn>
                    <Btn on={() => c().unsetAllMarks().clearNodes().run()} disabled={source} title="Clear formatting">
                        ⌫
                    </Btn>
                </div>
                <div className="rte-group">
                    {[
                        ["left", "⯇", "Align left"],
                        ["center", "≡", "Align center"],
                        ["right", "⯈", "Align right"],
                        ["justify", "☰", "Justify"],
                    ].map(([a, icon, title]) => (
                        <Btn key={a} on={() => c().setTextAlign(a).run()} active={s.align === a} disabled={source} title={title}>
                            {icon}
                        </Btn>
                    ))}
                </div>
                <div className="rte-group">
                    <Btn on={() => c().toggleBulletList().run()} active={s.bullet} disabled={source} title="Bulleted list">
                        •≡
                    </Btn>
                    <Btn on={() => c().toggleOrderedList().run()} active={s.ordered} disabled={source} title="Numbered list">
                        1≡
                    </Btn>
                    <Btn on={() => c().toggleTaskList().run()} active={s.task} disabled={source} title="Checklist">
                        ☑
                    </Btn>
                    <Btn on={() => c().toggleBlockquote().run()} active={s.quote} disabled={source} title="Quote">
                        ❝
                    </Btn>
                    <Btn on={() => c().setHorizontalRule().run()} disabled={source} title="Divider">
                        ―
                    </Btn>
                </div>
                <div className="rte-group">
                    <Btn on={() => setPanel(panel === "link" ? null : "link")} active={s.link || panel === "link"} disabled={source} title="Link (Ctrl+K)">
                        🔗
                    </Btn>
                    {uploads && (
                        <Btn on={() => setPanel(panel === "image" ? null : "image")} active={panel === "image"} disabled={source} title="Image">
                            🖼
                        </Btn>
                    )}
                    <Btn on={() => setPanel(panel === "video" ? null : "video")} active={panel === "video"} disabled={source} title="YouTube video">
                        ▶
                    </Btn>
                    <Btn on={() => c().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()} disabled={source || s.table} title="Table">
                        ⊞
                    </Btn>
                    <select
                        className="rte-select"
                        aria-label="Callout box"
                        disabled={source}
                        value={s.callout || ""}
                        onChange={(e) => {
                            e.target.value ? c().toggleCallout(e.target.value).run() : s.callout && c().toggleCallout(s.callout).run();
                        }}
                    >
                        <option value="">Callout…</option>
                        <option value="info">ℹ Info</option>
                        <option value="tip">✓ Tip</option>
                        <option value="warning">⚠ Warning</option>
                    </select>
                </div>
                <div className="rte-group rte-end">
                    <Btn on={toggleSource} active={source} title="Edit HTML source">
                        {"{ }"}
                    </Btn>
                    <Btn on={() => setFull(!full)} active={full} title={full ? "Exit full screen (Esc)" : "Full screen"}>
                        {full ? "⤡" : "⤢"}
                    </Btn>
                </div>
            </div>

            {panel === "color" && <Swatches colors={COLORS} onPick={(col) => c().setColor(col).run()} onClear={() => c().unsetColor().run()} clearLabel="Default color" />}
            {panel === "highlight" && <Swatches colors={HIGHLIGHTS} onPick={(col) => c().toggleHighlight({ color: col }).run()} onClear={() => c().unsetHighlight().run()} clearLabel="No highlight" />}
            {panel === "link" && <LinkPanel href={s.href} onApply={(url, blank) => {
                if (!url) c().extendMarkRange("link").unsetLink().run();
                else c().extendMarkRange("link").setLink({ href: url, target: blank ? "_blank" : null }).run();
                setPanel(null);
            }} onClose={() => setPanel(null)} />}
            {panel === "image" && (
                <div className="rte-panel">
                    <button type="button" className="button small" onClick={() => fileRef.current?.click()}>
                        Upload image
                    </button>
                    <span className="muted">or drag & drop / paste an image into the text</span>
                    <input
                        ref={fileRef}
                        type="file"
                        accept="image/png,image/jpeg,image/webp,image/gif"
                        hidden
                        multiple
                        onChange={(e) => {
                            insertFiles(e.target.files, editor);
                            e.target.value = "";
                            setPanel(null);
                        }}
                    />
                    <UrlInput placeholder="…or paste an image URL (https://…)" action="Insert" onSubmit={(url) => (c().setImage({ src: url, alt: "" }).run(), setPanel(null))} />
                </div>
            )}
            {panel === "video" && (
                <div className="rte-panel">
                    <UrlInput placeholder="YouTube link, e.g. https://www.youtube.com/watch?v=…" action="Embed" onSubmit={(url) => {
                        const ok = c().setYoutubeVideo({ src: url }).run();
                        if (!ok) setError("That doesn’t look like a YouTube link.");
                        setPanel(null);
                    }} />
                </div>
            )}

            {s.image && !source && (
                <div className="rte-context" role="toolbar" aria-label="Image">
                    <span>Image</span>
                    {["25%", "50%", "75%", "100%"].map((w) => (
                        <Btn key={w} on={() => c().updateAttributes("image", { width: w === "100%" ? null : w }).run()} active={(s.imageAttrs.width || "100%") === w} title={`Width ${w}`}>
                            {w}
                        </Btn>
                    ))}
                    {[
                        [null, "Inline"],
                        ["left", "Float left"],
                        ["center", "Center"],
                        ["right", "Float right"],
                    ].map(([a, t]) => (
                        <Btn key={t} on={() => c().updateAttributes("image", { align: a }).run()} active={(s.imageAttrs.align || null) === a} title={t}>
                            {t}
                        </Btn>
                    ))}
                    <input className="rte-alt" placeholder="Alt text (describe the image)" defaultValue={s.imageAttrs.alt || ""} key={s.imageAttrs.src} onBlur={(e) => c().updateAttributes("image", { alt: e.target.value }).run()} />
                    <Btn on={() => c().deleteSelection().run()} title="Remove image">
                        ✕
                    </Btn>
                </div>
            )}
            {s.table && !source && (
                <div className="rte-context" role="toolbar" aria-label="Table">
                    <span>Table</span>
                    <Btn on={() => c().addRowAfter().run()} title="Add row below">+ Row</Btn>
                    <Btn on={() => c().addColumnAfter().run()} title="Add column right">+ Column</Btn>
                    <Btn on={() => c().deleteRow().run()} title="Delete row">− Row</Btn>
                    <Btn on={() => c().deleteColumn().run()} title="Delete column">− Column</Btn>
                    <Btn on={() => c().toggleHeaderRow().run()} title="Toggle header row">Header</Btn>
                    <Btn on={() => c().mergeOrSplit().run()} title="Merge or split cells">Merge</Btn>
                    <Btn on={() => c().deleteTable().run()} title="Delete table">✕ Table</Btn>
                </div>
            )}

            {source ? (
                <textarea className="rte-source" value={html} onChange={(e) => setHtml(e.target.value)} spellCheck={false} aria-label="HTML source" style={{ minHeight }} />
            ) : (
                <EditorContent editor={editor} style={{ "--rte-min": `${minHeight}px` }} />
            )}
            <div className="rte-footer">
                <span>
                    {s.words.toLocaleString()} words · {s.chars.toLocaleString()} characters · {Math.max(1, Math.round(s.words / 200))} min read
                </span>
                {busy && <span className="rte-busy">{busy}</span>}
                {error && (
                    <span className="rte-error" role="alert">
                        {error}
                    </span>
                )}
                <span className="muted">Markdown shortcuts work: ## heading, - list, 1. list, &gt; quote, **bold**</span>
            </div>
        </div>
    );
}

function UrlInput({ placeholder, action, onSubmit, initial = "" }) {
    const [url, setUrl] = useState(initial);
    const submit = () => {
        const v = url.trim();
        if (/^https?:\/\//i.test(v)) onSubmit(v);
    };
    return (
        <span className="rte-url">
            <input
                type="url"
                value={url}
                placeholder={placeholder}
                onChange={(e) => setUrl(e.target.value)}
                onKeyDown={(e) => {
                    if (e.key === "Enter") {
                        e.preventDefault();
                        submit();
                    }
                }}
                autoFocus
            />
            <button type="button" className="button small secondary" onClick={submit}>
                {action}
            </button>
        </span>
    );
}

function LinkPanel({ href, onApply, onClose }) {
    const [url, setUrl] = useState(href);
    const [blank, setBlank] = useState(false);
    const normal = (v) => (!v ? "" : /^(https?:|mailto:|tel:|\/|#)/i.test(v) ? v : `https://${v}`);
    return (
        <div className="rte-panel">
            <span className="rte-url">
                <input
                    type="text"
                    value={url}
                    placeholder="Paste a link, page path (/contact) or email (mailto:…)"
                    onChange={(e) => setUrl(e.target.value)}
                    onKeyDown={(e) => {
                        if (e.key === "Enter") {
                            e.preventDefault();
                            onApply(normal(url.trim()), blank);
                        }
                        if (e.key === "Escape") onClose();
                    }}
                    autoFocus
                />
                <button type="button" className="button small secondary" onClick={() => onApply(normal(url.trim()), blank)}>
                    Apply
                </button>
            </span>
            <label className="check-field">
                <input type="checkbox" checked={blank} onChange={(e) => setBlank(e.target.checked)} /> Open in a new tab
            </label>
            {href && (
                <button type="button" className="text-link danger-text" onClick={() => onApply("", false)}>
                    Remove link
                </button>
            )}
        </div>
    );
}
