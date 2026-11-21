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
