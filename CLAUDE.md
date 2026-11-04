# Oweili — Saudi Art Platform

## Project Overview
- Name: Oweili (أويلي)
- Domain: oweili.com
- Founded: Saudi Arabia, open to the world
- Mission: The home of real art («بيت الفن الأصيل») — where artists prove their work is handmade, and buyers purchase with full confidence
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
- Done on the live database (checked 30 Oct 2026): all 8 tables, artworks.is_draft and the 'institute' role are in place. A safe re-run on 30 Oct found everything already there and changed nothing. database_update.sql stays in the project as the record (safe to run more than once)
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
- Any real payment provider, checkout or live money movement
- The orders and escrow structure exists but stays switched off (see Payments and escrow) until Sprint 4

## Pages Built
- index.php — Homepage: one full-screen hero (see Features)
- nav.php — Shared navbar (used across all pages)
- explore.php — Explore Art Styles: an art encyclopedia of 50 styles with a rich window for each (see Features)
- marketplace.php — "The Gallery" / «المعرض»: browse and buy artworks (bilingual). The file keeps its old name; visitors only ever see "Gallery"
- studio.php — retired: it only sends visitors to explore.php (permanent redirect). Its workshop ideas moved to training.php
- community.php — Community page: real counts and the newest artworks (see Features)
- chat.php — retired: the Artist Chat Room was removed. The file only sends visitors to community.php (permanent redirect), so old links and bookmarks still work
- support.php — Support and FAQ
- events.php — retired: sends old links to Art Events (articles_list.php?type=event) for good (301). The old events table was empty on the live site (checked 31 Oct 2026)
- institutes.php — Art Institutes («المعاهد الفنية»), its own page under the Discover menu (2 Nov 2026): Upcoming Workshops on top, then the institutes directory. No longer one of the Art Events type buttons
- training.php — retired address: sends old links to institutes.php for good (301), keeping ?city and the language
- articles_list.php — all published articles of one type (?type=event / artist_spotlight / art_education)
- article.php — one article (article.php?slug=...)
- admin_articles.php — team tool to write articles (sub-admins and admins only)
- article_lib.php — shared helpers for articles, consent records and institutes
- my_articles.php — artists write articles that wait for the admin's approval (see Features)
- auctions.php — "Oweili Auctions, Coming soon" with the real waiting-list counter and Notify me form (see Features); auction_lib.php — its shared code
- register.php / register_artist.php / register_collector.php — Sign up flows
- login.php / logout.php / verify.php / forgot.php — Auth
- edit_profile.php / edit_artwork.php — Self-service editing
- artist_studio.php — My Studio, the artist's private area (see Features)
- view.php — counts artwork views (Gallery window)
- certificate.php — one Certificate of Authenticity, ready to save as PDF; cert_verify.php — the public verify page at oweili.com/verify (see Features)
- artist_dashboard.php — Artist control panel ("Start New Artwork" button + drafts in progress); with ?id=N it is the artist's public profile, open to everyone
- upload_artwork.php — 3-stage proof-of-process upload (see Features below)
- artwork_lib.php — shared helpers for the upload: color list, art type pictures, photo checks and saving
- collector_dashboard.php — the Buyer dashboard (see Features). Inside the database the account type is still "collector"
- subadmin_dashboard.php — Sub-admin panel
- admin.php — Main admin panel (see Admin dashboard upgrades); admin_person.php (one person), admin_settings.php (Settings), admin_lib.php (shared staff actions)
- admin_institutes.php — team tool for institutes and courses
- bashro_api.php — the Bashro data connection (off by default)
- bashro_send.php — sends requests to Bashro's receive-only door (needs BASHRO_DOOR_SITE / BASHRO_DOOR_KEY in config.php; see Features)
- assets/js/art_styles.js — Shared data file for all art styles (single source of truth), bilingual. art_types.php reads the 50 style names from it
- art_types.php — the art type list: the 50 styles (names from art_styles.js) plus older types kept only so existing artworks keep their label (marked legacy; not offered in the upload or edit forms, except an artwork's own current type when editing)
- assets/js/art_covers.js — the drawn picture of each style, shared by explore.php and upload_artwork.php

## Features

### Three dashboards + ready for Bashro — Phase 1 of 4 built: Buyer dashboard (needs dashboards_update.sql)
- Plan (approved by the owner, 30 Oct 2026): Phase 1 Buyer dashboard + shared foundation, Phase 2 Artist dashboard upgrades, Phase 3 Admin dashboard upgrades (+ institutes tool), Phase 4 Bashro connection (read-only, secret key, off by default). After each phase: push, check the live site (phone + desktop, English + Arabic), short report with screenshots
- Buyer dashboard (collector_dashboard.php, «لوحة المشتري»): greeting, 3 number boxes (My requests, Favorites, Member since) and 5 tabs: My requests (every "I’m Interested" request with its status New / Contacted / In progress / Closed — «جديد / تم التواصل / قيد المتابعة / مغلق»), Favorites (hearts; the heart removes), Profile (name, phone, country, city, preferred language), Email & password, Purchases (purchase history; payments come in Sprint 4). A banner asks to confirm the email, with "Send the link again" (3 per hour). Only the buyer's own data; artists and staff are sent to their own dashboard
- Requests shown: the ones sent while signed in, plus (only once the email is confirmed) ones sent as a guest from the same email
- Heart button (favorites): on Gallery cards and in the artwork window. Signed-in buyers save with one tap; guests are sent to sign in and come straight back to that artwork; artists and staff do not see it. Only artworks a visitor can see in the Gallery can be saved. Shared code: assets/js/favorites.js + favorite.php
- "I’m Interested" for a signed-in buyer: name, phone and email are filled in from the account (they can change them), and the request is linked to the account. Guests use the form as before
- Sign up / sign in / confirm email / forgot password: all already in English and Arabic. New: the confirmation email is in both languages, the language the visitor signed up in is saved, at most 5 new accounts per hour from one address (3 per browser session) for buyers and artists, the welcome email sends buyers to their dashboard, and sign-in returns to the page that asked for it (only our own pages, never another site)
- Account safety: changing the password needs the current one, signs out every other phone and computer and sends a notice email. Changing the email needs the password, sends a confirmation link to the new address and a notice to the old one. A password reset by email also signs out every device
- Ready for Bashro (foundation): every person, artwork, request and sale has a permanent unique ID (public_id, made by the database) and created / updated dates. Status: requests have a real status column; people (active / pending / suspended) and artworks (draft / pending / approved / rejected / sold) get theirs from the existing columns (owUserStatusSql / owArtworkStatusSql in platform_lib.php), so it is never stored twice. Activity log: sign-up, email confirmed, sign in / out, profile, password, email, favorites, requests, password reset — with who, what, when and a monthly-changing one-way code of the visitor's address (never the address itself, PDPL)
- Files: collector_dashboard.php, favorite.php, assets/js/favorites.js, platform_lib.php, account_lib.php, marketplace.php, inquire.php, inquiry_lib.php, login.php, logout.php, register_collector.php, register_artist.php, verify.php, forgot.php, assets/css/oweili.css (.fav-btn), dashboards_update.sql (run on the live database 30 Oct 2026 by a temporary page, since removed; full backup first: .backups/db-u405344949_contact-20261030-224643.sql.gz)
- Tables: users, artworks, artwork_inquiries, purchases (new columns), favorites, activity_log, shipping_addresses and payouts (new; the last two stay empty until Sprint 4)

### Fast page changes (4 Nov 2026)
- Measured first (phone screen 390 px, 4G at 9 Mbps down / 1.5 up + 40 ms, phone-speed processor, median of 3; the timing script is kept outside the project). Before and after numbers are in the report of 4 Nov
- Instant pages: theme_head.php has speculation rules: Chrome, Edge and Android load a page in the background when the finger or mouse comes near its link (prefetch, "moderate") and get it fully ready when it is pressed (prerender, "conservative"). Fallback for other browsers: a <link rel=prefetch> on touch, or after the mouse rests 65 ms (Firefox uses it; Safari ignores both and gets the other speed-ups). Never for pages that change something when opened: logout, verify.php, forgot, upload_artwork, edit_artwork, edit_profile, my_articles, my_orders, commission_file, certificate, img, view, favorite, inquire, bashro_*, admin*, subadmin_dashboard, artist_commission, links with leave= / logout= / token= / k= (prerender also skips show=), [data-no-prefetch], downloads and new tabs; not on slow 2G / 3G or Data Saver (fallback). Tested: a signed-in artist hovering Log out, Upload and Edit profile loads nothing and stays signed in. A new page that changes something on a plain visit must be added to both lists (or its links get data-no-prefetch)
- No white flash: html has the page colour from the first moment (inline style in theme_head.php + oweili.css), dark mode included
- Fonts: the same DM Sans and Tajawal files Google sends, now kept in assets/fonts/ (SIL Open Font Licence: OFL-dmsans.txt, OFL-tajawal.txt), so text needs no trip to Google (the @import chain is gone). The DM Sans Latin file is preloaded; each file loads only when its letters and weight are used
- Browser caching (.htaccess): CSS, JS and fonts 1 year (immutable), photos 1 year, bg.mp4 30 days. owAsset() version numbers are a short fingerprint of the file's CONTENT (crc32), not its date, because every Hostinger deploy re-copies all files with a new date. Text is compressed (Hostinger's CDN already sends br). Hostinger's page cache stays OFF for pages on purpose (each page has a personal security token and the signed-in name)
- Background video: only on the homepage now; removed from the 26 other pages (they show the soft page colour)
- Scripts: discover.js loads with defer (the "js" class is set inline first, so nothing flickers)
- Photos: a soft tinted placeholder fills each photo frame until the photo has loaded (theme_head.php marks loaded images .ld; oweili.css). Frames already keep their size, so nothing jumps. The artwork page lets phones pick the 800 copy (srcset and the matching preload) instead of always the 1600 one
- Database: "does this table / column exist" answers are kept 10 minutes in .logs/schema_cache.json (owHasTable / owHasColumn in platform_lib.php; owSchemaForget() clears it, use it after a database update). Gallery 16 → 10 queries, artwork page 11 → 7. Indexes were already right; no database change
- Files: theme_head.php, assets/css/oweili.css, assets/fonts/ (new), .htaccess, ow_paths.php (owAsset), platform_lib.php, artwork.php, articles_list.php, explore.php, gifts.php, institutes.php, article.php, commission.php, auctions.php, and the 26 pages that had the video. Tables: none

### Words and facts check (4 Nov 2026)
- "Human" removed from every page, email and title (see Brand Style "Site words"): homepage headline and titles, footer, email footer (mail_lib.php), certificate, Gallery banner, artwork and profile descriptions, sign-up, Community, Explore (the end line, "The Story Behind It", "Signs of a Real Hand"), Terms (section "Handmade art only", anchor #handmade), Privacy, robots.txt, 14 lines of the style texts in art_styles.js
- Explore Art Styles facts: all 253 famous artists and the 50 style stories checked against Britannica, Wikipedia and museum sites (through search results; this environment cannot open those sites directly). Fixed: charcoal is "one of the oldest" drawing tools (the Blombos ochre drawing is older), the first camera photograph dates from the 1820s (Niépce, about 1826–27), Syd Mead's Blade Runner work is 1982 (not the 1970s), Lynda Benglis's early wax works were brushed in layers (her poured works were latex). Owner's wording (4 Nov 2026): Audubon "American, born in Haiti" / «أمريكي مولود في هايتي», Aivazovsky "Russian, of Armenian descent" / «روسي من أصل أرمني», Sargent "American, born in Florence" / «أمريكي مولود في فلورنسا» (all three of his entries); Montague Dawson stays as it is
- Files: index.php, footer.php, mail_lib.php, certificate.php, marketplace.php, artwork.php, artwork_lib.php, artist_dashboard.php, register.php, community.php, explore.php, terms.php, privacy.php, robots.txt, assets/js/explore.js, assets/js/art_styles.js. Tables: none

### Real visitor address for every limit (3 Nov 2026)
- config.php's clientIp() believes the X-Forwarded-For header, which any visitor can fake (checked on the live server), so limits could be dodged. config.php is NOT changed: our own visitor_lib.php has owVisitorIp(), which reads only REMOTE_ADDR (the real address on Hostinger, checked on the live server). Nothing in the project calls clientIp() any more; new code must use owVisitorIp()
- Uses it: the monthly one-way address code in the activity log (owIpHash, so every limit built on owRecentCount: sign-ups, gifts, commissions, Auctions Notify me, the verify page, "Send the link again", the Bashro connection), user sign-in (owLoginBlocked / owLoginRecord / owLoginStart replace isRateLimited / recordLoginAttempt / loginUser in login.php; 5 wrong tries in 10 minutes, the address saved on the new session row is the real one), the Bashro connection's address list, and "I'm Interested" (new: also 7 per address per hour, besides 5 per browser session)
- New: the admin.php password has its own lock: 5 wrong passwords in 10 minutes from one address → "Too many attempts. Please wait 10 minutes." (login_attempts rows marked "(admin.php password)"; they do not count toward the user sign-in lock)
- Tested with a new browser session and a new faked X-Forwarded-For on every try: the limits still trip (sign-in 6th try, admin 6th, Notify me 6th, I'm Interested 8th)
- Files: visitor_lib.php (new), platform_lib.php, login.php, admin.php, inquire.php, bashro_api.php. Tables: login_attempts, sessions, activity_log (no database change)

### Payment first, unpaid expiry, the Collect menu, Bashro check (3 Nov 2026)
- Private commissions: the assigned artist can read the request and send a price, but sees NO reference photo (page and commission_file.php) and cannot send a draft or final until the admin presses "Mark as paid…" in Admin → Commissions (optional note such as the bank reference). The payment is recorded in the activity log ('commission_paid', the one record while payments are switched off), and the buyer ("Payment received") and the artist ("you can start") get an email. The buyer's page says "Next: the payment" until then; the artist's page says the photos open after payment
- Unpaid commissions: not paid within 60 days of the request → the buyer gets "Your request will expire" 7 days before (once, logged 'commission_expiry_warned'), then the reference photos are deleted and logged ('commission_photos_deleted ... not paid within 60 days'). The request record stays. Runs in the daily clean-up (owCommUnpaidCleanup). Admin → Commissions shows how many days are left
- Custom gift requests: Admin → Gifts has "Mark as paid…" ('gift_paid' in the activity log); a custom gift cannot move to In progress or Done before it is paid. Ready-artwork gifts are unchanged
- Keep in mind: these payment records live in the activity log, so any future clean-up of old activity must keep 'commission_paid' and 'gift_paid'
- Main menu: Gallery · Collect ↓ (Gifts / هدايا, Commissions / طلب لوحة خاصة, Auctions / المزاد with "Soon") · Explore Art Styles · Discover ↓ · Community · Support ↓. Arabic «اقتنِ». Grouped so the menu still fits on one line on laptops (checked at 1101, 1150, 1280 and 1440px, English and Arabic, signed in and out). Same group in the ☰ phone menu
- Bashro: bashro_send.php reads BASHRO_DOOR_SITE and BASHRO_DOOR_KEY (stray spaces and capital letters are forgiven). On 3 Nov the live config.php had neither line (last saved 31 Oct), so nothing could be sent yet. Admin → Settings → "Sending to Bashro" has a "Send a test to Bashro" button that sends one clearly marked test lead and shows Bashro's answer
- Why only one message reached the test door on 2 Nov: the repeated "I'm Interested" used the same email on the same artwork the same day (saved once on purpose, so Bashro never gets the same lead twice) and the commission hit the hourly limit after many tests. With fresh emails all three arrive (202)
- Visitor address: fixed without touching config.php, see "Real visitor address" below
- Files: commission_lib.php, commission_file.php, artist_commission.php, admin_commissions.php, commission.php, admin_gifts.php, nav.php, bashro_send.php, admin_settings.php. Tables: none changed (activity_log, commissions, commission_files, gift_requests)

### Oweili Auctions: "Coming soon" teaser and waiting list (3 Nov 2026)
- auctions.php (also oweili.com/auctions through .htaccess): «مزادات أويلي، قريباً». A navy band (the soft navy card colour in dark mode) with "Coming soon", the title, a line about rare original artworks going to auction, the LIVE counter and a framed gavel drawing; then "Be the first to know" with the Notify me form, three cards (Rare originals, See how each was made, Certified authentic) and three steps (Join the list, Meet the lots, Bid when it opens)
- Live counter: "X people are waiting for the first auction" / «X شخص بانتظار أول مزاد» is the REAL number of rows in auction_waitlist, starting from zero; it counts up once and is checked every 30 seconds (auctions.php?count=1). Never a made-up number
- Notify me: name (optional) and email. One row per email (the email is unique; a second sign-up with the same email changes nothing and sends nothing). Thank-you message, a confirmation email in both languages with an unsubscribe link, and a line on how the email is used. Every future email to this list must include owAuctionUnsubUrl()
- Unsubscribe: auctions.php?leave=ID&t=TOKEN asks first (so email scanners cannot unsubscribe by opening the link), then deletes the email from the list
- Protection: security token, hidden robot trap, 3 sign-ups per visitor per hour plus 60 an hour for the whole site (request_lib.php owReqLimited)
- Menu: "Auctions" / «المزاد» with a small "Soon" / «قريباً» tag, right after Gallery (desktop and ☰). Between 1101 and 1280px the menu is slightly more compact so everything still fits on one line
- Admin: admin.php → "Auctions" tab: the count, every email with its name, language and account, and "Export to CSV" (admin.php?auctions_csv=1; Arabic-safe, never a spreadsheet formula). A new sign-up joins the grouped admin email alerts (kind 'auction') and goes to Bashro Sales as a lead (kind quote, service "Oweili Auctions waiting list"; Bashro needs a name, so the part before @ is used when none is given)
- Ready for later: owAuctionsLive() in auction_lib.php is the one switch; once a real auction page (auction_room.php) exists and site_settings 'auctions_live' = '1', this address sends visitors there. No bidding or payment now. Gallery works marked "Auction" are unchanged
- Privacy Policy (draft): what is kept for the waiting list, that it is used only for auction news, kept until you unsubscribe, and that it goes to Bashro
- auctions_update.sql (one new table) was run on the live database on 3 Nov 2026 by a temporary page (since removed), after a full backup: .backups/db-u405344949_contact-20261103-050636.sql.gz
- Files: auctions.php, auction_lib.php, auctions_update.sql (new); nav.php, assets/css/nav.css (.nav-soon, compact menu), .htaccess, admin.php, alert_lib.php, request_lib.php, sitemap.php, privacy.php. Tables: auction_waitlist (new), activity_log

### Security review of the night's work (2 Nov 2026)
- An independent read-only review found no serious holes. Fixed: an artist's "Save draft" after "Send for approval" now withdraws the request (so the admin never approves a version they did not read); private commission photos must always be re-saved (never kept with GPS, WEBP saved as JPG if needed: owStoreImage $strict); commission steps only move from the status they were read in, and artist steps only for that artist (no mixed steps when two people act at once); a suspended artist loses access to commission photos; cancelled commissions count 30 days from the cancel itself; links in emails always use https://oweili.com, never the visitor's Host header; gift and commission forms also have a cap for the whole site per hour (30 gifts, 15 commissions), because the visitor address that config.php's clientIp() reads can be faked
- Left for the owner: commissions that stop at "requested / assigned / quoted" keep their photos until cancelled

### Sending to Bashro (website door, project 3) (2 Nov 2026)
- bashro_send.php sends to Bashro's receive-only door (https://bashro.com/in/), exactly as docs/website-door-guide.md in the Bashro project says: one signed JSON message (HMAC-SHA256 over site, time, nonce and the exact body; a new nonce each time), only the guide's fields, never passwords, card, ID or bank numbers. Sent after the visitor's page is delivered, so nobody waits and the visitor never sees Bashro's answer. On 429 / 503 it is tried again after 1 minute, then 5 (3 tries in all, private queue .logs/bashro_queue.json, removed once done). Only the kind and the status number are logged (bashro_send)
- ON (as kind "quote" → Bashro Sales): "I'm Interested" (name, email, phone, artwork title, a line with price and link, language), gift requests (occasion, the artwork or the custom brief, delivery date, recipient's CITY only, wrapping, Surprise, a link to admin_gifts.php), commission requests (subject, brief, budget, date, a link to admin_commissions.php; never the photos)
- Built but OFF until Bashro accepts these kinds (see bashro-new-kinds-prompt.md for the Bashro session): new artist / buyer sign-ups (kind "signup": role, name, email, phone, city, country, language, the account's public_id) → define('BASHRO_DOOR_SIGNUPS', true); approvals waiting (kind "approval": a unique ref, what, a title with no personal details, the admin link) → define('BASHRO_DOOR_APPROVALS', true). Bashro answers 400 to them today
- Nothing is sent until config.php on the server has define('BASHRO_DOOR_SITE', '3') and define('BASHRO_DOOR_KEY', '<64-character key from Bashro>'). Separate from bashro_api.php (the read-only pull connection, still off). Links in the messages always use https://oweili.com
- Settings (admin.php?settings=1): "Sending to Bashro" card (on/off per kind, how to switch on, Bashro's last answers). Privacy Policy (draft): the Bashro line now says what is sent and that commission photos and recipient addresses never are; gift and commission data and the 30-day photo deletion are listed
- Tested against a local copy of the door's rules: all three kinds answered 202, a forced 503 was retried and then accepted, the two OFF kinds send nothing while off
- Files: bashro_send.php (new), inquiry_lib.php, marketplace.php (language with the request), gifts.php, commission.php, alert_lib.php, register_artist.php, register_collector.php, theme_head.php (retry after a page), admin_settings.php, privacy.php. Tables: activity_log (status only)

### Payments and escrow — built, SWITCHED OFF and hidden (2 Nov 2026)
- order_lib.php: orders for artworks, gifts, commissions and course or workshop tickets (orders, order_events, order_complaints; payouts gains order_id and note). Steps: created → paid → shipped → delivered → released, or delivered → complaint → released / refunded; created → cancelled; paid → refunded. Any other jump is refused. Every change is an order_events row
- Escrow: Oweili holds the money. It is released to the artist when the buyer presses "I received it", or 7 days after delivery with no open complaint (owOrderAutoRelease, once a day after a page is delivered). The release makes a payout row for the artist: the amount minus Oweili's commission (site_settings 'commission_rate', 15 by default, before VAT; shown, not editable on screen). Course and workshop tickets pay no artist
- Buyer: my_orders.php ("I received it", "Something is wrong" = complaint, which holds the money and alerts the admin). Admin: admin_orders.php (Requests → Orders (off)): what is still needed, orders and their history, open complaints with "Release to the artist" / "Refund the buyer", payouts with "Mark paid" + bank reference
- Switched on ONLY when all are true (owPaymentsOn): define('OWEILI_PAYMENT_GATEWAY', 'moyasar' | 'tap' | 'hyperpay') in config.php with a connection file written and tested for that provider (not built), the commercial registration number set (OWEILI_COMPANY_CR), and site_settings 'payments_enabled' = '1'. Until then my_orders.php answers 404, owOrderCreate refuses, and no buy button exists anywhere. Settings (admin.php?settings=1) has a "Payments and escrow" card with the checklist (CR, provider, bank account, VAT / ZATCA decision, Terms and Privacy updated by a lawyer, final switch on)
- Files: order_lib.php, admin_orders.php, my_orders.php (new); admin_settings.php, admin.php, theme_head.php, alert_lib.php, gallery_lib.php, robots.txt. Tables: orders, order_events, order_complaints, payouts, site_settings

### Private commissions (portraits and personal photos), confidential (2 Nov 2026)
- commission.php («الأعمال الخاصة»): the buyer describes the piece (portrait, family, pet, place or memory, other; optional style, size, budget, date), adds 1 to 6 reference photos (JPG/PNG/WEBP, 10MB each; location and hidden data removed), confirms they may share them, and may agree IN WRITING that the finished piece can be shown (tick + typing their full name, saved with the date). Without that it is never shown anywhere
- Photos live in the PRIVATE folder .private/commissions/ (outside uploads/, blocked for visitors by .htaccess plus its own deny file, files readable only by the hosting account, kept by deploys through .gitignore). They open only through commission_file.php: the admin, the buyer with their private link, or the assigned artist after accepting the confidentiality agreement. Everyone else gets 404. Never cached or indexed. Each time the artist opens a reference photo it is logged
- The buyer gets a private link once (commission.php?ref=C-2026-XXXXXX&k=…; only its SHA-256 is stored) on the thank-you page and by email: the steps, accept the price or ask for another, approve the draft or ask for changes, see the finished piece, "I received it"
- Steps: request → admin assigns an artist (admin_commissions.php) → the artist accepts the confidentiality agreement inside the site (6 points in both languages, tick + typed name, saved with date, time and a monthly one-way address code in commission_nda) → the artist sends a price and days → the buyer accepts → the artist sends a draft → the buyer approves (or asks for changes) → the artist sends the final photo → the buyer ("I received it") or the admin marks it delivered. The admin can cancel with a reason. Emails go to the buyer and the artist at each step, alerts to the admin. The artist never sees the buyer's name, phone or email
- A commission is never an artwork row, so it never appears in the Gallery, a profile or the sitemap
- Reference photos are deleted automatically 30 days after delivery (or 30 days after a cancel), once a day after any page is delivered (theme_head.php → owCommCleanup), or now by the admin. Each deletion is logged (commission_photos_deleted); drafts and finals stay for the record
- No payment: the admin handles money by hand (private note field)
- Artist side: My Studio → Commissions tab, artist_commission.php?id=N
- Files: commission.php, commission_lib.php, commission_file.php, artist_commission.php, admin_commissions.php (new); artist_studio.php, admin.php, theme_head.php, alert_lib.php, marketplace.php, gifts.php, robots.txt, .gitignore (.private/). Tables: commissions, commission_files, commission_nda, users, activity_log

### Oweili Gifts: "Gift a painting instead of flowers" (2 Nov 2026)
- gifts.php («هدايا أويلي»): occasions (wedding, graduation, new baby, Eid, Ramadan, thank you, Teacher's Day), a ready artwork from the Gallery (the 12 newest available, or the one chosen with ?artwork=N) or a custom one (what it should be + optional budget), gift wrapping, card message, delivery date (3 days to 1 year ahead), "Surprise" (the recipient never sees the price), the recipient's name, phone (optional), city and address, and the buyer's name, phone and email (filled in for a signed-in buyer)
- For now it is a request: saved in gift_requests, an email alert to the admin (alert kind 'gift', grouped like the others), a "we received your request" email to the buyer in both languages, and the admin follows up by hand in admin_gifts.php (Requests → Gifts): every detail, status New / Contacted / In progress / Done / Cancelled and a private note. No payment
- Safety: security token, hidden robot trap, at most 3 requests per visitor per hour, only artworks a visitor can see in the Gallery. Buyer and recipient details are shown only to the admin
- Links: the main menu (Collect → Gifts), Gallery top ("Gift a painting instead of flowers" card, first page), "Give it as a gift" in the artwork window and on the artwork page, the footer (Gifts / الهدايا)
- Buyers see their gift requests (and private commissions, status only) in the Buyer dashboard → My requests
- Files: gifts.php, admin_gifts.php, request_lib.php (new); collector_dashboard.php, sitemap.php, marketplace.php, artwork.php, footer.php, admin.php, alert_lib.php. Tables: gift_requests, artworks, users, activity_log

### Gallery variety: filters, collections, Artist of the week, Verified artist (2 Nov 2026)
- Gallery filters (marketplace.php, "More filters" / «تصفية أدق»): style (all 50, grouped), medium (oil, acrylic, watercolour, ink, charcoal & pencil, mixed media, print; matched in the medium the artist typed), price range (under 500 / 500–1,000 / 1,000–5,000 / over 5,000 SAR), size by the longest side (small up to 40 cm, medium 40–100, large over 100), the artist's city (only cities with Gallery works), color family (red, yellow, blue, green, earth & brown, black, white, grey; from the paints chosen in the upload) and "Verified artists only". New sort: Most viewed. Every value is checked against a fixed list and kept in the web address
- Curated collections: made in admin_gallery.php (admin only, Content → Gallery): title and intro in both languages, optional rule (price up to / from, one art type) plus artworks picked by hand. Shown as a row of cards on top of the Gallery and at marketplace.php?col=<slug>. Hidden, never deleted
- Artist of the week: picked in admin_gallery.php with one line in both languages. Shown as a small frosted card in the corner of the homepage (under the buttons on phones; still one screen) and as a large band on top of the Gallery. Nothing shows when no one is picked
- Verified artist badge («فنان موثّق»): given or removed in admin_gallery.php → Verified artists after checking an artist. Shown on Gallery cards, the artwork window, the artwork page, the profile and the Artist of the week. Style in oweili.css (.vf-badge)
- Files: gallery_lib.php (new), admin_gallery.php (new), admin_shell.php (new: owIsAdmin, owAdminGate, shared admin frame), marketplace.php, index.php, artist_dashboard.php, artwork.php, admin.php, assets/css/oweili.css, night_update.sql. Tables: site_settings, collections, collection_items (new), users (is_verified_artist, verified_artist_at), artworks, artwork_colors, activity_log

### Artists write articles, with the admin's approval (2 Nov 2026)
- My Studio → "My articles" tab («مقالاتي») and my_articles.php: an approved artist writes Art Events, Artist Spotlights and Art Education articles (titles and text in English and Arabic, cover + up to 6 photos, event dates and place). "Save draft" keeps it private; "Send for approval" puts it in admin.php → "Waiting for approval" (the same pending_changes path as a sub-admin's request) and sends the admin email alert
- The admin reads it with "Preview" (article.php?slug=…&preview=1, team only, never indexed), then Approve (it is published; the artist gets "Your article is published" with the link) or Reject with a reason (the artist gets the reason and can edit and send again). Until then visitors get 404
- A published article is locked for the artist (they write to contact@oweili.com to change it), so live text never changes without the admin. Drafts and not-approved articles can be edited or deleted. At most 40 articles per artist
- Spotlights with photos need a photo consent (name, contact, how, date, optional private proof, a confirm box), written by the artist and checked by the admin; the existing rule still blocks publishing without it
- Public article page: "Written by <artist>" with a link to their profile; artist events say "Shared by an Oweili artist, checked by the Oweili team"
- No database change. Files: my_articles.php (new), artist_studio.php, approval_lib.php (owArticleOfArtist, article emails), admin.php, article.php, robots.txt. Tables: articles, article_images, photo_consents, pending_changes, activity_log

### Oweili's own email: contact@oweili.com (1 Nov 2026)
- One setting for the whole site: OW_CONTACT_EMAIL = contact@oweili.com in ow_paths.php, read through owContactEmail(). It can also be changed without a code change by adding define('OWEILI_CONTACT_EMAIL', '…') to config.php. contact@elitport.com is no longer used anywhere on the site or in its emails
- Where it is used: every admin email ("I'm Interested" requests, photo changes, the new alerts below); the reply address (Reply-To) of every email the site sends, so artists' and buyers' replies reach it; and what visitors see: footer (new: the address under the links), Support page, Privacy Policy, Terms of Use, the menu's "Contact Us", Explore's "Suggest a Style", account notice emails, the delete message. Emails are still sent from no-reply@oweili.com (the mailbox in config.php). Admin → Email check shows both addresses
- New admin email alerts (alert_lib.php), each item with a button that opens the right admin page: an artwork waiting for approval (new, or sent back after changes) and changes waiting for approval (artwork, profile, article) → admin.php#pending; a new artist sign-up → admin.php#pending; a new buyer sign-up → that buyer's page (admin.php?person=N). In English and Arabic
- Not flooded: the first alert is sent at once; alerts within 15 minutes of the last alert email wait and arrive together in one email ("N updates waiting for you"), sent by the next page anyone opens once the 15 minutes are over. Alerts are always sent after the visitor's page has been delivered, so nobody waits. Kept in the activity log (admin_alert, admin_alert_mail); a small file .logs/admin_alerts_due remembers when a waiting group is due. No database change
- The admin sign-in keeps the page an alert button opened (admin.php?person=N or #pending), so after signing in you land on it
- Fixed on the way (found by the test): since 31 Oct, signing up without the optional Arabic name could stop with an error, because an empty Arabic name was saved as "nothing" and the users table does not accept that. It is now saved as empty text. Files: register_artist.php, register_collector.php
- Test (1 Nov 2026): one email "Oweili admin email test" was sent to contact@oweili.com by a temporary page (since removed); Hostinger accepted it (smtp.hostinger.com:465), from no-reply@oweili.com, reply to contact@oweili.com
- Files: ow_paths.php, mail_lib.php (Reply-To), alert_lib.php (new), theme_head.php (sends a waiting group after a page), admin_lib.php, inquiry_lib.php, legal_lib.php, account_lib.php, artist_dashboard.php, support.php, nav.php, explore.php, footer.php, assets/css/oweili.css (.ow-footer-mail), mail_check.php, admin.php (sign-in keeps the page), upload_artwork.php, edit_artwork.php, approval_lib.php, register_artist.php, register_collector.php. Tables: activity_log (read and written), users (names only)

### Emails sent the elitcv.com way (1 Nov 2026)
- Compared with the EliteCV2 / ElitCV projects (elitcv.com, whose emails reach the inbox). elitcv.com sends with PHPMailer 7.0.2 (mailer.php): smtp.hostinger.com, SMTPAuth, SSL, port 465, UTF-8, From "ElitCV" <noreply@elitcv.com>, an HTML body, English-only subjects, no Reply-To. Oweili used its own hand-written SMTP code, the From name "Oweili · أويلي" and mixed English + Arabic subjects
- Now Oweili sends the same way: PHPMailer 7.0.2 (the same files, copied to lib/PHPMailer/ with its licence) with the same settings, From "Oweili" <no-reply@oweili.com>. Kept on top (they only help): the plain-text version (AltBody), the company footer, Reply-To contact@oweili.com. The own SMTP code stays as a back-up if PHPMailer cannot send
- Admin emails (to contact@oweili.com) now have plain English subjects like elitcv's: "Oweili - New artist sign-up: …", "Oweili - 3 updates waiting for you", "Oweili - New inquiry: …", "Oweili - Photo changed: …". Emails to artists and buyers keep their two-language subjects (brand rule)
- Results (1 Nov 2026, temporary test page since removed): the admin alert sent with PHPMailer scored 10/10 on mail-tester (SPF, DKIM d=oweili.com and DMARC pass; not blocklisted; X-Mailer PHPMailer 7.0.2; plain text + HTML). The same email was sent to contact@oweili.com and accepted by Hostinger
- The elitport.com project is not in the GitHub repositories this session can reach, so it was not compared
- Files: mail_lib.php (owSendWithPhpMailer), lib/PHPMailer/ (new), alert_lib.php, admin_lib.php, inquiry_lib.php. Tables: none

### Email deliverability check (1 Nov 2026)
- Measured, not guessed (a temporary key-protected page on the live server, since removed, read DNS, blacklists, mail-tester and the sending mailbox):
  - DNS (Hostinger, nameservers dns-parking.com): SPF "v=spf1 include:_spf.mail.hostinger.com ~all", DKIM published for hostingermail-a (2048-bit; -b and -c empty), DMARC "v=DMARC1; p=none", MX mx1/mx2.hostinger.com. Exactly the same as elitcv.com
  - Hostinger signs every email with DKIM (d=oweili.com, s=hostingermail-a). SPF, DKIM and DMARC all pass and match oweili.com, both at mail-tester and at Hostinger's own mail servers
  - Blacklists: the sending servers (Hostinger → MailChannels relay) are on none of the 20 lists mail-tester checks; Mailspike rates them "good / very good"
  - mail-tester.com: the admin alert scored 10/10 (before the changes below; only note: HTML without a plain-text version), and the "Confirm your email" message scored 10/10 after them
  - Test emails sent to the sending mailbox (no-reply@oweili.com) were filed in its Inbox, not Junk, by Hostinger
  - Domain registered 5 Jul 2026 (elitcv.com: 1 Apr 2026)
- Changes on our side (mail_lib.php): every email now has a plain-text and an HTML version (multipart/alternative, quoted-printable), From "Oweili" <no-reply@oweili.com> (was "Oweili · أويلي" for a few hours), Reply-To "Oweili" <contact@oweili.com>, a correct Date and a unique Message-ID on oweili.com, plain-text emails get a clean branded HTML version, and every email ends with the same footer: Oweili, Elitport Limited, Jeddah, Saudi Arabia, contact@oweili.com, https://oweili.com and a service-email line. The commercial registration number line appears once OW_COMPANY_CR (ow_paths.php) or OWEILI_COMPANY_CR (config.php) is set. Company name and city are OW_COMPANY_NAME / OW_COMPANY_CITY in ow_paths.php
- Kept sending from no-reply@oweili.com: the score is already 10/10, and sending from contact@ would need that mailbox's password in config.php and would make admin emails "from" and "to" the same address
- No DNS change was needed. Still open: why contact@oweili.com files them as spam (needs the full headers of one such email from that mailbox)

### Certificate of Authenticity — live (switched on 1 Nov 2026)
- Every approved artwork gets a digital Certificate of Authenticity with a permanent number like OW-2026-7KQ4-M9XD (no 0/O or 1/I, easy to type). It is made automatically the moment the admin (or a sub-admin) approves the artwork
- Artist declaration: the last upload step ("Details & price") has a box the artist must tick: "I confirm this artwork is my original work, made by my own hand." / «أؤكد أن هذا العمل من إبداعي الأصلي، وقد صنعته بيدي.» The artwork cannot be sent without it (checked on the server). The date and time are saved (artworks.declared_at) and in the activity log (artwork_declare)
- Artwork details, asked in the upload and in edit_artwork.php when missing: medium (free text, with suggestions), width × height in cm, year made, and type: "Original, one of a kind" or "Limited print" with its number (e.g. 3 of 50). Arabic digits are understood. Artworks without them (or without the declaration) are approved as before but get their certificate only once the details are added; the admin sees what is missing
- The certificate (certificate.php?no=…): A4 landscape, one page, English and Arabic side by side, always light (also in dark mode): number, title, artist, medium, size, year, type, art style, the main photo, the 25/50/100% photos and the "✦ A Real Touch" badge when they exist, the artist's declaration and its date, the rights statement, "Issued by Oweili" with the date, a seal, and a QR code to the verify page. "Download PDF" opens the print window (choose Save as PDF; on phones Share / Print). Only its artist, the team and later its owner can open it; others are asked to sign in or get "not found"
- The QR code is made on our own server by qr_lib.php (written for Oweili, standard QR code, no outside service). It was checked against the standard's worked example and read back by a separate test reader
- The certificate keeps the details as they were approved (a snapshot). When an approved change to the details, or a "Looks fine" on a new photo, goes live, the certificate shows the new details with the same number
- Public verification page: oweili.com/verify (cert_verify.php, through .htaccess; verify.php stays the email-confirm page). Type a number or scan the QR code: "This certificate is real" with the photo and details, "Cancelled: this certificate is no longer valid" with the date, or "Certificate not found" for unknown or fake numbers. Small letters and spaces are fine. Buyer details are never shown. At most 30 checks a minute per visitor
- Rights statement on the certificate, the artwork page and the verify page: "The buyer owns the physical artwork. The copyright stays with the artist." / «يملك المشتري العمل الفني نفسه، وتبقى حقوق النشر والملكية الفكرية للفنان.»
- Where it shows: artwork page (artwork.php) shows the details, a "Certified authentic" badge with the number that opens the verify page, and the rights statement. My Studio has a "Certificates" tab (view and download, verify page) and each artwork says what it still needs for its certificate. Admin panel has a "Certificates" tab (admin.php#certs): every certificate with search, View, and "Cancel…" with a required reason; approved artworks still without a certificate and what they miss ("Make certificate" when ready). Each artwork waiting for review shows the declaration, the details and whether a certificate will be made
- certificates_update.sql was run on the live database on 1 Nov 2026 by a temporary page (since removed), after a full backup: .backups/db-u405344949_contact-20261101-074330.sql.gz. It only added the new artwork columns and the certificates table; no other table changed
- Never deleted: a certificate can only be cancelled (status, date, reason, who). Deleting an artwork cancels its certificate ("Artwork deleted by the artist"), so its number shows "Cancelled"
- Ready for later (Sprint 4): certificates.owner_user_id / owner_name / owner_added_at and owSetCertificateOwner() put the buyer's name on the certificate after a sale. Not used yet. No commercial registration number, VAT or contracts yet
- Activity log: cert_issue, cert_update, cert_cancel, cert_check (the verify page count, with the monthly one-way address code only)
- Files: cert_lib.php, qr_lib.php, certificate.php, cert_verify.php, cert_fields.php (the details form), certificates_update.sql (new); upload_artwork.php, edit_artwork.php, admin.php, admin_lib.php (owApproveArtwork, owPhotoOk), approval_lib.php (details wait for approval on live artworks), artist_studio.php, artist_dashboard.php (delete), artwork.php, assets/css/oweili.css (form, badge, --cert-* colours), .htaccess, robots.txt, sitemap.php. Tables: certificates (new), artworks (new columns medium, width_cm, height_cm, year_made, edition_type, edition_number, edition_size, declared_at), artwork_stages, users, activity_log

### Nothing goes public without admin approval — live (switched on 31 Oct 2026)
- Everything an artist adds or changes waits for the admin before visitors see it. Until then visitors see the old approved version, or nothing if it is new
  - New artworks: wait as before (is_approved = 0)
  - New artist accounts: the public profile (artist_dashboard.php?id=N) stays hidden (404) until the account is approved
  - Changes to a live artwork (titles, descriptions, price, art type, for-sale status) in edit_artwork.php: kept aside; the live artwork does not change. Exception kept on purpose: a new PHOTO on a live artwork still goes live straight away and alerts the admin (see "Photo changed, needs a look")
  - Changes to an approved artist's public profile in edit_profile.php (photo, names, bio, city, country, art styles, links): kept aside; the new photo is saved but not shown. The phone number is private, so it saves straight away
  - Articles and events: a sub-admin pressing "Published" saves a draft and asks the admin. The admin (admin.php password or an admin account) publishes directly. Edits by the team to an already published article stay live (only the team can write articles)
  - Changes to things that are not public yet (an artwork in review, a new account) and buyer profiles (never public) save directly
- Admin: admin.php → "Waiting for approval" / «بانتظار الموافقة» (admin.php#pending, also the overview box): New artist accounts, Changes to live things (a table: What / Now (visitors see) / Asked for, with the new photo), and New artworks with their Proof of Process. Approve or Reject; a reject needs a reason. The artist gets an email in both languages either way (with the reason). A spotlight with photos is only published with an active photo consent
- Artist side: the edit pages say that changes wait; while one waits, the form shows the waiting version and saving again replaces it (only one waiting change per thing). My Studio shows "waiting for approval" or the last reason a change was not approved, and a message after saving
- Every step is in the activity log (change_submit, change_approve, change_reject, user_approve, user_reject)
- approvals_update.sql was run on the live database on 31 Oct 2026 by a temporary page (since removed), after a full backup: .backups/db-u405344949_contact-20261031-200326.sql.gz. It only added the pending_changes table; no other table changed. (Without the table, everything works as before and the admin list says so)
- Files: approval_lib.php (new), approvals_update.sql (new), admin.php, edit_artwork.php, edit_profile.php, admin_articles.php, artist_studio.php, artist_dashboard.php, platform_lib.php. Tables: pending_changes (new), artworks, users, articles, photo_consents, activity_log

### Names exactly as typed (sign-up)
- The "82" next to Azeem's name came from the old sign-up form's Arabic name box, not from his email. Sign-up (artist and buyer) now has its own optional "Your name in Arabic" box, saves exactly what was typed, and refuses a name made only of numbers or symbols, in both languages. Edit Profile checks the same
- Everywhere a name is shown (menu, dashboards, admin, artist profile, welcome email), a name without letters is skipped (owRealName in platform_lib.php), so Azeem shows as "Azeem" until he types a real Arabic name. The live site had only his account, so no other account needed a fix
- Files: register_artist.php, register_collector.php, edit_profile.php, platform_lib.php (owRealName, owNameProblem), nav.php, admin.php, admin_person.php, collector_dashboard.php, artist_studio.php, verify.php. Tables: users

### One style for the language and light / dark buttons
- Every page prints the two buttons with one helper, owCornerControls() in theme_head.php, and one style in oweili.css (.ow-ctls / .ow-ctl): both 40px tall, same border, corners, font and an 8px gap, lined up together. Used by nav.php (all public pages, sign-in, sign-up, buyer and artist pages) and every staff page (admin, sub-admin, articles, institutes, person, settings, photo check, email check, error log). The check pages can now switch to Arabic too
- New pages must use owCornerControls(), never their own buttons

### "Photo changed, needs a look" (live artworks)
- When an artist changes the photo of an artwork that is live in the Gallery (approved, in edit_artwork.php; only artworks without stage photos can change their photo), the artwork stays live and:
  - an email goes to Oweili's admin address (contact@oweili.com) right away with the old and new photo side by side and a link to the admin panel
  - the admin panel shows it: an overview box "Photo changed, needs a look" and a "Photo changes" tab (admin.php#photos) with Before / Now photos, "Looks fine" and "Hide artwork…" (a reason is required)
  - "Looks fine" closes it and deletes the kept old photo. "Hide artwork" takes it out of the Gallery (not approved, with the reason) and the artist gets an email with the reason and sees it in My Studio; editing it sends it back to review
- The old photo is kept on the server until then, so the two can be compared. Works that are not live (in review or not approved) change their photo as before and go through the normal review
- No database change: the list is kept in the activity log (photo_change, closed by photo_ok or the hide)
- Files: edit_artwork.php, admin_lib.php (owPhotoChanged, owPhotoChangesPending, owPhotoOk), admin.php, platform_lib.php. Tables: artworks, activity_log

### Google and sharing
- robots.txt: search engines may read every public page; private areas (admin, dashboards, studio, edit pages, account links, the Bashro connection, consent proofs) are kept out. Names the sitemap
- sitemap.xml (made fresh by sitemap.php, through .htaccess): the main pages, the article lists, published articles, every artwork visitors can see (artwork.php?id=N) and every approved artist's profile, with the date each last changed. Never drafts, hidden or suspended accounts
- Every public page now has a title and description in both languages and link previews (WhatsApp, X, Facebook, LinkedIn: title, description, picture), from one helper: owSeo() in seo_lib.php. New public pages should use it. Pages without their own picture use assets/img/share.jpg (the logo mark, OWEILI and the tagline in both languages, 1200×630)
- Artwork page (artwork.php?id=N): the link to share. Big photo, titles, artist, type, "✦ A Real Touch" badge, price, description, Creation Journey, heart, "I’m Interested" (opens the artwork's window in the Gallery) and "See more in the Gallery". The preview shows the artwork photo. The Gallery window has a "Share this artwork" link (phone share sheet, or copies the link)
- marketplace.php?show=N opens that artwork's window even when it is not on the current Gallery page. Fixed on the way: links from Community, the Buyer dashboard and sign-in only opened the window when the artwork happened to be on the first page
- Files: seo_lib.php, sitemap.php, robots.txt, artwork.php, assets/img/share.jpg, .htaccess, marketplace.php, community.php, collector_dashboard.php, and every public page head. Tables read: artworks, users, articles

### Clean-up (31 Oct 2026)
- Old events: the live events table was empty (0 rows), so there was nothing to move. A temporary clean-up page that could move them (into published Art Events articles, after a full backup) was used and removed straight after. events.php now redirects to Art Events
- Admin panel: the old Art Styles, Events and Training tabs were removed, because nothing on the site reads those tables any more (styles come from assets/js/art_styles.js, events are articles, institutes and courses are in admin_institutes.php). The tables themselves are kept. Shortcut tabs to Articles & events and Institutes took their place
- uploads/deploy_test/ (a test file from 30 Oct) deleted on the server
- Kept on purpose: Photo check, Email check and Error log (useful admin tools, linked from Settings). config_setup.php was left alone at the time (owner's rule) and removed later, on 31 Oct, at the owner's request

### Speed on phones
- Measured on the live site with a phone on a slow connection: pages show their first content in about 1 to 1.5 seconds and weigh about 90 to 220 KB (pictures sized for the screen, files compressed and kept for 7 days, Gallery pictures below the first 4 load only when scrolled to)
- The background video (bg.mp4, about 3 MB) was the heaviest part. Now: phones on a slow connection (2G / 3G), with Data Saver on, or visitors who ask for less motion never download it (the soft page colour shows instead). Since 4 Nov 2026 only the homepage has it. The homepage starts it at once (class bg-now), because the video is the hero there
- The font server is contacted early (preconnect), so text in DM Sans and Tajawal shows sooner
- Files: theme_head.php (video and preconnect, for every page), index.php and every page with the background video. Tables: none

### Friendly 404 page
- 404.php: "This page has wandered off" / «هذه الصفحة غير موجودة», a short line, "Back to the Gallery" and "Home" buttons, in English and Arabic. Used for every missing address (.htaccess ErrorDocument) and by artwork.php for works that are not in the Gallery. Works from any address depth (it sets a base address so its links never break). Tables: none

### Privacy Policy and Terms of Use — DRAFT, under review
- privacy.php («سياسة الخصوصية») and terms.php («شروط الاستخدام»): full pages in English and Arabic, written to fit the Saudi Personal Data Protection Law (PDPL). Both show a "Draft, under review" banner on top. A list of sections at the top jumps to each part
- Privacy: who we are, Oweili as the middle party (buyer details never go to artists), what we collect (names, phones, emails, passwords as one-way codes, profile and artwork photos with GPS removed, requests, favorites, activity with a monthly one-way address code, consent records, browser storage), why, who can see it (public profile, team, Hostinger, Bashro, authorities), how long we keep it, PDPL rights, how to delete (email from the account address, done within 30 days), security, under-18s, changes
- Terms: using Oweili, middle party (no sales outside Oweili, "I’m Interested" is not a purchase), accounts (18+ or with a guardian), human-made art only and real 25/50/100% photos, artwork rights stay with the artist, prices in SAR and the 15% commission before VAT, conduct, removal, liability, Saudi law (Arabic version applies)
- Linked from: the footer (Privacy · Terms), the Support menu (Terms of Use now opens terms.php), the Support page cards, both sign-up forms ("By joining you agree to…") and the "I’m Interested" form
- The keeping periods (requests 2 years, activity 12 months, backups 90 days, consents 5 years after) are proposals for the owner and a lawyer to confirm. Automatic clean-up for them is not built yet
- Files: privacy.php, terms.php, legal_lib.php (shared layout), footer.php, nav.php, support.php, register_collector.php, register_artist.php, marketplace.php, assets/css/oweili.css (.agree-note). Tables: none

### Admin dashboard upgrades — Phase 3 of 4 built
- Overview on top of admin.php: Artists, Buyers, Artworks (and how many are in the Gallery), Interest requests (and how many are new), New sign-ups this week, Waiting for review, Online now
- Artists and Buyers tabs: a search box (name, email, phone, city), Approve / Suspend (was "Ban"; suspended accounts' works leave the Gallery), and "View" → admin.php?person=N: every detail of the person (admin only), status, approve / suspend, their artworks with status and views, interest in their works (full buyer details), requests they sent, favorites and their activity
- All artworks tab: every artwork with its status, reason and views, search, Mark as sold / Back to available, Approve, and "Take down…" with a reason
- Not approved (admin and sub-admin): the reviewer must write a reason; the artwork is kept (hidden from the Gallery) so the artist can fix it. Before, "Reject" deleted the artwork and its photos for good. The artist gets a short email for approved and not approved works (with the reason)
- Inquiries tab: status (New / Contacted / In progress / Closed) and a private note per request, links to the buyer account and the artist. Buyers see the status in their dashboard, never the note
- Content: Articles (events, spotlights, art education) as before, plus the new Institutes tool (admin_institutes.php): add and edit institutes (names, cities, descriptions in both languages, contact, website, logo, shown or hidden) and their courses (titles, dates, price, seats). Nothing is deleted, only hidden. Also linked for sub-admins
- Settings (admin.php?settings=1): what is switched on, links to Error log / Email check / Photo check / Articles / Institutes, the database backups on the server, and the Bashro connection status with how to switch it on
- Every staff action is in the activity log (admin sign-in, approve, not approved, sold, suspend, request status and note, institutes and courses)
- Layout (31 Oct 2026): a staff bar on top like the site menu — OWEILI · Admin on one side; language, dark mode and Log out in the corner, apart from the tools. Below the title, the tools sit in one tidy grouped row: Content (Articles, Institutes) · Checks (Photo check, Email check, Error log) · Site (Settings). On phones the groups stack, two buttons per row. Every admin label is now in both languages (Price, Joined, Last sign-in, SAR, art type names, Approve / Set back to pending were English only)
- Files: admin.php, admin_lib.php, admin_person.php, admin_settings.php, admin_institutes.php, subadmin_dashboard.php, platform_lib.php. Tables: users, artworks, artwork_inquiries, favorites, activity_log, institutes, courses (no database change needed)

### Bashro connection — Phase 4 of 4 built, switched OFF
- bashro_api.php: a private, read-only data connection for Bashro. Off by default: every request gets 404 until config.php on the server has define('BASHRO_API_ENABLED', true) and define('BASHRO_API_KEY', '…32+ random characters…') (optional: define('BASHRO_API_IPS', 'address,address')). The key goes in the Authorization header (Bearer), never in the web address
- Read only (GET): resource=summary | people | artworks | inquiries | favorites | activity, with since= (changed on or after) and after= (next page), up to 500 rows a page. Every record carries its permanent public_id, a status, created_at and updated_at. At most 120 requests a minute; every read and every refused key is in the activity log
- Admin → Settings shows whether it is on. Tables read: users, artworks, artwork_inquiries, favorites, activity_log

### Artist dashboard upgrades — Phase 2 of 4 built
- My Studio (artist_studio.php, «مرسمي»): the artist's private area, linked from their dashboard ("My Studio" button). 4 number boxes (Artworks, Views, Favorites, Interested buyers) and 4 tabs: My artworks (every work with its status: Draft / In review / In the Gallery / Not approved + the reason / Sold, and its views, favorites and "I'm Interested" count, never buyer names, phones or emails), Account (email choices, email, password), Activity (what they did and when), Sales & payouts (sold works, sales total, payouts list; structure only until payments open in Sprint 4)
- A work that was not approved shows the reason on My Studio and on the dashboard card. When the artist edits it, it goes back to review (reason cleared)
- Views: counted when a visitor opens the artwork window in the Gallery, once per visitor per artwork per day, never the artist's own looks (view.php)
- Interest notice: when someone presses "I'm Interested", the artist gets a short bilingual email that someone is interested, with no buyer details (Oweili is the middle party). They can turn it off in My Studio → Account
- Profile (edit_profile.php): photo, name, bio EN/AR, city, phone, social links (as before) plus country and art styles (up to 8 of the 50 styles; shown on the public profile with links to Explore Art Styles). Social links must now be real web addresses (http/https); "@name" becomes the full Instagram or X address. Before, any text was saved and shown as a link
- Activity log: artwork sent for review, edited, deleted, profile and email-choice changes
- Files: artist_studio.php, view.php, artist_dashboard.php, edit_profile.php, edit_artwork.php, upload_artwork.php, inquiry_lib.php (owNotifyArtistOfInterest), marketplace.php (view count), platform_lib.php (owSafeLink, owStyleChoices, action words), account_lib.php. Tables: users (country, art_styles, notify_inquiries, notify_news), artworks (view_count, rejected_reason, rejected_at), favorites, artwork_inquiries, payouts, activity_log (no database change needed)

### 3-stage artwork upload (proof of process) — built, needs database_update.sql
- What it does: artists start a new artwork from their dashboard and go through 5 steps: 1 titles (Arabic + English required), 2 photo at 25% (required), 3 photo at 50% (optional, can skip), 4 photo at 100% (required) + colors used + art type (all 50 encyclopedia styles as guided cards with their drawn picture, under 6 group labels: Traditional, Digital, Islamic & Arab Heritage, Contemporary, Nature & Portrait, Photography), 5 price in SAR + the 15% commission note, then "Submit for Review"
- Every step is saved straight away, so the artist can come back days later. Unfinished work is a private draft (artworks.is_draft = 1): only the artist sees it (dashboard → "Drafts in progress" → Continue). Admins and sub-admins only see it after it is submitted
- After submit: is_draft = 0, is_approved = 0 (waits for admin approval as before), image_url = the 100% photo, so the marketplace keeps working
- Photos: taken with the phone camera (camera opens directly on phones; on computers a file picker opens instead). Max 10MB, JPG/PNG/WEBP only, checked on the server. Saved to uploads/artworks/stages/ with random names. Each photo is re-saved: the phone's rotation is applied and hidden data such as GPS location is removed (privacy). taken_at = the camera's own date if present, otherwise the upload time
- Retaking a photo replaces the old one and deletes the old file. Deleting or rejecting an artwork also deletes its stage photos
- Photo lock: in edit_artwork.php, artworks with stage photos cannot change their main photo (blocked on the server too). A bilingual note explains why. Title, description, price, type and status can still be edited. Descriptions are optional when editing, like in the upload
- Files: upload_artwork.php, artwork_lib.php, artist_dashboard.php, edit_artwork.php (drafts are sent back to the upload flow), admin.php + subadmin_dashboard.php (drafts hidden from the review list), art_types.php (added mixed_media, digital_art, islamic_geometric), assets/css/nav.css
- Tables: artworks (new column is_draft), artwork_stages, artwork_colors — all created by database_update.sql. Until that file is run, the upload page shows a friendly "being set up" message and every other page keeps working
- Hosting needs: PHP upload_max_filesize and post_max_size of at least 10M on Hostinger (now 64M / 128M through .user.ini)

### Gallery redesign (marketplace.php) — live (approved by the owner)
- What it does: only approved works that are available or at auction, by artists who are not banned (no drafts). Search box (titles in both languages), 6 category pills: All / Traditional / Digital / Islamic Heritage / Contemporary / Photography («الكل / تقليدي / رقمي / تراث إسلامي / معاصر / تصوير»), a small Status row (All / Available / Auction) and a Sort menu: Newest First (default) / Price: Low to High / Price: High to Low
- Filters, search and sort now run on the server, so they cover every page of results, not just the 12 on screen. Every choice is kept in the web address (shareable) and when switching language. Every value is checked against a fixed list; all reads use prepared statements
- Links from Explore Art Styles (?type=oil) still work: they show that one type, light up its category and add a small removable chip
- Cards: picture 4:3 filling the frame, title 15px bold, artist name, navy art type pill, "✦ A Real Touch" / «✦ لمسة حقيقية» badge when the 25% and 100% photos exist, price, full-width navy "See the Story" button. Lift on hover, soft fade-in (off for visitors who reduce motion). 4 columns desktop, 3 tablet, 2 phone
- Empty: "No artworks here yet. Be the first to share yours." / «لا أعمال هنا بعد. كن أول من يشارك عمله.» + "Join as Artist" (and "See all artworks" when a filter is on)
- Banner: text only, no hand emoji
- Category list: art_categories.php, built from each type's encyclopedia group (Nature & Portrait styles sit in Traditional; Islamic & Arab Heritage = Islamic Heritage). Old types: vector art, 3D art and digital art in Digital; mixed media in Contemporary; photography in Photography; sculpture and other under All only
- Files: marketplace.php, art_categories.php. Tables read: artworks, users, artwork_stages, artwork_colors

### "I'm Interested" (Gallery popup) — built
- What it does: in the artwork window, under the price, a pink "I’m Interested" / «أنا مهتم» button opens a small form: Full Name, Phone Number, Email Address (all required) and the note "We use your details only to contact you about this artwork." On send: "Thank you! We will contact you soon." / «شكراً! سنتواصل معك قريباً.» Error messages in both languages
- Each inquiry is saved and emailed to Oweili's admin address (contact@oweili.com) (artwork title, artist name, price, link, buyer name, phone, email). If the email fails, the inquiry is still saved and shows in admin
- Safety: security token on the form, a hidden trap field for robots, at most 5 inquiries per visitor per hour, the same email on the same artwork within a day is saved once, and only artworks a visitor can see in the Gallery are accepted (checked on the server)
- Admin: admin.php → "Inquiries" tab (admin.php#inquiries): date, artwork, artist, buyer name, phone (tap to call), email (tap to write), newest first
- Files: marketplace.php (button, form, script), inquire.php (receives the form), inquiry_lib.php (table, checks, saving, email, list), admin.php (Inquiries tab), mail_lib.php (sending). Tables: artwork_inquiries (id, artwork_id, artist_id, buyer_name, buyer_phone, buyer_email, created_at), created automatically with CREATE TABLE IF NOT EXISTS the first time it is used (owner's request); reads artworks, users
- Removed on the way: a leftover debug block in marketplace.php that logged failed pictures to the browser console

### Creation Journey (marketplace) — built, needs database_update.sql
- What it does: in the artwork details window, buyers see "Creation Journey · Proof It Was Made by Hand" / «رحلة الإبداع · دليل الصنع اليدوي» (renamed 4 Nov 2026) with the stage photos in order (25% The Beginning, 50% Taking Shape if uploaded, 100% The Finished Piece), the date each was taken, the colors used and the art type
- The "✦ A Real Touch" / «✦ لمسة حقيقية» badge sits under the title (the same badge as everywhere else) — only on artworks that have the 25% and 100% photos
- Old artworks without stage photos show exactly as before (no journey, no badge)
- Every card in the marketplace grid also shows the "✦ A Real Touch" / «✦ لمسة حقيقية» badge when the artwork has the 25% and 100% photos
- Phone: the photos are one row you swipe sideways; tablet and desktop: side by side. Arabic runs right to left
- Only approved artworks on the current page are loaded — unapproved or draft photos are never sent to visitors
- Files: marketplace.php, artwork_lib.php (owJourneyHtml), assets/css/proof.css. Tables: artwork_stages, artwork_colors

### Creation Journey on artist profiles — built, needs database_update.sql
- What it does: on an artist's public profile (artist_dashboard.php?id=N), every artwork that has the 25% and 100% photos shows the "✦ A Real Touch" / «✦ لمسة حقيقية» badge, a strip of small stage photos (25% / 50% / 100%), and a "See the creation journey" / «شاهد رحلة الإبداع» link that opens the full journey with dates, colors and art type. Old artworks without stages show as before
- Anyone can now open a public profile, even without logging in (the marketplace links to it). Visitors only see artist accounts that are not banned, and only approved artworks. Earnings, drafts, edit and delete stay owner-only; your own dashboard (no ?id) still needs login
- Files: artist_dashboard.php, artwork_lib.php (owJourneyHtml), assets/css/proof.css. Tables: artwork_stages, artwork_colors

### Proof of Process (admin review) — built, needs database_update.sql
- What it does: every pending artwork in admin.php and subadmin_dashboard.php shows "Proof of Process" / «دليل المراحل»: each stage photo (click to open full size) with date and time, the time gap from the previous stage (e.g. "3 days after stage 25%"), "Artist skipped middle stage" when 50% is missing, colors, art type, and the review note
- Warnings to help the decision: "⚠ Less than an hour after the previous stage" and "⚠ Dated before stage X%"
- Note for reviewers: the date comes from the camera's own clock when the photo has one, so it can be wrong if the phone's clock is wrong
- Only admins (password gate) and sub-admins (logged in with that role) can open these pages
- Files: admin.php, subadmin_dashboard.php, artwork_lib.php (owProofPanelHtml), assets/css/proof.css. Tables: artwork_stages, artwork_colors


### Homepage (index.php) — one screen
- What it does: a single full-screen hero over the background video (bg.mp4, covers 100% of the screen) with a soft dark shade rgba(0,0,0,0.15) on top. Badge "✦ A Real Touch" / «✦ لمسة حقيقية»; headline "Everything here started with real hands" (was "…human hands" until 4 Nov 2026) / «كل ما تراه هنا، بدأ بيدٍ حقيقية» in one dark color (56px desktop, 36px phone, weight 300); subtitle "Every piece here was made by real hands. And you can see how." / «كل قطعة هنا صنعتها يد حقيقية. وتستطيع أن ترى كيف.» (18px, max 520px); two buttons "Explore Gallery" (marketplace.php) and "Join as Artist" (register_artist.php). No other sections, no footer, no scrolling, no dashes
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
- Number boxes come from the database: "Artists With Us" counts artist accounts that are not banned, "Artworks in Gallery" / «عمل فني في المعرض» counts the works a visitor can see in the Gallery (approved, available or auction, finished, artist not banned) and links to marketplace.php. Over 10 shows the number with "+" (e.g. 52+), 10 or less shows the plain number. "Art Styles Covered" / «أسلوب فني موثّق» is counted from assets/js/art_styles.js (now 50) and links to explore.php. "Saudi Community" stays a label
- Daily Inspiration / «إلهام اليوم»: the 3 newest approved, for-sale artworks by artists who are not banned (no drafts). Each shows the photo (whole artwork, never cropped), title and artist name in the page language, and opens that artwork in the Gallery (marketplace.php#artwork-N opens its details window). Pictures only from uploads/ or https
- Fewer than 3 artworks: the empty spots say "Be the first to share your work here." / «كن أول من يشارك عمله هنا.» with a "Join as Artist" / «انضم كفنان» button to register_artist.php
- Phone: one card per row. All reads use prepared statements; if the database is unavailable the page still shows with 0 and the invitation cards
- Files: community.php, marketplace.php (#artwork-N link, kept through the Arabic redirect), artwork_lib.php (owBi, owNotDraftSql). Tables read: users, artworks

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
- What it does: "Explore Art Styles" / «استكشف أساليب الرسم». Filter pills with counts: All 50 / Traditional 15 / Digital 8 / Islamic & Arab Heritage 7 / Contemporary 10 / Nature & Portrait 6 / Photography 4 (navy when active). Equal square cards (4 columns desktop, 3 tablet, 2 phone): a drawn cover, the style name and one feeling word. Hover: lift + thin pink border. Closing line: "Fifty styles. Fifty stories. Every one began with a hand." / «خمسون أسلوباً. خمسون حكاية. كلها بدأت بيد.»
- The window (max 760px, full screen on phones; close top right, top left in Arabic, Escape or tap outside) has 10 parts: name + feeling, one-line essence, The Story Behind It («حكاية الأسلوب», 3 paragraphs), Key Techniques (hover or tap explains), Signs of a Real Hand («علامات اليد الحقيقية», numbered), Tools & Materials, Difficulty & Time (Beginner / Intermediate / Advanced, time for one artwork, who it suits), Famous Artists (name, nationality, dates, contribution), Collector's Guide (3 tips), From Our Gallery
- From Our Gallery: the newest approved, for-sale artwork of that type by an artist who is not banned (one prepared database read in explore.php; pictures only from uploads/ or https). If there is none yet: the drawing, an invitation to artists, and the pink button. Button: "See [style] in Gallery" → marketplace.php?type=…. Styles with no Gallery type yet open the whole Gallery
- Gallery types: every style is now its own Gallery type (type = style id), so every "See [style] in Gallery" button opens exactly that style. Artworks saved under the old shared types (photography, digital_art, 3d, mixed_media, vector_art, sculpture, other) still show in the Gallery, but not in a style window
- Shared links open a style directly: explore.php#calligraphy. Unknown links are ignored
- Content: assets/js/art_styles.js (about 400KB, 120KB compressed; loaded after the page shows). Famous artists' names and dates must be double-checked by the team before launch (a list of doubts was given to the owner)
- Generative Art and NFT Art are written only as human-made work (the artist writes the rules; an NFT is an ownership certificate). No mention of AI anywhere
- Cover pictures are simple SVG drawings in the brand colors, in assets/js/art_covers.js (shared with the upload form; window.OW_ART_COVER). Their colors (.ill classes) live in oweili.css, so they follow light and dark mode. They can be replaced later with real artworks
- Files: explore.php, assets/js/art_styles.js, assets/js/art_covers.js, assets/js/explore.js, assets/css/discover.css, assets/js/discover.js, marketplace.php (?type=). Tables read: artworks, users

### Artist Chat Room — removed
- The chat room is gone from the site. "Artist Chat Room" / «غرفة محادثة الفنانين» is not in the menu, the Community dropdown, the ☰ menu or any page. Leftover hidden chat words and styles were removed from community.php, support.php, login.php, forgot.php, verify.php, edit_artwork.php and artist_dashboard.php
- chat.php now only redirects to community.php
- The messages table (set up in setup.sql as the chat room's messages) is not changed, and no page reads it any more. The "Messages Shared" box on Community was replaced by "Artworks in Gallery"
- Files: chat.php and the pages above. Tables: none changed

### Menu and homepage links — Sprint 3
- Discover menu (desktop and ☰): Art Events, Artist Spotlights, Art Institutes (see Navigation Structure)
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

### Page transitions (every page)
- Since 4 Nov 2026 (see "Fast page changes"): the browser's own soft cross-fade between pages (@view-transition in oweili.css, 0.18 s; Chrome, Edge, Safari 18.2+; off for visitors who ask for less motion). The old 0.2 s fade-out wait before every page change and the 0.3 s fade-in (pages started invisible) were removed
- Files: assets/css/oweili.css. Tables: none

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
- Artworks: .aw-card — every artwork image 4:3 on a white background with a thin border (the whole artwork shows, never cropped), artist name 13px, price 16px bold. The badge (.human-badge, "✦ A Real Touch" / «✦ لمسة حقيقية») is identical on every page: same class, same text, printed with owBi()
- Stage photos: artwork_lib.php owStagesHtml() draws 25% / 50% / 100% as same-size squares (120px; smaller on cards and drafts) joined by a dashed line; a missing stage shows a dashed grey square. Used by the marketplace journey, artist profile cards, drafts and the reviewer proof panel
- Dashboards: .dash + .role-artist / .role-collector / .role-institute / .role-subadmin / .role-admin set the role color; .stat-grid + .stat-card (3px top border in the role color); .table (alternating rows); .tabs / .tab. Admin and sub-admin panels now open with stat cards. There is no institute dashboard yet — the role color is ready for it
- Menu: 64px tall, bigger logo mark (four pink quarters + OWEILI), links 13px, pink underline on the current page (also marked in the ☰ menu)
- Removed: every hardcoded Helvetica Neue, the copied menu styles on 17 pages, page-by-page button/input/card copies, the gradient pink-to-blue heading text, and inline color styles
- Files: assets/css/oweili.css, nav.css, proof.css, discover.css, nav.php, footer.php, artwork_lib.php (owStagesHtml), and every page
- Tables: none

## Shared Files
- .htaccess — blocks public access to private files: CLAUDE.md and all .md files, all .sql files, skills-lock.json, hidden folders (.claude/, .agents/, .git/, .backups/, .logs/), log and backup files (.log, .bak, .old, .gz, .zip and similar) and the old resha-app/ and supabase/ folders. Visitors get 403 Forbidden. Keep it when changing hosting settings
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
- ow_paths.php — owBase() (web address of the site's folder, "/" on Hostinger) and owAsset('assets/css/x.css') (full CSS/JS address with its version number). Loaded by theme_head.php and artwork_lib.php, so every page has them. Use them for every link to our own files
- bg.mp4 — the background video, now in the GitHub project (next to index.php), so it deploys to Hostinger with every save to main. 8 seconds, 1280×720, H.264, about 2.9MB, with its index at the front of the file so phones can start playing before it has fully downloaded. Only the homepage loads it, from /bg.mp4 (theme_head.php skips it on slow connections, see Speed on phones). To replace it, keep the same name and keep it small (under 10MB)
- img.php — makes a smaller copy of an uploaded photo for the screen: img.php?f=uploads/...&w=400|800|1600 (?src= works the same, for hand-typed tests). Each copy is made once and kept in uploads/thumbs/ (JPG, made with GD, phone rotation applied). Only files inside uploads/ (never uploads/consents/ or uploads/thumbs/, checked again after the real path is found), only JPG/PNG/WEBP and only those 3 widths; anything else gets 404. Any file name works, including names with dots or spaces (the first version refused those, so phones still got the huge original). Made with GD; if GD is missing or the photo is too big for the memory, Imagick is used when the server has it. If a copy cannot be made, the original is sent. owImgSized($src, $w) in artwork_lib.php builds these addresses; owArtImgHtml() uses them with srcset (400 / 800) on Gallery and profile cards, the Gallery details window uses 1600, Community and Explore 800, stage photos and collector thumbnails 400. Opening a stage photo still shows the original
- img_lib.php — makes the phone-sized copies (400 / 800 / 1600 wide, always JPG, so older iPhones that cannot show WEBP still see them) and keeps them in uploads/thumbs/. Only one heavy copy is made at a time on the server (a lock file), so a phone opening a Gallery page of new photos cannot run the hosting out of memory; photos are opened straight from the file to use less memory. Copies are made right after an artwork photo is saved (stage photos and edit_artwork.php). Once a copy exists, owImgSized() links it directly as a normal picture file (/uploads/thumbs/….jpg), so the server does no work at all when phones view the Gallery. Used by img.php, artwork_lib.php and photo_check.php. Tables: none
- photo_check.php — "Photo check" button in admin.php (admin.php?photo_check=1, admin password needed; opening the file directly shows nothing). Shows whether the server has GD / Imagick, the memory limit, whether uploads/ and uploads/thumbs/ can be written, and the 8 newest artworks: saved photo address, file size and dimensions, and a live test of the phone copy ("Small copy works" or what failed). Tables read: artworks. Also has a "Make all small copies" button: makes the copies for every artwork photo and stage photo, one at a time (press again if it says some are still waiting). Tables read: artworks, artwork_stages
- mail_lib.php — sends every site email (password reset, account verification, welcome) with owSendMail(). Every email: plain text + HTML, From "Oweili · أويلي", Reply-To contact@oweili.com, company footer (see "Email deliverability check"). It signs in to the Hostinger mailbox itself (SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, optional SMTP_FROM / SMTP_FROM_NAME in config.php), tries port 465 and 587, then falls back to the old sendEmail() in config.php, then PHP mail(). Emails come from the mailbox address (Hostinger refuses any other). Failures go to the PHP error log with the mail server's answer, never the password. Tables: none
- mail_check.php — "Email check" button in admin.php (admin.php?mail_check=1, admin password needed; opening the file directly shows nothing). Shows the mailbox settings (never the password), whether the password-reset columns exist, and a "Send test" box that shows the mail server's exact answer. Tables read: users (column list only)
- platform_lib.php — shared pieces for the dashboards and Bashro: owHasColumn / owHasTable (pages keep working before a database update), owLog() activity log, owUserStatusSql / owArtworkStatusSql, favorites, inquiry status words, owSafeNext (safe return page after sign-in), owSignupLimited, the bilingual confirmation email. Tables: activity_log, favorites
- account_lib.php — a signed-in person's own account changes: owChangePassword, owChangeEmail, owResendVerify (with limits and notice emails). Used by the Buyer dashboard; the Artist dashboard will use it next. Tables: users, sessions, activity_log
- dashboards_update.sql — the database parts for the dashboards (approved 30 Oct 2026). Only adds columns, tables and indexes; safe to run more than once
- error_guard.php — loaded at the top of config.php on the live site. Visitors never see PHP errors, database errors or file paths: they get a short friendly page in English and Arabic ("We will be right back" / «سنعود حالاً»). The real error is written to the private log .logs/php-errors.log (blocked for visitors, kept by deploys through .gitignore, starts a fresh file at 5MB). Tables: none
- error_log_view.php — "Error log" button in admin.php (admin.php?error_log=1, admin password needed; opening the file directly shows nothing). Shows the newest 150 lines of the private log, newest first. Tables: none
- config_setup.php — removed (31 Oct 2026). It was used once to turn debug mode off; the owner then saved the new, separate database and email passwords in config.php through Hostinger File Manager. One safety copy of the original config.php is kept in .backups/ (private)
- email_test.php and smtp_test.php — removed (test pages are no longer needed; the Email check in admin.php covers it)
- .user.ini — PHP settings for the website, read by Hostinger's PHP from public_html: upload_max_filesize 64M and post_max_size 128M (bigger artist photos and videos), and errors hidden from visitors (display_errors Off, log_errors On). Takes up to 5 minutes to apply after a deploy. Blocked from visitors by .htaccess (all dot files). If hPanel → Advanced → PHP Configuration shows smaller numbers, set the same values there too
- .backups/ — database backups made on the server (for example .backups/db-u405344949_contact-20261030-200442.sql.gz, the full backup made before the 30 Oct database check). Visitors get 403 (.htaccess blocks every dot folder, and the folder has its own deny file); deploys keep it (.gitignore). To download: hPanel → Files → File Manager → public_html → turn on "Show hidden files" → .backups → right-click the file → Download. To restore: phpMyAdmin → the database → Import → choose the .sql.gz file (it re-creates every table with its rows)
- robots.txt / sitemap.php / seo_lib.php / 404.php — see "Google and sharing" and "Friendly 404 page" under Features
- index.html — tiny forwarding file only: sends old links and bookmarks to index.php. Do not put content in it

## Database
- DB name: u405344949_reshaart
- Tables: users (artists, collectors and admins), messages, art_styles, plus migration tables from SQL files
- The live database may use u405344949_contact — confirm in phpMyAdmin before any database change
- config.php is NOT in GitHub (contains DB and SMTP secrets and the admin password hash — lives on server only)
- Admin password: stored only as a hash in config.php as define('ADMIN_HASH', '...'); admin.php reads it from there. If it is missing, the admin login refuses with a setup message. Changing the hash signs out every admin

## SQL Migration Files
Multiple .sql files exist for setup, roles, profiles, purchases, financial, phone, reset, styles, and discover_setup.sql
- database_update.sql — the 8 new tables for the 5 pillars (safe to run more than once). Already in place on the live database (checked 30 Oct 2026)
- certificates_update.sql — the Certificate of Authenticity: new artwork columns (medium, size, year, original or print, declaration time) and the certificates table. Only adds; safe to run more than once. Already in place on the live database (1 Nov 2026)
- approvals_update.sql — the pending_changes table for "Nothing goes public without admin approval". Only adds one table; safe to run more than once. Already in place on the live database (31 Oct 2026)
- auctions_update.sql — the auctions waiting list (auction_waitlist). Only adds one table; safe to run more than once. Run on the live database 3 Nov 2026 after a full backup
- night_update.sql — collections, Artist of the week and settings (site_settings), Verified artist, gift requests, private commissions, orders and escrow (switched off). Only adds; safe to run more than once. Already in place on the live database (2 Nov 2026, full backup first: .backups/db-u405344949_contact-20261102-215015.sql.gz)
- dashboards_update.sql — the dashboards and Bashro parts (permanent IDs, dates, request status, favorites, activity log, empty shipping and payout tables). Safe to run more than once. Already in place on the live database (30 Oct 2026)

## Design System
- Style: Luxury white minimal
- Background: Video (bg.mp4) with white overlay on the homepage; the soft page colour on every other page
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
- Order (owner's choice): Gallery · Collect ↓ · Explore Art Styles · Discover ↓ · Community · Support ↓ (Arabic, right to left: المعرض · اقتنِ · استكشف أساليب الرسم · اكتشف · المجتمع · الدعم). The ☰ phone menu uses the same order
- Collect ↓ / «اقتنِ» → Gifts / «هدايا» (gifts.php), Commissions / «طلب لوحة خاصة» (commission.php), Auctions / «المزاد» (auctions.php, with a small "Soon" / «قريباً» tag until real auctions open). The top word opens gifts.php
- Gallery / «المعرض» → marketplace.php, no dropdown (never call it "Marketplace" / «السوق» on the site)
- Explore Art Styles → explore.php, no dropdown
- Discover ↓ → Art Events / Artist Spotlights (articles_list.php?type=...) / Art Institutes (institutes.php, its own page; training.php redirects there). Art Education was taken out of the menu because Explore Art Styles covers it; its page (articles_list.php?type=art_education) still works and is reachable from the type buttons on the articles pages
- Community → community.php (direct link, no dropdown; Meet Fellow Artists, Share Your Work and Learn Together were removed because they led nowhere specific)
- Support ↓ → Contact Us (email) / How It Works (support.php#how) / Terms of Use (terms.php)
- Dropdown icons: one thin outline style only (set once in nav.css on .d-icon, never filled). New icons must be outline drawings

## Known Issues
- Fixed: the Arabic menu order. A CSS line on 18 pages flipped the menu back to left-to-right; it was removed. Never add `flex-direction:row-reverse` to the menu for Arabic — dir="rtl" already puts items in the right order
- Fixed in Sprint 2: the last `[dir="rtl"] ... row-reverse` lines in page sections were removed. Do not add them back
- Fixed: the menu showed twice after the design-system merge, because browsers kept an old saved copy of nav.css. Every CSS and JS link now carries a version number (since 4 Nov 2026 a short fingerprint of the file's content, so a deploy that only re-copies files does not change it). Links are written with owAsset() from ow_paths.php, e.g. href="<?= owAsset('assets/css/nav.css') ?>", which prints /assets/css/nav.css?v=41315294. When a file's content changes, the number changes and every browser loads the new copy. Any new page must link its CSS and JS files the same way
- Fixed: the background video could stay invisible when the browser already had it ready (e.g. saved from an earlier visit). Every page now also checks whether the video is ready straight away
- Fixed: the Gallery (marketplace.php) never switched its background video on, so the page looked plain white instead of the soft video background. theme_head.php now switches on every video.bg-video on every page as soon as it can play. The page background itself (--color-bg) has always been white; the soft look comes from the video
- Fixed: on the live site the Gallery and artist profiles showed no colors or background, broken pictures ("?") and "by 82". Causes and fixes: (1) CSS, JS, the background video and pictures are now linked from the site's own folder with owBase() / owAsset() (ow_paths.php). On Hostinger the site sits at the top of public_html, so this gives "/assets/...", "/bg.mp4", "/uploads/...". It also keeps working if the site is ever moved into a folder, or a page is opened with extra text after its name. Never type a path to our own files by hand. (A short-lived change to "assets/..." without the first slash broke every page's styles on the live site and was replaced by this.) (2) Artwork pictures go through owImgSrc() in artwork_lib.php: "uploads/...", "/uploads/...", "./uploads/...", a full server path or an oweili.com web address all become "/uploads/..."; pictures on other sites are kept (http upgraded to https); a bare file name is looked for in uploads/artworks/. The browser always tries the real photo, and owArtImgHtml() swaps in a drawn placeholder only if it fails to load (never a broken "?"). An earlier version only showed photos it could find on the server itself and hid the rest behind the placeholder; that was too strict and was relaxed. Every page that shows artwork pictures uses them. (3) owArtistNameSql() skips any name made only of digits (an artist name saved as "82"), then uses the next name; with no name at all it shows "Oweili Artist" / «فنان أويلي»
- Fixed for phones: artwork photos in the Gallery and on artist profiles. (1) The picture frame uses CSS aspect-ratio, which iPhones on iOS 14 and older do not know, so the frame could collapse to zero height; oweili.css now falls back to the classic 4:3 padding frame on those phones. (2) The first 4 cards load their photos straight away (fetchpriority high) and only cards further down wait (lazy). (3) A photo that fails once, for example on a weak phone connection, is tried one more time before the drawn placeholder shows. Files: artwork_lib.php (owArtImgHtml), marketplace.php, artist_dashboard.php, assets/css/oweili.css
- Fixed: artwork photos showed on computers but not on phones (grey placeholder). Root cause: every card loaded the full camera original (often 4000x3000, 3 to 10MB each, up to 100MB for one Gallery page); phone browsers give up on such pictures when memory or the connection runs short, and the card then showed the placeholder. Cards now load a copy sized for the screen through img.php (typically 20 to 80KB). If that copy fails, the original is tried once before the placeholder shows
- Fixed: some artists got "Invalid email or password" with the right password. login.php now (1) finds the account whatever the capitals or stray spaces in the email (sign-up, login and password reset all store and compare emails in small letters), and (2) also accepts passwords saved the old way (plain text, MD5, SHA1, SHA256 — from the old site or typed into phpMyAdmin) or a hash with a stray space or line break. An old-style password is replaced by a proper hash the first time it matches. Typing the stored code itself never works. Files: login.php (owPasswordMatches), register_artist.php, register_collector.php, forgot.php. Tables: users (password column updated only for old-style saves)
- Fixed: password reset (and sign-up) emails did not arrive. All emails now go through mail_lib.php (see Shared Files), which talks to the Hostinger mailbox directly, tries both mail ports, sends from the mailbox address, and logs the exact reason when sending fails. The reset email is now in English and Arabic, and its 1-hour limit is counted by the database clock, so a time-zone difference between PHP and MySQL can no longer make a fresh link expire at once. Files: mail_lib.php, mail_check.php, admin.php, forgot.php, register_artist.php, register_collector.php, verify.php. Tables: users (reset_token, reset_expires)
- Fixed: artist sign-up was hard on phones (register_artist.php). (1) The video is now optional, marked "Optional / اختياري"; if one is sent it must be valid. (2) MOV videos (what iPhones record) are accepted as well as MP4. (3) When the files together were bigger than the server allows, the server threw the whole form away and the page said "Security check failed". It now says clearly that the files are too large, with the limits, in English and Arabic. A real expired page says so too. (4) Every upload field shows its limit (photo, each artwork photo, video, and all files together). The limits are read from the server settings, so they always match Hostinger (video at most 50MB). (5) The phone checks the sizes before sending, so nothing large is uploaded for nothing. Files: register_artist.php. Tables: users (art_video can now be empty)
- Fixed for phones (photos must always show): (1) every artwork picture now has a list of fallbacks. If the copy fails, the phone tries the 400 copy, then the same copy made fresh by img.php, then the original photo, and only then a drawn picture. It never shows a broken image or an empty box. This covers Gallery cards, the artwork popup (which used to just hide its picture), stage photos, Community, Explore and collector thumbnails. Helpers: owImgFallbacks(), owImgFallbackAttrs(), owImgPlaceholder(), OW_IMG_ONERROR in artwork_lib.php. (2) A copy a page needs but does not have yet is made in the background right after the page is sent (owQueueThumb / owMakeQueuedThumbs, only when the server can finish the page first, so nobody waits). Nobody has to press anything. (3) .htaccess and img.php tell Hostinger's CDN not to re-shrink or convert our pictures (no-transform), and tell the server's page cache never to keep pages, so phones never get an old copy of a page. Computers get exactly the same copies as before. Files: artwork_lib.php, marketplace.php, community.php, explore.php, assets/js/explore.js, collector_dashboard.php, img.php, .htaccess
- Found and fixed (30 Oct 2026): uploaded photos were being deleted by Hostinger's automatic deploy. Proof from the live server (a temporary read-only check page, since removed): every push to main makes Hostinger re-copy the project from GitHub (the server's git record shows a fresh "clone" each time), and every file that is not in GitHub was removed, unless it is on the .gitignore list. config.php (on the list) kept its old date; uploads/ (not on the list) was gone, so every artwork, stage and profile photo returned 404. uploads/ is now on .gitignore. Test: a small file put in uploads/deploy_test/ survived the next deploy and the one after. Rule: anything that lives only on the server (uploads, config.php) must be listed in .gitignore, or the next push deletes it. Photos uploaded before this fix are lost and must be uploaded again (or restored from a Hostinger backup)
- Fixed (31 Oct 2026): the admin overview showed 0 artists although "Azeem" has 2 artworks in the Gallery. Cause: the live site had one account (#1, Azeem) and its account type was "admin", not "artist", so it was not counted as an artist and his artist dashboard sent him away. After a full database backup (.backups/db-u405344949_contact-20261031-064738.sql.gz), a temporary page (since removed) changed only that account's type to artist (approved). Nothing was deleted; his password, email and artworks are unchanged. The admin panel itself never depended on that account type (it uses the admin password). 0 buyers was correct (no buyer accounts). His Arabic name is saved as "82" (not shown on the site); he can correct it in Edit Profile
- Fixed (31 Oct 2026): deleting an artwork could fail with no message. Azeem's two artworks (Mona Lisa, Scribble Portrait) were still in the live database after he pressed Delete, with nothing in the activity log and no error (checked with a temporary read-only page, since removed). The delete itself works; the request was stopped before it reached the database, and the page then either said nothing or showed a bare "Invalid CSRF token" page. Now (artist_dashboard.php): an old or expired page says "This page was open for a long time, so nothing was changed. Please press Delete again." in both languages; a signed-out artist is sent to sign in and back; a successful delete says "The artwork is deleted"; a refused one says so. Every try is in the activity log (artwork_delete, or artwork_delete_fail with the reason). A deleted artwork is removed from the database (so it leaves the dashboard, profile, Gallery, share link and admin counts), with its photo, stage photos, favorites and any waiting change. Deleting never waits for approval. Files: artist_dashboard.php, platform_lib.php. Tables: artworks, artwork_stages, favorites, pending_changes, activity_log. On 1 Nov 2026, at the owner's request, both artworks were deleted on the server by a temporary page (since removed), after a full backup: .backups/db-u405344949_contact-20261101-054022.sql.gz. Their photo files were already missing on the server (lost in the 30 Oct deploy problem). The live site now has 0 artworks
- The site is auto-deployed from GitHub main via Hostinger — anything saved to main goes live straight away, so test on a working branch first. Each deploy re-copies the project and removes files that are not in GitHub, except those listed in .gitignore (config.php, uploads/). Never take uploads/, config.php, .backups/ or .logs/ off .gitignore

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
- Site words (owner, 4 Nov 2026): visitor text, emails and page titles never use "human", "humans", «بشر», «بشري» or «إنسان». The tagline is "the home of real art" / «بيت الفن الأصيل» (footer, email footer, certificate). Say "real hands", "made by hand", "handmade", "an artist's own hand" instead. («بشرة» = skin and «بشرو» = Bashro are other words and stay.) Code names such as .human-badge stay, visitors never see them

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

### 27b. Test Level by Risk (standing rule, owner's choice)
- Choose how much to test by how risky the change is, and always say which level was used and why
- Low risk = quick checks only: text and wording, static pages, menus, the styles of a single page, sitemap / robots, cleaning up files on the server. Quick checks mean: the project's wording / lint checks, every link on the changed pages, and screenshots at phone and desktop width in English and Arabic. No full test run
- Core = full test run: login and accounts, payments, uploads, saving data, the shared layout or shared code used by many pages (for example nav.php, footer.php, theme_head.php, oweili.css, artwork_lib.php, config-related code), and anything a customer pays for or downloads
- A small fix after a full run: rerun only the checks that fix can affect, and explain why the rest are not affected
- Never run two heavy test runs at the same time. After each run, close the test browsers and test servers and delete the temporary test folders

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
