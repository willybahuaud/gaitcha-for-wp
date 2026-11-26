# Changelog

All notable changes to this project will be documented in this file.

## [1.3.3] — 2026-11-26

### Changed
- Gravity Forms: the field is now rendered as a `fieldset` with its label as `legend` (same markup as the native checkbox and consent fields), and the widget exposes its state to assistive technologies (`aria-required`, `aria-invalid`, `aria-describedby` pointing to the description and the validation message) (#4, #5). Forms using the legacy markup are unchanged. Custom CSS targeting `div.gfield--type-gaitcha` must drop the `div`
- Gravity Forms: a zero-specificity reset removes the browser's default fieldset border and padding on sites that disable the Gravity Forms CSS
- Core library updated to 0.8.1

### Fixed
- Gravity Forms: every page view requested a token and computed a proof of work before any interaction, because the adapter resets the widget on `gform_post_render`, which also fires on first display. A visitor who touched the form during that computation could leave the widget stuck in its pending state for 30 seconds, ending with "initialization failed" (#6). Fixed in the core library: a reset does nothing until the widget has been activated, and overlapping proof-of-work resolutions no longer cut each other off
- Native comment protection: scripts were enqueued on every singular view, including pages with comments closed (e.g. a static front page). They are now enqueued when the comment form is actually rendered (footer), so they load only where a comment form is displayed, including a form rendered for another post (#3). A theme that dequeues `gaitcha-native` to run its own initialization (AJAX navigation) must now do so on `wp_footer` with a priority below 20, no longer on `wp_enqueue_scripts`
- WPForms (AJAX forms): after a server-side refusal the widget stayed checked and was never reset, so every retry sent the same refused data until the page was reloaded. The adapter was printed before jQuery and silently skipped its reset handler; it now declares its jQuery dependency
- Formidable (AJAX forms): same dead end after a refusal. The reset handler was bound before jQuery was available and read the form as a jQuery object, while Formidable passes a DOM element
- Formidable (AJAX forms shown again after a successful submission): the widget was missing from the re-rendered form. `frmPageChanged` and `frmFormComplete` are jQuery events, native listeners never received them
- Contact Form 7: after a successful submission the widget stayed checked with a consumed token, so a second message from the same page was flagged as spam. The widget is now reset on `wpcf7mailsent`
- WPForms: "translation loading triggered too early" notice (WordPress 6.7+) on every request. With errors displayed, it corrupted REST and AJAX responses of other plugins (WS Form submissions failed with a JSON parse error). The field is now registered on `init`

## [1.3.2] — 2026-11-06

### Fixed
- Contact Form 7: the widget was never initialized on forms injected after page load (popins), which left them blocked.

## [1.3.1] — 2026-07-16

### Fixed
- Gravity Forms: the field was labeled "Untitled" in the form editor with no way to edit or hide it. The label now defaults to "Gaitcha", is editable (Label setting) and hideable (Field Label Visibility, Appearance tab) — same settings as the native GF captcha field

## [1.3.0] — 2026-06-10

### Added
- "Widget style" setting (Settings > Gaitcha > Appearance): Default or Minimal. Minimal is a sober monochrome variant — 2px radii, hairline borders, no shadows, ink-on-paper checked state — designed to blend into editorial and high-end sites. Combines freely with all themes (light/dark/auto)
- Builder previews (GF, WPForms, Formidable, Ninja Forms, WS Form, Elementor Pro) reflect the configured style

### Changed
- Core dependency bumped to `willybahuaud/gaitcha ^0.8`

### Fixed
- **Regression in 1.2.0** — Ninja Forms and WS Form: the widget never appeared. `initOnContainer` can run twice (mutation observer + DOM scan, or re-render events); since the core injects a placeholder at init time, the second call's `innerHTML = ''` wiped it. Containers are now guarded with a `data-gaitcha-bound` attribute

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
