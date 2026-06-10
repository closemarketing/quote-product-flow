=== Product Budget Configurator ===
Contributors: closetechnology, davidperez
Tags: configurator, budget, pricing, product, calculator
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Interactive step-by-step product configurator with dynamic pricing, budget generation, and email notifications.

== Description ==

**Product Budget Configurator** lets you build an interactive, multi-step product configurator on your WordPress site. Customers walk through phases, pick variations, and receive a detailed budget — all without leaving the page.

= Features =

* **Step-by-step wizard** — guide users through configurable phases
* **Multiple selection per phase** — allow checkbox-style multi-variation selection
* **Dynamic pricing** — per-variation prices summed in real time
* **Custom input fields** — attach a textarea to any variation ("Other / specify")
* **Direct phase inputs** — add text, textarea, or number inputs directly to a phase (no variations needed)
* **Question mode** — convert a variation into a numeric/text question; answer feeds into dependencies
* **Dependencies** — show or hide variations based on previous selections
* **Image preview** — real-time visual preview of selected options
* **PDF budget generation** — create and email a professional PDF budget
* **Email notifications** — send configuration details to the client and admin
* **Support contact buttons** — sticky phone and email buttons throughout the configurator
* **WhatsApp & email sharing** — share the budget PDF link via WhatsApp or attach it to an email
* **Import / Export** — CSV import and export of phases and variations
* **Recommended configurations** — one-click auto-fill based on first-phase selection
* **Price visibility control** — show or hide prices per user role
* **Custom colors, header, and footer** — style the configurator to match your brand
* **Admin budget editor** — manually create or edit budget lines in the enquiry post type
* **SVG support** — upload SVG files as variation icons

= Pro Features =

Unlock additional power with [Product Budget Configurator Pro](https://close.technology/wordpress-plugins/product-budget-configurator/):

* **Role-based discounts** — different prices per WordPress user role
* **PDF branding** — custom logo, colors, and layout in generated PDFs
* **Advanced recommendations** — multiple recommendation sets with dependency validation
* **Priority support** — direct access to the development team

== Installation ==

1. Upload the `product-budget-configurator` folder to `/wp-content/plugins/` or install via **Plugins > Add New**.
2. Activate the plugin from the **Plugins** page.
3. Go to **PBC** in the admin menu to configure settings.
4. Add the shortcode `[product-budget-configurator]` to any page to display the configurator.

== Frequently Asked Questions ==

= How do I add the configurator to a page? =

Add the shortcode `[product-budget-configurator]` to any page or post content.

= Can users select more than one option per step? =

Yes. Edit a phase and enable **Allow Multiple Selections** to let users check multiple variations.

= How do I attach a free-text field to a variation? =

Edit the variation and enable **Show custom input field**. A textarea will appear below all variations when that specific one is selected.

= How do I add an input field directly to a phase? =

Edit the phase and enable **Show direct input field**. Choose from Textarea, Text, or Number input types.

= Does it support dependencies between variations? =

Yes. In the variation editor, use the **Depends of** section to show a variation only when specific other variations are selected.

= Where are budgets saved? =

Budgets are saved as custom posts (enquiries) and PDF files in `/wp-content/uploads/pbc/`.

== Screenshots ==

1. Configurator front-end — step-by-step phase selection
2. Variation editor — fields, pricing, dependencies
3. Phase editor — settings and direct input options
4. Plugin settings page — appearance and email options
5. Budget admin view — enquiry with line editor

== Changelog ==

= 2.0.0 =
* Performance: Preload variation posts, meta and taxonomy terms in batch before render loop, eliminating N+1 queries per variation (200+ plugin queries reduced to 3).
* Fixed: PHP session lock released immediately after `session_start()` to prevent REST API timeouts.
* Fixed: Safe access to `wp_count_posts()` result in `is_multiple_products()` to avoid `stdClass::$publish` notice.

= 1.5.2 =
* Changed: WhatsApp share sends the budget PDF link; share-by-email attaches the PDF (filters `pbc_share_email_pdf_*`, `pbc_whatsapp_share_pdf_message`).
* Improved: Configurator choice cards — max 4 per row with consistent cell width; mobile 2 columns.
* Improved: Step navigation — 6 steps per row on desktop, better text contrast; PDF row text color on colored backgrounds.
* Improved: PDF — centered logo; financial summary hidden when prices are off or subtotal is zero; filter `pbc_pdf_show_financial_summary`.
* Improved: Calculate step — no empty preview column when there is no product image; share buttons spacing.
* Fixed: List padding in entry content for configurator pages.

= 1.5.1 =
* Added: Full budget line editor in admin (enquiry) — add rows manually or from catalog.
* Added: Support for question, quantity, fixed price, and multiple-selection line types when building budgets in admin.
* Added: Classic editor for enquiry post type so budget metabox saves reliably.
* Improved: Presupuestos show phase name and line type metadata consistent with frontend submissions.

= 1.5.0 =
* Added: Custom input fields for variations — show textarea when specific variation is selected.
* Added: Direct input fields for phases — show input directly without variations.
* Added: Three input types for phases: Textarea, Text, and Number (with ← / → increment buttons).
* Added: Multiple selection support per phase with checkboxes.
* Added: Real-time AJAX updates for multiple checkbox selections.
* Added: Recommended configurations system with one-click auto-fill.
* Added: Support contact buttons (phone and email) always visible in configurator.
* Added: WhatsApp and email sharing of the budget.
* Added: Variations count column in Phases admin list.
* Added: Phase filter in Variations admin list.
* Fixed: Form no longer auto-skips steps when direct input fields are present.
* Fixed: Nonce validation issues in AJAX requests.
* Improved: Modern button styling with flexbox layout and consistent ordering.

= 1.4.1 =
* Fixed: PDF generation and email sending.
* Fixed: Dependencies and phase handling.

= 1.4.0 =
* Added: Hierarchical products (parent-child).
* Added: Quantity selection per product.
* Added: PDF saving and client data capture.
* Added: Role-based price discounts.
* Added: Price visibility toggle.
* Removed: Template page option (use shortcode `[product-budget-configurator]`).
* Fixed: Dependencies and phase handling; image alpha/WebP.

= 1.3.1 =
* Included internal libraries.

= 1.3.0 =
* Option to print PDF.

= 1.2.0 =
* Added subtitle option.
* Fixed: email without VAT info and total.
* Fixed: clean session on first step.

= 1.1.0 =
* Coding standards pass.
* Added option to show prices.
* Save budgets in /uploads/pbc/.
* Added options for header and footer.
* Added image preview width option.
* Added variation sections.
* Bulk price updater.
* Fixed: option with space not working.

= 1.0 =
* First release.

== Upgrade Notice ==

= 1.5.2 =
PDF sharing improvements and layout fixes for configurator cards and step navigation.
