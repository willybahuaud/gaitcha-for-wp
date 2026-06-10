# Changelog

All notable changes to this project will be documented in this file.

## [1.2.0] — 2026-06-10

### Added
- Proof-of-work layer enabled by default: the init endpoint requires a solved HMAC-signed challenge before issuing tokens (core gaitcha 0.7.0). Tunable or disableable via the `gaitcha_config` filter (`pow`, `pow_difficulty`, `pow_challenge_ttl`)
- Placeholder widget visible at page load: same dimensions as the final captcha (zero layout shift), dimmed and non-interactive, exposes no field name or token
- Challenge nonces are consumed through `WPTokenStore` (anti-replay was already on by default): one solved challenge = one token

### Changed
- REST endpoint forwards the decoded JSON request body to the core `handleInit()` (two-phase init: challenge, then token)
- Core dependency bumped to `willybahuaud/gaitcha ^0.7`
- Core JS bundle updated (PoW solver in Blob-based Web Worker with main-thread fallback, pending widget state)

### Fixed
- Ninja Forms: form could never be resubmitted after a first attempt without checking the captcha (#2). NF kept a stale error on the gaitcha field in its Backbone model — the gaitcha checkbox lives outside that model, so checking it never lifted the error and NF blocked the resubmission client-side. The adapter now clears the field errors when the widget gets checked

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
