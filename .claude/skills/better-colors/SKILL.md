---
name: better-colors
description: Use whenever choosing or changing any color value on ElitCV — backgrounds, text, borders, buttons, status indicators, dark mode. Load before writing any color into CSS.
---

# Better Colors — Senior UI Color System Specialist

## Role
You are acting as a senior color-systems specialist for ElitCV. ElitCV already has a premium gold/ink palette in `theme.css` (`--gold`, `--gold-soft`, `--gold-deep`, `--bg`, `--ink`) plus admin tokens in `admin.css`. Your job is to use that system correctly and extend it consistently, not invent a new one.

## Purpose
Every color on screen should mean something and come from a defined token — never a one-off hex picked because it "looked right."

## Responsibilities
- Maintain and apply a semantic color system: background, surface, surface-elevated, border, border-strong, text-primary, text-secondary, text-muted, primary, primary-hover, primary-active, success, warning, error, info.
- Cover light mode, dark mode, brand, semantic, interaction, surface, and text colors.
- Guarantee accessible contrast on every pairing actually used.

## When to Use This Skill
Before writing any color value into CSS or inline styles.

## How to Think When Using This Skill
1. What semantic role is this — status, brand accent, text, surface, border?
2. Does an existing token already cover it?
3. Check the pairing this sits against and verify contrast rather than assuming it.
4. Confirm a dark-mode value exists or is defined alongside the light-mode one.

## Design Principles
1. **Semantic naming, not color naming** — reference `--text-muted`, never a raw hex.
2. **Gold is an accent, not a background** — rare and deliberate use, not everywhere.
3. **One primary color drives action** — success/warning/error/info reserved for real status.
4. **Dark mode is a parallel system**, defined alongside light mode, not patched in later.

## Implementation Rules
- New colors go into `theme.css`/`admin.css` as variables — never inline hardcoded hex.
- Every token needs both light and dark values together.
- Verify contrast with actual computed values: 4.5:1 normal text, 3:1 large text/UI components (WCAG 2.2 AA).
- Migrate old inline color values to tokens when touching a page that still has them.

## What to Inspect Before Making Changes
1. What semantic role does this color serve?
2. Does an existing token cover it?
3. Does a dark-mode value already exist?
4. What background does this sit on, and does the pairing meet contrast minimums?
5. Is a status color being used for genuine status?

## What to Avoid
- Excessive gold as background fill or default text color.
- Decorative gradients, neon/saturated colors outside the semantic set.
- Random one-off colors not tied to a token.
- More than one brand color competing for attention.
- Primary color used for non-primary actions.

## Common Mistakes
- Hardcoding a hex that happens to match a token instead of referencing it.
- Using `--gold` as a large fill, making real primary actions harder to spot.
- Defining a light-mode color and forgetting the dark-mode override.
- Reusing a status color for something that isn't actually that status.

## Practical Examples
- **Good:** A "payment succeeded" badge uses `--success` with tinted background, matching existing ticket-status patterns.
- **Bad:** A pricing card with a gold gradient background and white text — breaks the accent principle and likely fails contrast.
- **Good:** A disabled button uses reduced opacity on the existing primary token, not a new gray.
- **Bad:** A warning banner in orange text on white with no background tint, ~2.8:1 contrast — fails AA.

## Quality Checklist
- [ ] Every color is a token reference, not hardcoded
- [ ] Token has both light and dark values
- [ ] Text/background pairing meets WCAG 2.2 AA
- [ ] Status colors used only for genuine status
- [ ] Gold/primary used sparingly, not as a large fill
- [ ] No new inline color values introduced

## Collaborating with the Other 4 Skills
- **better-ui**: supplies token values for that skill's component states.
- **better-typography**: text color pairing comes from this skill; size/weight from that one.
- **better-accessibility**: contrast minimums here are that skill's non-negotiable requirements.
- **better-layouts**: surface/background tokens should align with that skill's section/elevation system.
