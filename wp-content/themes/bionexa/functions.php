<?php
/**
 * Bionexa theme setup.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function () {
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
		add_theme_support( 'editor-styles' );
		add_editor_style( 'assets/css/theme.css' );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		$theme = wp_get_theme();
		wp_enqueue_style( 'bionexa-theme', get_theme_file_uri( 'assets/css/theme.css' ), array(), $theme->get( 'Version' ) );
	}
);

add_action(
	'init',
	static function () {
		register_block_pattern_category( 'bionexa', array( 'label' => __( 'Bionexa', 'bionexa' ) ) );
		register_block_pattern_category( 'bionexa-pages', array( 'label' => __( 'Bionexa – full pages', 'bionexa' ) ) );

		register_block_style(
			'core/group',
			array(
				'name'  => 'card',
				'label' => __( 'Card', 'bionexa' ),
			)
		);
	}
);

/**
 * Block markup for a "Products by Category" block (editable with dropdowns in the editor).
 * Falls back to a shortcode if the category does not exist yet.
 */
function bionexa_products_block( string $category_slug, int $columns = 4, int $rows = 1, string $orderby = 'date' ): string {
	$term = function_exists( 'get_term_by' ) ? get_term_by( 'slug', $category_slug, 'product_cat' ) : false;
	if ( ! $term ) {
		return "<!-- wp:shortcode -->\n[products category=\"{$category_slug}\" limit=\"" . ( $columns * $rows ) . "\" columns=\"{$columns}\"]\n<!-- /wp:shortcode -->";
	}
	$attrs = array(
		'columns'    => $columns,
		'rows'       => $rows,
		'categories' => array( (int) $term->term_id ),
		'orderby'    => $orderby,
	);
	return '<!-- wp:woocommerce/product-category ' . wp_json_encode( $attrs ) . ' /-->';
}
