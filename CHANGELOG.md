# Changelog

All notable changes to this project will be documented in this file.

## [1.1.0] — 2026-03-12

### Added
- Elementor Pro connector (handler pattern, AJAX validation, editor preview)
- Native WordPress forms protection: login, register, lost password, comments
- Settings page (Appearance > Widget theme, Native forms toggles)
- Static widget preview in all form builder editors (GF, WPForms, Formidable, Ninja Forms, WS Form, Elementor Pro)
- WidgetPreview helper class for admin context rendering
- `.l10n.php` translation files for FR, DE, ES, IT (WP 6.5+ preferred format)
- `Domain Path: /languages` header for JIT translation loader

### Changed
- Captcha label locked to translated default — no more per-field customization
- Label shortened: EN "I'm a real person", FR "Je suis humain", DE "Ich bin ein Mensch", ES "Soy humano", IT "Sono umano"
- Widget max-width reduced from 290px to 260px
- Core dependency bumped to `willybahuaud/gaitcha ^0.6` (touch scoring, compact badge)
- All JS adapters simplified: removed `readFieldLabel()`, use `config.defaultLabel`
- Removed `data-gaitcha-label` HTML attribute from all connectors

### Fixed
- Fluent Forms: register gaitcha as input type for FF parser
- Fluent Forms: parse nested POST data for gaitcha token
- WPForms, Ninja Forms, Fluent Forms: validation bugs
- Gravity Forms: reset on validation errors via `gform_post_render`
- Elementor: enqueue scripts via frontend hook instead of render_field (cache compat)
