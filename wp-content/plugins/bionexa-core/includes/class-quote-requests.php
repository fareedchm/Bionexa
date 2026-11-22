<?php
/**
 * Custom quote requests: [bionexa_quote_form] shortcode.
 *
 * Used for custom formulation development, bulk SDS projects, ISO/IEC 17025
 * documentation packages, lab consultancy, etc. Each request is saved under
 * "Quote Requests" in wp-admin and emailed to the site admin.
 */

defined( 'ABSPATH' ) || exit;

class Bionexa_Quote_Requests {

	public const POST_TYPE = 'bionexa_quote';

	public static function services(): array {
		return apply_filters( 'bionexa_quote_services', Bionexa_Settings::lines( 'quote_services' ) );
	}

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_shortcode( 'bionexa_quote_form', array( __CLASS__, 'render_form' ) );
		add_action( 'init', array( __CLASS__, 'register_block' ) );
		add_action( 'admin_post_nopriv_bionexa_quote', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_bionexa_quote', array( __CLASS__, 'handle' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
	}

	public static function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Quote Requests', 'bionexa-core' ),
					'singular_name' => __( 'Quote Request', 'bionexa-core' ),
					'menu_name'     => __( 'Quote Requests', 'bionexa-core' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'menu_icon'       => 'dashicons-clipboard',
				'menu_position'   => 56,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
			)
		);
	}

	/** "Bionexa Quote Form" block, so the form can be added from the block inserter. */
	public static function register_block(): void {
		wp_register_script(
			'bionexa-blocks',
			BIONEXA_CORE_URL . 'assets/blocks.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-server-side-render' ),
			BIONEXA_CORE_VERSION,
			true
		);
		register_block_type(
			'bionexa/quote-form',
			array(
				'api_version'     => 3,
				'editor_script'   => 'bionexa-blocks',
				'render_callback' => static fn() => self::render_form(),
			)
		);
	}

	public static function render_form(): string {
		$status = isset( $_GET['quote'] ) ? sanitize_key( $_GET['quote'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$preset = isset( $_GET['service'] ) ? sanitize_title( wp_unslash( $_GET['service'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		if ( '' !== $preset && ! isset( self::services()[ $preset ] ) ) {
			// Links like ?service=17025 pre-select the first service whose name contains that word.
			foreach ( array_keys( self::services() ) as $slug ) {
				if ( str_contains( $slug, $preset ) ) {
					$preset = $slug;
					break;
				}
			}
		}

		ob_start();

		if ( 'sent' === $status ) {
			echo '<div class="bionexa-notice bionexa-notice--success">' . esc_html( Bionexa_Settings::get( 'quote_thanks' ) ) . '</div>';
		} elseif ( 'error' === $status ) {
			echo '<div class="bionexa-notice bionexa-notice--error">' . esc_html__( 'Please fill in your name, a valid email and a short description, then try again.', 'bionexa-core' ) . '</div>';
		}
		?>
		<form class="bionexa-quote-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<input type="hidden" name="action" value="bionexa_quote">
			<input type="hidden" name="redirect" value="<?php echo esc_url( get_permalink() ? get_permalink() : home_url( '/' ) ); ?>">
			<?php wp_nonce_field( 'bionexa_quote', 'bionexa_quote_nonce' ); ?>
			<p class="bionexa-hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>

			<div class="bionexa-grid-2">
				<p><label><?php esc_html_e( 'Your name', 'bionexa-core' ); ?> *<input type="text" name="name" required></label></p>
				<p><label><?php esc_html_e( 'Company', 'bionexa-core' ); ?><input type="text" name="company"></label></p>
				<p><label><?php esc_html_e( 'Email', 'bionexa-core' ); ?> *<input type="email" name="email" required></label></p>
				<p><label><?php esc_html_e( 'Phone / WhatsApp', 'bionexa-core' ); ?><input type="tel" name="phone"></label></p>
				<p><label><?php esc_html_e( 'Country', 'bionexa-core' ); ?><input type="text" name="country"></label></p>
				<p><label><?php esc_html_e( 'Service', 'bionexa-core' ); ?>
					<select name="service">
						<?php foreach ( self::services() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $preset, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select></label></p>
			</div>
			<p><label><?php esc_html_e( 'Project details (number of products, markets, deadline…)', 'bionexa-core' ); ?> *<textarea name="message" rows="6" required></textarea></label></p>
			<p><label><?php esc_html_e( 'Attachment (optional, max 10 MB)', 'bionexa-core' ); ?><input type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png"></label></p>
			<p><button type="submit" class="wp-element-button"><?php esc_html_e( 'Request a quote', 'bionexa-core' ); ?></button></p>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	public static function handle(): void {
		$redirect = isset( $_POST['redirect'] ) ? esc_url_raw( wp_unslash( $_POST['redirect'] ) ) : home_url( '/' );
		$redirect = wp_validate_redirect( $redirect, home_url( '/' ) );

		if ( ! isset( $_POST['bionexa_quote_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['bionexa_quote_nonce'] ), 'bionexa_quote' ) ) {
			wp_safe_redirect( add_query_arg( 'quote', 'error', $redirect ) );
			exit;
		}

		// Honeypot: bots fill every field. Pretend success.
		if ( ! empty( $_POST['website'] ) ) {
			wp_safe_redirect( add_query_arg( 'quote', 'sent', $redirect ) );
			exit;
		}

		$fields = array(
			'name'    => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'company' => sanitize_text_field( wp_unslash( $_POST['company'] ?? '' ) ),
			'email'   => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
			'phone'   => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
			'country' => sanitize_text_field( wp_unslash( $_POST['country'] ?? '' ) ),
			'service' => sanitize_title( wp_unslash( $_POST['service'] ?? '' ) ),
			'message' => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ),
		);

		if ( '' === $fields['name'] || ! is_email( $fields['email'] ) || '' === $fields['message'] ) {
			wp_safe_redirect( add_query_arg( 'quote', 'error', $redirect ) );
			exit;
		}

		$services      = self::services();
		$service_label = $services[ $fields['service'] ] ?? ( $services ? end( $services ) : __( 'General enquiry', 'bionexa-core' ) );

		$upload = bionexa_store_private_upload( 'attachment' );
		if ( is_wp_error( $upload ) ) {
			$upload = null;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'private',
				'post_title'  => sprintf( '%s – %s', $fields['name'], $service_label ),
			)
		);

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			foreach ( $fields as $key => $value ) {
				update_post_meta( $post_id, '_' . $key, $value );
			}
			if ( $upload ) {
				update_post_meta( $post_id, '_attachment', $upload );
			}
		}

		$body  = "New quote request on " . get_bloginfo( 'name' ) . "\n\n";
		$body .= "Service: {$service_label}\n";
		foreach ( array( 'name', 'company', 'email', 'phone', 'country' ) as $key ) {
			$body .= ucfirst( $key ) . ': ' . $fields[ $key ] . "\n";
		}
		$body .= "\n" . $fields['message'] . "\n";
		if ( $post_id ) {
			$body .= "\nView: " . admin_url( 'post.php?post=' . $post_id . '&action=edit' ) . "\n";
		}

		wp_mail(
			apply_filters( 'bionexa_quote_recipient', is_email( Bionexa_Settings::get( 'quote_email' ) ) ? Bionexa_Settings::get( 'quote_email' ) : get_option( 'admin_email' ) ),
			sprintf( '[%s] Quote request: %s', get_bloginfo( 'name' ), $service_label ),
			$body,
			array( 'Reply-To: ' . $fields['name'] . ' <' . $fields['email'] . '>' )
		);

		wp_safe_redirect( add_query_arg( 'quote', 'sent', $redirect ) );
		exit;
	}

	public static function add_meta_box(): void {
		add_meta_box( 'bionexa_quote_details', __( 'Request details', 'bionexa-core' ), array( __CLASS__, 'render_meta_box' ), self::POST_TYPE, 'normal', 'high' );
	}

	public static function render_meta_box( WP_Post $post ): void {
		$services = self::services();
		$rows     = array(
			'Name'    => get_post_meta( $post->ID, '_name', true ),
			'Company' => get_post_meta( $post->ID, '_company', true ),
			'Email'   => get_post_meta( $post->ID, '_email', true ),
			'Phone'   => get_post_meta( $post->ID, '_phone', true ),
			'Country' => get_post_meta( $post->ID, '_country', true ),
			'Service' => $services[ get_post_meta( $post->ID, '_service', true ) ] ?? '',
		);
		echo '<table class="widefat striped"><tbody>';
		foreach ( $rows as $label => $value ) {
			printf( '<tr><th style="width:140px">%s</th><td>%s</td></tr>', esc_html( $label ), esc_html( (string) $value ) );
		}
		echo '</tbody></table>';
		echo '<h4>' . esc_html__( 'Message', 'bionexa-core' ) . '</h4><p>' . nl2br( esc_html( (string) get_post_meta( $post->ID, '_message', true ) ) ) . '</p>';

		$attachment = get_post_meta( $post->ID, '_attachment', true );
		if ( is_array( $attachment ) && ! empty( $attachment['file'] ) ) {
			printf( '<p><a class="button" href="%s">%s %s</a></p>', esc_url( bionexa_private_file_url( $attachment['file'] ) ), esc_html__( 'Download', 'bionexa-core' ), esc_html( $attachment['name'] ) );
		}

		$email = (string) get_post_meta( $post->ID, '_email', true );
		if ( is_email( $email ) ) {
			printf( '<p><a class="button button-primary" href="mailto:%s">%s</a></p>', esc_attr( $email ), esc_html__( 'Reply by email', 'bionexa-core' ) );
		}
	}

	public static function columns( array $columns ): array {
		return array(
			'cb'              => $columns['cb'] ?? '',
			'title'           => __( 'Request', 'bionexa-core' ),
			'bionexa_email'   => __( 'Email', 'bionexa-core' ),
			'bionexa_country' => __( 'Country', 'bionexa-core' ),
			'date'            => $columns['date'] ?? __( 'Date', 'bionexa-core' ),
		);
	}

	public static function column_content( string $column, int $post_id ): void {
		if ( 'bionexa_email' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_email', true ) );
		} elseif ( 'bionexa_country' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_country', true ) );
		}
	}
}
