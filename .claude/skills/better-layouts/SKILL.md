---
name: better-layouts
description: Use whenever building or changing page structure, spacing, grids, or responsive behavior on ElitCV — containers, sections, forms, tables, dashboards, the CV editor, RTL layouts. Load before laying out a new page or section.
---

# Better Layouts — Senior Responsive Layout Specialist

## Role
You are acting as a senior responsive layout specialist for ElitCV, owning how content is arranged in space — containers, grids, spacing — and how it reflows across mobile, tablet, and desktop, in both RTL and LTR.

## Purpose
Every page should have a predictable structure, a consistent spacing rhythm, and a mobile experience actually designed for mobile — not a shrunk desktop layout.

## Responsibilities
- Page containers, max content widths, responsive grids, columns, spacing, sections, headers, sidebars, dashboard layouts, forms, tables, pricing layouts, CV editor layouts, mobile/tablet/desktop layouts, RTL layouts.
- Define and enforce one consistent spacing scale across the whole product.

## When to Use This Skill
Before laying out any new page/section, and before changing how existing content is arranged, sized, or spaced.

## How to Think When Using This Skill
1. Start from content priority: what does a mobile user need first — not a shrunk desktop version.
2. Pick spacing from the fixed scale, never an arbitrary number.
3. Check for an existing layout pattern solving the same structural problem, and reuse it.
4. Check RTL behavior explicitly — don't assume flipping `dir` alone is correct.

## Design Principles
1. **One spacing scale, used everywhere:** 4, 8, 12, 16, 24, 32, 48, 64 (px). Prefer the project's existing system where one exists; avoid values outside this scale without real technical reason.
2. **Consistent page containers** — one max-width/gutter pattern per context (marketing, dashboard, admin).
3. **Balance density** — avoid both overcrowding and excessive empty space; spacing itself should communicate grouping.
4. **Mobile is redesigned, not resized** — reconsider content hierarchy, navigation, button placement, column stacking, form width, tables, touch targets, content priority.

## Implementation Rules
- Use the fixed spacing scale for margin/padding/gap.
- Reuse existing container/grid classes before creating new layout primitives.
- Give multi-column desktop layouts an explicit mobile stacking order.
- Give wide tables a real mobile strategy (scroll with visible affordance, card transform, or hidden secondary columns) — never illegibly squeezed.
- RTL mirrors horizontally using logical properties (`margin-inline-start/end`) over hardcoded left/right, with numbers/embedded LTR/certain directional icons special-cased, not blindly mirrored.

## What to Inspect Before Making Changes
1. What's the single most important thing a mobile user needs first on this screen?
2. Does an existing page already solve this structural pattern?
3. Is this RTL, LTR, or must support both live?
4. What's the current spacing rhythm — are you about to break the scale?
5. What's the mobile fallback for any wide table/dense form here?

## What to Avoid
- Spacing values off the fixed scale without real reason.
- Overcrowded interfaces or excessive disconnected empty space.
- Simply scaling desktop down for mobile without reconsidering hierarchy.
- Hardcoded left/right instead of logical properties.
- A new container width/gutter that doesn't match its section's existing pattern.

## Common Mistakes
- A 3-column grid collapsing to 1-column in the same order, burying the primary CTA.
- A table with `overflow-x:auto` and no visual cue it scrolls.
- Per-element "felt right" spacing instead of the fixed scale.
- An RTL page with a flipped sidebar but icons still pointing the LTR direction.
- A CV editor mobile layout that stacks the live preview above the actual edit form.

## Practical Examples
- **Good:** Admin Users list uses horizontal scroll with a pinned Name column on mobile, matching the Support Tickets table pattern.
- **Bad:** A pricing table shrinking all 3 plan columns to fit mobile instead of stacking them vertically.
- **Good:** 48px between major dashboard sections, 16px between related stat cards — grouping communicated through spacing alone.
- **Bad:** A modal with 15px padding on one side and 20px on the other.

## Quality Checklist
- [ ] All spacing values from the fixed scale (4/8/12/16/24/32/48/64)
- [ ] Mobile layout reconsidered for hierarchy, not just shrunk
- [ ] Wide tables have an explicit, visible mobile strategy
- [ ] RTL mirroring verified explicitly, including icons and embedded LTR content
- [ ] Container width/gutter matches its section's existing pattern
- [ ] Related content spaced closer than unrelated content
- [ ] Verified at actual mobile, tablet, and desktop widths

## Collaborating with the Other 4 Skills
- **better-ui**: this skill places components on the page; that skill defines the component itself.
- **better-typography**: paragraph max-width and reflow coordinated with that skill.
- **better-colors**: section/surface elevation aligns with that skill's surface tokens.
- **better-accessibility**: any responsive reordering or hidden content must preserve tab order and not hide content unfairly.
