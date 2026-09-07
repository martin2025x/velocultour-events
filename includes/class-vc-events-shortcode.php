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
				$anmeldung   = get_post_meta( $post->ID, '_vc_event_anmeldung', true );
				$thumb_id    = get_post_thumbnail_id( $post->ID );
				$image_url   = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '';
				$alt         = $thumb_id ? get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ) : '';
				if ( ! $alt ) $alt = $post->post_title;

				// v1.3.0: bis zu vier Termine. Gross steht der naechste bevorstehende,
				// die uebrigen kommen als Zeile darunter.
				$termine   = VC_Events_CPT::get_termine( $post->ID );
				$naechster = VC_Events_CPT::get_naechster_termin( $post->ID );
				if ( ! $naechster && $datum ) {
					$naechster = $datum;   // Altbestand ohne Mehrfachtermine
				}

				$ts  = $naechster ? strtotime( $naechster ) : 0;
				$day = $ts ? wp_date( 'd', $ts ) : '—';
				// Monat nach deutscher Rechtschreibung abgekuerzt und Jahr getrennt:
				// "SEPTEMBER 26" sprengte die schmale Datumsspalte.
				$month = $ts ? VC_Events_CPT::monat_kurz( $ts ) : '';
				$year  = $ts ? wp_date( 'Y', $ts ) : '';

				// Weitere Termine (ohne den bereits gross angezeigten)
				$weitere = array();
				foreach ( $termine as $t ) {
					if ( $t !== $naechster ) {
						$weitere[] = wp_date( 'd.m.', strtotime( $t ) );
					}
				}

				// Beschriftung des Buttons nach Art des Ziels
				$anmeldung_text = 'Zur Anmeldung';
				if ( $anmeldung ) {
					$schema = strtolower( (string) wp_parse_url( $anmeldung, PHP_URL_SCHEME ) );
					if ( 'mailto' === $schema ) {
						$anmeldung_text = 'Per E-Mail anmelden';
					} elseif ( 'tel' === $schema ) {
						$anmeldung_text = 'Telefonisch anmelden';
					}
				}

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
							<span class="event-card__month"><?php echo esc_html( $month ); ?></span>
							<span class="event-card__year"><?php echo esc_html( $year ); ?></span>
						</div>
						<div class="event-card__main">
							<h3 class="event-card__title"><?php echo esc_html( $post->post_title ); ?></h3>
							<?php if ( $meta ) : ?>
								<p class="event-card__meta"><?php echo esc_html( $meta ); ?></p>
							<?php endif; ?>
							<?php if ( $desc ) : ?>
								<div class="event-card__desc"><?php echo wp_kses_post( wpautop( $desc ) ); ?></div>
							<?php endif; ?>
							<?php if ( $weitere ) : ?>
								<p class="event-card__weitere">
									<span>Weitere Termine</span>
									<?php echo esc_html( implode( ' · ', $weitere ) ); ?>
								</p>
							<?php endif; ?>
							<?php if ( $anmeldung ) : ?>
								<a class="event-card__anmeldung" href="<?php echo esc_url( $anmeldung ); ?>"
									<?php // mailto und tel nicht in einem neuen Tab oeffnen — das laesst
									// sonst eine leere Seite zurueck.
									$extern = 0 === strpos( $anmeldung, 'http' );
									echo $extern ? ' target="_blank" rel="noopener"' : ''; ?>
								   data-hover>
									<?php echo esc_html( $anmeldung_text ); ?> <span aria-hidden="true">&rarr;</span>
								</a>
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
