<?php
/**
 * "Bionexa Settings" screen in wp-admin.
 *
 * Lets the site owner change every list and label used by the SDS order form,
 * the quote form and the Order Now button, without touching code.
 * Lists are plain text boxes: one item per line.
 */

defined( 'ABSPATH' ) || exit;

class Bionexa_Settings {

	public const OPTION = 'bionexa_settings';

	/** Field definitions: key => [label, type, default, help]. */
	public static function fields(): array {
		return array(
			'sds_heading'      => array(
				'SDS form – heading',
				'text',
				'Tell us about the product that needs an SDS',
				'Shown at the top of the order form on SDS products.',
			),
			'sds_product_types' => array(
				'SDS form – product types',
				'lines',
				"Cosmetic / personal care\nHousehold / cleaning product\nEssential oil / fragrance / candle\nIndustrial chemical / raw material\nPaint / coating / adhesive\nBattery / electronics with batteries\nAgrochemical / fertiliser\nFood additive / supplement ingredient\nOther",
				'One per line. Customers pick one.',
			),
			'sds_word_form_url' => array(
				'SDS form – Word form download link',
				'url',
				'',
				'Link to your blank SDS Information Form (Word). To change it: Media → Add New Media File, upload the new file, click it, "Copy URL to clipboard", and paste it here. Leave empty to hide the "Prefer Word?" box.',
			),
			'sds_physical_forms' => array(
				'SDS form – physical form',
				'lines',
				"Solid\nPowder / granules\nLiquid\nPaste / gel / cream\nGas\nAerosol",
				'One per line. Customers pick one.',
			),
			'sds_regions'      => array(
				'SDS form – countries / regulations',
				'lines',
				"USA – OSHA HazCom 2012 / 2024 (GHS)\nEuropean Union – REACH Annex II / CLP\nUnited Kingdom – UK REACH / GB CLP\nCanada – WHMIS 2015 (HPR)\nAustralia / New Zealand – WHS / GHS Rev 7\nGCC / Middle East – GHS\nChina – GB/T 16483 & GB/T 17519\nJapan – JIS Z 7253\nSouth Korea – K-REACH / MoEL\nIndia – MSIHC / BIS\nLatin America – Mexico NOM-018, Brazil ABNT NBR 14725\nTransport only – UN / ADR / IATA / IMDG classification\nOther (tell us in the notes)",
				'One per line. Customers tick one or more.',
			),
			'sds_channels'     => array(
				'SDS form – where will you sell it?',
				'lines',
				"Amazon (Dangerous Goods / Hazmat review)\neBay\nWalmart Marketplace\nEtsy\nShopify / own web store\nDistributor / B2B / import\nOther",
				'One per line. Customers pick one.',
			),
			'sds_languages'    => array(
				'SDS form – languages',
				'lines',
				"English\nSpanish\nFrench\nGerman\nItalian\nPortuguese\nDutch\nPolish\nArabic\nChinese\nJapanese\nKorean",
				'One per line. The first one is ticked by default and included in the price; each extra language costs the amount set on the product.',
			),
			'sds_extra_questions' => array(
				'SDS form – your extra questions',
				'lines_optional',
				'',
				'Want to ask customers something more? Type one question per line (e.g. "Pack sizes", "UN number if known"). Each becomes an optional box under "Additional information". Leave empty for none.',
			),
			'quote_services'   => array(
				'Quote form – services',
				'lines',
				"SDS preparation – multiple products / bulk\nSDS review, update or translation\nCustom formulation development\nReformulation / cost reduction\nISO/IEC 17025 documentation & accreditation support\nCustom SOPs / quality manual\nGHS / CLP labels & regulatory compliance\nAmazon / eBay hazmat & dangerous goods compliance\nOther",
				'One per line. Shown in the Request a Quote and Contact forms.',
			),
			'quote_email'      => array(
				'Quote form – send requests to',
				'email',
				'',
				'Email address that receives quote requests. Leave empty to use the site admin email.',
			),
			'quote_thanks'     => array(
				'Quote form – thank-you message',
				'text',
				'Thank you! Your request has been received. We usually reply within one business day.',
				'',
			),
			'order_now_label'  => array(
				'"Order Now" button text',
				'text',
				'Order Now',
				'The button that takes customers straight to checkout.',
			),
		);
	}

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	/** Raw stored value (or default) for a field. */
	public static function get( string $key ): string {
		$fields = self::fields();
		$saved  = get_option( self::OPTION, array() );
		$value  = is_array( $saved ) && isset( $saved[ $key ] ) ? (string) $saved[ $key ] : '';
		if ( '' === trim( $value ) && isset( $fields[ $key ] ) && ! in_array( $fields[ $key ][1], array( 'email', 'url', 'lines_optional' ), true ) ) {
			$value = $fields[ $key ][2];
		}
		return $value;
	}

	/**
	 * A "lines" field as [slug => label]. The slug is derived from the label, so the
	 * owner only ever edits plain text.
	 */
	public static function lines( string $key ): array {
		$out = array();
		foreach ( preg_split( '/\R/', self::get( $key ) ) as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$slug = sanitize_title( $line );
			if ( '' === $slug ) {
				$slug = 'item-' . count( $out );
			}
			$out[ $slug ] = $line;
		}
		return $out;
	}

	public static function menu(): void {
		add_menu_page(
			__( 'Bionexa Settings', 'bionexa-core' ),
			__( 'Bionexa Settings', 'bionexa-core' ),
			'manage_options',
			'bionexa-settings',
			array( __CLASS__, 'render' ),
			'dashicons-admin-generic',
			57
		);
	}

	public static function register(): void {
		register_setting(
			'bionexa_settings',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);
	}

	public static function sanitize( $input ): array {
		$clean = array();
		foreach ( self::fields() as $key => $field ) {
			$value = is_array( $input ) && isset( $input[ $key ] ) ? (string) $input[ $key ] : '';
			switch ( $field[1] ) {
				case 'lines':
				case 'lines_optional':
					$clean[ $key ] = sanitize_textarea_field( $value );
					break;
				case 'email':
					$clean[ $key ] = sanitize_email( $value );
					break;
				case 'url':
					$clean[ $key ] = esc_url_raw( trim( $value ) );
					break;
				default:
					$clean[ $key ] = sanitize_text_field( $value );
			}
		}
		return $clean;
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Bionexa Settings', 'bionexa-core' ); ?></h1>
			<p><?php esc_html_e( 'Change the choices shown in your SDS information form and quote form. Type one item per line. Empty a box and save to restore the original list.', 'bionexa-core' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'bionexa_settings' ); ?>
				<table class="form-table" role="presentation">
					<?php foreach ( self::fields() as $key => $field ) : ?>
						<?php
						$id   = 'bionexa_' . $key;
						$name = self::OPTION . '[' . $key . ']';
						$saved = get_option( self::OPTION, array() );
						$value = self::get( $key );
						?>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field[0] ); ?></label></th>
							<td>
								<?php if ( in_array( $field[1], array( 'lines', 'lines_optional' ), true ) ) : ?>
									<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="<?php echo esc_attr( (string) min( 14, max( 5, substr_count( $value, "\n" ) + 2 ) ) ); ?>" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
								<?php else : ?>
									<input type="<?php echo in_array( $field[1], array( 'email', 'url' ), true ) ? esc_attr( $field[1] ) : 'text'; ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="<?php echo 'email' === $field[1] ? esc_attr( get_option( 'admin_email' ) ) : ''; ?>">
								<?php endif; ?>
								<?php if ( $field[3] ) : ?>
									<p class="description"><?php echo esc_html( $field[3] ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
