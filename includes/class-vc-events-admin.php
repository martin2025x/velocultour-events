<?php
/**
 * Admin: Metabox + Custom-Spalten für vc_event.
 *
 * @package velocultour-events
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class VC_Events_Admin {

	const PT = VC_Events_CPT::POST_TYPE;

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_metabox' ) );
		add_action( 'save_post_' . self::PT, array( __CLASS__, 'save_meta' ), 10, 2 );

		add_filter( 'manage_' . self::PT . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::PT . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_filter( 'manage_edit-' . self::PT . '_sortable_columns', array( __CLASS__, 'sortable' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'orderby_datum' ) );
		add_action( 'admin_head', array( __CLASS__, 'admin_css' ) );
	}

	public static function add_metabox() {
		add_meta_box(
			'vc_event_details',
			'Veranstaltungs-Details',
			array( __CLASS__, 'render_metabox' ),
			self::PT,
			'normal',
			'high'
		);
	}

	public static function render_metabox( $post ) {
		wp_nonce_field( 'vc_event_meta', 'vc_event_meta_nonce' );
		$rubrik  = get_post_meta( $post->ID, '_vc_event_rubrik', true );
		$datum   = get_post_meta( $post->ID, '_vc_event_datum', true );
		$datum2  = get_post_meta( $post->ID, '_vc_event_datum_2', true );
		$datum3  = get_post_meta( $post->ID, '_vc_event_datum_3', true );
		$datum4  = get_post_meta( $post->ID, '_vc_event_datum_4', true );
		$uhrzeit = get_post_meta( $post->ID, '_vc_event_uhrzeit', true );
		$ort     = get_post_meta( $post->ID, '_vc_event_ort', true );
		$desc    = get_post_meta( $post->ID, '_vc_event_desc', true );
		$anmeldung = get_post_meta( $post->ID, '_vc_event_anmeldung', true );
		$rubriken = vc_events_rubriken();
		?>
		<style>
			.vc-evt-grid{display:grid;grid-template-columns:140px 1fr;gap:14px 18px;max-width:740px}
			.vc-evt-grid label{font-weight:600;align-self:center;color:#444}
			.vc-evt-grid input[type=text],
			.vc-evt-grid input[type=date],
			.vc-evt-grid select,
			.vc-evt-grid textarea:not(.wp-editor-area){width:100%;padding:8px 10px;border:1px solid #c3c4c7;border-radius:4px;font-size:14px}
			.vc-evt-grid .hint{font-size:12px;color:#666;margin-top:4px}
			.vc-evt-grid textarea:not(.wp-editor-area){font-family:inherit;line-height:1.45}
			.vc-evt-grid .vc-evt-editor .wp-editor-container{border:1px solid #c3c4c7;border-radius:4px;overflow:hidden}
			.vc-evt-grid label[for=vceventdesc]{align-self:start;padding-top:8px}
			.vc-evt-preview{margin-top:18px;padding:14px;background:#fafaf7;border-left:3px solid #E77C05;font-size:13px;line-height:1.5}
			.vc-evt-termine{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px 14px}
			.vc-evt-termin{display:flex;flex-direction:column;gap:4px;font-weight:400}
			.vc-evt-termin span{font-size:11px;color:#666;letter-spacing:.03em;text-transform:uppercase}
			.vc-evt-termin input{width:100%}
		</style>
		<div class="vc-evt-grid">
			<label for="vc_evt_rubrik">Rubrik *</label>
			<div>
				<select name="vc_event_rubrik" id="vc_evt_rubrik">
					<?php foreach ( $rubriken as $slug => $r ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $rubrik, $slug ); ?>><?php echo esc_html( $r['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<div class="hint">Bestimmt die Badge-Farbe oben links auf der Card.</div>
			</div>

			<label for="vc_evt_datum">Termine *</label>
			<div>
				<div class="vc-evt-termine">
					<label class="vc-evt-termin">
						<span>1. Termin</span>
						<input type="date" name="vc_event_datum" id="vc_evt_datum" value="<?php echo esc_attr( $datum ); ?>">
					</label>
					<label class="vc-evt-termin">
						<span>2. Termin</span>
						<input type="date" name="vc_event_datum_2" value="<?php echo esc_attr( $datum2 ); ?>">
					</label>
					<label class="vc-evt-termin">
						<span>3. Termin</span>
						<input type="date" name="vc_event_datum_3" value="<?php echo esc_attr( $datum3 ); ?>">
					</label>
					<label class="vc-evt-termin">
						<span>4. Termin</span>
						<input type="date" name="vc_event_datum_4" value="<?php echo esc_attr( $datum4 ); ?>">
					</label>
				</div>
				<div class="hint">
					Der erste Termin ist Pflicht, die übrigen optional — für Veranstaltungen,
					die mehrfach stattfinden. Auf der Card erscheint groß der <strong>nächste
					noch bevorstehende</strong> Termin, die weiteren darunter.<br>
					Die Veranstaltung verschwindet erst von der Startseite, wenn <em>alle</em>
					Termine vorbei sind.
				</div>
			</div>

			<label for="vc_evt_uhrzeit">Uhrzeit</label>
			<div>
				<input type="text" name="vc_event_uhrzeit" id="vc_evt_uhrzeit" value="<?php echo esc_attr( $uhrzeit ); ?>" placeholder="z. B. 19:00 Uhr  oder  10–15 Uhr">
				<div class="hint">Freitext. Beispiele: <code>19:00 Uhr</code> · <code>10–15 Uhr</code> · <code>ganztägig</code></div>
			</div>

			<label for="vc_evt_ort">Ort</label>
			<div>
				<input type="text" name="vc_event_ort" id="vc_evt_ort" value="<?php echo esc_attr( $ort ); ?>" placeholder="z. B. Showroom Neuhof, Trailpark Vogelsberg">
			</div>

			<label for="vc_evt_anmeldung">Anmeldung (URL)</label>
			<div>
				<input type="url" name="vc_event_anmeldung" id="vc_evt_anmeldung" value="<?php echo esc_attr( $anmeldung ); ?>" placeholder="https://www.reisewelt-neuhof.de/…">
				<div class="hint">
					Ist das Feld gefüllt, erscheint auf der Card ein Button. Leer lassen bei
					Veranstaltungen ohne Anmeldung.<br><br>
					<strong>Drei Möglichkeiten:</strong><br>
					1. <strong>Webadresse</strong> — z. B. die reisewelt-Veranstaltungsseite.
					Hängt man die ID des Formulars an
					(<code>…/fahrsicherheitstraining-rhoen/#vfbp-form-58</code>), landen Besucher
					direkt beim Formular. Button heißt dann „Zur Anmeldung“.<br>
					2. <strong>E-Mail</strong> — <code>mailto:info@velocultour.de</code>, gern mit
					Betreff: <code>mailto:info@velocultour.de?subject=Anmeldung%20Fahrtechniktraining</code>.
					Button heißt dann „Per E-Mail anmelden“.<br>
					3. <strong>Telefon</strong> — <code>tel:+4966559999080</code>.
					Button heißt dann „Telefonisch anmelden“.
				</div>
			</div>

			<label for="vceventdesc">Infotext</label>
			<div class="vc-evt-editor">
				<?php
				wp_editor(
					$desc,
					'vceventdesc',
					array(
						'textarea_name' => 'vc_event_desc',
						'textarea_rows' => 10,
						'media_buttons' => false,
						'teeny'         => true,   // schlanke Leiste: Fett, Kursiv, Listen, Link
						'quicktags'     => true,
						'tinymce'       => array(
							'toolbar1' => 'bold,italic,bullist,numlist,link,unlink,undo,redo',
							'toolbar2' => '',
						),
					)
				);
				?>
				<div class="hint">
					Listen über die Aufzählungs-Buttons in der Leiste anlegen &mdash; nicht mit
					&bdquo;-&ldquo; am Zeilenanfang. Kurz halten: Der Text steht auf der
					Startseiten-Card unter der Uhrzeit, sehr lange Texte machen die Card hoch.
				</div>
			</div>

			<label>Bild</label>
			<div class="hint" style="margin-top:0">Rechts in der Box <em>„Beitragsbild"</em> festlegen. Querformat, mindestens 800×600 px.</div>
		</div>

		<div class="vc-evt-preview">
			<strong>So erscheint die Card auf der Startseite:</strong><br>
			Badge: <em><?php echo esc_html( $rubriken[ $rubrik ]['label'] ?? '— bitte wählen —' ); ?></em><br>
			Datum: <strong><?php echo $datum ? esc_html( wp_date( 'd. F Y', strtotime( $datum ) ) ) : '— bitte setzen —'; ?></strong>
			<?php if ( $uhrzeit ) echo ' · ' . esc_html( $uhrzeit ); ?>
			<?php if ( $ort ) echo ' · ' . esc_html( $ort ); ?>
		</div>
		<?php
	}

	public static function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST['vc_event_meta_nonce'] ) ) return;
		if ( ! wp_verify_nonce( $_POST['vc_event_meta_nonce'], 'vc_event_meta' ) ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		$rubrik = sanitize_text_field( $_POST['vc_event_rubrik'] ?? '' );
		$rubriken = vc_events_rubriken();
		if ( ! isset( $rubriken[ $rubrik ] ) ) $rubrik = 'event';
		update_post_meta( $post_id, '_vc_event_rubrik', $rubrik );

		// Termine: bis zu vier, jeweils YYYY-MM-DD.
		$termine = array();
		foreach ( array( 'vc_event_datum', 'vc_event_datum_2', 'vc_event_datum_3', 'vc_event_datum_4' ) as $i => $feld ) {
			$d = sanitize_text_field( wp_unslash( $_POST[ $feld ] ?? '' ) );
			if ( $d && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) ) {
				$d = '';
			}
			$schluessel = 0 === $i ? '_vc_event_datum' : '_vc_event_datum_' . ( $i + 1 );
			update_post_meta( $post_id, $schluessel, $d );
			if ( $d ) {
				$termine[] = $d;
			}
		}

		// Spaetester Termin. Danach richtet sich, wie lange die Veranstaltung
		// auf der Startseite bleibt — sonst verschwaende sie schon nach dem
		// ersten von vier Terminen.
		update_post_meta( $post_id, '_vc_event_datum_ende', $termine ? max( $termine ) : '' );

		update_post_meta( $post_id, '_vc_event_uhrzeit', sanitize_text_field( $_POST['vc_event_uhrzeit'] ?? '' ) );
		update_post_meta( $post_id, '_vc_event_ort',     sanitize_text_field( $_POST['vc_event_ort'] ?? '' ) );
		// Infotext darf Formatierung enthalten (Listen, fett, Links) -> wp_kses_post
		update_post_meta( $post_id, '_vc_event_desc', wp_kses_post( wp_unslash( $_POST['vc_event_desc'] ?? '' ) ) );

		// Anmelde-Ziel. Erlaubt sind Webadressen, mailto: und tel: — esc_url_raw
		// laesst nur die von WordPress freigegebenen Protokolle durch, javascript:
		// faellt also raus.
		$anmeldung = trim( (string) wp_unslash( $_POST['vc_event_anmeldung'] ?? '' ) );

		// Wer nur "info@velocultour.de" eintraegt, meint eine Mailadresse. Das
		// Browserfeld lehnt das zwar ab, aber falls es doch durchkommt (Autofill,
		// Kopieren), ergaenzen wir das Schema still.
		if ( $anmeldung && false === strpos( $anmeldung, ':' ) && is_email( $anmeldung ) ) {
			$anmeldung = 'mailto:' . $anmeldung;
		}

		update_post_meta( $post_id, '_vc_event_anmeldung', esc_url_raw( $anmeldung ) );
	}

	// ============================================================
	// Custom-Spalten in der Übersicht
	// ============================================================
	public static function columns( $cols ) {
		$new = array();
		if ( isset( $cols['cb'] ) ) $new['cb'] = $cols['cb'];
		$new['vc_evt_img']    = 'Bild';
		$new['title']         = 'Titel';
		$new['vc_evt_rubrik'] = 'Rubrik';
		$new['vc_evt_datum']  = 'Datum';
		$new['vc_evt_ort']    = 'Ort';
		$new['date']          = $cols['date'] ?? 'Veröffentlicht';
		return $new;
	}

	public static function column_content( $col, $post_id ) {
		if ( 'vc_evt_img' === $col ) {
			$thumb = get_post_thumbnail_id( $post_id );
			if ( $thumb ) {
				echo '<a href="' . esc_url( get_edit_post_link( $post_id ) ) . '">';
				echo wp_get_attachment_image( $thumb, array( 90, 60 ), false, array(
					'style' => 'object-fit:cover;width:90px;height:60px;display:block;border-radius:3px;',
				) );
				echo '</a>';
			} else {
				echo '<div style="width:90px;height:60px;background:#f0f0f0;border:1px dashed #ccc;display:flex;align-items:center;justify-content:center;color:#999;font-size:10px;border-radius:3px;">kein Bild</div>';
			}
		}
		if ( 'vc_evt_rubrik' === $col ) {
			$rubrik = get_post_meta( $post_id, '_vc_event_rubrik', true );
			$rubriken = vc_events_rubriken();
			if ( isset( $rubriken[ $rubrik ] ) ) {
				$colors = array(
					'reise'    => '#E77C05',
					'training' => '#1a4d2e',
					'vortrag'  => '#0A0A0B',
					'workshop' => '#1a4d2e',
					'event'    => '#E77C05',
				);
				$bg = $colors[ $rubrik ] ?? '#525252';
				echo '<span style="display:inline-block;padding:3px 9px;background:' . esc_attr( $bg ) . ';color:#fff;font-size:11px;letter-spacing:.05em;text-transform:uppercase;font-weight:600;border-radius:2px;">' . esc_html( $rubriken[ $rubrik ]['label'] ) . '</span>';
			} else {
				echo '—';
			}
		}
		if ( 'vc_evt_datum' === $col ) {
			$termine = VC_Events_CPT::get_termine( $post_id );
			$u       = get_post_meta( $post_id, '_vc_event_uhrzeit', true );

			if ( ! $termine ) {
				echo '<em style="color:#B91C1C;">kein Datum</em>';
				return;
			}

			$heute    = strtotime( current_time( 'Y-m-d' ) );
			$offen    = 0;
			$ausgaben = array();

			foreach ( $termine as $t ) {
				$ts       = strtotime( $t );
				$vergangen = $ts < $heute;
				if ( ! $vergangen ) {
					$offen++;
				}
				$ausgaben[] = sprintf(
					'<span style="%s">%s</span>',
					$vergangen ? 'color:#999;text-decoration:line-through;' : 'font-weight:600;',
					esc_html( wp_date( 'D, d.m.Y', $ts ) )
				);
			}

			echo implode( '<br>', $ausgaben ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			if ( $u ) {
				echo '<br><small>' . esc_html( $u ) . '</small>';
			}
			if ( 0 === $offen ) {
				echo '<br><small style="color:#B91C1C;">alle Termine vergangen</small>';
			} elseif ( count( $termine ) > 1 ) {
				echo '<br><small style="color:#666;">' . (int) $offen . ' von ' . count( $termine ) . ' noch offen</small>';
			}
		}
		if ( 'vc_evt_ort' === $col ) {
			echo esc_html( get_post_meta( $post_id, '_vc_event_ort', true ) ?: '—' );
		}
	}

	public static function sortable( $cols ) {
		$cols['vc_evt_datum'] = 'vc_evt_datum';
		return $cols;
	}

	public static function orderby_datum( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) return;
		if ( $query->get( 'post_type' ) !== self::PT ) return;
		if ( $query->get( 'orderby' ) === 'vc_evt_datum' ) {
			$query->set( 'meta_key', '_vc_event_datum' );
			$query->set( 'orderby', 'meta_value' );
		} else if ( ! $query->get( 'orderby' ) ) {
			// Default: nach Datum sortieren
			$query->set( 'meta_key', '_vc_event_datum' );
			$query->set( 'orderby', 'meta_value' );
			$query->set( 'order', 'ASC' );
		}
	}

	public static function admin_css() {
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== self::PT ) return;
		?>
		<style>
			.wp-list-table .column-vc_evt_img    { width: 110px; }
			.wp-list-table .column-vc_evt_rubrik { width: 200px; }
			.wp-list-table .column-vc_evt_datum  { width: 140px; }
			.wp-list-table tbody tr td.column-vc_evt_img { padding: 8px; }
		</style>
		<?php
	}
}
