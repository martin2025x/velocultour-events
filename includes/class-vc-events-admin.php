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
		$uhrzeit = get_post_meta( $post->ID, '_vc_event_uhrzeit', true );
		$ort     = get_post_meta( $post->ID, '_vc_event_ort', true );
		$desc    = get_post_meta( $post->ID, '_vc_event_desc', true );
		$rubriken = vc_events_rubriken();
		?>
		<style>
			.vc-evt-grid{display:grid;grid-template-columns:140px 1fr;gap:14px 18px;max-width:740px}
			.vc-evt-grid label{font-weight:600;align-self:center;color:#444}
			.vc-evt-grid input[type=text],
			.vc-evt-grid input[type=date],
			.vc-evt-grid select,
			.vc-evt-grid textarea{width:100%;padding:8px 10px;border:1px solid #c3c4c7;border-radius:4px;font-size:14px}
			.vc-evt-grid .hint{font-size:12px;color:#666;margin-top:4px}
			.vc-evt-grid textarea{font-family:inherit;line-height:1.45}
			.vc-evt-preview{margin-top:18px;padding:14px;background:#fafaf7;border-left:3px solid #E77C05;font-size:13px;line-height:1.5}
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

			<label for="vc_evt_datum">Datum *</label>
			<div>
				<input type="date" name="vc_event_datum" id="vc_evt_datum" value="<?php echo esc_attr( $datum ); ?>">
				<div class="hint">Vergangene Termine erscheinen nicht mehr auf der Startseite.</div>
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

			<label for="vc_evt_desc">Kurzbeschreibung</label>
			<div>
				<textarea name="vc_event_desc" id="vc_evt_desc" rows="3" placeholder="2–3 Sätze, was Teilnehmer erwartet."><?php echo esc_textarea( $desc ); ?></textarea>
				<div class="hint">Optimal 150–200 Zeichen. Wird unter der Uhrzeit angezeigt.</div>
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

		// Datum: YYYY-MM-DD
		$datum = sanitize_text_field( $_POST['vc_event_datum'] ?? '' );
		if ( $datum && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $datum ) ) $datum = '';
		update_post_meta( $post_id, '_vc_event_datum', $datum );

		update_post_meta( $post_id, '_vc_event_uhrzeit', sanitize_text_field( $_POST['vc_event_uhrzeit'] ?? '' ) );
		update_post_meta( $post_id, '_vc_event_ort',     sanitize_text_field( $_POST['vc_event_ort'] ?? '' ) );
		update_post_meta( $post_id, '_vc_event_desc',    sanitize_textarea_field( $_POST['vc_event_desc'] ?? '' ) );
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
			$d = get_post_meta( $post_id, '_vc_event_datum', true );
			$u = get_post_meta( $post_id, '_vc_event_uhrzeit', true );
			if ( $d ) {
				$ts = strtotime( $d );
				$today = strtotime( current_time( 'Y-m-d' ) );
				$past = $ts < $today;
				echo '<strong' . ( $past ? ' style="color:#999;text-decoration:line-through;"' : '' ) . '>' . esc_html( wp_date( 'D, d.m.Y', $ts ) ) . '</strong>';
				if ( $u ) echo '<br><small>' . esc_html( $u ) . '</small>';
				if ( $past ) echo '<br><small style="color:#B91C1C;">vergangen</small>';
			} else {
				echo '<em style="color:#B91C1C;">kein Datum</em>';
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
