# 🔍 Maroc PC — Project Improvement Suggestions

After a full review of your codebase, here are prioritized suggestions across security, architecture, UX, and features.

---

## 🔴 Critical — Security & Bugs

### 1. Open Redirect Vulnerability in `login.php`
The `$next` parameter is taken directly from user input and used in `header("Location: $next")`. An attacker could craft a URL like `login.php?next=https://evil.com` to redirect users after login.
- **Fix:** Validate that `$next` is a relative URL or belongs to your domain before redirecting.

### 2. Remember Me Token Not Stored in DB
In `login.php`, the "remember me" cookie generates a token but **never stores it** in the database (line 63 says "Optionnel"). This means:
- The token can never be validated on return visits
- The feature is essentially non-functional
- **Fix:** Create a `remember_tokens` table and verify tokens on session restore.

### 3. Checkout Page Has a Debug/Agent Log Script
`checkout.html` (line 556–559) contains an inline script that sends data to `http://127.0.0.1:7242/ingest/...` — this looks like leftover debug/instrumentation code. **Remove it before production.**

### 4. Admin Auth Has No Role Verification
`adminRequireAuth()` only checks `$_SESSION['admin_id']` — there's no role or privilege level check. If someone manages to set that session key, they get full access.
- **Fix:** Store and verify an admin role/flag from the database.

### 5. Password Trimming in `signup.php`
Line 17: `$pass_raw = trim($_POST["pass"] ?? "")` — trimming passwords can silently remove intentional leading/trailing spaces, weakening passwords users chose deliberately.

### 6. Checkout Title Says "TechGear" Instead of "Maroc PC"
`checkout.html` line 7: `<title>Checkout - TechGear</title>` — looks like a leftover from a template.

---

## 🟠 Important — Architecture & Code Quality

### 7. Duplicate Code Everywhere (DRY Violations)
The **role modal** HTML + JS is copy-pasted across `index.html`, `products.html`, `cart.html`, and `checkout.html`. Same for:
- Header/nav markup
- Footer markup
- Sidebar markup
- Theme toggle logic

> **Suggestion:** Extract shared components. Options:
> - Use PHP includes (`header.php`, `footer.php`, `role-modal.php`)
> - Or convert `.html` pages to `.php` pages so you can use `require_once`

### 8. Mixed `.html` and `.php` Pages
Some pages are `.html` (index, products, cart, checkout) and some are `.php` (login, signup, account, dashboard). This creates inconsistency:
- HTML pages can't use PHP session data directly
- You rely on a separate `auth-status.php` AJAX call to check login state
- **Suggestion:** Convert all pages to `.php` for consistency and server-side rendering of auth state.

### 9. Dual Data Source Problem
Products exist in **both** `js/data.js` (client-side) and the MySQL `products` table (server-side). The admin dashboard syncs DB → `data.js`, but this creates risks:
- Data can go out of sync
- Client-side data can be tampered with
- **Suggestion:** Long-term, serve products from a REST API endpoint and remove the static `data.js` file.

### 10. CSS Files Are Scattered and Redundant
You have CSS in at least **8 locations** with overlapping styles:
- Root: `index.css`, `cart.css`, `products.css`, `signup.css`, `login.css`, `checkout.css`, `dashboard.css`, `light-mode-industrial.css`
- `/css/`: `styles.css`, `checkout.css`, `dashboard.css`, `account.css`, `auth-nav.css`
- Inline `<style>` blocks in PHP files

> **Suggestion:** Consolidate into a design system: `base.css` (tokens/reset), `components.css` (reusable), `layout.css` (page-specific).

### 11. `<script>` Tags Outside `<body>` in `index.html`
Lines 18-19: `data.js` and `products.js` are loaded between `</head>` and `<body>`, which is invalid HTML placement.

---

## 🟡 Recommended — UX & Features

### 12. Newsletter Form Doesn't Work
The newsletter section in `index.html` has a `<button type="button">` but no handler. The `api/subscribe.php` endpoint exists but is never called from the frontend.

### 13. Checkout Has No Login Gate
The checkout page shows an "Account Required (Sign In)" radio but doesn't actually enforce it. A user can fill out the form and place an order without being logged in, which could create orphan orders.

### 14. Search Is Client-Side Only
The product search works by filtering `data.js` in the browser. For a larger catalog, this won't scale. Consider adding a server-side search API endpoint (your AI handler already has MySQL full-text search — reuse that pattern).

### 15. No Product Detail Page
Clicking a product shows a quick-view modal, but there's no dedicated product page with:
- Full description
- Multiple images
- Customer reviews
- Related products
- SEO-friendly URL (`/product/rtx-4090`)

### 16. No Wishlist / Favorites Feature
There's a user account system with orders, but no way to save products for later. This is a high-engagement feature for e-commerce.

### 17. No Order Confirmation Email
The `place-order.php` API creates orders but doesn't send any confirmation email. You already have the `sendResetEmail()` mail infrastructure — extend it.

### 18. Checkout State Dropdown Lists US States
The shipping form in `checkout.html` lists Alabama, Alaska, California, etc. Since this is a Moroccan store, it should list **Moroccan regions/cities** (Casablanca-Settat, Rabat-Salé-Kénitra, etc.).

### 19. Footer Copyright Says 2024 in Checkout
`checkout.html` line 540 says `© 2024` while other pages say `© 2026`.

---

## 🟢 Nice to Have — Polish & Performance

### 20. Large Unoptimized Images
- `gpu.png` = **1.99 MB**
- `gup-light.png` = **1.35 MB**  
- `signup.png` = **1.72 MB**
- `logo.png` = **716 KB**

> **Suggestion:** Convert to WebP, compress, and serve responsive sizes with `<picture>` / `srcset`.

### 21. No Favicon
There's no `<link rel="icon">` on any page. Add a favicon for brand identity in browser tabs.

### 22. Missing `<meta name="description">` on Most Pages
Only the homepage has a semi-descriptive title. Add proper meta descriptions for SEO.

### 23. Mixed Language (French + English)
- Login/signup forms are in **French** (labels, error messages, buttons)
- Homepage, products, cart, checkout are in **English**
- Google Translate widget is used as a workaround

> **Suggestion:** Pick a primary language and use a proper i18n system, or at least be consistent. For a Moroccan audience, French or bilingual FR/AR would be more natural.

### 24. No Loading States on API Calls
When placing an order or the AI chatbot is processing, there's no loading spinner or disabled button state to prevent double-submissions.

### 25. `index2.html` / `index2.css` Are Orphaned Files
These appear to be test/scratch files (168 bytes CSS, 733 bytes HTML) with no links pointing to them. Consider cleaning them up.

---

## 📊 Summary Table

| Priority | Count | Category |
|----------|-------|----------|
| 🔴 Critical | 6 | Security & Bugs |
| 🟠 Important | 5 | Architecture & Code Quality |
| 🟡 Recommended | 8 | UX & Features |
| 🟢 Nice to Have | 6 | Polish & Performance |
| **Total** | **25** | |

---

> [!TIP]
> If I had to pick the **top 5 to tackle first**, they would be:
> 1. Fix the open redirect in login.php (#1)
> 2. Remove the debug script from checkout.html (#3)
> 3. Convert HTML pages to PHP and extract shared components (#7, #8)
> 4. Fix the checkout page for Moroccan context (#6, #18, #19)
> 5. Optimize the large images (#20)

Let me know which ones you'd like me to help implement!
