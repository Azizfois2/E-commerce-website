# Goal Description

To execute a major strategic pivot from the "Lego-style" DIY component market to the "Pre-built Lifestyle" laptop market. We will build the **Advanced+ Laptop Finder Ecosystem**. 

Unlike Newegg's exhaustive filter library, our tool will be an **Outcome-Oriented Curator**. It will focus on what the laptop *does* (e.g., portability, gaming frames, screen clarity) rather than raw spec dumping. 

## User Review Required

> [!IMPORTANT]
> **The Data-Entry Nightmare**: You correctly identified that managing 50+ laptop SKUs with volatile lifecycles is the biggest risk. As an AI, I cannot magically scrape your offline local suppliers, but I *can* build an architecture to mitigate this.
> **Proposal**: Alongside the manual `admin-laptop-form.php`, I propose building a **CSV Importer tool** in the admin dashboard. This will allow you to take an Excel/CSV file from your supplier and bulk-upload/update laptop inventory and prices in 3 seconds.

## Open Questions

> [!WARNING]
> 1. **Data Sourcing**: Do your local suppliers provide you with Excel/CSV sheets for their inventory? If so, adding a CSV bulk-importer is the best way to avoid the data-entry nightmare.
> 2. **Split Navigation**: For the desktop navbar, we will split "Products" into a dropdown/dual-button: **[ Build Your PC ]** (Component heavy) and **[ Find Your Laptop ]** (Outcome heavy). Does this align with your vision?

## Proposed Changes

### Phase 1: The Database Architecture
#### [NEW] `setup-laptops-table.php`
- Executes the SQL to create the `laptops` table with the exact schema you provided (`usage_category`, `battery_wh`, `weight_kg`, etc.).

#### [NEW] `admin-laptops.php` & `admin-laptop-form.php`
- Dedicated CRUD interfaces for managing laptops, distinct from raw components.
- *(Optional based on your feedback: Add CSV Bulk Import functionality).*

#### [NEW] `export-laptops.php` -> `assets/js/laptop_data.js`
- Separates laptop data from `data.js` to ensure fast loading speeds.
- Includes computed "Advanced Data" scores (e.g., Portability Score calculated from `weight_kg` + `screen_size`).

### Phase 2: The UI & Finder Core
#### [NEW] `laptop-finder.php`
- The curated interface featuring the **5 Golden Filters**:
  1. Budget (Price Range)
  2. Primary Use (Gaming, Business, Student, Creative)
  3. Portability (Ultralight, Standard, Desktop Replacement)
  4. Screen Quality (OLED, High Refresh, Standard)
  5. GPU Tier (Integrated vs Dedicated)
- **The Upsell**: An aggressive recommendation for the **"Laptop Optimization Pack"** (Clean Windows install, Bloatware removal, Thermal repaste) during checkout/selection to increase margin.

### Phase 3: Global Navigation & Homepage
#### [MODIFY] `index.html`
- Replace generic laptop listings with **Thematic Categorization**:
  - "The Road Warrior" (Business, <1.5kg, OLED)
  - "The Desktop Replacement" (Gaming, 18", RTX 4080+)
  - "The Value Student" (IPS, Long Battery)
- Implement the "Split Navigation" concept in the header.

---

## Verification Plan

### Automated Tests
- Database schema verification.

### Manual Verification
1. Bulk inject/manually create 5 diverse laptops (Gaming, Student, Creator, Ultrabook).
2. Test the Finder UI: Ensure selecting "Gaming" + "Heavy" + "High Budget" exclusively surfaces the Desktop Replacement laptop.
3. Verify that the "Laptop Optimization Pack" successfully adds to the cart alongside the laptop.
4. Verify `laptop_data.js` loads independently and correctly formats outcome-based scores.
