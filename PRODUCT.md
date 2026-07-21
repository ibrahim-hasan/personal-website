# Ibrahim Hasan — Product Contract

This document defines what the product is, who it serves, and the standard every feature must meet. It is an intent and decision document, not an implementation-status report. Verify the current code and tests before claiming that a capability is complete.

## Product in one sentence

An Arabic-first, bilingual personal platform that turns Ibrahim Hasan's strategic technology practice, selected work, and writing into a credible path from discovery to consultation and long-term reader engagement.

## Positioning

Ibrahim connects business understanding, solution architecture, disciplined delivery, and practical AI in one precise path: from a difficult question, to a reliable system, to measurable impact.

The site must feel like the work itself: thoughtful before technical, rigorous without being cold, inventive without becoming theatrical, and commercially useful without sounding promotional.

## Audiences

### Primary

- Business owners, executives, and decision-makers in Saudi Arabia and the GCC.
- Leaders facing an operational, software, AI, data, or digital-transformation decision.
- Organizations that need senior judgment before committing to a tool, vendor, or implementation.

### Secondary

- Product teams, agencies, and technical leaders seeking an experienced delivery partner.
- Readers who value grounded writing about technology, operations, governance, and decision-making.
- Prospective collaborators evaluating Ibrahim's way of thinking and quality of execution.

## Jobs to be done

Visitors should be able to:

1. Understand what Ibrahim does and how he thinks within the first meaningful screen.
2. See evidence through real work, specific methods, and concrete writing—not invented metrics or generic claims.
3. Determine whether their problem fits the practice.
4. Move from context to a consultation request without losing what they already explained.
5. Read, listen to, save, appreciate, discuss, and share useful articles.
6. Create and control a reader account, including profile, password, library, and account deletion.

Administrators should be able to:

1. Manage bilingual projects, services, articles, audio, media, and site settings.
2. Publish localized content with stable, locale-aware URLs and metadata.
3. Moderate comments and reports without exposing unnecessary personal data.
4. Review consultation requests and reader activity in a focused, branded Filament workspace.
5. Trust that uploads, conversions, validations, permissions, and database migrations follow one consistent architecture.

## Product pillars

### 1. Strategic clarity

Start with the operation, decision, data, ownership, and risk. Technology appears as a consequence of understanding—not as the opening pitch.

### 2. Evidence over performance

Use real projects, real operating patterns, clear trade-offs, and specific writing. Never invent testimonials, client relationships, outcomes, or vanity metrics.

### 3. Editorial utility

Writing is a product, not a blog appendix. Articles must support focused reading, audio, navigation, saving, appreciation, moderated discussion, sharing, and discovery.

### 4. Arabic-first bilingual quality

Arabic is the primary composition. English is an equally complete experience, not a literal mirror. Copy, direction, typography, dates, slugs, metadata, and routes must be correct per locale.

### 5. Controlled creative distinction

The experience should be memorable through typography, composition, pattern, motion, and interaction—but never at the cost of comprehension, responsiveness, accessibility, or speed.

### 6. Operational integrity

The admin, database, media model, permissions, migrations, tests, and deployment behavior are part of the product quality. A polished public surface does not compensate for fragile operations.

## Core journeys

### Qualified visitor → consultation

Homepage or service discovery → method and proof → decision-room context → consultation form with context preserved → clear success state and follow-up expectation.

### Reader → engaged member

Writing discovery → topic filtering → article → read or listen → appreciate, save, comment, or share → optional account creation → personal reading library and account controls.

### Administrator → safe publication

Sign in → create or edit localized content → upload managed media → review preview and metadata → publish → verify public localized route, sitemap presence, and presentation → moderate later engagement.

## Functional scope

### Public website

- Home, services, selected work, writing library, article pages, about, and consultation entry points.
- Responsive navigation, language switching, mobile menu, footer, and go-to-top behavior.
- Purposeful motion with reduced-motion support.

### Editorial community

- Articles with localized titles, summaries, content, slugs, topics, dates, reading time, and SEO metadata.
- Audio narration and an accessible audio player where available.
- Appreciations, bookmarks/reading list, reading progress, comments, replies, and reports.
- Reader authentication, verified email where required, account settings, password change, and account deletion.
- Native sharing and social fallbacks with correct Open Graph metadata.

### Portfolio and services

- Localized projects and services with meaningful categorization and narrative fields.
- Consistent project media and logos, not manually entered public paths.
- Real case-study structure: context, challenge, change, impact, and relevant tags.

### Administration

- A branded Filament panel that retains its approved admin font and uses the website palette deliberately.
- Focused resources for projects, services, articles, audio, comments/reports, consultation requests, users, roles, and settings.
- Helpful empty states, clear validations, consistent tables, appropriate permissions, and no unrelated inherited product concepts.

### Platform capabilities

- Laravel Boost for version-aware development support and Laravel AI only where it creates a controlled, reviewable workflow.
- Localized routes with `mcamara/laravel-localization`.
- Translatable fields with `spatie/laravel-translatable`.
- Translatable, stable slugs with `spatie/laravel-sluggable` and locale-aware route resolution.
- Dynamic media through Spatie Media Library and its Filament integration.
- Sitemap generation, canonical URLs, hreflang, metadata, structured data, and production-only analytics.

## Content and data contract

### Localization

- Store bilingual editorial fields as translations where the domain requires both languages.
- Slugs are translated and must resolve only in the active locale, with safe redirects when a previous slug is intentionally preserved.
- Locale switches should take the visitor to the equivalent content, not a generic homepage fallback when an equivalent exists.
- Never infer a missing translation silently. Use an intentional fallback or a clear unpublished state.

### Media

- All dynamic images use Spatie Media Library collections and Filament media upload fields.
- Define named conversions by use case: card/thumbnail, hero, Open Graph, and logo where applicable.
- Preserve the original; produce modern responsive derivatives; use meaningful alt text per locale.
- Logos use consistent containers and optical sizing. Do not stretch, crop, or normalize by width alone.
- Public-path text fields are legacy behavior and must not be reintroduced.

### Community and privacy

- Comments must persist reliably, validate in the active locale, and enter the intended moderation state.
- Likes/appreciations, bookmarks, and progress must be idempotent per authenticated user.
- Account deletion must clearly explain consequences and safely remove or anonymize related personal data according to policy.
- Rate-limit and authorize write actions. Do not expose email addresses or private moderation data publicly.

## Conversion and proof

- Primary CTA: request a free consultation.
- Secondary CTA: explore the method, selected work, or relevant writing.
- Consultation CTAs should not be duplicated in adjacent sections without a distinct purpose.
- Decision-room output must carry its selected context into the consultation flow.
- Proof should come from project narratives, operating detail, and thoughtful content. Public client logos, testimonials, and metrics require explicit, truthful source material.

## SEO, discoverability, and performance

- Every indexable page needs a unique localized title, description, canonical URL, and hreflang set.
- Articles need Open Graph/Twitter metadata, share-ready imagery, `Article` structured data, and useful internal links.
- The organization/person/navigation structure should make major sections eligible for search sitelinks, while recognizing that Google decides whether to display them.
- XML sitemaps must include localized canonical content and exclude private, duplicate, utility, and admin pages.
- Analytics load only in production and must not block content.
- Images and video must be correctly sized, compressed, lazy-loaded when below the fold, and accompanied by stable dimensions to prevent layout shift.
- Protect Core Web Vitals: avoid autoplay audio, oversized hero media, render-blocking extras, and motion that causes reflow.

## Accessibility and inclusion

- Target WCAG 2.2 AA contrast, keyboard operation, visible focus, semantic landmarks, and descriptive labels.
- Maintain at least 44px touch targets for primary controls.
- Support 320px layouts, zoom, RTL/LTR, screen readers, and `prefers-reduced-motion`.
- Essential content must remain available without JavaScript or animation.
- Arabic glyphs must never be clipped. Fix the owning box, overflow, transform, font metric, or composition—not line-height blindly.

## Product voice

- Confident, exact, calm, and useful.
- Technically fluent without unnecessary jargon.
- No hype, fear-based AI language, inflated certainty, or generic transformation slogans.
- Prefer a clear claim supported by a mechanism or example.
- Arabic copy should read as authored Arabic; English copy should read as authored English.

## Non-goals

- A generic purple SaaS landing page.
- A résumé or chronological career archive.
- A wall of interchangeable cards, icons, or decorative labels.
- A public social network or engagement system optimized for volume.
- AI features without a specific user decision, review path, and measurable benefit.
- Fake social proof, decorative dashboards, or metrics without evidence.

## Decision hierarchy

When requirements conflict, decide in this order:

1. Truth, privacy, and data integrity.
2. Comprehension and task completion.
3. Accessibility and responsive behavior.
4. Performance and maintainability.
5. Brand distinction and delight.

Creative execution is expected, but it must survive the first four constraints.

## Definition of done

A change is not complete until:

- The Arabic and English experiences are both correct where the feature is localized.
- Desktop, tablet, narrow mobile, RTL, and LTR states have been checked as relevant.
- Empty, loading, error, success, hover, focus, disabled, and long-content states are considered.
- Backend validation, authorization, persistence, migrations, and media behavior are tested where applicable.
- Focused automated tests pass; PHP is formatted when changed; frontend assets build when changed.
- The live local page is visually verified when presentation or interaction changes.
- No unrelated user work in the dirty worktree is overwritten.
