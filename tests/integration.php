<?php
/** Run against a disposable WordPress database with wp eval-file --user=<admin>. */
use const Tax14\YEAR;
use const Tax14\PDF;
use const Tax14\ENTITY;

function tax14_expect( $condition, $message ) {
	if ( ! $condition ) { WP_CLI::error( $message ); }
	WP_CLI::log( 'PASS ' . $message );
}
$admin = get_current_user_id();
$pages = Tax14\create_starter( true );
tax14_expect( ! is_wp_error( $pages ), 'Starter setup succeeds' );
tax14_expect( 7 === count( $pages ), 'All seven starter pages exist' );
tax14_expect( 'draft' === get_post_status( $pages['privacy-policy'] ), 'Privacy policy is an unpublished placeholder' );
tax14_expect( 'draft' === get_post_status( $pages['cookie-policy'] ), 'Cookie policy is an unpublished placeholder' );
tax14_expect( '' === Tax14\legal_links(), 'Unpublished policies do not create dead footer links' );
$original = get_post( $pages['about'] )->post_content;
wp_update_post( array( 'ID' => $pages['about'], 'post_content' => 'Preserve this existing content.' ) );
$count = (int) wp_count_posts( 'annual_report' )->publish;
$again = Tax14\create_starter();
tax14_expect( $count === (int) wp_count_posts( 'annual_report' )->publish && 3 === $count, 'Setup is idempotent and creates three sample reports' );
tax14_expect( 'Preserve this existing content.' === get_post( $pages['about'] )->post_content, 'Rerunning setup preserves edited pages' );
wp_update_post( array( 'ID' => $pages['about'], 'post_content' => wp_slash( $original ) ) );
$sample = get_page_by_path( 'sample-annual-report-2026', OBJECT, 'annual_report' );
$pdf = (int) get_post_meta( $sample->ID, PDF, true );
tax14_expect( Tax14\valid_pdf( $pdf ) && str_starts_with( file_get_contents( get_attached_file( $pdf ) ), '%PDF-' ), 'Starter PDF is a real media attachment' );
tax14_expect( ! Tax14\valid_pdf( $pages['about'] ), 'Pages cannot masquerade as PDF attachments' );
$invalid = wp_insert_post( array( 'post_type' => 'annual_report', 'post_title' => 'Invalid test', 'post_status' => 'publish', 'meta_input' => array( YEAR => 2027, PDF => 0 ) ) );
tax14_expect( 'draft' === get_post_status( $invalid ), 'Publishing without a PDF is blocked' );
$bad_year = wp_insert_post( array( 'post_type' => 'annual_report', 'post_title' => 'Invalid year', 'post_status' => 'publish', 'meta_input' => array( YEAR => 9999, PDF => $pdf ) ) );
tax14_expect( 'draft' === get_post_status( $bad_year ), 'Out-of-range fiscal years cannot publish' );
$title = '<script>alert(1)</script> Test report';
$valid = wp_insert_post( array( 'post_type' => 'annual_report', 'post_title' => $title, 'post_status' => 'publish', 'meta_input' => array( YEAR => 2027, PDF => $pdf, ENTITY => '<b>Example</b>' ) ) );
tax14_expect( 'publish' === get_post_status( $valid ), 'Valid reports publish' );
$rendered = Tax14\render_reports( array( 'limit' => 3 ) );
tax14_expect( strpos( $rendered, '2027' ) < strpos( $rendered, '2026' ), 'Reports sort by fiscal year descending' );
tax14_expect( ! str_contains( $rendered, '<script>' ) && str_contains( $rendered, 'noopener noreferrer' ), 'Report output escapes titles and secures new tabs' );
$_POST = array( 'tax14_year' => '2001', 'tax14_pdf' => $pdf );
do_action( 'save_post_annual_report', $valid );
tax14_expect( 2027 === (int) get_post_meta( $valid, YEAR, true ), 'Missing nonce prevents metadata writes' );
$subscriber = wp_insert_user( array( 'user_login' => 'tax14-subscriber', 'user_pass' => wp_generate_password(), 'role' => 'subscriber' ) );
wp_set_current_user( $subscriber );
$_POST['tax14_report_nonce'] = wp_create_nonce( 'tax14_report' );
do_action( 'save_post_annual_report', $valid );
tax14_expect( 2027 === (int) get_post_meta( $valid, YEAR, true ), 'Subscriber cannot change report metadata even with a nonce' );
tax14_expect( is_wp_error( Tax14\create_starter() ), 'Subscriber cannot seed content' );
$_POST = array();
wp_set_current_user( $admin );
$_POST = array( 'tax14_year' => '2028', 'tax14_pdf' => $pdf, 'tax14_entity' => '<b>Edited company</b>', 'tax14_report_nonce' => wp_create_nonce( 'tax14_report' ) );
do_action( 'save_post_annual_report', $valid );
tax14_expect( 2028 === (int) get_post_meta( $valid, YEAR, true ) && 'Edited company' === get_post_meta( $valid, ENTITY, true ), 'Authorized form saves sanitized metadata' );
$_POST = array();
// Verify REST validation separately from classic form submission.
$request = new WP_REST_Request( 'POST', '/wp/v2/annual_report' );
$request->set_param( 'status', 'publish' );
$request->set_param( 'title', 'Missing REST PDF' );
$response = rest_do_request( $request );
tax14_expect( 400 === $response->get_status(), 'REST publish rejects missing report metadata' );
wp_set_current_user( 0 );
$response = rest_do_request( $request );
tax14_expect( 401 === $response->get_status(), 'Anonymous REST publishing is denied' );
wp_set_current_user( $admin );
foreach ( array( $invalid, $bad_year, $valid ) as $id ) { wp_delete_post( $id, true ); }
update_option( 'tax14_contact', array( 'name' => '', 'office' => 'Example Accounting Office', 'email' => 'demo@example.test', 'phone' => '+30 210 000 0000', 'address' => '[Office address]', 'hours' => '[Business hours]' ) );
WP_CLI::success( 'Integration checks complete.' );
