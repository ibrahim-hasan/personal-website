---
name: ibrahim-website-product
description: "Use for any feature, copy, scope, prioritization, information architecture, user journey, admin workflow, reader community, consultation, SEO, localization, or product decision in Ibrahim Hasan's personal website. Activate whenever work changes what the product does, who it serves, or how a visitor, reader, or administrator completes a task."
license: MIT
metadata:
  author: ibrahim-hasan
  scope: project
---

# Ibrahim Website Product

Protect the product intent encoded in the repository's `PRODUCT.md`.

## Required context

Before making a product-affecting change:

1. Read `PRODUCT.md` completely.
2. Read the relevant current route, model, view/component, translation, and focused tests.
3. Treat browser annotations as evidence about a specific surface, not permission to globalize the change.
4. Distinguish intended product behavior from current implementation status.

## Decision method

Frame the request as:

- **Audience:** visitor, qualified lead, reader, authenticated member, moderator, or administrator.
- **Job:** the concrete decision or task they need to complete.
- **Evidence:** what proves the design or workflow is useful and truthful.
- **Risk:** privacy, persistence, localization, discoverability, accessibility, or operational failure.
- **Success:** an observable outcome, not a visual preference alone.

Use the product hierarchy: truth and integrity → comprehension → accessibility/responsiveness → performance/maintainability → distinction and delight.

## Product invariants

- Arabic-first and genuinely bilingual.
- Consultation context is preserved between discovery and submission.
- Writing is a complete reader product: discover, read/listen, save, appreciate, discuss, share, and manage the account.
- Public proof is specific and real; never fabricate metrics, testimonials, or relationships.
- Admin quality, migrations, media, permissions, and moderation are product quality.
- AI must have a bounded user purpose, review path, and measurable benefit.

## Output expectations

When implementing, include the full journey and its edge states. When reviewing, explain the product consequence and recommend the smallest owning change. When a durable product decision changes, update `PRODUCT.md` in the same task.

