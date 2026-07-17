---
name: ibrahim-website-design
description: "Use for every public-site or Filament visual, responsive, typography, motion, pattern, media, logo, navigation, card, article, table, form, empty-state, or interaction change in Ibrahim Hasan's website. Activate for design critique, browser annotations, visual QA, and any request involving clipping, spacing, alignment, overflow, RTL/LTR, or breakpoints."
license: MIT
metadata:
  author: ibrahim-hasan
  scope: project
---

# Ibrahim Website Design

Apply the design contract in `DESIGN.md` and the product constraints in `PRODUCT.md`.

## Required sequence

1. Read `DESIGN.md` completely and the relevant section of `PRODUCT.md`.
2. Inspect the owning CSS token, utility, component, and script before editing.
3. Review sibling components so the fix joins the system instead of creating a variant.
4. Scope annotated color or layout changes to the exact surface unless the user explicitly asks for a shared-token change.
5. Preserve a deliberate interaction while fixing it; do not remove motion as a shortcut.

## Non-negotiables

- Public typography uses Thmanyah families; approved admin typography stays intact.
- Arabic glyph clipping is solved at the owning box/overflow/transform—not with blind global line-height.
- Pattern remains continuous, restrained, and structurally meaningful.
- Arrows retain logical direction on hover.
- Logo boxes and optical logo heights are consistent.
- Intermediate widths receive an intentional layout, especially hero video and editorial grids.
- Dividers remain attached to their component during reveal motion.
- Native nested scrollbars, horizontal overflow, duplicate adjacent CTAs, and layout-shifting success messages are defects.

## Verification matrix

Check as relevant:

- Arabic RTL and English LTR.
- 320–390px, 740–835px, laptop, and wide desktop.
- Default, hover, focus-visible, active, disabled, loading, success, empty, and error.
- Keyboard, zoom, reduced motion, long text, missing media, and slow-loading media.
- Scroll behavior: sticky navigation, reveal, dividers, patterns, rails, and mobile menu.

Use the in-app browser for final visual truth when available. Run focused tests and the production asset build for implemented changes.

