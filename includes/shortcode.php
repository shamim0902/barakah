<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'barakah', 'barakah_render_shortcode' );

/**
 * Format HH:MM to 12-hour time.
 *
 * @param string $time Time string.
 * @return string
 */
function barakah_format_time_12( $time ) {
    $clean = trim( (string) $time );
    $clean = preg_replace( '/\s*\([^)]*\)\s*$/', '', $clean );
    if ( ! preg_match( '/^(\d{1,2}):(\d{2})$/', $clean, $m ) ) {
        return esc_html( $clean );
    }

    $h = (int) $m[1];
    $i = (int) $m[2];
    $suffix = ( $h >= 12 ) ? 'PM' : 'AM';
    $h12 = $h % 12;
    if ( 0 === $h12 ) {
        $h12 = 12;
    }

    return sprintf( '%d:%02d %s', $h12, $i, $suffix );
}

/**
 * Convert HH:MM to total minutes.
 *
 * @param string $time Time string.
 * @return int
 */
function barakah_time_to_minutes( $time ) {
    $clean = trim( (string) $time );
    $clean = preg_replace( '/\s*\([^)]*\)\s*$/', '', $clean );
    if ( ! preg_match( '/^(\d{1,2}):(\d{2})$/', $clean, $m ) ) {
        return -1;
    }
    return ( (int) $m[1] * 60 ) + (int) $m[2];
}

/**
 * Apply minute offset to HH:MM.
 *
 * @param string $time   Time string.
 * @param int    $offset Offset in minutes.
 * @return string
 */
function barakah_adjust_time( $time, $offset ) {
    $mins = barakah_time_to_minutes( $time );
    if ( $mins < 0 ) {
        return (string) $time;
    }

    $mins += (int) $offset;
    if ( $mins < 0 ) {
        $mins += 1440;
    }
    if ( $mins >= 1440 ) {
        $mins -= 1440;
    }

    return sprintf( '%02d:%02d', (int) floor( $mins / 60 ), (int) ( $mins % 60 ) );
}

/**
 * Apply caution settings to timings array.
 *
 * @param array $timings Timings.
 * @return array
 */
function barakah_apply_caution_timings_php( $timings ) {
    if ( ! is_array( $timings ) ) {
        return [];
    }

    $out = $timings;
    $sehri_caution = (int) get_option( 'barakah_sehri_caution_minutes', 0 );
    $iftar_caution = (int) get_option( 'barakah_iftar_caution_minutes', 0 );

    if ( isset( $out['Fajr'] ) && $sehri_caution > 0 ) {
        $out['Fajr'] = barakah_adjust_time( $out['Fajr'], -$sehri_caution );
    }
    if ( isset( $out['Maghrib'] ) && $iftar_caution > 0 ) {
        $out['Maghrib'] = barakah_adjust_time( $out['Maghrib'], $iftar_caution );
    }

    return $out;
}

/**
 * Render compact Prayer Times widget.
 */
function barakah_render_prayer_times_widget( $timings, $city, $country, $mode ) {
    $rows = [
        'Fajr'    => 'Fajr',
        'Sunrise' => 'Sunrise',
        'Dhuhr'   => 'Dhuhr',
        'Asr'     => 'Asr',
        'Maghrib' => 'Maghrib',
        'Isha'    => 'Isha',
    ];

    ob_start();
    ?>
    <div class="bk-mini bk-mini-prayer bk-mini-<?php echo esc_attr( $mode ); ?>">
        <div class="bk-mini-head">Prayer Times - <?php echo esc_html( $city . ', ' . $country ); ?></div>
        <table class="bk-mini-table" aria-label="Daily prayer times">
            <tbody>
            <?php foreach ( $rows as $key => $label ) : ?>
                <?php if ( ! isset( $timings[ $key ] ) ) { continue; } ?>
                <tr>
                    <th scope="row"><?php echo esc_html( $label ); ?></th>
                    <td><?php echo esc_html( barakah_format_time_12( $timings[ $key ] ) ); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render compact Ramadan widget.
 */
function barakah_render_ramadan_widget( $timings, $date, $city, $country, $mode ) {
    $sehri = isset( $timings['Fajr'] ) ? barakah_format_time_12( $timings['Fajr'] ) : '--';
    $iftar = isset( $timings['Maghrib'] ) ? barakah_format_time_12( $timings['Maghrib'] ) : '--';

    $hijri = isset( $date['hijri'] ) && is_array( $date['hijri'] ) ? $date['hijri'] : [];
    $greg  = isset( $date['gregorian'] ) && is_array( $date['gregorian'] ) ? $date['gregorian'] : [];

    $hijri_label = trim(
        ( isset( $hijri['day'] ) ? $hijri['day'] : '' ) . ' ' .
        ( isset( $hijri['month']['en'] ) ? $hijri['month']['en'] : '' ) . ' ' .
        ( isset( $hijri['year'] ) ? $hijri['year'] : '' ) . ' AH'
    );

    $greg_label = trim(
        ( isset( $greg['weekday']['en'] ) ? $greg['weekday']['en'] : '' ) . ', ' .
        ( isset( $greg['day'] ) ? $greg['day'] : '' ) . ' ' .
        ( isset( $greg['month']['en'] ) ? $greg['month']['en'] : '' ) . ' ' .
        ( isset( $greg['year'] ) ? $greg['year'] : '' )
    );

    ob_start();
    ?>
    <div class="bk-mini bk-mini-ramadan bk-mini-<?php echo esc_attr( $mode ); ?>">
        <div class="bk-mini-head">Ramadan Essentials - <?php echo esc_html( $city . ', ' . $country ); ?></div>
        <?php if ( ! empty( $hijri_label ) ) : ?>
            <div class="bk-mini-sub"><?php echo esc_html( $hijri_label ); ?></div>
        <?php endif; ?>
        <?php if ( ! empty( $greg_label ) ) : ?>
            <div class="bk-mini-meta"><?php echo esc_html( $greg_label ); ?></div>
        <?php endif; ?>
        <div class="bk-mini-cards">
            <div class="bk-mini-card">
                <div class="bk-mini-label">Sehri</div>
                <div class="bk-mini-time"><?php echo esc_html( $sehri ); ?></div>
            </div>
            <div class="bk-mini-card">
                <div class="bk-mini-label">Iftar</div>
                <div class="bk-mini-time"><?php echo esc_html( $iftar ); ?></div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render compact random hadith widget.
 */
function barakah_render_hadith_widget( $api, $mode ) {
    $hadith = $api->get_random_hadith();

    ob_start();
    ?>
    <div class="bk-mini bk-mini-hadith bk-mini-<?php echo esc_attr( $mode ); ?>">
        <div class="bk-mini-head">Random Hadith</div>
        <blockquote class="bk-mini-quote"><?php echo esc_html( $hadith['text'] ?? '' ); ?></blockquote>
        <div class="bk-mini-meta">
            <?php if ( ! empty( $hadith['narrator'] ) ) : ?>
                <span><?php echo esc_html( $hadith['narrator'] ); ?></span>
            <?php endif; ?>
            <?php if ( ! empty( $hadith['source'] ) ) : ?>
                <span><?php echo esc_html( $hadith['source'] ); ?></span>
            <?php endif; ?>
            <?php if ( ! empty( $hadith['reference'] ) ) : ?>
                <span><?php echo esc_html( $hadith['reference'] ); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render compact random dua widget.
 */
function barakah_render_dua_widget( $api, $mode ) {
    $duas = $api->get_bangla_duas();
    $dua  = [];

    if ( is_array( $duas ) && ! empty( $duas ) ) {
        $dua = $duas[ array_rand( $duas ) ];
    }

    $arabic = isset( $dua['arabic'] ) ? $dua['arabic'] : 'اللَّهُمَّ بَارِكْ لَنَا فِي رَمَضَانَ';
    $pron   = isset( $dua['bangla_pronunciation'] ) ? $dua['bangla_pronunciation'] : '';
    $mean   = isset( $dua['bangla_meaning'] ) ? $dua['bangla_meaning'] : 'O Allah, bless us in Ramadan.';

    ob_start();
    ?>
    <div class="bk-mini bk-mini-dua bk-mini-<?php echo esc_attr( $mode ); ?>">
        <div class="bk-mini-head">Daily Dua</div>
        <div class="bk-mini-arabic"><?php echo esc_html( $arabic ); ?></div>
        <?php if ( ! empty( $pron ) ) : ?>
            <div class="bk-mini-sub"><?php echo esc_html( $pron ); ?></div>
        <?php endif; ?>
        <div class="bk-mini-meta"><?php echo esc_html( $mean ); ?></div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render compact Islamic date + next prayer widget.
 */
function barakah_render_date_widget( $timings, $date, $city, $country, $mode ) {
    $hijri = isset( $date['hijri'] ) && is_array( $date['hijri'] ) ? $date['hijri'] : [];
    $greg  = isset( $date['gregorian'] ) && is_array( $date['gregorian'] ) ? $date['gregorian'] : [];

    $hijri_label = trim(
        ( isset( $hijri['day'] ) ? $hijri['day'] : '' ) . ' ' .
        ( isset( $hijri['month']['en'] ) ? $hijri['month']['en'] : '' ) . ' ' .
        ( isset( $hijri['year'] ) ? $hijri['year'] : '' ) . ' AH'
    );

    $greg_label = trim(
        ( isset( $greg['weekday']['en'] ) ? $greg['weekday']['en'] : '' ) . ', ' .
        ( isset( $greg['day'] ) ? $greg['day'] : '' ) . ' ' .
        ( isset( $greg['month']['en'] ) ? $greg['month']['en'] : '' ) . ' ' .
        ( isset( $greg['year'] ) ? $greg['year'] : '' )
    );

    $prayers = [
        'Fajr'    => 'Fajr',
        'Dhuhr'   => 'Dhuhr',
        'Asr'     => 'Asr',
        'Maghrib' => 'Maghrib',
        'Isha'    => 'Isha',
    ];

    $now = (int) current_time( 'timestamp' );
    $now_mins = (int) gmdate( 'G', $now ) * 60 + (int) gmdate( 'i', $now );

    $next_name = 'Fajr';
    $next_time = isset( $timings['Fajr'] ) ? $timings['Fajr'] : '--';

    foreach ( $prayers as $key => $label ) {
        if ( ! isset( $timings[ $key ] ) ) {
            continue;
        }
        $mins = barakah_time_to_minutes( $timings[ $key ] );
        if ( $mins >= 0 && $mins > $now_mins ) {
            $next_name = $label;
            $next_time = $timings[ $key ];
            break;
        }
    }

    ob_start();
    ?>
    <div class="bk-mini bk-mini-date bk-mini-<?php echo esc_attr( $mode ); ?>">
        <div class="bk-mini-head">Islamic Date & Next Prayer</div>
        <div class="bk-mini-sub"><?php echo esc_html( $city . ', ' . $country ); ?></div>
        <?php if ( ! empty( $hijri_label ) ) : ?>
            <div class="bk-mini-arabic"><?php echo esc_html( $hijri_label ); ?></div>
        <?php endif; ?>
        <?php if ( ! empty( $greg_label ) ) : ?>
            <div class="bk-mini-meta"><?php echo esc_html( $greg_label ); ?></div>
        <?php endif; ?>
        <div class="bk-mini-next">
            Next: <strong><?php echo esc_html( $next_name ); ?></strong> - <?php echo esc_html( barakah_format_time_12( $next_time ) ); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function barakah_render_shortcode( $atts ) {
    $atts = shortcode_atts(
        [
            'city'    => get_option( 'barakah_city', 'Dhaka' ),
            'country' => get_option( 'barakah_country', 'Bangladesh' ),
            'method'  => get_option( 'barakah_method', '1' ),
            'mode'    => 'dark',
            'widget'  => 'full',
        ],
        $atts,
        'barakah'
    );

    $atts['city']    = sanitize_text_field( $atts['city'] );
    $atts['country'] = sanitize_text_field( $atts['country'] );
    $atts['method']  = sanitize_text_field( $atts['method'] );
    $atts['mode']    = ( 'light' === strtolower( (string) $atts['mode'] ) ) ? 'light' : 'dark';
    $atts['widget']  = sanitize_key( $atts['widget'] );

    $allowed_widgets = [ 'full', 'prayer_times', 'ramadan', 'hadith', 'dua', 'date' ];
    if ( ! in_array( $atts['widget'], $allowed_widgets, true ) ) {
        $atts['widget'] = 'full';
    }

    $api  = Barakah_API::get_instance();
    $data = $api->get_prayer_times(
        $atts['city'],
        $atts['country'],
        '',
        (int) $atts['method']
    );

    $timings = isset( $data['timings'] ) && is_array( $data['timings'] )
        ? barakah_apply_caution_timings_php( $data['timings'] )
        : [];
    $date = isset( $data['date'] ) && is_array( $data['date'] ) ? $data['date'] : [];

    if ( 'prayer_times' === $atts['widget'] ) {
        return barakah_render_prayer_times_widget( $timings, $atts['city'], $atts['country'], $atts['mode'] );
    }

    if ( 'ramadan' === $atts['widget'] ) {
        return barakah_render_ramadan_widget( $timings, $date, $atts['city'], $atts['country'], $atts['mode'] );
    }

    if ( 'hadith' === $atts['widget'] ) {
        return barakah_render_hadith_widget( $api, $atts['mode'] );
    }

    if ( 'dua' === $atts['widget'] ) {
        return barakah_render_dua_widget( $api, $atts['mode'] );
    }

    if ( 'date' === $atts['widget'] ) {
        return barakah_render_date_widget( $timings, $date, $atts['city'], $atts['country'], $atts['mode'] );
    }

    $has_server_data = ! empty( $data['timings'] ) && empty( $data['error'] );
    $two_col         = get_option( 'barakah_two_column', '0' );

    $bangla_duas = $api->get_bangla_duas();

    wp_localize_script( 'barakah-script', 'barakahData', [
        'timings'              => isset( $data['timings'] ) ? $data['timings'] : [],
        'date'                 => isset( $data['date'] ) ? $data['date'] : [],
        'banglaDuas'           => $bangla_duas,
        'hasData'              => $has_server_data,
        'headerGreeting'       => get_option( 'barakah_header_greeting', '' ),
        'greeting'             => get_option( 'barakah_greeting', '' ),
        'allowLocationChange'  => get_option( 'barakah_allow_location_change', '0' ),
        'hijriAdjustDirection' => get_option( 'barakah_hijri_adjust_direction', 'none' ),
        'hijriAdjustDays'      => (int) get_option( 'barakah_hijri_adjust_days', 0 ),
        'sehriCautionMinutes'  => (int) get_option( 'barakah_sehri_caution_minutes', 0 ),
        'iftarCautionMinutes'  => (int) get_option( 'barakah_iftar_caution_minutes', 0 ),
    ] );

    ob_start();
    ?>
    <div
        id="barakah-widget"
        data-city="<?php echo esc_attr( $atts['city'] ); ?>"
        data-country="<?php echo esc_attr( $atts['country'] ); ?>"
        data-method="<?php echo esc_attr( $atts['method'] ); ?>"
        data-mode="<?php echo esc_attr( $atts['mode'] ); ?>"
        data-columns="<?php echo esc_attr( $two_col ); ?>"
    >
        <?php if ( ! $has_server_data ) : ?>
        <div class="bk-loading">
            <div class="bk-loading-moon">🌙</div>
            <div class="bk-loading-spinner"></div>
            <p>Loading prayer times for <?php echo esc_html( $atts['city'] ); ?>…</p>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
