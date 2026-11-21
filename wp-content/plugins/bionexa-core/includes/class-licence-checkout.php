<?php
/**
 * Require customers to accept the Formulation & Document Licence when the cart
 * contains formulations or SOPs, and record the acceptance on the order.
 *
 * The licence text lives on an ordinary, editable WordPress page whose ID is
 * stored in the option "bionexa_licence_page_id" (created by setup/install.sh).
 */

defined( 'ABSPATH' ) || exit;

class Bionexa_Licence_Checkout {

	public static function init(): void {
		add_filter( 'woocommerce_get_terms_and_conditions_checkbox_text', array( __CLASS__, 'terms_text' ) );
		add_action( 'woocommerce_review_order_before_submit', array( __CLASS__, 'render_checkbox' ) );
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'validate' ) );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'render_admin' ) );
	}

	private static function cart_needs_licence(): bool {
		if ( ! WC()->cart ) {
			return false;
		}
		foreach ( WC()->cart->get_cart() as $item ) {
			if ( in_array( Bionexa_Product_Fields::kind( $item['product_id'] ), array( 'formulation', 'sop' ), true ) ) {
				return true;
			}
		}
		return false;
	}

	/** When WooCommerce shows its own terms checkbox, the licence is accepted through that one checkbox. */
	private static function uses_wc_terms(): bool {
		return wc_terms_and_conditions_checkbox_enabled();
	}

	private static function licence_link(): string {
		$page_id = (int) get_option( 'bionexa_licence_page_id' );
		return $page_id ? sprintf( '<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url( get_permalink( $page_id ) ), esc_html__( 'Formulation & Document Licence', 'bionexa-core' ) ) : esc_html__( 'Formulation & Document Licence', 'bionexa-core' );
	}

	public static function terms_text( $text ) {
		if ( self::cart_needs_licence() ) {
			/* translators: %s: licence page link */
			$text = rtrim( (string) $text, '. ' ) . '. ' . sprintf( __( 'I also accept the %s: files are for my own business use and may not be resold or shared.', 'bionexa-core' ), self::licence_link() );
		}
		return $text;
	}

	public static function render_checkbox(): void {
		if ( ! self::cart_needs_licence() || self::uses_wc_terms() ) {
			return;
		}
		$link = self::licence_link();
		?>
		<p class="form-row validate-required bionexa-licence">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
				<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="bionexa_licence" value="1">
				<span>
					<?php
					/* translators: %s: licence page link */
					printf( wp_kses_post( __( 'I have read and accept the %s. Files are for my own business use and may not be resold or shared.', 'bionexa-core' ) ), wp_kses_post( $link ) );
					?>
				</span>&nbsp;<abbr class="required" title="required">*</abbr>
			</label>
		</p>
		<?php
	}

	private static function accepted(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification -- checkout nonce checked by WooCommerce.
		return self::uses_wc_terms() ? ! empty( $_POST['terms'] ) : ! empty( $_POST['bionexa_licence'] );
	}

	public static function validate(): void {
		// WooCommerce itself rejects the order when its terms checkbox is not ticked.
		if ( self::cart_needs_licence() && ! self::uses_wc_terms() && ! self::accepted() ) { // phpcs:ignore WordPress.Security.NonceVerification -- checkout nonce checked by WooCommerce.
			wc_add_notice( __( 'Please accept the Formulation & Document Licence to continue.', 'bionexa-core' ), 'error' );
		}
	}

	public static function save( WC_Order $order, $data ): void {
		if ( self::cart_needs_licence() && self::accepted() ) {
			$order->update_meta_data( '_bionexa_licence_accepted', gmdate( 'c' ) );
			$order->update_meta_data( '_bionexa_licence_ip', WC_Geolocation::get_ip_address() );
		}
	}

	public static function render_admin( WC_Order $order ): void {
		$when = $order->get_meta( '_bionexa_licence_accepted' );
		if ( $when ) {
			printf(
				'<p><strong>%s</strong><br>%s (IP %s)</p>',
				esc_html__( 'Licence accepted', 'bionexa-core' ),
				esc_html( $when ),
				esc_html( (string) $order->get_meta( '_bionexa_licence_ip' ) )
			);
		}
	}
}
