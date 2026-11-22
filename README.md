# Bionexa – WordPress + WooCommerce website

> **Running the site day to day?** Read [`docs/EDITING-GUIDE.md`](docs/EDITING-GUIDE.md) — step-by-step, no code.

A website you can edit yourself, for:

- **SDS (Safety Data Sheet) preparation for clients worldwide**, including Amazon, eBay, Walmart and Etsy sellers. Customers fill in an order form with the product name, type, countries/regulations, sales channel, languages, rush delivery and a composition file upload. The price adjusts automatically.
- **Selling formulations** as instant downloads: cosmetics, cleaning, industrial and agro.
- **Selling SOPs and quality documents** such as ISO/IEC 17025 kits, single SOPs, ISO 9001 and GMP.
- **Add to Cart** and **Order Now** buttons. Order Now goes straight to checkout.
- **Request a Quote** form for bulk SDS work, custom formulation and 17025 consultancy. Requests are saved in wp-admin and emailed to you.
- **Licence acceptance at checkout** for formulations and SOPs. The date and IP address are recorded on the order.

## What's in the repo

| Path | What it is |
|---|---|
| `wp-content/themes/bionexa/` | Block theme. You can edit everything in **Appearance → Editor**: header, footer, colours, fonts and templates. |
| `wp-content/plugins/bionexa-core/` | Business features: SDS order form, Order Now, product specs tab, licence checkbox and quote requests. |
| `setup/install.sh` + `setup/setup.php` | One-command install. It creates pages, categories, 11 sample products and store settings. It is safe to re-run. |
| `docker-compose.yml` | Local copy of the site for testing. |

## Run it on your computer

You need Docker Desktop.

```bash
cp .env.example .env          # change ADMIN_PASSWORD
docker compose up -d
./setup/install.sh
```

Then open http://localhost:8080. The admin area is at http://localhost:8080/wp-admin.

## Put it live on your .com

1. **Hosting.** Choose any WordPress host with PHP 8.1+, SSL and SSH/WP-CLI, for example SiteGround, Hostinger Business, Cloudways or Kinsta. Point your domain to it and install WordPress.
2. **Upload the code.** Upload `wp-content/themes/bionexa` and `wp-content/plugins/bionexa-core` to the same folders on the server, using SFTP or the hosting file manager.
3. **Run the setup over SSH.** From the WordPress folder, run:
   ```bash
   WP_CLI="wp" SETUP_DIR=/path/to/uploaded/setup SKIP_CORE_INSTALL=1 /path/to/uploaded/setup/install.sh
   ```
   If you don't have SSH, do it by hand: install WooCommerce, activate the **Bionexa** theme and the **Bionexa Core** plugin, then create the pages and products in wp-admin.
4. **Payments.** Go to WooCommerce → Settings → Payments and connect **Stripe** (cards, Apple Pay, Google Pay) and/or **PayPal**. Bank transfer is already switched on.
5. **Email.** Install an SMTP plugin, such as *WP Mail SMTP*, so order and quote emails don't land in spam.
6. **Your details.** Replace the placeholder email and phone number in the footer (Appearance → Editor → Patterns → Footer) and on the Contact page.
7. **Legal pages.** Have a lawyer review *Terms & Conditions*, *Formulation & Document Licence*, *Refund Policy* and *Privacy Policy*. All four are marked as templates.

## Day-to-day editing

- **Pages** (Home, SDS Services, About and the rest): open **Pages** and edit with blocks. Ready-made sections are under **+ → Patterns → Bionexa**.
- **Products**: open **Products → Add New**. In the **Bionexa** tab, set *Product kind*:
  - **SDS preparation service**: shows the order form. Set *Each extra language* and *Rush fee* prices here.
  - **Formulation** or **SOP / quality document**: tick *Virtual* + *Downloadable* and upload the file. Customers must accept the licence at checkout.
  - The *Specifications* fields (standard, version, format, pages, what's included) appear as a tab on the product page.
- **SDS orders**: open **WooCommerce → Orders**. Each SDS line shows the customer's answers and a **Download customer file** button. Uploaded files are stored privately and can't be opened through a web link.
- **Quote requests**: listed in the **Quote Requests** menu, with a *Reply by email* button.
- **Header menu**: Appearance → Editor → Navigation.
- **SDS form & quote form choices** (countries, languages, channels, services, Order Now text, quote email): **Bionexa Settings** in the admin menu — one item per line.

The sample products use placeholder text files as downloads. Replace them with your real PDF/Word files, and change the prices, before you launch.

See [`docs/ROADMAP.md`](docs/ROADMAP.md) for extra features you can add.
