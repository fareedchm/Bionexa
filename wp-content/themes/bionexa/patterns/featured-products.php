<?php
/**
 * Title: Featured formulations & SOPs
 * Slug: bionexa/featured-products
 * Categories: bionexa, woo-commerce
 * Description: Latest products from the Formulations and SOPs categories. Click a product grid to change its category, columns or rows in the sidebar.
 * Inserter: true
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|50"},"margin":{"top":"0"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading -->
<h2 class="wp-block-heading">Ready-to-use formulations</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><a href="/product-category/formulations/">View all formulations →</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><?php echo bionexa_products_block( 'formulations' ); // phpcs:ignore ?></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group alignwide" style="margin-top:var(--wp--preset--spacing--50)"><!-- wp:heading -->
<h2 class="wp-block-heading">SOPs &amp; ISO/IEC 17025 documents</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><a href="/product-category/sops-quality-documents/">View all documents →</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><?php echo bionexa_products_block( 'sops-quality-documents' ); // phpcs:ignore ?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
