<?php
namespace Tax14;
defined( 'ABSPATH' ) || exit;

function starter_block( $name, $attributes, $html ) {
	return '<!-- wp:' . $name . ( $attributes ? ' ' . wp_json_encode( $attributes ) : '' ) . ' -->' . $html . '<!-- /wp:' . $name . ' -->';
}
function starter_p( $text, $class = '' ) {
	return starter_block( 'paragraph', $class ? array( 'className' => $class ) : array(), '<p' . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . '>' . $text . '</p>' );
}
function starter_h( $text, $level = 2 ) {
	return starter_block( 'heading', array( 'level' => $level ), '<h' . $level . ' class="wp-block-heading">' . esc_html( $text ) . '</h' . $level . '>' );
}
function starter_group( $content, $class = '', $wide = false ) {
	$attrs = array( 'layout' => array( 'type' => 'default' ) );
	if ( $class ) { $attrs['className'] = $class; }
	if ( $wide ) { $attrs['align'] = 'wide'; }
	return starter_block( 'group', $attrs, '<div class="wp-block-group' . ( $wide ? ' alignwide' : '' ) . ( $class ? ' ' . esc_attr( $class ) : '' ) . '">' . $content . '</div>' );
}
function starter_columns( $left, $right ) {
	return starter_block( 'columns', array(), '<div class="wp-block-columns">' . starter_block( 'column', array( 'width' => '34%' ), '<div class="wp-block-column" style="flex-basis:34%">' . $left . '</div>' ) . starter_block( 'column', array( 'width' => '66%' ), '<div class="wp-block-column" style="flex-basis:66%">' . $right . '</div>' ) . '</div>' );
}
function starter_button( $text, $url, $outline = false ) {
	return starter_block( 'button', $outline ? array( 'className' => 'is-style-outline' ) : array(), '<div class="wp-block-button' . ( $outline ? ' is-style-outline' : '' ) . '"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a></div>' );
}
function starter_timeline( $education = false ) {
	if ( $education ) {
		return starter_group( starter_p( '[Start year] — [Graduation year]', 'timeline-date' ) . starter_h( '[Degree / qualification]', 3 ) . starter_p( '[University / institution] · [Field of study]' ) . starter_p( '[Describe relevant studies, dissertation, or professional training.]' ), 'timeline-entry' );
	}
	return starter_group( starter_p( '[Start date] — [End date / Present]', 'timeline-date' ) . starter_h( '[Job title]', 3 ) . starter_p( '[Company / organisation] · [Location]' ) . starter_p( '[Describe the role, responsibilities, and industries served.]' ) . starter_block( 'list', array(), '<ul class="wp-block-list">' . starter_block( 'list-item', array(), '<li>[Key responsibility]</li>' ) . starter_block( 'list-item', array(), '<li>[Verified achievement]</li>' ) . '</ul>' ), 'timeline-entry' );
}
function starter_pages( $ids ) {
	$links = array_map( 'get_permalink', $ids );
	$reports = get_post_type_archive_link( 'annual_report' );
	$buttons = starter_block( 'buttons', array(), '<div class="wp-block-buttons">' . starter_button( 'View profile', $links['about'] ) . starter_button( 'Annual reports', $reports, true ) . '</div>' );
	$hero = starter_group( starter_p( 'Professional profile & annual reports', 'eyebrow' ) . starter_h( '[Accountant name]', 1 ) . starter_p( '[Professional title / verified qualification]', 'eyebrow' ) . starter_p( '[Introduce your accounting specialisation, who you work with, and the approach clients can expect.]', 'hero-intro' ) . $buttons, 'hero', true );
	$about = starter_group( starter_columns( starter_p( '01 / Profile', 'eyebrow' ) . starter_h( 'Clarity. Care. Perspective.' ), starter_p( '[Describe your professional background and areas of expertise. Include your years of experience only after verification.]' ) . starter_p( '[Explain your work philosophy and the services or industries you support.]' ) . starter_p( '<a href="' . esc_url( $links['about'] ) . '">More about my practice →</a>' ) ), 'section-rule', true );
	$experience = starter_group( starter_columns( starter_p( '02 / Experience', 'eyebrow' ) . starter_h( 'Professional experience' ), starter_timeline() . starter_p( '<a href="' . esc_url( $links['experience'] ) . '">View full experience →</a>' ) ), 'section-rule', true );
	$education = starter_group( starter_columns( starter_p( '03 / Qualifications', 'eyebrow' ) . starter_h( 'Education & training' ), starter_timeline( true ) . starter_p( '<a href="' . esc_url( $links['education'] ) . '">View education &amp; qualifications →</a>' ) ), 'section-rule', true );
	$report_section = starter_group( starter_p( '04 / Publications', 'eyebrow' ) . starter_h( 'Annual reports' ) . starter_p( 'Published financial summaries, available to view and download.' ) . '<!-- wp:accountant/reports {"limit":3} /-->' . starter_p( '<a href="' . esc_url( $reports ) . '">View all annual reports →</a>' ), 'section-rule', true );
	$contact = starter_group( starter_columns( starter_p( '05 / Contact', 'eyebrow' ) . starter_h( 'Start a conversation.' ), starter_p( '[Add a short invitation to contact your office.]' ) . starter_block( 'buttons', array(), '<div class="wp-block-buttons">' . starter_button( 'Contact the office', $links['contact'] ) . '</div>' ) ), 'section-rule', true );
	return array(
		'home' => $hero . $about . $experience . $education . $report_section . $contact,
		'about' => starter_p( 'Background & approach', 'eyebrow' ) . starter_p( '[Write a professional summary, including your current role and verified qualifications.]' ) . starter_h( 'Areas of specialisation' ) . starter_p( '[List the accounting services, subjects, and industries in which you specialise.]' ) . starter_h( 'Work philosophy' ) . starter_p( '[Describe how you approach accuracy, communication, deadlines, and client relationships.]' ) . starter_h( 'Professional background' ) . starter_p( '[Summarise your career. Add an optional portrait with meaningful alt text using an Image block.]' ) . starter_h( 'Memberships & certifications' ) . starter_p( '[Add verified professional memberships or remove this section.]' ),
		'experience' => starter_p( 'Career history', 'eyebrow' ) . starter_p( '[Summarise your professional experience. List the most recent position first.]' ) . starter_timeline() . starter_timeline(),
		'education' => starter_p( 'Education & professional development', 'eyebrow' ) . starter_timeline( true ) . starter_timeline( true ) . starter_h( 'Certifications' ) . starter_p( '[Certification title] · [Issuing organisation] · [Year]' ) . starter_p( '[Add an optional credential link using the editor toolbar.]' ) . starter_h( 'Seminars & training' ) . starter_p( '[Add relevant professional training, or remove this section.]' ),
		'contact' => starter_p( 'Get in touch', 'eyebrow' ) . starter_p( '[Add a short introduction and preferred contact method.]' ) . starter_group( '<!-- wp:accountant/contact /-->', 'contact-panel' ) . starter_p( '[Optional: add your LinkedIn profile using a Social Icons block.]' ),
		'privacy-policy' => starter_group( starter_h( 'Policy content to be completed' ) . starter_p( '[Describe the actual data collected, purposes, providers, retention, contact details, and applicable rights after reviewing your own operations. Use Settings → Privacy for WordPress suggested policy content. This draft is not a legal policy.]' ), 'legal-placeholder' ),
		'cookie-policy' => starter_group( starter_h( 'Cookie information to be completed' ) . starter_p( '[Document the cookies and similar technologies actually used by your installed site and any embeds. Review before publishing. This draft is not a legal policy.]' ), 'legal-placeholder' ),
	);
}
