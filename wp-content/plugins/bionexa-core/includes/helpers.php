<?php
/**
 * Shared helpers.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Absolute path of the private folder where customer files (compositions, CoAs) are stored.
 */
function bionexa_private_upload_dir(): string {
	$uploads = wp_upload_dir();
	return trailingslashit( $uploads['basedir'] ) . 'bionexa-private';
}

/**
 * Create the private upload folder and block direct web access to it.
 */
function bionexa_protect_upload_dir(): string {
	$dir = bionexa_private_upload_dir();
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	if ( ! file_exists( $dir . '/.htaccess' ) ) {
		file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	if ( ! file_exists( $dir . '/index.php' ) ) {
		file_put_contents( $dir . '/index.php', "<?php // Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	return $dir;
}

/**
 * Store an uploaded file from $_FILES[$field] in the private folder.
 *
 * @return array{file:string,name:string}|WP_Error|null Null when nothing was uploaded.
 */
function bionexa_store_private_upload( string $field ) {
	if ( empty( $_FILES[ $field ] ) || empty( $_FILES[ $field ]['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return null;
	}

	$file = $_FILES[ $field ]; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

	if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
		return new WP_Error( 'bionexa_upload', __( 'The file could not be uploaded. Please try again.', 'bionexa-core' ) );
	}

	$max_bytes = (int) apply_filters( 'bionexa_max_upload_bytes', 10 * MB_IN_BYTES );
	if ( (int) $file['size'] > $max_bytes ) {
		return new WP_Error( 'bionexa_upload', sprintf( __( 'Files must be smaller than %s.', 'bionexa-core' ), size_format( $max_bytes ) ) );
	}

	$allowed = apply_filters(
		'bionexa_allowed_upload_mimes',
		array(
			'pdf'  => 'application/pdf',
			'doc'  => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'xls'  => 'application/vnd.ms-excel',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'csv'  => 'text/csv',
			'txt'  => 'text/plain',
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png'  => 'image/png',
		)
	);

	$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed );
	if ( empty( $check['ext'] ) ) {
		return new WP_Error( 'bionexa_upload', __( 'Allowed file types: PDF, Word, Excel, CSV, TXT, JPG, PNG.', 'bionexa-core' ) );
	}

	$dir      = bionexa_protect_upload_dir();
	$original = sanitize_file_name( $file['name'] );
	$stored   = wp_unique_filename( $dir, wp_generate_password( 12, false ) . '-' . $original );

	if ( ! move_uploaded_file( $file['tmp_name'], $dir . '/' . $stored ) ) {
		return new WP_Error( 'bionexa_upload', __( 'The file could not be saved.', 'bionexa-core' ) );
	}

	return array(
		'file' => $stored,
		'name' => $original,
	);
}

/**
 * Admin-only download link for a privately stored file.
 */
function bionexa_private_file_url( string $stored ): string {
	return wp_nonce_url(
		add_query_arg(
			array(
				'action' => 'bionexa_private_file',
				'file'   => rawurlencode( $stored ),
			),
			admin_url( 'admin-post.php' )
		),
		'bionexa_private_file_' . $stored
	);
}

add_action(
	'admin_post_bionexa_private_file',
	static function () {
		$stored = isset( $_GET['file'] ) ? sanitize_file_name( wp_unslash( rawurldecode( $_GET['file'] ) ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		check_admin_referer( 'bionexa_private_file_' . $stored );

		if ( ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to download this file.', 'bionexa-core' ), 403 );
		}

		$path = bionexa_private_upload_dir() . '/' . $stored;
		if ( '' === $stored || ! is_file( $path ) ) {
			wp_die( esc_html__( 'File not found.', 'bionexa-core' ), 404 );
		}

		nocache_headers();
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . basename( $path ) . '"' );
		header( 'Content-Length: ' . filesize( $path ) );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
);
