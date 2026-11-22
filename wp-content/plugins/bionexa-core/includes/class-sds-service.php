<?php
/**
 * SDS (Safety Data Sheet) preparation service ordering.
 *
 * Products whose kind is "sds" show the Bionexa SDS Information Form, following the
 * company's own Word form:
 *   1. Product information        2. Supplier / manufacturer details
 *   3. Composition / ingredients  4. Physical and chemical properties
 *   5. Regulatory & safety information (+ languages, rush delivery, notes)
 *   + "Additional information": extra questions the owner adds in Bionexa Settings.
 *
 * Every answer is saved on the order. Extra languages and rush delivery change the price.
 */

defined( 'ABSPATH' ) || exit;

class Bionexa_SDS_Service {

	/** Still required when the customer uploads the completed Word form. */
	private const ALWAYS_REQUIRED = array( 'product_name', 'regions' );

	/** Files uploaded during validation, picked up when the cart item is created. */
	private static $pending_uploads = array();

	public static function regions(): array {
		return apply_filters( 'bionexa_sds_regions', Bionexa_Settings::lines( 'sds_regions' ) );
	}

	public static function channels(): array {
		return apply_filters( 'bionexa_sds_channels', Bionexa_Settings::lines( 'sds_channels' ) );
	}

	public static function product_types(): array {
		return apply_filters( 'bionexa_sds_product_types', Bionexa_Settings::lines( 'sds_product_types' ) );
	}

	public static function physical_forms(): array {
		return apply_filters( 'bionexa_sds_physical_forms', Bionexa_Settings::lines( 'sds_physical_forms' ) );
	}

	public static function languages(): array {
		return apply_filters( 'bionexa_sds_languages', Bionexa_Settings::lines( 'sds_languages' ) );
	}

	/**
	 * The form, as sections of fields.
	 * Field: label, type (text|textarea|email|tel|url|select|checkboxes|file), required, options, help, placeholder, wide.
	 */
	public static function sections(): array {
		$sections = array();

		// "Prefer Word?" box: download the blank form, upload it completed.
		$word_url = Bionexa_Settings::get( 'sds_word_form_url' );
		if ( '' !== $word_url ) {
			$sections['word'] = array(
				'title'     => __( 'Prefer to fill in our Word form?', 'bionexa-core' ),
				'class'     => 'bionexa-sds-section--word',
				'help_html' => sprintf(
					/* translators: %s: download link */
					__( '%s, fill it in at your own pace, then upload it here. If you upload the completed form, you only need to enter the product name and tick the countries below.', 'bionexa-core' ),
					'<a class="bionexa-word-download" href="' . esc_url( $word_url ) . '" download>' . esc_html__( '⬇ Download the SDS Information Form (Word)', 'bionexa-core' ) . '</a>'
				),
				'fields'    => array(
					'word_form' => array( 'label' => __( 'Upload your completed SDS Information Form', 'bionexa-core' ), 'type' => 'file', 'accept' => '.doc,.docx,.pdf', 'help' => __( 'Word or PDF, max 10 MB.', 'bionexa-core' ) ),
				),
			);
		}

		$sections += array(
			'product'    => array(
				'title'  => __( '1. Product information', 'bionexa-core' ),
				'fields' => array(
					'product_name'  => array( 'label' => __( 'Product name / trade name', 'bionexa-core' ), 'type' => 'text', 'required' => true ),
					'brand'         => array( 'label' => __( 'Brand name', 'bionexa-core' ), 'type' => 'text' ),
					'intended_use'  => array( 'label' => __( 'Intended use(s) of the product', 'bionexa-core' ), 'type' => 'textarea', 'required' => true, 'placeholder' => __( 'e.g. hand cleaning, laboratory research, industrial degreasing', 'bionexa-core' ), 'wide' => true ),
					'physical_form' => array( 'label' => __( 'Physical form', 'bionexa-core' ), 'type' => 'select', 'required' => true, 'options' => self::physical_forms() ),
					'product_type'  => array( 'label' => __( 'Product type', 'bionexa-core' ), 'type' => 'select', 'options' => self::product_types() ),
					'website'       => array( 'label' => __( 'Product website or catalogue link (if available)', 'bionexa-core' ), 'type' => 'url', 'placeholder' => 'https://', 'wide' => true ),
				),
			),
			'supplier'   => array(
				'title'  => __( '2. Supplier / manufacturer details', 'bionexa-core' ),
				'help'   => __( 'These details appear in Section 1 of your SDS.', 'bionexa-core' ),
				'fields' => array(
					'company'   => array( 'label' => __( 'Company name and full address', 'bionexa-core' ), 'type' => 'textarea', 'required' => true, 'wide' => true ),
					'phone'     => array( 'label' => __( 'Contact number', 'bionexa-core' ), 'type' => 'tel', 'required' => true ),
					'email'     => array( 'label' => __( 'Email', 'bionexa-core' ), 'type' => 'email', 'required' => true ),
					'emergency' => array( 'label' => __( 'Emergency contact details', 'bionexa-core' ), 'type' => 'text', 'help' => __( '24-hour emergency phone number, if you have one.', 'bionexa-core' ), 'wide' => true ),
					'logo'      => array( 'label' => __( 'Company logo (optional, for inclusion in SDS)', 'bionexa-core' ), 'type' => 'file', 'accept' => '.png,.jpg,.jpeg,.pdf', 'wide' => true ),
				),
			),
			'composition' => array(
				'title'  => __( '3. Composition / ingredients', 'bionexa-core' ),
				'fields' => array(
					'composition'      => array(
						'label'       => __( 'Chemical names, CAS numbers and percentage (or range) of each ingredient', 'bionexa-core' ),
						'type'        => 'textarea',
						'rows'        => 5,
						'placeholder' => __( "One ingredient per line, e.g.\nEthanol – CAS 64-17-5 – 70%\nWater – CAS 7732-18-5 – 25–30%", 'bionexa-core' ),
						'help'        => __( 'Kept strictly confidential. Not sure? Upload your formula below or ask your manufacturer — we can sign an NDA.', 'bionexa-core' ),
						'wide'        => true,
					),
					'composition_file' => array( 'label' => __( 'Upload formula / composition sheet (optional)', 'bionexa-core' ), 'type' => 'file', 'wide' => true ),
				),
			),
			'properties' => array(
				'title'       => __( '4. Physical and chemical properties', 'bionexa-core' ),
				'help'        => __( 'Optional — fill in what you know and leave the rest blank.', 'bionexa-core' ),
				'collapsible' => true,
				'fields'      => array(
					'appearance' => array( 'label' => __( 'Appearance, colour, odour', 'bionexa-core' ), 'type' => 'text', 'wide' => true ),
					'ph'         => array( 'label' => __( 'pH', 'bionexa-core' ), 'type' => 'text' ),
					'boiling'    => array( 'label' => __( 'Boiling point', 'bionexa-core' ), 'type' => 'text' ),
					'melting'    => array( 'label' => __( 'Melting point', 'bionexa-core' ), 'type' => 'text' ),
					'flash'      => array( 'label' => __( 'Flash point', 'bionexa-core' ), 'type' => 'text' ),
					'density'    => array( 'label' => __( 'Density', 'bionexa-core' ), 'type' => 'text' ),
					'solubility' => array( 'label' => __( 'Solubility', 'bionexa-core' ), 'type' => 'text' ),
				),
			),
			'regulatory' => array(
				'title'  => __( '5. Regulatory & safety information', 'bionexa-core' ),
				'fields' => array(
					'regions'      => array( 'label' => __( 'Country / region for which the SDS is required', 'bionexa-core' ), 'type' => 'checkboxes', 'required' => true, 'options' => self::regions(), 'wide' => true ),
					'existing_sds' => array( 'label' => __( 'Any existing SDS or ingredient SDSs (if available)', 'bionexa-core' ), 'type' => 'file', 'wide' => true ),
					'channel'      => array( 'label' => __( 'Where will you sell it?', 'bionexa-core' ), 'type' => 'select', 'options' => self::channels() ),
				),
			),
		);

		// Extra questions the owner adds in Bionexa Settings (one per line).
		$extra = array();
		foreach ( Bionexa_Settings::lines( 'sds_extra_questions' ) as $slug => $question ) {
			$extra[ 'extra_' . $slug ] = array( 'label' => $question, 'type' => 'text', 'wide' => true );
		}
		if ( $extra ) {
			$sections['extra'] = array(
				'title'  => __( 'Additional information', 'bionexa-core' ),
				'fields' => $extra,
			);
		}

		return apply_filters( 'bionexa_sds_form_sections', $sections );
	}

	public static function init(): void {
		add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'render_form' ) );
		add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'validate' ), 10, 3 );
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'add_cart_item_data' ), 10, 2 );
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'display_cart_item_data' ), 10, 2 );
		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'apply_price_addons' ), 20 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'save_order_item_meta' ), 10, 3 );
		add_action( 'woocommerce_after_order_itemmeta', array( __CLASS__, 'render_admin_file_links' ), 10, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( __CLASS__, 'loop_button_text' ), 10, 2 );
		add_filter( 'woocommerce_product_add_to_cart_url', array( __CLASS__, 'loop_button_url' ), 10, 2 );
		add_filter( 'woocommerce_product_supports', array( __CLASS__, 'disable_loop_ajax' ), 10, 3 );
	}

	private static function is_sds( $product ): bool {
		return 'sds' === Bionexa_Product_Fields::kind( $product );
	}

	/** Submitted value for a field, unslashed but not yet sanitised for storage. */
	private static function posted( string $key, $default = '' ) {
		// phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- WooCommerce add-to-cart has no nonce; sanitised per type by callers.
		return isset( $_POST['bionexa_sds'][ $key ] ) ? wp_unslash( $_POST['bionexa_sds'][ $key ] ) : $default;
	}

	private static function file_input_name( string $key ): string {
		return 'bionexa_sds_file_' . $key;
	}

	/* ------------------------------------------------------------------ */
	/* Product page form                                                   */
	/* ------------------------------------------------------------------ */

	public static function render_form(): void {
		global $product;
		if ( ! self::is_sds( $product ) ) {
			return;
		}

		$lang_price = (float) $product->get_meta( '_bionexa_extra_language_price' );
		$rush_price = (float) $product->get_meta( '_bionexa_rush_price' );
		$all_langs  = array_values( self::languages() );
		$langs      = (array) self::posted( 'languages', array_slice( $all_langs, 0, 1 ) );

		echo '<div class="bionexa-sds-form">';
		echo '<h3 class="bionexa-sds-form__title">' . esc_html( Bionexa_Settings::get( 'sds_heading' ) ) . '</h3>';
		echo '<p class="bionexa-sds-form__intro">' . esc_html__( 'Fields marked * are required. Everything you share is kept confidential.', 'bionexa-core' ) . '</p>';

		foreach ( self::sections() as $section_key => $section ) {
			$extra_cls = isset( $section['class'] ) ? ' ' . sanitize_html_class( $section['class'] ) : '';
			$open_tag  = ! empty( $section['collapsible'] ) ? '<details class="bionexa-sds-section bionexa-sds-section--collapsible' . $extra_cls . '"><summary>' : '<fieldset class="bionexa-sds-section' . $extra_cls . '"><legend>';
			$close_lbl = ! empty( $section['collapsible'] ) ? '</summary>' : '</legend>';
			echo $open_tag . esc_html( $section['title'] ) . $close_lbl; // phpcs:ignore WordPress.Security.EscapeOutput -- static markup.
			if ( ! empty( $section['help'] ) ) {
				echo '<p class="bionexa-sds-section__help">' . esc_html( $section['help'] ) . '</p>';
			}
			if ( ! empty( $section['help_html'] ) ) {
				echo '<p class="bionexa-sds-section__help">' . wp_kses_post( $section['help_html'] ) . '</p>';
			}
			echo '<div class="bionexa-sds-grid">';
			foreach ( $section['fields'] as $key => $field ) {
				self::render_field( $key, $field );
			}
			echo '</div>';
			echo ! empty( $section['collapsible'] ) ? '</details>' : '</fieldset>';
		}

		// Languages, rush delivery and notes.
		?>
		<fieldset class="bionexa-sds-section">
			<legend><?php esc_html_e( 'Delivery options', 'bionexa-core' ); ?></legend>
			<div class="form-row bionexa-wide">
				<span class="bionexa-label">
					<?php esc_html_e( 'SDS languages', 'bionexa-core' ); ?>
					<?php if ( $lang_price > 0 ) : ?>
						<small><?php printf( esc_html__( 'First language included, each extra +%s', 'bionexa-core' ), wp_kses_post( wc_price( $lang_price ) ) ); ?></small>
					<?php endif; ?>
				</span>
				<div class="bionexa-checkgrid bionexa-checkgrid--compact">
					<?php foreach ( $all_langs as $lang ) : ?>
						<label><input type="checkbox" name="bionexa_sds[languages][]" value="<?php echo esc_attr( $lang ); ?>" <?php checked( in_array( $lang, $langs, true ) ); ?>> <?php echo esc_html( $lang ); ?></label>
					<?php endforeach; ?>
				</div>
			</div>

			<?php if ( $rush_price > 0 ) : ?>
				<p class="form-row bionexa-wide">
					<label class="bionexa-inline"><input type="checkbox" name="bionexa_sds[rush]" value="1" <?php checked( '1', self::posted( 'rush' ) ); ?>>
					<?php printf( esc_html__( 'Rush delivery (+%s)', 'bionexa-core' ), wp_kses_post( wc_price( $rush_price ) ) ); ?></label>
				</p>
			<?php endif; ?>

			<p class="form-row bionexa-wide">
				<label for="bionexa_sds_notes"><?php esc_html_e( 'Notes (Amazon ASIN, UN number, pack sizes, deadlines…)', 'bionexa-core' ); ?></label>
				<textarea id="bionexa_sds_notes" name="bionexa_sds[notes]" rows="3"><?php echo esc_textarea( (string) self::posted( 'notes' ) ); ?></textarea>
			</p>
		</fieldset>
		</div>
		<?php
	}

	private static function render_field( string $key, array $field ): void {
		$id       = 'bionexa_sds_' . $key;
		$name     = 'bionexa_sds[' . $key . ']';
		$required = ! empty( $field['required'] );
		$relax    = $required && ! in_array( $key, self::ALWAYS_REQUIRED, true ) ? ' data-relaxable="1"' : '';
		$value    = self::posted( $key, 'checkboxes' === $field['type'] ? array() : '' );
		$classes  = 'form-row' . ( ! empty( $field['wide'] ) || in_array( $field['type'], array( 'textarea', 'checkboxes', 'file' ), true ) ? ' bionexa-wide' : '' );
		$star     = $required ? ' <abbr class="required" title="required">*</abbr>' : '';
		$help     = ! empty( $field['help'] ) ? '<small>' . esc_html( $field['help'] ) . '</small>' : '';

		echo '<div class="' . esc_attr( $classes ) . '">';

		if ( 'checkboxes' === $field['type'] ) {
			echo '<span class="bionexa-label">' . esc_html( $field['label'] ) . $star . '</span>' . $help; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<div class="bionexa-checkgrid" data-required="' . ( $required ? '1' : '0' ) . '" data-label="' . esc_attr( $field['label'] ) . '">';
			foreach ( $field['options'] as $opt_value => $opt_label ) {
				printf(
					'<label><input type="checkbox" name="%1$s[]" value="%2$s" %3$s> %4$s</label>',
					esc_attr( $name ),
					esc_attr( $opt_value ),
					checked( in_array( $opt_value, (array) $value, true ), true, false ),
					esc_html( $opt_label )
				);
			}
			echo '</div></div>';
			return;
		}

		echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . $star . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput

		switch ( $field['type'] ) {
			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="%3$d" placeholder="%4$s" %5$s>%6$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					(int) ( $field['rows'] ?? 3 ),
					esc_attr( $field['placeholder'] ?? '' ),
					$required ? 'required' . $relax : '',
					esc_textarea( (string) $value )
				);
				break;

			case 'select':
				printf( '<select id="%1$s" name="%2$s" %3$s>', esc_attr( $id ), esc_attr( $name ), $required ? 'required' . $relax : '' ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo '<option value="">' . esc_html__( 'Choose…', 'bionexa-core' ) . '</option>';
				foreach ( $field['options'] as $opt_value => $opt_label ) {
					printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $opt_value ), selected( (string) $value, (string) $opt_value, false ), esc_html( $opt_label ) );
				}
				echo '</select>';
				break;

			case 'file':
				printf(
					'<input type="file" id="%1$s" name="%2$s" accept="%3$s">',
					esc_attr( $id ),
					esc_attr( self::file_input_name( $key ) ),
					esc_attr( $field['accept'] ?? '.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png' )
				);
				if ( '' === $help ) {
					$help = '<small>' . esc_html__( 'PDF, Word, Excel or image, max 10 MB.', 'bionexa-core' ) . '</small>';
				}
				break;

			default: // text, email, tel, url
				printf(
					'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" placeholder="%5$s" %6$s>',
					esc_attr( $field['type'] ),
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					esc_attr( $field['placeholder'] ?? '' ),
					$required ? 'required' . $relax : ''
				);
		}

		echo $help . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/* ------------------------------------------------------------------ */
	/* Cart                                                                */
	/* ------------------------------------------------------------------ */

	public static function validate( $passed, $product_id, $quantity ) {
		if ( ! self::is_sds( $product_id ) ) {
			return $passed;
		}

		// Uploads bigger than the server's limit arrive with an empty form.
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( empty( $_POST['bionexa_sds'] ) && ! empty( $_SERVER['CONTENT_LENGTH'] ) ) {
			wc_add_notice( __( 'Your files are too large to upload together. Please upload smaller files, or email them to us after ordering.', 'bionexa-core' ), 'error' );
			return false;
		}

		// Customers who upload the completed Word form only need the product name and countries.
		// phpcs:ignore WordPress.Security.NonceVerification
		$has_word_form = ! empty( $_FILES[ self::file_input_name( 'word_form' ) ]['name'] );

		foreach ( self::sections() as $section ) {
			foreach ( $section['fields'] as $key => $field ) {
				if ( 'file' === $field['type'] ) {
					continue;
				}
				$value    = self::posted( $key, '' );
				$empty    = is_array( $value ) ? ! array_filter( $value ) : '' === trim( (string) $value );
				$required = ! empty( $field['required'] ) && ( ! $has_word_form || in_array( $key, self::ALWAYS_REQUIRED, true ) );

				if ( $required && $empty ) {
					/* translators: %s: field label */
					wc_add_notice( sprintf( __( 'Please fill in: %s', 'bionexa-core' ), $field['label'] ), 'error' );
					$passed = false;
				} elseif ( ! $empty && 'email' === $field['type'] && ! is_email( $value ) ) {
					wc_add_notice( __( 'Please enter a valid email address.', 'bionexa-core' ), 'error' );
					$passed = false;
				}
			}
		}

		if ( ! $passed ) {
			return false;
		}

		self::$pending_uploads = array();
		foreach ( self::sections() as $section ) {
			foreach ( $section['fields'] as $key => $field ) {
				if ( 'file' !== $field['type'] ) {
					continue;
				}
				$upload = bionexa_store_private_upload( self::file_input_name( $key ) );
				if ( is_wp_error( $upload ) ) {
					/* translators: 1: field label, 2: error */
					wc_add_notice( sprintf( __( '%1$s: %2$s', 'bionexa-core' ), $field['label'], $upload->get_error_message() ), 'error' );
					return false;
				}
				if ( $upload ) {
					$upload['label']               = $field['label'];
					self::$pending_uploads[ $key ] = $upload;
				}
			}
		}

		return true;
	}

	public static function add_cart_item_data( array $data, $product_id ): array {
		if ( ! self::is_sds( $product_id ) ) {
			return $data;
		}

		$answers = array(); // label => value, in form order.
		$regions = array();

		foreach ( self::sections() as $section ) {
			foreach ( $section['fields'] as $key => $field ) {
				if ( 'file' === $field['type'] ) {
					continue;
				}
				$raw = self::posted( $key, '' );

				switch ( $field['type'] ) {
					case 'checkboxes':
						$picked = array_values( array_intersect( array_map( 'sanitize_title', (array) $raw ), array_keys( $field['options'] ) ) );
						if ( 'regions' === $key ) {
							$regions = $picked;
						}
						$value = implode( '; ', array_map( static fn( $k ) => $field['options'][ $k ], $picked ) );
						break;
					case 'select':
						$raw   = sanitize_title( (string) $raw );
						$value = $field['options'][ $raw ] ?? '';
						break;
					case 'textarea':
						$value = sanitize_textarea_field( (string) $raw );
						break;
					case 'email':
						$value = sanitize_email( (string) $raw );
						break;
					case 'url':
						$value = esc_url_raw( (string) $raw );
						break;
					default:
						$value = sanitize_text_field( (string) $raw );
				}

				if ( '' !== $value ) {
					$answers[ $field['label'] ] = $value;
				}
			}
		}

		$all_lang = array_values( self::languages() );
		$langs    = array_values( array_intersect( array_map( 'sanitize_text_field', (array) self::posted( 'languages', array() ) ), $all_lang ) );

		$data['bionexa_sds'] = array(
			'product_name' => $answers[ self::sections()['product']['fields']['product_name']['label'] ] ?? '',
			'answers'      => $answers,
			'regions'      => $regions,
			'languages'    => $langs ? $langs : array_slice( $all_lang, 0, 1 ),
			'rush'         => ! empty( self::posted( 'rush' ) ),
			'notes'        => sanitize_textarea_field( (string) self::posted( 'notes' ) ),
			'uploads'      => self::$pending_uploads,
		);

		self::$pending_uploads  = array();
		$data['bionexa_unique'] = wp_generate_uuid4(); // Each SDS request is its own cart line.
		return $data;
	}

	/** Short summary for the cart and checkout. */
	private static function summary( array $sds ): array {
		$regions = self::regions();
		$rows    = array(
			__( 'Product', 'bionexa-core' )   => $sds['product_name'],
			__( 'Regions', 'bionexa-core' )   => implode( '; ', array_map( static fn( $r ) => $regions[ $r ] ?? $r, $sds['regions'] ) ),
			__( 'Languages', 'bionexa-core' ) => implode( ', ', $sds['languages'] ),
		);
		if ( $sds['rush'] ) {
			$rows[ __( 'Delivery', 'bionexa-core' ) ] = __( 'Rush', 'bionexa-core' );
		}
		if ( ! empty( $sds['uploads'] ) ) {
			$rows[ __( 'Files attached', 'bionexa-core' ) ] = (string) count( $sds['uploads'] );
		}
		return array_filter( $rows, static fn( $v ) => '' !== $v );
	}

	public static function display_cart_item_data( array $item_data, array $cart_item ): array {
		if ( empty( $cart_item['bionexa_sds'] ) ) {
			return $item_data;
		}
		foreach ( self::summary( $cart_item['bionexa_sds'] ) as $key => $value ) {
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
	/* Orders: every answer is stored, shown in wp-admin and in emails.    */
	/* ------------------------------------------------------------------ */

	public static function save_order_item_meta( WC_Order_Item_Product $item, $cart_item_key, array $values ): void {
		if ( empty( $values['bionexa_sds'] ) ) {
			return;
		}
		$sds = $values['bionexa_sds'];

		foreach ( $sds['answers'] as $label => $value ) {
			$item->add_meta_data( $label, $value, true );
		}
		$item->add_meta_data( __( 'SDS languages', 'bionexa-core' ), implode( ', ', $sds['languages'] ), true );
		if ( $sds['rush'] ) {
			$item->add_meta_data( __( 'Delivery', 'bionexa-core' ), __( 'Rush', 'bionexa-core' ), true );
		}
		if ( '' !== $sds['notes'] ) {
			$item->add_meta_data( __( 'Notes', 'bionexa-core' ), $sds['notes'], true );
		}
		foreach ( $sds['uploads'] as $upload ) {
			$item->add_meta_data( $upload['label'], $upload['name'], true );
		}
		if ( $sds['uploads'] ) {
			$item->add_meta_data( '_bionexa_uploads', $sds['uploads'], true );
		}
	}

	public static function render_admin_file_links( $item_id, $item ): void {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			return;
		}
		$uploads = $item->get_meta( '_bionexa_uploads' );
		if ( ! is_array( $uploads ) || ! $uploads ) {
			return;
		}
		echo '<p class="bionexa-admin-files">';
		foreach ( $uploads as $upload ) {
			printf(
				'<a class="button button-small" href="%1$s">%2$s %3$s</a> ',
				esc_url( bionexa_private_file_url( $upload['file'] ) ),
				esc_html__( 'Download', 'bionexa-core' ),
				esc_html( $upload['name'] )
			);
		}
		echo '</p>';
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
