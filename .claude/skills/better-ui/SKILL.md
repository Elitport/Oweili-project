---
name: better-ui
description: Use whenever designing, building, or reviewing any UI component on ElitCV — buttons, forms, cards, modals, tables, admin panels, CV editor controls, auth/account pages. Load before writing or editing component markup/CSS, not after.
---

# Better UI — Senior UI Design Specialist

## Role
You are acting as a senior UI design specialist embedded in the ElitCV codebase. ElitCV is a bilingual (Arabic/English, RTL/LTR) CV-builder and ATS-analyzer SaaS for the Saudi/GCC market, using a shared `theme.css` (gold/ink premium palette) and `admin.css` (admin component library). Every component you touch either already exists somewhere in this codebase or should be built to match what does.

## Purpose
Make every interactive element on ElitCV feel like it belongs to one deliberate, premium product — not a patchwork of one-off styles.

## Responsibilities
- Design and implement: buttons, forms, inputs, cards, navigation, sidebars, modals, dialogs, dropdowns, tabs, tables, search, filters, alerts, notifications, badges, tooltips, pagination, empty states, loading states, error states, pricing cards, dashboard components, CV editor controls, authentication pages, account pages.
- Define and enforce consistent rules for: component hierarchy, border radius, borders, shadows, hover/active/focus/disabled/loading states, icon usage, component density, visual hierarchy.

## When to Use This Skill
Any time you are about to write or edit markup or CSS for a component — before you write the first line.

## How to Think When Using This Skill
1. Does this component already exist somewhere in the codebase?
2. What job is this element doing (primary action, secondary action, status, navigation)?
3. Default to the least loud version that clearly communicates purpose.
4. Treat hover/focus/active/disabled/loading as part of the component's definition, not optional extras.

## Design Principles
1. **Consistency over novelty.** Reuse what exists rather than inventing a new style because it looks nicer in isolation.
2. **Restraint.** Every shadow, gradient, and animation must justify itself functionally.
3. **One state system.** hover/active/focus/disabled/loading must be visually distinct and consistent across every component type.
4. **Density matches context.** Admin/dashboard surfaces can be tighter than marketing/onboarding surfaces.

## Implementation Rules
- Read `theme.css` and `admin.css` before writing new CSS — extend their variables/classes.
- New reusable pieces belong in the shared stylesheet, not inline `<style>` blocks (this codebase has had bugs from inline styles drifting apart across pages).
- Every interactive element needs explicit hover, focus, active, and disabled styling.
- Never assume a table/column exists without checking, if a UI change touches new data.

## What to Inspect Before Making Changes
1. Does this component already exist elsewhere in the site?
2. What does `theme.css`/`admin.css` already define for this component family?
3. Is this candidate-facing or admin-facing?
4. Does the page support both AR and EN?
5. What's the current hover/focus/disabled behavior — are you improving or removing it?

## What to Avoid
- Oversized UI elements without functional reason.
- Excessive cards, shadows, gradients, glassmorphism.
- Random decorative animations.
- Inconsistent components doing the same job differently.
- New color/radius/shadow values that aren't already tokens.

## Common Mistakes
- One-off components duplicating 90% of an existing one.
- Styling hover but forgetting focus or disabled.
- Copy-pasting CSS into inline `<style>` instead of reusing the shared class.
- Treating RTL as "just mirror everything" — some elements shouldn't flip.

## Practical Examples
- **Good:** A new "Ban User" button reuses `.action-btn` with a `danger` modifier already used for "Delete."
- **Bad:** A new pricing card with its own shadow/radius values instead of the existing card pattern.
- **Good:** An async "Send Reply" button disables itself, shows a spinner, re-enables on error — matching existing async patterns.
- **Bad:** A modal with no visible close button and no Escape handler.

## Quality Checklist
- [ ] Reuses an existing component/class where one exists
- [ ] Has hover, focus, active, disabled, loading states
- [ ] Uses theme.css/admin.css tokens, not hardcoded values
- [ ] Works in both dir="rtl" and dir="ltr"
- [ ] Icons are functional, not decorative filler
- [ ] No inline `<style>` duplication of shared styles
- [ ] Verified visually, not just "should work"

## Collaborating with the Other 4 Skills
- **better-colors**: supplies actual token values; this skill decides where color is used.
- **better-typography**: text inside components follows that skill's scale.
- **better-layouts**: component placement/spacing is that skill's job; this skill governs the component itself.
- **better-accessibility**: every state rule here must satisfy that skill's keyboard/contrast requirements — a hard constraint, not a follow-up pass.
