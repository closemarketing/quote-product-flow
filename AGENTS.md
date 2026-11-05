# AGENTS.md — Quote Product Flow

AI agent instructions for working in this repository.

## Project overview

**Quote Product Flow** is a free WordPress plugin (GPL-2.0+) that provides an interactive, step-by-step product configurator with dynamic pricing, PDF budget generation, and email notifications. A separate **Pro** add-on (not in this repository) extends it with features like role-based discounts, PDF branding, recommendations, and import/export.

- Plugin slug: `quote-product-flow`
- Text domain: `quote-product-flow`
- PHP prefix: `qpfw_` (functions/globals), `QPFW_` (constants), `QPFW` (class prefix)
- Minimum WordPress: 4.0 | Tested up to: 6.x
- PHP: 7.4+

## Repository layout

```
pbc.php                        Main plugin file (bootstrap, constants, autoload)
includes/
  class-qpfw-admin-plugin.php   Admin menu, settings page, script enqueue
  class-qpfw-helper-posttypes.php  Custom post types, native metaboxes (variation, phases)
  class-qpfw-public.php         Frontend shortcode [pbc], AJAX handlers
  class-qpfw-request.php        Session and request handling
  class-qpfw-svg-support.php    SVG upload support
  helpers/
    class-calculations.php     Price calculation logic
    class-show-parts.php       Frontend rendering helpers
    class-show-template.php    Template loader
    class-generate-pdf.php     PDF generation (spipu/html2pdf + tecnickcom/tcpdf)
  assets/
    admin.css                  Admin styles (toggle sliders, settings layout)
    admin-scripts.js           Admin JS (repeatable groups, wp.media, toggles)
    public.css / public.js     Frontend styles and scripts
tests/                         PHPUnit + PHPStan tests
vendor/                        Composer dependencies (DO NOT edit)
composer.json / composer.lock
```

## Data model

Custom post types: `variation`, `phases`.

Post meta keys (stored as serialized PHP arrays via `update_post_meta`):

| Key | Type | Notes |
|-----|------|-------|
| `pbc_pricegroup` | `array[]` | `[{pbc_meaprice, pbc_pricem, pbc_rolprice, pbc_rol}]` |
| `pbc_depends` | `array[]` | `[{pbc_depvar, pbc_depvalue}]` |
| `pbc_imgprodgroup` | `array[]` | `[{pbc_depvarimgprod[], pbc_imgprod}]` |
| `pbc_question_depends` | `array[]` | `[{pbc_qdep_key, pbc_qdep_value, pbc_qdep_phase}]` |
| `pbc_is_question` | `'1'` or `''` | Toggle: convert variation to question |
| `pbc_show_custom_input` | `'1'` or `''` | Toggle: show textarea below variation |

Read these with `get_post_meta($id, 'pbc_pricegroup', true)` cast to `(array)`.

## Coding rules

- Follow WordPress coding standards (WPCS). Run `phpcs --standard=phpcs.xml.dist` before committing.
- No Meta Box library — all metaboxes use native WordPress API (`add_meta_box`, `save_post_{type}`).
- No license manager code in this free plugin — license logic lives in the Pro add-on only.
- Text domain is always `'quote-product-flow'` (never `'pbc'`).
- Do not call `load_plugin_textdomain()` — WordPress.org auto-loads it.
- Always escape output: `esc_html_e()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`.
- Always verify nonces in `save_post` hooks and AJAX handlers.
- Sanitize all input: `sanitize_text_field()`, `absint()`, `wp_kses_post()` as appropriate.
- Never `echo` raw `$_POST` data.

## Metabox pattern

Repeatable field groups use `<script type="text/template">` rows cloned by `admin-scripts.js`. See `render_variation_metabox()` in `class-qpfw-helper-posttypes.php` for the canonical pattern. The save handler iterates the posted arrays and stores them as serialized arrays.

## Toggle UI pattern

Boolean variation options use CSS toggle sliders (`.qpfw-toggle`) with a Dashicons info icon tooltip (`.qpfw-tooltip`). The description text goes in `data-tip` on the tooltip span, not as visible label text.

## Asset enqueue

Admin assets are enqueued in `class-qpfw-admin-plugin.php → enqueue_admin_scripts()` using `wp_enqueue_script` / `wp_enqueue_style` with the handle prefix `pbc-`. The `wp.media` API requires `wp_enqueue_media()` to be called on the same hook.

## Testing

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse
vendor/bin/phpcs --standard=phpcs.xml.dist includes/ pbc.php
```

## Distribution

Build artifacts are controlled by `.distignore`. The distributed zip must NOT contain: development config files, test suites, AI instruction files (`AGENTS.md`, `CLAUDE.md`, `.claude/`, `.cursor/`), or `composer.json/lock`.

Pro-only features are gated by `qpfw_is_pro()` (returns `false` in free) or the `qpfw_is_pro` filter.
