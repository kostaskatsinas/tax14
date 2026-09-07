<?php
namespace Tax14;
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/starter.php';

add_action( 'admin_menu', function () {
	add_management_page( __( 'Accountant Setup', 'accountant-annual-reports' ), __( 'Accountant Setup', 'accountant-annual-reports' ), 'manage_options', 'tax14-setup', __NAMESPACE__ . '\\setup_page' );
} );
add_action( 'admin_init', function () {
	register_setting( 'tax14_contact', 'tax14_contact', array( 'type' => 'array', 'sanitize_callback' => function ( $input ) {
		$output = array();
		foreach ( array( 'name', 'office', 'email', 'phone', 'address', 'hours' ) as $key ) {
			$value = is_scalar( $input[ $key ] ?? null ) ? (string) $input[ $key ] : '';
			$output[ $key ] = 'email' === $key ? sanitize_email( $value ) : sanitize_textarea_field( $value );
		}
		return $output;
	} ) );
} );
function setup_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$details = get_option( 'tax14_contact', array() );
	?>
	<div class="wrap"><h1><?php esc_html_e( 'Accountant Setup', 'accountant-annual-reports' ); ?></h1>
	<?php settings_errors(); ?>
	<?php if ( isset( $_GET['starter'] ) && 'done' === $_GET['starter'] ) : ?>
	<div class="notice notice-success"><p><?php esc_html_e( 'Starter content is ready. Review all placeholders, set your site name in Settings → General, and replace the sample PDFs before launch.', 'accountant-annual-reports' ); ?></p></div>
	<?php endif; ?>
	<h2><?php esc_html_e( 'Shared contact details', 'accountant-annual-reports' ); ?></h2>
	<p><?php esc_html_e( 'These fields update both the Contact page and the footer. Leave optional fields empty to hide them. Edit all other profile content under Pages.', 'accountant-annual-reports' ); ?></p>
	<form method="post" action="options.php">
	<?php settings_fields( 'tax14_contact' ); ?>
	<table class="form-table" role="presentation">
	<?php foreach ( array( 'name' => __( 'Name', 'accountant-annual-reports' ), 'office' => __( 'Office name', 'accountant-annual-reports' ), 'email' => __( 'Email', 'accountant-annual-reports' ), 'phone' => __( 'Phone', 'accountant-annual-reports' ), 'address' => __( 'Address', 'accountant-annual-reports' ), 'hours' => __( 'Business hours', 'accountant-annual-reports' ) ) as $key => $label ) : ?>
	<tr><th><label for="tax14-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th><td>
	<?php if ( in_array( $key, array( 'address', 'hours' ), true ) ) : ?>
	<textarea class="large-text" rows="3" id="tax14-<?php echo esc_attr( $key ); ?>" name="tax14_contact[<?php echo esc_attr( $key ); ?>]"><?php echo esc_textarea( $details[ $key ] ?? '' ); ?></textarea>
	<?php else : ?>
	<input class="regular-text" type="<?php echo 'email' === $key ? 'email' : 'text'; ?>" id="tax14-<?php echo esc_attr( $key ); ?>" name="tax14_contact[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $details[ $key ] ?? '' ); ?>">
	<?php endif; ?></td></tr>
	<?php endforeach; ?></table><?php submit_button( __( 'Save contact details', 'accountant-annual-reports' ) ); ?></form>
	<hr><h2><?php esc_html_e( 'Optional starter content', 'accountant-annual-reports' ); ?></h2>
	<p><?php esc_html_e( 'Creates missing profile pages, a navigation menu, and three clearly labelled sample PDF reports for 2026, 2025, and 2024. Existing pages and reports are preserved. Legal pages remain draft until reviewed and published. Sample pages and reports will be public.', 'accountant-annual-reports' ); ?></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="tax14_starter"><?php wp_nonce_field( 'tax14_starter' ); ?>
	<p><label><input type="checkbox" name="set_home" value="1"> <?php esc_html_e( 'Use the Home page as the site homepage', 'accountant-annual-reports' ); ?></label></p>
	<?php submit_button( __( 'Create starter content', 'accountant-annual-reports' ), 'secondary' ); ?></form>
	<h2><?php esc_html_e( 'Publish an annual report', 'accountant-annual-reports' ); ?></h2>
	<p><?php esc_html_e( 'Annual Reports → Add New → enter title and fiscal year → choose or upload PDF → Publish. The editor contains the description; Excerpt adds a short summary to report listings.', 'accountant-annual-reports' ); ?></p>
	</div>
	<?php
}
add_action( 'admin_post_tax14_starter', function () {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'upload_files' ) ) { wp_die( esc_html__( 'You are not allowed to create starter content.', 'accountant-annual-reports' ), '', array( 'response' => 403 ) ); }
	check_admin_referer( 'tax14_starter' );
	$result = create_starter( ! empty( $_POST['set_home'] ) );
	if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ) ); }
	wp_safe_redirect( admin_url( 'tools.php?page=tax14-setup&starter=done' ) );
	exit;
} );

/** Creates valid, labelled placeholder PDFs through WordPress's standard media pipeline. */
function sample_pdf( $year ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$stream = "BT /F1 22 Tf 60 740 Td (SAMPLE ANNUAL REPORT " . (int) $year . ") Tj 0 -45 Td /F1 12 Tf (Demonstration document only. No financial information.) Tj 0 -24 Td (Replace this sample with your approved annual PDF report.) Tj ET";
	$objects = array( '<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>', '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>', '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>', '<< /Length ' . strlen( $stream ) . ">>\nstream\n" . $stream . "\nendstream" );
	$pdf = "%PDF-1.4\n";
	$offsets = array( 0 );
	foreach ( $objects as $index => $object ) { $offsets[] = strlen( $pdf ); $pdf .= ( $index + 1 ) . " 0 obj\n" . $object . "\nendobj\n"; }
	$xref = strlen( $pdf );
	$pdf .= "xref\n0 6\n0000000000 65535 f \n";
	foreach ( array_slice( $offsets, 1 ) as $offset ) { $pdf .= sprintf( "%010d 00000 n \n", $offset ); }
	$pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";
	$temp = wp_tempnam( 'tax14-sample.pdf' );
	if ( ! $temp || false === file_put_contents( $temp, $pdf ) ) { return new \WP_Error( 'tax14_pdf', 'Could not create a sample PDF. Check the WordPress temporary directory.' ); }
	$result = media_handle_sideload( array( 'name' => 'sample-annual-report-' . (int) $year . '.pdf', 'tmp_name' => $temp ), 0, 'Sample annual report ' . (int) $year . ' — demonstration only' );
	if ( file_exists( $temp ) ) { wp_delete_file( $temp ); }
	return $result;
}
function create_starter( $set_home = false ) {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'upload_files' ) ) { return new \WP_Error( 'tax14_forbidden', 'Administrator access is required.' ); }
	if ( ! add_option( 'tax14_setup_lock', time(), '', false ) ) { return new \WP_Error( 'tax14_busy', 'Starter setup is already running. If a previous attempt was interrupted, remove the tax14_setup_lock option with WP-CLI and retry.' ); }
	try {
		$pages = array();
		// Create pages before their content so every URL works in subdirectory installations.
		foreach ( array( 'home' => 'Home', 'about' => 'About', 'experience' => 'Experience', 'education' => 'Education', 'contact' => 'Contact', 'privacy-policy' => 'Privacy Policy', 'cookie-policy' => 'Cookie Policy' ) as $slug => $title ) {
			$existing = get_page_by_path( $slug );
			if ( 'privacy-policy' === $slug && get_option( 'wp_page_for_privacy_policy' ) ) { $existing = get_post( (int) get_option( 'wp_page_for_privacy_policy' ) ); }
			if ( $existing ) { $pages[ $slug ] = $existing->ID; continue; }
			$id = wp_insert_post( array( 'post_type' => 'page', 'post_title' => $title, 'post_name' => $slug, 'post_status' => 'draft', 'meta_input' => array( '_tax14_starter_pending' => 1 ) ), true );
			if ( is_wp_error( $id ) ) { return $id; }
			$pages[ $slug ] = $id;
		}
		foreach ( starter_pages( $pages ) as $slug => $content ) {
			$id = $pages[ $slug ];
			if ( ! get_post_meta( $id, '_tax14_starter_pending', true ) ) { continue; }
			$result = wp_update_post( array( 'ID' => $id, 'post_content' => wp_slash( $content ), 'post_status' => str_contains( $slug, 'policy' ) ? 'draft' : 'publish' ), true );
			if ( is_wp_error( $result ) ) { return $result; }
			delete_post_meta( $id, '_tax14_starter_pending' );
		}
		foreach ( array( 2026, 2025, 2024 ) as $year ) {
			if ( get_page_by_path( 'sample-annual-report-' . $year, OBJECT, 'annual_report' ) ) { continue; }
			$pdf = sample_pdf( $year );
			if ( is_wp_error( $pdf ) ) { return $pdf; }
			$id = wp_insert_post( array( 'post_type' => 'annual_report', 'post_title' => 'Sample annual report ' . $year, 'post_name' => 'sample-annual-report-' . $year, 'post_status' => 'publish', 'post_content' => '<p>This is a demonstration document. Replace it with an approved annual report before launching the website.</p>', 'post_excerpt' => 'Demonstration PDF — replace before launch.', 'meta_input' => array( YEAR => $year, PDF => $pdf, ENTITY => '[Company / entity name]' ) ), true );
			if ( is_wp_error( $id ) ) { wp_delete_attachment( $pdf, true ); return $id; }
			wp_update_post( array( 'ID' => $pdf, 'post_parent' => $id ) );
		}
		if ( ! get_posts( array( 'post_type' => 'wp_navigation', 'post_status' => 'publish', 'numberposts' => 1 ) ) ) {
			$nav = '';
			foreach ( array( 'home' => 'Home', 'about' => 'About', 'experience' => 'Experience', 'education' => 'Education', 'reports' => 'Annual Reports', 'contact' => 'Contact' ) as $slug => $label ) {
				$url = 'reports' === $slug ? get_post_type_archive_link( 'annual_report' ) : get_permalink( $pages[ $slug ] );
				$nav .= '<!-- wp:navigation-link ' . wp_json_encode( array( 'label' => $label, 'type' => 'custom', 'url' => $url, 'kind' => 'custom' ) ) . ' /-->';
			}
			$result = wp_insert_post( array( 'post_type' => 'wp_navigation', 'post_status' => 'publish', 'post_title' => 'Accountant primary navigation', 'post_content' => wp_slash( $nav ) ), true );
			if ( is_wp_error( $result ) ) { return $result; }
		}
		if ( ! get_option( 'wp_page_for_privacy_policy' ) ) { update_option( 'wp_page_for_privacy_policy', $pages['privacy-policy'] ); }
		if ( $set_home ) { update_option( 'show_on_front', 'page' ); update_option( 'page_on_front', $pages['home'] ); }
		flush_rewrite_rules();
		return $pages;
	} finally { delete_option( 'tax14_setup_lock' ); }
}
