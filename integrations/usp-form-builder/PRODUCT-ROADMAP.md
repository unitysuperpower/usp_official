# USP Form Builder product direction

Build an independent WordPress form builder that makes creating, testing, and managing forms easy for small businesses and agencies. Compete through reliable workflows, accessible design, and useful features.

## Current foundation

The repository includes a visual builder, field search, templates, field duplication and ordering, undo/redo, responsive canvas sizes, conditional rules, theme controls, JSON export, email settings, submission restrictions, webhook settings, and an entries interface. These need end-to-end WordPress validation before being advertised as production ready. Pro field controls do not establish that a complete payment or integration service exists.

The builder currently restricts file uploads, star ratings, GDPR/terms consent, country/nationality, rating sliders, multi-step forms, digital signatures, calculations, complete addresses, international phone, admissions, and payment fields to the Pro tier. Pro-only field access is checked in the builder and on save, public rendering, and submission. A Pro payment field is a form input/demo workflow, not a verified payment-provider integration.

## First editing improvements implemented

- Save status distinguishes saved, unsaved, saving, and failed states.
- Leaving the builder with unsaved edits triggers the browser's navigation warning.
- Ctrl+S / Cmd+S saves; duplicate requests are blocked during a save.
- Edits made while a save is running remain unsaved after that request succeeds.
- Field search explains when no fields match.
- Opening an existing empty form preserves its empty canvas.
- Save, export, and change tracking share one settings collector.

## Additional builder improvements implemented

- Country and nationality fields include a searchable country/territory list.
- Form outline searches existing fields by label, field key, and type.
- Outline selection opens field settings and scrolls the canvas to the selected field.
- Field settings show position and associate common labels with their inputs.
- Toolbars wrap and smaller screens stack the builder panels.
- Undo/redo restores settings and style controls, pending edits, title changes, and field widths; the preview and active settings panel stay synchronized.

## Next milestones

| Milestone | Deliverable | Acceptance criteria |
| --- | --- | --- |
| Builder experience | Clear Build / Settings / Entries navigation, searchable form outline, accessible dialogs and field controls | Build and reorder a form by keyboard; dialogs restore focus; small screens retain access to all actions |
| Reliability | Revision history, recoverable drafts, settings validation | Restore a previous version; recover interrupted editing; show specific validation and save errors |
| Form capabilities | Multi-step forms, calculations, repeatable groups, conditional sections, save-and-resume | Validate steps on server and client; hidden fields do not block completion; calculation rules cannot execute arbitrary code |
| Submission workflow | Entry detail view, tags, notes, filters, bulk actions, delivery history | Staff can find and process an entry; failed notifications can be inspected and retried |
| Integrations | WordPress block, documented import format, CRM adapters, signed webhooks with retries | Import validates supported fields and settings; delivery failures are visible; integrations have connection tests |
| Advanced workflows | Payment-provider integration, bookings, PDF documents, conversational mode | Verify payments through provider webhooks; prevent duplicate processing; clearly explain external services |

## Release gates

Test on a running WordPress installation with administrator and restricted accounts. Verify form saving, public validation, conditional behavior, file uploads, email delivery, entries, and upgrades. Include keyboard and mobile checks. Review permissions, nonce handling, sanitization, output escaping, upload access, and webhook destinations before shipping.

## Competitive reference

Fluent Forms already documents drag-and-drop editing, AI form creation, conversational forms, and conditional logic. Matching basic field types is only a starting point. USP's proposed distinction is a cohesive editing and submission workflow, visible delivery diagnostics, strong accessibility, and transparent feature availability.

References checked on October 12, 2026:
- https://docs.fluentforms.com/
- https://fluentforms.com/docs/set-up-forms-with-conditional-logic-in-fluent-forms/

The milestones above are proposed work, not features included in the current plugin.
