# Oweili — Saudi Art Platform

## Project Overview
- Name: Oweili (أويلي)
- Domain: oweili.com
- Founded: Saudi Arabia, open to the world
- Mission: The home of real human art — where artists prove their work is handmade, and buyers purchase with full confidence
- Stack: PHP, MySQL, HTML, CSS, JavaScript
- Hosting: Hostinger — auto-deploy from GitHub main branch (now active)

## Roles
- Artist: registers, photographs artwork in 3 stages, sets price and art type
- Collector: browses and buys artworks
- Institute Admin: manages their institute page, courses and registrations
- Sub-admin: approves artworks and manages content
- Admin: full control

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
- The SQL for all of these is ready in database_update.sql (also adds the 'institute' role to users). It has NOT been run yet — the owner runs it in phpMyAdmin after a backup
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
- Sprint 2: typography, design applied to all pages, homepage rebuilt to reflect vision (done — see Features)
- Sprint 3: articles system + institutes directory + art education with images (built — see Features; institutes are still added in phpMyAdmin, an institute admin tool is next)
- Sprint 4: payments (last — after everything else is complete)

## What NOT to build yet
- Any payment system, checkout, orders or commission calculation
- These wait until Sprint 4

## Pages Built
- index.php — Homepage: one full-screen hero (see Features)
- nav.php — Shared navbar (used across all pages)
- explore.php — Explore Art Styles: an art encyclopedia of 50 styles with a rich window for each (see Features)
- marketplace.php — "The Gallery" / «المعرض»: browse and buy artworks (bilingual). The file keeps its old name; visitors only ever see "Gallery"
- studio.php — retired: it only sends visitors to explore.php (permanent redirect). Its workshop ideas moved to training.php
- community.php — Community page: real counts and the newest artworks (see Features)
- support.php — Support and FAQ
- events.php — old events page (no longer in the menu; its events live in the old `events` table — move them into Art Events articles)
- training.php — Art Institutes: Upcoming Workshops on top, then the institutes directory
- articles_list.php — all published articles of one type (?type=event / artist_spotlight / art_education)
- article.php — one article (article.php?slug=...)
- admin_articles.php — team tool to write articles (sub-admins and admins only)
- article_lib.php — shared helpers for articles, consent records and institutes
- register.php / register_artist.php / register_collector.php — Sign up flows
- login.php / logout.php / verify.php / forgot.php — Auth
- edit_profile.php / edit_artwork.php — Self-service editing
- artist_dashboard.php — Artist control panel ("Start New Artwork" button + drafts in progress); with ?id=N it is the artist's public profile, open to everyone
- upload_artwork.php — 3-stage proof-of-process upload (see Features below)
- artwork_lib.php — shared helpers for the upload: color list, art type pictures, photo checks and saving
- collector_dashboard.php — Collector control panel
- subadmin_dashboard.php — Sub-admin panel
- admin.php — Main admin panel
- assets/js/art_styles.js — Shared data file for all art styles (single source of truth), bilingual

## Features

### 3-stage artwork upload (proof of process) — built, needs database_update.sql
- What it does: artists start a new artwork from their dashboard and go through 5 steps: 1 titles (Arabic + English required), 2 photo at 25% (required), 3 photo at 50% (optional, can skip), 4 photo at 100% (required) + colors used + art type (guided cards with small drawn examples), 5 price in SAR + the 15% commission note, then "Submit for Review"
- Every step is saved straight away, so the artist can come back days later. Unfinished work is a private draft (artworks.is_draft = 1): only the artist sees it (dashboard → "Drafts in progress" → Continue). Admins and sub-admins only see it after it is submitted
- After submit: is_draft = 0, is_approved = 0 (waits for admin approval as before), image_url = the 100% photo, so the marketplace keeps working
- Photos: taken with the phone camera (camera opens directly on phones; on computers a file picker opens instead). Max 10MB, JPG/PNG/WEBP only, checked on the server. Saved to uploads/artworks/stages/ with random names. Each photo is re-saved: the phone's rotation is applied and hidden data such as GPS location is removed (privacy). taken_at = the camera's own date if present, otherwise the upload time
- Retaking a photo replaces the old one and deletes the old file. Deleting or rejecting an artwork also deletes its stage photos
- Photo lock: in edit_artwork.php, artworks with stage photos cannot change their main photo (blocked on the server too). A bilingual note explains why. Title, description, price, type and status can still be edited. Descriptions are optional when editing, like in the upload
- Files: upload_artwork.php, artwork_lib.php, artist_dashboard.php, edit_artwork.php (drafts are sent back to the upload flow), admin.php + subadmin_dashboard.php (drafts hidden from the review list), art_types.php (added mixed_media, digital_art, islamic_geometric), assets/css/nav.css
- Tables: artworks (new column is_draft), artwork_stages, artwork_colors — all created by database_update.sql. Until that file is run, the upload page shows a friendly "being set up" message and every other page keeps working
- Hosting needs: PHP upload_max_filesize and post_max_size of at least 10M on Hostinger

### Creation Journey (marketplace) — built, needs database_update.sql
- What it does: in the artwork details window, buyers see "Creation Journey — Proof of Human Art" / «رحلة الإبداع — دليل الفن البشري» with the stage photos in order (25% The Beginning, 50% Taking Shape if uploaded, 100% The Finished Piece), the date each was taken, the colors used and the art type
- A small blue "✓ Human Art" / «✓ فن بشري» badge sits under the title (the same badge as everywhere else) — only on artworks that have the 25% and 100% photos
- Old artworks without stage photos show exactly as before (no journey, no badge)
- Every card in the marketplace grid also shows a tiny "✓ Human Art" / «✓ فن بشري» badge when the artwork has the 25% and 100% photos
- Phone: the photos are one row you swipe sideways; tablet and desktop: side by side. Arabic runs right to left
- Only approved artworks on the current page are loaded — unapproved or draft photos are never sent to visitors
- Files: marketplace.php, artwork_lib.php (owJourneyHtml), assets/css/proof.css. Tables: artwork_stages, artwork_colors

### Creation Journey on artist profiles — built, needs database_update.sql
- What it does: on an artist's public profile (artist_dashboard.php?id=N), every artwork that has the 25% and 100% photos shows the "✓ Human Art" badge, a strip of small stage photos (25% / 50% / 100%), and a "See the creation journey" / «شاهد رحلة الإبداع» link that opens the full journey with dates, colors and art type. Old artworks without stages show as before
- Anyone can now open a public profile, even without logging in (the marketplace links to it). Visitors only see artist accounts that are not banned, and only approved artworks. Earnings, drafts, edit and delete stay owner-only; your own dashboard (no ?id) still needs login
- Files: artist_dashboard.php, artwork_lib.php (owJourneyHtml), assets/css/proof.css. Tables: artwork_stages, artwork_colors

### Proof of Process (admin review) — built, needs database_update.sql
- What it does: every pending artwork in admin.php and subadmin_dashboard.php shows "Proof of Process" / «دليل المراحل»: each stage photo (click to open full size) with date and time, the time gap from the previous stage (e.g. "3 days after stage 25%"), "Artist skipped middle stage" when 50% is missing, colors, art type, and the review note
- Warnings to help the decision: "⚠ Less than an hour after the previous stage" and "⚠ Dated before stage X%"
- Note for reviewers: the date comes from the camera's own clock when the photo has one, so it can be wrong if the phone's clock is wrong
- Only admins (password gate) and sub-admins (logged in with that role) can open these pages
- Files: admin.php, subadmin_dashboard.php, artwork_lib.php (owProofPanelHtml), assets/css/proof.css. Tables: artwork_stages, artwork_colors


### Homepage (index.php) — one screen
- What it does: a single full-screen hero over the background video (bg.mp4, covers 100% of the screen) with a soft dark shade rgba(0,0,0,0.15) on top. Badge "✦ A Real Touch" / «✦ لمسة حقيقية»; headline "Everything here started with human hands" / «كل ما تراه هنا، بدأ بيدٍ حقيقية» in one dark color (56px desktop, 36px phone, weight 300); subtitle "Every piece here was made by real hands. And you can see how." / «كل قطعة هنا صنعتها يد حقيقية. وتستطيع أن ترى كيف.» (18px, max 520px); two buttons "Explore Gallery" (marketplace.php) and "Join as Artist" (register_artist.php). No other sections, no footer, no scrolling, no dashes
- The badge, headline, subtitle and buttons fade in gently (CSS only; turned off for visitors who reduce motion)
- Removed on purpose (owner's request): "Art You Can Trust" steps, "Latest Verified Artworks", the Join cards and the Discover cards
- Files: index.php, assets/css/oweili.css, nav.css. Tables: none

### Design overhaul (all pages) — Sprint 2
- Every page links oweili.css. Purple (#aa00ff) and the wrong blue (#0055ff) are now brand blue #0066ff; green, orange and gold accents are now brand blue or a pink tint; small pink text (error boxes, small badges) is now dark #111111 on the pink tint (rule 2)
- All text is at least 12px, including the menu. Because the menu text is bigger, the ☰ menu now takes over below 1100px (was 1024px)
- The remaining Arabic "flip" lines (row-reverse) were removed from all pages — Arabic sections now read right to left correctly
- nav.php now sets the menu words itself (one list for every page), so new pages need no menu translation code
- Darker shades (approved by the owner): --color-primary-dark #e0004b for pink button hovers, and --color-accent-dark #0047ab for small blue text on light blue backgrounds. Both live in oweili.css; pages use var(--color-primary-dark) / var(--color-accent-dark). Paint colors in the upload (artwork_lib.php) are real paint swatches and stay
- Not changed on purpose: admin.php and subadmin_dashboard.php keep their own staff header instead of the public menu

### Articles system (admin tool) — Sprint 3, needs database_update.sql
- What it does: admin_articles.php has 3 tabs — "Art Events" / «الملتقيات الفنية», "Artist Spotlights" / «أضواء على الفنانين», "Art Education" / «التثقيف الفني». Each tab lists its articles (title, Draft/Published, date created, View, Edit, Delete with a confirm question) and a "New Article" button
- Form: title and text in English and Arabic (all required), cover image, extra photos (up to 12, shown under the text), Draft or Published. Events also need start date, optional end date, and the location in both languages
- The web address (slug) is made from the English title (lowercase, spaces to hyphens, -2 added if taken). It stays fixed once the article has been published, so shared links never break
- Artist Spotlights have a "Photo consent" section: artist name EN/AR, email or phone, how they agreed (Email / WhatsApp / Signed Form / In Person / Other), the date, and a proof file (photo or PDF). A spotlight with photos cannot be published until there is a consent that is not withdrawn
- Consent records can never be deleted — only "Mark as withdrawn" (saves the date). If the last consent is withdrawn, the article goes back to draft automatically. Deleting an article keeps its consent records and proof files
- Who: sub-admins and admins (logged in with that role) and admins signed in on admin.php. Everyone else gets "Staff only". Linked from admin.php and subadmin_dashboard.php ("Articles")
- Photos are re-saved like artwork photos (rotation fixed, GPS removed) into uploads/articles/. Proof files go to uploads/consents/, which is private: blocked in .htaccess (plus its own lock file); staff open them only through admin_articles.php?proof=N
- Files: admin_articles.php, article_lib.php, artwork_lib.php (owStoreImage), .htaccess. Tables: articles, article_images, photo_consents

### Public articles pages — Sprint 3, needs database_update.sql
- articles_list.php?type=event|artist_spotlight|art_education: page title and short intro, buttons to switch type (and to Art Institutes), grid of cards (cover, title, date — events show their dates and place). A warm message and an "Explore Artworks" button when there are none yet
- article.php?slug=...: full-width cover, title, published date, the text in the page language (paragraphs; any HTML is shown as plain text for safety), extra photos, "Back" to the list and "Explore Artworks". Events also show dates, place and "Added by the Oweili team" / «أضافه فريق أويلي». Unknown or draft articles show a friendly "not found" page
- Visitors only see published articles; spotlights with photos also need an active consent
- Files: articles_list.php, article.php, article_lib.php, assets/css/discover.css, assets/js/discover.js. Tables: articles, article_images, photo_consents

### Upcoming Workshops (top of training.php)
- What it does: "Upcoming Workshops" / «ورش العمل القادمة» with 5 planned workshops as cards in the same style as the institute cards: Desert Light in Watercolor, Desert Landscapes in Oil, Digital Arabic Calligraphy, Saudi Heritage Drawing (charcoal), Contemporary Saudi Expression (mixed media). Each shows its art type, title, a short line, "Coming Soon" / «قريباً» and a "Notify Me" / «أبلغني» button to register.php (allowed by rule 25: real planned feature with a notify button)
- Below it, "Institutes Directory" / «دليل المعاهد» introduces the institutes list
- Moved here from the old Studio page, which was removed from the menu and now redirects to explore.php
- Files: training.php, studio.php (redirect), nav.php (Studio and its dropdown removed; leftover Studio menu words removed from 15 pages). Tables: none for the workshops

### Community page (community.php): real numbers and real artworks
- Number boxes come from the database: "Artists With Us" counts artist accounts that are not banned, "Messages Shared" counts the messages table. Over 10 shows the number with "+" (e.g. 52+), 10 or less shows the plain number. "Art Styles Covered" / «أسلوب فني موثّق» is counted from assets/js/art_styles.js (now 50) and links to explore.php. "Saudi Community" stays a label
- Daily Inspiration / «إلهام اليوم»: the 3 newest approved, for-sale artworks by artists who are not banned (no drafts). Each shows the photo (whole artwork, never cropped), title and artist name in the page language, and opens that artwork in the Gallery (marketplace.php#artwork-N opens its details window). Pictures only from uploads/ or https
- Fewer than 3 artworks: the empty spots say "Be the first to share your work here." / «كن أول من يشارك عمله هنا.» with a "Join as Artist" / «انضم كفنان» button to register_artist.php
- Phone: one card per row. All reads use prepared statements; if the database is unavailable the page still shows with 0 and the invitation cards
- Files: community.php, marketplace.php (#artwork-N link, kept through the Arabic redirect), artwork_lib.php (owBi, owNotDraftSql). Tables read: users, messages, artworks

### Artist names shown to visitors
- Gallery cards, the artwork window, Community, Explore, the artist profile (page title, main heading, photo text) and the Artist column of the collector's purchases show the artist name chosen at sign-up ("the name people will see"), not the private full name. If it is empty, the full name is used. In Arabic: the Arabic name if the artist filled it in, otherwise the same artist name
- One shared helper: owArtistNameSql() in artwork_lib.php. New pages that show artist names should use it
- On the profile, the Arabic line under the heading shows only when it differs from the main name. The collector's own name on their dashboard is not an artist name, so it stays as is
- Files: artwork_lib.php, marketplace.php, community.php, explore.php, artist_dashboard.php, collector_dashboard.php. Tables read: users

### Art Institutes directory (training.php) — Sprint 3, needs database_update.sql
- What it does: "Art Institutes" / «المعاهد الفنية» with a city filter (cities come from the database) and institute cards: logo (or a building icon), name, city, short description, number of active courses, and "View Courses" / «عرض الدورات» that opens the course list (title, dates, price in SAR or "Free" / «مجاني», seats left or "Fully booked", "Register Interest" → the institute's email, or phone, or website)
- Only active institutes and courses that have not ended are shown. Empty: "We are gathering the best art institutes for you. Check back soon." / «نجمع لك أفضل المعاهد الفنية. عد قريباً.»
- Institutes and courses are added in phpMyAdmin for now (no admin screen yet). The old `training` table is no longer used
- Files: training.php, article_lib.php, assets/css/discover.css, assets/js/discover.js. Tables: institutes, courses

### Explore Art Styles (explore.php) — art encyclopedia of 50 styles, live (approved by the owner)
- What it does: "Explore Art Styles" / «استكشف أساليب الرسم». Filter pills with counts: All 50 / Traditional 15 / Digital 8 / Islamic & Arab Heritage 7 / Contemporary 10 / Nature & Portrait 6 / Photography 4 (navy when active). Equal square cards (4 columns desktop, 3 tablet, 2 phone): a drawn cover, the style name and one feeling word. Hover: lift + thin pink border. Closing line: "Fifty styles. Fifty human stories. Every one began with a hand." / «خمسون أسلوباً. خمسون قصة إنسانية. كلها بدأت بيد.»
- The window (max 760px, full screen on phones; close top right, top left in Arabic, Escape or tap outside) has 10 parts: name + feeling, one-line essence, The Human Story (3 paragraphs), Key Techniques (hover or tap explains), Signs of a Human Hand (numbered), Tools & Materials, Difficulty & Time (Beginner / Intermediate / Advanced, time for one artwork, who it suits), Famous Artists (name, nationality, dates, contribution), Collector's Guide (3 tips), From Our Gallery
- From Our Gallery: the newest approved, for-sale artwork of that type by an artist who is not banned (one prepared database read in explore.php; pictures only from uploads/ or https). If there is none yet: the drawing, an invitation to artists, and the pink button. Button: "See [style] in Gallery" → marketplace.php?type=…. Styles with no Gallery type yet open the whole Gallery
- Gallery types used: exact types where they exist; Chinese Ink → ink, Islamic Mosaic → mosaic, Concept Art → digital_painting, Pixel Art / Motion Graphics / Generative Art / NFT Art → digital_art, all four photography styles → photography. No type yet: Tempera, Fresco, Pencil Drawing, Encaustic, Stained Glass, Islamic Ornamental Art, Manuscript Art, Egyptian Linen Art, Textile Art, Street Art, Hyperrealism, Pop Art, Minimalism, Surrealism, Expressionism, Conceptual Art, Performance Art, Botanical, Animal, Marine, Architectural Drawing
- Shared links open a style directly: explore.php#calligraphy. Unknown links are ignored
- Content: assets/js/art_styles.js (about 400KB, 120KB compressed; loaded after the page shows). Famous artists' names and dates must be double-checked by the team before launch (a list of doubts was given to the owner)
- Generative Art and NFT Art are written only as human-made work (the artist writes the rules; an NFT is an ownership certificate). No mention of AI anywhere
- Cover pictures are simple SVG drawings in the brand colors; they follow light and dark mode and can be replaced later with real artworks
- Files: explore.php, assets/js/art_styles.js, assets/js/explore.js, assets/css/discover.css, assets/js/discover.js, marketplace.php (?type=). Tables read: artworks, users

### Menu and homepage links — Sprint 3
- Discover menu (desktop and ☰): Art Events, Artist Spotlights, Art Education, Art Institutes
- Files: nav.php

### Warm voice for all visitor text
- What it does: every public page now speaks like a real person. English sounds like a trusted gallery owner; Arabic like a friend who loves art. Short sentences (15 words at most), no " — " dashes, no stiff words like "utilize" or "our system". Artists are celebrated, collectors are welcomed
- Rewritten: index.php, marketplace.php, studio.php, community.php, support.php (headings and FAQ answers), explore.php (all style texts), articles_list.php, article.php, training.php, register.php, register_artist.php, register_collector.php. Pages that had no search description now have one in both languages
- The FAQ answer about cost is now honest: joining is free, and Oweili keeps 15% (before VAT) only when a work sells
- Fixed on the way: one Studio block ("Workshops with Saudi Art Institutes") never switched to Arabic; it does now
- When adding new text, keep this voice. Tables: none

### Rebrand: new colors + dark mode — live (approved by the owner)
- What it does: new palette — warm pink #DB8084 (hover #C96A6E), deep navy #23314A (hover #1A2538), soft grey-blue #8FA1B1, body text #4A5568. Role colors: artist pink, collector navy, institute grey-blue, sub-admin #5B8A6E, admin navy
- Dark mode (class "dark-mode" on <html>): background #0F1520, frosted navy glass cards, headings #F0F4F8, body #CBD5E0, links grey-blue #8FA1B1, menu #0F1520 with blur, and a navy shade rgba(15,21,32,0.6) over the background video
- No page has its own colors any more: every color comes from oweili.css variables (plus helpers like --rgb-primary, --color-surface, --color-on-primary), so light and dark mode work on every page. Kept on purpose: real paint swatches in the upload, black behind admin videos, and the welcome email in verify.php (email apps cannot read CSS variables, so it uses the new colors typed in)
- Homepage: new headline and badge (see Homepage)

### Light / dark mode button
- What it does: a small round button next to the language button in the menu (and in the admin, sub-admin and articles staff headers). Moon icon in light mode ("Dark mode" / «الوضع الليلي»), sun icon in dark mode ("Light mode" / «الوضع النهاري»). The icons are drawn in SVG
- The choice is saved in the browser (localStorage "theme" = dark or light) and kept on every page and visit. Before the visitor chooses, the site follows their phone or computer setting (light for most people)
- No flash: theme_head.php runs at the top of every page head, before anything is drawn. New pages must include it before oweili.css: <?php include __DIR__ . '/theme_head.php'; ?>
- Files: theme_head.php (the switch and owThemeButton()), nav.php, oweili.css (dark colors under html.dark-mode, button style), admin.php, subadmin_dashboard.php, admin_articles.php, and every page head. Tables: none
- Files: assets/css/oweili.css, nav.css, proof.css, discover.css, nav.php and footer.php (logo color), artwork_lib.php (art type drawings follow the text color), verify.php, and every page's style block. Tables: none

### Design system (one theme) — design-system branch
- What it does: one look for every page and dashboard, defined once in assets/css/oweili.css and used everywhere
- Fonts: DM Sans (English) + Tajawal (Arabic). Sizes: hero 48px (36px phone), section title 32px (26px phone), card title 24px, subtitle 18px, body 16px, labels and captions 13px, badges 12px (smallest allowed). Line height 1.6 English, 1.7 Arabic
- Colors (variables): --color-primary #ff0055, --color-primary-dark #e0004b, --color-accent #0066ff, --color-accent-dark #0047ab, --color-dark #111111, --color-body, --color-light, --color-bg, --color-card, --color-border. Role colors for dashboards: --color-artist #ff0055, --color-collector #0066ff, --color-institute #7c3aed, --color-subadmin #059669, --color-admin #111111
- Two extra helpers for readability: --color-border-strong (form field edges, so fields are visible) and small error text uses #e0004b (passes contrast; the field border itself turns #ff0055)
- Spacing (8px grid): --space-xs 8, sm 16, md 24, lg 32, xl 48, 2xl 64, 3xl 96. Sections: 96px top and bottom desktop, 48px phone
- Buttons: .btn + .btn-primary (pink) / .btn-secondary (white, dark border) / .btn-accent (blue) / .btn-dark / .btn-danger (delete, reject, ban — dark text on a pink tint) / .btn-ghost; .btn-sm (36px) only in tables and tight rows; .btn-block for full width. Disabled = 40% faded. Focus = 2px outline
- Forms: .field (label above, 13px bold) with 48px inputs, 8px corners, 15px text, blue border on focus; .field.has-error + .field-error for errors; .alert-error / .alert-ok and .err-box / .ok-box for messages
- Cards: .card (white, 16px corners, 24px padding, soft shadow), .card-hover (lifts 4px), .card-media (200px image area)
- Artworks: .aw-card — every artwork image 4:3 on a white background with a thin border (the whole artwork shows, never cropped), artist name 13px, price 16px bold. The Human Art badge (.human-badge, "✓ Human Art" / «✓ فن بشري») is identical on every page
- Stage photos: artwork_lib.php owStagesHtml() draws 25% / 50% / 100% as same-size squares (120px; smaller on cards and drafts) joined by a dashed line; a missing stage shows a dashed grey square. Used by the marketplace journey, artist profile cards, drafts and the reviewer proof panel
- Dashboards: .dash + .role-artist / .role-collector / .role-institute / .role-subadmin / .role-admin set the role color; .stat-grid + .stat-card (3px top border in the role color); .table (alternating rows); .tabs / .tab. Admin and sub-admin panels now open with stat cards. There is no institute dashboard yet — the role color is ready for it
- Menu: 64px tall, bigger logo mark (four pink quarters + OWEILI), links 13px, pink underline on the current page (also marked in the ☰ menu)
- Removed: every hardcoded Helvetica Neue, the copied menu styles on 17 pages, page-by-page button/input/card copies, the gradient pink-to-blue heading text, and inline color styles
- Files: assets/css/oweili.css, nav.css, proof.css, discover.css, nav.php, footer.php, artwork_lib.php (owStagesHtml), and every page
- Tables: none

## Shared Files
- .htaccess — blocks public access to private files: CLAUDE.md and all .md files, all .sql files, skills-lock.json, and hidden folders (.claude/, .agents/, .git/). Visitors get 403 Forbidden. Keep it when changing hosting settings
- assets/css/oweili.css — the design system (see Features). Applies to every page; pages keep only their own layout in their <style> blocks
- assets/css/nav.css — styles for the shared menu. nav.php links it itself, so no page copies menu styles any more
- oweili.css also loads the brand fonts (DM Sans + Tajawal from Google Fonts) and sets --font-en, --font-ar and --font-sans. --font-sans draws English letters in DM Sans and Arabic letters in Tajawal automatically. Every page uses it, including form fields and buttons; the welcome email in verify.php names the fonts directly because email apps cannot read CSS variables
- oweili.css also has the two-language text rule for every page: <span class="bi-en">…</span><span class="bi-ar">…</span> shows the one matching the page direction
- assets/css/proof.css — styles for the Creation Journey and Proof of Process panels. Text is printed in both languages and the one matching the page direction is shown
- assets/css/discover.css + assets/js/discover.js — shared look and language button for the Discover pages (articles_list.php, article.php, training.php)
- footer.php — the same footer on every public page: logo mark, links (Gallery/المعرض, Events/الملتقيات, Institutes/المعاهد, Support/الدعم), © line in both languages. Social icons show only once their real addresses are filled in at the top of footer.php
- uploads/consents/ — private consent proofs; never link to it directly
- assets/logo/ — home for the official logo files (not added yet)
- Mobile menu (in nav.php) — on screens 1100px and smaller, a ☰ button opens a full-width menu with all main links (48px tall, bilingual). It closes on link tap, outside tap or Escape. On phones (560px and smaller) Sign In / Join Free (or the account links) move into this menu. No database tables used
- Every page that shows the main menu now uses nav.php, including index.php and marketplace.php
- index.html — tiny forwarding file only: sends old links and bookmarks to index.php. Do not put content in it

## Database
- DB name: u405344949_reshaart
- Tables: users (artists, collectors and admins), messages, art_styles, plus migration tables from SQL files
- The live database may use u405344949_contact — confirm in phpMyAdmin before any database change
- config.php is NOT in GitHub (contains DB and SMTP secrets and the admin password hash — lives on server only)
- Admin password: stored only as a hash in config.php as define('ADMIN_HASH', '...'); admin.php reads it from there. If it is missing, the admin login refuses with a setup message. Changing the hash signs out every admin

## SQL Migration Files
Multiple .sql files exist for setup, roles, profiles, purchases, financial, phone, reset, styles, and discover_setup.sql
- database_update.sql — the 8 new tables for the 5 pillars (safe to run more than once). Not run yet

## Design System
- Style: Luxury white minimal
- Background: Video (bg.mp4) with white overlay
- Primary color: #DB8084 (warm pink)
- Accent color: #23314A (deep navy)
- Dark mode: the sun / moon button in the menu; before a choice it follows the visitor's device (see "Light / dark mode button" under Features)
- Font: DM Sans (English) + Tajawal (Arabic), from Google Fonts — loaded once in oweili.css (see rule 30)
- Cards: border-radius 16px, white, 1px border, soft shadow, 24px padding
- Buttons: 8px corners (not pill), 48px tall, bold 14px uppercase. Chips, tags and badges stay rounded
- Navbar: fixed top, 64px, frosted glass, dropdown menus, pink underline on the current page
- The full design system lives in assets/css/oweili.css — see "Design system (one theme)" under Features

## Languages
- Bilingual: English (LTR) and Arabic (RTL)
- Toggle button in navbar switches language
- All pages must support both directions
- dir attribute must be set on html element when switching

## Navigation Structure
- Community → community.php
- Discover → Art Events / Artist Spotlights / Art Education (articles_list.php?type=...) / Art Institutes (training.php)
- Explore Art Styles → explore.php
- Support → support.php
- Gallery / «المعرض» → marketplace.php (never call it "Marketplace" / «السوق» on the site)

## Known Issues
- Fixed: the Arabic menu order. A CSS line on 18 pages flipped the menu back to left-to-right; it was removed. Never add `flex-direction:row-reverse` to the menu for Arabic — dir="rtl" already puts items in the right order
- Fixed in Sprint 2: the last `[dir="rtl"] ... row-reverse` lines in page sections were removed. Do not add them back
- Fixed: the menu showed twice after the design-system merge, because browsers kept an old saved copy of nav.css. Every CSS and JS link now carries a version number taken from the file's last-changed time, e.g. href="/assets/css/nav.css?v=<?= @filemtime(__DIR__ . '/assets/css/nav.css') ?>". When a file changes, the number changes and every browser loads the new copy. Any new page must link its CSS and JS files the same way
- Fixed: the background video could stay invisible when the browser already had it ready (e.g. saved from an earlier visit). Every page now also checks whether the video is ready straight away
- The site is auto-deployed from GitHub main via Hostinger — anything saved to main goes live straight away, so test on a working branch first

## Work Rules
- Always answer in simple non-technical English
- Never break existing design or language system
- When editing any file, keep the existing navbar and style intact
- assets/js/art_styles.js is the single source for all art style data — never duplicate it. Pages load the file instead of copying the content
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
- Primary: #DB8084 (warm pink) — use for key actions and highlights
- Accent: #23314A (deep navy) — use for links and secondary actions
- Background: white #ffffff with frosted glass cards
- Text: #23314A for headings, #4A5568 for body, #8FA1B1 for captions on large text
- Hover on pink buttons: --color-primary-dark #C96A6E. Navy hover: --color-accent-dark #1A2538
- Text on pink buttons is navy (--color-on-primary), because white on the new pink is too faint to read
- Never add random colors — stick to this palette
- Ensure contrast ratio is always readable
- Pink #DB8084 is for buttons, borders and marks only; pink words in big headings use --color-highlight (#C96A6E on light). Never small pink text (large text = 18px+, or 14px+ bold) — never for small text, since it fails WCAG AA at small sizes
- Button labels on pink must be bold and at least 14px
- Small text links use blue #0066ff or dark grey #111111 — never pink

### 3. Better Typography
- Font: DM Sans for English, Tajawal for Arabic (Google Fonts, see rule 30). Pages use var(--font-sans) from oweili.css — never type a font name into a page
- Headings: bold, tight letter spacing
- Body: 16px minimum, line height 1.7 Arabic / 1.6 English (rule 30). Existing pages still use 13-14px body text until they are updated
- Arabic text must align right and use proper RTL sizing
- Never mix too many font sizes — keep a clear hierarchy

### 4. Better Layouts
- Use CSS Grid for card galleries (4 columns desktop, 2 tablet, 1-2 mobile)
- Consistent padding: 32px desktop, 16px mobile
- Cards: border-radius 16px, subtle shadow, white background
- Sections must breathe — use generous spacing between them
- Navbar is always fixed at top, 64px height, frosted glass

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
- Gallery (marketplace.php): browse → buy
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
- Logo: the four-quarter circle mark in the primary pink (#DB8084) + "OWEILI" in bold uppercase
- Primary color: #DB8084 (warm pink) — buttons and large highlights only
- Accent color: #23314A (deep navy) — links and secondary actions
- Background: white #ffffff with frosted glass cards
- Arabic font: Tajawal (Google Fonts)
- English font: DM Sans (Google Fonts)

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

## Design & Brand Creation Skills

### 29. Theme Factory
- Oweili has one visual theme only — never mix themes
- The theme must feel like a luxury art gallery: clean, refined, spacious
- Core theme values:
  * White space is as important as content — never crowd the page
  * Every color must serve a purpose — no decorative colors
  * Typography creates the mood — font choice is as important as color
  * Every element must feel intentional — nothing random or default
- When building or redesigning any page, apply the full theme consistently:
  * Use CSS variables from oweili.css for every color
  * Use the same font stack on every page
  * Use the same spacing system (8px grid: 8, 16, 24, 32, 48, 64, 96)
  * Use the same border-radius system (8px for inputs and buttons, 16px for cards)
- Never use default browser styles — everything must be intentionally designed

### 30. Frontend Design (Professional)
- Before writing any HTML or CSS, think about the visual hierarchy first:
  * What is the ONE thing the visitor should notice first?
  * What is the second thing?
  * What action do we want them to take?
- Apply these professional design principles on every page:
  * Contrast: important things are dark and bold, supporting things are light and small
  * Alignment: everything aligns to an invisible grid — nothing floats randomly
  * Proximity: related things are close together, unrelated things have space between them
  * Repetition: the same style repeats — buttons always look the same, headings always look the same
- Typography rules for Oweili:
  * Arabic: use "Tajawal" from Google Fonts — elegant, readable, modern
  * English: use "DM Sans" from Google Fonts — clean, contemporary, professional
  * Headline size: 48-64px desktop, 32-40px mobile
  * Body size: 16px minimum for readability
  * Line height: 1.7 for Arabic body text, 1.6 for English body text
  * Never use more than 2 font families on one page
- Spacing rules:
  * Sections need at least 80px padding top and bottom on desktop
  * Cards need at least 24px internal padding
  * Never put two things right next to each other without breathing room

### 31. Algorithmic Art & Visual Identity
- Oweili's visual identity is built on human creativity — never use AI art images
- For visual elements that represent Oweili (icons, patterns, backgrounds):
  * Use SVG-based geometric patterns inspired by Islamic art
  * Use the brand colors only: #ff0055 and #0066ff
  * Keep them subtle — they support the content, never compete with it
- The logo placeholder (until a professional logo is purchased):
  * Use a simple geometric SVG mark in #ff0055
  * Always paired with the word "OWEILI" in DM Sans bold
  * Never stretch, rotate, or recolor the logo mark
- When creating any visual element:
  * Ask: does this feel like a luxury art gallery?
  * Ask: does this look like it was made by a human with intention?
  * Ask: does this serve the content or distract from it?

### 32. Canva & Social Media Content
- Every social media post for Oweili must follow these rules:
  * Always include the Oweili brand mark or name
  * Use only brand colors: #ff0055 and #0066ff on white
  * Arabic posts: right-aligned, Tajawal font
  * English posts: left-aligned, DM Sans font
  * Never use stock photos — use artworks from real Oweili artists only
  * Post formats to always have ready:
    - Artist spotlight: artist photo + artwork + short quote
    - New artwork: artwork photo + title + artist name + price
    - Art education: tip or fact about an art style with a visual
    - Event announcement: event name + date + location + visual
- Tone for every post:
  * Warm, proud, human
  * Never salesy or promotional-sounding
  * Always bilingual — Arabic first, then English below
  * Hashtags in both languages
