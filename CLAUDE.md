# Oweili — Saudi Art Platform

## Project Overview
- Name: Oweili (أويلي)
- Domain: oweili.com
- Founded: Saudi Arabia, open to the world
- Mission: The home of real human art — where artists prove their work is handmade, and buyers purchase with full confidence
- Stack: PHP, MySQL, HTML, CSS, JavaScript
- Hosting: Hostinger — auto-deploy from GitHub main branch (now active)

## What Oweili Is (5 pillars)

### 1. Art Marketplace with Proof of Process
- Artists photograph their artwork in 3 stages using their phone camera from inside the website:
  * 25% — early stage (first strokes, first layer)
  * 50% — middle stage (work in progress)
  * 100% — finished piece (final photo, ready to sell)
- When uploading, artist selects:
  * Colors used (from a color list shown during upload)
  * Type of art (guided selection with example images — never automatic AI detection)
  * Price
- Oweili charges 15% commission (excluding VAT), which covers shipping and advertising
- Buyers see the full creation journey, not just the final piece
- This is what makes Oweili different from every other marketplace

### 2. Art Institutes Directory
- A directory of art institutes in Jeddah and Saudi Arabia, all in one place
- Each institute gets its own login (institute account type)
- Institute admins can see: number of course registrations, financial summary
- Helps anyone looking for art education find the right place easily

### 3. Global Art Events
- Art events and gatherings happening around the world
- Each event has a simple article (title, description, date, location, image)
- Added only by Oweili admins — never by the public
- Helps local artists stay connected with the global art world

### 4. Artist Spotlight Articles
- Simple articles about notable local and international artists
- For local artists: photos are added only after the artist gives personal approval
- A consent record must be saved before any local artist photo is published
- Written and managed by the Oweili team only

### 5. Art Education
- Explains art types, their names, why they are called that, their history
- With images for every art style
- Makes Oweili a trusted reference for anyone wanting to learn about art
- Content lives in the database (single source — never typed directly into pages)

## Business Rules
- Commission: 15% of artwork price, excluding VAT, covers shipping and advertising
- Payments: NOT built yet — this is the last thing to add, after everything else is complete
- Proof of process: all 3 stage photos must be taken through the Oweili website camera
- Art type selection: always guided with example images, never automatic AI detection
- Local artist photos: never published without a saved consent record

## Database Tables Needed (not yet built — need approval before creating)
- artwork_stages: the 3 stage photos per artwork (25%, 50%, 100%) with dates
- artwork_colors: colors used in each artwork
- institutes: name in Arabic and English, city, description, logo, contact, owner account
- courses: belongs to an institute, with dates, price and available seats
- course_registrations: who signed up, payment status
- articles: type (event / artist spotlight / art education), title and body in both languages, cover image, published or draft
- article_images: extra photos for each article
- photo_consents: record of local artist approval (who, when, how)

## Sprint Plan
- Sprint 1 (now): update CLAUDE.md + database tables + proof-of-process camera upload
- Sprint 2: typography, design applied to all pages, homepage rebuilt to reflect vision
- Sprint 3: articles system + institutes directory + art education with images
- Sprint 4: payments (last — after everything else is complete)

## What NOT to build yet
- Any payment system, checkout, orders or commission calculation
- These wait until Sprint 4

## Pages Built
- index.php — Homepage
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

## Shared Files
- assets/css/oweili.css — shared styles. The brand color variables (--color-primary, --color-accent, etc.) work on every page. All other shared styles only switch on for pages with <body class="ow">, so older pages keep their look until they are converted one by one
- assets/logo/ — home for the official logo files (not added yet)
- Mobile menu (in nav.php) — on screens 1024px and smaller, a ☰ button opens a full-width menu with all main links (48px tall, bilingual). It closes on link tap, outside tap or Escape. On phones (560px and smaller) Sign In / Join Free (or the account links) move into this menu. No database tables used
- Every page that shows the main menu now uses nav.php, including index.php and marketplace.php
- index.html — tiny forwarding file only: sends old links and bookmarks to index.php. Do not put content in it

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
- Fixed: the Arabic menu order. A CSS line on 18 pages flipped the menu back to left-to-right; it was removed. Never add `flex-direction:row-reverse` to the menu for Arabic — dir="rtl" already puts items in the right order
- Some page sections (buttons, stats rows, filters) still use `[dir="rtl"] ... row-reverse` and may show in the wrong order in Arabic — check each when converting a page
- Manual deployment means live site may be out of sync with repo

## Work Rules
- Always answer in simple non-technical English
- Never break existing design or language system
- When editing any file, keep the existing navbar and style intact
- art_styles.js is the single source for all art style data — never duplicate it
- art_styles.js does not exist yet — creating it is a priority in the next sprint. Until then, do not duplicate art style data in new pages.
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
- Homepage goal: attract artists and collectors worldwide → register or explore
- Do not restrict language or content to Saudi Arabia only
- Saudi culture is celebrated but not exclusive
- Marketplace: browse → buy
- Artist profile: discover → follow or contact
- Never leave a page without a next step for the visitor

### 9. Brand Style
- Oweili celebrates human art only — never mention or suggest AI art
- Voice: confident, warm, bilingual, open to the world — proudly Saudi-born
- Always use "Oweili" not "Resha Art" (old name)
- Tagline concept: human hands, real art, trusted platform

### 10. Outreach Composer
- When writing emails or messages to artists or galleries, keep the tone professional but warm
- Always offer clear value: exposure, sales, community
- Write in both Arabic and English

### 11. Ad Manager
- When creating ad copy for Oweili, focus on trust and authenticity
- Key message: real artists, real art, safe to buy
- Target: art lovers worldwide, with a celebration of Saudi and Arab art culture

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
- Our advantage: human-only art, bilingual, Saudi-born with a global vision, trusted platform
- Always keep this in mind when suggesting features

### 15. Sprint Planning
- When we have a list of things to build, group them into small sprints
- Each sprint = 3 to 5 tasks maximum
- Always start with the thing that helps users the most

### 16. Brainstorm
- When asked for ideas, give at least 5 options
- Always think about the artist first, then the collector, then the visitor
- Think about artists and collectors worldwide in every suggestion, and celebrate Saudi and Arab art culture where it fits

## Growth & Customer Rules

### 17. Growth Pulse
- When asked how the platform is doing, look at these signals: number of registered artists, number of artworks listed, number of sales, and most visited pages
- Always suggest one action to improve the weakest area

### 18. Lead Finder
- Potential artists for Oweili are real human artists worldwide: art school graduates, artists active on Instagram with original work, and members of art associations — including Saudi and Arab art schools and associations
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
- When searching for information to help Oweili, look at the global art market, and give special attention to the Saudi art scene, Gulf art market, and Islamic and Arabic art traditions
- Always check that information fits a worldwide audience and respects Saudi culture before suggesting it

### 25. Artifact Design
- When building any page or component, make it visually complete
- Every page must look complete and professional
- Coming Soon is only allowed when a real feature is planned and has a notify me button
- Never use Coming Soon as a filler for missing content
- No placeholder text and no empty sections
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

TARGET AUDIENCE:
- Oweili is open to all real human artists worldwide, not only Saudi artists
- Based in Saudi Arabia, but welcoming artists from every country
- The only requirement: you must be a real human artist, not AI-generated content
- Collectors and art lovers from anywhere can buy and browse

KEY MESSAGES:
- Real human artists, real art, safe to buy
- A Saudi-born platform with a global vision
- Every artwork is made by human hands

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

## Development & Security Rules

### 26. Frontend Design
- Every page must look visually complete and professional before upload
- Use modern CSS: CSS variables for all colors, CSS Grid for layouts, flexbox for alignment
- Every interactive element must have hover, focus and active states
- Animations must be smooth and subtle — nothing flashy or distracting
- Always test the page layout at 3 sizes: phone (375px), tablet (768px), desktop (1280px)
- Arabic RTL and English LTR must both look equally good

### 27. Code Review
- Before saving any file to GitHub, check it for these problems:
  - Broken links or missing files
  - Hardcoded passwords or secrets (never allowed)
  - Duplicate code that could be shared
  - Missing Arabic or English translations
  - Console errors or PHP warnings
- If any problem is found, fix it before saving
- Report what was checked and what was fixed

### 28. Security Review
- Run a security check on every PHP file before upload:
  - No SQL injection risks — always use prepared statements
  - No XSS risks — always escape output
  - No direct file access without login check on protected pages
  - No sensitive data exposed in URLs
  - No debug files left in the project (like mailtest.php)
- If a security risk is found, fix it and explain what it was in simple words
- Never store passwords in plain text
- Always follow the Saudi Personal Data Protection Law
