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
- explore.php — Art Styles: an art encyclopedia (50 styles shown: painting and drawing) with a rich window for each (see Features)
- marketplace.php — "The Gallery" / «المعرض»: browse and buy artworks (bilingual). The file keeps its old name; visitors only ever see "Gallery"
- studio.php — retired: it only sends visitors to explore.php (permanent redirect). Its workshop ideas moved to training.php
- community.php — Community page: real counts and the newest artworks (see Features). HIDDEN FOR NOW, to bring back later (6 Nov 2026, Admin → Settings)
- chat.php — retired: the Artist Chat Room was removed. The file only sends visitors to community.php (permanent redirect), so old links and bookmarks still work
- support.php — Support and FAQ
- events.php — retired: sends old links to Art Events (articles_list.php?type=event) for good (301). The old events table was empty on the live site (checked 31 Oct 2026)
- art_map.php — Art Map («خريطة الفن», menu «وجهات», 12 Nov 2026): Museums, Art Galleries and Institutes tabs with a filter by the 13 regions (see Features); places in the art_places table. admin_art_map.php — the admin's tool for its museums and galleries
- art_place.php — the Art Map's pages for Google: /art-map/<region>/<place> and /art-map/<region> (13 Nov 2026); place_claim.php — "Is this your place?" form
- institutes.php — retired address since 12 Nov 2026: sends old links to art_map.php?tab=institutes for good (301)
- training.php — retired address: sends old links to the Art Map's Institutes tab for good (301), keeping ?city and the language
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
- assets/js/art_styles.js — Shared data file for all art styles (single source of truth), bilingual. art_types.php reads the style names from it (77 styles, 50 shown)
- art_types.php — the art type list: the styles (names from art_styles.js; hidden ones marked legacy, OW_MORE_STYLES) plus older types kept only so existing artworks keep their label (marked legacy; not offered in the upload or edit forms, except an artwork's own current type when editing)
- assets/js/art_covers.js — the drawn picture of each style, shared by explore.php and upload_artwork.php
- assets/js/art_photos.js + assets/img/styles/ — the real artwork shown on each Art Styles card and window, with its credit and licence (7 Nov 2026)

## Features

### New Art Education and Art Events drafts (14 Nov 2026, owner)
- 5 Art Education drafts (not published), English and Arabic, each with its own free cover painting and a "Picture: …" / «الصورة: …» credit line: How to start your first art collection (Fitz Henry Lane, Brace's Rock, public domain); What a signed original painting means (Monet, Impression, Sunrise, public domain); Choosing paintings for your living room (Kandinsky, Composition VIII, public domain); Art as a gift: how to choose a painting for someone (Derain, Mountains at Collioure, public domain); Color in Saudi art: from Al-Qatt Al-Asiri to today (Rijal Alma painted pillar, photo Richard Mortel, CC BY 2.0). Covers copied from assets/img/styles/ to uploads/articles/
- 2 new Art Events drafts from official sources: Highlights Exhibition at Ithra Winter (Dhahran Expo, 13 to 28 Nov 2026, ithra.com) and AlUla Arts Festival 2027 (15 Jan to 6 Feb 2027, experiencealula.com). Along the Way, Misk Art Week 2026 and Made With Your Magic were already drafts (11 Nov), so they were not added again
- Saved on the live database 14 Nov 2026 by a temporary locked page (removed straight after), after a full backup (.backups/db-u405344949_contact-20261114-124650.sql.gz). Admin → Waiting for approval → "Article drafts from the Oweili team" (Preview, Edit, Publish). Tables: articles

### "I received the artwork" with private photos (14 Nov 2026, owner, later the same day)
- Replaces the plain "Received" button. The collector presses "I received the artwork" / «استلمت العمل» (Buyer dashboard → Purchases links to my_orders.php?o=N, where the form opens) and must add 1 to 3 photos of the artwork as received (OW_RECEIPT_MAX), with the note "Photograph the artwork only, please, with no people and no personal details" / «صوّر العمل فقط من فضلك، بدون أشخاص أو تفاصيل شخصية», the required tick "The artwork arrived in good condition" / «وصل العمل بحالة سليمة» and the optional tick, off by default, "Oweili may share my photo of the artwork" / «أوافق أن تنشر أويلي صورة العمل». Without the good-condition tick the collector is pointed to "Something is wrong"
- Photo safety (owOrderConfirm in order_lib.php → owStoreImage strict): real image check (content and picture size), JPG / PNG / WebP only, 10 MB each, at most 3 (also checked on the server), turned upright and re-saved so location (GPS) and camera data are removed (checked on the test copy: no GPS left). Saved in the private folder .private/receipts/ (own deny file, 0600, kept by deploys through .gitignore). The receipt is recorded in the activity log (order_receipt: photo names, good = 1, share = 0/1); no database change
- Private: receipt_photo.php?o=ORDER&n=0..2 opens a photo only for the admin and the artist of that order; everyone else (visitors, the collector, other artists) gets 404; the folder itself answers 403
- Sending it sends the payout request to the owner straight away; Admin → Waiting for approval → "Artist payouts waiting for your approval" shows the photos next to the request, the good-condition line and whether the collector agreed to sharing. The artist sees the photos in My Studio → Sales & payouts ("Your artworks as the collectors received them")
- No receipt: the payout request goes to the owner automatically 7 days after delivery with no complaint (OW_PAYOUT_ASK_DAYS = 7, was 2); the one reminder email (1 day after delivery) stays, now asking for "I received the artwork" with a photo. A complaint still freezes the payout
- Wording: Terms (Buying an artwork: a new line on "I received the artwork", the photos and the 7 days; the artists' payment line: "with a photo of the artwork (or 7 days after delivery with no complaint)"), Privacy (What we collect: "Receipt photos": why we keep them, who sees them, location removed, shared only with the tick), "What you get" on Join as an Artist, and the admin notes (Settings, Orders, Courses and tickets). Terms and Privacy "Last updated" 14 November 2026
- Tested on the test copy (payments switched on there only): no photo / no tick / a fake image / 4 photos refused and nothing saved; 2 photos saved without GPS; request sent at once; photos shown in Admin and My Studio; 404 for visitors, the collector and another artist; day 6 = reminder only, day 8 = request sent automatically; complaint = frozen
- Files: order_lib.php, my_orders.php, receipt_photo.php (new), collector_dashboard.php, admin.php, artist_studio.php, admin_settings.php, admin_orders.php, admin_bookings.php, terms.php, privacy.php, register_artist.php. Tables: orders (confirmed_at), activity_log (no structure change)

### Artwork payout rule: the owner approves every payout (14 Nov 2026, owner)
- Artworks only (courses and museums unchanged). The fixed 7-day wait is gone (OW_ESCROW_DAYS removed). Now (order_lib.php):
  - The collector presses "Received" / «تم الاستلام» (Buyer dashboard → Purchases, and my_orders.php). This never pays the artist: it saves confirmed_at and sends a payout approval request to the owner (owOrderConfirm → owOrderPayoutAsk: activity_log order_payout_request, admin alert kind 'payout', link admin.php#payouts)
  - No "Received": one reminder email to the collector 1 day after delivery (OW_PAYOUT_REMIND_DAYS, order_received_reminder); 2 days after the order was marked delivered with no complaint (OW_PAYOUT_ASK_DAYS; release_due_at now holds this time) the request goes to the owner anyway. Daily by owOrderPayoutChecks() (owOrderAutoRelease() keeps its name for theme_head.php and only calls it; it never releases)
  - A problem reported by the collector (order status complaint) freezes the payout: it never appears in the waiting list until the owner resolves it in Admin → Orders (release or refund)
  - Admin → Waiting for approval → "Artist payouts waiting for your approval" (#payouts, owOrderPayoutWaiting(), counted in the waiting total): "Approve the payout" releases the order and makes the artist's payout row. owOrderMove() refuses 'released' from anyone but the admin
  - Ready for Bashro approvals later: the alert kind is 'payout'; Bashro has no type for it yet, so nothing is sent there
  - No database change (uses orders.confirmed_at, release_due_at and the activity log). Everything stays switched off until payments are switched on
- Wording updated in both languages: Terms (artists' payment line), Join as an Artist "What you get" ("You are paid after the collector confirms receipt and Oweili approves the payment."), Admin → Settings → Payments and escrow, Admin → Orders, Admin → Courses and tickets (artwork rule card), my_orders.php
- Tested on the test copy (payments switched on there only): Received does not pay; the request reaches Admin; the reminder email is sent once; 2 days after delivery the request comes by itself; a complaint freezes it; only the admin can release; Approve made the artist's payout (1000 SAR − 15% = 850 SAR)
- Files: order_lib.php, my_orders.php, collector_dashboard.php, admin.php, admin_orders.php, admin_settings.php, admin_bookings.php, alert_lib.php, terms.php, register_artist.php, theme_head.php (comment only). Tables: orders, payouts, order_events, activity_log (no structure change)

### Course booking and museum tickets — built, HIDDEN behind one switch (14 Nov 2026, owner)
- One switch: Admin → Settings → "Hidden for now" → "Course booking and museum tickets" (site_settings bookings_on; gallery_lib.php OW_HIDDEN_PARTS 'bookings'). While off: every new public page answers 404 (a signed-in admin gets a preview), the Art Map shows nothing new (no ticket buttons, no course links, institutes that joined through the sign-up stay off the Art Map, region pages, sitemap and place_claim.php: owSignupInstSql() / owBookableCourseSql() in artmap_lib.php), and the Terms do not show the booking rules. Checked on the test copy: with the switch off, every public page is the same as before
- Institutes: register_institute.php (account type institute, waits for the owner's approval; its row in institutes has via_signup = 1 and is_active = 0 until approved). institute_dashboard.php: Courses (add / edit / hide; every new or changed course waits for approval), Registrations (student name, phone, email), Earnings and payouts, Profile (description, phone, website, payout details). Only its own data. collector_dashboard.php sends institute and museum accounts to their own dashboards (dashboardUrl() in config.php sends them there)
- Courses (new columns, bookable = 1): title and short description EN/AR, in person or online, level, what students learn EN/AR, materials included, start and end dates, days and times EN/AR, total hours, price (0 = free), seats (registration closes when full or once the course has started), location EN/AR (in person) or a meeting link (online; shown only to registered students and in their email), language, suitable age. course.php?id=N: the public page with "Register and pay" (collector accounts, sign-in needed). A seat is held 30 minutes while paying (owBookingCleanup gives expired holds back)
- Museums: Admin → Art Map → each museum → "Tickets": Partner switch, Oweili's commission % (per museum), payout rule (days after the visit / once a month / by hand), agreement note, official tickets link (for non-partners; else the website), museum account email (makes a museum account and sends a "choose your password" email). Ticket types (name EN/AR, price, how many people it lets in) and dates (from / to, open days, tickets per day) are set by the owner (admin_bookings.php?setup=N) or by the museum (museum_dashboard.php → Ticket types and dates; shared code owMuseumSetupPost / owMuseumSetupHtml). Art Map and place pages: "Buy ticket / اشترِ تذكرتك" (partner with types and dates) or "Official tickets / التذاكر الرسمية" (others, if we have a link). ticket_buy.php?p=N → payment → email ticket with a QR code (ticket_qr.php makes the PNG; ticket.php?k=… shows it). The QR opens ticket_check.php: the museum account (or the admin) sees valid / already used / another date and "Mark as used" (once); anyone else sees nothing about the buyer. museum_dashboard.php: Tickets (by date), Scan (camera with BarcodeDetector, or type the ticket number), Ticket types and dates, Earnings and payouts
- Payments (pay_lib.php): TEST MODE ONLY. pay.php (choose), pay_test.php (Oweili's own test checkout: "Pay (test)" / "Payment fails (test)"), pay_return.php (asks the provider again before marking paid; also the provider's notify call). Prepared for Moyasar (hosted invoice page: mada, cards, Apple Pay), Tabby (pay in 4, KSA host api.tabby.sa) and Tamara (sandbox api-sandbox.tamara.co): each shows only once its TEST keys are in config.php on the server (OWEILI_MOYASAR_SK; OWEILI_TABBY_PK, OWEILI_TABBY_SK, OWEILI_TABBY_MERCHANT; OWEILI_TAMARA_TOKEN). Keys without "test" are refused. Not tried against the providers yet (no keys). Refunds: test = recorded; providers through their refund calls
- Owner, 14 Nov 2026 (later the same day): Oweili's own test payment is offered ONLY to the signed-in admin (owPayGateways(), owPayTestApprove() and pay_test.php check owIsAdmin()); a visitor never sees it, and with no provider keys the payment page says "Online payment is not open yet." To test, sign in on admin.php and as the collector in the same browser. sitemap.php adds every bookable, approved, active course (course.php?id=N) by itself while the bookings switch is on, none while it is off. The artwork payout rule was changed the same day (see "Artwork payout rule: the owner approves every payout")
- Money and the three payout rules (kept apart): artworks stay in order_lib.php (unchanged, switched off); courses: course commission (Admin → Settings → "Course commission", site_settings course_commission, 10 by default), due to the institute on the course start date; museums: each museum's own commission and rule. Institute and museum payouts live in partner_payouts (never the artists' payouts table); Admin marks them paid with the transfer reference. Cancel (Admin) gives the seat or tickets back, refunds the payment and cancels the payout
- Booking and refund rules (owBookingRules in booking_lib.php, draft for a lawyer to check): courses refundable until the day before the start, tickets until the day before the visit if unused, used / started = not refunded (E-Commerce Law Art. 13), full refund if the institute or museum cancels or the course starts more than 15 days late (Art. 14), clear information before paying (Art. 7–8); each rule links its official source (laws.boe.gov.sa, mc.gov.sa). Shown on the course and ticket pages (tick needed before paying) and in the Terms (a new section, only while the switch is on; legal_lib.php accepts a section of ready HTML as its 6th item)
- Admin: admin_bookings.php ("Courses and tickets", card on the Admin home): the three payout rules side by side, institutes (approve / not approved / suspend, with reason and email), courses (approve / not approved with reason), registrations, partner museums (types and dates), tickets, payouts (mark paid), payment providers' key status. Admin → Waiting for approval counts institutes and courses waiting. Emails: student confirmation (with the meeting link for online courses), new registration to the institute, the ticket with its QR, cancellations, approvals. Activity log: course_*, ticket_*, institute_*, partner_payout_paid, museum_account_new (actor type museum added)
- Database (bookings_update.sql, add-only, safe to run twice; run on the live database 14 Nov 2026 by a temporary locked page, removed straight after, after a full backup: .backups/db-u405344949_contact-20261114-101553.sql.gz): users.role and activity_log.actor_type gain 'museum'; institutes via_signup, payout_note; courses bookable, status, reject_reason and the course fields; course_registrations ref_no, student details, money split, payment_id, paid_at, cancelled_at, refund_amount, hold_until; art_places partner, ticket_commission, payout_rule, payout_days, payout_note, tickets_url, user_id; new tables museum_ticket_types, museum_ticket_dates, museum_tickets, booking_payments, partner_payouts
- Files: booking_lib.php, pay_lib.php, course.php, ticket_buy.php, pay.php, pay_test.php, pay_return.php, ticket.php, ticket_qr.php, ticket_check.php, register_institute.php, institute_dashboard.php, museum_dashboard.php, my_bookings.php, admin_bookings.php, bookings_update.sql (new); gallery_lib.php, admin_settings.php, admin_art_map.php, admin.php, art_map.php, art_place.php, artmap_lib.php, place_claim.php, terms.php, legal_lib.php, collector_dashboard.php, alert_lib.php, platform_lib.php

### Phone homepage buttons, Terms title, sign-up label, older institute rows (14 Nov 2026, owner)
- Homepage on phones (index.php, 640 px and narrower only): "Explore Gallery" and "Join as Artist" sit straight under the headline, then the two lines and the trust points; nothing removed, the video unchanged. Checked live: laptops (1366, 1536) and a 768 px tablet keep every part in exactly the same place; "Explore Gallery" now starts at 540 px on an iPhone 13 (screen 664) and 501 px on an iPhone SE (screen 568)
- Terms (terms.php): the same centred title card as Privacy ('centered_head')
- Join as an Artist (register_artist.php): the field is labelled "Public name: shown to visitors" / «الاسم العام: يظهر للزوار», like Edit Profile
- Art Map (art_map.php): the older institutes' cards (logo row) sit in their own rows (.m-old), the name starting level with the logo's top, so names line up in every row; the All and Institutes tabs keep their order (museums and galleries, older institutes, newer institutes)
- Checked: the temporary pages zz-names-9552d2aaeb7c29b6.php, zz-addr-… and zz-edu7-… are gone from the project, from git and from the server (404, also with their keys)
- Files: index.php, terms.php, register_artist.php, art_map.php. Tables: none

### Public name, Arabic addresses, Collector, Art Map search and rows, titles, calm photo pages (13 Nov 2026, owner)
- Edit Profile (edit_profile.php): artists have their own box "Public name: shown to visitors" / «الاسم العام: يظهر للزوار» (users.artist_name, required) and two private boxes "Full name in English: private" / "Full name in Arabic: private". Saving never copies the full name into the public name any more (before, every save did). owArtistNameSql() (artwork_lib.php) now shows the public name on Arabic pages too (it used to show the Arabic full name); with no public name it still falls back to the full name. Checked on the live database 13 Nov 2026 (temporary read-only page, removed straight after, and every server backup): no artist's public name had been overwritten; the only real artist (Azeem, #1) has never had a public name. Artist names on Arabic pages sit in <bdi> (owBiName() in artwork_lib.php; Gallery card and window, artwork page, profile heading, "Written by", Art Styles window) so a Latin name like "Sara A." reads correctly. Admin labels: "Public name", "Full name (… , private)"
- Art Map addresses in Arabic: new column art_places.address_ar (art_places_address_ar.sql, add-only), shown on Arabic pages (owPlaceAddressAr() in artmap_lib.php; cards and place pages), else the English one. All 105 saved addresses translated (87 published + 18 drafts); "Address in Arabic" box in Admin → Art Map. Added and filled on the live database 13 Nov 2026 by a temporary locked page (removed straight after), after a full backup (.backups/db-u405344949_contact-20261113-173421.sql.gz). Google place data keeps the English address
- One name for buyers: "Collector" / «مقتنٍ، المقتني، المقتنون» on every page, dashboard, email and in Admin (Terms, Privacy and the certificate rights line included: "The collector owns the physical artwork…"). Code names (role 'collector', 'buyer' keys, buyer_name columns) are unchanged. «مشتريات» (purchases) stays
- My Studio: the Commissions tab follows the Commissions switch (Admin → Settings); an artist who already has a commission keeps it (artist_studio.php)
- Art Map search (art_map.php): a small box "Place or city" / «مكان أو مدينة» in the Region filter, same row as the tabs on laptops (?q=, name or city, English or Arabic; أ/إ/آ = ا, ة = ه, ى = ي); kept with the tabs and Region; "No places match … Clear the search" when nothing matches
- Even rows: Art Map tabs and region pages (art_place.php) put cards with a photo and cards without one in separate rows; every card in a row has the same height, buttons at the bottom, photo credits keep 2 lines, a short last row is centred (.m-grp, .rg-list flex). The older institutes' cards got their own rows on 14 Nov 2026
- Art Education: when 4 per row would leave one card alone (5, 9, 13 …) the list shows 3 per row with the last row centred (articles_list.php .a-even). The certificate guide's cover is now Paul Cézanne, Mont Sainte-Victoire and the Viaduct of the Arc River Valley, 1882–85 (CC0, the Art Styles copy), with its credit line in both languages; saved on the live database 13 Nov 2026 by a temporary locked page (removed), after a full backup (.backups/db-u405344949_contact-20261113-174520.sql.gz); the old cover file is kept
- Titles: Support (no badge, no pink word, centred), Join (one colour), Art Styles (bold) and Privacy (a centred title card; legal_lib.php 'centered_head', Privacy only) use the same title style as the other pages. Terms got the same card on 14 Nov 2026
- Artist profile: "This is your public page…" sits on a frosted card (.self-note, artist_dashboard.php)
- Article and place pages with their own photo (an article with a cover, a place with a photo) have the calm plain page background instead of a second photo (article.php, art_place.php); without one they keep their background photo
- Homepage on phones: pushed on 14 Nov 2026 (see above)
- Files: edit_profile.php, artwork_lib.php, approval_lib.php, art_map.php, art_place.php, artmap_lib.php, admin_art_map.php, art_places_address_ar.sql (new), artist_studio.php, articles_list.php, explore.php, register.php, support.php, legal_lib.php, privacy.php, artist_dashboard.php, marketplace.php, artwork.php, article.php, assets/js/explore.js, assets/js/art_styles.js, and the Collector wording in admin.php, admin_*.php, alert_lib.php, artist_commission.php, cert_*.php, collector_dashboard.php, commission*.php, inquiry_lib.php, order_lib.php, terms.php, register_*.php, edit_artwork.php. Tables: users (read), art_places (address_ar, new), articles (one cover and credit)

### Leftovers, admin titles, Art Education pictures, photo backgrounds, hreflang, homepage speed (13 Nov 2026, owner)
- Privacy (privacy.php, What we collect): one line on "Is this your place?" (name, email, phone, photo, corrections; used only to update the place; the photo shows only after approval), in both languages; "Last updated" 13 November 2026
- nav.php: art_place.php counts as Art Map, so "Art Map" / «وجهات» is underlined on the place and region pages. 404.php: on phones in light mode the English © line uses --color-dark too
- Admin article titles (admin.php: Waiting for approval → team drafts and "Publish an article"; admin_articles.php lists): the page's language big, the other language small under it (owTitleStack(), .ttl-big / .ttl-sub)
- Art Education (articles_list.php): subtitle "Guides for buying and caring for art." / «أدلة لشراء الفن والعناية به.» The 5 guides (already published on the live site; their status was not changed) got their own free cover paintings from Wikimedia Commons (no faces, flowers, nudity, religious text or drinks), each with its "Picture: …" / «الصورة: …» credit line: original vs printed copy = Van Gogh, Wheatfield with Crows, 1890 (public domain); choosing a painting for your home = Bierstadt, Looking Down Yosemite Valley, California, 1865 (public domain); caring for a painting = Monet, The Houses of Parliament, Sunset, 1903 (CC0, NGA); why prices differ = Shishkin and Savitsky, Morning in a Pine Forest, 1889 (public domain); certificate of authenticity = Kuindzhi, Moonlit Night on the Dnieper, 1880 (public domain). Proofread in both languages: only "colours" → "colors" (the site's spelling) was wrong. Saved on the live database by a temporary locked page (removed straight after), after a full backup (.backups/db-u405344949_contact-20261113-150515.sql.gz); covers copied to uploads/articles/
- Photo backgrounds on the last plain pages (approved photos, frosted cards): Forgot password (forgot.php) = pink-palette (like Sign in); Verify (cert_verify.php) = museum-walk, its heading and form on a frosted card (.vf-head) and the "not found" bar on a solid card; the public artist profile (artist_dashboard.php) = easels-class
- SEO (seo_lib.php owSeo): every public page now names its English and Arabic versions (hreflang en = the plain address, ar = the same address with lang=ar, x-default = English) and each language version is its own canonical address (not on noindex pages). Titles, descriptions, link previews, sitemap.xml (163 addresses with the Art Map pages) and robots.txt were already right
- Homepage speed (theme_head.php): the hero video (1.3 MB) starts downloading when the page has loaded, not before, so it no longer slows the page; its still frame shows until then. Test copy, slow phone (Google-style: 1.6 Mbps, 150 ms, 4x slower processor): fully loaded 2.16 s → 1.60 s, first picture unchanged at about 1.35 s. The live server's own answer time varies from 0.3 to 1 second (also for plain files), so live numbers jump
- Security check (13 Nov 2026): .backups/, .logs/, .private/ (also place-claims and geo), uploads/ listing, uploads/consents/, CLAUDE.md, .sql files, .git, .gitignore, .user.ini and .htaccess answer 403; config.php runs but prints nothing; every temporary page is gone (404). Uploads keep only names the site makes with an image (or video / PDF) ending; photos are checked by their content and redrawn; forms have a security token, a hidden trap field and hourly limits. Nothing needed fixing
- Files: privacy.php, nav.php, 404.php, admin.php, admin_articles.php, articles_list.php, forgot.php, cert_verify.php, artist_dashboard.php, seo_lib.php, theme_head.php. Tables: articles (5 guides: cover_image_path, body_en, body_ar)

### Art Map: no example photo, own photos, "Is this your place?", pages for Google (13 Nov 2026, owner)
- No more example photos (the type-*.jpg files and OW_MAP_TYPE_PHOTO are gone). A place without a real photo is a compact card: a soft band at the top in its region's own tint (13 tints, one per region, light and dark mode; .m-band.r-<region> in art_map.php) with a small outline icon of its kind (owMapKindIcon() in artmap_lib.php) and the city. Places with a real photo come first in every tab, then the others in their usual order. Text, buttons and credits unchanged
- Admin → Art Map (admin_art_map.php): "Add a photo" / "Change the photo" under each place: JPG, PNG or WebP up to 10 MB, an optional credit line and credit link. The photo is turned upright, redrawn (no GPS or camera data), at most 800 px wide, saved as JPG + WebP in uploads/places/ (kept by deploys); it replaces the old photo (an older uploaded file is deleted; our own assets/img/places photos stay). Logged art_place_photo. Fixed on the way: the search box in Admin → Art Map stopped the page (the same query name used three times)
- "Is this your place? Send us your photo and details" / «هل هذا مكانك؟ أرسل لنا صورتك وبياناتك»: a small link on every Art Map card (art_places and the older institutes) and on every place page → place_claim.php?p=<id> (or ?i=<institute id>): name, email, phone (optional), photo (optional), photo credit (optional), corrections, and a tick "I represent this place, and I have the right to share this photo". Spam protection: security token, hidden trap field, the tick, at most 3 per visitor per hour and 30 an hour for the whole site. Saved in the new table art_place_claims (art_place_claims.sql, add-only; created on the live database 13 Nov 2026 by a temporary locked page, removed straight after, after a full backup: .backups/db-u405344949_contact-20261113-001622.sql.gz); the photo waits in the private folder .private/place-claims/. An email goes to contact@oweili.com. Nothing shows publicly: Admin → Waiting for approval shows "Art Map: Is this your place? messages" with the count, and Admin → Art Map (#claims) lists each one with Approve (ticked "Use their photo on the place": the photo becomes the place photo, credit = the sender's credit or the place name) or Reject. Text corrections are applied by the admin with Edit. The waiting photo is deleted after the decision. Logged place_claim, place_claim_approve, place_claim_reject
- Pages for Google: every public place has its own page at oweili.com/art-map/<region>/<name> (art_place.php, .htaccess rules; the name part is made from the English name, -2 when two places in one region share it; owMapDirectory() in artmap_lib.php, no database change): name, city and region, type, text, photo with credit (or the band), address, phone, website, Instagram, Google Maps, "Is this your place?", a breadcrumb, a good title and description in both languages and Google place data (schema.org Museum / ArtGallery / School with address, phone, image, map link, coordinates when saved, website and Instagram; BreadcrumbList). Region pages at oweili.com/art-map/<region>: "Museums and art galleries in Riyadh" / «متاحف ومعارض الرياض», every place of the region grouped by type (photo places first), each linked to its page (CollectionPage data). The older institutes are included (one with the same name as a museum or gallery is left out, like on the All tab). All in sitemap.xml (13 region pages + every place). The names on the Art Map cards link to these pages
- Photos round 3 (free only): every published place without a photo searched again on Flickr Creative Commons (commercial-use licences only), by Arabic name, other English spellings, name and city, and in Richard Mortel's Saudi albums, plus Wikimedia Commons; each candidate checked by eye (the place itself, no faces, no flowers, no religious text). 3 more places: Diriyah Museum (Salwa Palace; Ali Lajami, public domain), Hejaz Railway Museum in Madinah (Richard Mortel, CC BY 2.0) and Jazan Heritage Village (Richard Mortel, CC BY 2.0). Rejected on the way: a National Museum hall with visitors' faces and a Madinah Railway Museum hall with a flag carrying religious writing. Now 23 of the 123 published places have a real photo. Saved on the live database 13 Nov 2026 by a temporary locked page (removed straight after), after a full backup (.backups/db-u405344949_contact-20261113-010619.sql.gz)
- Files: art_map.php, artmap_lib.php, admin_art_map.php, admin.php, place_claim.php (new), art_place.php (new), art_place_claims.sql (new), .htaccess, sitemap.php; assets/img/places/type-* removed. Tables: art_places (photo fields), art_place_claims (new), institutes (read), activity_log

### Art Map: more free photos, example photo per type (12 Nov 2026, owner)
- Free sources only (no Google Places): Flickr Creative Commons (CC BY, CC BY-SA, CC0, public domain only) and Wikimedia Commons, each place searched by name; every photo checked by eye (the building or an empty room, no faces, no flowers, no religious text, really that place). 7 more places got a real photo: Red Sea Museum, Naseef House, teamLab Borderless Jeddah, Ibrahim Palace, Dar Al Madinah Museum, Maraya, Al-Jouf Regional Museum (assets/img/places/<src_ref>.jpg/.webp, 480 px wide, 11 to 34 KB; credit "Author, Licence, Flickr / Wikimedia Commons" under the photo). Now 20 of the 123 published places have a real photo. Saved on the live database 12 Nov 2026 by a temporary locked page (removed straight after), after a full backup (.backups/db-u405344949_contact-20261112-192719.sql.gz)
- Places with no photo now show one free example photo for their type instead of the drawing, with the tag "Example photo" / «صورة توضيحية» on the photo and "Example photo: <credit>" under it, so it never pretends to be the place: museum = an empty gallery room with a bench (Dale Cruse, CC BY 4.0), gallery = a white gallery with a sculpture (Dale Cruse, CC BY 4.0), institute = an art studio with easels (Michael Coghlan, CC BY-SA 2.0), all from Flickr (OW_MAP_TYPE_PHOTO in artmap_lib.php; assets/img/places/type-*.jpg/.webp, 480 × 300). The drawing stays only as a fallback if a file is missing
- Files: art_map.php, artmap_lib.php, assets/img/places/. Tables: art_places (photo_path, photo_credit, photo_page, photo_license)

### Art Map: places published, new "All" tab (12 Nov 2026, owner)
- The owner's file oweili-art-map-places.json (sent again with oweili-art-map-review.xlsx, the same 148 places and the same confidence levels) was already imported that morning, so nothing new was imported
- Published on the live database 12 Nov 2026 by a temporary locked page (removed straight after), after a full backup: every art_places draft whose confidence is not "low" (123 places: 70 museums, 44 galleries, 9 institutes). The 22 low-confidence places stay drafts for the owner to check in admin_art_map.php. The 11 hidden institutes of the older institutes list were not changed (still hidden)
- art_map.php: a first tab "All" / «الكل», open by default (art_map.php with no tab), showing museums, galleries and institutes together (museums, then galleries, then institutes; owArtPlacesAllPublished() in artmap_lib.php); count "N places" / «N وجهة». Region opens on "All regions" / «كل المناطق». The Museums, Art Galleries and Institutes tabs (?tab=museums / galleries / institutes) and the Region filter work as before. On phones the four tabs take two lines
- Places without a photo keep the neat placeholder drawing (13 places have a free-licence photo)
- Same day (owner): the 11 hidden institutes of the older institutes list are shown (institutes.is_active = 1; all 11 teach painting or drawing, none only design or digital art): Misk Art Institute, Ithra Academy, Hayy Jameel, the colleges at Princess Nourah, King Saud, Umm Al-Qura and Taibah universities, King Faisal University's Art Education department, Darat Safeya Binzagr, the Visual Arts Commission mentorship programme and Afaq Academy. Done by a temporary locked page (removed straight after), after a full backup (.backups/db--20261112-161931.sql.gz). The Art Map now shows 20 institutes (Riyadh 7, Makkah 7, Eastern 4, Madinah 1, Asir 1) and 134 places in all. Hayy Jameel shows once on the All tab, as a gallery (owner, same day: an older-list institute with the same name as a museum or gallery is left out of the All tab only; it stays on the Institutes tab). Institute cards show the course count only when there is at least one active course (no more "0 active courses")
- Files: art_map.php, artmap_lib.php. Tables: art_places (status only), institutes (is_active)

### Gallery: new search and filter bar, side panel, phone sheet (12 Nov 2026, owner)
- marketplace.php only. The line «كل عمل هنا رسمته يد فنان، بصبر وحب» / "Every piece here was made by an artist’s own hand, with patience and love." now sits inside the title card under the description (.ph-note; also on collection pages); the separate pill under the card is gone
- Laptops: one rounded frosted bar (.mp-bar): [search "Search artworks" / «ابحث عن عمل فني» | Topic ▾ | Status ▾ | Sort ▾ | filters button], thin lines between the parts. Status is now a dropdown (All / Available; Auction only while the Auctions page is on). Choosing a Topic, Status or Sort opens the Gallery at once; a new Topic drops a style from Art Styles, as before. When the page scrolls past it, the bar sticks under the menu, slimmer and still frosted (.stuck, its place kept so nothing jumps)
- The filters button (pink circle, sliders icon, with a small number for extra filters on) opens "More filters" / «تصفية أدق» as a side panel sliding in (from the left in Arabic): Style (grouped), Medium, Price, Size, Orientation, Artist’s city, Color and Verified artists only, all as tappable chips, plus "Clear all" (clears what the panel shows) and "Show results". Closes with ×, a tap outside or Escape; the keyboard stays inside while it is open
- Phones (768 px and narrower): one line [search] [filters button]; the button opens a bottom sheet "Filters" / «التصفية» with Topic, Status, Sort and every extra filter, "Clear all" and "Show results"
- Chosen filters show as small removable chips with × under the bar (search words, topic or style, status, medium, price, size, orientation, city, color, verified)
- Same filters, same values and the same web addresses as before (empty and default choices are left out). One change agreed by the owner: "Show results" in More filters now keeps the chosen Topic (before, it quietly reset it to All)
- Light animations (panel slide, chip fade), off for visitors who ask for less motion. Same colours (oweili.css variables) and fonts. Background photo, artwork cards, menu, footer and other pages unchanged
- Files: marketplace.php. Tables: none (reads artworks, users, artwork_colors as before)

### Art Map: Region next to the tabs, Arabic title «وجهات الفن» (12 Nov 2026, owner)
- art_map.php: the three tabs and the Region filter sit in one row (.m-bar): [Museums] [Art Galleries] [Institutes] Region [All regions]. Laptops: one row. Phones: the tabs on one line and Region with its list on one short line under them (it does not fit beside the tabs at phone width). The big page title in Arabic is «وجهات الفن» (English stays "Art Map"). The Arabic browser tab title and link preview also say «وجهات الفن» (owner, same day). Files: art_map.php. Tables: none

### Art Map background photo (12 Nov 2026, owner)
- art_map.php: the owner's photo of a bright corridor with colourful paintings on wooden easels and benches (easels-corridor, assets/img/studio/easels-corridor-640/1280/1920/2000 .avif/.webp/.jpg, focus 50% 50%, phone colour #8b7f73, its own tiny copy) replaces museum-walk. Used the same way as the other photo pages: full screen on laptops (cover, no zoom), the whole photo on top on phones fading into its own colour, the same dark-mode dimming. Nothing else changed (other photo pages checked: same HTML); museum-walk files are kept. Files: studio_lib.php, art_map.php, assets/img/studio/. Tables: none

### Art Map: 148-place import, own table, 13 regions, photos, «وجهات» (12 Nov 2026, owner)
- The places now live in their own table art_places (art_places.sql, add-only: kind museum / gallery / institute, status draft / published, names, city, region, short text EN/AR, address, phone, website, instagram, maps_url, lat / lng, photo_path / photo_credit / photo_page / photo_license, sources (JSON, admin only), confidence high / medium / low (admin only), src_ref so an import never adds a place twice). The old site_settings 'art_places' JSON is no longer read (kept, unused)
- Imported as DRAFTS on the live database 12 Nov 2026 by a temporary locked page (removed straight after), after a full backup (.backups/db-u405344949_contact-20261112-051159.sql.gz): the 21 earlier drafts plus 124 places from the owner's file oweili-art-map-places.json (148 places from all 13 regions). 24 skipped as duplicates of the 21 drafts or the 11 institutes: SAMoCA, National Museum, Darat Safeya Binzagr, House of Islamic Arts, Red Sea Museum, Tariq Abdulhakim Center, Tayebat Museum, Ithra Museum, Fatimah Museum, Abstract Art Gallery, Alajlan Art Gallery, Hewar, Naila, Athr Gallery, Hafez Gallery (Jeddah), Hayy Jameel, ATHR AlUla, Madrasat Addeera, Al-Muftaha Arts Village, Art Street Abha, Misk Art Institute, Art Education Department KFU, Hayy Learning, Ithra Academy. Now 145 drafts: 79 museums, 50 galleries, 16 institutes; 22 with low confidence
- art_map.php: each card shows, only when they exist: photo (or a neat placeholder drawing), name, city, type, short text, address, phone (tap to call), Official website, Instagram, Google Maps. Filter by the 13 regions (?region=riyadh / makkah / eastern / madinah / qassim / hail / tabuk / jouf / northern / asir / jazan / najran / baha; OW_MAP_REGIONS); old ?city= links are turned into their region. The Institutes tab shows the older institutes table (unchanged, with courses) and the published art_places institutes
- Photos: free-licence photos from Wikimedia Commons (public domain, CC BY, CC BY-SA) of the building or an empty interior, no faces, no flowers, no religious text; each checked by eye. 13 places have one (assets/img/places/<src_ref>.webp/.jpg, 480 px, 17 to 118 KB) with a small credit (author, licence, Wikimedia Commons, link); the rest show the placeholder
- admin_art_map.php (admin only): filters (type, status, region, confidence, search), Publish / Back to draft, Edit (all fields, sources one per line), + New place; sources and confidence shown only there, "Low" confidence in red (badge and red card edge). Admin → Waiting for approval counts the art_places drafts
- Menu: the Arabic label is «وجهات» (English stays "Art Map"); the page heading is unchanged
- Files: art_places.sql (new), artmap_lib.php, art_map.php, admin_art_map.php, institutes.php, nav.php, assets/img/places/ (new). Tables: art_places (new), institutes, courses, activity_log

### Art Map replaces Art Institutes in the menu (12 Nov 2026, owner)
- art_map.php «خريطة الفن»: the top menu item "Art Institutes" became "Art Map" (same place, same number of items; nav.php, desktop and ☰). institutes.php and training.php now answer 301 to art_map.php?tab=institutes (the old city becomes one of the Art Map cities, the language is kept). Sitemap and the iPhone early-load list name art_map.php
- 3 tabs: Museums «المتاحف» (default), Art Galleries «المعارض الفنية», Institutes «المعاهد». The Institutes tab is the old directory unchanged (cards, courses, the hidden Upcoming Workshops), plus "Official website" and "Google Maps" buttons on each card. City filter (?city=): Riyadh, Jeddah, AlUla, Dammam / Khobar (Dhahran is in this group), Abha, Other cities; institutes are sorted into these by their city name (owMapCityKey)
- Each museum or gallery: name, city, a short text in both languages, the official website and a Google Maps link (a Google Maps search for the name and city, unless a Google Maps link is saved). Same look as the other photo pages: museum-walk photo, frosted cards, laptop and phone. No pictures of the places
- Museums and galleries are kept as ONE setting, site_settings 'art_places' (JSON; no database structure change). Every place starts as a draft; only the admin publishes it: admin_art_map.php (admin only; Admin → Articles tab → "Art Map" card): Publish / Back to draft / Edit / + New place, each with the source the team checked (only the admin sees it); nothing is deleted. Admin → Waiting for approval shows "Art Map drafts" with the count (in the waiting total). Logged: art_place_save, art_place_publish, art_place_draft
- 21 places researched on official websites (the place's own site, the Ministry of Culture / Museums Commission, Visit Saudi or Experience AlUla; checked through search results limited to those sites, because this environment cannot open them directly) and saved as drafts on the live database 12 Nov 2026 by a temporary locked page (removed straight after), after a full backup (.backups/db-u405344949_contact-20261112-040239.sql.gz): 9 museums (National Museum, SAMoCA at JAX, Red Sea Museum, Tariq Abdulhakim Center, House of Islamic Art, Tayebat Museum, Ithra Museum, Alfelwah and Aljowharah Museum, Fatimah Museum) and 12 galleries (ATHR Gallery, ATHR AlUla, Hafez Gallery, Hayy Jameel, Saudi Art Council, Naila Art Gallery, Hewar Art Gallery, Al Ajlan Art Gallery, Abstract Art Gallery, Madrasat Addeera, Al Muftaha Village, Art Street Abha)
- Files: art_map.php, artmap_lib.php, admin_art_map.php (new); institutes.php, training.php (redirects), nav.php, sitemap.php, theme_head.php (early-load list), admin.php, gallery_lib.php (workshops switch link). Tables: site_settings (art_places), institutes, courses, activity_log

### Privacy line on stage photo details, 404 contrast on phones (12 Nov 2026, owner)
- Privacy Policy (privacy.php, What we collect): one new line after "Artwork photos": "Stage photo details: we read the camera make, model and photo time of the stage photos, only to check that artworks are original. This is never shown publicly." / «تفاصيل صور المراحل: نقرأ نوع الكاميرا وطرازها ووقت التقاط صور المراحل، فقط للتأكد من أن الأعمال أصلية. ولا يُعرض ذلك للعموم أبداً.» The "Last updated" date was not changed
- 404 page on phones (768 px and narrower), light mode: the small Arabic © line at the bottom («© 2026 أويلي · بيت الفن الأصيل») uses --color-dark instead of --color-body (contrast 4.45 → passes). Only in 404.php; the shared footer is unchanged elsewhere. The English © line there still measures 4.45 (not asked)
- OW_STAGE_SPAN_MIN stays 30 minutes. Files: privacy.php, 404.php. Tables: none

### Stolen-art warnings for the admin, Support card hidden (night of 11 Nov 2026, owner)
- Admin → Waiting for approval → New artworks: a pink box "Checks for copied art (only you see this; nothing is blocked)" under the Proof of Process, only when there is something to say (stolen_lib.php, owStolenWarningsHtml; admin.php only, not the sub-admin panel, nothing public). Warnings only: an upload is never blocked
  - Duplicate check: a 64-bit picture fingerprint (difference hash) of the artwork's main photo, of the whole picture and of the centre 80% and 60%, compared with the main photo of every other artwork that is not a draft. Same or very close (OW_DUP_MAX_DIST = 5 of 64; measured: a resized or 10%-cropped copy = 1, 65 different paintings = 8 or more) shows "Looks like an artwork by [artist]" with both pictures, a link to the other artwork and "the same artist" when it is. Fingerprints are kept in .logs/img_hash.json (private, kept by deploys) so each photo is read once
  - Photo time check: owStoreImage() (artwork_lib.php) now also returns the camera make, model and whether the photo carries the camera's own date, read before the photo is cleaned; upload_artwork.php keeps them with owLogStageCamera() in the activity log (action stage_photo, actor "system", so the artist's Activity list does not show it; details = stage, make, model, camera date yes/no, time, photo path). Warns when all the stage photos (2 or 3) carry camera dates within OW_STAGE_SPAN_MIN = 30 minutes of each other, or come from different cameras; a grey line says how many stage photos have no camera date (screenshot, edited, or sent through an app). Artworks uploaded before 11 Nov 2026 have no camera notes, so only the duplicate check applies to them
- Support (support.php): the "Report Misuse of Your Art" / «أبلغ عن استخدام فنك دون إذن» card is hidden for now; one switch brings it back ($spReportOn = true at the Legal & Policies cards). While hidden, the 3 other cards sit in one row on laptops (.legal-grid.three) and stack on phones as before
- Files: stolen_lib.php (new), artwork_lib.php, upload_artwork.php, admin.php, support.php. Tables: activity_log (stage_photo rows), artworks, artwork_stages, users (no structure change)

### Packing guide for artists (11 Nov 2026, owner)
- My Studio → "Packing guide" / «دليل التغليف» tab (artist_studio.php?tab=packing): only the signed-in artist sees it (visitors are sent to sign in, buyers and staff to their own pages; not on any public page). One line "Oweili arranges the shipping. You only pack the artwork." / «أويلي يتولى الشحن. عليك فقط تغليف العمل.», then 5 numbered steps, each with a simple outline drawing (no people): glassine paper on the front (never bubble wrap straight on the paint), corner protectors, bubble wrap, a strong snug box with no empty space, the certificate number on the box and the back label on the artwork; a last line links to the Certificates tab (numbers and back labels). No shipping prices or agreements
- Files: artist_studio.php. Tables: none

### One commission number, one artist home, 5 drafts removed (11 Nov 2026, owner)
- Oweili's commission is ONE number in Admin → Settings → "Oweili’s commission" (admin.php?settings=1#commission; 1 to 50, before VAT; logged setting_change). site_settings commission_rate = the rate now (15 by default) and commission_history = JSON [[date, rate], …] written at every change (the first change also stores the old rate "before the first change"), so each sold artwork keeps the rate in force on its sale date (sold_at, else updated_at, else created_at). No database structure change. Code: owCommissionPct(), owCommissionText(), owCommissionHistory(), owCommissionAt($date), owSetCommission(), owArtistShare($price, $soldOn), owArtistShareText() in platform_lib.php; order_lib.php owCommissionRate() reads the same number. Shown from the setting: the "What you get" box (register_artist.php), the Terms (price section), the Help page FAQ (support.php), the upload's price step ("You receive 85%", upload_artwork.php), the private commission price box (artist_commission.php), My Studio, Admin → Settings → Payments
- Terms (terms.php, Buying an artwork): the 15-day late-delivery refund is for ready artworks only; private commissions will have their own terms
- My Studio (artist_studio.php) is the artist's only home. artist_dashboard.php without ?id answers 301 to artist_studio.php (?saved=1 → ?done=saved), so the menu, sign-in and every old link land there; artist_dashboard.php?id=N is only the public profile, also for the artist's own page ("My public page": the same view as visitors, with a line "This is your public page, as visitors see it" and a way back; the artist can see it before the account is approved). The old owner parts of that page (money figures, drafts, delete buttons) were removed there; Delete is now a button on each artwork and draft in My Studio (the deleting itself and its messages stay in artist_dashboard.php's POST part, which sends back to My Studio). edit_artwork.php and upload_artwork.php send artists to My Studio
- One summary on top of My Studio, worked out once: Artworks (every piece sent to Oweili, with "+ N drafts in progress" under it), Sold, Your share after commission (each sale at its own rate), Waiting for approval (pieces in review + changes waiting, e.g. a profile change, + the account itself until approved); views, favorites and interested buyers in one line under it. Sales & payouts: works sold, sales total, your share all time and this year, and the share of each sale with its rate. The public profile's "Total Artworks" counts the same pieces visitors can see
- Deleted on the live database 11 Nov 2026 by a temporary locked page (removed straight after), after a full backup (.backups/db-u405344949_contact-20261111-195330.sql.gz): the event drafts Venice Biennale 2026, Ithra Design Week 2026, Tuwaiq Sculpture 2027 and In Praise of the Artisan, and the hidden institute Diriyah Art Futures (not painting). 9 event drafts and 11 hidden institutes stay
- Files: platform_lib.php, order_lib.php, admin_settings.php, register_artist.php, terms.php, support.php, upload_artwork.php, artist_commission.php, artist_studio.php, artist_dashboard.php, edit_artwork.php. Tables: site_settings (commission_rate, commission_history), articles, institutes (no structure change)

### Test data removed, homepage line and trust points, events and institutes drafts, Privacy and Terms reviewed (11 Nov 2026, owner)
- Test data deleted on the live database 11 Nov 2026 by a temporary locked page (removed straight after), after a full backup: .backups/db-u405344949_contact-20261111-181834.sql.gz. Deleted: the TEST article (#1, test-how-watercolour-glazing-works), the accounts TEST Artist (#4, no-reply@oweili.com) and TEST Artist 2 (#5, contact@oweili.com) with the TEST artwork (#3) and its stage photos, its 2 approved edits (pending_changes), the portfolio and profile pictures, 2 sign-in tries and 45 test entries in the activity log (their sign-ups, sign-ins, uploads, approvals, admin alerts, the test "Write to Us" message and the 5 messages sent to Bashro). The certificate OW-2026-CDC9-6VCY stays as Cancelled ("Account deleted by the Oweili team"). There were no test "I'm Interested", gift or waiting-list rows left. Azeem (#1, real) is unchanged. The deletion itself is logged (user_delete, artwork_delete)
- Homepage (index.php): under the headline one line "Oweili is an online gallery where you buy original handmade paintings straight from real artists." / «أويلي معرض إلكتروني تقتني فيه لوحات أصلية مرسومة باليد، مباشرة من فنانين حقيقيين.» (.hero-what) and, under the buttons, 3 small trust points with thin outline icons (.hero-trust): Proof of process: 25%, 50%, 100% · Certificate of authenticity with QR · Every artist reviewed. Video, still frame, badge, headline, line and buttons unchanged; laptops (1366 × 768) still one screen
- Art Events and Art Institutes researched (official websites, checked through search results limited to each official site, because this environment cannot open those sites directly): 13 event articles saved as unpublished team drafts (Admin → Waiting for approval → "Article drafts from the Oweili team": Preview, Edit, Publish), each ending with its official website; 12 institutes saved hidden (Admin → Institutes: Show). No pictures (Wikimedia image files cannot be downloaded from here; never pictures from the events' own sites). Saved on the live database by a temporary locked page (removed straight after), after a full backup: .backups/db-u405344949_contact-20261111-184811.sql.gz
- Privacy Policy and Terms of Use reviewed against the PDPL and the E-Commerce Law (no lawyer yet): the "Draft, under review" banner is gone, "Last updated: 11 November 2026" shows under the lead (legal_lib.php). Privacy: who runs Oweili (OW_COMPANY_NAME, the commercial registration number once set), "Write to Us" messages and the artist signature, photos of other people in commissions, no sensitive data, legitimate interest, news only on request, no automatic decisions, transfers abroad with safeguards, keeping periods for messages and signatures, a copy in readable form, identity check, breach notice to SDAIA within 72 hours, a new "Cookies and browser storage" section (sign-in cookie up to 7 days, ow_lang, ow_seen, ow_nostat, local storage). Terms: who runs Oweili and the contact, a new "Buying an artwork" section (total confirmed in writing before paying, an invoice for every sale, 7-day right to cancel after receiving, not for private commissions, damaged or different from description, delivery more than 15 days late, Ministry of Commerce), "Oweili’s own content", the certificate rights line, artists paid after the buyer receives the work and responsible for their own tax
- Files: index.php, privacy.php, terms.php, legal_lib.php. Tables: articles (13 drafts), institutes (12 hidden), users, artworks, artwork_stages, certificates, pending_changes, portfolio_images, login_attempts, activity_log

### Fixes from the UX review (11 Nov 2026, owner)
- Artwork page on phones: the page is exactly the screen width again and the ☰ button shows (the Creation Journey strip made the page grid wider than the phone; now it scrolls inside its own card: grid-template-columns minmax(0,1fr)). Files: artwork.php
- Under the price, on the artwork page and in the Gallery window: "We’ll contact you within 24 hours to arrange payment and delivery." / «سنتواصل معك خلال 24 ساعة لترتيب الدفع والتوصيل.» (OW_BUY_NEXT in cert_lib.php). The Gallery window also shows the size ("Size 70 × 50 cm · Medium · Landscape") and a small "Certified authentic … · Verify" badge when the artwork has a valid certificate (one extra read each for the artworks in the window data). Shared size words: owArtworkSizeWords() in cert_lib.php (artwork page and window)
- One language per page: the Gallery window shows the title and "About this work" in the page language only (the other language only when the artist left it empty); the artwork page no longer shows the other-language title under the heading; a medium picked from the suggestions is shown in the page language (OW_MEDIUMS + owMediumWords() in cert_lib.php; "Watercolour" is read as "Watercolor"); the artist profile and the artist dashboard show the artist name and artwork titles in the page language only. Picture alt texts stay in both languages (rule 5). The certificate stays bilingual
- Join as an Artist (register_artist.php): a "What you get" / «ما تحصل عليه» box at the top: free to join; Oweili takes 15% only when a work sells; you are paid after the buyer receives the work; every artist is reviewed by our team. Join page (register.php): thin outline drawings (palette, framed picture) instead of the emoji, the artist card says the video is optional, and "Already one of us? Sign In" sits on a small frosted card over the photo
- Upload (upload_artwork.php): the title is needed in Arabic OR English (or both); when only one is written, the same title is saved for both languages, so every page still shows one. The dash in the intro line was replaced by a full stop
- Artist earnings: the Artist Dashboard boxes now show "Your share this year / all time, after Oweili’s 15% (SAR)" (the sold prices minus the commission); My Studio → Sales & payouts has a third box "Your share, after Oweili’s 15% (SAR)" next to the sales total. owArtistShareText() in platform_lib.php (rate from site_settings commission_rate, 15 by default)
- Forms: the exact wrong box is marked (the site's error colour, the deep brand pink --color-primary-dark, as a 2 px edge, also while typing in it) and gets the focus; typing in it clears the mark. Server forms: owBadField() / owBadAttr() / owBadScript() in platform_lib.php (Join as an Artist, Join as a buyer, Sign in (both boxes on a wrong email or password, never saying which), Forgot password, Upload step 1); "I'm Interested" (marketplace.php) and "Write to Us" (support.php) mark their boxes in the page script. Every label on these forms is linked to its box (checked: no box without a label)
- Dark mode article text a little lighter (article.php: --color-light). Art Styles: the card list keeps room before the cards are drawn, so the end text and footer no longer jump (layout shift 0.3 → 0; explore.php .sc-grid:empty)
- Bigger tap areas, same look: Gallery Status buttons (an invisible 44 px area), footer links and the footer email (padding with matching negative margin), "Remember me" on Sign in
- "Sara A.." → "Sara A." in the My Studio greeting (a name ending with a full stop keeps one)
- Support: renamed "Help & Support" / «المساعدة والدعم» (heading and page title; the top menu word "Support" is unchanged). While the Community card is hidden, the separate "Get in Touch" card is gone and "Get in Touch" / «تواصل معنا» is the title of the form card
- Files: artwork.php, marketplace.php, cert_lib.php, cert_fields.php, register.php, register_artist.php, register_collector.php, upload_artwork.php, artist_dashboard.php, artist_studio.php, platform_lib.php, login.php, forgot.php, support.php, article.php, explore.php, assets/css/oweili.css. Tables: none (reads artworks, certificates)

### Square language button, shorter Gallery top, centred cards (11 Nov 2026, owner)
- The language button is now the same 40 × 40 square as the dark-mode button (one bolder letter). The owner accepted that the neighbouring items in the top bar slide over a little to close the space (oweili.css button.ow-ctl.ow-ctl.lang-btn)
- Gallery (marketplace.php only): a smaller title card (padding, 28 px heading; 24 px on phones) and a little less space around the line under it. Laptops: search on its own line, then Topic, Status, Sort and "More filters" side by side in one row (an opened "More filters" takes the full width). Phones: Topic and Sort side by side, Status and "More filters" below. Laptop 1366 × 768: the first artwork now starts at about 455 px (was 734), so artworks show on the first screen; phones: about 890 px (was 1008), still below the first screen because of the whole photo on top
- Fewer artworks than a full row (fewer than 4): the cards keep their normal width and sit centred (data-few on #artGrid, flex). Full rows are unchanged
- Files: assets/css/oweili.css, marketplace.php. Tables: none

### Art Education pictures, TEST article hidden (10 Nov 2026, owner)
- The TEST article ("TEST How watercolour glazing works" / «اختبار: كيف يعمل التزجيج بالألوان المائية») was set back to draft, so visitors get 404; nothing deleted
- Each of the 5 Art Education articles has a cover picture (shown on its card and at the top of the article, the existing layout) and a last line in its text with the picture credit ("Picture: …" / «الصورة: …»). The pictures are the site's own free-licence copies already used on Art Styles (assets/img/styles/, 800 px, 80 to 145 KB), copied to uploads/articles/: original vs printed copy = Van Gogh, Wheat Field with Cypresses (CC0); choosing a painting for your home = Cézanne, Mont Sainte-Victoire (CC0); caring for a painting = Gijsbrechts, The Reverse of a Framed Painting (public domain); why prices differ = Ruisdael, The Windmill at Wijk bij Duurstede (public domain); certificate of authenticity = Dürer, Young Hare (public domain)
- Run on the live database 10 Nov 2026 by a temporary locked page (removed straight after), after a full backup: .backups/db-u405344949_contact-20261110-194315.sql.gz. No code or layout change. Tables: articles

### Language button shows one letter (10 Nov 2026, owner)
- The language button shows only E on Arabic pages and ع on English pages; screen readers hear "English" / «العربية» (aria-label). Since 11 Nov 2026 a 40 × 40 square (see above). Was the same size (84 px), place and colours as before; checked with 320 before / after screenshots (every page, laptop and phone, light and dark, Arabic and English): only the letter changed. Older page scripts that still write the full word when switching in place are turned back into the letter by one small script in theme_head.php
- Files: theme_head.php (owCornerControls and the letter script), lang_lib.php, nav.php. Tables: none

### Artist signature on certificates waits for approval (9 Nov 2026, owner) — live 10 Nov 2026
- The artist draws a signature in signature.php (My signature). A new, changed or removed signature goes to Admin → Waiting for approval (pending_changes as a profile change with data signature = new / remove; no database change). The waiting picture is kept apart in .private/signatures/artist-N.pending.png. Certificates show only the approved artist-N.png, so until the admin approves they keep the last approved signature, or none. Approve puts the new one on the certificates (or removes the old one); Reject deletes the waiting picture and the artist sees the reason on signature.php and by email. The artist can cancel a waiting request. A waiting signature is kept when the artist saves Edit Profile. Oweili has no signature on the certificate, only its seal (the upload in Admin → Settings was removed)
- Files: sig_lib.php, signature.php, approval_lib.php, admin.php, edit_profile.php, certificate.php, admin_settings.php. Tables: pending_changes, activity_log

### Visitor statistics: no data centers, no test browsers (9 Nov 2026, owner)
- Not counted any more: visits from data centers and cloud servers, and browsers run by test or robot software. The network of each address comes from DB-IP's free "IP to ASN Lite" file (CC BY 4.0), kept next to the city file in .private/geo/dbip-asn-lite.mmdb and refreshed by the site itself once a month like the city file (owGeoMaybeUpdate, one download per page at most, at most one try every 6 hours per file). owIsDataCenter() in geo_lib.php: a fixed list of cloud network numbers (OW_DC_ASN: Amazon, Google, Microsoft, Oracle, DigitalOcean, Linode, OVH, Hetzner, Vultr, Alibaba, Tencent, Contabo, Scaleway, Leaseweb, M247, Hostinger and others) plus hosting words in the network name (OW_DC_WORDS). Kept on purpose: Cloudflare, Fastly and Akamai's relay network (OW_DC_KEEP), because Apple iCloud Private Relay and Cloudflare WARP send real people through them. An address whose network is unknown is still counted. The browser signal (theme_head.php) is not sent when navigator.webdriver is on (Playwright, Selenium and similar)
- The 4 test and server visits already counted (Manchester 2, San Francisco 2) were deleted on the live database 9 Nov 2026 by a temporary locked page (removed straight after), after a full backup
- Files: geo_lib.php, visit.php, theme_head.php. Tables: visitor_stats

### Live database fix found by the end-to-end test (9 Nov 2026, owner) — live 9 Nov 2026
- Artist sign-up crashed on the live site after saving the account ("We will be right back"): the error log said Unknown column 'artist_id' in register_artist.php (the portfolio pictures). Cause: the live portfolio_images table is an older version (id, user_id, image_url, created_at) made before signup_setup.sql; that file only creates the table when it is missing, so it never changed the old one. The test copy is built fresh from the project's .sql files, so it had the new layout
- The whole live structure was compared with the test copy (a temporary read-only page, structure only, removed straight after). Only two real gaps: portfolio_images (artist_id, sort_order missing) and artworks.sold_at (financial_setup.sql was never run on the live database; "Mark as sold", setting an artwork to Sold and the My Studio sales tab would have crashed). purchases has an extra artist_id on the live site (nothing writes there yet; harmless)
- Fixed (add-only, portfolio_update.sql, safe to run more than once): artworks.sold_at; portfolio_images.artist_id + sort_order + index, old user_id copied into artist_id (user_id kept). Run on the live database 9 Nov 2026 by a temporary locked page (removed straight after), after a full backup: .backups/db-u405344949_contact-20261109-060646.sql.gz (39 tables, 63 rows; the same backup code was checked by restoring a test backup into an empty database). register_artist.php also fills the old user_id column while it exists
- Rule from now on: before trusting a .sql file with CREATE TABLE IF NOT EXISTS, compare the live table's columns; an older table is never changed by it
- Found the same day by the upload test: the live artworks.type was still the old list ENUM('abstract','landscape','portrait','other'), so every other art type (oil, watercolor, all Art Styles) was saved as empty and the upload stopped at step 4. reset_setup.sql (which makes it VARCHAR(50)) had never been run there. Changed on the live database 9 Nov 2026 with that one line (ALTER TABLE artworks MODIFY COLUMN type VARCHAR(50) NOT NULL DEFAULT 'other'; nothing deleted) by a temporary locked page (removed straight after), after a full backup: .backups/db-u405344949_contact-20261109-091456.sql.gz. It was the only column whose type differed from the test copy

### Visitor city statistics, "Write to Us" form, 5 Art Education drafts (owner, 9 Nov 2026) — live 9 Nov 2026
- Visitor statistics (geo_lib.php, visit.php): the country and city of each visitor come from DB-IP's free "IP to City Lite" database (CC BY 4.0, credit "IP Geolocation by DB-IP" on the Privacy page and in Admin), kept on our own server in .private/geo/ (not in GitHub; the site downloads the new month's file by itself after a page has been sent, at most one try every 6 hours, checked before it replaces the old one). Read with MaxMind's open reader (lib/maxmind-db, Apache-2.0). Only date, country, city and a count are saved (table visitor_stats, visitor_stats.sql, add-only); the internet address is never saved. Counted once per browser per day: after the page has loaded, theme_head.php sends a tiny signal to visit.php (a cookie ow_seen holds only the date). Not counted: robots (user agent), Admin pages, the admin password session, admin / sub-admin accounts and any browser that once opened Admin (cookie ow_nostat, set by admin.php). Pages are not slower (the lookup runs after the answer is sent). Arabic names for the main Saudi and Gulf cities (OW_CITY_AR, owCityName); other cities stay in English; country names come from PHP's own list in both languages. Admin → "Visitors" tab: today, 7 and 30 days, top cities, by country, by city. Privacy Policy: one line in both languages
- Support page "Write to Us" is now a form (name, email, optional phone, message) instead of an email button (support.php, support_send.php). Security token, hidden robot trap, at most 3 messages per visitor per hour (also by the real visitor address) and 30 an hour for the whole site. Sends the message to contact@oweili.com, the same message to Bashro Customer Service (new door kind "contact", bashro_send.php owBashroContact, after the page is sent) and a short "We received your message · وصلنا رسالتك" email to the visitor. Nothing is saved in a table; the activity log keeps only that a message was sent (support_message, language only)
- 5 Art Education articles written for the Oweili team, saved as unpublished drafts (no author account): How to tell an original painting from a printed copy; How to choose a painting for your home; How to care for a painting: light, heat and cleaning; Why original art prices differ; What a certificate of authenticity is, and why it matters. No pictures. Admin → Waiting for approval has a new list "Article drafts from the Oweili team" with Preview, Edit and Publish (action publish_team_article, logged article_publish); the waiting count includes them
- Run on the live database 9 Nov 2026 by a temporary locked page (removed straight after), after a full backup: the visitor_stats table and the 5 drafts
- Files: geo_lib.php, visit.php, visitor_stats.sql, lib/maxmind-db/, theme_head.php, admin.php, privacy.php, support.php, support_send.php, bashro_send.php. Tables: visitor_stats (new), articles, activity_log

### Password reset links last 24 hours, "Send me a new link" (9 Nov 2026, owner)
- forgot.php: a reset link now works for 24 hours (was 1 hour; counted by the database clock). It still works only once, and a new link cancels the old one. The email says "It works for 24 hours" / «يعمل لمدة 24 ساعة»
- The "This reset link is invalid or has expired" page now shows a short line "Type your email and we will send you a new link. It works for 24 hours." and the button "Send me a new link" / «أرسل لي رابطاً جديداً» (the same email form, the same answer whether or not the email has an account)
- Files: forgot.php. Tables: users (reset_token, reset_expires; no database change)

### Confirm links last 48 hours, "Send me a new link" (9 Nov 2026, owner)
- The "Confirm your email" link now works for 48 hours (was 24): new artist and buyer accounts (register_artist.php, register_collector.php), a new link sent again and a changed email address (account_lib.php). The email says "The link works for 48 hours" / «يعمل الرابط لمدة 48 ساعة» (platform_lib.php owVerifyEmailBody)
- verify.php: the "invalid or expired link" page keeps its "Create account" button and now also has "Already joined? Get a new confirmation link" with an email box and "Send me a new link" / «أرسل لي رابطاً جديداً». verify.php?resend=1 shows only that form. The answer is always the same ("Check your email … if this email has an account that is not confirmed yet"), so nobody can find out who is signed up; only artist and buyer accounts that are not confirmed get an email. Limits from owResendVerify (3 per hour per browser, 6 per hour for the whole site), security token and a hidden robot trap. A new link replaces the old one, so only the newest link works
- login.php: under "Don't have an account?" a line "Did not get the confirmation email?" with a "Send me a new link" button to verify.php?resend=1
- Why TEST Artist 2's link said "invalid" (9 Nov): the account was still not confirmed and its saved code was unused and valid until 10 Nov 07:24, so the link that was pressed carried a different code than the one saved; on the test copy the same email's link worked at the first press (a second press of a used link shows the same "invalid" page)
- Files: verify.php, login.php, account_lib.php, platform_lib.php, register_artist.php, register_collector.php. Tables: users (no database change)

### "I'm Interested" confirmation email, admin Delete buttons (8 Nov 2026, owner) — live 8 Nov 2026
- After "I'm Interested" the visitor gets a short "We received your request · وصلنا طلبك" email in both languages (the same look as the other emails, owReqTellBuyer), with a link to the artwork. Sent once per saved request (inquiry_lib.php owSaveInquiry)
- Admin → a person's page (admin.php?person=N): "Delete account" for artist and buyer accounts only (never staff), with an "Are you sure?" step. Removes the account, its artworks (photos, stage photos, colours, favorites, waiting changes), portfolio pictures, profile photo, sessions and favorites; its "I'm Interested" requests stay but lose the account link. Certificates are never deleted: they become Cancelled ("Account deleted by the Oweili team"). An account that is part of real records (gift requests, commissions, NDAs, orders, payouts, purchases, articles, photo consents, institutes, course registrations) is refused with a message: suspend it instead. Admin → Inquiries: a "Delete" button per request with an "Are you sure?" step. Both are logged (user_delete, inquiry_delete). Nothing is sent to Bashro. Files: admin_lib.php (owAdminDeleteUser, owAdminDeleteBlockers, owAdminDeleteInquiry), admin.php, admin_person.php, inquiry_lib.php. Tables: users, artworks, artwork_stages, artwork_colors, favorites, sessions, pending_changes, portfolio_images, artwork_inquiries, certificates, activity_log

### New Gallery background photo (8 Nov 2026, owner) — live 8 Nov 2026
- Gallery (marketplace.php): the owner's black-and-white photo of a quiet gallery hall with framed photographs and a bench (gallery-hall, assets/img/studio/gallery-hall-640/1280/1920/2000 .avif/.webp/.jpg, focus 50% 50%, phone colour #404040) replaces gallery-visit. Used the same way as the other photo pages: full screen on laptops (cover, no zoom), the whole photo on top on phones fading into its own colour, the same dark-mode dimming. Nothing else changed; gallery-visit files are kept. Files: studio_lib.php, marketplace.php, assets/img/studio/. Tables: none

### Art Styles: painting and drawing only, 15 hidden, 15 new colour styles (8 Nov 2026, owner) — live 8 Nov 2026
- Owner: for now Art Styles is about painting and drawing only, with the focus on colour (types, techniques, looks). Other arts come back later
- Hidden, never deleted (one switch): Stained glass, Mosaic, Islamic mosaic, Islamic geometry, Islamic ornament, Egyptian linen, Textile, Manuscript, Collage, Conceptual, Performance, Architecture, Printmaking, Ukiyo-e, Arabic calligraphy. The list is OW_MORE_STYLES in art_types.php; while the switch is off they are marked legacy there, so they leave Art Styles (explore.php sends them to explore.js as OW_HIDDEN_STYLES), the Gallery "More filters" style list, the artist profile style choices and the public profile, and the admin collection rule list. An artwork that has one keeps its label; an artist's earlier choice is kept when they save their profile. Switch: Admin → Settings → "Hidden for now, to bring back later" → "Other art styles" (site_settings more_styles_on = '1' brings all 15 back; gallery_lib.php OW_HIDDEN_PARTS 'more_styles'). The same switch also hides the Gallery topics Arabic calligraphy and Islamic ornament (OW_MORE_TOPICS in art_types.php, owArtTopicsShown() in artwork_lib.php) from the Gallery topic dropdown and the upload form; old ?cat= links still work, and an artwork that already has one keeps its label. The edit form (edit_artwork.php) hides them too (owner, 8 Nov 2026), and an artwork keeps the topic it already has. The Gallery's "More filters" style list also puts Al-Qatt Al-Asiri under Traditional and drops the Heritage group while the switch is off. While hidden, Art Styles shows Al-Qatt Al-Asiri under Traditional and hides the Islamic & Arab Heritage filter (explore.js); with the switch on both go back
- Order on Art Styles: colour painting styles first, drawing last (Pencil, Charcoal, Ink & Pen, Chinese Ink; DRAWING in explore.js)
- 15 new colour styles, same card, window and credit line as the others: Palette Knife Painting, Impasto, Fluid / Pour Painting, Dry Brush, Glazing, Alla Prima, Symbolism, Luminism, Tonalism (Traditional), Abstract Expressionism, Colour Field, Op Art, Futurism, Naive Art, Art Deco Painting (Contemporary). Texts in art_styles.js (after Post-Impressionism and Fauvism), drawn covers in art_covers.js, free artworks with credits in art_photos.js and assets/img/styles/. Visible now: 50 styles. Each is also a Gallery type, so it shows in the Gallery's "More filters" style list, the profile style choices and the admin rule list
- Art Nouveau picture: Gustav Klimt, Beech Grove I, 1902 (public domain) instead of the Métro entrance photo
- Abstract Expressionism picture (owner: a clearly free licence, not Pollock): Wassily Kandinsky, Improvisation 28 (second version), 1912 (Guggenheim; public domain by age, Kandinsky died 1944)
- Files: art_types.php, gallery_lib.php, artwork_lib.php, explore.php, assets/js/explore.js, assets/js/art_styles.js, assets/js/art_covers.js, assets/js/art_photos.js, assets/img/styles/, edit_profile.php, artist_dashboard.php, marketplace.php, upload_artwork.php, edit_artwork.php. Tables: site_settings (one new setting, no structure change)

### Art Styles 38 → 50, Gallery search hint on phones, Support card centred (8 Nov 2026, owner) — live 8 Nov 2026
- Art Styles: 12 new styles with the same card, window and credit line as the others: Impressionism, Pointillism, Renaissance, Baroque, Romanticism, Art Nouveau, Ukiyo-e (Japanese woodblock prints), Realism, Post-Impressionism (Traditional, now 24), Cubism, Fauvism (Contemporary, now 12), Al-Qatt Al-Asiri (Islamic & Arab Heritage, now 8). Texts in assets/js/art_styles.js (added after the last style of each group; the existing styles are unchanged), drawn covers in assets/js/art_covers.js, free artworks with credits in assets/js/art_photos.js and assets/img/styles/. Like every style, each new one is also a Gallery type, so it shows in the Gallery's "More filters" style list, the artist profile style choices and the admin collection rule list
- Owner's changes before going live: Baroque shows Jacob van Ruisdael, The Windmill at Wijk bij Duurstede, about 1670 (Rijksmuseum, public domain; no drinks, no faces); the Al-Qatt Al-Asiri picture is credited "Traditional women painters of Asir" (photo Richard Mortel, CC BY 2.0); the style's Famous artists list names Fatima Abou Gahas without dates (owner, 8 Nov 2026)
- Gallery: on phones (768 px and narrower) in English the search hint is "Search artworks…" (the longer one was cut off); laptops and Arabic unchanged (marketplace.php)
- Support: while Community is hidden, the only contact card ("Write to Us") sits in the middle column on computers (support.php). Tables: none

### Night of 7 Nov 2026 (owner): real artworks on Art Styles, home video on phones, new Art Events photo — live 8 Nov 2026
- 8 Nov 2026 (owner): four Art Styles pictures replaced. Conceptual: Marcel Duchamp, Bicycle Wheel (1951 version; photo Toohool, CC BY-SA 4.0) instead of Fountain. Pop Art: Charles Demuth, I Saw the Figure 5 in Gold, 1928 (Met, CC0) instead of Lucky Strike (a cigarette brand). Gouache: Paul Sandby, Dartmouth Castle, 1794 (Yale Center for British Art: "gouache on thin paper"; public domain; shown without the small figures at the bottom). Islamic ornament: the same Alhambra plaster panel (Jebulon, CC0) cut so the carved writing band at the top no longer shows. Checked and kept: the Alhambra tiles (no writing) and Hugo Ball's Karawane (printed sound-poem words, no face)
- 8 Nov 2026 (owner): Support page: the "Join the Community" card is shown only while the Community page is switched on (owCommunityPageOn(), Admin → Settings), so it comes back by itself. Very small phones (360 px and narrower, e.g. iPhone SE): the menu bar was 9 px too wide and pushed the ☰ button past the screen edge; slightly tighter spaces there (gap 8 px, side padding 12 px) so it sits 12 px inside. Files: support.php, assets/css/nav.css. Tables: none
- Art Styles: every one of the 38 style cards now shows a real, free artwork instead of the drawn cover (public domain, CC0 or a CC BY-SA / CC BY licence that allows commercial use; no faces of people, no flowers, no nudity, no Quranic verses or religious text; calligraphy is a tughra, ornament and geometry are patterns). Each style window shows the artwork whole with a small credit under it in the page language: artist, title, year, the photographer when the licence asks for it, "Source: Wikimedia Commons" (link to the file page) and the licence (link). Small copies only: cards 440 px (about 1 MB for all 38 together), windows 800 px; WebP with JPG fallback, loaded lazily. The drawn covers (art_covers.js) stay for the upload form and as the fallback if a photo is missing. Card size, layout and titles unchanged
- Files: explore.php (loads art_photos.js, picture styles, credit line), assets/js/explore.js (photo() and credit()), assets/js/art_photos.js (new: size, credit, licence and file page of each style's picture), assets/img/styles/ (new: <style id>.jpg/.webp 800 px and <style id>-s.jpg/.webp 440 px). Tables: none
- Homepage on phones (640 px and narrower): the same wide blue video as computers (hero-wide.mp4, the hand painting dots) instead of the tall pink brush, shown WHOLE at full width just under the menu (16:9, no zoom, no crop), fading softly into #6b5551 (the colour of the video's own bottom edge), which fills the rest of the page; the title card sits below the video and the page scrolls a little when the card does not fit. Dark mode: the same 72% dimming as computers. The still frame on phones is hero-wide too. Computers unchanged (checked pixel by pixel at 1536 × 864 and 1366 × 768, light and dark, English and Arabic). hero-pink-tall.* files are kept, no longer used. Files: index.php. Tables: none
- Art Events (articles_list.php?type=event and event articles): the owner's photo of a bright concrete hall with posters, a glass roof and cafe tables (events-hall, assets/img/studio/events-hall-640/1280/1920/2000 .avif/.webp/.jpg, focus 50% 50%, phone colour #4b6b6c) replaces museum-walk; full screen on computers, the whole photo on top on phones, the same dark dimming. Art Education unchanged; the hidden Artist Spotlights keep museum-walk. Files: studio_lib.php, articles_list.php, article.php. Tables: none

### Gallery language button fixed (7 Nov 2026, owner)
- Cause: the Gallery's own language button (tgl() in marketplace.php) built its address with mpUrl(), which leaves out lang=en because English is the default. Without lang= in the address the server used the saved cookie (still Arabic), so English never came. Now the chosen language is always added to the address (lang=en or lang=ar), and every other choice (topic, sort, page) is kept
- Tested Arabic → English → Arabic on phone and laptop on every page in the menu and footer: Home, Gallery (also with filters), Art Styles, Art Events, Art Education, Art Institutes, Support, Terms, Privacy, Sign in, Join
- Files: marketplace.php. Tables: none

### Phones: the whole background photo, then its own colour (7 Nov 2026, owner)
- Phones and small tablets (768 px and narrower), every page with a background photo (not the homepage video): the WHOLE photo shows at full screen width just under the menu bar, with no zoom and no crop. Its bottom fades softly into a colour taken from the photo's own bottom edge (OW_STUDIO_UNDER in studio_lib.php), which fills the rest of the page. The page (title card first) starts below the photo, so nothing covers it. Dark mode: the same slight dimming, as a 28% dark layer over both the photo and the colour, so they match without a line. Phones download a copy as wide as the screen
- Laptops and computers unchanged: every laptop page was compared pixel by pixel before and after, light and dark
- Files: assets/css/oweili.css, studio_lib.php (OW_STUDIO_UNDER, --ow-under and --ow-ratio printed by owStudioBackdrop, phone sizes in owStudioPhoto). Tables: none

### Footer at the bottom, dimmer photos in dark mode (6 Nov 2026, owner)
- Footer: on every page with a background photo, short pages (Art Events, Art Education, an article that is not found) now put the footer at the bottom of the screen, like Art Institutes: the page body is a column at least one screen tall and the main part grows (body:has(> .ow-backdrop) in oweili.css). Measured on 11 photo pages at 1536 × 864 and 390 × 844: only the short pages moved; every longer page is exactly the same
- Dark mode: the page photos and the homepage video and still frame are a little dimmer (brightness 72%, same colours). Light mode unchanged
- Files: assets/css/oweili.css. Tables: none

### Gallery topic dropdown (6 Nov 2026)
- Owner: the 10 topic buttons on the Gallery (All + the 9 topics) are now one dropdown list "Topic" / «الموضوع», "All" / «الكل» chosen by default, in the same centred column and at the same width as the search box. Choosing a topic opens the Gallery filtered the same way (?cat=<topic>, other choices kept); old ?cat= and ?type= links still work and show their topic in the list
- Files: marketplace.php. Tables: none
- Same day, owner: the Gallery labels «الموضوع» / «الحالة» / «الترتيب» (Topic / Status / Sort) sit on small frosted cards over the photo (the same glass as the other cards). The footer no longer repeats the top menu: the Gallery, Art Styles, Events, Institutes and Support links were removed; the logo, Privacy, Terms, the contact email and the © line stay (footer.php)
- Same day, owner: the «متاح» (Available) status button and the «عدد الأعمال» (artwork count) line on the Gallery got the same frosted look. Bashro check: the live config.php has BASHRO_DOOR_SITE = 3 and a valid 64-character BASHRO_DOOR_KEY (added by Bashro's session); one marked test "I'm Interested" (the Settings test) answered 202. Checked by a temporary locked page, removed straight after. Now matching the door guide's section 4b (owner, same day): signup sends role, name, email, phone, city, joined (sign-up date); approval sends ref, type (artwork_new / artwork_changed / profile_change / artist_new; article approvals are not sent, Bashro has no type for them), title, link; owBashroAccountDeleted(email) sends account_deleted (always on once the door is set up). Sign-ups and approvals stay OFF (BASHRO_DOOR_SIGNUPS / BASHRO_DOOR_APPROVALS not set). One marked test of each answered 202 (temporary locked page, removed). Oweili has no delete-account button yet (accounts are deleted by email request), so nothing calls owBashroAccountDeleted today. Files: bashro_send.php, alert_lib.php

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

### One centred column on Gallery, Art Styles and Art Events, like Art Institutes (6 Nov 2026, last round)
- Owner: the cards use the same layout as Art Institutes: the title card, the line under it and every card sit one under the other in the middle of the page, at the same width (the 420 px card column) and spacing. The side placement is off (OW_STUDIO_SIDE is empty; the CSS stays for later)
- Gallery on computers (marketplace.php, its own styles, 769 px and wider): the title card and its line are centred; the search box, the topic filters, the status and sort row and "More filters" sit in a centred 420 px column (the open filters in 2 columns); the count and the empty card are centred too. Phones unchanged
- Art Styles keeps its grid of style cards (centred, 960 px); Art Events centres its title card and empty card. Photos, colours, text, menu and footer unchanged
- Files: marketplace.php, studio_lib.php. Tables: none

### Edge to edge again on every screen, tuned for laptops (6 Nov 2026, last round)
- Owner: only laptop screens matter. The side colour fill for very wide screens was removed completely (no bands, no shading at the sides, on any screen size): every background photo fills the whole screen edge to edge (cover). OW_STUDIO_EDGE and the --ow-edge values are gone
- Tuned for 1366 × 768, 1440 × 900 and 1536 × 864 (focus points in OW_STUDIO_FOCUS): Art Events 50% 0% (the arch at the top of the photo stays, only the floor is trimmed), Art Styles 58% 30% (the palette), Support 55% 75% (the studio table), Gallery 50% 58% (the woman and the paintings), Art Education 50% 50% (the books). The see-through footer and the smaller cards on the side stay
- Files: studio_lib.php, assets/css/oweili.css. Tables: none

### More of each photo on wide screens, see-through footer, smaller cards (6 Nov 2026, last round)
- Very wide screens (wider than 1600 px, owner): the background photo fits the screen HEIGHT instead of filling the width, so the whole photo (or nearly) shows; the small space left at its sides is a soft solid colour taken from the photo's own left and right edge (OW_STUDIO_EDGE in studio_lib.php: the average colour of the outer 3% of each side of the owner's original). No blur, no strips. A photo wider than the screen still fills it. Measured at 1920 × 1080: Art Events 85% → 100%, Art Education 90% → 100%, Gallery 84% → 100%, Support 84% → 100% of the photo visible. Screens up to 1600 px and phones are unchanged (cover; phones keep the centre)
- See-through footer on every page with a photo: the footer band is transparent; only its links and its © line sit on two light frosted strips (white in light mode, navy in dark mode)
- Smaller cards on photo pages: the page title card (.d-head, .page-header) fits its words (at most 560 px, smaller heading); the empty-list cards are at most 420 px (articles) or 460 px (Gallery); the Art Styles card grid is at most 960 px wide on computers
- Cards away from the main subject (OW_STUDIO_SIDE, printed as data-side on the photo layer; computers only): Art Events (the arch and visitors in the middle) and Gallery (the woman in the middle) put the title card and the empty-list card on the reading side (right in Arabic, left in English), close to the screen edge on wide screens; Art Styles puts its title card on the left, over the empty paper
- Art Styles photo: the owner's "explore-palette-brushes-wide.jpg" (the same photo as pink-palette, already used on Sign in and Join), replacing the paint buckets
- owStudioBackdrop() now prints the photo position, the tiny copy and the edge colours as values on the layer (--ow-pos, --ow-tiny, --ow-edge-l, --ow-edge-r); the layer's background comes from them in oweili.css
- Files: studio_lib.php, assets/css/oweili.css, explore.php. Tables: none

### "Art Styles" name, zoom check (6 Nov 2026, last round)
- Renamed "Explore Art Styles" / «استكشف أساليب الرسم» to "Art Styles" / «أساليب الرسم» everywhere visitors see it: the menu (computer and ☰), the page heading, the page tab title, the link preview and the older pages' own menu words. The footer now has an "Art Styles" / «أساليب الرسم» link after Gallery. The file stays explore.php
- Zoom check (measured, not guessed): no background photo has any extra zoom, scale or parallax (every transform is "none"; each picture box is exactly the screen size, object-fit cover). Visible share of each original on a 1440 × 900 laptop: Art Events 94%, Art Education 100%, Art Styles 90%, Gallery 94%, Institutes 96%, Support 94%, Sign in / Join 83%, 404 63% (square photo). On a 1920 × 1080 screen (a wider window) about 76 to 81% (Art Styles 90%, Sign in 67%, 404 50%). What hides parts of a photo is the page itself: the menu, the frosted title card and, on short pages, the footer
- The new photo for Art Styles ("explore-palette-brushes-wide.jpg") did not arrive as a file; paint-buckets stays until it does
- Files: nav.php, footer.php, explore.php, lang_lib.php, marketplace.php, upload_artwork.php, the older pages' menu words (login, register, register_artist, register_collector, forgot, verify, support, community, artist_dashboard, edit_profile, edit_artwork). Tables: none

### Wide photos only, one video per screen, handmade topics only (6 Nov 2026, late night)
- Owner: every photo and video must show the WHOLE picture. Tall photos were zoomed on computers, so only WIDE photos are page backgrounds now, on computer and phone (cover, sized to the screen). On phones and other tall screens the photo keeps its CENTRE in view (object-position 50% 50%, oweili.css), only its sides are trimmed. Never stretched; the full-size copies were added so big screens never get a softened photo (pink-palette 1500; paint-buckets, gallery-visit, studio-table, easels-class 2000, avif / webp / jpg)
- Photo per page: Gallery → gallery-visit (the woman looking at framed paintings); Explore Art Styles → paint-buckets (for now, the owner will send a new one); Art Institutes → easels-class; Support → studio-table; Sign in, Join, Join as an artist (and Join as a buyer, Gifts) → pink-palette. Art Events (list and event articles) → museum-walk (the owner's wide photo of visitors in a museum hall with stone reliefs, sent 6 Nov 2026); Art Education (list and its articles) → open-books (the owner's black-and-white stack of open books); both 640 / 1280 / 1920 / 2000, centred. The 404 page → gallery-arch (the owner's black-and-white photo of a visitor walking alone through a dark gallery arch, sent 6 Nov 2026; square, so computers trim a little of the dark top and bottom). Its card never covers the visitor: on computers it sits on the right side, on phones it is more compact and sits at the bottom (404.php). Commissions → plain page background. The tall photos (home-painting, brushes-jars, paint-cloth, palette-hand, art-book) are no longer used as backgrounds (files kept)
- Homepage: one video only, looping. Computers (wider than 640 px): the wide blue hand painting dots (hero-wide.mp4). Phones: the owner's tall pink brush (hero-pink-tall.mp4, centred), with its own still frame (assets/img/hero/hero-pink-tall.*) and tiny copy (OW_HERO_TINY['pink-tall']). The cross-fade and hero-pink-wide.mp4 were removed
- Handmade only: "Digital" / «رقمي» and "Photography" / «تصوير» are gone. Explore Art Styles hides their 12 styles (explore.js HIDDEN_CATS; art_styles.js unchanged, nothing deleted), now 38 styles; the end line and description no longer count them. art_types.php marks them legacy (old artworks keep their label; never offered)
- The 9 topics, one list (owArtTopics() in artwork_lib.php), English and Arabic: Oil · Acrylic · Watercolor · Charcoal and pencil · Arabic calligraphy · Islamic ornament · Abstract · Nature and landscapes · Saudi heritage («الرسم الزيتي · الأكريليك · الألوان المائية · الفحم والرصاص · الخط العربي · الزخرفة الإسلامية · الفن التجريدي · الطبيعة والمناظر الطبيعية · التراث السعودي»). Used by the Gallery filter (art_categories.php: All + the 9, ?cat=<topic>) and the upload form (9 picture cards, guided, with the drawn pictures of art_covers.js) and the edit form (an artwork's own older type stays offered). Each topic saves one art type (store) and shows several in the Gallery (types); two new types charcoal_pencil and saudi_heritage (art_types.php, topic_only, not Explore styles). A style link from Explore (?type=pencil) also shows the artworks saved under its topic
- Art Events and Art Education: no type tabs under the title and no «اكتشف» badge (they are in the top menu); the badge is also gone from Art Institutes
- Files: studio_lib.php, assets/img/studio/, assets/img/hero/, assets/video/, index.php, assets/css/oweili.css, marketplace.php, register_artist.php, articles_list.php, article.php, 404.php, commission.php, institutes.php, explore.php, assets/js/explore.js, art_types.php, art_categories.php, artwork_lib.php, upload_artwork.php, edit_artwork.php, platform_lib.php. Tables: none (artworks.type keeps its values)

### Flat menu, own photos for Art Education and Art Institutes (6 Nov 2026, night)
- Owner: the "Discover" / «اكتشف» dropdown is gone. Its items sit straight in the top menu: Gallery · Explore Art Styles · Art Events · Art Education · Art Institutes · Support ↓ (Arabic: المعرض · استكشف أساليب الرسم · الملتقيات الفنية · التثقيف الفني · المعاهد الفنية · الدعم). Same flat list in the ☰ phone menu. Artist Spotlights joins the row only while it is switched on in Admin → Settings
- Active-page underline: Art Events lights on articles_list.php?type=event and event articles, Art Education on ?type=art_education and education articles, Art Institutes on institutes.php (and the old training.php address). Phone menu marks the same item
- Fits on one line from 1101 to 1440 px, English and Arabic, signed out, artist and admin, with the language and dark-mode buttons fully on screen (nav.css: between 1101 and 1200 px the links and account buttons are a little tighter)
- Photos: Art Events keeps the woman looking at framed paintings (gallery-visit); Art Education (list and its articles) = the owner's art book with tea (art-book, focus 50% 45%); Art Institutes = the owner's classroom with easels (easels-class, focus 42% 55%). Same style as the other pages: full screen, sized to the screen, cover, full colours, frosted cards, with a tiny copy so there is no flash. Files: assets/img/studio/art-book-480/720/1080 and easels-class-640/1280/1920 (avif, webp, jpg)
- Files: nav.php, assets/css/nav.css, studio_lib.php, articles_list.php, article.php, institutes.php, assets/img/studio/. Tables: none

### Homepage videos edge to edge (6 Nov 2026, evening)
- Owner: the homepage video did not fill the screen (blurred strips at the sides, thin black lines at the video's edges). Now both videos and the still frame fill the whole screen behind the content, edge to edge, like the background photos (object-fit: cover), on computer and phone. No blurred sides any more (.ow-fill removed from oweili.css; owStudioTinyVar is no longer used by the homepage)
- The black lines were in the blue video file itself (8 px bars on each side of the owner's 1920×1080 original). hero-wide.mp4 was re-made from the original with the bars trimmed off (crop 1880×1058, scaled to 1280×720, H.264, no sound, start-fast): 1.3 MB (was 1.7 MB). Checked: no black edge left. The still frame (assets/img/hero/hero-wide.*) and its tiny copy (OW_HERO_TINY['blue']) were made again from the new first frame. The pink files had no bars
- Main subject kept in view: blue video object-position 66% 50% (the hand and brush), pink 38% 55% (the brush tip). Phones get the same wide blue video (cover, so the sides are cut on a tall screen) and the owner's vertical pink video; the special phone layout from earlier the same day (video strip under the menu, compact card) was removed. The soft 1.2 s fade between the videos, the frosted card and the fast loading (blue first, pink after the page has shown) are unchanged
- Files: index.php, assets/video/hero-wide.mp4, assets/img/hero/, studio_lib.php (OW_HERO_TINY), assets/css/oweili.css. Tables: none

### Full-screen photos again, sized to the screen, main subject in view (6 Nov 2026, later the same day)
- Owner: go back to photos that fill the whole screen (that looked good); remove the blurred fill and the narrow strip; fix only the zoom. The page photos are now object-fit: cover again, on a layer the size of the SCREEN (never the page: checked on pages up to 5,700 px long), so a long page never zooms them in. Each photo keeps its main subject in view with its own focus point (OW_STUDIO_FOCUS in studio_lib.php, used as object-position): hand and canvas (home-painting 62% 42%), brushes (45% 45%), paint buckets with the hand (42% 40%), the woman with the paintings (50% 58%), the studio table (55% 58%), palette and brushes (58% 45%), hand and palette (38% 52%), cloth (50% 50%). The browser is told the real shown width (sizes = max(100vw, ratio × 100vh)). The tiny copy stays only as the first frame before the photo loads (no white flash), placed the same way; no blurred frame any more
- The same whole-shape files are used (no new crops). The homepage videos stay as they are (whole frame with blurred fill, .ow-fill now only for them). Colours and frosted cards unchanged
- Upcoming Workshops: the cards were still shown to a signed-in admin as a preview; now they are hidden for everyone (institutes.php), until switched on in Admin → Settings
- Files: studio_lib.php, assets/css/oweili.css, institutes.php. Tables: none

### Whole photos and whole video frames, never zoomed (6 Nov 2026) — the page photo part was replaced the same day (see above); the homepage video part stays
- Owner: the background photos and the homepage video were zoomed in, so only a small part showed and the idea of the photo was lost (e.g. the woman looking at the framed paintings must show the woman, the wall and the paintings). Now every picture is shown WHOLE, like the original
- Photos (studio_lib.php): new files made from the owner's originals in their own shape (no crop): assets/img/studio/<key>-<width>.<avif|webp|jpg>; portrait photos 480 / 720 / 1080 wide, landscape 640 / 1280 / 1920 (pink palette 640 / 960 / 1280). The old 9:16 / 16:9 crops (…-tall-… / …-wide-…) were removed. The layer is the size of the SCREEN (position: fixed, 100lvh; checked: it stays the screen size on pages up to 5,700 px long), the photo is fitted inside it (object-fit: contain), and the space around it is filled with a soft blurred copy of the same photo (.ow-fill in oweili.css: the photo's own tiny copy of about 0.8 KB, OW_STUDIO[...][5], blurred by the browser; owStudioTinyVar()). The browser is told the real shown width (sizes = min(100vw, ratio × 100vh)), so phones download 10 to 34 KB copies. Full colours, no veil; the frosted cards behind the text stay
- The hand on the palette (Commissions, hidden for now) now shows whole, so the painting of roses on its easel is visible again (it cannot be cut out without cutting the palette). The studio table (Support) now shows whole, so the coffee can's brand name shows
- Photo per page (owner): hand painting the landscape (home-painting) → Gallery (always, also with artworks); brushes in jars → Join as an artist; hand on the palette → Commissions; paint buckets → Explore Art Styles; woman looking at framed paintings (gallery-visit) → Art Events, Artist Spotlights, Art Education, articles, Institutes; studio table → Support; pink palette → Sign in, Join, Join as a buyer (and the hidden Gifts page); paint-splattered cloth → the 404 page
- Homepage videos (index.php): every frame is fitted whole (object-fit: contain) with its own blurred fill; each video has its own layer (.hv-pink fades over the blue one, 1.2 s, same timing as before). Phones now get the same wide blue video (hero-wide.mp4), shown whole right under the menu with the text card below it (phones at least 640 px tall; the card is a little more compact there; shorter phones keep the card in the middle), and the owner's vertical pink video (hero-pink-tall.mp4, the owner's file made smaller, never cropped) as it is. The vertical cut of the blue video (hero-tall.mp4) and its still frame (hero-tall.*) were removed. Still frame: hero-wide only
- Also: the "Institutes Directory" heading and the Join page heading got the small frosted card (they sat straight on the photo); the Join page's two cards are solid in dark mode
- Files: studio_lib.php, assets/img/studio/ (new files), assets/img/hero/, assets/video/, index.php, assets/css/oweili.css, marketplace.php, login.php, register.php, register_collector.php. Tables: none

### Hidden for now, to bring back later: Commissions and Artist Spotlights (6 Nov 2026, second round)
- Owner (6 Nov 2026): hide two more, the same way as Community and Gifts. NOTHING deleted: pages, code, tables (commissions, commission_files, commission_nda, articles, photo_consents) and every saved request stay
- Commissions page (commission.php): the request form answers the normal 404 page to visitors (a signed-in admin sees a preview). Gone from the Collect menu, the sitemap, the Gallery's "A private commission from your photo" card and the Gifts page's commission line. A buyer's OWN private link from earlier (commission.php?ref=…&k=…, and its answers: accept a price, approve a draft, "I received it") keeps working (tested), and so do Admin → Commissions and the artist's commission pages
- The Collect menu (اقتنِ) shows only while one of its pages (Gifts, Commissions, Auctions) is shown; with all three hidden it is gone on computer and phone
- Artist Spotlights: gone from the Discover menu (computer and phone), the type buttons on the article pages and the sitemap. articles_list.php?type=artist_spotlight and every spotlight article answer 404 to visitors (owArticlePublicSql leaves them out while hidden); a signed-in admin still sees them. Artists can still write spotlights in My Studio (they wait for the admin as before)
- Menu now: Gallery · Explore Art Styles · Discover ↓ (Art Events, Art Education, Art Institutes) · Support ↓. Art Education is back in Discover (owner, 6 Nov 2026)
- Switches: two more rows in Admin → Settings → "Hidden for now, to bring back later": site_settings commissions_page_on and spotlights_on ('1' = shown; missing or '0' = hidden). owCommissionsPageOn(), owSpotlightsOn() in gallery_lib.php
- Files: gallery_lib.php, nav.php, commission.php, gifts.php, marketplace.php, sitemap.php, article_lib.php, articles_list.php, article.php. Tables: site_settings (two new settings, no structure change)

### Hidden for now, to bring back later: Community, Gifts, Upcoming Workshops (6 Nov 2026)
- Owner (6 Nov 2026): "We're just starting, so I want the site simpler and focused." Hidden the same way as Auctions. NOTHING deleted: the pages, the code, the tables (gift_requests, …) and every saved request stay
- Hidden for now, to bring back later:
  - Community page (community.php): not in the menu (desktop and ☰) or the sitemap. Visitors who open it get the normal 404 page; a signed-in admin sees a preview with a notice. chat.php sends old links to the Gallery while it is hidden (to Community again once it is shown)
  - Gifts page (gifts.php): not in the Collect menu (the word "Collect" then opens Commissions), the footer, the sitemap, the Gallery's "Give a gift that lasts" card (the Commissions card then fills the row), the artwork window's and the artwork page's "Give it as a gift" link. Visitors get the 404 page (also ?artwork=N), the admin a preview. Admin → Gifts (admin_gifts.php) and a buyer's earlier gift requests in their dashboard stay
  - Upcoming Workshops: the 5 "Coming soon" cards with "Notify me" (Desert Light in Watercolor, Desert Landscapes in Oil, Digital Arabic Calligraphy, Saudi Heritage Drawing, Contemporary Saudi Expression) on Art Institutes (institutes.php). Hidden from visitors; the admin still sees them with a notice. The institutes directory itself is unchanged
- One switch each: Admin → Settings → "Hidden for now, to bring back later" (admin.php?settings=1#hidden-parts): Show / Hide and Preview, one click, logged as setting_change. site_settings keys community_page_on, gifts_page_on, workshops_on ('1' = shown; missing or '0' = hidden). Code: OW_HIDDEN_PARTS, owPartOn(), owCommunityPageOn(), owGiftsPageOn(), owWorkshopsOn(), owHiddenPageGate(), owHiddenNotice() in gallery_lib.php
- Files: gallery_lib.php, admin_settings.php, community.php, gifts.php, chat.php, institutes.php, nav.php, footer.php, sitemap.php, marketplace.php, artwork.php. Tables: site_settings (three new settings, no structure change)

### No flash between pages, found on the live site (6 Nov 2026)
- Measured, not guessed: every frame of 8 page changes was recorded on the live site (Android and iPhone screen sizes, light and dark, English and Arabic; the recording scripts are kept outside the project), then again on the test copy after each fix
- Cause 1 (every page with a background photo, and the homepage): the new page was drawn before its full-screen photo (or the homepage still frame) was ready, so for a few frames the page was plain white (navy in dark mode) and the photo then popped in. Fix: each photo carries a tiny blurred copy of itself (about 0.7 KB; since the whole-photo change it lives in OW_STUDIO and OW_HERO_TINY in studio_lib.php, owStudioTinyVar(), and also fills the space around the whole photo) that is drawn the moment the page opens; the real photo then sharpens on top of it. Photos at the top of a page are decoded before they are drawn (decoding="sync"). The homepage .hero-bg uses the same tiny copy of its still frame. The videos were checked: they start invisible over the still frame and fade in, so they never cause a blink
- Cause 2 (Arabic, the 12 older pages whose words are filled in by their own script, owHtmlAttrs(true)): they were hidden until the browser said "page ready", so a phone could draw one empty frame first. Now the last thing in the page (a small script with id ow-ready, added by owLangFilter) shows the page the moment its words are in, and Chrome / Android are told not to draw it at all before that point (<link rel="expect" href="#ow-ready" blocking="render">). Fixed on the way: the server part of this never ran before (it looked for "ow-wait" in a part of the page it never sees); it now uses a flag set by owHtmlAttrs
- Also: the small "page opening" fades from 70% to full (riseUp, mpRise, ow-riseUp, ow-fadeUp) now start at full, so content never dims while the page changes
- Never add a full-screen picture without its tiny copy, and never hide a whole page until a script runs
- Files: studio_lib.php, index.php, lang_lib.php, assets/css/oweili.css, register.php, verify.php, marketplace.php, forgot.php, login.php, support.php, community.php. Tables: none

### Real homepage video, photos in full colour (5 Nov 2026)
- The old homepage video (bg.mp4) carried the Google Gemini mark, so it was AI-made: it is deleted from the project and the site. The homepage now plays a real Pexels video of a hand painting white dots on a colourful canvas (sent by the owner; Pexels licence: free for commercial use). Computers and tablets: assets/video/hero-wide.mp4 (1280×720, 1.7 MB); phones (640 px and narrower): assets/video/hero-tall.mp4 (a vertical 540×960 cut of the same video, 1.1 MB). No sound, made to start fast (index at the front), loops. The owner's second video (a vertical "brush pink" one for phones) did not arrive as a file; when it does, it replaces hero-tall.mp4 with the same name
- Two videos in turn (owner, 5 Nov 2026): blue (hand painting dots) then pink (a brush; the owner's Pexels files), a soft 1.2 s cross-fade between them, looping, on computers and phones. Pink: assets/video/hero-pink-wide.mp4 (1280×720, 0.8 MB) and hero-pink-tall.mp4 (540×1024, 0.4 MB). Only the blue video loads at the start; the pink one loads after the page has shown, when the browser is idle; until it is ready the blue one simply loops. Tested: blue → pink → blue, phone and computer, English and Arabic; Data Saver / slow 3G download no video at all. First view 0.48 s (English), 0.52 s (Arabic)
- Still frame: assets/img/hero/hero-wide and hero-tall (.avif / .webp / .jpg, 18 to 107 KB) show at once behind the video, and alone on slow 2G / 3G, Data Saver or reduced motion. Phone 4G timing on the test copy: first view 0.45 s (English), 0.56 s (Arabic)
- No veil over any video or photo any more (owner): they keep their real, strong colours. Text stays readable only where it sits: on the homepage the badge, headline, line and buttons sit on a small frosted-glass card (.hero-card in index.php, white in light mode, navy in dark mode); on the other pages the page headings (.d-head, .page-header) get the same frosted card (--studio-glass in oweili.css), the Explore filter buttons get a frosted face, and cards over photos are solid in dark mode. Same layout and texts as before
- Files: index.php, theme_head.php (phones get the tall copy), assets/css/oweili.css, assets/video/ and assets/img/hero/ (new); bg.mp4 removed. Tables: none

### One language per page, dates as day / month / year, homepage photo (5 Nov 2026)
- Homepage: was put back as before (no studio photo); since the later 5 Nov change it plays a real Pexels video instead (see "Real homepage video")
- Text over every background photo: a stronger veil (--studio-veil) and a soft halo in the page colour behind headings, paragraphs and labels that sit straight on the photo (--studio-glow, oweili.css)
- One language per page: lang_lib.php now passes BOTH languages through owLangOne(): dropdown options, list labels, placeholders, hover and screen-reader labels written as "English · عربي" keep only the page language (owLangPick); picture alt texts keep both (rule 5). English pages get an English-only title, Arabic pages an Arabic-only one (owSeo). The medium suggestions (cert_fields.php) and the country lists (edit_profile.php, collector_dashboard.php) are in the page language; a country picked in Arabic is saved in English (owCountryEn in platform_lib.php). The "Optional" badge on the artist sign-up video is one language. File boxes show our own button and line in the page language ("Choose files / No file chosen" / «اختر الملفات / لم يتم اختيار ملف», theme_head.php) instead of the browser's words
- The language button now fetches the page again in the new language (so every word comes from the server), except when the visitor has typed into a form on the page (then it switches in place, nothing typed is lost)
- Dates: every date box becomes three lists, Day / Month / Year, in the page language (Arabic month names يناير … ديسمبر), never mm/dd/yyyy. The real box stays hidden with the same name and receives YYYY-MM-DD, so the server is unchanged; earliest / latest dates are checked with a message in the page language (theme_head.php). Used on gifts, commissions, artist articles and the team's article and consent forms. A box with data-native keeps the browser's own picker
- Standard sizes: a short note explains a greyed-out choice, in the page language: "Square is greyed out because this size is not square. Choose portrait or landscape." / «خيار «مربع» غير متاح لأن هذا المقاس ليس مربعاً. اختر طولي أو عرضي.», and for square sizes "This size is square, so it can only be square." (size_lib.php)
- Files: lang_lib.php, seo_lib.php, theme_head.php, size_lib.php, cert_fields.php, platform_lib.php, edit_profile.php, collector_dashboard.php, register_artist.php, assets/css/oweili.css. Tables: none

### Full-page studio photos, Arabic from the first moment, standard sizes (5 Nov 2026)
- Full-page photos: the studio photos are now the background of the WHOLE page, behind all content (owStudioBackdrop() in studio_lib.php, printed right after <body>). A still layer (position: fixed, height 100lvh), not background-attachment: fixed, so it also stays still on iPhone and does not jump when the address bar hides. A soft veil on top (--studio-veil in oweili.css: white in light mode, navy in dark mode) keeps text, cards and buttons readable; the page's old video veil is hidden where a photo shows (one veil only). Phones get 9:16 copies 480 / 720 wide (AVIF 13 to 68 KB), computers 16:9 copies 1280 / 1500 / 1920; AVIF, then WebP, then JPEG. The old header photos and card strips were removed
- Map: homepage = the original video, no photo (owner, 5 Nov 2026); Join as an artist = brushes-jars; Gifts = pink-palette; Explore Art Styles = paint-buckets; 404, the empty Gallery and empty Art Events / Institutes lists = paint-cloth; Commissions = palette-hand (cut so the painting of roses in the photo never shows), Art Events / Artist Spotlights / Art Education / articles / Institutes = gallery-visit (always, also when the list is empty; the paint-cloth stand-in is no longer used there), Support = studio-table (the phone cut avoids the big coffee-can brand name). These last three arrived as files on 5 Nov 2026 and are live. Section headings that sit straight on a photo (Support "Get in Touch", Institutes "Upcoming Workshops") get the same small frosted card; Support's contact cards are solid in dark mode
- Language decided on the server (lang_lib.php): owLang() reads ?lang=ar/en (also saved), then the cookie ow_lang (a year, Lax), else English. Every page prints <html <?php owHtmlAttrs(); ?>>: Arabic visitors get lang="ar" dir="rtl" from the first byte, and the page goes through owLangFilter(): Arabic title (data-title-ar, owSeo() titles are Arabic-only for Arabic visitors, og:locale ar_SA), every plain data-en / data-ar text and the menu words already in Arabic, the language button says "English". The 12 older pages whose words are filled in by their own script (community, support, artist_dashboard, login, register, register_artist, register_collector, forgot, verify, upload_artwork, edit_artwork, edit_profile: owHtmlAttrs(true)) stay hidden for Arabic visitors until that script has run (class ow-wait, at most 1.5 s as a safety), so English never shows first. English visitors are not buffered at all
- Browser side (theme_head.php): the language button writes the cookie (and localStorage for the pages' own scripts); a kept copy made in the other language (Back button, iPhone early loading, a page Chrome prepared in the background) reloads once, hidden, never in a loop. Visitors from before 5 Nov who had the choice only in the browser are moved to the cookie on their first page. nav.php no longer flips the direction itself; marketplace.php no longer redirects to ?lang=ar
- Emails: links to oweili.com get lang=ar when the reader's account language is Arabic (users.preferred_lang), otherwise the language of the visitor who caused the email; Oweili's own address is never changed (owMailLangLinks in mail_lib.php)
- Standard sizes (size_lib.php): one fixed list instead of free width and height. Extra small 20×20, 20×30, 25×25; Small 30×30, 30×40, 40×40; Medium 40×50, 50×50, 50×70, 60×60; Large 60×90, 70×100, 80×80, 80×120; Extra large 100×100, 100×150, 120×180, plus orientation (portrait, landscape, square; square sizes are always square). Used in the upload and edit forms (cert_fields.php: width and height come from the size), private commissions and custom gifts (optional, words also kept in size_note), and the Gallery filter (5 groups + orientation; old ?size=small / medium / large links still work). The artwork page shows the size with its group and orientation. Bashro leads ("I'm Interested", gifts, commissions) carry "Size: 50 × 70 cm, Medium (m), portrait". A change of size on a live artwork waits for approval like the other details
- Database (sizes_update.sql, only adds): size_code, size_category (xs / s / m / l / xl), orientation in artworks (with an index), commissions and gift_requests. Existing artworks were mapped once to the nearest standard size (closest in cm, then the closest shape); their typed width and height were kept for their certificates. Run on the live database on 5 Nov 2026 by a temporary page (sizes_db.php, since removed) after a full backup: .backups/db-u405344949_contact-20261105-124745.sql.gz. The live site had 0 artworks then, so no artwork needed mapping
- Files: lang_lib.php, size_lib.php, sizes_update.sql (new); studio_lib.php, theme_head.php, nav.php, seo_lib.php, mail_lib.php, cert_fields.php, cert_lib.php, approval_lib.php, upload_artwork.php, edit_artwork.php, commission.php, gifts.php, marketplace.php, gallery_lib.php, artwork.php, inquiry_lib.php, bashro_send.php, assets/css/oweili.css, assets/img/studio/, and the <html> line of every page (41 files). Tables: artworks, commissions, gift_requests (new columns), users (preferred_lang read)

### Auctions hidden, studio mood photos (5 Nov 2026)
- Auctions page HIDDEN (owner: nobody should copy the idea). Nothing deleted: auctions.php, auction_lib.php, the auction_waitlist table and its sign-ups all stay. One switch: site_settings 'auctions_page_on' ('1' = shown; missing or '0' = hidden), read by owAuctionsPageOn() in gallery_lib.php, changed in Admin → Settings → "Auctions page" (Show / Hide, one click, logged as setting_change). While hidden: no menu item (desktop Collect and ☰), not in the sitemap, the Gallery's "Auction" status filter is hidden, the word is gone from the early-loading list in theme_head.php, and oweili.com/auctions (and ?count=1, the Notify me form) answers the normal 404 page to everyone except a signed-in admin, who sees a preview with a notice. Unsubscribe links from earlier confirmation emails (?leave=) keep working. Admin → Auctions tab (list, CSV) unchanged. Privacy Policy still lists the waiting list (the data is still kept)
- Studio mood photos (studio_lib.php, assets/img/studio/): the owner's photos of an artist's studio, cropped for phone ("tall", 640 / 960) and computer ("wide", 1280 / 1333), in AVIF + WebP + JPEG (phone copies 16 to 52 KB). Mood only: NEVER shown as artworks for sale or as an Oweili artist's work, never in the Gallery grid or sitemap. owStudioPhoto($key, $eager) prints the <picture> with alt text in English and Arabic; photos at the top of a page load at once, the rest lazily
- Where: homepage (home-painting, behind the video: it shows at once, the video fades in over it, and phones on slow connections or with reduced motion see only the photo), Join as an artist (brushes-jars, strip at the top of the card), Gifts (pink-palette, behind the header), Explore Art Styles (paint-buckets, behind the header), the 404 page and the empty Gallery (paint-cloth, strip). Styles in oweili.css: .ow-photo-head (photo behind a header with --studio-veil: white veil in light mode, navy in dark mode) and .ow-card-photo (rounded strip). Waiting for the files (they arrived only as pictures in the chat): "hand on the palette" → Commissions, "woman looking at framed paintings" → Art Events / Discover, "studio table with brushes" → Support (add to OW_STUDIO in studio_lib.php as palette-hand, gallery-visit, studio-table)
- Timed (phone, 4G): first content 0.38 to 0.49 s on every page with a photo
- Files: studio_lib.php, assets/img/studio/ (new); gallery_lib.php, nav.php, sitemap.php, auctions.php, admin_settings.php, marketplace.php, theme_head.php, assets/css/oweili.css, index.php, gifts.php, explore.php, register_artist.php, 404.php. Tables: site_settings (one new setting, no structure change), auction_waitlist (unchanged)

### Smooth page changes on iPhone and Android (5 Nov 2026)
- elitport.com could not be opened from this environment (its network rules block the site), so the work followed the three causes found on Oweili
- Content shows at once: every page-open "rise" (riseUp, mpRise, ow-riseUp, ow-fadeUp, the homepage .fade with its 0.05–0.5 s delays, discover .reveal sections hidden until scrolled) is now one 0.15 s fade from 70% to full, with no delay and no movement; .reveal sections are simply visible. Never add page-open animations that start hidden, move, or wait. Hover lifts and the windows that open on a tap keep their own small motion
- Back button (back/forward cache): PHP's session sends "no-store", which stopped browsers from keeping pages. Public pages now call owPublicPage() (page_cache.php) right after config.php: "private, no-cache" (kept only in the visitor's own browser, never by Hostinger's CDN). Public pages: index, marketplace, explore, community, gifts, auctions, support, institutes, articles_list, article (not ?preview), artwork, artist_dashboard (only ?id= of another artist, never one's own panel), login, register, register_artist, register_collector, privacy, terms, cert_verify, 404, commission (not the private ?ref / ?k link). Every other page stays "no-store"
- Sign-in safety: every page carries a short one-way code of the sign-in state (owStateCode: the account, that sign-in's own code and the admin sign-in; NOT the browser session number, because the live server gives visitors who are not signed in a new session on almost every page, which made every kept page reload). A page shown from a kept copy asks auth_state.php (never cached) and reloads if the visitor signed in or out since. Private pages (everything that is not public) always reload when Back shows them. Tested: signing out then Back shows the signed-out Gallery; Back to the Buyer dashboard after signing out lands on sign-in
- iPhone (Safari has no speculation rules): theme_head.php fetches the page the moment a finger touches a link (or the mouse rests 65 ms), with the header X-Ow-Prefetch: 1, at most 8 per page, not on slow 2G / 3G or Data Saver. owPublicPage() answers such a request with "private, max-age=10", so the tap opens that copy without downloading again (tested: one download per tap). Only PUBLIC pages are fetched early (the list in theme_head.php), never logout, email links, uploads, edit pages, staff pages or private links. Chrome / Edge / Android keep the speculation rules from 4 Nov
- Why Back reloaded on the live site before: the server sends a fresh session cookie with every page, and Chrome drops "no-store" pages from its back/forward cache whenever a cookie changes; "private, no-cache" pages stay kept. Test browsers meet Hostinger's own bot check ("Checking your browser…", a 403 page that reloads itself) on their first visits; timings are taken after it has passed
- Timed on an iPhone-sized phone and an Android phone (390 px, 4G 9 Mbps + 40 ms, 4x slower processor, median of 3); numbers are in the report of 5 Nov
- Files: page_cache.php, auth_state.php (new), theme_head.php, assets/css/discover.css, assets/css/oweili.css, index.php, marketplace.php, community.php, support.php, register.php, login.php, forgot.php, verify.php, and the public pages listed above. Tables: none

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

### Private commissions (portraits and personal photos), confidential (2 Nov 2026) — the request page is HIDDEN FOR NOW, to bring back later (6 Nov 2026; buyers' private links still work)
- commission.php («الأعمال الخاصة»): the buyer describes the piece (portrait, family, pet, place or memory, other; optional style, size, budget, date), adds 1 to 6 reference photos (JPG/PNG/WEBP, 10MB each; location and hidden data removed), confirms they may share them, and may agree IN WRITING that the finished piece can be shown (tick + typing their full name, saved with the date). Without that it is never shown anywhere
- Photos live in the PRIVATE folder .private/commissions/ (outside uploads/, blocked for visitors by .htaccess plus its own deny file, files readable only by the hosting account, kept by deploys through .gitignore). They open only through commission_file.php: the admin, the buyer with their private link, or the assigned artist after accepting the confidentiality agreement. Everyone else gets 404. Never cached or indexed. Each time the artist opens a reference photo it is logged
- The buyer gets a private link once (commission.php?ref=C-2026-XXXXXX&k=…; only its SHA-256 is stored) on the thank-you page and by email: the steps, accept the price or ask for another, approve the draft or ask for changes, see the finished piece, "I received it"
- Steps: request → admin assigns an artist (admin_commissions.php) → the artist accepts the confidentiality agreement inside the site (6 points in both languages, tick + typed name, saved with date, time and a monthly one-way address code in commission_nda) → the artist sends a price and days → the buyer accepts → the artist sends a draft → the buyer approves (or asks for changes) → the artist sends the final photo → the buyer ("I received it") or the admin marks it delivered. The admin can cancel with a reason. Emails go to the buyer and the artist at each step, alerts to the admin. The artist never sees the buyer's name, phone or email
- A commission is never an artwork row, so it never appears in the Gallery, a profile or the sitemap
- Reference photos are deleted automatically 30 days after delivery (or 30 days after a cancel), once a day after any page is delivered (theme_head.php → owCommCleanup), or now by the admin. Each deletion is logged (commission_photos_deleted); drafts and finals stay for the record
- No payment: the admin handles money by hand (private note field)
- Artist side: My Studio → Commissions tab, artist_commission.php?id=N
- Files: commission.php, commission_lib.php, commission_file.php, artist_commission.php, admin_commissions.php (new); artist_studio.php, admin.php, theme_head.php, alert_lib.php, marketplace.php, gifts.php, robots.txt, .gitignore (.private/). Tables: commissions, commission_files, commission_nda, users, activity_log

### Oweili Gifts: "Give a gift that lasts" / «أهدِ ما يبقى» (2 Nov 2026; tagline changed 5 Nov 2026) — HIDDEN FOR NOW, to bring back later (6 Nov 2026)
- gifts.php («هدايا أويلي»): occasions (wedding, graduation, new baby, Eid, Ramadan, thank you, Teacher's Day), a ready artwork from the Gallery (the 12 newest available, or the one chosen with ?artwork=N) or a custom one (what it should be + optional budget), gift wrapping, card message, delivery date (3 days to 1 year ahead), "Surprise" (the recipient never sees the price), the recipient's name, phone (optional), city and address, and the buyer's name, phone and email (filled in for a signed-in buyer)
- For now it is a request: saved in gift_requests, an email alert to the admin (alert kind 'gift', grouped like the others), a "we received your request" email to the buyer in both languages, and the admin follows up by hand in admin_gifts.php (Requests → Gifts): every detail, status New / Contacted / In progress / Done / Cancelled and a private note. No payment
- Safety: security token, hidden robot trap, at most 3 requests per visitor per hour, only artworks a visitor can see in the Gallery. Buyer and recipient details are shown only to the admin
- Links: the main menu (Collect → Gifts), Gallery top ("Give a gift that lasts" card, first page), "Give it as a gift" in the artwork window and on the artwork page, the footer (Gifts / الهدايا)
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

### Privacy Policy and Terms of Use (reviewed 11 Nov 2026, the draft banner removed; see the 11 Nov entry above)
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

### Upcoming Workshops (top of institutes.php) — HIDDEN FOR NOW, to bring back later (6 Nov 2026)
- What it does: "Upcoming Workshops" / «ورش العمل القادمة» with 5 planned workshops as cards in the same style as the institute cards: Desert Light in Watercolor, Desert Landscapes in Oil, Digital Arabic Calligraphy, Saudi Heritage Drawing (charcoal), Contemporary Saudi Expression (mixed media). Each shows its art type, title, a short line, "Coming Soon" / «قريباً» and a "Notify Me" / «أبلغني» button to register.php (allowed by rule 25: real planned feature with a notify button)
- Below it, "Institutes Directory" / «دليل المعاهد» introduces the institutes list
- Moved here from the old Studio page, which was removed from the menu and now redirects to explore.php
- Files: training.php, studio.php (redirect), nav.php (Studio and its dropdown removed; leftover Studio menu words removed from 15 pages). Tables: none for the workshops

### Community page (community.php): real numbers and real artworks — HIDDEN FOR NOW, to bring back later (6 Nov 2026)
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
- .htaccess — blocks public access to private files: CLAUDE.md and all .md files, all .sql files, skills-lock.json, hidden folders (.claude/, .agents/, .git/, .backups/, .logs/), log and backup files (.log, .bak, .old, .gz, .zip and similar) and the old resha-app/ and supabase/ folders. Visitors get 403 Forbidden. Nothing inside uploads/ can run as a script (13 Nov 2026, owner): any uploads/ address whose name carries a script ending (.php, .phtml, .phar, .cgi, .py, .sh, .asp, .jsp and similar, also hidden inside a name like x.php.jpg) answers 403; photos, videos and PDFs there open as before (checked live). Keep it when changing hosting settings
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
- assets/video/hero-wide.mp4 (blue, computers and phones, fills the screen; black side bars trimmed 6 Nov 2026) and hero-pink-wide.mp4 / hero-pink-tall.mp4 (pink) — the homepage videos, played in turn with a cross-fade (real Pexels footage; bg.mp4, an AI-made video, was removed on 5 Nov 2026). H.264, no sound, index at the front of the file so phones start playing at once. Only the homepage loads them (theme_head.php skips them on slow connections, Data Saver and reduced motion; phones get the owner's vertical pink video hero-pink-tall). To replace one, keep its name and keep it small (under 3 MB)
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
- bookings_update.sql — course booking and museum tickets (14 Nov 2026). Only adds (two account-type values, columns, five tables); safe to run more than once
- art_places_address_ar.sql — the Art Map address in Arabic (13 Nov 2026). Only adds one column; safe to run more than once. Run on the live database 13 Nov 2026 after a full backup
- art_place_claims.sql — the "Is this your place?" messages (13 Nov 2026). Only adds one table; safe to run more than once. Run on the live database 13 Nov 2026 after a full backup
- art_places.sql — the Art Map's places table (12 Nov 2026). Only adds one table; safe to run more than once. Run on the live database 12 Nov 2026 after a full backup
- database_update.sql — the 8 new tables for the 5 pillars (safe to run more than once). Already in place on the live database (checked 30 Oct 2026)
- visitor_stats.sql — the visitor city statistics (date, country, city, count). Only adds one table; safe to run more than once. Run on the live database 9 Nov 2026 after a full backup
- portfolio_update.sql — artworks.sold_at and the portfolio_images columns the code uses (artist_id, sort_order). Only adds; safe to run more than once. Run on the live database 9 Nov 2026 after a full backup
- sizes_update.sql — the standard sizes: size_code, size_category, orientation in artworks, commissions and gift_requests. Only adds; safe to run more than once (5 Nov 2026)
- certificates_update.sql — the Certificate of Authenticity: new artwork columns (medium, size, year, original or print, declaration time) and the certificates table. Only adds; safe to run more than once. Already in place on the live database (1 Nov 2026)
- approvals_update.sql — the pending_changes table for "Nothing goes public without admin approval". Only adds one table; safe to run more than once. Already in place on the live database (31 Oct 2026)
- auctions_update.sql — the auctions waiting list (auction_waitlist). Only adds one table; safe to run more than once. Run on the live database 3 Nov 2026 after a full backup
- night_update.sql — collections, Artist of the week and settings (site_settings), Verified artist, gift requests, private commissions, orders and escrow (switched off). Only adds; safe to run more than once. Already in place on the live database (2 Nov 2026, full backup first: .backups/db-u405344949_contact-20261102-215015.sql.gz)
- dashboards_update.sql — the dashboards and Bashro parts (permanent IDs, dates, request status, favorites, activity log, empty shipping and payout tables). Safe to run more than once. Already in place on the live database (30 Oct 2026)

## Design System
- Style: Luxury white minimal
- Background: one real Pexels video on the homepage (blue on computers, pink on phones), WIDE studio photos only behind the other pages (cover, sized to the screen, centre kept in view on phones); no veil, text on small frosted-glass cards (6 Nov 2026)
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
- Toggle button in navbar switches language; the choice is saved in the cookie ow_lang and the SERVER sends each page in that language (lang_lib.php, see Features 5 Nov 2026). New pages must print <html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?>>
- All pages must support both directions
- dir attribute must be set on html element when switching

## Navigation Structure
- Now (owner, 6 Nov 2026, night; Art Map since 12 Nov 2026): no Discover dropdown. Gallery · Art Styles («أساليب الرسم», renamed from Explore Art Styles) · Art Events · Art Education · Art Map (Arabic menu word «وجهات»; was Art Institutes) · Support ↓ (Artist Spotlights, Community and the Collect menu come back only when switched on). The lines below describe the older layout
- Order (owner's choice): Gallery · Collect ↓ · Explore Art Styles · Discover ↓ · Community · Support ↓ (Community, Gifts, Commissions and Artist Spotlights are hidden for now, to bring back later, 6 Nov 2026: while hidden the menu is Gallery · Explore Art Styles · Discover ↓ (Art Events, Art Education, Art Institutes) · Support ↓, and the Collect menu is gone because all its pages are hidden) (Arabic, right to left: المعرض · اقتنِ · استكشف أساليب الرسم · اكتشف · المجتمع · الدعم). The ☰ phone menu uses the same order
- Collect ↓ / «اقتنِ» → Gifts / «هدايا» (gifts.php, hidden for now: then the top word opens commission.php), Commissions / «طلب لوحة خاصة» (commission.php), and Auctions / «المزاد» (auctions.php, with a small "Soon" tag) ONLY while the Auctions page is switched on in Admin → Settings (hidden since 5 Nov 2026). The top word opens gifts.php
- Gallery / «المعرض» → marketplace.php, no dropdown (never call it "Marketplace" / «السوق» on the site)
- Explore Art Styles → explore.php, no dropdown
- Discover ↓ → Art Events / Artist Spotlights (hidden for now, to bring back later) / Art Education (back in the menu since 6 Nov 2026) (articles_list.php?type=...) / Art Institutes (institutes.php, its own page; training.php redirects there). Art Education was taken out of the menu because Explore Art Styles covers it; its page (articles_list.php?type=art_education) still works and is reachable from the type buttons on the articles pages
- Community → community.php (hidden for now, to bring back later; direct link, no dropdown; Meet Fellow Artists, Share Your Work and Learn Together were removed because they led nowhere specific)
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
- Fixed: password reset (and sign-up) emails did not arrive. All emails now go through mail_lib.php (see Shared Files), which talks to the Hostinger mailbox directly, tries both mail ports, sends from the mailbox address, and logs the exact reason when sending fails. The reset email is now in English and Arabic, and its time limit (24 hours since 9 Nov 2026) is counted by the database clock, so a time-zone difference between PHP and MySQL can no longer make a fresh link expire at once. Files: mail_lib.php, mail_check.php, admin.php, forgot.php, register_artist.php, register_collector.php, verify.php. Tables: users (reset_token, reset_expires)
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
- STRICT RULE (owner, 6 Nov 2026, standing rule for every task): change ONLY what the owner asks for. Do not change anything else: no photos, colors, text, layout, menu, footer, sizes or other pages. If something else seems to need changing, ask the owner first and wait for the answer

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
- No flowers in marketing (owner, 5 Nov 2026): Oweili never compares itself with flower shops. No "flower", "flowers", "bouquet", «ورد», «زهور», «باقة», «بدل الورد» in OUR OWN marketing text: Gifts, buttons, banners, emails, page titles and descriptions. The rule does NOT apply to Explore Art Styles (art_styles.js): those are historical facts about artists and stay accurate (e.g. Ai Weiwei's work is really titled "Sunflower Seeds", Nolde painted flowers, Rory McEwen painted single flowers). Gifts tagline: "Give a gift that lasts" / «أهدِ ما يبقى»; the message is a handmade artwork that stays for years. «اللازورد» (lapis) and «وردي» (pink) are other words
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
