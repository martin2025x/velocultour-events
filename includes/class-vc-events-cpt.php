<?php
/**
 * Custom Post Type: vc_event
 *
 * @package velocultour-events
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class VC_Events_CPT {

	const POST_TYPE = 'vc_event';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
	}

	public static function register_post_type() {
		register_post_type( self::POST_TYPE, array(
			'label'              => 'Veranstaltungen',
			'labels'             => array(
				'name'               => 'Veranstaltungen',
				'singular_name'      => 'Veranstaltung',
				'add_new'            => 'Veranstaltung anlegen',
				'add_new_item'       => 'Neue Veranstaltung',
				'edit_item'          => 'Veranstaltung bearbeiten',
				'new_item'           => 'Neue Veranstaltung',
				'view_item'          => 'Veranstaltung ansehen',
				'search_items'       => 'Veranstaltungen suchen',
				'not_found'          => 'Keine Veranstaltungen gefunden.',
				'menu_name'          => 'Veranstaltungen',
				'all_items'          => 'Alle Veranstaltungen',
				'featured_image'     => 'Bild für die Card',
				'set_featured_image' => 'Bild festlegen',
			),
			'public'             => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_icon'          => 'dashicons-calendar-alt',
			'menu_position'      => 27,
			'supports'           => array( 'title', 'thumbnail' ),
			'has_archive'        => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'show_in_rest'       => false,
		) );
	}

	/**
	 * Hilfsfunktion: Holt die nächsten N Events (zukünftig, sortiert nach Datum).
	 *
	 * @param int $limit
	 * @return WP_Post[]
	 */
	public static function get_upcoming( $limit = 3 ) {
		$today = current_time( 'Y-m-d' );
		$q = new WP_Query( array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => (int) $limit,
			'meta_key'       => '_vc_event_datum',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			// Sichtbar bleiben, solange IRGENDEIN Termin noch bevorsteht. Massgeblich
			// ist daher _vc_event_datum_ende (spaetester Termin), nicht der erste.
			// Der zweite Zweig faengt Veranstaltungen ab, die vor der Umstellung auf
			// mehrere Termine gespeichert wurden und das Feld noch nicht haben.
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_vc_event_datum_ende',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				),
				array(
					'relation' => 'AND',
					array(
						'key'     => '_vc_event_datum_ende',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_vc_event_datum',
						'value'   => $today,
						'compare' => '>=',
						'type'    => 'DATE',
					),
				),
			),
			'no_found_rows'  => true,
		) );
		return $q->posts;
	}

	/**
	 * Alle gepflegten Termine einer Veranstaltung, aufsteigend sortiert.
	 *
	 * @param int $post_id
	 * @return string[] Liste von Datumsangaben im Format YYYY-MM-DD
	 */
	public static function get_termine( $post_id ) {
		$termine = array();
		foreach ( array( '_vc_event_datum', '_vc_event_datum_2', '_vc_event_datum_3', '_vc_event_datum_4' ) as $key ) {
			$d = get_post_meta( $post_id, $key, true );
			if ( $d ) {
				$termine[] = $d;
			}
		}
		$termine = array_values( array_unique( $termine ) );
		sort( $termine );
		return $termine;
	}

	/**
	 * Der naechste noch bevorstehende Termin — oder der letzte, wenn alle vorbei sind.
	 *
	 * @param int $post_id
	 * @return string
	 */
	public static function get_naechster_termin( $post_id ) {
		$termine = self::get_termine( $post_id );
		if ( ! $termine ) {
			return '';
		}
		$heute = current_time( 'Y-m-d' );
		foreach ( $termine as $t ) {
			if ( $t >= $heute ) {
				return $t;
			}
		}
		return end( $termine );
	}
}
