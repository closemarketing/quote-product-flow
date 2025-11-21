=== Product Budget Configurator ===
Contributors: closemarketing, davidperez
Tags: budget, configurator
Requires at least: 4.0
Tested up to: 6.0

Creates a configurator with all variables.

== Description ==

Product Budget Configurator is a powerful WordPress plugin that allows you to create interactive product configurators with multiple phases, variations, and pricing options.

= Key Features =

* **Interactive Configurator**: Step-by-step wizard for product configuration
* **Hierarchical Products**: Support for multiple products with parent-child relationships
* **Dynamic Pricing**: Prices per role with discount support
* **PDF Generation**: Generate and email professional budget PDFs
* **Image Preview**: Real-time visual preview of selected options
* **Support Buttons**: Always-visible contact buttons for technical support (phone & email)
* **Price Visibility**: Control price display per user role
* **Customizable**: Custom colors, headers, footers, and styling options
* **Email Notifications**: Send configuration details to clients and administrators

= Support Contact Feature =

Enable sticky support buttons that remain visible throughout the configuration process. Users can quickly contact technical support via:
* **Phone**: Direct call link (opens phone dialer or FaceTime)
* **Email**: Opens email client with support address pre-filled
* Visual notifications confirm when buttons are clicked

Configure support options in the plugin settings under "Support Contact".


== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your
WordPress installation and then activate the Plugin from Plugins page.

== Changelog ==
= 1.4.2-beta.1 ==
*  Settings Page Redesign: Complete visual overhaul with modern card-based layout, purple gradient theme, and improved user experience. License management now integrated directly into settings.
*  Configurator skip fixed panel.
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

