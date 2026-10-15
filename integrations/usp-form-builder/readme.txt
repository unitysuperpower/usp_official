=== USP Form Builder ===
Contributors: waseem
Tags: form builder, contact form, drag and drop, forms, conditional logic, file upload
Requires at least: 6.3
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A powerful, visual drag-and-drop form builder for WordPress with real-time conditional logic, file uploads, pre-built templates, email automations, and bulk submission management.

== Description ==

**USP Form Builder** by [USP Tech Solution](https://usptechsolution.com/) provides an intuitive, high-performance way to create contact forms, job applications with CV uploads, RSVP registrations with conditional logic, customer satisfaction ratings, and multi-step surveys.

= Key Features =
* **Full Builder Undo / Redo:** Restore fields, titles, notification settings, submission restrictions, and styling. History keeps the most recent 35 states while the builder is open.
* **Searchable Form Outline:** Find existing fields by label, type, or key and select them to edit. See field order, required fields, and conditional rules at a glance.
* **Country & Nationality Fields:** Add a ready-made list of 249 countries and territories, with a search inside the dropdown, a native-select fallback, and server-side answer validation. Nationality is collected as a country name.
* **Visual Drag & Drop Builder:** Create and customize responsive forms in seconds with live canvas preview (Desktop, Tablet, Mobile).
* **1-Click Pre-Built Form Templates:** Instant starter templates for Simple Contact, Job Application & CV, Feedback & Rating, and Event RSVP (Conditional).
* **Real-Time Conditional Logic:** Show or hide fields dynamically based on user selections with multi-rule matching (ALL / ANY).
* **Advanced Field Types:** File Uploads, Rating Range Sliders, Time Pickers, Website URLs, Hidden Fields, along with standard inputs, selects, radios, and checkboxes.
* **Secure File Upload Handling:** Validates file extensions and size limits using native WordPress upload APIs (`wp_handle_upload`).
* **Email Automations & Merge Tags:** Dynamic tags (`{all_fields}`, `{form_title}`, `{entry_id}`, `{user_name}`, `{user_email}`), custom Reply-To, and Submitter Auto-Responder confirmation emails.
* **Submissions & Entry Management:** View, search, and filter entries by Form, Status (All, Unread, Read, Starred), Date Range, and Search keywords.
* **Bulk Entry Actions:** Bulk mark read, unread, star, and delete entries with one click.
* **CSV Export:** Export filtered submission records to CSV with UTF-8 BOM encoding for Excel.
* **Anti-Spam Honeypot:** Catches spam bots silently without annoying CAPTCHAs.
* **Shortcode Support:** Embed any form anywhere on your site using `[usp_form id="1"]`.

== Installation ==

1. Upload the `usp-form-builder` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **USP Forms** in the left admin menu to start creating forms or load a template.
4. Copy the generated shortcode (e.g., `[usp_form id="1"]`) and paste it into any post, page, or widget.

== Frequently Asked Questions ==

= How do I add a country or nationality field? =
Open the form builder, find **Choice Fields** under **Add fields**, and click **Country** or **Nationality**. The country list is included automatically. Change the label, placeholder, help text, width, and required setting in the Field panel. Visitors can open the country dropdown and search below its country list before choosing an answer. Nationality uses country names (for example, Pakistan), rather than demonyms (Pakistani).

= How do I embed a form on a page? =
Simply copy the shortcode from the Forms list or builder top bar (e.g. `[usp_form id="1"]`) and insert it into any Gutenberg Shortcode block or classic content editor.

= How does conditional logic work? =
Select any field on the canvas, switch to the Field Options tab, toggle **Conditional Logic**, and set rules to show or hide the field when another input meets specific conditions.

= Are uploaded files securely stored? =
Yes! Files are processed via WordPress's secure `wp_handle_upload` API with strict MIME type and file extension verification. Download links appear directly in your dashboard submissions manager.
