=== TechSolution Form Studio Pro ===
Contributors: usptechsolution
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: techsolution-form-studio
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Pro add-on for TechSolution Form Studio: address, international phone, signature, calculation, payment details and admission fields.

== Description ==

Adds these field types to the TechSolution Form Studio builder:

* Complete address with country, province / state and city lists for 250 countries and territories (bundled data, no external service).
* International phone with per-country dial codes, digit limits and display formats.
* Digital signature pad (mouse, touch or stylus), saved as an image with the entry.
* Calculation fields with live totals from other fields, recalculated on the server. Formulas are parsed as arithmetic only.
* Payment details for your records. No payment is processed; the full card number and security code are never sent to your site.
* Admission application block with a configurable list of programs.
* Templates: University Admissions and Order Form with Total.

The Pro add-on also keeps the old `[usp_form id="…"]` shortcode from USP Form Builder working.

== Licensing and updates ==

All features work without a license. Activate your license under Form Studio → Pro License to receive automatic updates and support. Activation, daily checks and updates send the license key, the site address and the plugin version to usp.com.pk (https://usp.com.pk).

== Location data ==

Address lists are from the Countries States Cities Database (https://github.com/dr5hn/countries-states-cities-database), available under the Open Database License (ODbL 1.0). See licenses/location-data-ODbL-1.0.txt.

== Changelog ==

= 2.0.0 =
* First release as a separate add-on for TechSolution Form Studio.
* Card numbers and security codes are no longer transmitted; only the brand and last four digits are stored.
* Calculations use an arithmetic parser and are recalculated on the server.
* Admission programs are configurable.
