<?php
/**
 * Shortcode [vc_events] — rendert die nächsten N Events im Velo-Layout.
 *
 * Attribute:
 *   limit (int)  Anzahl Cards (Default 3)
 *
 * Hinweis: Das Markup ist identisch zum bestehenden Theme-HTML
 * (.events-banner / .events-grid / .event-card), damit das CSS aus
 * dem Theme greift. Nur die Daten werden dynamisch aus dem CPT
 * vc_event geladen.
 *
 * @package velocultour-events
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class VC_Events_Shortcode {

	public static function init() {
		add_shortcode( 'vc_events', array( __CLASS__, 'render' ) );
	}

	public static function render( $atts ) {
		$atts = shortcode_atts( array(
			'limit' => 3,
		), $atts, 'vc_events' );

		$events = VC_Events_CPT::get_upcoming( (int) $atts['limit'] );
		if ( empty( $events ) ) return '';

		$rubriken = vc_events_rubriken();

		ob_start();
		?>
		<div class="events-grid">
			<?php $i = 0; foreach ( $events as $post ) :
				$rubrik_slug = get_post_meta( $post->ID, '_vc_event_rubrik', true ) ?: 'event';
				$rubrik      = $rubriken[ $rubrik_slug ] ?? $rubriken['event'];
				$datum       = get_post_meta( $post->ID, '_vc_event_datum', true );
				$uhrzeit     = get_post_meta( $post->ID, '_vc_event_uhrzeit', true );
				$ort         = get_post_meta( $post->ID, '_vc_event_ort', true );
				$desc        = get_post_meta( $post->ID, '_vc_event_desc', true );
				$thumb_id    = get_post_thumbnail_id( $post->ID );
				$image_url   = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '';
				$alt         = $thumb_id ? get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ) : '';
				if ( ! $alt ) $alt = $post->post_title;

				$ts = $datum ? strtotime( $datum ) : 0;
				$day = $ts ? wp_date( 'd', $ts ) : '—';
				// Monat + 2-stellige Jahreszahl, z.B. "Juni 26"
				$month_year = $ts ? wp_date( 'F y', $ts ) : '';

				$meta_parts = array();
				if ( $uhrzeit ) $meta_parts[] = $uhrzeit;
				if ( $ort )     $meta_parts[] = $ort;
				$meta = implode( ' · ', $meta_parts );

				$delay_class = $i === 0 ? '' : ' delay-' . $i;
				$i++;
			?>
				<article class="event-card reveal<?php echo esc_attr( $delay_class ); ?>" data-hover>
					<div class="event-card__media">
						<?php if ( $image_url ) : ?>
							<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy" />
						<?php endif; ?>
						<span class="event-card__badge event-card__badge--<?php echo esc_attr( $rubrik['mod'] ); ?>"><?php echo esc_html( $rubrik['label'] ); ?></span>
					</div>
					<div class="event-card__body">
						<div class="event-card__date">
							<span class="event-card__day"><?php echo esc_html( $day ); ?></span>
							<span class="event-card__month"><?php echo esc_html( $month_year ); ?></span>
						</div>
						<div class="event-card__main">
							<h3 class="event-card__title"><?php echo esc_html( $post->post_title ); ?></h3>
							<?php if ( $meta ) : ?>
								<p class="event-card__meta"><?php echo esc_html( $meta ); ?></p>
							<?php endif; ?>
							<?php if ( $desc ) : ?>
								<p class="event-card__desc"><?php echo esc_html( $desc ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
