<?php
/**
 * SDS (Safety Data Sheet) preparation service ordering.
 *
 * Adds an order form to products whose kind is "sds": product name, product type,
 * target regions/regulations, sales channel (Amazon, eBay ...), languages, rush delivery,
 * composition upload and notes. Extra languages and rush delivery change the price.
 */

defined( 'ABSPATH' ) || exit;

class Bionexa_SDS_Service {

	/** Pending upload from validation, picked up when the cart item is created. */
	private static $pending_upload = null;

	public static function regions(): array {
		return apply_filters(
			'bionexa_sds_regions',
			array(
				'us'     => 'USA – OSHA HazCom 2012 / 2024 (GHS)',
				'eu'     => 'European Union – REACH Annex II / CLP',
				'uk'     => 'United Kingdom – UK REACH / GB CLP',
				'ca'     => 'Canada – WHMIS 2015 (HPR)',
				'au'     => 'Australia / New Zealand – WHS / GHS Rev 7',
				'gcc'    => 'GCC / Middle East – GHS',
				'cn'     => 'China – GB/T 16483 & GB/T 17519',
				'jp'     => 'Japan – JIS Z 7253',
				'kr'     => 'South Korea – K-REACH / MoEL',
				'in'     => 'India – MSIHC / BIS',
				'latam'  => 'Latin America – Mexico NOM-018, Brazil ABNT NBR 14725',
				'un'     => 'Transport only – UN / ADR / IATA / IMDG classification',
				'other'  => 'Other (tell us in the notes)',
			)
		);
	}

	public static function channels(): array {
		return apply_filters(
			'bionexa_sds_channels',
			array(
				'amazon'      => 'Amazon (Dangerous Goods / Hazmat review)',
				'ebay'        => 'eBay',
				'walmart'     => 'Walmart Marketplace',
				'etsy'        => 'Etsy',
				'shopify'     => 'Shopify / own web store',
				'b2b'         => 'Distributor / B2B / import',
				'other'       => 'Other',
			)
		);
	}

	public static function product_types(): array {
		return apply_filters(
			'bionexa_sds_product_types',
			array(
				'cosmetic'   => 'Cosmetic / personal care',
				'cleaning'   => 'Household / cleaning product',
				'fragrance'  => 'Essential oil / fragrance / candle',
				'industrial' => 'Industrial chemical / raw material',
				'coating'    => 'Paint / coating / adhesive',
				'battery'    => 'Battery / electronics with batteries',
				'agro'       => 'Agrochemical / fertiliser',
				'food'       => 'Food additive / supplement ingredient',
				'other'      => 'Other',
			)
		);
	}

	public static function languages(): array {
		return apply_filters(
			'bionexa_sds_languages',
			array( 'English', 'Spanish', 'French', 'German', 'Italian', 'Portuguese', 'Dutch', 'Polish', 'Arabic', 'Chinese', 'Japanese', 'Korean' )
		);
	}

	public static function init(): void {
		add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'render_form' ) );
		add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'validate' ), 10, 3 );
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'add_cart_item_data' ), 10, 2 );
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'display_cart_item_data' ), 10, 2 );
		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'apply_price_addons' ), 20 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'save_order_item_meta' ), 10, 3 );
		add_action( 'woocommerce_after_order_itemmeta', array( __CLASS__, 'render_admin_file_link' ), 10, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( __CLASS__, 'loop_button_text' ), 10, 2 );
		add_filter( 'woocommerce_product_add_to_cart_url', array( __CLASS__, 'loop_button_url' ), 10, 2 );
		add_filter( 'woocommerce_product_supports', array( __CLASS__, 'disable_loop_ajax' ), 10, 3 );
	}

	private static function is_sds( $product ): bool {
		return 'sds' === Bionexa_Product_Fields::kind( $product );
	}

	/* ------------------------------------------------------------------ */
	/* Product page form                                                   */
	/* ------------------------------------------------------------------ */

	public static function render_form(): void {
		global $product;
		if ( ! self::is_sds( $product ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification -- re-populating the form after a failed submit.
		$posted = static function ( string $key, $default = '' ) {
			return isset( $_POST[ $key ] ) ? wc_clean( wp_unslash( $_POST[ $key ] ) ) : $default;
		};
		// phpcs:enable

		$lang_price = (float) $product->get_meta( '_bionexa_extra_language_price' );
		$rush_price = (float) $product->get_meta( '_bionexa_rush_price' );
		$regions    = (array) $posted( 'bionexa_sds_regions', array() );
		$langs      = (array) $posted( 'bionexa_sds_languages', array( 'English' ) );
		?>
		<fieldset class="bionexa-sds-form">
			<legend><?php esc_html_e( 'Tell us about the product that needs an SDS', 'bionexa-core' ); ?></legend>

			<p class="form-row">
				<label for="bionexa_sds_product_name"><?php esc_html_e( 'Product / trade name', 'bionexa-core' ); ?> <abbr class="required" title="required">*</abbr></label>
				<input type="text" id="bionexa_sds_product_name" name="bionexa_sds_product_name" required value="<?php echo esc_attr( $posted( 'bionexa_sds_product_name' ) ); ?>">
			</p>

			<p class="form-row">
				<label for="bionexa_sds_product_type"><?php esc_html_e( 'Product type', 'bionexa-core' ); ?> <abbr class="required" title="required">*</abbr></label>
				<select id="bionexa_sds_product_type" name="bionexa_sds_product_type" required>
					<option value=""><?php esc_html_e( 'Choose…', 'bionexa-core' ); ?></option>
					<?php foreach ( self::product_types() as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $posted( 'bionexa_sds_product_type' ), $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<div class="form-row">
				<span class="bionexa-label"><?php esc_html_e( 'Countries / regulations', 'bionexa-core' ); ?> <abbr class="required" title="required">*</abbr></span>
				<div class="bionexa-checkgrid">
					<?php foreach ( self::regions() as $value => $label ) : ?>
						<label><input type="checkbox" name="bionexa_sds_regions[]" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $regions, true ) ); ?>> <?php echo esc_html( $label ); ?></label>
					<?php endforeach; ?>
				</div>
			</div>

			<p class="form-row">
				<label for="bionexa_sds_channel"><?php esc_html_e( 'Where will you sell it?', 'bionexa-core' ); ?> <abbr class="required" title="required">*</abbr></label>
				<select id="bionexa_sds_channel" name="bionexa_sds_channel" required>
					<option value=""><?php esc_html_e( 'Choose…', 'bionexa-core' ); ?></option>
					<?php foreach ( self::channels() as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $posted( 'bionexa_sds_channel' ), $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<div class="form-row">
				<span class="bionexa-label">
					<?php esc_html_e( 'SDS languages', 'bionexa-core' ); ?>
					<?php if ( $lang_price > 0 ) : ?>
						<small><?php printf( esc_html__( 'First language included, each extra +%s', 'bionexa-core' ), wp_kses_post( wc_price( $lang_price ) ) ); ?></small>
					<?php endif; ?>
				</span>
				<div class="bionexa-checkgrid bionexa-checkgrid--compact">
					<?php foreach ( self::languages() as $lang ) : ?>
						<label><input type="checkbox" name="bionexa_sds_languages[]" value="<?php echo esc_attr( $lang ); ?>" <?php checked( in_array( $lang, $langs, true ) ); ?>> <?php echo esc_html( $lang ); ?></label>
					<?php endforeach; ?>
				</div>
			</div>

			<?php if ( $rush_price > 0 ) : ?>
				<p class="form-row">
					<label><input type="checkbox" name="bionexa_sds_rush" value="1" <?php checked( '1', $posted( 'bionexa_sds_rush' ) ); ?>>
					<?php printf( esc_html__( 'Rush delivery (+%s)', 'bionexa-core' ), wp_kses_post( wc_price( $rush_price ) ) ); ?></label>
				</p>
			<?php endif; ?>

			<p class="form-row">
				<label for="bionexa_sds_file"><?php esc_html_e( 'Composition / formula / supplier SDS / CoA (optional)', 'bionexa-core' ); ?></label>
				<input type="file" id="bionexa_sds_file" name="bionexa_sds_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png">
				<small><?php esc_html_e( 'Kept confidential. You can also email it after ordering.', 'bionexa-core' ); ?></small>
			</p>

			<p class="form-row">
				<label for="bionexa_sds_notes"><?php esc_html_e( 'Notes (Amazon ASIN, UN number, pack sizes, deadlines…)', 'bionexa-core' ); ?></label>
				<textarea id="bionexa_sds_notes" name="bionexa_sds_notes" rows="3"><?php echo esc_textarea( $posted( 'bionexa_sds_notes' ) ); ?></textarea>
			</p>
		</fieldset>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Cart                                                                */
	/* ------------------------------------------------------------------ */

	public static function validate( $passed, $product_id, $quantity ) {
		if ( ! self::is_sds( $product_id ) ) {
			return $passed;
		}

		// phpcs:disable WordPress.Security.NonceVerification -- WooCommerce add-to-cart has no nonce.
		if ( empty( $_POST['bionexa_sds_product_name'] ) ) {
			wc_add_notice( __( 'Please enter the product / trade name for your SDS.', 'bionexa-core' ), 'error' );
			$passed = false;
		}
		if ( empty( $_POST['bionexa_sds_product_type'] ) ) {
			wc_add_notice( __( 'Please choose the product type.', 'bionexa-core' ), 'error' );
			$passed = false;
		}
		if ( empty( $_POST['bionexa_sds_regions'] ) ) {
			wc_add_notice( __( 'Please choose at least one country / regulation.', 'bionexa-core' ), 'error' );
			$passed = false;
		}
		if ( empty( $_POST['bionexa_sds_channel'] ) ) {
			wc_add_notice( __( 'Please tell us where you will sell the product.', 'bionexa-core' ), 'error' );
			$passed = false;
		}
		// phpcs:enable

		if ( $passed ) {
			$upload = bionexa_store_private_upload( 'bionexa_sds_file' );
			if ( is_wp_error( $upload ) ) {
				wc_add_notice( $upload->get_error_message(), 'error' );
				return false;
			}
			self::$pending_upload = $upload;
		}

		return $passed;
	}

	public static function add_cart_item_data( array $data, $product_id ): array {
		if ( ! self::is_sds( $product_id ) ) {
			return $data;
		}

		// phpcs:disable WordPress.Security.NonceVerification
		$regions  = array_intersect( array_map( 'sanitize_key', (array) wp_unslash( $_POST['bionexa_sds_regions'] ?? array() ) ), array_keys( self::regions() ) );
		$langs    = array_intersect( array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['bionexa_sds_languages'] ?? array() ) ), self::languages() );
		$type     = sanitize_key( wp_unslash( $_POST['bionexa_sds_product_type'] ?? '' ) );
		$channel  = sanitize_key( wp_unslash( $_POST['bionexa_sds_channel'] ?? '' ) );

		$data['bionexa_sds'] = array(
			'product_name' => sanitize_text_field( wp_unslash( $_POST['bionexa_sds_product_name'] ?? '' ) ),
			'product_type' => array_key_exists( $type, self::product_types() ) ? $type : 'other',
			'regions'      => array_values( $regions ),
			'channel'      => array_key_exists( $channel, self::channels() ) ? $channel : 'other',
			'languages'    => $langs ? array_values( $langs ) : array( 'English' ),
			'rush'         => ! empty( $_POST['bionexa_sds_rush'] ),
			'notes'        => sanitize_textarea_field( wp_unslash( $_POST['bionexa_sds_notes'] ?? '' ) ),
			'upload'       => self::$pending_upload,
		);
		// phpcs:enable

		self::$pending_upload = null;
		$data['bionexa_unique'] = wp_generate_uuid4(); // Each SDS request is its own cart line.
		return $data;
	}

	/** Human-readable label => value pairs for an SDS request. */
	private static function describe( array $sds ): array {
		$types    = self::product_types();
		$channels = self::channels();
		$regions  = self::regions();

		$rows = array(
			__( 'Product name', 'bionexa-core' ) => $sds['product_name'],
			__( 'Product type', 'bionexa-core' ) => $types[ $sds['product_type'] ] ?? $sds['product_type'],
			__( 'Regions', 'bionexa-core' )      => implode( '; ', array_map( static fn( $r ) => $regions[ $r ] ?? $r, $sds['regions'] ) ),
			__( 'Sales channel', 'bionexa-core' ) => $channels[ $sds['channel'] ] ?? $sds['channel'],
			__( 'Languages', 'bionexa-core' )    => implode( ', ', $sds['languages'] ),
		);
		if ( $sds['rush'] ) {
			$rows[ __( 'Delivery', 'bionexa-core' ) ] = __( 'Rush', 'bionexa-core' );
		}
		if ( ! empty( $sds['upload']['name'] ) ) {
			$rows[ __( 'Attached file', 'bionexa-core' ) ] = $sds['upload']['name'];
		}
		if ( '' !== $sds['notes'] ) {
			$rows[ __( 'Notes', 'bionexa-core' ) ] = $sds['notes'];
		}
		return $rows;
	}

	public static function display_cart_item_data( array $item_data, array $cart_item ): array {
		if ( empty( $cart_item['bionexa_sds'] ) ) {
			return $item_data;
		}
		foreach ( self::describe( $cart_item['bionexa_sds'] ) as $key => $value ) {
			$item_data[] = array(
				'key'   => $key,
				'value' => $value,
			);
		}
		return $item_data;
	}

	public static function apply_price_addons( WC_Cart $cart ): void {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		foreach ( $cart->get_cart() as $item ) {
			if ( empty( $item['bionexa_sds'] ) ) {
				continue;
			}
			/** @var WC_Product $product */
			$product = $item['data'];
			$parent  = wc_get_product( $item['product_id'] );
			$base    = (float) $parent->get_price( 'edit' );
			$extra   = max( 0, count( $item['bionexa_sds']['languages'] ) - 1 ) * (float) $parent->get_meta( '_bionexa_extra_language_price' );
			$rush    = $item['bionexa_sds']['rush'] ? (float) $parent->get_meta( '_bionexa_rush_price' ) : 0.0;
			$product->set_price( $base + $extra + $rush );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Orders                                                              */
	/* ------------------------------------------------------------------ */

	public static function save_order_item_meta( WC_Order_Item_Product $item, $cart_item_key, array $values ): void {
		if ( empty( $values['bionexa_sds'] ) ) {
			return;
		}
		foreach ( self::describe( $values['bionexa_sds'] ) as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
		}
		if ( ! empty( $values['bionexa_sds']['upload']['file'] ) ) {
			$item->add_meta_data( '_bionexa_upload_file', $values['bionexa_sds']['upload']['file'], true );
		}
		$item->add_meta_data( '_bionexa_sds_request', $values['bionexa_sds'], true );
	}

	public static function render_admin_file_link( $item_id, $item ): void {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			return;
		}
		$stored = (string) $item->get_meta( '_bionexa_upload_file' );
		if ( '' !== $stored ) {
			printf(
				'<p><a class="button button-small" href="%s">%s</a></p>',
				esc_url( bionexa_private_file_url( $stored ) ),
				esc_html__( 'Download customer file', 'bionexa-core' )
			);
		}
	}

	/* ------------------------------------------------------------------ */
	/* Shop listings: SDS products must be configured on their own page.   */
	/* ------------------------------------------------------------------ */

	public static function loop_button_text( $text, $product ) {
		return self::is_sds( $product ) ? __( 'Start SDS order', 'bionexa-core' ) : $text;
	}

	public static function loop_button_url( $url, $product ) {
		return self::is_sds( $product ) ? $product->get_permalink() : $url;
	}

	public static function disable_loop_ajax( $supports, $feature, $product ) {
		if ( 'ajax_add_to_cart' === $feature && self::is_sds( $product ) ) {
			return false;
		}
		return $supports;
	}
}
