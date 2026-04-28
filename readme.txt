=== Product Budget Configurator ===
Contributors: closemarketing, davidperez
Tags: budget, configurator
Requires at least: 4.0
Tested up to: 6.0
Stable tag: 1.5.3

Creates a configurator with all variables.

== Description ==

Product Budget Configurator is a powerful WordPress plugin that allows you to create interactive product configurators with multiple phases, variations, and pricing options.

= Key Features =

* **Interactive Configurator**: Step-by-step wizard for product configuration
* **Multiple Selection**: Allow users to select multiple variations per phase with checkboxes
* **Hierarchical Products**: Support for multiple products with parent-child relationships
* **Dynamic Pricing**: Prices per role with discount support
* **PDF Generation**: Generate and email professional budget PDFs
* **Image Preview**: Real-time visual preview of selected options
* **Support Buttons**: Always-visible contact buttons for technical support (phone & email)
* **Price Visibility**: Control price display per user role
* **Customizable**: Custom colors, headers, footers, and styling options
* **Email Notifications**: Send configuration details to clients and administrators
* **Custom Input Fields**: Add custom input fields to variations and phases
* **Direct Input Fields**: Add input fields directly to phases without variations (textarea, text, or number with increment/decrement buttons)

= Multiple Selection Feature =

Enable multiple variations selection per phase, allowing users to select multiple options at once:
* **Checkbox Mode**: Users can select multiple variations in a single phase
* **Real-time Updates**: Selected variations appear instantly in the configuration summary
* **Combined Display**: Multiple selections are displayed as comma-separated names (e.g., "Albañil, Electricista, Otro")
* **Price Calculation**: Prices from all selected variations are automatically summed
* **Easy Configuration**: Enable multiple selection mode per phase via the phase settings

To enable: Edit a phase and check the "Allow Multiple Selections" option.

= Support Contact Feature =

Enable sticky support buttons that remain visible throughout the configuration process. Users can quickly contact technical support via:
* **Phone**: Direct call link (opens phone dialer or FaceTime)
* **Email**: Opens email client with support address pre-filled
* Visual notifications confirm when buttons are clicked

Configure support options in the plugin settings under "Support Contact".

= Custom Input Fields =

The plugin supports two types of custom input fields:

**1. Variation Custom Input (Conditional)**
When editing a variation, you can enable "Show custom input field" checkbox. This will display a textarea field below all variations when that specific variation is selected. Perfect for options like "Other" where users need to specify additional details.

**2. Phase Direct Input (Always Visible)**
When editing a phase, you can enable "Show direct input field" checkbox. This displays an input field directly without needing variations. You can choose from three input types:
* **Textarea**: Large multi-line text box (default)
* **Text**: Single-line text input
* **Number**: Numeric input with increment/decrement buttons (← and →) that change the value by 1

The number input has a default value of 0 and includes custom styled buttons for easy value adjustment. All input values are saved in the session and included in the final configuration summary.


== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your
WordPress installation and then activate the Plugin from Plugins page.

== Changelog ==
= 1.5.3 =
* Performance: Preload variation posts, meta and taxonomy terms in batch before render loop, eliminating N+1 queries per variation (200+ plugin queries reduced to 3).
* Fixed: PHP session lock released immediately after `session_start()` to prevent REST API timeouts.
* Fixed: Safe access to `wp_count_posts()` result in `is_multiple_products()` to avoid `stdClass::$publish` notice.

= 1.5.2 =
* Changed: WhatsApp share sends the budget PDF link; share-by-email attaches the budget PDF (filters `pbc_share_email_pdf_*`, `pbc_whatsapp_share_pdf_message`).
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
*  Added: Custom input fields for variations - show textarea when specific variation is selected
*  Added: Direct input fields for phases - show input directly without variations
*  Added: Three input types for phases: Textarea (large), Text (single line), and Number (with increment/decrement buttons)
*  Added: Number input with custom styled buttons (← decrease, → increase) that change value by 1
*  Added: Default value of 0 for number inputs
*  Improved: Custom input fields appear below all variations (not inside each variation)
*  Improved: Input values are saved in session and displayed in configuration summary
*  Fixed: Form no longer auto-skips steps when direct input fields are present
*  Added: Multiple selection support per phase with checkboxes
*  Added: Real-time AJAX updates for multiple checkbox selections
*  Added: Comma-separated display of multiple selected variations
*  Added: Automatic price summing for multiple selections
*  Added: Helper function check_phases_options() for advanced phase validation
*  Fixed: Variation name display in real-time when selecting multiple options
*  Fixed: Restored Import/Export menu entry that was missing from admin menu
*  Fixed: Phase breadcrumb navigation now adapts to long phase names with flexible height
*  Fixed: Removed padding from phase steps for better visual balance
*  Improved: All button styles now use high specificity to prevent theme conflicts
*  Improved: Buttons maintain consistent styling across different WordPress themes
*  Fixed: Removed arrow decorations (::after) from Back, Restart, and Recommendation buttons
*  Fixed: Button layout now uses flexbox for proper alignment in a single row
*  Fixed: Buttons correctly display in row for all states (navigation, calculation, etc.)
*  Improved: Reduced button size with smaller padding (6px 14px) and font-size (13px)
*  Fixed: Notice messages now display in black color for better readability
*  Improved: Modern button styling with rounded corners, shadows, and hover effects
*  Fixed: Button order maintained consistently: Back > Restart > Recommendation > Next/Calculate
*  Configurator skip fixed panel.
*  Settings Page Redesign: Complete visual overhaul with modern card-based layout, purple gradient theme, and improved user experience. License management now integrated directly into settings.
*  Added: Export/Import page with real-time logging.
*  Added: Export/Import functionality for phases and variations with slug-based references.
*  Added: Automatic slug generation for phases and variations.
*  Added: Export to two separate CSV files (phases and variations).
*  Added: CSV format with comma-separated complex fields (dependencies, prices, image groups).
*  Added: Import from separate CSV files with automatic ID mapping.
*  Added: Real-time log display showing progress during export/import operations.
*  Added: Support for importing phases and variations independently.
*  Added: Recommended configurations system with one-click auto-fill
*  Added: Multiple recommendations based on first-phase selection
*  Added: Separate admin page for managing recommendations (PBC > Recommendations)
*  Added: Dependency validation in recommendation admin interface
*  Added: Collapsible recommendation panels in admin
*  Added: Real-time dependency filtering in admin editor
*  Added: Auto-progression through all phases when applying recommendations
*  Added: Green "Recomendación" button in frontend (appears after first selection)
*  Added: Automatic budget calculation after recommendation process
*  Added: Restart button to clear configuration and start from step 1
*  Added: Side-by-side layout for product preview and summary on calculation page
*  Added: Support contact buttons (phone and email) always visible in configurator
*  Added: Settings to enable/disable support buttons and configure phone/email
*  Added: Visual notification (fade-in) when clicking support buttons
*  Added: Share configuration via WhatsApp and Email
*  Added: Variations count column in Phases admin list with clickable links to filter variations by phase
*  Added: Phase filter dropdown in Variations admin list with auto-submit functionality
*  Added: Clickable phase links in Variations list that navigate to Phases list with scroll positioning
*  Fixed: Configurator skip fixed panel
*  Fixed: Nonce validation issues in AJAX requests
*  Feature: Support buttons are sticky and always accessible during configuration process
*  Fixed: Configurator skip fixed panel.

= 1.4.1 =
*  Fixed: Pdfs generation and send emails correctly.
*  Fixed: Problems with dependencies and phases.

= 1.4.0 =
*  Added: You can add hierarchical in products, so you can have different products.
*  Added: You can select quantity of each products.
*  Added: Print PDF now saves it internally and gets client data.
*  Added: Prices per roles discount.
*  Added: Don't show prices in PDF if the option is not selected. Show them in admin.
*  Removed: removed the template page option. Now is only shows with shortcode: [pbc].
*  Removed: removed bullets points.
*  Added: Price visibility toggle.
*  Fixed: problems with dependencies and phases.
*  Fixed: problems with image alpha webp.

= 1.3.1 =
*  Included Internal libraries.

= 1.3.0 =
*  Option to Print PDF.

= 1.2.0 =
*	Adds subtitle option.
*  Fix: email without VAT info and total.
*  Fix: clean session in first step.

= 1.1.0 =
*	Coding standards.
*  Added option to show prices.
*  Save budgets in /uploads/pbc/
*  Updated Metabox Group 1.3.14
*  Added options for header and footer.
*  Added option for width image preview.
*  Added sections in variations.
*  Refactored Classes.
*  Added options to customize Budget.
*  Bulk price updater.
*  Fix: Error option with space not working.

= 1.0 =
*	First released.


== Links ==
*	[Closemarketing](https://close.marketing/)
*	[Closetechnology](https://close.technology/)

