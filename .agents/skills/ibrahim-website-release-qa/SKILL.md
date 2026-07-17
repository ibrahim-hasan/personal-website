---
name: ibrahim-website-release-qa
description: "Use before declaring any Ibrahim Website feature complete, production-ready, merged, committed, or deployed. Covers Laravel tests, bilingual routes and slugs, media conversions, SEO metadata and sitemap, reader/community persistence, Filament operations, responsive browser QA, asset builds, and dirty-worktree safety."
license: MIT
metadata:
  author: ibrahim-hasan
  scope: project
---

# Ibrahim Website Release QA

Completion requires evidence across code, data, browser behavior, and presentation.

## Start safely

1. Inspect the active repository and `git status` before edits or staging.
2. Preserve unrelated user changes in the dirty worktree.
3. Read `PRODUCT.md`, `DESIGN.md`, and the relevant project/domain skills.
4. Use Laravel Boost `search-docs` before Laravel ecosystem changes.

## Focused gates

### Backend and data

- Validate happy, failure, authorization, and edge paths with PHPUnit feature tests.
- Confirm migrations are reversible or intentionally documented forward fixes.
- Confirm comments, appreciations, bookmarks, progress, consultation requests, and account changes persist as intended.
- Verify rate limits, policies, privacy, and moderation state.

### Localization

- Resolve Arabic and English routes, including translated slugs and locale switching.
- Check missing-translation behavior, validation messages, mixed-direction content, dates, canonicals, and hreflang.
- Clear/rebuild route and config caches when they affect the path being tested.

### Media and SEO

- Verify Media Library collection, original, conversions, responsive output, alt text, and fallback.
- Check localized title/description, canonical, Open Graph, structured data, robots behavior, and sitemap inclusion/exclusion.
- Confirm analytics is production-only.

### Visual and interaction

- Run the matrix in `DESIGN.md` at mobile, intermediate, laptop, and wide widths.
- Scroll through reveals, sticky elements, dividers, patterns, media, menus, rails, footer, and go-to-top.
- Confirm keyboard focus and reduced-motion behavior.

### Build quality

- Run the smallest relevant `php artisan test --compact` target.
- Run `vendor/bin/pint --dirty --format agent` after PHP edits.
- Run the production frontend build after CSS, JS, Blade presentation, or asset changes.
- Do not claim the full suite passed unless it was actually run.

## Handoff format

Lead with the outcome, name the meaningful behavior changed, list the evidence actually run, and identify any remaining risk. Do not commit or push unless the user explicitly asks.

