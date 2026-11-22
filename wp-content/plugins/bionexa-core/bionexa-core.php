<?php
/**
 * Plugin Name:       Bionexa Core
 * Description:       Business features for Bionexa: SDS preparation ordering, "Order Now" buttons, formulation & SOP document specs, licence acceptance at checkout, and custom quote requests.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Requires Plugins:  woocommerce
 * Author:            Bionexa
 * Text Domain:       bionexa-core
 * License:           GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'BIONEXA_CORE_VERSION', '1.0.0' );
define( 'BIONEXA_CORE_FILE', __FILE__ );
define( 'BIONEXA_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'BIONEXA_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once BIONEXA_CORE_DIR . 'includes/helpers.php';
require_once BIONEXA_CORE_DIR . 'includes/class-settings.php';
require_once BIONEXA_CORE_DIR . 'includes/class-quote-requests.php';

// Settings and quote requests work even without WooCommerce.
Bionexa_Settings::init();
Bionexa_Quote_Requests::init();

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Bionexa Core needs WooCommerce to be installed and active.', 'bionexa-core' ) . '</p></div>';
				}
			);
			return;
		}

		require_once BIONEXA_CORE_DIR . 'includes/class-product-fields.php';
		require_once BIONEXA_CORE_DIR . 'includes/class-buy-now.php';
		require_once BIONEXA_CORE_DIR . 'includes/class-sds-service.php';
		require_once BIONEXA_CORE_DIR . 'includes/class-licence-checkout.php';

		Bionexa_Product_Fields::init();
		Bionexa_Buy_Now::init();
		Bionexa_SDS_Service::init();
		Bionexa_Licence_Checkout::init();
	}
);

// Styles load on the site and inside the block editor, so forms look the same while editing.
add_action(
	'enqueue_block_assets',
	static function () {
		wp_enqueue_style( 'bionexa-core', BIONEXA_CORE_URL . 'assets/bionexa-core.css', array(), BIONEXA_CORE_VERSION );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_script( 'bionexa-core', BIONEXA_CORE_URL . 'assets/bionexa-core.js', array(), BIONEXA_CORE_VERSION, true );
	}
);

register_activation_hook(
	__FILE__,
	static function () {
		Bionexa_Quote_Requests::register_post_type();
		bionexa_protect_upload_dir();
		flush_rewrite_rules();
	}
);
