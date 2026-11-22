<?php
/**
 * "Order Now" button: adds the product to the cart and jumps straight to checkout.
 *
 * On product pages the button submits the normal add-to-cart form (so quantity,
 * variations and SDS form fields are all kept) with an extra flag; in product
 * lists it links to ?add-to-cart=ID&bionexa_buy_now=1.
 */

defined( 'ABSPATH' ) || exit;

class Bionexa_Buy_Now {

	public static function init(): void {
		add_action( 'woocommerce_after_add_to_cart_button', array( __CLASS__, 'render_single_button' ) );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( __CLASS__, 'append_loop_button' ), 10, 2 );
		add_filter( 'woocommerce_add_to_cart_redirect', array( __CLASS__, 'redirect_to_checkout' ), 99 );
	}

	private static function enabled_for( $product ): bool {
		return $product instanceof WC_Product
			&& $product->is_purchasable()
			&& $product->is_in_stock()
			&& 'yes' !== $product->get_meta( '_bionexa_hide_order_now' );
	}

	public static function render_single_button(): void {
		global $product;
		if ( ! self::enabled_for( $product ) ) {
			return;
		}
		// Same name/value as the regular add-to-cart button; formaction adds the "buy now" flag.
		printf(
			'<button type="submit" name="add-to-cart" value="%1$d" formaction="%2$s" class="single_add_to_cart_button button alt bionexa-order-now">%3$s</button>',
			absint( $product->get_id() ),
			esc_url( add_query_arg( 'bionexa_buy_now', 1, $product->get_permalink() ) ),
			esc_html( Bionexa_Settings::get( 'order_now_label' ) )
		);
	}

	public static function append_loop_button( string $html, $product ): string {
		// Products that need options (variable, SDS form) must be configured on their own page first.
		if ( ! self::enabled_for( $product ) || ! $product->is_type( 'simple' ) || 'sds' === Bionexa_Product_Fields::kind( $product ) ) {
			return $html;
		}
		$url = add_query_arg(
			array(
				'add-to-cart'     => $product->get_id(),
				'bionexa_buy_now' => 1,
			),
			wc_get_cart_url()
		);
		return $html . sprintf(
			' <a href="%s" class="button bionexa-order-now" rel="nofollow">%s</a>',
			esc_url( $url ),
			esc_html( Bionexa_Settings::get( 'order_now_label' ) )
		);
	}

	public static function redirect_to_checkout( $url ) {
		if ( ! empty( $_REQUEST['bionexa_buy_now'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return wc_get_checkout_url();
		}
		return $url;
	}
}
