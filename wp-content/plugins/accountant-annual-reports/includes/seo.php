<?php
namespace Tax14;
defined( 'ABSPATH' ) || exit;

// Core owns document titles, robots directives, and singular canonical links.
// Yield metadata to an installed SEO plugin, or to the explicit integration filter.
add_action( 'wp_head', function () {
	$enabled = ! defined( 'WPSEO_VERSION' ) && ! defined( 'RANK_MATH_VERSION' ) && ! defined( 'AIOSEO_VERSION' ) && ! defined( 'SEOPRESS_VERSION' );
	if ( ! apply_filters( 'tax14_enable_seo', $enabled ) || is_404() || is_search() ) { return; }
	$description = '';
	$url = home_url( '/' );
	if ( is_singular() ) {
		$post = get_queried_object();
		if ( post_password_required( $post ) ) { return; }
		$description = $post->post_excerpt;
		$url = get_permalink( $post );
	} elseif ( is_post_type_archive( 'annual_report' ) ) {
		$description = __( 'Published annual financial reports and PDF summaries, organised by fiscal year.', 'accountant-annual-reports' );
		$url = get_post_type_archive_link( 'annual_report' );
	}
	if ( ! $description && is_front_page() ) { $description = get_bloginfo( 'description' ); }
	$description = wp_trim_words( wp_strip_all_tags( $description ), 35, '…' );
	if ( $description ) {
		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
	}
	echo '<meta property="og:title" content="' . esc_attr( wp_get_document_title() ) . '">' . "\n";
	echo '<meta property="og:type" content="website">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
	if ( ! is_front_page() ) { return; }
	$contact = get_option( 'tax14_contact', array() );
	if ( empty( $contact['office'] ) && empty( $contact['name'] ) ) { return; }
	$schema = array( '@context' => 'https://schema.org', '@type' => empty( $contact['office'] ) ? 'Person' : 'ProfessionalService', 'name' => ( $contact['office'] ?? '' ) ?: $contact['name'], 'url' => home_url( '/' ) );
	foreach ( array( 'email' => 'email', 'phone' => 'telephone', 'address' => 'address' ) as $key => $property ) {
		if ( ! empty( $contact[ $key ] ) ) { $schema[ $property ] = $contact[ $key ]; }
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 5 );
add_action( 'init', function () { add_post_type_support( 'page', 'excerpt' ); } );
