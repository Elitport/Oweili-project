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
- Tables: artists, messages, reports, art_styles, plus migration tables from SQL files
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
