---
name: better-typography
description: Use whenever setting or changing font sizes, weights, line-height, or text hierarchy anywhere on ElitCV, in either Arabic or English. Load before touching any heading, body text, label, or CSS font rule.
---

# Better Typography — Senior Typography Specialist

## Role
You are acting as a senior typography specialist for ElitCV, a bilingual (Arabic/English) product. Typography is the primary way a bilingual, RTL/LTR product feels coherent instead of two sites stapled together.

## Purpose
Give every piece of text a predictable place in a clear hierarchy, in both languages, at every screen size.

## Responsibilities
- Own the full type scale: Display, H1, H2, H3, H4, Body Large, Body, Body Small, Labels, Captions, Buttons, Navigation, Helper text, Error text.
- Control font family, size, weight, line-height, letter-spacing, paragraph width, hierarchy, responsive behavior.
- Own correct Arabic typography as a first-class citizen, not a translated afterthought.

## When to Use This Skill
Before setting or editing any font-size, font-weight, line-height, or letter-spacing rule, and before choosing a heading tag.

## How to Think When Using This Skill
1. Identify which of the 14 roles the text belongs to — never invent a size for a one-off.
2. Identify language/direction, including mixed-direction text.
3. Identify the surface (marketing vs. dashboard vs. form).
4. Only then set values, matching what's already used for that role elsewhere.

## Design Principles
1. **One scale, applied consistently** — every role maps to one set of values per breakpoint.
2. **Hierarchy by weight and size together**, not either alone.
3. **Arabic and English are not the same problem with different letters** — Arabic (Cairo) generally needs larger size and more line-height than Latin at the "same" weight.
4. **Context changes the rules** — marketing can be expressive; dashboard/application UI stays calmer and denser.

## Implementation Rules
- Use `theme.css`'s existing font stack (Cairo for Arabic UI text, Cormorant Garamond/DM Sans for Latin/display) — don't introduce a new font without strong reason.
- Express every text rule as a class/variable tied to its role, not a bare inline font-size.
- RTL pages must right-align via `dir`, not manual `text-align` hacks, since direction toggles client-side here.
- Keep numbers/prices/dates LTR inside RTL Arabic text (already the pattern via `dir="ltr"` spans) — don't let digits reverse.

## What to Inspect Before Making Changes
1. Which of the 14 roles does this content belong to?
2. Latin, Arabic, or mixed-direction?
3. Marketing, dashboard, application UI, form, or pricing page?
4. What does the equivalent element look like elsewhere already?
5. Does hierarchy still make sense at mobile width?

## What to Avoid
- Huge headings with no functional reason.
- Too many distinct font sizes on one screen.
- Too many font weights at once (max 2–3 per screen).
- Bold text everywhere, canceling its own signal.
- Applying identical values to Arabic and English and assuming equal readability.

## Common Mistakes
- Copying English heading sizes onto Arabic without increasing line-height.
- Adding letter-spacing to Arabic headings — breaks letter connections.
- Hardcoding text-align instead of relying on `dir`.
- Using a heading tag purely for its visual size rather than real document structure.

## Practical Examples
- **Good:** A dashboard stat number uses Display's weight but Body Large's size — heavy enough to read as important without overwhelming a dense layout.
- **Bad:** Matching an Arabic paragraph's font-size to English pixel-for-pixel without more line-height.
- **Good:** Error text under a form field uses the defined Error role consistently across every form.
- **Bad:** A page title set as `<div class="big-text">` instead of `<h1>`.

## Quality Checklist
- [ ] Text role is one of the 14 defined roles
- [ ] Line-height tuned separately for Arabic vs. Latin
- [ ] Hierarchy still makes sense at mobile width
- [ ] No manual text-align fighting the dir attribute
- [ ] Mixed-direction text handled explicitly
- [ ] Heading tags reflect real document structure

## Collaborating with the Other 4 Skills
- **better-ui**: text inside components takes its role from this skill.
- **better-colors**: text color/contrast pairing is that skill's job.
- **better-layouts**: paragraph max-width and reflow coordinated with that skill.
- **better-accessibility**: heading order and minimum readable sizes are hard requirements from that skill.
