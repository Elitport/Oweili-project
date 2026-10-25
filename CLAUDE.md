# Oweili — Saudi Art Platform

## Project Overview
- Name: Oweili (previously Resha Art)
- Domain: oweili.com
- Purpose: Saudi bilingual art community platform for artists and collectors
- Hosting: Hostinger (manual upload — no auto-deploy)
- Stack: PHP, MySQL, HTML, CSS, JavaScript
- Deployment: Files uploaded manually to Hostinger public_html, SQL run via phpMyAdmin

## Roles
- Artist — can register, upload artworks, manage profile
- Collector — can browse and buy artworks
- Sub-admin — limited admin access
- Admin — full control via admin.php

## Pages Built
- index.html — Homepage
- nav.php — Shared navbar (used across all pages)
- explore.php — Art styles gallery
- marketplace.php — Browse and buy artworks (bilingual)
- studio.php — Studio content page
- community.php — Community page
- support.php — Support and FAQ
- events.php — Art events (under Discover)
- training.php — Training institutes (under Discover)
- register.php / register_artist.php / register_collector.php — Sign up flows
- login.php / logout.php / verify.php / forgot.php — Auth
- edit_profile.php / edit_artwork.php — Self-service editing
- artist_dashboard.php — Artist control panel
- collector_dashboard.php — Collector control panel
- subadmin_dashboard.php — Sub-admin panel
- admin.php — Main admin panel
- art_styles.js — Shared data file for all art styles (single source of truth)

## Database
- DB name: u405344949_reshaart
- Tables: users (artists, collectors and admins), messages, art_styles, plus migration tables from SQL files
- The live database may use u405344949_contact — confirm in phpMyAdmin before any database change
- config.php is NOT in GitHub (contains DB and SMTP secrets — lives on server only)

## SQL Migration Files
Multiple .sql files exist for setup, roles, profiles, purchases, financial, phone, reset, styles, and discover_setup.sql

## Design System
- Style: Luxury white minimal
- Background: Video (bg.mp4) with white overlay
- Primary color: #ff0055
- Accent color: #0066ff
- Font: Helvetica Neue
- Cards: border-radius 16px, glass effect
- Navbar: fixed top, frosted glass, dropdown menus

## Languages
- Bilingual: English (LTR) and Arabic (RTL)
- Toggle button in navbar switches language
- All pages must support both directions
- dir attribute must be set on html element when switching

## Navigation Structure
- The Studio → studio.php
- Community → community.php
- Discover → events.php / training.php
- Explore Art Styles → explore.php
- Support → support.php
- Marketplace → marketplace.php

## Known Issues
- Language switch does not fully apply RTL to navbar in some pages
- Manual deployment means live site may be out of sync with repo

## Work Rules
- Always answer in simple non-technical English
- Never break existing design or language system
- When editing any file, keep the existing navbar and style intact
- art_styles.js is the single source for all art style data — never duplicate it
- config.php must never be added to GitHub
- Always confirm before making changes to database structure

## Design Rules (apply to every page you create or edit)

### 1. Better UI
- Every button, card, and element must have a clear purpose
- Navigation must be intuitive and consistent across all pages
- Empty states must be handled (show a message if no artworks found)
- Loading states must be smooth
- Mobile first — every page must work perfectly on phone

### 2. Better Colors
- Primary: #ff0055 (red/pink) — use for key actions and highlights
- Accent: #0066ff (blue) — use for links and secondary actions
- Background: white #ffffff with frosted glass cards
- Text: #111111 for headings, rgba(0,0,0,0.65) for body
- Never add random colors — stick to this palette
- Ensure contrast ratio is always readable
- Pink #ff0055 is for buttons and large highlights only (large text = 18px+, or 14px+ bold) — never for small text, since it fails WCAG AA at small sizes
- Button labels on pink must be bold and at least 14px
- Small text links use blue #0066ff or dark grey #111111 — never pink

### 3. Better Typography
- Font: Helvetica Neue for English, system Arabic font for Arabic
- Headings: bold, tight letter spacing
- Body: 13-14px, line height 1.7-1.8
- Arabic text must align right and use proper RTL sizing
- Never mix too many font sizes — keep a clear hierarchy

### 4. Better Layouts
- Use CSS Grid for card galleries (4 columns desktop, 2 tablet, 1-2 mobile)
- Consistent padding: 32px desktop, 16px mobile
- Cards: border-radius 16px, subtle shadow, white background
- Sections must breathe — use generous spacing between them
- Navbar is always fixed at top, 56px height, frosted glass

### 5. Better Accessibility
- All images must have alt text in both Arabic and English
- Color contrast must pass WCAG AA minimum
- Interactive elements must have hover and focus states
- Font size minimum 12px, body minimum 13px
- RTL and LTR must both be fully functional — never leave one broken

## Content & Marketing Rules (apply whenever relevant)

### 6. Social Content Engine
- When asked to create social media content for Oweili, write posts that highlight real human artists (not AI)
- Always create content in both Arabic and English
- Tone: warm, artistic, proud of Saudi art culture
- Platforms: Instagram, X (Twitter), TikTok

### 7. SEO & AI Visibility
- Every page must have a proper title tag and meta description in both languages
- Use clear headings (H1, H2, H3) that describe the page content
- Image alt text must be descriptive in both Arabic and English
- URLs must be clean and readable (no random numbers)

### 8. Content Strategy
- Every page must have a clear purpose and call to action
- Homepage: attract visitors → register or explore
- Marketplace: browse → buy
- Artist profile: discover → follow or contact
- Never leave a page without a next step for the visitor

### 9. Brand Style
- Oweili celebrates human art only — never mention or suggest AI art
- Voice: confident, warm, bilingual, proudly Saudi
- Always use "Oweili" not "Resha Art" (old name)
- Tagline concept: human hands, real art, trusted platform

### 10. Outreach Composer
- When writing emails or messages to artists or galleries, keep the tone professional but warm
- Always offer clear value: exposure, sales, community
- Write in both Arabic and English

### 11. Ad Manager
- When creating ad copy for Oweili, focus on trust and authenticity
- Key message: real artists, real art, safe to buy
- Target: art lovers in Saudi Arabia and Gulf region

## Project Management Rules

### 12. Write Spec
- Before building any new feature, write a clear plan: what it does, who uses it, what pages it affects, and what database changes are needed
- Keep specs short and simple — no technical jargon

### 13. Roadmap
- When asked about priorities, always think in 3 phases:
  - Phase 1: must have (site works and earns trust)
  - Phase 2: should have (grows the community)
  - Phase 3: nice to have (makes it special)
- Always tell me which phase we are in

### 14. Competitive Brief
- Oweili competes with: general marketplaces (not art-focused), social media (Instagram), and international art sites
- Our advantage: human-only art, bilingual, Saudi-focused, trusted platform
- Always keep this in mind when suggesting features

### 15. Sprint Planning
- When we have a list of things to build, group them into small sprints
- Each sprint = 3 to 5 tasks maximum
- Always start with the thing that helps users the most

### 16. Brainstorm
- When asked for ideas, give at least 5 options
- Always think about the artist first, then the collector, then the visitor
- Consider Saudi culture and art scene in every suggestion

## Growth & Customer Rules

### 17. Growth Pulse
- When asked how the platform is doing, look at these signals: number of registered artists, number of artworks listed, number of sales, and most visited pages
- Always suggest one action to improve the weakest area

### 18. Lead Finder
- Potential artists for Oweili are: Saudi art school graduates, artists active on Instagram with original work, members of Saudi art associations
- When looking for leads, focus on quality over quantity — one serious artist is better than ten casual ones

### 19. Review & Reputation
- Every artwork sold must have a review option for the buyer
- Every artist profile must show their average rating
- Fake reviews are never allowed — only verified buyers can review
- Respond to negative feedback professionally and quickly

### 20. Speed to Lead
- When a new artist registers, they should feel welcomed immediately
- Send a welcome message within 24 hours
- Guide them to upload their first artwork as the next step
- Never leave a new user without a clear next action

### 21. CRM Autopilot
- Keep track of: active artists, inactive artists (no uploads in 30 days), top sellers, and new registrations
- When asked, give a simple summary of each group
- Suggest a re-engagement message for inactive artists

## Research & Documentation Rules

### 22. Deep Research
- When asked to research something for Oweili (competitors, Saudi art market, pricing strategies, art trends), give a thorough answer with real examples
- Always end research with 3 clear recommendations for Oweili specifically

### 23. Documentation
- Every new feature built must be documented in CLAUDE.md
- Documentation must include: what the feature does, which files it affects, and any database tables it uses
- Keep documentation short and clear — no technical jargon

### 24. Search & Knowledge
- When searching for information to help Oweili, prioritize sources about: Saudi art scene, Gulf art market, Islamic and Arabic art traditions
- Always check if information is relevant to Saudi culture before suggesting it

### 25. Artifact Design
- When building any page or component, make it visually complete
- No placeholder text, no empty sections, no "coming soon" without a reason
- Every page must look ready to show to a real user or investor

## Branding Rules

### Identity
- Platform name: Oweili (أويلي) — never use "Resha Art" (old name)
- Logo: the four-quarter circle mark in #ff0055 red + "OWEILI" in bold uppercase
- Primary color: #ff0055 (red) — buttons and large highlights only
- Accent color: #0066ff (blue) — links and secondary actions
- Background: white #ffffff with frosted glass cards
- Arabic font: system Arabic font, clean and readable
- English font: Helvetica Neue

### Brand Voice
- Tone in English: confident, warm, professional
- Tone in Arabic: أويلي يتحدث بثقة ودفء واحترافية
- Key message: real human artists, real art, safe to buy
- Oweili celebrates human hands — never mention or suggest AI art
- Always write in both Arabic and English
- Arabic is always RTL, English is always LTR

### Brand Guidelines (what to do and what NOT to do)
DO:
- Use the exact brand colors only
- Keep the logo clean with enough white space around it
- Use warm, human storytelling in all content
- Highlight that every artist is a real verified human

DO NOT:
- Use random colors outside the brand palette
- Stretch or change the logo shape
- Use AI-generated art to represent the platform
- Use "Resha Art" anywhere on the site or in content
- Use pink for small text (accessibility rule)

### Consistency Guard
- Before any page goes live, check it against these brand rules
- Flag anything that uses wrong colors, wrong tone, or mentions AI art
- Flag any page that still says "Resha Art"
- Every new page must have both Arabic and English content before launch

### Brand Assets
- Logo file: stored in /assets/logo/ folder
- Brand colors are defined in the CSS as variables
- Any new template or design must follow this guide
- No copyrighted fonts or images — free or owned assets only

### Golden Rule
- No logo, color, font or identity change without owner approval
- Branding prepares the change, owner approves, then Claude Code builds it
