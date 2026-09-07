<?php
/** Presentation only. Reports and starter content live in the companion plugin. */
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'accountant-site', get_template_directory() . '/languages' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/site.css' );
	add_theme_support( 'responsive-embeds' );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'accountant-site', get_template_directory_uri() . '/assets/site.css', array(), wp_get_theme()->get( 'Version' ) );
} );

add_action( 'init', function () {
	register_block_pattern_category( 'accountant', array( 'label' => __( 'Accountant', 'accountant-site' ) ) );
} );
