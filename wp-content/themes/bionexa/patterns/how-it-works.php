<?php
/**
 * Title: How it works (SDS ordering steps)
 * Slug: bionexa/how-it-works
 * Categories: bionexa
 * Inserter: true
 */

$bionexa_steps = array(
	array( '1', 'Order online', 'Pick your SDS package, choose countries and languages, and pay securely by card or PayPal.' ),
	array( '2', 'Share your data', 'Upload your composition, supplier SDS or CoA. Everything is kept confidential (NDA on request).' ),
	array( '3', 'We classify & write', 'Our chemists classify hazards, check exposure limits and transport rules, and draft the SDS.' ),
	array( '4', 'Download & sell', 'Receive Word + PDF files, ready for Amazon, eBay, customs and your B2B customers.' ),
);
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"},"margin":{"top":"0"}}},"backgroundColor":"surface","layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group alignfull has-surface-background-color has-background" style="margin-top:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">How SDS ordering works</h2>
<!-- /wp:heading -->

<!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|40","margin":{"top":"var:preset|spacing|40"}}},"layout":{"type":"grid","minimumColumnWidth":"220px"}} -->
<div class="wp-block-group alignwide" style="margin-top:var(--wp--preset--spacing--40)">
<?php foreach ( $bionexa_steps as $bionexa_step ) : ?>
<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontSize":"2.25rem","fontWeight":"800","lineHeight":"1"}},"textColor":"accent"} -->
<p class="has-accent-color has-text-color" style="font-size:2.25rem;font-weight:800;line-height:1"><?php echo esc_html( $bionexa_step[0] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html( $bionexa_step[1] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"muted"} -->
<p class="has-muted-color has-text-color"><?php echo esc_html( $bionexa_step[2] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->
