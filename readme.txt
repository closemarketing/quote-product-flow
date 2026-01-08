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
* **Multiple Selection**: Allow users to select multiple variations per phase with checkboxes
* **Hierarchical Products**: Support for multiple products with parent-child relationships
* **Dynamic Pricing**: Prices per role with discount support
* **PDF Generation**: Generate and email professional budget PDFs
* **Image Preview**: Real-time visual preview of selected options
* **Support Buttons**: Always-visible contact buttons for technical support (phone & email)
* **Price Visibility**: Control price display per user role
* **Customizable**: Custom colors, headers, footers, and styling options
* **Email Notifications**: Send configuration details to clients and administrators

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


== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your
WordPress installation and then activate the Plugin from Plugins page.

== Changelog ==
= 2.0.1 ==
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

= 2.0.0 ==
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

