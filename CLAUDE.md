# Oweili — Saudi Art Platform

## Project Overview
- Name: Oweili (previously Resha Art)
- Domain: oweili.com
- Purpose: Saudi bilingual art community platform for artists
- Hosting: Hostinger (shared hosting)
- Stack: PHP, MySQL, HTML, CSS, JavaScript

## Database
- DB name: u405344949_reshaart
- Tables:
  - artists (id, name, email, phone, password, is_banned, verified)
  - messages
  - reports
  - art_styles

## Files in public_html
- index.html — Homepage with hero section and navbar
- explore.php — Art styles gallery page (12 styles)
- art_styles.js — Shared data file for all art styles (single source of truth)
- chat.php — Artist chat room
- login.php — Sign in page
- register.php — Join free page
- verify.php — Email verification
- logout.php — Logout
- admin.php — Admin panel (password stored privately, not in this file)
- admin_styles.php — Admin art styles manager
- studio.php — Studio page (built, needs content)
- community.php — Community page (built, needs content)
- support.php — Support & FAQ page (built, needs content)
- config.php — Database connection
- bg.mp4 — Background video

## Design System
- Style: Luxury white minimal
- Background: Video (bg.mp4) with white overlay
- Primary color: #ff0055 (red/pink)
- Accent color: #0066ff (blue)
- Font: Helvetica Neue
- Cards: rounded-16px, glass effect, white background
- Navbar: fixed top, frosted glass, dropdown menus

## Languages
- Bilingual: English (LTR) and Arabic (RTL)
- Toggle button in navbar switches language
- All pages must support both directions

## Navigation Structure
- The Studio → studio.php (dropdown: Watercolor, Oil, Digital, Charcoal)
- Community → community.php (dropdown: Chat, Meet Artists, Share, Learn)
- Explore Art Styles → explore.php (no dropdown)
- Support → support.php (dropdown: Contact, How it works, Terms)

## Work Style
- Always answer in simple non-technical English
- Never break existing design or language system
- When editing any file, keep the existing navbar and style intact
- art_styles.js is the single source for all art style data — never duplicate it
