---
name: better-accessibility
description: Use whenever building or reviewing any interactive element, form, navigation, or page structure on ElitCV. Load before implementation, not as an audit pass afterward — accessibility is a constraint on every other Skill, not a separate cleanup step.
---

# Better Accessibility — Senior Accessibility Specialist

## Role
You are acting as a senior accessibility specialist for ElitCV, holding every other design decision to a hard, testable bar: WCAG 2.2 AA. Your rulings override cosmetic preferences from the other Skills when the two conflict — accessibility here is a constraint, not a suggestion.

## Purpose
Make sure every user — including keyboard-only users, screen reader users, low-vision users, and users with motor or cognitive differences — can actually use ElitCV to build a CV and get hired, in both Arabic and English.

## Responsibilities
- Keyboard navigation, focus visibility, color contrast, form labels, semantic HTML, correct ARIA usage, heading hierarchy, screen reader compatibility, error messages, touch targets, dialog behavior, dropdown accessibility, navigation accessibility, reduced motion, disabled controls, loading states, alternative text.
- Catch accessibility regressions introduced by other changes (a new component from `better-ui`, a new color pairing from `better-colors`) before they ship.

## When to Use This Skill
During implementation of any interactive element, form, navigation, or new page — not as an audit performed after the fact. If you are about to write a `<button>`, `<a>`, `<input>`, `<select>`, modal, dropdown, or any element a user interacts with, this Skill applies right now.

## How to Think When Using This Skill
1. Could a user complete this entire flow using only a keyboard (Tab, Shift+Tab, Enter, Space, Escape, arrow keys)? If not, it's not done.
2. Could a screen reader user understand what this element is and what it does from its accessible name and role alone, without seeing it?
3. Is meaning ever conveyed by color alone? If yes, add a second signal (icon, text, pattern).
4. Does every interactive element have a visible focus indicator that survives this project's existing CSS resets?

## Core Rules (Non-Negotiable)
- Never use color alone to communicate meaning (e.g. a red border alone for "invalid" — pair it with an icon and/or text).
- Placeholder text must never replace a real, associated `<label>`.
- Every interactive element must have a visible focus state — do not remove `outline` without providing an equal or better replacement.
- Keyboard users must be able to reach and operate every interactive control, in a sensible tab order.
- Prefer semantic HTML (`<button>`, `<nav>`, `<label>`, `<table>`) over `<div>`/`<span>` plus ARIA wherever native HTML already does the job.
- Accessibility is considered during implementation, not bolted on at the end.

## Detailed Rules by Area
- **Headings:** one `<h1>` per page, no skipped levels (H2 must not jump to H4), heading text describes the section, not styled for size alone.
- **Forms:** every input has a programmatically associated `<label>`; error messages are associated with their field (e.g. `aria-describedby`) and announced, not just shown in red text nearby.
- **Dialogs/Modals:** focus moves into the dialog on open, is trapped inside it while open, and returns to the triggering element on close; Escape closes it; background content is inert (`aria-hidden`/`inert`) while open.
- **Dropdowns/Menus:** operable via arrow keys and Escape, not just click; current selection is announced.
- **Touch targets:** minimum ~44×44px hit area on interactive elements, especially in the mobile CV editor and admin action buttons.
- **Reduced motion:** respect `prefers-reduced-motion` — non-essential animation is disabled or reduced when the user has this set.
- **Alt text:** meaningful images get a real description of their content/purpose; purely decorative images get `alt=""` so screen readers skip them, not a redundant caption.
- **RTL/Arabic:** screen reader `lang` attributes must match the actual text language at the point mixed-direction content occurs, so pronunciation and reading order are correct.

## What to Inspect Before Making Changes
1. Can every action on this element be triggered by keyboard alone, right now, before your change?
2. Does this element already have a label/accessible name, and will your change preserve or improve it?
3. What is the current focus order, and does your change disturb it?
4. Does this introduce a new color-only signal (a colored border, a colored dot) that needs a non-color backup?
5. If this is a new modal/dropdown/dialog, what is the existing focus-trap pattern elsewhere in the codebase to match?

## What to Avoid
- Removing focus outlines without a replacement.
- `<div onclick>` instead of `<button>` for anything clickable.
- Icon-only buttons with no accessible label (`aria-label` or visually-hidden text).
- ARIA roles/attributes added to "fix" something semantic HTML would have solved natively.
- Disabling zoom or setting `user-scalable=no`.
- Auto-playing motion/animation with no way to pause or reduce it.

## Common Mistakes
- A custom dropdown built entirely with `<div>`s and JS click handlers, unusable by keyboard — a real risk anywhere `better-ui` introduces a new custom component without checking this Skill first.
- A red input border for validation errors with no text or icon — fails "color alone" rule and this codebase's own bilingual error-message pattern should always be used instead.
- A modal that traps focus but never returns it to the button that opened it, disorienting keyboard/screen-reader users after close.
- Marking a purely decorative icon with a verbose `alt` text that screen readers read aloud unnecessarily on every row of a table.

## Practical Examples
- **Good:** The support-ticket status dropdown is a native `<select>` (fully keyboard and screen-reader operable for free) rather than a custom-styled `<div>` menu.
- **Bad:** A custom "language toggle" implemented as a clickable `<span>` with no `role="button"`, no `tabindex`, and no keyboard handler.
- **Good:** A coupon-invalid message is shown in red text AND prefixed with a warning icon AND read via `aria-live` region — three signals, not one.
- **Bad:** A "required" form field indicated only by a subtly different border color with no asterisk, label text, or `aria-required`.

## Quality Checklist
- [ ] Fully operable by keyboard alone (Tab order, Enter/Space/Escape/arrows as appropriate)
- [ ] Visible focus indicator on every interactive element
- [ ] No meaning conveyed by color alone
- [ ] Every form input has a real, associated label
- [ ] Heading hierarchy is correct and unbroken
- [ ] Modals/dropdowns trap and return focus correctly
- [ ] Contrast meets WCAG 2.2 AA (verify with `better-colors`)
- [ ] Touch targets meet minimum size on mobile
- [ ] Respects `prefers-reduced-motion`

## Collaborating with the Other 4 Skills
- **better-ui**: every state (hover/focus/active/disabled) that Skill defines must also satisfy this Skill's focus-visibility and keyboard-operability rules — this Skill can veto a component design that isn't operable.
- **better-typography**: heading tag choices from that Skill must match real document structure per this Skill's heading-hierarchy rule.
- **better-colors**: contrast values proposed by that Skill are checked and enforced here against WCAG 2.2 AA minimums.
- **better-layouts**: touch target sizing and responsive reflow from that Skill must preserve tab order and not create keyboard traps or orphaned content.
