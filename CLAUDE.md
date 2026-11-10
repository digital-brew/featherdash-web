# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## AI Guidelines

Always read and follow the guidelines in `.ai/guidelines/` before working:
- `.ai/guidelines/git.md` — commit only when explicitly asked, no AI author/co-author attribution, show files/message/author before committing
- `.ai/guidelines/models.md` — model selection for workflows and subagents

## Project Overview

Marketing site for **Featherdash** (a WordPress admin theme plugin by DigitalBrew), built on **Radicle** (Roots' WordPress stack):
- **Backend**: Acorn 6 (Laravel) inside WordPress 7.1, Bedrock layout
- **Frontend build**: Vite 8 + `@roots/vite-plugin` + `laravel-vite-plugin`
- **Styling**: Tailwind CSS 4 (design tokens in `resources/css/common/theme.css`, exported to `theme.json` at build)
- **Blocks**: Native Gutenberg blocks — React edit component + server-side Blade render (no ACF blocks)
- **Interactive**: Alpine.js on the frontend
- **Fonts**: Self-hosted via `@fontsource-variable/*` (Bricolage Grotesque for display, DM Sans for body)

## Local Development

- Local URL: https://local.featherdash.io/ (production: https://featherdash.io), served by **Lerd** (Podman-based; `.lando.yml` is a Radicle leftover and not used)
- PHP 8.5 and Composer come from Lerd (`~/.local/share/lerd/bin`); Node/npm from mise
- Database host is `lerd-mysql` (see `.env`)

```bash
npm install          # Node dependencies
npm run dev          # Vite dev server with HMR
npm run build        # Production build into public/build/
npm run lint         # oxlint
npm run format       # oxfmt

composer install     # PHP dependencies + WordPress core + plugins
composer lint        # mago lint
composer format      # mago format --check (composer format:fix to apply)
composer test        # Pest

wp acorn <command>   # Acorn/Artisan commands (e.g. wp acorn make:block, wp acorn acf:options)
npm run translate    # Regenerate .pot / update .po files
```

Playwright E2E specs live in `tests/e2e/` (`npx playwright test`, set `WP_HOME`).

## Composer & Plugins

- WordPress.org plugins/themes come from `repo.wp-packages.org` (repository name `wp-packages`). Package vendor is **`wp-plugin/<slug>`** / **`wp-theme/<slug>`** — not `wpackagist-plugin/` and not `wp-packages/`.
- Premium plugins come from the private SatisPress repo (`digital-brew/*` only). Credentials live in the gitignored `auth.json`.
- Plugins install to `public/content/plugins/` (gitignored) and must be activated in WP.
- Mail goes through `roots/acorn-mail` (SMTP via `MAIL_*` env vars) — do not add an SMTP plugin.
- `roots/acorn-post-types` already pulls in `johnbillion/extended-cpts`; post types are configured in `config/post-types.php`.
- Installed for theme code: `log1x/acf-composer` (options pages/fields in `app/Options`, `app/Fields`), `log1x/navi` (menus), `log1x/sage-directives`.

## Architecture & Key Files

### Providers (`app/Providers/`, registered in `composer.json` → `extra.acorn.providers`)
- `BlocksServiceProvider` — registers server-rendered blocks (`register_block_type()`, API v3) and core block render overrides
- `ThemeServiceProvider` — theme supports, menus, image sizes from `config/theme.php`
- `AssetsServiceProvider` — editor styles/scripts injection, generated `theme.json` path
- `ApiServiceProvider` — REST endpoints

### Views (`resources/views/`)
- `layouts/app.blade.php` — page shell; includes `sections/header` and `sections/footer`
- `sections/header.blade.php`, `sections/footer.blade.php` — **sitewide** header and footer (not blocks)
- `blocks/*.blade.php` — server render templates for custom blocks
- `components/` — anonymous Blade components (`<x-button>`, `<x-heading>`, …)
- View data comes from composers in `app/View/Composers/` (`App` composer applies to all views)

### Frontend assets
- `resources/css/app.css` — frontend entry; `resources/css/editor.css` — block editor entry
- `resources/css/common/theme.css` — Tailwind `@theme` tokens (colors, fonts) shared by both
- `resources/js/app.js` — frontend (Alpine); `resources/js/editor.js` — registers editor blocks
- `resources/images/icons/*.svg` — Blade Icons default set (`<x-icon-name />`)
- `theme.json` is a source file; the built version with Tailwind tokens lands in `public/build/assets/theme.json`

## Working With Blocks

Custom blocks are dynamic: attributes are edited in React, markup is rendered by Blade.

### Landing page sections (`featherdash/*`) — preferred pattern

Each section lives in `resources/js/editor/blocks/<slug>/`:
- `block.json` — **single source** of name, attributes and defaults, read by both PHP and JS. Give every attribute a default (Blade expects every variable to exist), but keep content defaults empty: new blocks start blank with placeholders, the copy lives in the pattern.
- `edit.jsx` — inline editing with shared helpers from `resources/js/editor/components/` (`Text` RichText wrapper, `useItems`/`ItemControls`/`AddItem` for array attributes, `IconUpload`, `MediaField`/`useMediaUrl`, `ColorPanels`, `Anchor`, `UrlField`)
- `colors.js` — the block's color settings (`{ key, label, token }`), shown in the editor's Styles tab
- `index.js` — re-exports `metadata` + `edit`; `editor.js` auto-registers every `blocks/*/index.js` with `save: () => null`

Rendering: `BlocksServiceProvider` registers every `block.json` with `App\Blocks\Section`, which renders `resources/views/blocks/<slug>.blade.php`. Attributes arrive as variables, already passed through `wp_kses_post` (so `{!! $heading !!}` is safe; still use `esc_url()` on URLs). `$wrapper('classes')` outputs the block wrapper attributes plus the `anchor` id.

- Colors: every section has a `colors` object attribute. `App\Blocks\Section` turns it into CSS variables on the wrapper (`bg` → `--c-bg`), and templates read them with the design token as fallback: `bg-[var(--c-card-bg,var(--color-sky))]`. Unset colors store nothing, so blocks follow the design. Repeated items with different looks (feature cards, featured plans, story rows) set per-item variables (`--card-bg`, `--row-bg`…) inline and share one set of classes. Keep JSX and Blade class strings identical.
- Images: anywhere a visual lives (hero visual, feature card illustrations, video poster, card icons) there is an upload; when empty the built-in mockup/illustration shows. Card icons store `iconId`; `<x-card-icon :item>` falls back to the bundled SVG named in `icon` (`resources/images/icons/featherdash/`, two-tone 48px illustrations), rendered in the editor by `IconUpload`.
- Pattern: `resources/patterns/landing.php` holds the full landing page copy, registered as the `featherdash/landing` block pattern
- Landing pages use `front-page.blade.php` or the "Landing Page" template (`template-landing.blade.php`), which render full-width without the default container

### Radicle-style blocks (`radicle/*`)

1. `resources/js/editor/<name>.block.jsx` — exports `name`, `title`, `category`, `icon`, `attributes`, `edit`, `save`
2. Add it to the `blocks` array in `resources/js/editor.js`
3. `app/Blocks/<Name>.php` — `render()` returning `view('blocks.<name>', …)`
4. Register in `BlocksServiceProvider` with the same attribute schema as the JS
5. `resources/views/blocks/<name>.blade.php`

### Rank Math

`resources/js/editor/rank-math.js` hooks `rank_math_content` to replace the sections' empty saved markup with HTML built from their attributes (hero h1, section h2, item h3, links, image alts), so Rank Math's editor score reflects the real page. Update it when a section gains or renames text fields.

## Docs (synced from Markdown)

The `doc` post type (`config/post-types.php`, archive at `/docs/`) is filled from the Featherdash plugin's Markdown docs by:

```bash
wp acorn docs:sync              # create/update changed docs (idempotent)
wp acorn docs:sync --dry-run    # show what would change
wp acorn docs:sync --force      # rewrite everything (e.g. after changing the converter)
wp acorn docs:sync --prune      # also unpublish docs whose .md file was removed
wp acorn docs:sync --source=/path/to/docs --changelog=/path/to/CHANGELOG.md
```

- Sources: `config/docs.php` (`DOCS_PATH`, `DOCS_CHANGELOG`; defaults to `public/content/plugins/featherdash/docs` and its `CHANGELOG.md`). Production needs these files present (the WordPress.org build may not ship `docs/`), so point the env vars at a checkout and run the sync on deploy.
- `README.md` is the docs home: shown at `/docs/` (stored in the `featherdash_docs_index` option) and its link order sets each doc's `menu_order`. `CHANGELOG.md` becomes the `changelog` Page (template `template-docs.blade.php`).
- Each `*.md` becomes one doc (slug = file name). First `# Heading` → title, first paragraph → excerpt (and the Rank Math description), the rest → core blocks via `App\Docs\MarkdownToBlocks` + `BlockMarkup`. Relative `*.md` links point to the matching doc/archive/changelog URL; headings get ids so `file.md#section` links work. Bump `MarkdownToBlocks::VERSION` when the generated markup changes so the next sync rewrites everything.
- Posts are matched by `_docs_source` meta (file name) and skipped while `_docs_hash` matches. Edits made in WordPress are overwritten on the next sync (the editor shows a warning); change the Markdown in the plugin repo instead.
- Don't name a command option `--path`: WP-CLI swallows it (global WordPress path option).
- Views: `archive-doc`, `single-doc`, `template-docs` (all use `<x-docs-layout>` with the sidebar nav from `App\View\Composers\Docs`), prose styles in `.docs-prose` (`resources/css/app.css`).

## Header, Footer & Site Settings

- Sitewide, not blocks: `sections/header.blade.php` / `sections/footer.blade.php`, data from `App\View\Composers\SiteChrome`
- Menus via Navi: `primary_navigation` (header) and `footer_navigation` (top-level items become column headings, children become links)
- Content comes from the ACF options page **Site Settings** (`app/Options/SiteSettings.php`): announcement bar, header button, footer tagline, newsletter, copyright (`{year}` placeholder)
- The header is sticky: the announcement bar plus a floating frosted-glass bar (logo, plain nav links, light teal button with arrow), with a glass dropdown panel below `md`. `resources/js/components/sticky-header.js` publishes `--nav-height` and `--header-height`. `#main` is padded by `--nav-height`, a leading hero block pulls up underneath it, and `scroll-padding-top` keeps anchored sections below the header (smooth scroll respects reduced motion).
- Footer newsletter posts straight to Klaviyo's client subscriptions API (`resources/js/components/newsletter.js`). It renders only when the Klaviyo plugin's public API key and the "Klaviyo list ID" option are both set.

## Style & Format

- PHP: mago (`mago.toml`, 120 cols, sorted imports) — run `composer format:fix` and `composer lint`
- JS/CSS: oxlint + oxfmt
- i18n text domain: `radicle`; wrap strings in `__()`

## Quirks & Gotchas

- **No `theme.json` element styles or root padding.** WordPress prints them as unlayered CSS, which beats Tailwind's layered utilities. Element defaults (headings, p, lists) live in `resources/css/common/base.css`; `resources/views/blocks/_global.theme.js` holds only block styles.
- **The editor may not be iframed.** Plugins registering pre-v3 blocks (AIOSEO, Advanced Forms) make WordPress render the canvas inline, so wp-admin CSS leaks in. That's why `editor.css` imports Tailwind with `important` and resets typography inside `[data-type^="featherdash/"]`.
- **Composer autoload order.** Some plugins bundle unprefixed copies of shared libraries (AIOSEO Pro shipped Monolog 1 / PSR Log 1 and crashed Acorn on its first log write). `public/content/mu-plugins/composer-autoload-priority.php` re-prepends the project autoloader after each plugin loads so our versions always win. The free AIOSEO now in use doesn't bundle them, but keep the mu-plugin as a guard.
- The Featherdash plugin is required as `digital-brew/featherdash:dev-main` from its private GitHub repo (`vcs` repository, authenticated by the `github-oauth` token in `auth.json`). Locally (since 2026-11-05), `public/content/plugins/featherdash` is a copy of the **free** build (unzipped from the Freemius free zip, also served at `public/download/featherdash.zip`) instead of the repo symlink; `composer install`/`update` replaces it with the git version, so unzip the free zip again afterwards. Run `composer update digital-brew/featherdash` to deploy new plugin commits.

- `vendor/`, `public/wp/`, `public/content/plugins/*`, `public/build/` are build artifacts — never edit them.
- Composer network access may need to run outside Claude's sandbox (sandbox DNS blocks `codeload.github.com`).
- Editor styles are injected via `block_editor_settings_all`; editor scripts via `admin_head` reading `editor.deps.json`.
- Theme supports must be registered in `config/theme.php`, not only in `theme.json`.

## Tailwind CSS classes

These rules apply whenever you write or edit Tailwind classes (Blade, JSX/JS, CSS). When asked to audit or refactor the classes, follow the process at the end.

1. **Tailwind's default scale is used whenever a value matches it.**
2. **Values that repeat but aren't in the defaults become theme tokens** in the `@theme` block.
3. **Arbitrary values (`[...]`) are kept only for true one-offs.**
4. **Lengths are written in rem, not px.**

A refactor must not change the rendered output: same computed values, same breakpoints, same states.

### This project

- Tailwind **4.3**.
- Theme tokens (`@theme`): `resources/css/common/theme.css`.
- `@utility` definitions: `resources/css/common/utilities.css`.
- Verify with `npm run build`; lint/format with `npm run lint` and `npm run format`.

### Rules

**Use the defaults (Tailwind 4):**
- Spacing, sizing and inset (`p`, `m`, `gap`, `w`, `h`, `size`, `top`…) are calculated from `--spacing` (0.25rem), so any multiple of 4px has a utility: `p-[18px]` stays arbitrary (written `p-[1.125rem]`), but `gap-[28px]` → `gap-7`, `w-[220px]` → `w-55`, `size-[84px]` → `size-21`, `min-h-[56px]` → `min-h-14`. Half steps also exist (`p-2.5` = 10px).
- Font size: `text-xs/sm/base/lg/xl/2xl…` (12/14/16/18/20/24px…). Don't use them when the value only roughly matches.
- Radius: `rounded-sm/md/lg/xl/2xl/3xl/4xl` (4/6/8/12/16/24/32px), `rounded-full`.
- Line height, letter spacing, font weight, opacity, z-index, shadows, max-widths (`max-w-md`, `max-w-3xl`…): use the named utility when it matches exactly.
- Colours: only theme tokens (`bg-brand`, `text-ink/60`). A raw hex value in a class is a bug unless it's a true one-off.
- CSS variables: use the shorthand `bg-(--card-bg)`, not `bg-[var(--card-bg)]`. Keep a fallback (`bg-[var(--x,var(--color-brand))]`) only when one is needed.

**Add theme tokens:**
- A non-default value used **2 or more times** for the same purpose becomes a token in `@theme`, named by its role, not its value. For example `--radius-card: 1.25rem` gives `rounded-card`, `--text-hero: clamp(3.75rem, 11vw, 9.375rem)` gives `text-hero`, and `--color-ink-soft` gives `text-ink-soft`.
- Use the correct namespace so a utility is generated: `--color-*`, `--font-*`, `--text-*` (with optional `--text-*--line-height`), `--radius-*`, `--shadow-*`, `--spacing-*`, `--container-*`, `--breakpoint-*`, `--ease-*`, `--animate-*`, `--tracking-*`, `--leading-*`.
- Don't add a token for a value that already matches a default; use the default.
- Repeated *combinations* (the same 4+ utilities appearing together in several places) become an `@utility`, but only when they represent one concept (`pill`, `section-title`). Don't merge unrelated classes.

**Units: rem, not px:**
- Write every length in rem (1rem = 16px): arbitrary values (`p-[1.125rem]`, not `p-[18px]`), theme tokens (`1.25rem`, not `20px`), `clamp()`/`calc()` bounds (`clamp(3.75rem, 11vw, 9.375rem)`) and lengths in custom CSS.
- Converting an existing px value to rem is a refactor, not a design change: it keeps the computed value. Use the exact quotient (15px → `0.9375rem`), never a rounded one. This relies on a 16px root; this project doesn't set `html { font-size }` today, so if that changes, stop and ask the user.
- Keep px for border, outline, ring and divider widths (`border-[3px]`, `outline-[1px]`), where the line shouldn't grow with text size.
- Breakpoints: `--breakpoint-*` tokens in rem, like the defaults (`md` = 48rem).

**Keep arbitrary values only when:**
- the value is used once and isn't in the scale,
- it's an inherently arbitrary value (`clamp()`, `calc()`, a grid template, an aspect ratio, a one-off transform or shadow),
- it's a selector variant (`[&_iframe]:size-full`).

Don't change values to make them fit the scale. Ask the user if a change would alter the design (for example 18px → 16px).

### Audit process

1. **Inventory.** List every arbitrary class across templates, components, JS/JSX and CSS (`grep -rhoE '\b[a-z-]+-\[[^]]+\]'`), grouped by property and value, with counts and file locations. Flag every px length (other than border/outline/ring widths) for conversion to rem.
2. **Classify** each one as: replace with a default, promote to a token, or keep as a one-off. Show this as a table and **wait for the user's approval** before editing.
3. **Apply** the changes. Where markup is duplicated (for example a server template and its editor/JSX copy), change both identically.
4. **Verify:** run the production build and the linter or formatter (see *This project*). Diff the compiled CSS before and after, or compare computed styles on key pages with screenshots at desktop and mobile widths. Report any visual difference.
5. **Summarise:** the tokens added, the number of classes replaced, the px values converted to rem, the arbitrary values kept with a one-line reason each, and any value that was *close to* a default but left unchanged because it didn't match exactly.
