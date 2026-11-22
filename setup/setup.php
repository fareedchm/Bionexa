<?php
/**
 * Creates Bionexa pages, product categories, sample products and store settings.
 * Run with:  wp eval-file setup/setup.php [CURRENCY]
 *
 * Idempotent: pages are matched by slug, categories by slug, products by SKU.
 */

if ( ! defined( 'ABSPATH' ) || ! class_exists( 'WooCommerce' ) ) {
	fwrite( STDERR, "Run this with WP-CLI after WooCommerce is active.\n" );
	return;
}

$currency = strtoupper( $args[0] ?? 'USD' );

/* -------------------------------------------------------------------------- */
/* Helpers                                                                    */
/* -------------------------------------------------------------------------- */

/** Return a registered pattern's markup with nested pattern references expanded (so it is fully editable). */
function bionexa_setup_pattern( string $slug, int $depth = 0 ): string {
	$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( $slug );
	if ( ! $pattern || $depth > 5 ) {
		WP_CLI::warning( "Pattern $slug not found." );
		return '';
	}
	return preg_replace_callback(
		'#<!-- wp:pattern \{"slug":"([^"]+)"\} /-->#',
		static fn( $m ) => bionexa_setup_pattern( $m[1], $depth + 1 ),
		$pattern['content']
	);
}

function bionexa_setup_paragraphs( array $paragraphs ): string {
	$out = '';
	foreach ( $paragraphs as $p ) {
		if ( str_starts_with( $p, '## ' ) ) {
			$out .= "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html( substr( $p, 3 ) ) . "</h2>\n<!-- /wp:heading -->\n\n";
		} else {
			$out .= "<!-- wp:paragraph -->\n<p>" . $p . "</p>\n<!-- /wp:paragraph -->\n\n";
		}
	}
	return $out;
}

/** Create a page if no page with that slug exists. Returns the page ID. */
function bionexa_setup_page( string $slug, string $title, string $content, string $template = '' ): int {
	$existing = get_page_by_path( $slug, OBJECT, 'page' );
	if ( $existing ) {
		WP_CLI::log( "  page exists: /$slug/" );
		return (int) $existing->ID;
	}
	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_title'   => $title,
			'post_content' => $content,
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}
	if ( $template ) {
		update_post_meta( $id, '_wp_page_template', $template );
	}
	WP_CLI::log( "  page created: /$slug/" );
	return (int) $id;
}

function bionexa_setup_category( string $name, string $slug, int $parent = 0, string $description = '' ): int {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $term ) {
		return (int) $term->term_id;
	}
	$result = wp_insert_term( $name, 'product_cat', array( 'slug' => $slug, 'parent' => $parent, 'description' => $description ) );
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( $result->get_error_message() );
	}
	WP_CLI::log( "  category created: $name" );
	return (int) $result['term_id'];
}

/** A placeholder download so sample products work end-to-end. Replace with real files. */
function bionexa_setup_sample_file( string $sku, string $title ): string {
	$uploads = wp_upload_dir();
	$dir     = trailingslashit( $uploads['basedir'] ) . 'woocommerce_uploads/bionexa-samples';
	wp_mkdir_p( $dir );
	$file = "$dir/" . sanitize_file_name( strtolower( $sku ) ) . '.txt';
	if ( ! file_exists( $file ) ) {
		file_put_contents( $file, "$title\n\nSAMPLE FILE – replace this download in WooCommerce → Products → Edit product → Downloadable files.\n" );
	}
	return trailingslashit( $uploads['baseurl'] ) . 'woocommerce_uploads/bionexa-samples/' . basename( $file );
}

function bionexa_setup_product( array $p ): void {
	if ( wc_get_product_id_by_sku( $p['sku'] ) ) {
		WP_CLI::log( "  product exists: {$p['name']}" );
		return;
	}

	$product = new WC_Product_Simple();
	$product->set_name( $p['name'] );
	$product->set_sku( $p['sku'] );
	$product->set_regular_price( (string) $p['price'] );
	$product->set_virtual( true );
	$product->set_category_ids( $p['cats'] );
	$product->set_short_description( $p['short'] );
	$product->set_description( $p['description'] );
	$product->set_catalog_visibility( 'visible' );
	$product->set_status( 'publish' );

	if ( ! empty( $p['featured'] ) ) {
		$product->set_featured( true );
	}

	if ( in_array( $p['kind'], array( 'formulation', 'sop' ), true ) ) {
		$product->set_downloadable( true );
		$product->set_download_limit( 5 );
		$product->set_download_expiry( 30 );
		$download = new WC_Product_Download();
		$download->set_name( $p['name'] );
		$download->set_file( bionexa_setup_sample_file( $p['sku'], $p['name'] ) );
		$download->set_id( wp_generate_uuid4() );
		$product->set_downloads( array( $download ) );
	} else {
		$product->set_sold_individually( false );
	}

	$product->update_meta_data( '_bionexa_kind', $p['kind'] );
	foreach ( $p['meta'] ?? array() as $key => $value ) {
		$product->update_meta_data( $key, $value );
	}

	$product->save();
	WP_CLI::log( "  product created: {$p['name']}" );
}

/* -------------------------------------------------------------------------- */
/* Store settings                                                             */
/* -------------------------------------------------------------------------- */

WP_CLI::log( 'Store settings' );

$settings = array(
	'woocommerce_currency'                           => $currency,
	'woocommerce_enable_guest_checkout'              => 'yes',
	'woocommerce_enable_signup_and_login_from_checkout' => 'yes',
	'woocommerce_enable_myaccount_registration'      => 'yes',
	'woocommerce_cart_redirect_after_add'            => 'no',
	'woocommerce_enable_ajax_add_to_cart'            => 'yes',
	'woocommerce_downloads_grant_access_after_payment' => 'yes',
	'woocommerce_downloads_require_login'            => 'no',
	'woocommerce_file_download_method'               => 'xsendfile' === get_option( 'woocommerce_file_download_method' ) ? 'xsendfile' : 'force',
	'woocommerce_calc_taxes'                         => 'no',
	'woocommerce_ship_to_countries'                  => 'disabled', // Everything is digital / a service.
	'woocommerce_coming_soon'                        => 'no',
	'woocommerce_onboarding_profile'                 => array( 'skipped' => true ),
	'woocommerce_task_list_hidden'                   => 'yes',
	'woocommerce_show_marketplace_suggestions'       => 'no',
	'woocommerce_allow_tracking'                     => 'no',
	'blogdescription'                                => 'SDS preparation, formulations & ISO/IEC 17025 documents — worldwide',
);
foreach ( $settings as $key => $value ) {
	update_option( $key, $value );
}

// Bank transfer is enabled so orders can be tested immediately; add Stripe / PayPal for cards.
$bacs            = get_option( 'woocommerce_bacs_settings', array() );
$bacs['enabled'] = 'yes';
$bacs['title']   = $bacs['title'] ?? 'Direct bank transfer';
update_option( 'woocommerce_bacs_settings', $bacs );

/* -------------------------------------------------------------------------- */
/* WooCommerce pages (classic shortcodes so all Bionexa checkout features work) */
/* -------------------------------------------------------------------------- */

WP_CLI::log( 'WooCommerce pages' );
WC_Install::create_pages();

$classic = array(
	'woocommerce_cart_page_id'     => '<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->',
	'woocommerce_checkout_page_id' => '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->',
);
foreach ( $classic as $option => $content ) {
	$page_id = (int) get_option( $option );
	if ( $page_id && ! str_contains( (string) get_post_field( 'post_content', $page_id ), '[woocommerce_' ) ) {
		wp_update_post( array( 'ID' => $page_id, 'post_content' => $content ) );
	}
}

/* -------------------------------------------------------------------------- */
/* Product categories                                                         */
/* -------------------------------------------------------------------------- */

WP_CLI::log( 'Product categories' );

$cat_sds  = bionexa_setup_category( 'SDS Services', 'sds-services', 0, 'Safety Data Sheet preparation, review and translation for every market and marketplace.' );
$cat_form = bionexa_setup_category( 'Formulations', 'formulations', 0, 'Ready-to-use formulations with ingredient lists, percentages, process steps and QC parameters. Instant download.' );
$cat_cos  = bionexa_setup_category( 'Cosmetics & Personal Care', 'cosmetics-personal-care', $cat_form );
$cat_hh   = bionexa_setup_category( 'Household & Cleaning', 'household-cleaning', $cat_form );
$cat_ind  = bionexa_setup_category( 'Industrial', 'industrial', $cat_form );
bionexa_setup_category( 'Agro & Fertiliser', 'agro', $cat_form );
$cat_sop  = bionexa_setup_category( 'SOPs & Quality Documents', 'sops-quality-documents', 0, 'Editable SOPs, manuals, forms and records for ISO/IEC 17025, ISO 9001 and GMP.' );
$cat_iso  = bionexa_setup_category( 'ISO/IEC 17025', 'iso-iec-17025', $cat_sop );
bionexa_setup_category( 'ISO 9001 & GMP', 'iso-9001-gmp', $cat_sop );

/* -------------------------------------------------------------------------- */
/* Pages                                                                      */
/* -------------------------------------------------------------------------- */

WP_CLI::log( 'Pages' );

$home = bionexa_setup_page( 'home', 'Home', bionexa_setup_pattern( 'bionexa/home' ) );
bionexa_setup_page( 'sds-services', 'SDS Services', bionexa_setup_pattern( 'bionexa/page-sds-services' ), 'page-wide' );
bionexa_setup_page( 'request-a-quote', 'Request a Quote', bionexa_setup_pattern( 'bionexa/page-quote' ) );
bionexa_setup_page( 'contact', 'Contact', bionexa_setup_pattern( 'bionexa/page-contact' ) );
bionexa_setup_page( 'about', 'About Bionexa', bionexa_setup_pattern( 'bionexa/page-about' ) );

$licence = bionexa_setup_page(
	'formulation-licence',
	'Formulation & Document Licence',
	bionexa_setup_paragraphs(
		array(
			'<strong>Template – have this reviewed by a lawyer for your jurisdiction before going live.</strong>',
			'## 1. Licence',
			'When you buy a formulation, SOP or other document from Bionexa you receive a non-exclusive, non-transferable licence to use it inside your own business (one legal entity) to manufacture products, train staff or run your quality system.',
			'## 2. You may',
			'Manufacture and sell finished products made with a purchased formulation; edit SOPs and forms and use them in your own laboratory or company; print copies for internal use.',
			'## 3. You may not',
			'Resell, share, publish or upload the files (or modified versions of them) to other people, marketplaces or websites; claim authorship of the documents; or use them to offer competing document or formulation services.',
			'## 4. Safety and responsibility',
			'Formulations are starting points for qualified people. You are responsible for raw-material quality, stability, efficacy and safety testing, product registration and labelling in your market. Bionexa is not liable for products you manufacture.',
			'## 5. SDS services',
			'Safety Data Sheets are prepared from the information you provide. You must make sure that information is complete and correct and inform us of any changes to the composition.',
			'## 6. Refunds',
			'Because digital files are delivered instantly, they are non-refundable once downloaded, unless a file is defective and we cannot fix it.',
		)
	)
);
update_option( 'bionexa_licence_page_id', $licence );

$terms = bionexa_setup_page(
	'terms-and-conditions',
	'Terms & Conditions',
	bionexa_setup_paragraphs(
		array(
			'<strong>Template – replace with your own terms (company name, address, governing law) before going live.</strong>',
			'## Orders',
			'Prices are shown in ' . esc_html( $currency ) . '. An order is confirmed when payment is received. Services start once we have the information needed to complete them.',
			'## Delivery',
			'Downloadable products are available immediately after payment in My Account → Downloads. SDS and other services are delivered by email within the stated turnaround after we receive complete information.',
			'## Confidentiality',
			'All formulas, compositions and documents you share with us are treated as confidential and used only to deliver your order.',
			'## Licence',
			'Use of purchased files is governed by our <a href="' . esc_url( get_permalink( $licence ) ) . '">Formulation &amp; Document Licence</a>.',
		)
	)
);
update_option( 'woocommerce_terms_page_id', $terms );

bionexa_setup_page(
	'refund-policy',
	'Refund Policy',
	bionexa_setup_paragraphs(
		array(
			'<strong>Template – adjust to your business.</strong>',
			'Digital downloads (formulations, SOPs, document kits) are non-refundable once downloaded, except where the file is defective and we cannot provide a corrected version.',
			'SDS and other services can be cancelled for a full refund before work starts. After work has started we refund the part of the service not yet delivered. Revisions are free for 30 days after delivery.',
		)
	)
);

// Remove WordPress / WooCommerce demo content that would only confuse.
foreach ( array( 'sample-page', 'refund_returns' ) as $demo_slug ) {
	$demo = get_page_by_path( $demo_slug, OBJECT, 'page' );
	if ( $demo ) {
		wp_delete_post( $demo->ID, true );
	}
}
$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
if ( $hello ) {
	wp_delete_post( $hello->ID, true );
}

// The footer links to the Privacy Policy, so publish WordPress's built-in template (review its text!).
$privacy_id = (int) get_option( 'wp_page_for_privacy_policy' );
if ( $privacy_id && 'draft' === get_post_status( $privacy_id ) ) {
	wp_update_post( array( 'ID' => $privacy_id, 'post_status' => 'publish' ) );
}

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home );

/* -------------------------------------------------------------------------- */
/* Sample products – edit prices, text and files in wp-admin                  */
/* -------------------------------------------------------------------------- */

WP_CLI::log( 'Sample products' );

$sds_includes = "16-section GHS SDS (Word + PDF)\nHazard classification, pictograms, H & P statements\nTransport section (UN / ADR / IATA / IMDG)\nFree revisions for 30 days";

$products = array(
	array(
		'sku'         => 'SDS-STD',
		'name'        => 'SDS Preparation – Single Product',
		'price'       => 149,
		'cats'        => array( $cat_sds ),
		'kind'        => 'sds',
		'featured'    => true,
		'short'       => 'A complete, GHS-compliant Safety Data Sheet for one product, written for the countries you sell in. First language included.',
		'description' => '<p>We classify your product and prepare a full 16-section SDS that meets the rules of each region you select — OSHA HazCom (USA), REACH/CLP (EU), UK REACH, WHMIS (Canada) and more.</p><p>Ideal for Amazon, eBay and Walmart sellers, importers, and private-label brands. Price is per product; add one order line per product.</p>',
		'meta'        => array(
			'_bionexa_standard'             => 'UN GHS Rev. 10 · OSHA · REACH/CLP · WHMIS',
			'_bionexa_format'               => 'Word (.docx) + PDF',
			'_bionexa_turnaround'           => '3–5 business days (48 h rush available)',
			'_bionexa_includes'             => $sds_includes,
			'_bionexa_extra_language_price' => '49',
			'_bionexa_rush_price'           => '75',
		),
	),
	array(
		'sku'         => 'SDS-AMZ',
		'name'        => 'Amazon / eBay Marketplace SDS Package',
		'price'       => 199,
		'cats'        => array( $cat_sds ),
		'kind'        => 'sds',
		'featured'    => true,
		'short'       => 'Listing stuck in dangerous-goods (hazmat) review? Get a marketplace-ready SDS plus an exemption sheet where applicable.',
		'description' => '<p>Everything you need to clear Amazon dangerous-goods review or eBay hazmat checks: a compliant SDS, a transport classification, and — for non-hazardous products — guidance on the exemption sheet. Includes one round of support if the marketplace asks for changes.</p>',
		'meta'        => array(
			'_bionexa_standard'             => 'Amazon DG requirements · GHS · IATA / IMDG',
			'_bionexa_format'               => 'Word (.docx) + PDF',
			'_bionexa_turnaround'           => '3–5 business days (48 h rush available)',
			'_bionexa_includes'             => $sds_includes . "\nExemption sheet guidance (where applicable)\nSupport with marketplace feedback",
			'_bionexa_extra_language_price' => '49',
			'_bionexa_rush_price'           => '75',
		),
	),
	array(
		'sku'         => 'SDS-REV',
		'name'        => 'SDS Review, Update or Re-branding',
		'price'       => 79,
		'cats'        => array( $cat_sds ),
		'kind'        => 'sds',
		'short'       => 'Already have an SDS? We check it against current rules, update it to the latest GHS revision, or re-brand a supplier SDS under your company name.',
		'description' => '<p>Upload your existing SDS and tell us which countries you sell in. We correct classification errors, update outdated regulatory references and deliver a clean, current version.</p>',
		'meta'        => array(
			'_bionexa_format'               => 'Word (.docx) + PDF',
			'_bionexa_turnaround'           => '2–3 business days',
			'_bionexa_extra_language_price' => '39',
			'_bionexa_rush_price'           => '50',
		),
	),
	array(
		'sku'         => 'FRM-HSG-70',
		'name'        => 'Hand Sanitizer Gel (70% Ethanol) – Formulation',
		'price'       => 49,
		'cats'        => array( $cat_form, $cat_cos ),
		'kind'        => 'formulation',
		'featured'    => true,
		'short'       => 'Clear, non-sticky alcohol gel based on WHO-recommended ethanol levels. Full ingredient list, percentages, process and QC specifications.',
		'description' => '<p>A production-ready formula with step-by-step manufacturing instructions, recommended raw-material grades and suppliers, in-process checks and finished-product specifications.</p>',
		'meta'        => array(
			'_bionexa_format'   => 'PDF + Excel cost calculator',
			'_bionexa_pages'    => '8',
			'_bionexa_version'  => '1.0',
			'_bionexa_includes' => "Formula with % w/w and function of each ingredient\nManufacturing process & equipment\nQC specifications (pH, viscosity, alcohol content)\nCost calculator (Excel)",
		),
	),
	array(
		'sku'         => 'FRM-DSH-CON',
		'name'        => 'Liquid Dishwash Concentrate – Formulation',
		'price'       => 39,
		'cats'        => array( $cat_form, $cat_hh ),
		'kind'        => 'formulation',
		'short'       => 'High-foam, skin-friendly dishwashing liquid with LABSA/SLES base. Scalable from 10 L to 10 t.',
		'description' => '<p>Includes neutralisation steps, thickening with salt, preservative options and a troubleshooting guide for cloudiness and viscosity drift.</p>',
		'meta'        => array(
			'_bionexa_format'   => 'PDF + Excel cost calculator',
			'_bionexa_pages'    => '7',
			'_bionexa_version'  => '1.0',
			'_bionexa_includes' => "Formula & ingredient functions\nBatch process with neutralisation\nQC specifications\nTroubleshooting guide",
		),
	),
	array(
		'sku'         => 'FRM-SHP-AD',
		'name'        => 'Anti-Dandruff Shampoo – Formulation',
		'price'       => 59,
		'cats'        => array( $cat_form, $cat_cos ),
		'kind'        => 'formulation',
		'short'       => 'Sulphate-based anti-dandruff shampoo with piroctone olamine, conditioning polymer and pearlescent finish.',
		'description' => '<p>Includes stability-testing protocol and notes on claims and labelling for cosmetic markets.</p>',
		'meta'        => array(
			'_bionexa_format'   => 'PDF',
			'_bionexa_pages'    => '10',
			'_bionexa_version'  => '1.0',
			'_bionexa_includes' => "Formula & ingredient functions\nHot/cold process instructions\nStability protocol\nLabel & claims notes",
		),
	),
	array(
		'sku'         => 'FRM-IND-DEG',
		'name'        => 'Industrial Degreaser (Water-Based) – Formulation',
		'price'       => 69,
		'cats'        => array( $cat_form, $cat_ind ),
		'kind'        => 'formulation',
		'short'       => 'Alkaline, solvent-free heavy-duty degreaser for workshops, food plants and floors.',
		'description' => '<p>Includes dilution chart and material-compatibility notes.</p>',
		'meta'        => array(
			'_bionexa_format'   => 'PDF',
			'_bionexa_pages'    => '8',
			'_bionexa_version'  => '1.0',
			'_bionexa_includes' => "Formula & ingredient functions\nProcess & QC\nDilution chart\nCompatibility notes",
		),
	),
	array(
		'sku'         => 'SOP-17025-KIT',
		'name'        => 'ISO/IEC 17025:2017 Complete Documentation Kit',
		'price'       => 299,
		'cats'        => array( $cat_sop, $cat_iso ),
		'kind'        => 'sop',
		'featured'    => true,
		'short'       => 'Quality manual, 25+ SOPs, forms and records for testing and calibration laboratories. Fully editable.',
		'description' => '<p>Everything a laboratory needs to build an ISO/IEC 17025:2017 management system: quality manual, procedures for every clause, forms, registers and an internal-audit checklist. Edit the Word files with your laboratory name and logo.</p>',
		'meta'        => array(
			'_bionexa_standard' => 'ISO/IEC 17025:2017',
			'_bionexa_format'   => 'Word (.docx) + Excel',
			'_bionexa_pages'    => '200+',
			'_bionexa_version'  => '2026.1',
			'_bionexa_includes' => "Quality manual\n25+ SOPs (clauses 4–8)\n60+ forms and registers\nInternal audit checklist\nClause cross-reference matrix",
		),
	),
	array(
		'sku'         => 'SOP-17025-MU',
		'name'        => 'SOP – Estimation of Measurement Uncertainty',
		'price'       => 39,
		'cats'        => array( $cat_sop, $cat_iso ),
		'kind'        => 'sop',
		'short'       => 'Step-by-step GUM-based procedure with a worked example and Excel uncertainty budget.',
		'description' => '<p>Meets ISO/IEC 17025 clause 7.6. Includes a worked example and an editable uncertainty budget spreadsheet.</p>',
		'meta'        => array(
			'_bionexa_standard' => 'ISO/IEC 17025:2017 – clause 7.6',
			'_bionexa_format'   => 'Word (.docx) + Excel',
			'_bionexa_pages'    => '12',
			'_bionexa_version'  => '2026.1',
		),
	),
	array(
		'sku'         => 'SOP-17025-MV',
		'name'        => 'SOP – Method Validation & Verification',
		'price'       => 39,
		'cats'        => array( $cat_sop, $cat_iso ),
		'kind'        => 'sop',
		'short'       => 'Procedure and report templates for validating in-house methods and verifying standard methods.',
		'description' => '<p>Covers selectivity, linearity, LOD/LOQ, accuracy, precision and robustness, aligned with ISO/IEC 17025 clause 7.2.</p>',
		'meta'        => array(
			'_bionexa_standard' => 'ISO/IEC 17025:2017 – clause 7.2',
			'_bionexa_format'   => 'Word (.docx) + Excel',
			'_bionexa_pages'    => '15',
			'_bionexa_version'  => '2026.1',
		),
	),
	array(
		'sku'         => 'SOP-17025-IA',
		'name'        => 'SOP – Internal Audit',
		'price'       => 29,
		'cats'        => array( $cat_sop, $cat_iso ),
		'kind'        => 'sop',
		'short'       => 'Internal audit procedure, annual programme, checklist and non-conformity report forms.',
		'description' => '<p>Aligned with ISO/IEC 17025 clause 8.8 and ISO 19011 guidance.</p>',
		'meta'        => array(
			'_bionexa_standard' => 'ISO/IEC 17025:2017 – clause 8.8',
			'_bionexa_format'   => 'Word (.docx)',
			'_bionexa_pages'    => '10',
			'_bionexa_version'  => '2026.1',
		),
	),
);

foreach ( $products as $p ) {
	bionexa_setup_product( $p );
}

WP_CLI::success( 'Bionexa content ready.' );
