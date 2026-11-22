<?php
/**
 * "Bionexa" tab in the WooCommerce product editor + "Specifications" tab on the product page.
 *
 * Every product can be marked as one of:
 *   - sds          SDS preparation service (shows the SDS order form)
 *   - formulation  Downloadable formulation (needs licence acceptance)
 *   - sop          SOP / quality document, e.g. ISO/IEC 17025 (needs licence acceptance)
 */

defined( 'ABSPATH' ) || exit;

class Bionexa_Product_Fields {

	public const KINDS = array( 'sds', 'formulation', 'sop' );

	/** Text fields: meta key => label. */
	private const SPEC_FIELDS = array(
		'_bionexa_standard'   => 'Standard / regulation',
		'_bionexa_version'    => 'Document version',
		'_bionexa_format'     => 'File format',
		'_bionexa_pages'      => 'Pages',
		'_bionexa_turnaround' => 'Turnaround time',
	);

	public static function init(): void {
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_admin_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_admin_panel' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save' ) );
		add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'add_frontend_tab' ) );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_kind_badge' ), 4 );
	}

	public static function kind( $product ): string {
		$product = wc_get_product( $product );
		if ( ! $product ) {
			return '';
		}
		$kind = (string) $product->get_meta( '_bionexa_kind' );
		return in_array( $kind, self::KINDS, true ) ? $kind : '';
	}

	public static function add_admin_tab( array $tabs ): array {
		$tabs['bionexa'] = array(
			'label'    => __( 'Bionexa', 'bionexa-core' ),
			'target'   => 'bionexa_product_data',
			'priority' => 15,
		);
		return $tabs;
	}

	public static function render_admin_panel(): void {
		echo '<div id="bionexa_product_data" class="panel woocommerce_options_panel"><div class="options_group">';

		woocommerce_wp_select(
			array(
				'id'          => '_bionexa_kind',
				'label'       => __( 'Product kind', 'bionexa-core' ),
				'options'     => array(
					''            => __( 'Standard product', 'bionexa-core' ),
					'sds'         => __( 'SDS preparation service', 'bionexa-core' ),
					'formulation' => __( 'Formulation (downloadable)', 'bionexa-core' ),
					'sop'         => __( 'SOP / quality document', 'bionexa-core' ),
				),
				'desc_tip'    => true,
				'description' => __( 'SDS services show an order form. Formulations and SOPs require licence acceptance at checkout.', 'bionexa-core' ),
			)
		);

		woocommerce_wp_checkbox(
			array(
				'id'          => '_bionexa_hide_order_now',
				'label'       => __( 'Hide "Order Now"', 'bionexa-core' ),
				'description' => __( 'Show only "Add to cart" for this product.', 'bionexa-core' ),
			)
		);

		echo '</div><div class="options_group">';
		echo '<p class="form-field"><strong>' . esc_html__( 'Specifications (shown in a tab on the product page)', 'bionexa-core' ) . '</strong></p>';

		foreach ( self::SPEC_FIELDS as $key => $label ) {
			woocommerce_wp_text_input(
				array(
					'id'    => $key,
					'label' => __( $label, 'bionexa-core' ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
				)
			);
		}

		woocommerce_wp_textarea_input(
			array(
				'id'          => '_bionexa_includes',
				'label'       => __( "What's included", 'bionexa-core' ),
				'description' => __( 'One item per line.', 'bionexa-core' ),
				'rows'        => 5,
			)
		);

		echo '</div><div class="options_group bionexa-sds-pricing">';
		echo '<p class="form-field"><strong>' . esc_html__( 'SDS service pricing add-ons', 'bionexa-core' ) . '</strong></p>';

		woocommerce_wp_text_input(
			array(
				'id'        => '_bionexa_extra_language_price',
				'label'     => sprintf( __( 'Each extra language (%s)', 'bionexa-core' ), get_woocommerce_currency_symbol() ),
				'data_type' => 'price',
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'        => '_bionexa_rush_price',
				'label'     => sprintf( __( 'Rush delivery fee (%s)', 'bionexa-core' ), get_woocommerce_currency_symbol() ),
				'data_type' => 'price',
			)
		);

		echo '</div></div>';
		// Only show the SDS pricing boxes when "SDS preparation service" is chosen.
		?>
		<script>
		( function () {
			var kind = document.getElementById( '_bionexa_kind' );
			var box = document.querySelector( '.bionexa-sds-pricing' );
			if ( ! kind || ! box ) { return; }
			var sync = function () { box.style.display = 'sds' === kind.value ? '' : 'none'; };
			kind.addEventListener( 'change', sync );
			sync();
		} )();
		</script>
		<?php
	}

	public static function save( WC_Product $product ): void {
		// phpcs:disable WordPress.Security.NonceVerification -- WooCommerce verifies the product save nonce.
		$kind = isset( $_POST['_bionexa_kind'] ) ? sanitize_key( wp_unslash( $_POST['_bionexa_kind'] ) ) : '';
		$product->update_meta_data( '_bionexa_kind', in_array( $kind, self::KINDS, true ) ? $kind : '' );
		$product->update_meta_data( '_bionexa_hide_order_now', isset( $_POST['_bionexa_hide_order_now'] ) ? 'yes' : 'no' );

		foreach ( array_keys( self::SPEC_FIELDS ) as $key ) {
			$product->update_meta_data( $key, isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '' );
		}
		$product->update_meta_data( '_bionexa_includes', isset( $_POST['_bionexa_includes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_bionexa_includes'] ) ) : '' );

		foreach ( array( '_bionexa_extra_language_price', '_bionexa_rush_price' ) as $key ) {
			$product->update_meta_data( $key, isset( $_POST[ $key ] ) ? wc_format_decimal( wp_unslash( $_POST[ $key ] ) ) : '' );
		}
		// phpcs:enable
	}

	public static function add_frontend_tab( array $tabs ): array {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return $tabs;
		}

		$has_specs = '' !== (string) $product->get_meta( '_bionexa_includes' );
		foreach ( array_keys( self::SPEC_FIELDS ) as $key ) {
			$has_specs = $has_specs || '' !== (string) $product->get_meta( $key );
		}

		if ( $has_specs ) {
			$tabs['bionexa_specs'] = array(
				'title'    => __( 'Specifications', 'bionexa-core' ),
				'priority' => 15,
				'callback' => array( __CLASS__, 'render_frontend_tab' ),
			);
		}
		return $tabs;
	}

	public static function render_frontend_tab(): void {
		global $product;

		echo '<table class="bionexa-specs shop_attributes"><tbody>';
		foreach ( self::SPEC_FIELDS as $key => $label ) {
			$value = (string) $product->get_meta( $key );
			if ( '' !== $value ) {
				printf( '<tr><th>%s</th><td>%s</td></tr>', esc_html__( $label, 'bionexa-core' ), esc_html( $value ) ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
			}
		}
		echo '</tbody></table>';

		$includes = array_filter( array_map( 'trim', explode( "\n", (string) $product->get_meta( '_bionexa_includes' ) ) ) );
		if ( $includes ) {
			echo '<h3>' . esc_html__( "What's included", 'bionexa-core' ) . '</h3><ul class="bionexa-includes">';
			foreach ( $includes as $item ) {
				echo '<li>' . esc_html( $item ) . '</li>';
			}
			echo '</ul>';
		}
	}

	public static function render_kind_badge(): void {
		global $product;
		$labels = array(
			'sds'         => __( 'Service · Worldwide', 'bionexa-core' ),
			'formulation' => __( 'Instant download', 'bionexa-core' ),
			'sop'         => __( 'Instant download · Editable', 'bionexa-core' ),
		);
		$kind = self::kind( $product );
		if ( isset( $labels[ $kind ] ) ) {
			printf( '<span class="bionexa-badge bionexa-badge--%1$s">%2$s</span>', esc_attr( $kind ), esc_html( $labels[ $kind ] ) );
		}
	}
}
