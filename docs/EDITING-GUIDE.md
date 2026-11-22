# How to run your website — no code needed

Everything below is done by clicking and typing in your WordPress dashboard (`yourdomain.com/wp-admin`). You never need to touch HTML or code.

---

## Products (formulations, SOPs) and services (SDS)

### Add a new formulation or SOP
1. **Products → Add New Product**
2. Type the **name** and the **description** (big box), and a **short description** (small box lower down).
3. In the **Product data** box:
   - **General** tab: type the **Regular price**. Add a **Sale price** if you want a discount.
   - Tick **Virtual** and **Downloadable**, then click **Add File** and upload your PDF or Word file.
   - **Bionexa** tab: set **Product kind** to *Formulation* or *SOP / quality document*. Fill in the specifications you want shown (standard, version, pages, what's included — one item per line).
4. On the right, tick its **Product category** (e.g. *Formulations → Cosmetics*).
5. Click **Set product image** to add a picture.
6. Click **Publish**.

### Add a new SDS service package
Same as above, but:
- Tick **Virtual** only (not Downloadable).
- **Bionexa** tab → **Product kind** = *SDS preparation service*. Boxes appear for **Each extra language** price and **Rush delivery fee**.
- Category: **SDS Services**.

The **SDS Information Form** appears on the product page automatically. It follows your own Word form:
1. Product information: name, brand, intended use, physical form, website.
2. Supplier / manufacturer details: company and address, phone, email, emergency contact, logo upload.
3. Composition: chemical names, CAS numbers and percentages, plus a formula upload.
4. Physical and chemical properties (optional, fold-out): appearance, pH, boiling, melting and flash point, density, solubility.
5. Regulatory: countries, upload of existing SDS, sales channel.

At the top there is a **"Prefer to fill in our Word form?"** box. Customers can download your blank Word form, fill it in, and upload it. If they upload it, they only need to type the product name and tick the countries.

It ends with delivery options: languages (extra cost), rush delivery (extra cost) and notes.

All answers and uploaded files appear on the order in **WooCommerce → Orders**, and in the new-order email you receive.

### Edit or remove a product
- **Products → All Products**, hover over a product → **Edit** (change anything, click **Update**) or **Trash** (removes it from the shop).
- To hide a product without deleting it: Edit → right side, **Catalog visibility** → *Hidden*, or change **Status** to *Draft*.

### Product categories
**Products → Categories**: add, rename or delete categories (e.g. add "Pharmaceutical"). Categories appear in the shop automatically.

---

## Change the SDS order form and quote form choices

**Bionexa Settings** (left menu). Each box is one item per line — just type:
- countries / regulations, product types, sales channels (Amazon, eBay…), languages
- services listed in the Request a Quote form
- **the Word form link**. To replace the Word form: **Media → Add New Media File**, upload the new file, click it, press **Copy URL to clipboard**, and paste it into *SDS form – Word form download link*. Empty the box to hide the "Prefer Word?" box.
- physical forms (solid, liquid, powder…)
- **your own extra questions** for the SDS form: type one question per line, e.g. "UN number (if known)". Each appears as an optional box under "Additional information"
- the email address that receives quote requests
- the "Order Now" button text

Click **Save Changes**. Empty a box and save to get the original list back.

---

## Edit page text and pictures

1. **Pages → All Pages** → click a page (Home, SDS Services, About, Contact…).
2. Click on any text and type. Click any button to change its words or link.
3. To add a section: click the **+** (top left) → **Patterns** → **Bionexa** → drag in a ready-made section (FAQ, services, call-to-action…). Or add simple blocks: Paragraph, Heading, Image, Button, Video.
4. To remove a section: click it → the **⋮** menu on the little toolbar → **Delete**.
5. Click **Save**.

**Product grids on pages** (e.g. "Ready-to-use formulations" on the Home page): click the grid → the right sidebar lets you choose a different category and the number of columns/rows.

**Add a new page**: Pages → Add New Page, type a title and content, click **Publish**. Then add it to the menu (below).

---

## Menu, header, footer, logo and colours

**Appearance → Editor**:
- **Navigation** — add, remove, rename or reorder menu links.
- **Patterns → Header / Footer** — change the footer email, phone, links and copyright text.
- **Styles** (half-black circle icon) — change colours and fonts for the whole site.
- **Logo**: in the Header, click the logo placeholder and upload your logo.

Click **Save** when done.

---

## Orders, downloads and quotes

- **WooCommerce → Orders** — every order. Open one to see what the customer filled in on the SDS form and click **Download customer file**. When you finish an SDS, email it to the customer and set the order status to **Completed**.
- Formulations and SOPs are delivered automatically: the customer gets a download link by email and in **My Account → Downloads**.
- **Quote Requests** (left menu) — every quote request, with a **Reply by email** button.

## Discounts and coupons
**Marketing → Coupons → Add coupon**: e.g. code `WELCOME10`, 10% off.

---

## Things to set up once
- **WooCommerce → Settings → Payments**: connect Stripe (cards) and/or PayPal.
- **WooCommerce → Settings → General**: currency, store address.
- **Settings → General**: site title and tagline.
- Review the text on **Terms & Conditions**, **Formulation & Document Licence**, **Refund Policy** and **Privacy Policy** pages.

If you ever get stuck, WordPress has a large help community and nearly every hosting company offers WordPress support by chat.
