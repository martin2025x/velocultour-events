<?php
/**
 * Plugin Name: velocultour Events
 * Plugin URI:  https://velocultour.de
 * GitHub Plugin URI: martin2025x/velocultour-events
 * Primary Branch: main
 * Description: Save-the-Date-Block auf der Startseite. CPT vc_event mit Rubriken (Radreisen-Nachtreffen, Fahrtechniktraining, Infovortrag, eigene). Pflege im WP-Admin: Datum, Uhrzeit, Ort, Beschreibung, Bild.
 * Version:     1.3.0
 * Author:      velocultour Webteam
 * Requires PHP: 7.4
 * Requires at least: 6.0
 *
 * @package velocultour-events
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'VC_EVT_VERSION', '1.3.0' );
define( 'VC_EVT_DIR', plugin_dir_path( __FILE__ ) );

require_once VC_EVT_DIR . 'includes/class-vc-events-cpt.php';
require_once VC_EVT_DIR . 'includes/class-vc-events-admin.php';
require_once VC_EVT_DIR . 'includes/class-vc-events-shortcode.php';

add_action( 'plugins_loaded', function () {
	VC_Events_CPT::init();
	VC_Events_Admin::init();
	VC_Events_Shortcode::init();
} );

register_activation_hook( __FILE__, function () {
	VC_Events_CPT::register_post_type();
	flush_rewrite_rules();
} );
register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );

/**
 * Zentral: Rubrik-Definition. Slug ↔ Label ↔ Badge-CSS-Modifier.
 */
function vc_events_rubriken() {
	return array(
		'reise'    => array( 'label' => 'Radreisen-Nachtreffen', 'mod' => 'reise' ),
		'training' => array( 'label' => 'Fahrtechniktraining',   'mod' => 'training' ),
		'vortrag'  => array( 'label' => 'Infovortrag',           'mod' => 'vortrag' ),
		'workshop' => array( 'label' => 'Workshop',              'mod' => 'training' ),
		'event'    => array( 'label' => 'Event / Sonstiges',     'mod' => 'reise' ),
	);
}
