<?php
/**
 * Title: FAQ
 * Slug: bionexa/faq
 * Categories: bionexa, text
 * Inserter: true
 */

$bionexa_faq = array(
	array( 'Will your SDS be accepted by Amazon?', 'Yes. We prepare SDS in the 16-section GHS format Amazon requires for dangerous-goods (hazmat) review, including the sections Amazon checks most often: classification, composition, transport (Section 14) and the revision date. If Amazon asks for changes, revisions are free for 30 days.' ),
	array( 'Which countries do you cover?', 'USA (OSHA HazCom), EU (REACH Annex II / CLP), UK, Canada (WHMIS), Australia/NZ, GCC, China, Japan, Korea, India and Latin America. Choose one or several regions when you order.' ),
	array( 'What information do I need to provide?', 'The full composition with CAS numbers and percentages is ideal. Supplier SDS, a CoA or a product label also helps. If you do not know the composition, ask your manufacturer — we can sign an NDA with them.' ),
	array( 'How fast will I get my SDS?', 'Standard delivery is 3–5 business days after we receive complete information. Rush delivery (48 hours) is available at checkout.' ),
	array( 'How do I receive formulations and SOPs?', 'They are instant downloads. After payment you get a download link by email and in My Account → Downloads. SOPs are supplied as editable Word files.' ),
	array( 'Which payment methods do you accept?', 'Credit/debit cards and PayPal. For larger projects we can also invoice by bank transfer.' ),
);
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"},"margin":{"top":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Frequently asked questions</h2>
<!-- /wp:heading -->
<?php foreach ( $bionexa_faq as $bionexa_item ) : ?>

<!-- wp:details -->
<details class="wp-block-details"><summary><?php echo esc_html( $bionexa_item[0] ); ?></summary><!-- wp:paragraph -->
<p><?php echo esc_html( $bionexa_item[1] ); ?></p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->
<?php endforeach; ?></div>
<!-- /wp:group -->
