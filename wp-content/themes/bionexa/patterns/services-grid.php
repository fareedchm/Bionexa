<?php
/**
 * Title: Services grid
 * Slug: bionexa/services-grid
 * Categories: bionexa, featured
 * Inserter: true
 */

$bionexa_cards = array(
	array( '🧾', 'SDS preparation', 'New or updated Safety Data Sheets in 16-section GHS format for the USA, EU, UK, Canada, Australia, GCC, Asia and Latin America.', '/sds-services/', 'Order an SDS' ),
	array( '📦', 'Amazon & eBay hazmat SDS', 'Listing blocked for dangerous-goods review? We prepare marketplace-ready SDS and exemption sheets so you can get selling again.', '/sds-services/', 'Fix my listing' ),
	array( '⚗️', 'Ready-made formulations', 'Tested starting formulas for cosmetics, cleaning, disinfectants, industrial and agro products — with process and QC notes.', '/product-category/formulations/', 'Browse formulations' ),
	array( '📑', 'SOPs & ISO/IEC 17025', 'Editable quality manuals, SOPs, forms and records for testing &amp; calibration laboratories working towards ISO/IEC 17025:2017.', '/product-category/sops-quality-documents/', 'Browse documents' ),
	array( '🧪', 'Custom formulation development', 'We develop, reformulate or cost-reduce products to your brief, with stability guidance and a full technical dossier.', '/request-a-quote/?service=formulation', 'Request a quote' ),
	array( '🏷️', 'Labels & compliance', 'GHS / CLP labels, UFI and poison-centre notifications, product classification and regulatory reviews.', '/request-a-quote/?service=labels', 'Request a quote' ),
);
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|50"},"margin":{"top":"0"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:paragraph {"align":"center","className":"bionexa-eyebrow"} -->
<p class="has-text-align-center bionexa-eyebrow">What we do</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Services &amp; products</h2>
<!-- /wp:heading -->

<!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|40","margin":{"top":"var:preset|spacing|50"}}},"layout":{"type":"grid","minimumColumnWidth":"300px"}} -->
<div class="wp-block-group alignwide" style="margin-top:var(--wp--preset--spacing--50)">
<?php foreach ( $bionexa_cards as $bionexa_card ) : ?>
<!-- wp:group {"className":"is-style-card","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-card"><!-- wp:paragraph {"className":"bionexa-icon"} -->
<p class="bionexa-icon"><?php echo $bionexa_card[0]; // phpcs:ignore ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo $bionexa_card[1]; // phpcs:ignore ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"muted"} -->
<p class="has-muted-color has-text-color"><?php echo $bionexa_card[2]; // phpcs:ignore ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600"}}} -->
<p style="font-weight:600"><a href="<?php echo esc_url( $bionexa_card[3] ); ?>"><?php echo $bionexa_card[4]; // phpcs:ignore ?> →</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->
