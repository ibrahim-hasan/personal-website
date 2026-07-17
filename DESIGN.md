# Ibrahim Hasan — Design System and Experience Contract

This document is the canonical design reference for the public website and Filament admin. It explains the intent behind the system, the rules that preserve it, and the recurring defects that must not return. `PRODUCT.md` owns product intent; this file owns visual and interaction decisions.

## Design thesis

The visual language combines editorial authority, architectural precision, and controlled movement. It should feel Arabic-first, contemporary, confident, and unmistakably personal—never like a template, a résumé, or a generic technology landing page.

The desired emotional response is **controlled fascination**: an unusual composition or motion earns attention, while typography, hierarchy, and interaction continually reassure the visitor that the system is deliberate.

## Experience principles

1. **Clarity before spectacle.** A visitor should understand the argument before noticing the technique.
2. **One strong idea per section.** Avoid competing patterns, motions, CTAs, and typographic moments.
3. **Composition over decoration.** Use scale, negative space, rhythm, and alignment before adding ornaments.
4. **Arabic and English are separate compositions.** Preserve meaning and hierarchy, not mechanical mirroring.
5. **Motion explains state.** It may reveal, connect, transition, or confirm; it must not merely keep moving.
6. **Precision is visible in edge cases.** Narrow screens, long Arabic strings, empty states, media ratios, and focus states are part of the design.

## Brand character

- Exacting, calm, inventive, and technically mature.
- Confident without shouting; premium without ornamental luxury clichés.
- Human and editorial, but not nostalgic.
- Strategic technology rather than futuristic technology theatre.

## Color system

The implementation tokens in `resources/css/app.css` are the source of truth.

### Core surfaces

- `canvas`: soft cool gray-lavender — the primary neutral page surface.
- `canvas-bright`: near-white lavender — forms, reading surfaces, and raised editorial areas.
- `canvas-deep`: darker neutral lavender — structural contrast and selected secondary regions.
- The intentionally warmer pale-lavender `#ded3ea` belongs only to explicitly approved editorial surfaces. Do not globalize a local annotation into the shared canvas token.

### Ink

- `ink`: near-black violet for primary headings and body emphasis.
- `ink-soft`: long-form copy and secondary explanatory text.
- `ink-muted`: metadata and tertiary labels, only where contrast remains sufficient.

### Violet scale

- Violet is the signature action and connective color.
- Use deep violet for authoritative fields and light violet for contrast text or selected pale surfaces.
- Do not create new blue-purple gradients by default. Prefer flat fields, tonal transitions, pattern, and measured contrast.

### Semantic colors

- Success is reserved for real completion or approved state.
- Danger is reserved for destructive action and errors.
- Do not use semantic colors as decoration.

## Typography

### Public website

- UI: `Thmanyah Sans`.
- Editorial body: `Thmanyah Text`.
- Display: `Thmanyah Display`.
- Fallback: `Noto Sans`, then system sans.

Display typography is a core brand asset. It may be large and tightly composed, but Arabic descenders, dots, ligatures, and elongation must remain visible.

### Filament admin

- Preserve the approved admin font stack. Do not force the public display font across tables, forms, and dense operations.
- The brand wordmark may use its dedicated SVG; operational text must remain legible and consistent.

### Type rules

- Use the established `display-hero`, `display-page`, `display-section`, and `copy-lead` utilities.
- Favor `clamp()` for responsive scale and a readable maximum line length.
- Arabic display line-height can be compact, but never solve clipping by globally increasing line-height. Inspect overflow, transforms, fixed heights, masks, and font metrics first.
- Metadata must read as one secondary system—not a random sequence of equally loud labels.
- Avoid uppercase English microcopy inside Arabic compositions unless it is a brand name or genuinely useful technical label.

## Layout and rhythm

- Canonical content container: `site-container`, max width `86rem` with responsive inline padding.
- Sections use the established feature, standard, and compact vertical rhythm utilities.
- Large compositions may break the internal grid, but should not break the page container or create accidental horizontal scrolling.
- Prefer intentional asymmetry. Random width, height, and alignment differences are defects unless they reinforce a clear hierarchy.
- Dividers belong to the component they separate. They must not float away, disappear during reveal motion, or leave a gap between the rule and its row.

## Direction and localization

- Direction is semantic: Arabic `rtl`, English `ltr`.
- Arrows indicate a destination or movement; their meaning must not reverse on hover. Hover may translate the arrow slightly in the same logical direction.
- Dates, numerals, punctuation, and mixed Latin brand names should remain readable in bidi contexts.
- Mobile alignment should follow the locale, not collapse everything to the physical left.
- Locale switching must preserve the equivalent route and should not cause layout jumps.

## Pattern language

The geometric pattern is an architectural signature, not a generic background texture.

- Use the approved continuous geometric source; never approximate it with unrelated checkerboards or decorative diamonds.
- Best uses: deep-violet statement bands, selected CTA regions, method sections, controlled image framing, and low-contrast admin authentication context.
- Pattern opacity must protect text contrast.
- Avoid visible seams, cut motifs, mismatched background-size offsets, and separate layers that drift apart.
- When pattern frames an image, image and frame move as one composition—or remain still. Do not apply pointer parallax to only one layer.
- Do not use the pattern in every violet section. Repetition removes its significance.

## Imagery and media

- Art direction should harmonize real photography with the palette through crop, lighting, and restrained color treatment—not blanket filters that flatten skin tones or project screenshots.
- Portrait background replacement may use architectural cool neutrals, deep violet, soft lavender, and subtle pattern logic while preserving a natural, credible person.
- Clothing adjustments must remain realistic and professional; color changes should support the palette without looking generated.
- Project screenshots remain legible evidence. Do not tint them so heavily that their interface cannot be understood.
- Hero video is vertical media inside a controlled editorial frame. Its maximum size must remain balanced at desktop, tablet, and intermediate widths.
- Do not loop the hero video indefinitely. At completion, transition to a purposeful end state or replay choice without layout shift; respect reduced motion and user control.
- Posters and fallbacks are mandatory. Video must not block first content render.

## Logos and wordmarks

- Use named SVG assets for brand marks; do not rebuild logos with plain text.
- Logo boxes in the same system have identical external dimensions.
- Logos inside those boxes use consistent optical height and internal breathing room, not identical width.
- Select the correct light/dark asset for the surface.
- Never crop the Ibrahim wordmark. Preserve its long Arabic stroke and bottom glyph extents through a correct SVG viewBox and `object-fit: contain`.
- Project logos should be integrated into the card composition, not pasted over screenshots as an arbitrary floating sticker.

## Motion system

### Timing and easing

- Use the shared quart, quint, and expo easings from `:root`.
- Micro-interactions: roughly 140–240ms.
- Section and media reveals: roughly 420–700ms.
- Complex transitions may be longer only when they communicate a meaningful state change.

### Rules

- Scroll reveal may translate and fade content, but dividers and component boundaries must remain visually owned by their rows.
- Do not animate element dimensions when opacity or transform can communicate the state.
- Avoid continuous movement near reading copy. A slow media drift is acceptable only when subtle, bounded, and paused for reduced motion.
- Navigation border, shadow, and background transitions must begin and end as one coherent state—no first-scroll flicker.
- Success feedback should replace content inside the triggering control for about two seconds when possible, preserving control width and avoiding a new layout row.
- Always implement a complete `prefers-reduced-motion` path.

## Component contracts

### Navigation

- Desktop navigation balances wordmark, primary destinations, locale, consultation CTA, and reader account access.
- Mobile navigation is a dedicated full-screen composition with one scrollbar, no duplicated descriptive label, and no native nested scrollbar.
- Account state should make library, profile, and deletion controls discoverable without crowding the primary navigation.

### Buttons and links

- Primary button: solid violet, high-contrast text, clear focus, no unexplained icon tile.
- Secondary button: quiet outline or pale surface with equivalent height.
- Text links: editorial underline and directional arrow with consistent spacing.
- Icon-only controls require labels and a minimum 44px target.
- Maintain stable dimensions across default, hover, focus, loading, success, and disabled states.

### Editorial rows

- Metadata is grouped and secondary; title is the primary scan target; summary provides context; arrow communicates entry.
- Use consistent inline padding and an owned separator on every viewport.
- Reveal motion may return, but it must transform the row as one unit so its separator never appears detached.
- Long titles must wrap without colliding with the arrow, index, or metadata.

### Topic/filter rails

- Horizontal topic lists use aligned previous/next controls when content overflows.
- Chips and arrow buttons share one vertical center and touch-target height.
- Disabled navigation is visibly disabled but still aligned.
- Scrolling should be smooth, locale-aware, and free from native ugly overflow bars.

### Cards and case studies

- Cards are not a universal default. Use them where grouping, comparison, or state genuinely benefits.
- Case studies should prioritize evidence: image, project identity, narrative, and outcome.
- Media and copy heights may differ only through a deliberate editorial rhythm; unexplained empty space is a defect.
- Brand marks, tags, and numbers should support scanning without competing with the project title.

### Article pages

- The hero prioritizes title and essential metadata; imagery must not contain baked-in caption text.
- Sharing belongs in a compact dedicated rail after the hero media or at another clear transition—not inside the title hierarchy.
- Reading mode is one calm control; excessive rules above and below it are unnecessary.
- Article contents must not duplicate numbering already included in a heading.
- Avoid adjacent consultation sections with the same purpose.

### Footer

- Maintain logical locale alignment and a responsive hierarchy on narrow screens.
- The utility row should not collapse to the physical left in Arabic.
- Social labels should be meaningful; decorative numbering is omitted unless it communicates an actual system.

### Filament admin

- Dark navigation and light/dark content surfaces should feel like the same brand, not a public-site clone.
- Sidebar groups, selected states, dashboard cards, tables, forms, upload fields, auth shell, and empty states must be designed as one system.
- Empty states explain what is absent and, where authorized, the useful next action.
- Table density must remain usable on narrow admin widths; avoid broken wrapping that produces one word per line.
- The correct wordmark asset must remain visible on both light and dark topbars.

## Responsive system

- Design from content behavior rather than device names.
- Mandatory checks: 320–390px mobile, approximately 740–835px narrow tablet/intermediate, common laptop, and wide desktop.
- Intermediate widths are first-class. Do not jump from a large two-column hero to a full-width vertical video without a constrained middle state.
- Replace fixed heights with intrinsic sizing unless the fixed frame is a tested design requirement.
- Prevent horizontal overflow at the owner: grid min-width, transformed child, long token, media frame, or absolute decoration.
- Controls may wrap only when the wrap remains intentional; otherwise use an overflow rail.

## Accessibility

- WCAG 2.2 AA contrast target.
- Visible `:focus-visible` treatment on every interactive component.
- Semantic heading order and landmarks.
- Descriptive localized alt text for informative media; empty alt for decorative assets.
- Keyboard-accessible menu, filters, media controls, comments, and account actions.
- Motion, autoplay, and video controls respect user preferences.
- Never rely on color alone for selected, error, or success state.

## Recurring defects to prevent

- Arabic text clipped at the bottom or masked by a fixed-height parent.
- A local color annotation accidentally changing every shared canvas surface.
- Dividers separating from content during scroll reveal.
- Hover animation reversing an RTL arrow.
- Pattern seams, cuts, or parallax between image and frame.
- Unequal logo boxes or optical logo heights.
- Oversized portrait/video at intermediate widths.
- Duplicate CTAs directly adjacent to each other.
- Native scrollbars inside mobile menu or horizontal rails.
- Metadata with weak hierarchy or random spacing.
- Mobile footer content aligned to the physical left in Arabic.
- White or invisible brand assets on the wrong admin surface.
- Success messages adding a new row and shifting layout.
- Image path text inputs replacing managed media uploads.

## Visual QA protocol

For any frontend change:

1. Identify the owning token or component; scope local annotations locally.
2. Inspect Arabic and English if the component is localized.
3. Verify default, hover, focus, active, disabled, loading, success, empty, and error states as relevant.
4. Check mobile, intermediate, laptop, and wide layouts.
5. Scroll through the component to catch sticky, reveal, divider, pattern, and overflow defects.
6. Test keyboard navigation and reduced motion.
7. Compare the result with this document and the user's annotated evidence.
8. Run the focused automated tests and production asset build appropriate to the change.

## Change discipline

- Prefer the smallest owning change that fixes the whole class of defect.
- Do not globalize a one-off visual request without explicit direction.
- Preserve deliberate motion when fixing its implementation; do not remove an interaction merely because its first version is buggy.
- Do not replace specific brand choices with generic “clean UI” conventions.
- When a new pattern becomes recurring, update this document so future work starts from the decision instead of rediscovering it.
