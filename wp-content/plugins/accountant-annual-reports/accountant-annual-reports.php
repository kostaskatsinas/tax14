<?php
/**
 * Plugin Name: Accountant Annual Reports
 * Description: Annual PDF reports, reusable contact details, and optional accountant starter content. No external services.
 * Version: 1.0.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 * Text Domain: accountant-annual-reports
 */
namespace Tax14;
defined( 'ABSPATH' ) || exit;

const VERSION = '1.0.0';
const YEAR = '_tax14_year';
const PDF = '_tax14_pdf';
const ENTITY = '_tax14_entity';

function valid_year( $value ) {
	return is_scalar( $value ) && preg_match( '/^\d{4}$/', (string) $value ) && (int) $value >= 1900 && (int) $value <= 2100;
}
function sanitize_year( $value ) { return valid_year( $value ) ? (int) $value : 0; }
function valid_pdf( $id ) {
	$id = absint( $id );
	return $id && 'attachment' === get_post_type( $id ) && 'application/pdf' === get_post_mime_type( $id )
		&& 'pdf' === strtolower( pathinfo( (string) get_attached_file( $id ), PATHINFO_EXTENSION ) )
		&& (bool) wp_get_attachment_url( $id );
}
function sanitize_pdf( $value ) { return valid_pdf( $value ) ? absint( $value ) : 0; }

function register_content() {
	register_post_type( 'annual_report', array(
		'labels' => array( 'name' => __( 'Annual Reports', 'accountant-annual-reports' ), 'singular_name' => __( 'Annual Report', 'accountant-annual-reports' ), 'add_new_item' => __( 'Add New Annual Report', 'accountant-annual-reports' ), 'edit_item' => __( 'Edit Annual Report', 'accountant-annual-reports' ) ),
		'public' => true, 'show_in_rest' => true, 'has_archive' => 'annual-reports',
		'rewrite' => array( 'slug' => 'annual-reports', 'with_front' => false ),
		'menu_icon' => 'dashicons-media-document', 'menu_position' => 21,
		'supports' => array( 'title', 'editor', 'excerpt', 'revisions', 'custom-fields' ),
	) );
	foreach ( array( YEAR => 'integer', PDF => 'integer', ENTITY => 'string' ) as $key => $type ) {
		register_post_meta( 'annual_report', $key, array(
			'type' => $type, 'single' => true, 'show_in_rest' => true, 'revisions_enabled' => true,
			'sanitize_callback' => YEAR === $key ? __NAMESPACE__ . '\\sanitize_year' : ( PDF === $key ? __NAMESPACE__ . '\\sanitize_pdf' : 'sanitize_text_field' ),
			'auth_callback' => function ( $allowed, $key, $post_id ) { return current_user_can( 'edit_post', $post_id ) && current_user_can( 'upload_files' ); },
		) );
	}
}
add_action( 'init', __NAMESPACE__ . '\\register_content' );
register_activation_hook( __FILE__, function () { register_content(); flush_rewrite_rules(); } );
register_deactivation_hook( __FILE__, function () { unregister_post_type( 'annual_report' ); flush_rewrite_rules(); } );

// A single native form avoids split saves between Gutenberg and classic meta boxes.
// All profile pages continue to use Gutenberg and the Site Editor.
add_filter( 'use_block_editor_for_post_type', function ( $use, $type ) { return 'annual_report' === $type ? false : $use; }, 10, 2 );
add_action( 'add_meta_boxes_annual_report', function () {
	remove_meta_box( 'postcustom', 'annual_report', 'normal' );
	add_meta_box( 'tax14-report', __( 'Report details', 'accountant-annual-reports' ), __NAMESPACE__ . '\\report_box', 'annual_report', 'normal', 'high' );
} );
function report_box( $post ) {
	wp_nonce_field( 'tax14_report', 'tax14_report_nonce' );
	$pdf = (int) get_post_meta( $post->ID, PDF, true );
	?>
	<p><?php esc_html_e( 'Enter a fiscal year, choose a PDF, and publish. Use the Publish panel to change the publication date. Uploaded documents are public; use published reports only.', 'accountant-annual-reports' ); ?></p>
	<p><label for="tax14-year"><strong><?php esc_html_e( 'Fiscal year (required to publish)', 'accountant-annual-reports' ); ?></strong></label><br>
	<input type="number" id="tax14-year" name="tax14_year" min="1900" max="2100" step="1" value="<?php echo esc_attr( get_post_meta( $post->ID, YEAR, true ) ); ?>"></p>
	<p><label for="tax14-entity"><?php esc_html_e( 'Company / entity (optional)', 'accountant-annual-reports' ); ?></label><br>
	<input type="text" class="widefat" id="tax14-entity" name="tax14_entity" value="<?php echo esc_attr( get_post_meta( $post->ID, ENTITY, true ) ); ?>"></p>
	<p><strong><?php esc_html_e( 'PDF document (required to publish)', 'accountant-annual-reports' ); ?></strong></p>
	<input type="hidden" id="tax14-pdf" name="tax14_pdf" value="<?php echo esc_attr( $pdf ); ?>">
	<p id="tax14-pdf-name" aria-live="polite"><?php echo $pdf && valid_pdf( $pdf ) ? esc_html( basename( get_attached_file( $pdf ) ) ) : esc_html__( 'No PDF selected', 'accountant-annual-reports' ); ?></p>
	<button type="button" class="button" id="tax14-select-pdf"><?php esc_html_e( 'Choose or upload PDF', 'accountant-annual-reports' ); ?></button>
	<button type="button" class="button" id="tax14-remove-pdf"><?php esc_html_e( 'Remove selection', 'accountant-annual-reports' ); ?></button>
	<?php
}
add_action( 'admin_enqueue_scripts', function () {
	$screen = get_current_screen();
	if ( ! $screen || 'annual_report' !== $screen->post_type || 'post' !== $screen->base ) { return; }
	wp_enqueue_media();
	wp_enqueue_script( 'tax14-admin', plugins_url( 'assets/admin.js', __FILE__ ), array( 'media-views' ), VERSION, true );
	wp_localize_script( 'tax14-admin', 'tax14Media', array( 'title' => __( 'Choose annual report PDF', 'accountant-annual-reports' ), 'button' => __( 'Use this PDF', 'accountant-annual-reports' ), 'empty' => __( 'No PDF selected', 'accountant-annual-reports' ), 'invalid' => __( 'Please select a PDF file.', 'accountant-annual-reports' ) ) );
} );

function form_authorized( $id ) {
	return isset( $_POST['tax14_report_nonce'] ) && is_string( $_POST['tax14_report_nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tax14_report_nonce'] ) ), 'tax14_report' )
		&& ( $id ? current_user_can( 'edit_post', $id ) : current_user_can( 'edit_posts' ) ) && current_user_can( 'upload_files' );
}
function form_values() {
	return array(
		YEAR => sanitize_year( $_POST['tax14_year'] ?? '' ),
		PDF => sanitize_pdf( is_scalar( $_POST['tax14_pdf'] ?? '' ) ? $_POST['tax14_pdf'] : 0 ),
		ENTITY => sanitize_text_field( wp_unslash( $_POST['tax14_entity'] ?? '' ) ),
	);
}
function candidate_meta( $id, $incoming = array() ) {
	return array_merge( array( YEAR => get_post_meta( $id, YEAR, true ), PDF => get_post_meta( $id, PDF, true ), ENTITY => get_post_meta( $id, ENTITY, true ) ), $incoming );
}
function publishable( $meta, $title ) {
	return valid_year( $meta[ YEAR ] ) && valid_pdf( $meta[ PDF ] ) && '' !== trim( wp_strip_all_tags( $title ) );
}
add_filter( 'wp_insert_post_data', function ( $data, $postarr ) {
	if ( 'annual_report' !== $data['post_type'] || ! in_array( $data['post_status'], array( 'publish', 'future' ), true ) ) { return $data; }
	// The REST filter below validates the complete incoming metadata before core saves it.
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) { return $data; }
	$id = absint( $postarr['ID'] ?? 0 );
	$meta = form_authorized( $id ) ? form_values() : candidate_meta( $id, $postarr['meta_input'] ?? array() );
	if ( ! publishable( $meta, $data['post_title'] ) ) {
		$data['post_status'] = 'draft';
		if ( form_authorized( $id ) ) { set_transient( 'tax14_invalid_' . get_current_user_id(), 1, 60 ); }
	}
	return $data;
}, 10, 2 );
add_filter( 'rest_pre_insert_annual_report', function ( $prepared, $request ) {
	$id = absint( $request['id'] );
	$status = $prepared->post_status ?? ( $id ? get_post_status( $id ) : 'draft' );
	$meta = candidate_meta( $id, (array) ( $request['meta'] ?? array() ) );
	$title = $prepared->post_title ?? get_the_title( $id );
	if ( in_array( $status, array( 'publish', 'future' ), true ) && ! publishable( $meta, $title ) ) {
		return new \WP_Error( 'tax14_invalid_report', __( 'A title, fiscal year from 1900 to 2100, and PDF attachment are required to publish.', 'accountant-annual-reports' ), array( 'status' => 400 ) );
	}
	return $prepared;
}, 10, 2 );
add_action( 'save_post_annual_report', function ( $id ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $id ) || ! form_authorized( $id ) ) { return; }
	foreach ( form_values() as $key => $value ) { update_post_meta( $id, $key, $value ); }
} );
add_action( 'admin_notices', function () {
	$key = 'tax14_invalid_' . get_current_user_id();
	if ( get_transient( $key ) ) {
		delete_transient( $key );
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Report saved as a draft. Add a title, a fiscal year from 1900 to 2100, and a valid PDF before publishing.', 'accountant-annual-reports' ) . '</p></div>';
	}
} );

add_filter( 'manage_annual_report_posts_columns', function ( $columns ) {
	$columns['tax14_year'] = __( 'Fiscal year', 'accountant-annual-reports' );
	$columns['tax14_pdf'] = __( 'PDF', 'accountant-annual-reports' );
	return $columns;
} );
add_action( 'manage_annual_report_posts_custom_column', function ( $column, $id ) {
	if ( 'tax14_year' === $column ) { echo esc_html( get_post_meta( $id, YEAR, true ) ); }
	if ( 'tax14_pdf' === $column ) { echo valid_pdf( get_post_meta( $id, PDF, true ) ) ? esc_html__( 'Attached', 'accountant-annual-reports' ) : esc_html__( 'Missing', 'accountant-annual-reports' ); }
}, 10, 2 );
function order_reports( $query ) {
	$query->set( 'meta_key', YEAR );
	$query->set( 'orderby', array( 'meta_value_num' => 'DESC', 'date' => 'DESC', 'ID' => 'DESC' ) );
}
add_action( 'pre_get_posts', function ( $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive( 'annual_report' ) ) { order_reports( $query ); $query->set( 'posts_per_page', 12 ); }
} );

function pdf_links( $id ) {
	$pdf = get_post_meta( $id, PDF, true );
	if ( ! valid_pdf( $pdf ) ) { return '<p>' . esc_html__( 'PDF currently unavailable.', 'accountant-annual-reports' ) . '</p>'; }
	$url = wp_get_attachment_url( $pdf );
	$title = get_the_title( $id );
	return '<div class="tax14-pdf-links"><a class="tax14-pdf-button" target="_blank" rel="noopener noreferrer" href="' . esc_url( $url ) . '" aria-label="' . esc_attr( sprintf( __( 'View %s — PDF, opens in a new tab', 'accountant-annual-reports' ), $title ) ) . '">' . esc_html__( 'View PDF', 'accountant-annual-reports' ) . ' <span aria-hidden="true">↗</span><span class="screen-reader-text">' . esc_html__( ' (opens in a new tab)', 'accountant-annual-reports' ) . '</span></a><a download href="' . esc_url( $url ) . '" aria-label="' . esc_attr( sprintf( __( 'Download %s — PDF', 'accountant-annual-reports' ), $title ) ) . '">' . esc_html__( 'Download PDF', 'accountant-annual-reports' ) . '</a></div>';
}
function report_date( $id ) {
	return '<time datetime="' . esc_attr( get_the_date( DATE_W3C, $id ) ) . '">' . esc_html( get_the_date( '', $id ) ) . '</time>';
}
function render_reports( $attributes = array() ) {
	$archive = ! empty( $attributes['archive'] ) && is_post_type_archive( 'annual_report' );
	if ( $archive ) { global $wp_query; $query = $wp_query; }
	else {
		$query = new \WP_Query( array( 'post_type' => 'annual_report', 'post_status' => 'publish', 'posts_per_page' => min( 12, max( 1, absint( $attributes['limit'] ?? 3 ) ) ), 'meta_key' => YEAR, 'orderby' => array( 'meta_value_num' => 'DESC', 'date' => 'DESC', 'ID' => 'DESC' ), 'no_found_rows' => true ) );
	}
	$html = '<div class="tax14-reports alignwide">';
	if ( ! $query->have_posts() ) { return $html . '<p>' . esc_html__( 'No annual reports have been published yet.', 'accountant-annual-reports' ) . '</p></div>'; }
	foreach ( $query->posts as $report ) {
		$id = $report->ID;
		$entity = get_post_meta( $id, ENTITY, true );
		$html .= '<article class="tax14-report-row"><p class="tax14-report-year">' . esc_html( get_post_meta( $id, YEAR, true ) ) . '</p><div class="tax14-report-copy"><h2 class="tax14-report-title"><a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a></h2><p class="tax14-report-meta">' . esc_html__( 'Published:', 'accountant-annual-reports' ) . ' ' . report_date( $id ) . ( $entity ? ' · ' . esc_html( $entity ) : '' ) . '</p>';
		if ( $report->post_excerpt ) { $html .= '<p>' . esc_html( $report->post_excerpt ) . '</p>'; }
		$html .= '</div>' . pdf_links( $id ) . '</article>';
	}
	if ( $archive && $query->max_num_pages > 1 ) {
		$html .= '<nav class="tax14-pagination" aria-label="' . esc_attr__( 'Annual reports pages', 'accountant-annual-reports' ) . '">' . paginate_links( array( 'total' => $query->max_num_pages, 'current' => max( 1, get_query_var( 'paged' ) ) ) ) . '</nav>';
	}
	return $html . '</div>';
}
function render_details() {
	$id = get_the_ID();
	if ( 'annual_report' !== get_post_type( $id ) ) { return ''; }
	return '<div class="tax14-report-details"><p class="tax14-report-meta">' . esc_html__( 'Fiscal year:', 'accountant-annual-reports' ) . ' ' . esc_html( get_post_meta( $id, YEAR, true ) ) . ' · ' . esc_html__( 'Published:', 'accountant-annual-reports' ) . ' ' . report_date( $id ) . '</p><p>' . esc_html( get_post_meta( $id, ENTITY, true ) ) . '</p>' . pdf_links( $id ) . '<p><a href="' . esc_url( get_post_type_archive_link( 'annual_report' ) ) . '">' . esc_html__( '← All annual reports', 'accountant-annual-reports' ) . '</a></p></div>';
}
add_filter( 'the_content', function ( $content ) {
	if ( is_singular( 'annual_report' ) && in_the_loop() && is_main_query() && 'accountant-site' !== get_template() ) { return render_details() . $content; }
	return $content;
} );
add_shortcode( 'annual_reports', function ( $attributes ) { return render_reports( shortcode_atts( array( 'limit' => 3 ), $attributes ) ); } );

function render_contact( $summary = false ) {
	$details = get_option( 'tax14_contact', array() );
	$html = '<div class="tax14-contact">';
	foreach ( array( 'name', 'office', 'email', 'phone', 'address', 'hours' ) as $key ) {
		$value = $details[ $key ] ?? '';
		if ( ! $value || ( $summary && ! in_array( $key, array( 'email', 'phone' ), true ) ) ) { continue; }
		if ( 'email' === $key ) { $value = '<a href="mailto:' . esc_attr( $value ) . '">' . esc_html( $value ) . '</a>'; }
		elseif ( 'phone' === $key ) { $value = '<a href="tel:' . esc_attr( preg_replace( '/[^+0-9]/', '', $value ) ) . '">' . esc_html( $value ) . '</a>'; }
		else { $value = nl2br( esc_html( $value ) ); }
		$html .= '<p>' . $value . '</p>';
	}
	return $html . '</div>';
}
function legal_links() {
	$links = array();
	foreach ( array( 'privacy-policy' => __( 'Privacy Policy', 'accountant-annual-reports' ), 'cookie-policy' => __( 'Cookie Policy', 'accountant-annual-reports' ) ) as $slug => $label ) {
		$privacy_id = (int) get_option( 'wp_page_for_privacy_policy' );
		$page = 'privacy-policy' === $slug ? ( $privacy_id ? get_post( $privacy_id ) : null ) : get_page_by_path( $slug );
		if ( $page && 'publish' === $page->post_status ) { $links[] = '<a href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( $label ) . '</a>'; }
	}
	return $links ? '<p class="tax14-legal-links">' . implode( ' · ', $links ) . '</p>' : '';
}
add_action( 'init', function () {
	wp_register_style( 'tax14-reports', plugins_url( 'assets/reports.css', __FILE__ ), array(), VERSION );
	wp_register_script( 'tax14-blocks', plugins_url( 'assets/blocks.js', __FILE__ ), array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-i18n' ), VERSION, true );
	$blocks = array(
		'reports' => __NAMESPACE__ . '\\render_reports', 'report-details' => __NAMESPACE__ . '\\render_details',
		'contact' => function () { return render_contact(); }, 'contact-summary' => function () { return render_contact( true ); },
		'copyright' => function () { return '<p>© ' . esc_html( wp_date( 'Y' ) ) . ' ' . esc_html( get_bloginfo( 'name' ) ) . '</p>'; },
		'legal-links' => __NAMESPACE__ . '\\legal_links',
	);
	foreach ( $blocks as $name => $render ) {
		register_block_type( 'accountant/' . $name, array( 'api_version' => 3, 'editor_script' => 'tax14-blocks', 'style' => 'tax14-reports', 'render_callback' => $render,
			'attributes' => 'reports' === $name ? array( 'limit' => array( 'type' => 'number', 'default' => 3 ), 'archive' => array( 'type' => 'boolean', 'default' => false ), 'align' => array( 'type' => 'string', 'default' => 'wide' ) ) : array(),
			'supports' => array( 'html' => false, 'align' => array( 'wide', 'full' ) ),
		) );
	}
} );
// Also style the shortcode and fallback templates when another theme is active.
add_action( 'wp_enqueue_scripts', function () { wp_enqueue_style( 'tax14-reports' ); } );

require_once __DIR__ . '/includes/setup.php';
require_once __DIR__ . '/includes/seo.php';
