<?php
/**
 * Astra Child Theme Functions - Cinema Booking System with Multi-Screen Support
 * Version: 2.0 - Multi-Screen Enhanced
 */

// Enqueue parent and child theme styles
add_action('wp_enqueue_scripts', 'astra_child_style');
function astra_child_style() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style'));
}

/**
 * Register Custom Post Types
 */
function cinema_register_post_types() {
    // Movies Custom Post Type
    register_post_type('movies', array(
        'labels' => array(
            'name' => 'Movies',
            'singular_name' => 'Movie',
            'add_new' => 'Add New Movie',
            'add_new_item' => 'Add New Movie',
            'edit_item' => 'Edit Movie',
            'new_item' => 'New Movie',
            'view_item' => 'View Movie',
            'search_items' => 'Search Movies',
            'not_found' => 'No movies found',
            'not_found_in_trash' => 'No movies found in trash'
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'menu_icon' => 'dashicons-video-alt',
        'rewrite' => array('slug' => 'movies')
    ));

    // Showtimes Custom Post Type
    register_post_type('showtimes', array(
        'labels' => array(
            'name' => 'Showtimes',
            'singular_name' => 'Showtime',
        ),
        'public' => true,
        'supports' => array('title', 'custom-fields'),
        'menu_icon' => 'dashicons-clock'
    ));
}
add_action('init', 'cinema_register_post_types');

require_once 'events/events-functions.php'; // Event Booking System

/**
 * ========================================================================
 * MULTI-SCREEN DATABASE TABLES - ENHANCED VERSION
 * ========================================================================
 */
function cinema_create_enhanced_tables() {
    global $wpdb;
    
    $bookings_table = $wpdb->prefix . 'cinema_bookings';
    $seats_table = $wpdb->prefix . 'cinema_seats';
    $seat_locks_table = $wpdb->prefix . 'cinema_seat_locks';
    $seat_layouts_table = $wpdb->prefix . 'cinema_seat_layouts';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Enhanced bookings table WITH SCREEN NUMBER
    $sql_bookings = "CREATE TABLE $bookings_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        booking_reference varchar(20) NOT NULL UNIQUE,
        showtime_id mediumint(9) NOT NULL,
        screen_number int NOT NULL DEFAULT 1,
        user_id mediumint(9) NOT NULL,
        customer_email varchar(100) NOT NULL,
        customer_name varchar(100) NOT NULL,
        customer_phone varchar(20),
        booking_date datetime DEFAULT CURRENT_TIMESTAMP,
        show_date date NOT NULL,
        show_time time NOT NULL,
        total_amount decimal(10,2) NOT NULL,
        booking_fee decimal(10,2) DEFAULT 0,
        discount_amount decimal(10,2) DEFAULT 0,
        booking_status varchar(20) DEFAULT 'pending',
        payment_status varchar(20) DEFAULT 'pending',
        payment_method varchar(50),
        stripe_payment_intent varchar(100),
        stripe_charge_id varchar(100),
        cancellation_reason text,
        cancelled_at datetime,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY showtime_id (showtime_id),
        KEY screen_number (screen_number),
        KEY user_id (user_id),
        KEY booking_reference (booking_reference),
        KEY booking_status (booking_status),
        KEY payment_status (payment_status)
    ) $charset_collate;";
    
    // Enhanced seats table WITH SCREEN NUMBER
    $sql_seats = "CREATE TABLE $seats_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        booking_id mediumint(9) NOT NULL,
        screen_number int NOT NULL DEFAULT 1,
        seat_number varchar(10) NOT NULL,
        seat_row varchar(5) NOT NULL,
        seat_column varchar(5) NOT NULL,
        seat_type varchar(20) DEFAULT 'regular',
        ticket_type varchar(20) DEFAULT 'adult',
        price decimal(10,2) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY booking_id (booking_id),
        KEY screen_number (screen_number),
        KEY seat_number (seat_number)
    ) $charset_collate;";
    
    // Seat locks table WITH SCREEN NUMBER
    $sql_locks = "CREATE TABLE $seat_locks_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        showtime_id mediumint(9) NOT NULL,
        screen_number int NOT NULL DEFAULT 1,
        seat_number varchar(10) NOT NULL,
        session_id varchar(100) NOT NULL,
        locked_at datetime DEFAULT CURRENT_TIMESTAMP,
        expires_at datetime NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY showtime_seat (showtime_id, screen_number, seat_number),
        KEY session_id (session_id),
        KEY expires_at (expires_at)
    ) $charset_collate;";
    
    // Seat layouts table for all 4 screens
    $sql_layouts = "CREATE TABLE $seat_layouts_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        screen_number int NOT NULL,
        layout_name varchar(100) NOT NULL,
        seat_configuration text NOT NULL,
        total_seats int NOT NULL,
        wheelchair_seats text,
        is_active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY screen_number (screen_number)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_bookings);
    dbDelta($sql_seats);
    dbDelta($sql_locks);
    dbDelta($sql_layouts);

    // Movie interactions table (ratings, watchlist, favorites)
    $interactions_table = $wpdb->prefix . 'cinema_movie_interactions';
    $sql_interactions = "CREATE TABLE $interactions_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        movie_id bigint(20) NOT NULL,
        interaction_type varchar(20) NOT NULL,
        value tinyint(3) DEFAULT NULL,
        review text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_movie_interaction (user_id, movie_id, interaction_type),
        KEY movie_id (movie_id),
        KEY user_id (user_id),
        KEY interaction_type (interaction_type)
    ) $charset_collate;";

    dbDelta($sql_interactions);
    
    // Insert default seat layouts for all 4 screens
    cinema_insert_all_screen_layouts();
}

/**
 * Insert seat layouts for all 4 screens based on floor plan
 */
function cinema_insert_all_screen_layouts() {
    global $wpdb;
    $table = $wpdb->prefix . 'cinema_seat_layouts';
    
    // Check if layouts already exist
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    if ($count > 0) return;
    
    // AUDI-A1 Layout (Screen 1) - 129 seats + 4 wheelchair
    $audi_a1 = array(
        'rows' => array(
            'A' => array('seats' => range(1, 15), 'type' => 'regular'),
            'B' => array('seats' => range(1, 15), 'type' => 'regular'),
            'C' => array('seats' => range(1, 15), 'type' => 'regular'),
            'D' => array('seats' => range(1, 15), 'type' => 'regular'),
            'E' => array('seats' => range(1, 15), 'type' => 'regular'),
            'F' => array('seats' => range(1, 15), 'type' => 'regular'),
            'G' => array('seats' => range(1, 15), 'type' => 'regular'),
            'H' => array('seats' => range(1, 15), 'type' => 'regular'),
            'I' => array(
                'seats' => array(1, 2, 3, 4, 5, 6, 'W1', 'W2', 7, 8, 9, 10, 11, 12, 'W3', 'W4'),
                'types' => array('W1' => 'wheelchair', 'W2' => 'wheelchair', 'W3' => 'wheelchair', 'W4' => 'wheelchair')
            )
        ),
        'aisles' => array('vertical' => array(7, 8))
    );
    
    // AUDI-A2 Layout (Screen 2) - 150 seats + 4 wheelchair  
    $audi_a2 = array(
        'rows' => array(
            'A' => array('seats' => range(1, 18), 'type' => 'regular'),
            'B' => array('seats' => range(1, 18), 'type' => 'regular'),
            'C' => array('seats' => range(1, 18), 'type' => 'regular'),
            'D' => array('seats' => range(1, 18), 'type' => 'regular'),
            'E' => array('seats' => range(1, 18), 'type' => 'regular'),
            'F' => array('seats' => range(1, 18), 'type' => 'regular'),
            'G' => array('seats' => range(1, 18), 'type' => 'regular'),
            'H' => array('seats' => range(1, 18), 'type' => 'regular'),
            'I' => array(
                'seats' => array(1, 2, 3, 4, 5, 6, 7, 'W1', 'W2', 8, 9, 10, 11, 12, 13, 14, 'W3', 'W4'),
                'types' => array('W1' => 'wheelchair', 'W2' => 'wheelchair', 'W3' => 'wheelchair', 'W4' => 'wheelchair')
            )
        ),
        'aisles' => array('vertical' => array(9))
    );
    
    // AUDI-A3 Layout (Screen 3) - 209 seats + 4 wheelchair
    $audi_a3 = array(
        'rows' => array(
            'A' => array('seats' => range(1, 24), 'type' => 'regular'),
            'B' => array('seats' => range(1, 24), 'type' => 'regular'),
            'C' => array('seats' => range(1, 24), 'type' => 'regular'),
            'D' => array('seats' => range(1, 24), 'type' => 'regular'),
            'E' => array('seats' => range(1, 24), 'type' => 'regular'),
            'F' => array('seats' => range(1, 24), 'type' => 'regular'),
            'G' => array('seats' => range(1, 24), 'type' => 'regular'),
            'H' => array('seats' => range(1, 24), 'type' => 'regular'),
            'I' => array(
                'seats' => array(1, 2, 3, 4, 5, 6, 7, 8, 9, 'W1', 'W2', 10, 11, 12, 13, 14, 15, 16, 17, 18, 'W3', 'W4'),
                'types' => array('W1' => 'wheelchair', 'W2' => 'wheelchair', 'W3' => 'wheelchair', 'W4' => 'wheelchair')
            )
        ),
        'aisles' => array('vertical' => array(11, 12))
    );
    
    // AUDI-A4 Layout (Screen 4) - 210 seats + 6 wheelchair
    $audi_a4 = array(
        'rows' => array(
            'A' => array('seats' => range(1, 24), 'type' => 'regular'),
            'B' => array('seats' => range(1, 24), 'type' => 'regular'),
            'C' => array('seats' => range(1, 24), 'type' => 'regular'),
            'D' => array('seats' => range(1, 24), 'type' => 'regular'),
            'E' => array('seats' => range(1, 24), 'type' => 'regular'),
            'F' => array('seats' => range(1, 24), 'type' => 'regular'),
            'G' => array('seats' => range(1, 24), 'type' => 'regular'),
            'H' => array('seats' => range(1, 24), 'type' => 'regular'),
            'I' => array(
                'seats' => array(1, 2, 3, 4, 5, 6, 7, 8, 'W1', 'W2', 9, 10, 11, 12, 13, 14, 15, 16, 'W3', 'W4', 17, 18, 'W5', 'W6'),
                'types' => array('W1' => 'wheelchair', 'W2' => 'wheelchair', 'W3' => 'wheelchair', 'W4' => 'wheelchair', 'W5' => 'wheelchair', 'W6' => 'wheelchair')
            )
        ),
        'aisles' => array('vertical' => array(10, 16))
    );
    
    // Insert all screen layouts
    $screens = array(
        array('screen_number' => 1, 'layout_name' => 'AUDI-A1 Layout', 'config' => $audi_a1, 'total_seats' => 133, 'wheelchair_seats' => 'I-W1,I-W2,I-W3,I-W4'),
        array('screen_number' => 2, 'layout_name' => 'AUDI-A2 Layout', 'config' => $audi_a2, 'total_seats' => 154, 'wheelchair_seats' => 'I-W1,I-W2,I-W3,I-W4'),
        array('screen_number' => 3, 'layout_name' => 'AUDI-A3 Layout', 'config' => $audi_a3, 'total_seats' => 213, 'wheelchair_seats' => 'I-W1,I-W2,I-W3,I-W4'),
        array('screen_number' => 4, 'layout_name' => 'AUDI-A4 Layout', 'config' => $audi_a4, 'total_seats' => 216, 'wheelchair_seats' => 'I-W1,I-W2,I-W3,I-W4,I-W5,I-W6')
    );
    
    foreach ($screens as $screen) {
        $wpdb->insert($table, array(
            'screen_number' => $screen['screen_number'],
            'layout_name' => $screen['layout_name'],
            'seat_configuration' => json_encode($screen['config']),
            'total_seats' => $screen['total_seats'],
            'wheelchair_seats' => $screen['wheelchair_seats'],
            'is_active' => 1
        ));
    }
}

// Run on activation
register_activation_hook(__FILE__, 'cinema_create_enhanced_tables');
add_action('after_switch_theme', 'cinema_create_enhanced_tables');

/**
 * ========================================================================
 * META BOXES - UPDATED FOR MULTI-SCREEN
 * ========================================================================
 */
function cinema_add_meta_boxes() {
    add_meta_box('movie_details', 'Movie Details', 'movie_details_callback', 'movies', 'normal', 'high');
    add_meta_box('showtime_details', 'Showtime Details', 'showtime_details_callback', 'showtimes', 'normal', 'high');
}
add_action('add_meta_boxes', 'cinema_add_meta_boxes');

// Movie Details Meta Box (unchanged)
function movie_details_callback($post) {
    wp_nonce_field('movie_details_nonce', 'movie_details_nonce');
    
    $director = get_post_meta($post->ID, '_movie_director', true);
    $producer = get_post_meta($post->ID, '_movie_producer', true);
    $cast = get_post_meta($post->ID, '_movie_cast', true);
    $duration = get_post_meta($post->ID, '_movie_duration', true);
    $rating = get_post_meta($post->ID, '_movie_rating', true);
    $release_date = get_post_meta($post->ID, '_movie_release_date', true);
    $genre = get_post_meta($post->ID, '_movie_genre', true);
    $trailer_url = get_post_meta($post->ID, '_movie_trailer_url', true);
    $trailer_banner = get_post_meta($post->ID, '_movie_trailer_banner', true);
    $background = get_post_meta($post->ID, '_movie_background', true);
    
    echo '<table class="form-table">';
    echo '<tr><th><label for="movie_director">Director</label></th>';
    echo '<td><input type="text" id="movie_director" name="movie_director" value="' . esc_attr($director) . '" size="50" /></td></tr>';
    
    echo '<tr><th><label for="movie_producer">Producer</label></th>';
    echo '<td><input type="text" id="movie_producer" name="movie_producer" value="' . esc_attr($producer) . '" size="50" /></td></tr>';
    
    echo '<tr><th><label for="movie_cast">Cast</label></th>';
    echo '<td><textarea id="movie_cast" name="movie_cast" rows="3" cols="50">' . esc_textarea($cast) . '</textarea></td></tr>';
    
    echo '<tr><th><label for="movie_duration">Duration (minutes)</label></th>';
    echo '<td><input type="number" id="movie_duration" name="movie_duration" value="' . esc_attr($duration) . '" /></td></tr>';
    
    echo '<tr><th><label for="movie_rating">Rating</label></th>';
    echo '<td><select id="movie_rating" name="movie_rating">';
    echo '<option value="G"' . selected($rating, 'G', false) . '>G</option>';
    echo '<option value="PG"' . selected($rating, 'PG', false) . '>PG</option>';
    echo '<option value="PG-13"' . selected($rating, 'PG-13', false) . '>PG-13</option>';
    echo '<option value="R"' . selected($rating, 'R', false) . '>R</option>';
    echo '</select></td></tr>';
    
    echo '<tr><th><label for="movie_release_date">Release Date</label></th>';
    echo '<td><input type="date" id="movie_release_date" name="movie_release_date" value="' . esc_attr($release_date) . '" /></td></tr>';
    
    echo '<tr><th><label for="movie_genre">Genre</label></th>';
    echo '<td><input type="text" id="movie_genre" name="movie_genre" value="' . esc_attr($genre) . '" size="50" /></td></tr>';
    
    echo '<tr><th><label for="movie_trailer_url">Trailer URL</label></th>';
    echo '<td><input type="url" id="movie_trailer_url" name="movie_trailer_url" value="' . esc_attr($trailer_url) . '" size="50" /></td></tr>';

    // Movie Background
    echo '<tr><th><label for="movie_background">Movie Background Image</label></th><td>';
    echo '<input type="hidden" id="movie_background" name="movie_background" value="' . esc_attr($background) . '" />';
    echo '<button type="button" class="button" id="upload_movie_background">Choose Image</button>';
    echo '<button type="button" class="button" id="remove_movie_background" style="margin-left: 10px; display: ' . ($background ? 'inline-block' : 'none') . ';">Remove</button>';
    echo '<div id="movie_background_preview" style="margin-top: 10px;">';
    if ($background) echo '<img src="' . esc_url(wp_get_attachment_image_url($background, 'medium')) . '" style="max-width: 300px;" />';
    echo '</div></td></tr>';
    
    // Trailer Banner
    echo '<tr><th><label for="movie_trailer_banner">Trailer Banner</label></th><td>';
    echo '<input type="hidden" id="movie_trailer_banner" name="movie_trailer_banner" value="' . esc_attr($trailer_banner) . '" />';
    echo '<button type="button" class="button" id="upload_trailer_banner">Choose Image</button>';
    echo '<button type="button" class="button" id="remove_trailer_banner" style="margin-left: 10px; display: ' . ($trailer_banner ? 'inline-block' : 'none') . ';">Remove</button>';
    echo '<div id="trailer_banner_preview" style="margin-top: 10px;">';
    if ($trailer_banner) echo '<img src="' . esc_url(wp_get_attachment_image_url($trailer_banner, 'medium')) . '" style="max-width: 300px;" />';
    echo '</div></td></tr>';
    
    echo '</table>';
    
    // Media uploader scripts
    ?>
<script>
jQuery(document).ready(function($) {
    var mediaUploaderBackground, mediaUploaderTrailer;

    $('#upload_movie_background').click(function(e) {
        e.preventDefault();
        if (mediaUploaderBackground) {
            mediaUploaderBackground.open();
            return;
        }
        mediaUploaderBackground = wp.media({
            title: 'Choose Movie Background',
            button: {
                text: 'Choose Image'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        mediaUploaderBackground.on('select', function() {
            var attachment = mediaUploaderBackground.state().get('selection').first().toJSON();
            $('#movie_background').val(attachment.id);
            $('#movie_background_preview').html('<img src="' + attachment.url +
                '" style="max-width: 300px;" />');
            $('#remove_movie_background').show();
        });
        mediaUploaderBackground.open();
    });

    $('#remove_movie_background').click(function(e) {
        e.preventDefault();
        $('#movie_background').val('');
        $('#movie_background_preview').html('');
        $(this).hide();
    });

    $('#upload_trailer_banner').click(function(e) {
        e.preventDefault();
        if (mediaUploaderTrailer) {
            mediaUploaderTrailer.open();
            return;
        }
        mediaUploaderTrailer = wp.media({
            title: 'Choose Trailer Banner',
            button: {
                text: 'Choose Image'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        mediaUploaderTrailer.on('select', function() {
            var attachment = mediaUploaderTrailer.state().get('selection').first().toJSON();
            $('#movie_trailer_banner').val(attachment.id);
            $('#trailer_banner_preview').html('<img src="' + attachment.url +
                '" style="max-width: 300px;" />');
            $('#remove_trailer_banner').show();
        });
        mediaUploaderTrailer.open();
    });

    $('#remove_trailer_banner').click(function(e) {
        e.preventDefault();
        $('#movie_trailer_banner').val('');
        $('#trailer_banner_preview').html('');
        $(this).hide();
    });
});
</script>
<?php
}

// Showtime Details Meta Box - UPDATED WITH SCREEN 1-4 DROPDOWN
function showtime_details_callback($post) {
    wp_nonce_field('showtime_details_nonce', 'showtime_details_nonce');
    
    $movie_id = get_post_meta($post->ID, '_showtime_movie_id', true);
    $screen_number = get_post_meta($post->ID, '_showtime_screen', true);
    $show_date = get_post_meta($post->ID, '_showtime_date', true);
    $show_time = get_post_meta($post->ID, '_showtime_time', true);
    $ticket_price = get_post_meta($post->ID, '_showtime_ticket_price', true);
    
    $movies = get_posts(array('post_type' => 'movies', 'numberposts' => -1));
    
    echo '<table class="form-table">';
    echo '<tr><th><label for="showtime_movie_id">Movie</label></th>';
    echo '<td><select id="showtime_movie_id" name="showtime_movie_id"><option value="">Select Movie</option>';
    foreach($movies as $movie) {
        echo '<option value="' . $movie->ID . '"' . selected($movie_id, $movie->ID, false) . '>' . $movie->post_title . '</option>';
    }
    echo '</select></td></tr>';
    
    // UPDATED: Screen Number dropdown with 1-4 options
    echo '<tr><th><label for="showtime_screen">Screen Number</label></th>';
    echo '<td><select id="showtime_screen" name="showtime_screen" required>';
    echo '<option value="">Select Screen</option>';
    echo '<option value="1"' . selected($screen_number, '1', false) . '>Screen 1 (AUDI-A1 - 133 seats)</option>';
    echo '<option value="2"' . selected($screen_number, '2', false) . '>Screen 2 (AUDI-A2 - 154 seats)</option>';
    echo '<option value="3"' . selected($screen_number, '3', false) . '>Screen 3 (AUDI-A3 - 213 seats)</option>';
    echo '<option value="4"' . selected($screen_number, '4', false) . '>Screen 4 (AUDI-A4 - 216 seats)</option>';
    echo '</select>';
    echo '<p class="description">Select which screen this showtime will use. Each screen has a different seat layout.</p>';
    echo '</td></tr>';
    
    echo '<tr><th><label for="showtime_date">Show Date</label></th>';
    echo '<td><input type="date" id="showtime_date" name="showtime_date" value="' . esc_attr($show_date) . '" /></td></tr>';
    
    echo '<tr><th><label for="showtime_time">Show Time</label></th>';
    echo '<td><input type="time" id="showtime_time" name="showtime_time" value="' . esc_attr($show_time) . '" /></td></tr>';
    
    echo '<tr><th><label for="showtime_ticket_price">Ticket Price</label></th>';
    echo '<td><input type="number" id="showtime_ticket_price" name="showtime_ticket_price" value="' . esc_attr($ticket_price) . '" step="0.01" /></td></tr>';
    
    echo '</table>';
}

// Save Meta Box Data - UPDATED TO SAVE SCREEN NUMBER
function cinema_save_meta_boxes($post_id) {
    // Movie meta
    if (isset($_POST['movie_details_nonce']) && wp_verify_nonce($_POST['movie_details_nonce'], 'movie_details_nonce')) {
        $fields = array('director', 'producer', 'cast', 'duration', 'rating', 'release_date', 'genre', 'trailer_url', 'trailer_banner', 'background');
        foreach($fields as $field) {
            if (isset($_POST['movie_' . $field])) {
                update_post_meta($post_id, '_movie_' . $field, sanitize_text_field($_POST['movie_' . $field]));
            }
        }
    }
    
    // Showtime meta - INCLUDES SCREEN NUMBER
    if (isset($_POST['showtime_details_nonce']) && wp_verify_nonce($_POST['showtime_details_nonce'], 'showtime_details_nonce')) {
        if (isset($_POST['showtime_movie_id'])) {
            update_post_meta($post_id, '_showtime_movie_id', intval($_POST['showtime_movie_id']));
        }
        if (isset($_POST['showtime_screen'])) {
            $screen = intval($_POST['showtime_screen']);
            if ($screen >= 1 && $screen <= 4) {
                update_post_meta($post_id, '_showtime_screen', $screen);
            }
        }
        if (isset($_POST['showtime_date'])) {
            update_post_meta($post_id, '_showtime_date', sanitize_text_field($_POST['showtime_date']));
        }
        if (isset($_POST['showtime_time'])) {
            update_post_meta($post_id, '_showtime_time', sanitize_text_field($_POST['showtime_time']));
        }
        if (isset($_POST['showtime_ticket_price'])) {
            update_post_meta($post_id, '_showtime_ticket_price', floatval($_POST['showtime_ticket_price']));
        }
    }
}
add_action('save_post', 'cinema_save_meta_boxes');

// Enqueue media uploader in admin
function cinema_admin_scripts($hook) {
    global $post;
    if ($hook == 'post-new.php' || $hook == 'post.php') {
        if ('movies' === $post->post_type) {
            wp_enqueue_media();
        }
    }
}
add_action('admin_enqueue_scripts', 'cinema_admin_scripts');

/**
 * ========================================================================
 * MULTI-SCREEN AJAX HANDLERS
 * ========================================================================
 */

add_action('wp_ajax_cinema_get_seat_map', 'cinema_get_seat_map_ajax');
add_action('wp_ajax_nopriv_cinema_get_seat_map', 'cinema_get_seat_map_ajax');

function cinema_get_seat_map_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    $showtime_id = intval($_POST['showtime_id']);
    if (!$showtime_id) {
        wp_send_json_error(array('message' => 'Invalid showtime'));
    }
    
    // Support both regular movie showtimes and event showtimes which use a different meta key
    $post_type = get_post_type($showtime_id);
    if ($post_type === 'event_showtimes') {
        $screen_number = get_post_meta($showtime_id, '_event_showtime_screen', true);
    } else {
        $screen_number = get_post_meta($showtime_id, '_showtime_screen', true);
    }

    // Basic validation (screen numbers are expected to be positive integers)
    $screen_number = intval($screen_number);
    if (!$screen_number || $screen_number < 1) {
        wp_send_json_error(array('message' => 'Invalid screen number'));
    }
    
    global $wpdb;
    $layouts_table = $wpdb->prefix . 'cinema_seat_layouts';
    
    $layout = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $layouts_table WHERE screen_number = %d AND is_active = 1",
        $screen_number
    ));
    
    if (!$layout) {
        wp_send_json_error(array('message' => 'Screen layout not found'));
    }
    
    if (!session_id()) {
        session_start();
    }
    $session_id = session_id();
    
    // Get permanently booked seats
    $booked_seats = cinema_get_booked_seats($showtime_id, $screen_number);
    
    // Get temporarily locked seats (excluding current user)
    $locked_seats = cinema_get_locked_seats($showtime_id, $screen_number, $session_id);
    
    $seat_config = json_decode($layout->seat_configuration, true);
    
    // If there's a saved cart in session for this showtime, include it so client can restore selections
    $saved_cart = array();
    if (isset($_SESSION['cinema_cart']) && is_array($_SESSION['cinema_cart'])) {
        $cart = $_SESSION['cinema_cart'];
        if (isset($cart['showtime_id']) && intval($cart['showtime_id']) === $showtime_id && !empty($cart['items'])) {
            $saved_cart = $cart['items'];
        }
    }

    wp_send_json_success(array(
        'screen_number' => $screen_number,
        'layout_name' => $layout->layout_name,
        'seat_configuration' => $seat_config,
        'total_seats' => $layout->total_seats,
        'wheelchair_seats' => explode(',', $layout->wheelchair_seats),
        'booked_seats' => $booked_seats,
        'locked_seats' => $locked_seats,
        'session_id' => $session_id,
        'saved_cart' => $saved_cart
    ));
}


/**
 * ========================================================================
 * 7. CREATE BOOKING - MARK AS PERMANENTLY BOOKED
 * ========================================================================
 */
add_action('wp_ajax_cinema_create_booking_confirmed', 'cinema_create_booking_confirmed_ajax');
add_action('wp_ajax_nopriv_cinema_create_booking_confirmed', 'cinema_create_booking_confirmed_ajax');

function cinema_create_booking_confirmed_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    if (!session_id()) {
        session_start();
    }
    
    $showtime_id = intval($_POST['showtime_id']);
    $seat_numbers = isset($_POST['seat_numbers']) ? $_POST['seat_numbers'] : array();
    $customer_name = sanitize_text_field($_POST['customer_name']);
    $customer_email = sanitize_email($_POST['customer_email']);
    $customer_phone = sanitize_text_field($_POST['customer_phone']);
    $total_amount = floatval($_POST['total_amount']);
    
    if (!$showtime_id || empty($seat_numbers)) {
        wp_send_json_error(array('message' => 'Invalid booking data'));
    }
    
    // Determine booking type
    $booking_type = isset($_POST['booking_type']) && $_POST['booking_type'] === 'event' ? 'event' : null;
    $post_type = get_post_type($showtime_id);
    if ($booking_type === 'event' || $post_type === 'event_showtimes') {
        $screen_number = get_post_meta($showtime_id, '_event_showtime_screen', true);
        $booking_type = 'event';
        $event_id = get_post_meta($showtime_id, '_event_showtime_event_id', true);
    } else {
        $screen_number = get_post_meta($showtime_id, '_showtime_screen', true);
        $booking_type = 'movie';
        $event_id = null;
    }
    $show_date = get_post_meta($showtime_id, '_showtime_date', true);
    $show_time = get_post_meta($showtime_id, '_showtime_time', true);
    $user_id = is_user_logged_in() ? get_current_user_id() : 0;
    
    global $wpdb;
    $bookings_table = $wpdb->prefix . 'cinema_bookings';
    $seats_table = $wpdb->prefix . 'cinema_seats';
    $locks_table = $wpdb->prefix . 'cinema_seat_locks';
    
    // Start transaction
    $wpdb->query('START TRANSACTION');
    
    try {
        // Double-check seats are not already booked
        foreach ($seat_numbers as $seat_number) {
            if (cinema_is_seat_permanently_booked($showtime_id, $screen_number, $seat_number)) {
                throw new Exception('Seat ' . $seat_number . ' is already booked');
            }
        }
        
        // Generate unique booking reference
        $booking_reference = 'BK' . strtoupper(uniqid());
        
        // Insert booking record with CONFIRMED status and PAID payment status
        $wpdb->insert($bookings_table, array(
            'booking_reference' => $booking_reference,
            'showtime_id' => $showtime_id,
            'booking_type' => $booking_type,
            'event_id' => $event_id,
            'screen_number' => $screen_number,
            'user_id' => $user_id,
            'customer_email' => $customer_email,
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone,
            'show_date' => $show_date,
            'show_time' => $show_time,
            'total_amount' => $total_amount,
            'booking_status' => 'confirmed', // CONFIRMED immediately
            'payment_status' => 'paid', // PAID immediately
            'payment_method' => 'stripe',
            'booking_date' => current_time('mysql'),
            'created_at' => current_time('mysql')
        ));
        
        $booking_id = $wpdb->insert_id;
        
        if (!$booking_id) {
            throw new Exception('Failed to create booking');
        }
        
        // Insert seat records
        foreach ($seat_numbers as $seat_number) {
            // Extract row and column from seat number (e.g., A5 -> row:A, col:5)
            preg_match('/([A-Z]+)([0-9]+)/', $seat_number, $matches);
            $seat_row = $matches[1] ?? '';
            $seat_column = $matches[2] ?? '';
            
            $wpdb->insert($seats_table, array(
                'booking_id' => $booking_id,
                'screen_number' => $screen_number,
                'seat_number' => $seat_number,
                'seat_row' => $seat_row,
                'seat_column' => $seat_column,
                'seat_type' => 'regular',
                'ticket_type' => 'adult',
                'price' => $total_amount / count($seat_numbers),
                'created_at' => current_time('mysql')
            ));
        }
        
        // Release temporary locks for this session
        $session_id = session_id();
        $wpdb->delete($locks_table, array(
            'showtime_id' => $showtime_id,
            'session_id' => $session_id
        ));
        
        // Commit transaction
        $wpdb->query('COMMIT');
        
        wp_send_json_success(array(
            'message' => 'Booking confirmed successfully',
            'booking_id' => $booking_id,
            'booking_reference' => $booking_reference
        ));
        
    } catch (Exception $e) {
        // Rollback on error
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => $e->getMessage()));
    }
}

/**
 * ========================================================================
 * 8. CANCEL BOOKING - UNLOCK SEATS
 * ========================================================================
 */
add_action('wp_ajax_cinema_cancel_booking_unlock', 'cinema_cancel_booking_unlock_ajax');

function cinema_cancel_booking_unlock_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Please login'));
    }
    
    $booking_id = intval($_POST['booking_id']);
    $user_id = get_current_user_id();
    
    global $wpdb;
    $bookings_table = $wpdb->prefix . 'cinema_bookings';
    
    // Get booking
    $booking = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $bookings_table WHERE id = %d AND user_id = %d",
        $booking_id,
        $user_id
    ));
    
    if (!$booking) {
        wp_send_json_error(array('message' => 'Booking not found'));
    }
    
    // Check if already cancelled
    if ($booking->booking_status === 'cancelled') {
        wp_send_json_error(array('message' => 'Booking already cancelled'));
    }
    
    // Update booking status to cancelled
    $wpdb->update($bookings_table, 
        array(
            'booking_status' => 'cancelled',
            'payment_status' => 'refunded',
            'cancelled_at' => current_time('mysql'),
            'cancellation_reason' => 'Cancelled by user'
        ),
        array('id' => $booking_id)
    );
    
    wp_send_json_success(array(
        'message' => 'Booking cancelled successfully. Seats are now available for rebooking.'
    ));
}




/**
 * ========================================================================
 * 9. CLEAN EXPIRED LOCKS - CRON JOB
 * ========================================================================
 */
add_action('cinema_clean_expired_locks_cron', 'cinema_clean_expired_locks_job');

function cinema_clean_expired_locks_job() {
    global $wpdb;
    $locks_table = $wpdb->prefix . 'cinema_seat_locks';
    
    $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM $locks_table WHERE expires_at < %s",
        current_time('mysql')
    ));
    
    if ($deleted > 0) {
        error_log("Cinema: Cleaned $deleted expired seat locks");
    }
}

// Schedule cron if not scheduled
if (!wp_next_scheduled('cinema_clean_expired_locks_cron')) {
    wp_schedule_event(time(), 'every_five_minutes', 'cinema_clean_expired_locks_cron');
}


/**
 * ========================================================================
 * 10. HELPER FUNCTION - GET USER'S LOCKED SEATS
 * ========================================================================
 */
function cinema_get_user_locked_seats($showtime_id) {
    if (!session_id()) {
        session_start();
    }
    $session_id = session_id();
    
    global $wpdb;
    $locks_table = $wpdb->prefix . 'cinema_seat_locks';
    
    $seats = $wpdb->get_col($wpdb->prepare(
        "SELECT seat_number FROM $locks_table 
        WHERE showtime_id = %d 
        AND session_id = %s 
        AND expires_at > %s",
        $showtime_id,
        $session_id,
        current_time('mysql')
    ));
    
    return $seats ? $seats : array();
}



/**
 * ========================================================================
 * 11. EXTEND LOCK TIME - OPTIONAL (call when user is active)
 * ========================================================================
 */
add_action('wp_ajax_cinema_extend_seat_lock', 'cinema_extend_seat_lock_ajax');
add_action('wp_ajax_nopriv_cinema_extend_seat_lock', 'cinema_extend_seat_lock_ajax');

function cinema_extend_seat_lock_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    $showtime_id = intval($_POST['showtime_id']);
    
    if (!session_id()) {
        session_start();
    }
    $session_id = session_id();
    
    global $wpdb;
    $locks_table = $wpdb->prefix . 'cinema_seat_locks';
    
    // Extend lock by configured minutes (keep consistent with client lock duration)
    $lock_minutes = intval(get_option('cinema_seat_lock_duration', 2));
    if ($lock_minutes <= 0) $lock_minutes = 2;
    $new_expires_at = date('Y-m-d H:i:s', strtotime('+' . $lock_minutes . ' minutes'));
    
    $updated = $wpdb->update($locks_table,
        array('expires_at' => $new_expires_at),
        array(
            'showtime_id' => $showtime_id,
            'session_id' => $session_id
        )
    );
    
    if ($updated) {
        wp_send_json_success(array(
            'message' => 'Lock extended',
            'expires_at' => $new_expires_at
        ));
    } else {
        wp_send_json_error(array('message' => 'No locks found to extend'));
    }
}


function cinema_get_booked_seats($showtime_id, $screen_number) {
    global $wpdb;
    $bookings_table = $wpdb->prefix . 'cinema_bookings';
    $seats_table = $wpdb->prefix . 'cinema_seats';
    
    // Get seats from CONFIRMED bookings only (payment completed)
    $booked_seats = $wpdb->get_col($wpdb->prepare(
        "SELECT s.seat_number 
        FROM $seats_table s
        INNER JOIN $bookings_table b ON s.booking_id = b.id
        WHERE b.showtime_id = %d 
        AND b.screen_number = %d
        AND b.booking_status = 'confirmed'
        AND b.payment_status = 'paid'
        AND (b.cancelled_at IS NULL OR b.booking_status != 'cancelled')",
        $showtime_id,
        $screen_number
    ));
    
    return $booked_seats ? $booked_seats : array();
}

// Get locked seats
function cinema_get_locked_seats($showtime_id, $screen_number, $exclude_session = null) {
    global $wpdb;
    $locks_table = $wpdb->prefix . 'cinema_seat_locks';
    
    // First, clean expired locks
    $wpdb->query($wpdb->prepare(
        "DELETE FROM $locks_table WHERE expires_at < %s",
        current_time('mysql')
    ));
    
    // Get current valid locks (excluding current user's session if specified)
    $sql = "SELECT seat_number 
            FROM $locks_table 
            WHERE showtime_id = %d 
            AND screen_number = %d
            AND expires_at > %s";
    
    $params = array($showtime_id, $screen_number, current_time('mysql'));
    
    if ($exclude_session) {
        $sql .= " AND session_id != %s";
        $params[] = $exclude_session;
    }
    
    $locked_seats = $wpdb->get_col($wpdb->prepare($sql, $params));
    
    return $locked_seats ? $locked_seats : array();
}

/**
 * ========================================================================
 * 2. LOCK SEATS - TEMPORARY LOCK (10 MINUTES)
 * ========================================================================
 */
add_action('wp_ajax_cinema_lock_seats', 'cinema_lock_seats_ajax');
add_action('wp_ajax_nopriv_cinema_lock_seats', 'cinema_lock_seats_ajax');

function cinema_lock_seats_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    $showtime_id = intval($_POST['showtime_id']);
    $seat_numbers = isset($_POST['seat_numbers']) ? $_POST['seat_numbers'] : array();
    
    if (!$showtime_id || empty($seat_numbers)) {
        wp_send_json_error(array('message' => 'Invalid data'));
    }
    
    // Support event showtimes which store meta under different keys
    $post_type = get_post_type($showtime_id);
    if ($post_type === 'event_showtimes') {
        $screen_number = get_post_meta($showtime_id, '_event_showtime_screen', true);
    } else {
        $screen_number = get_post_meta($showtime_id, '_showtime_screen', true);
    }
    
    // Start session if not started
    if (!session_id()) {
        session_start();
    }
    $session_id = session_id();
    
    global $wpdb;
    $locks_table = $wpdb->prefix . 'cinema_seat_locks';
    
    // Lock expires in configured minutes
    $lock_minutes = intval(get_option('cinema_seat_lock_duration', 2));
    if ($lock_minutes <= 0) $lock_minutes = 2;
    $expires_at = date('Y-m-d H:i:s', strtotime('+' . $lock_minutes . ' minutes'));
    
    foreach ($seat_numbers as $seat_number) {
        // Check if seat is PERMANENTLY BOOKED (paid)
        $is_booked = cinema_is_seat_permanently_booked($showtime_id, $screen_number, $seat_number);
        
        if ($is_booked) {
            wp_send_json_error(array(
                'message' => 'Seat ' . $seat_number . ' is already booked',
                'seat' => $seat_number
            ));
        }
        
        // Check if seat is TEMPORARILY LOCKED by another user
        $existing_lock = $wpdb->get_row($wpdb->prepare(
            "SELECT session_id, expires_at FROM $locks_table 
            WHERE showtime_id = %d 
            AND screen_number = %d 
            AND seat_number = %s 
            AND expires_at > %s",
            $showtime_id,
            $screen_number,
            $seat_number,
            current_time('mysql')
        ));
        
        if ($existing_lock && $existing_lock->session_id !== $session_id) {
            wp_send_json_error(array(
                'message' => 'Seat ' . $seat_number . ' is currently selected by another user',
                'seat' => $seat_number
            ));
        }
        
        // Lock the seat (REPLACE will update if exists, insert if not)
        $wpdb->replace($locks_table, array(
            'showtime_id' => $showtime_id,
            'screen_number' => $screen_number,
            'seat_number' => $seat_number,
            'session_id' => $session_id,
            'locked_at' => current_time('mysql'),
            'expires_at' => $expires_at
        ));
    }
    
    wp_send_json_success(array(
        'message' => 'Seats locked successfully',
        'expires_at' => $expires_at,
        'session_id' => $session_id
    ));
}

/**
 * ========================================================================
 * 4. CHECK IF SEAT IS PERMANENTLY BOOKED
 * ========================================================================
 */
function cinema_is_seat_permanently_booked($showtime_id, $screen_number, $seat_number) {
    global $wpdb;
    $bookings_table = $wpdb->prefix . 'cinema_bookings';
    $seats_table = $wpdb->prefix . 'cinema_seats';
    
    $is_booked = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) 
        FROM $seats_table s
        INNER JOIN $bookings_table b ON s.booking_id = b.id
        WHERE b.showtime_id = %d 
        AND b.screen_number = %d
        AND s.seat_number = %s
        AND b.booking_status = 'confirmed'
        AND b.payment_status = 'paid'
        AND (b.cancelled_at IS NULL OR b.booking_status != 'cancelled')",
        $showtime_id,
        $screen_number,
        $seat_number
    ));
    
    return $is_booked > 0;
}

/**
 * ========================================================================
 * 3. RELEASE SEAT LOCKS - IMMEDIATE UNLOCK ON DESELECT
 * ========================================================================
 */
add_action('wp_ajax_cinema_release_seat_locks', 'cinema_release_seat_locks_ajax');
add_action('wp_ajax_nopriv_cinema_release_seat_locks', 'cinema_release_seat_locks_ajax');

function cinema_release_seat_locks_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    $showtime_id = intval($_POST['showtime_id']);
    $seat_numbers = isset($_POST['seat_numbers']) ? $_POST['seat_numbers'] : array();
    
    if (!session_id()) {
        session_start();
    }
    $session_id = session_id();
    
    global $wpdb;
    $locks_table = $wpdb->prefix . 'cinema_seat_locks';
    
    if (!empty($seat_numbers)) {
        // Release specific seats for this showtime and session
        foreach ($seat_numbers as $seat_number) {
            $wpdb->delete($locks_table, array(
                'showtime_id' => $showtime_id,
                'seat_number' => $seat_number,
                'session_id' => $session_id
            ));
        }
    } else {
        // Release all seats for this session and showtime
        $wpdb->delete($locks_table, array(
            'showtime_id' => $showtime_id,
            'session_id' => $session_id
        ));
    }
    
    wp_send_json_success(array('message' => 'Seat locks released'));
}

/**
 * Release locks and destroy session on user logout so seats are freed immediately
 */
add_action('wp_logout', 'cinema_on_user_logout');
function cinema_on_user_logout() {
    if (!session_id()) {
        @session_start();
    }
    $session_id = session_id();

    if ($session_id) {
        global $wpdb;
        $locks_table = $wpdb->prefix . 'cinema_seat_locks';
        // Remove locks created by this session
        $wpdb->delete($locks_table, array('session_id' => $session_id));
    }

    // Destroy session to avoid restoring old cart data
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']
        );
    }
    @session_destroy();
}

/**
 * Clear client-side temporary booking storage after logout redirect
 * If the logout redirect contains a parameter like ?logged_out=1 we inject script to clear localStorage
 */
add_action('wp_footer', 'cinema_clear_client_storage_after_logout');
function cinema_clear_client_storage_after_logout() {
    if (isset($_GET['logged_out']) && intval($_GET['logged_out']) === 1) {
        ?>
        <script>
            try {
                localStorage.removeItem('cinema_temp_booking');
                localStorage.removeItem('cinema_booking_backup');
            } catch(e) { /* ignore */ }
        </script>
        <?php
    }
}


/**
 * ========================================================================
 * 5. CHECK SEAT AVAILABILITY - REAL-TIME UPDATES
 * ========================================================================
 */
add_action('wp_ajax_cinema_check_seat_availability', 'cinema_check_seat_availability_ajax');
add_action('wp_ajax_nopriv_cinema_check_seat_availability', 'cinema_check_seat_availability_ajax');

function cinema_check_seat_availability_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    $showtime_id = intval($_POST['showtime_id']);
    // Resolve screen number for events or regular showtimes
    $post_type = get_post_type($showtime_id);
    if ($post_type === 'event_showtimes') {
        $screen_number = get_post_meta($showtime_id, '_event_showtime_screen', true);
    } else {
        $screen_number = get_post_meta($showtime_id, '_showtime_screen', true);
    }
    
    if (!session_id()) {
        session_start();
    }
    $session_id = session_id();
    
    // Get permanently booked seats (paid)
    $booked_seats = cinema_get_booked_seats($showtime_id, $screen_number);
    
    // Get temporarily locked seats (excluding current user's locks)
    $locked_seats = cinema_get_locked_seats($showtime_id, $screen_number, $session_id);
    
    // Combine both
    $unavailable = array_merge($booked_seats, $locked_seats);
    
    wp_send_json_success(array(
        'unavailable' => array_unique($unavailable),
        'permanently_booked' => $booked_seats,
        'temporarily_locked' => $locked_seats,
        'timestamp' => current_time('mysql')
    ));
}

// Save cart with screen info
add_action('wp_ajax_cinema_save_cart', 'cinema_save_cart_ajax');
add_action('wp_ajax_nopriv_cinema_save_cart', 'cinema_save_cart_ajax');

function cinema_save_cart_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    $showtime_id = intval($_POST['showtime_id']);
    $cart_items = isset($_POST['cart_items']) ? json_decode(stripslashes($_POST['cart_items']), true) : array();
    
    if (!$showtime_id || empty($cart_items)) {
        wp_send_json_error(array('message' => 'Invalid cart data'));
    }
    
    // Determine screen number and booking type (event vs movie)
    $post_type = get_post_type($showtime_id);
    if ($post_type === 'event_showtimes') {
        $screen_number = get_post_meta($showtime_id, '_event_showtime_screen', true);
        $booking_type = 'event';
        // If the event id is provided as part of the meta, include it
        $event_id = get_post_meta($showtime_id, '_event_showtime_event_id', true);
    } else {
        $screen_number = get_post_meta($showtime_id, '_showtime_screen', true);
        $booking_type = 'movie';
        $event_id = null;
    }
    
    // Save to session
    if (!session_id()) {
        session_start();
    }
    
    $_SESSION['cinema_cart'] = array(
        'showtime_id' => $showtime_id,
        'screen_number' => $screen_number,
        'booking_type' => $booking_type,
        'event_id' => $event_id,
        // Ensure each item also carries the showtime_id for easier removal
        'items' => array_map(function($it) use ($showtime_id) {
            if (!isset($it['showtime_id'])) $it['showtime_id'] = $showtime_id;
            return $it;
        }, $cart_items),
        'timestamp' => time()
    );
    
    wp_send_json_success(array('message' => 'Cart saved successfully'));
}

// Remove cart item
add_action('wp_ajax_cinema_remove_cart_item', 'cinema_remove_cart_item_ajax');
add_action('wp_ajax_nopriv_cinema_remove_cart_item', 'cinema_remove_cart_item_ajax');

function cinema_remove_cart_item_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    if (!session_id()) {
        session_start();
    }
    
    $seat = sanitize_text_field($_POST['seat']);
    $showtime_id = intval($_POST['showtime_id']);
    
    if (isset($_SESSION['cinema_cart'])) {
        $cart = $_SESSION['cinema_cart'];
        
        $cart['items'] = array_filter($cart['items'], function($item) use ($seat, $showtime_id) {
            return !($item['seat'] === $seat && $item['showtime_id'] == $showtime_id);
        });
        
        $cart['items'] = array_values($cart['items']);
        
        if (empty($cart['items'])) {
            unset($_SESSION['cinema_cart']);
            wp_send_json_success(array('message' => 'Cart is empty'));
        } else {
            $_SESSION['cinema_cart'] = $cart;
            wp_send_json_success(array('message' => 'Item removed'));
        }
    }
    
    wp_send_json_error(array('message' => 'Cart not found'));
}

// Clear cart
add_action('wp_ajax_cinema_clear_cart', 'cinema_clear_cart_ajax');
add_action('wp_ajax_nopriv_cinema_clear_cart', 'cinema_clear_cart_ajax');

function cinema_clear_cart_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    if (!session_id()) {
        session_start();
    }
    
    unset($_SESSION['cinema_cart']);
    wp_send_json_success(array('message' => 'Cart cleared'));
}

/**
 * ========================================================================
 * ENQUEUE SCRIPTS WITH MULTI-SCREEN SUPPORT
 * ========================================================================
 */
function cinema_enqueue_scripts() {
    // CSS
    wp_enqueue_style('cinema-style', get_stylesheet_directory_uri() . '/cinema-style.css');
    
    // Common utilities
    wp_enqueue_script('cinema-common', get_stylesheet_directory_uri() . '/js/cinema-common.js', array('jquery'), '2.0', true);
    
    // Multi-screen seat selection
    if (is_page_template('page-seat-selection.php') || is_page_template('template-seat-selection.php') || is_page_template('page-event-seat-selection.php')) {
        wp_enqueue_script('cinema-multi-screen', get_stylesheet_directory_uri() . '/js/cinema-multi-screen.js', array('jquery', 'cinema-common'), '2.0', true);
    }
    
    // Movies page
    if (is_post_type_archive('movies') || is_singular('movies')) {
        wp_enqueue_script('cinema-movies', get_stylesheet_directory_uri() . '/js/cinema-movies.js', array('jquery', 'cinema-common'), '1.0', true);
    }

    // Events page
    if (is_post_type_archive('events') || is_singular('events')) {
        wp_enqueue_script('cinema-events', get_stylesheet_directory_uri() . '/js/cinema-events.js', array('jquery', 'cinema-common'), '1.0', true);
    }
    
    // Cart page
    if (is_page_template('page-cart.php')) {
        wp_enqueue_script('cinema-cart', get_stylesheet_directory_uri() . '/js/cinema-cart.js', array('jquery', 'cinema-common'), '1.0', true);
    }
    
    // Payment page
    if (is_page_template('page-payment.php')) {
        wp_enqueue_script('cinema-payment', get_stylesheet_directory_uri() . '/js/cinema-payment.js', array('jquery', 'cinema-common'), '1.0', true);
    }
    
    // Localize script
    wp_localize_script('cinema-common', 'cinema_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cinema_nonce'),
        'is_logged_in' => is_user_logged_in(),
        'login_url' => home_url('/login/'),
        // include site base so JS can build absolute URLs correctly (supports subdirectory installs)
        'site_url' => untrailingslashit(home_url())
    ));
}
add_action('wp_enqueue_scripts', 'cinema_enqueue_scripts');

/**
 * ========================================================================
 * STRIPE CONFIGURATION
 * ========================================================================
 */
define('STRIPE_PUBLISHABLE_KEY', 'STRIPE_PUBLISHABLE_KEY');
define('STRIPE_SECRET_KEY', 'STRIPE_SECRET_KEY');
define('STRIPE_WEBHOOK_SECRET', 'STRIPE_WEBHOOK_KEY');
//stripe configuration


require_once get_stylesheet_directory() . '/includes/vendor/autoload.php';
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

/**
 * ========================================================================
 * INCLUDE CINEMA SYSTEM FILES
 * ========================================================================
 */
require_once get_stylesheet_directory() . '/includes/class-cinema-booking-manager.php';
require_once get_stylesheet_directory() . '/includes/class-cinema-stripe-payment.php';
require_once get_stylesheet_directory() . '/admin/cinema-admin-panel.php';
require_once get_stylesheet_directory() . '/shortcode/shortcode-movie-categorize.php';
require_once get_stylesheet_directory() . '/shortcode/shortcode-events-categorize.php';
require_once get_stylesheet_directory() . '/shortcode/shortcode-login-logout.php';

/**
 * Centralized session starter.
 * Start sessions early and consistently for front-end and AJAX requests.
 * Avoid starting sessions in admin to prevent unexpected side-effects.
 */
add_action('init', 'cinema_maybe_start_session', 1);
function cinema_maybe_start_session() {
    // Don't start sessions in CLI or during REST where not needed
    if ( defined('WP_CLI') && WP_CLI ) {
        return;
    }

    // Start for AJAX and frontend requests, but avoid admin screens
    if ( is_admin() && ! ( defined('DOING_AJAX') && DOING_AJAX ) ) {
        return;
    }

    if ( ! session_id() ) {
        // Suppress warnings if headers already sent (shouldn't happen when hooked to init)
        @session_start();
    }
}

/**
 * ========================================================================
 * AUTHENTICATION SYSTEM
 * ========================================================================
 */

// Check authentication
function cinema_check_authentication() {
    if (is_page_template('page-seat-selection.php') || is_page_template('page-payment.php') || is_page_template('template-seat-selection.php')) {
        if (!is_user_logged_in()) {
            if (!session_id()) session_start();
            $_SESSION['cinema_redirect_after_login'] = $_SERVER['REQUEST_URI'];
            wp_redirect(home_url('/login/?redirect_to=' . urlencode($_SERVER['REQUEST_URI'])));
            exit;
        }
    }
}
add_action('template_redirect', 'cinema_check_authentication');

// User Login
add_action('wp_ajax_nopriv_cinema_user_login', 'cinema_user_login_ajax');
function cinema_user_login_ajax() {
    check_ajax_referer('cinema_auth_nonce', 'nonce');
    
    $username = sanitize_text_field($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    $creds = array(
        'user_login' => $username,
        'user_password' => $password,
        'remember' => $remember
    );
    
    $user = wp_signon($creds, false);
    
    if (is_wp_error($user)) {
        wp_send_json_error(array('message' => $user->get_error_message()));
    } else {
        wp_send_json_success(array(
            'message' => 'Login successful!',
            'redirect_url' => isset($_POST['redirect_to']) ? $_POST['redirect_to'] : home_url('/movies')
        ));
    }
}

// User Registration
add_action('wp_ajax_nopriv_cinema_user_register', 'cinema_user_register_ajax');
function cinema_user_register_ajax() {
    check_ajax_referer('cinema_auth_nonce', 'nonce');
    
    $username = sanitize_text_field($_POST['username']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    
    if (empty($username) || empty($email) || empty($password)) {
        wp_send_json_error(array('message' => 'Please fill in all required fields'));
    }
    
    if (username_exists($username)) {
        wp_send_json_error(array('message' => 'Username already exists'));
    }
    
    if (email_exists($email)) {
        wp_send_json_error(array('message' => 'Email already registered'));
    }
    
    if (strlen($password) < 8) {
        wp_send_json_error(array('message' => 'Password must be at least 8 characters'));
    }
    
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error(array('message' => $user_id->get_error_message()));
    }
    
    wp_update_user(array(
        'ID' => $user_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'display_name' => $first_name . ' ' . $last_name
    ));
    
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    
    cinema_send_welcome_email($user_id, $username, $email);
    
    wp_send_json_success(array(
        'message' => 'Registration successful!',
        'redirect_url' => isset($_POST['redirect_to']) ? $_POST['redirect_to'] : home_url('/movies')
    ));
}

// Forgot Password

/**
 * ========================================================================
 * MOVIE INTERACTIONS (Ratings / Watchlist / Favorites) - AJAX Endpoints
 * ========================================================================
 */

// Add or update an interaction (rating, watchlist, favorite)
add_action('wp_ajax_cinema_add_interaction', 'cinema_add_interaction_ajax');
function cinema_add_interaction_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in to perform this action', 'code' => 'not_logged_in'));
    }

    global $wpdb;
    $table = $wpdb->prefix . 'cinema_movie_interactions';

    $user_id = get_current_user_id();
    $movie_id = isset($_POST['movie_id']) ? intval($_POST['movie_id']) : 0;
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
    $value = isset($_POST['value']) ? intval($_POST['value']) : null;
    $review = isset($_POST['review']) ? sanitize_textarea_field($_POST['review']) : null;

    if (!$movie_id || !in_array($type, array('rating','watchlist','favorite'))) {
        wp_send_json_error(array('message' => 'Invalid parameters', 'code' => 'invalid_params'));
    }

    // Ratings: enforce value between 1-5
    if ($type === 'rating') {
        if ($value === null || $value < 1 || $value > 5) {
            wp_send_json_error(array('message' => 'Invalid rating value', 'code' => 'invalid_rating'));
        }
    } else {
        // For watchlist/favorite value is null
        $value = null;
    }

    // Try to insert; if exists, update (ratings can be updated, watchlist/favorite should be idempotent)
    $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d AND movie_id = %d AND interaction_type = %s", $user_id, $movie_id, $type));

    if ($existing) {
        // Update
        $updated = $wpdb->update($table, array(
            'value' => $value,
            'review' => $review,
            'updated_at' => current_time('mysql', 1)
        ), array('id' => $existing->id), array('%d','%s','%s'), array('%d'));

        if ($updated === false) {
            wp_send_json_error(array('message' => 'Database error updating interaction'));
        }

        wp_send_json_success(array('message' => 'Interaction updated', 'action' => 'updated'));
    } else {
        $inserted = $wpdb->insert($table, array(
            'user_id' => $user_id,
            'movie_id' => $movie_id,
            'interaction_type' => $type,
            'value' => $value,
            'review' => $review,
            'created_at' => current_time('mysql', 1),
            'updated_at' => current_time('mysql', 1)
        ), array('%d','%d','%s','%d','%s','%s','%s'));

        if ($inserted === false) {
            wp_send_json_error(array('message' => 'Database error inserting interaction'));
        }

        wp_send_json_success(array('message' => 'Interaction created', 'action' => 'created'));
    }
}

// Remove an interaction (used for toggling watchlist/favorite)
add_action('wp_ajax_cinema_remove_interaction', 'cinema_remove_interaction_ajax');
function cinema_remove_interaction_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in to perform this action', 'code' => 'not_logged_in'));
    }

    global $wpdb;
    $table = $wpdb->prefix . 'cinema_movie_interactions';

    $user_id = get_current_user_id();
    $movie_id = isset($_POST['movie_id']) ? intval($_POST['movie_id']) : 0;
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';

    if (!$movie_id || !in_array($type, array('watchlist','favorite','rating'))) {
        wp_send_json_error(array('message' => 'Invalid parameters', 'code' => 'invalid_params'));
    }

    $deleted = $wpdb->delete($table, array('user_id' => $user_id, 'movie_id' => $movie_id, 'interaction_type' => $type), array('%d','%d','%s'));

    if ($deleted === false) {
        wp_send_json_error(array('message' => 'Database error deleting interaction'));
    }

    wp_send_json_success(array('message' => 'Interaction removed'));
}

// Get aggregated stats for a movie
add_action('wp_ajax_cinema_get_movie_stats', 'cinema_get_movie_stats_ajax');
add_action('wp_ajax_nopriv_cinema_get_movie_stats', 'cinema_get_movie_stats_ajax');
function cinema_get_movie_stats_ajax() {
    global $wpdb;
    $table = $wpdb->prefix . 'cinema_movie_interactions';

    $movie_id = isset($_REQUEST['movie_id']) ? intval($_REQUEST['movie_id']) : 0;
    if (!$movie_id) {
        wp_send_json_error(array('message' => 'Invalid movie id', 'code' => 'invalid_params'));
    }

    // Average rating and count
    $rating_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE movie_id = %d AND interaction_type = %s", $movie_id, 'rating'));
    $rating_sum = $wpdb->get_var($wpdb->prepare("SELECT SUM(value) FROM $table WHERE movie_id = %d AND interaction_type = %s", $movie_id, 'rating'));
    $average = $rating_count ? round($rating_sum / $rating_count, 2) : 0;

    // Watchlist count
    $watchlist_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE movie_id = %d AND interaction_type = %s", $movie_id, 'watchlist'));

    // Favorite count
    $favorite_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE movie_id = %d AND interaction_type = %s", $movie_id, 'favorite'));

    wp_send_json_success(array(
        'rating' => array('average' => floatval($average), 'count' => intval($rating_count)),
        'watchlist_count' => intval($watchlist_count),
        'favorite_count' => intval($favorite_count)
    ));
}

// Get current user's interactions for a specific movie
add_action('wp_ajax_cinema_get_user_interaction', 'cinema_get_user_interaction_ajax');
function cinema_get_user_interaction_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in', 'code' => 'not_logged_in'));
    }

    global $wpdb;
    $table = $wpdb->prefix . 'cinema_movie_interactions';

    $user_id = get_current_user_id();
    $movie_id = isset($_REQUEST['movie_id']) ? intval($_REQUEST['movie_id']) : 0;

    if (!$movie_id) wp_send_json_error(array('message' => 'Invalid movie id', 'code' => 'invalid_params'));

    $rows = $wpdb->get_results($wpdb->prepare("SELECT interaction_type, value, review FROM $table WHERE user_id = %d AND movie_id = %d", $user_id, $movie_id), ARRAY_A);

    $out = array();
    foreach ($rows as $r) {
        $out[$r['interaction_type']] = array('value' => $r['value'], 'review' => $r['review']);
    }

    wp_send_json_success($out);
}
add_action('wp_ajax_nopriv_cinema_forgot_password', 'cinema_forgot_password_ajax');
function cinema_forgot_password_ajax() {
    check_ajax_referer('cinema_auth_nonce', 'nonce');
    
    $email = sanitize_email($_POST['email']);
    
    if (!is_email($email)) {
        wp_send_json_error(array('message' => 'Invalid email address'));
    }
    
    $user = get_user_by('email', $email);
    
    if (!$user) {
        wp_send_json_error(array('message' => 'No user found with this email'));
    }
    
    $reset_key = get_password_reset_key($user);
    
    if (is_wp_error($reset_key)) {
        wp_send_json_error(array('message' => $reset_key->get_error_message()));
    }
    
    cinema_send_password_reset_email($user, $reset_key);
    
    wp_send_json_success(array('message' => 'Password reset link sent to your email'));
}

// Reset Password
add_action('wp_ajax_nopriv_cinema_reset_password', 'cinema_reset_password_ajax');
function cinema_reset_password_ajax() {
    check_ajax_referer('cinema_auth_nonce', 'nonce');
    
    $key = sanitize_text_field($_POST['key']);
    $login = sanitize_text_field($_POST['login']);
    $password = $_POST['password'];
    
    $user = check_password_reset_key($key, $login);
    
    if (is_wp_error($user)) {
        wp_send_json_error(array('message' => 'Invalid or expired reset link'));
    }
    
    if (strlen($password) < 8) {
        wp_send_json_error(array('message' => 'Password must be at least 8 characters'));
    }
    
    reset_password($user, $password);
    
    wp_send_json_success(array(
        'message' => 'Password reset successful!',
        'redirect_url' => home_url('/login')
    ));
}

// Send welcome email
function cinema_send_welcome_email($user_id, $username, $email) {
    $subject = 'Welcome to ' . get_bloginfo('name');
    $message = sprintf(
        '<h2>Welcome to %s!</h2><p>Hi %s,</p><p>Thank you for registering. Your account has been created successfully.</p><p><strong>Username:</strong> %s</p><p><a href="%s">Start Booking Now</a></p>',
        get_bloginfo('name'), $username, $username, home_url('/movies')
    );
    $headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail($email, $subject, $message, $headers);
}

// Send password reset email
function cinema_send_password_reset_email($user, $reset_key) {
    $reset_url = home_url('/reset-password/?key=' . $reset_key . '&login=' . rawurlencode($user->user_login));
    $subject = 'Password Reset - ' . get_bloginfo('name');
    $message = sprintf(
        '<h2>Password Reset Request</h2><p>Hi %s,</p><p>Click the link below to reset your password:</p><p><a href="%s">Reset Password</a></p><p>Link expires in 24 hours.</p>',
        $user->display_name, $reset_url
    );
    $headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail($user->user_email, $subject, $message, $headers);
}

// Redirect logged-in users
function cinema_redirect_logged_in_users() {
    if (is_user_logged_in()) {
        if (is_page_template('template-login.php') || is_page_template('template-register.php')) {
            wp_redirect(home_url('/movies'));
            exit;
        }
    }
}
add_action('template_redirect', 'cinema_redirect_logged_in_users');

/**
 * ========================================================================
 * USER ACCOUNT AJAX HANDLERS
 * ========================================================================
 */

// Get booking details
add_action('wp_ajax_cinema_get_booking_details', 'cinema_get_booking_details_ajax');
function cinema_get_booking_details_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Please log in'));
    }
    
    $booking_id = intval($_POST['booking_id']);
    
    global $cinema_booking_manager;
    $booking = $cinema_booking_manager->get_booking($booking_id);
    
    if (!$booking || $booking->user_id != get_current_user_id()) {
        wp_send_json_error(array('message' => 'Booking not found'));
    }
    
    $is_modal_request = isset($_POST['for_modal']) && $_POST['for_modal'] == 'true';
    
    if ($is_modal_request) {
        $movie_banner = get_post_meta($booking->movie_id, '_movie_trailer_banner', true);
        
        if (!empty($movie_banner)) {
            $movie_poster = is_numeric($movie_banner) ? wp_get_attachment_image_url((int) $movie_banner, 'medium') : esc_url($movie_banner);
        } else {
            $movie_poster = get_the_post_thumbnail_url($booking->movie_id, 'medium');
        }
        
        // Build seats array with seat_number and seat_type where available
        $seats = [];
        if (isset($booking->seats) && is_array($booking->seats)) {
            foreach ($booking->seats as $s) {
                if (is_object($s)) {
                    $seats[] = array(
                        'seat_number' => $s->seat_number,
                        'seat_type' => isset($s->seat_type) ? $s->seat_type : 'regular'
                    );
                } elseif (is_array($s)) {
                    $seats[] = array(
                        'seat_number' => isset($s['seat_number']) ? $s['seat_number'] : (isset($s['seat']) ? $s['seat'] : ''),
                        'seat_type' => isset($s['seat_type']) ? $s['seat_type'] : (isset($s['type']) ? $s['type'] : 'regular')
                    );
                } else {
                    // string seat identifier
                    $seats[] = array(
                        'seat_number' => $s,
                        'seat_type' => 'regular'
                    );
                }
            }
        }
        
        $is_upcoming = strtotime($booking->show_date . ' ' . $booking->show_time) > time();
        $is_cancelled = $booking->booking_status === 'cancelled';
        
        if ($is_cancelled) {
            $status_class = 'cancelled';
            $status_text = 'Cancelled';
        } elseif ($booking->booking_status === 'pending') {
            $status_class = 'pending';
            $status_text = 'Pending';
        } elseif ($is_upcoming) {
            $status_class = 'confirmed';
            $status_text = 'Confirmed';
        } else {
            $status_class = 'past';
            $status_text = 'Completed';
        }
        
        $ticket_count = count($seats);
        $service_fee = isset($booking->booking_fee) ? floatval($booking->booking_fee) : 0;
        $total_amount = floatval($booking->total_amount);
        $ticket_price = $ticket_count > 0 ? ($total_amount - $service_fee) / $ticket_count : 0;
        
        $modal_data = array(
            'movie_poster' => $movie_poster ?: '',
            'movie_title' => $booking->movie_title,
            'booking_reference' => $booking->booking_reference,
            'status_class' => $status_class,
            'status_text' => $status_text,
            'show_date' => $booking->show_date,
            'show_time' => $booking->show_time,
            'theater' => isset($booking->theater) ? $booking->theater : 'Main Theater',
            'screen' => isset($booking->screen_number) ? 'Screen ' . $booking->screen_number : 'Screen 1',
            'seats' => $seats,
            'ticket_price' => number_format($ticket_price, 2, '.', ''),
            'ticket_count' => $ticket_count,
            'service_fee' => number_format($service_fee, 2, '.', ''),
            'total_amount' => number_format($total_amount, 2, '.', ''),
            'booking_date' => $booking->booking_date,
            'payment_method' => isset($booking->payment_method) ? $booking->payment_method : 'Stripe',
            'transaction_id' => isset($booking->transaction_id) ? $booking->transaction_id : 'N/A'
        );
        
        wp_send_json_success($modal_data);
    } else {
        wp_send_json_success(array('booking' => $booking));
    }
}

// Cancel booking
add_action('wp_ajax_cinema_cancel_booking', 'cinema_cancel_booking_ajax');
function cinema_cancel_booking_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Please login'));
    }
    
    $booking_id = intval($_POST['booking_id']);
    $user_id = get_current_user_id();
    
    global $cinema_booking_manager;
    $booking = $cinema_booking_manager->get_booking($booking_id);
    
    if (!$booking || $booking->user_id != $user_id) {
        wp_send_json_error(array('message' => 'Booking not found'));
    }
    
    $result = $cinema_booking_manager->cancel_booking($booking_id, 'Cancelled by user');
    
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    } else {
        wp_send_json_success(array('message' => 'Booking cancelled successfully'));
    }
}

// Update profile
add_action('wp_ajax_cinema_update_profile', 'cinema_update_profile_ajax');
function cinema_update_profile_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Please login'));
    }
    
    $user_id = get_current_user_id();
    
    $user_data = array(
        'ID' => $user_id,
        'first_name' => sanitize_text_field($_POST['first_name']),
        'last_name' => sanitize_text_field($_POST['last_name']),
        'display_name' => sanitize_text_field($_POST['display_name']),
        'user_email' => sanitize_email($_POST['user_email'])
    );
    
    if (!is_email($user_data['user_email'])) {
        wp_send_json_error(array('message' => 'Invalid email'));
    }
    
    $email_exists = email_exists($user_data['user_email']);
    if ($email_exists && $email_exists != $user_id) {
        wp_send_json_error(array('message' => 'Email already in use'));
    }
    
    $result = wp_update_user($user_data);
    
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }
    
    update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
    
    wp_send_json_success(array('message' => 'Profile updated successfully'));
}

// Change password
add_action('wp_ajax_cinema_change_password', 'cinema_change_password_ajax');
function cinema_change_password_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Please login'));
    }
    
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
        wp_send_json_error(array('message' => 'Current password incorrect'));
    }
    
    if (strlen($new_password) < 8) {
        wp_send_json_error(array('message' => 'Password must be at least 8 characters'));
    }
    
    wp_set_password($new_password, $user_id);
    
    wp_send_json_success(array('message' => 'Password changed successfully'));
}

/**
 * ========================================================================
 * BOOKING CREATION WITH MULTI-SCREEN SUPPORT
 * ========================================================================
 */

// Create booking from payment page
add_action('wp_ajax_cinema_create_booking_payment', 'cinema_create_booking_payment_ajax');
add_action('wp_ajax_nopriv_cinema_create_booking_payment', 'cinema_create_booking_payment_ajax');

function cinema_create_booking_payment_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    if (!session_id()) {
        session_start();
    }
    
    if (!isset($_SESSION['cinema_cart'])) {
        wp_send_json_error(array('message' => 'Cart is empty'));
    }
    
    $cart_items = json_decode(stripslashes($_POST['cart_items']), true);
    $customer_name = sanitize_text_field($_POST['customer_name']);
    $customer_email = sanitize_email($_POST['customer_email']);
    $customer_phone = sanitize_text_field($_POST['customer_phone']);
    
    global $cinema_booking_manager;
    
    $all_bookings = array();
    
    // Group by showtime
    $grouped = array();
    foreach ($cart_items as $item) {
        $showtime_id = $item['showtime_id'];
        if (!isset($grouped[$showtime_id])) {
            $grouped[$showtime_id] = array();
        }
        $grouped[$showtime_id][] = $item;
    }
    
    // Create booking for each showtime group
    foreach ($grouped as $showtime_id => $items) {
        $seats = array();
        foreach ($items as $item) {
            $seats[] = array(
                'number' => $item['seat'],
                'price' => $item['price'],
                'type' => $item['type']
            );
        }
            // Determine if this showtime belongs to an event
            $post_type = get_post_type($showtime_id);
            $booking_type = 'movie';
            $event_id = null;
            if ($post_type === 'event_showtimes') {
                $booking_type = 'event';
                $event_id = get_post_meta($showtime_id, '_event_showtime_event_id', true);
            }

            $booking_data = array(
                'showtime_id' => $showtime_id,
                'seats' => $seats,
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone,
                'booking_type' => $booking_type,
                'event_id' => $event_id
            );
        
        $result = $cinema_booking_manager->create_booking($booking_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        $all_bookings[] = $result;
    }
    
    wp_send_json_success(array(
        'success' => true,
        'booking_id' => $all_bookings[0]['booking_id'],
        'booking_reference' => $all_bookings[0]['booking_reference']
    ));
}

/**
 * ========================================================================
 * SECURITY & VALIDATION
 * ========================================================================
 */

// Rate limiting
function cinema_check_booking_rate_limit() {
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $transient_key = 'cinema_booking_limit_' . md5($user_ip);
    
    $attempts = get_transient($transient_key);
    
    if ($attempts && $attempts >= 5) {
        return new WP_Error('rate_limit', 'Too many attempts. Try again in 1 hour.');
    }
    
    $attempts = $attempts ? $attempts + 1 : 1;
    set_transient($transient_key, $attempts, HOUR_IN_SECONDS);
    
    return true;
}

// Validate booking data
function cinema_validate_booking_data($data) {
    $errors = array();
    
    if (empty($data['showtime_id']) || !is_numeric($data['showtime_id'])) {
        $errors[] = 'Invalid showtime';
    }
    
    if (empty($data['seats']) || !is_array($data['seats'])) {
        $errors[] = 'No seats selected';
    }
    
    if (empty($data['customer_email']) || !is_email($data['customer_email'])) {
        $errors[] = 'Invalid email';
    }
    
    if (empty($data['customer_name'])) {
        $errors[] = 'Name required';
    }
    
    if (count($data['seats']) > 8) {
        $errors[] = 'Maximum 8 seats per booking';
    }
    
    if (!empty($errors)) {
        return new WP_Error('validation_failed', implode(', ', $errors));
    }
    
    return true;
}

/**
 * ========================================================================
 * HELPER FUNCTIONS
 * ========================================================================
 */

// Get movie showtimes
function get_movie_showtimes($movie_id, $date = '') {
    $args = array(
        'post_type' => 'showtimes',
        'meta_query' => array(
            array(
                'key' => '_showtime_movie_id',
                'value' => $movie_id,
                'compare' => '='
            )
        ),
        'posts_per_page' => -1
    );
    
    if ($date) {
        $args['meta_query'][] = array(
            'key' => '_showtime_date',
            'value' => $date,
            'compare' => '='
        );
    }
    
    return get_posts($args);
}

// Format showtime
function format_showtime($time) {
    return date('g:i A', strtotime($time));
}

// Get rating badge
function get_movie_rating_badge($rating) {
    $badge_class = 'rating-badge';
    switch($rating) {
        case 'G': $badge_class .= ' rating-g'; break;
        case 'PG': $badge_class .= ' rating-pg'; break;
        case 'PG-13': $badge_class .= ' rating-pg13'; break;
        case 'R': $badge_class .= ' rating-r'; break;
    }
    return '<span class="' . $badge_class . '">' . $rating . '</span>';
}

/**
 * ========================================================================
 * CRON JOBS
 * ========================================================================
 */

// Clean expired locks
add_action('cinema_clean_expired_locks', 'cinema_clean_expired_locks_cron');
function cinema_clean_expired_locks_cron() {
    global $wpdb;
    $locks_table = $wpdb->prefix . 'cinema_seat_locks';
    
    $wpdb->query($wpdb->prepare(
        "DELETE FROM $locks_table WHERE expires_at < %s",
        current_time('mysql')
    ));
}

if (!wp_next_scheduled('cinema_clean_expired_locks')) {
    wp_schedule_event(time(), 'every_five_minutes', 'cinema_clean_expired_locks');
}

// Custom cron schedule
add_filter('cron_schedules', 'cinema_custom_cron_schedules');
function cinema_custom_cron_schedules($schedules) {
    $schedules['every_five_minutes'] = array(
        'interval' => 300,
        'display' => __('Every 5 Minutes')
    );
    return $schedules;
}

/**
 * ========================================================================
 * BODY CLASSES & PAGE CUSTOMIZATION
 * ========================================================================
 */

// Hide header/footer on specific pages
add_action('wp_head', function () {
    $hide_pages = array(
        'seat-selection', 'My Account', 'Login', 'Register', 
        'Forgot Password', 'Reset Password', 'Payment', 
        'Booking Confirmation', 'Cart', 'event-seat-selection'
    );
    
    foreach ($hide_pages as $page) {
        if (is_page($page) || is_page_template('page-' . strtolower(str_replace(' ', '-', $page)) . '.php')) {
            echo '<style>
                header, footer, .site-header, .site-footer, #masthead, #colophon { 
                    display: none !important; 
                }
                body { 
                    padding-top: 0 !important; 
                    padding-bottom: 0 !important; 
                }
            </style>';
            break;
        }
    }
});

// Add body classes for auth pages
add_filter('body_class', 'cinema_auth_body_class');
function cinema_auth_body_class($classes) {
    $auth_templates = array(
        'template-login.php', 'template-register.php', 
        'template-forgot-password.php', 'template-reset-password.php'
    );
    
    foreach ($auth_templates as $template) {
        if (is_page_template($template)) {
            $classes[] = 'auth-page';
            $classes[] = 'no-header-footer';
            break;
        }
    }
    
    return $classes;
}

/**
 * ========================================================================
 * DEPRECATED/LEGACY HANDLERS (Keep for backward compatibility)
 * ========================================================================
 */

// Legacy seat selection handler
add_action('wp_ajax_cinema_seat_selection', 'cinema_handle_seat_selection');
add_action('wp_ajax_nopriv_cinema_seat_selection', 'cinema_handle_seat_selection');

function cinema_handle_seat_selection() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    $showtime_id = intval($_POST['showtime_id']);
    $selected_seats = sanitize_text_field($_POST['seats']);
    $booking_data = sanitize_text_field($_POST['booking_data']);
    
    if (!session_id()) {
        session_start();
    }
    
    $_SESSION['cinema_booking'] = array(
        'showtime_id' => $showtime_id,
        'selected_seats' => $selected_seats,
        'booking_data' => $booking_data,
        'timestamp' => time()
    );
    
    wp_send_json_success(array(
        'message' => 'Seats selected successfully',
        'seats' => $selected_seats,
        'redirect_url' => home_url('/cart/')
    ));
}

// Legacy remove seat handler
add_action('wp_ajax_cinema_remove_seat', 'cinema_handle_remove_seat');
add_action('wp_ajax_nopriv_cinema_remove_seat', 'cinema_handle_remove_seat');

function cinema_handle_remove_seat() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    $seat_to_remove = sanitize_text_field($_POST['seat']);
    
    if (!session_id()) {
        session_start();
    }
    
    if (isset($_SESSION['cinema_booking'])) {
        $seats = explode(',', $_SESSION['cinema_booking']['selected_seats']);
        $seats = array_filter($seats, function($seat) use ($seat_to_remove) {
            return trim($seat) !== trim($seat_to_remove);
        });
        $_SESSION['cinema_booking']['selected_seats'] = implode(',', $seats);
        
        wp_send_json_success(array('message' => 'Seat removed successfully'));
    }
    
    wp_send_json_error(array('message' => 'Failed to remove seat'));
}

/**
 * ========================================================================
 * ADMIN CUSTOMIZATION
 * ========================================================================
 */

// Add admin notice for multi-screen setup
add_action('admin_notices', 'cinema_multi_screen_admin_notice');
function cinema_multi_screen_admin_notice() {
    global $wpdb;
    $layouts_table = $wpdb->prefix . 'cinema_seat_layouts';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$layouts_table'") === $layouts_table;
    
    if (!$table_exists) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>Cinema Multi-Screen:</strong> Database tables not created. Please deactivate and reactivate the theme to create tables.</p>';
        echo '</div>';
        return;
    }
    
    // Check if layouts are inserted
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $layouts_table");
    
    if ($count == 0) {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>Cinema Multi-Screen:</strong> Setting up seat layouts for 4 screens...</p>';
        echo '</div>';
        cinema_insert_all_screen_layouts();
    } elseif ($count == 4) {
        // Success - don't show anything after setup is complete
    }
}

// Add custom columns to showtimes list
add_filter('manage_showtimes_posts_columns', 'cinema_showtime_columns');
function cinema_showtime_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['movie'] = 'Movie';
    $new_columns['screen'] = 'Screen';
    $new_columns['date'] = 'Date';
    $new_columns['time'] = 'Time';
    $new_columns['price'] = 'Price';
    return $new_columns;
}

add_action('manage_showtimes_posts_custom_column', 'cinema_showtime_column_content', 10, 2);
function cinema_showtime_column_content($column, $post_id) {
    switch ($column) {
        case 'movie':
            $movie_id = get_post_meta($post_id, '_showtime_movie_id', true);
            if ($movie_id) {
                $movie = get_post($movie_id);
                echo $movie ? $movie->post_title : 'N/A';
            }
            break;
        case 'screen':
            $screen = get_post_meta($post_id, '_showtime_screen', true);
            echo $screen ? 'Screen ' . $screen : 'Not Set';
            break;
        case 'date':
            $date = get_post_meta($post_id, '_showtime_date', true);
            echo $date ? date('M d, Y', strtotime($date)) : 'N/A';
            break;
        case 'time':
            $time = get_post_meta($post_id, '_showtime_time', true);
            echo $time ? date('g:i A', strtotime($time)) : 'N/A';
            break;
        case 'price':
            $price = get_post_meta($post_id, '_showtime_ticket_price', true);
            if ($price !== '' && $price !== null) {
                // Ensure numeric and format with 2 decimals, prepend currency symbol
                $formatted = number_format((float) $price, 2);
                echo '$' . $formatted;
            } else {
                echo 'N/A';
            }
            break;
    }
}

/**
 * ========================================================================
 * DEBUGGING TOOLS (Remove in production)
 * ========================================================================
 */

// Force create tables (for development only)
// Uncomment to manually trigger table creation
// add_action('init', 'cinema_force_create_tables');
// function cinema_force_create_tables() {
//     if (isset($_GET['create_cinema_tables']) && current_user_can('manage_options')) {
//         cinema_create_enhanced_tables();
//         wp_die('Database tables created! <a href="' . admin_url() . '">Go to Dashboard</a>');
//     }
// }

/**
 * Get event bookings for a user
 * 
 * @param int $user_id The ID of the user to get bookings for
 * @return array Array of booking objects with event details
 */
function cinema_get_event_bookings($user_id) {
    global $wpdb;
    $table_bookings = $wpdb->prefix . 'cinema_bookings';
    $table_seats = $wpdb->prefix . 'cinema_seats';

    $bookings = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            b.*,
            GROUP_CONCAT(s.seat_number) as seat_numbers,
            e.post_title as event_title,
            e.ID as event_id,
            em.meta_value as venue_name
        FROM $table_bookings b
        LEFT JOIN $table_seats s ON b.id = s.booking_id
        LEFT JOIN {$wpdb->posts} e ON b.event_id = e.ID
        LEFT JOIN {$wpdb->postmeta} em ON e.ID = em.post_id AND em.meta_key = '_event_venue'
        WHERE b.user_id = %d 
        AND b.booking_type = 'event'
        GROUP BY b.id
        ORDER BY b.show_date DESC, b.show_time DESC",
        $user_id
    ));

    // Format the seats array for each booking (handle empty seat_numbers)
    foreach ($bookings as $booking) {
        $seat_numbers = array();
        if (!empty($booking->seat_numbers)) {
            $seat_numbers = array_filter(array_map('trim', explode(',', $booking->seat_numbers)));
        }

        $booking->seats = array_map(function($seat_number) {
            return (object)['seat_number' => $seat_number];
        }, $seat_numbers);

        unset($booking->seat_numbers);
        
        // Add movie_id as null for events to prevent undefined property errors
        $booking->movie_id = null;
        $booking->movie_title = null;
    }

    return $bookings;
}

/**
 * ========================================================================
 * END OF FUNCTIONS.PHP
 * ========================================================================
 * 
 * Multi-Screen Cinema Booking System v2.0
 * - Supports 4 different screens with unique seat layouts
 * - Screen-specific seat configurations based on floor plan
 * - Wheelchair accessibility for all screens
 * - Real-time seat locking and availability
 * - Complete booking management system
 * 
 * Features:
 * ✓ AUDI-A1 (Screen 1): 133 seats with 4 wheelchair spaces
 * ✓ AUDI-A2 (Screen 2): 154 seats with 4 wheelchair spaces
 * ✓ AUDI-A3 (Screen 3): 213 seats with 4 wheelchair spaces
 * ✓ AUDI-A4 (Screen 4): 216 seats with 6 wheelchair spaces
 * ✓ Dynamic seat layout loading
 * ✓ Real-time seat availability updates
 * ✓ 10-minute seat locking
 * ✓ Multi-screen booking support
 * ✓ User authentication & authorization
 * ✓ Stripe payment integration
 * ✓ Booking management & cancellation
 * ✓ Email notifications
 * ✓ Security & rate limiting
 * 
 * Usage:
 * 1. Create a showtime and select Screen Number (1-4)
 * 2. System automatically loads the correct seat layout
 * 3. Users select seats and proceed to payment
 * 4. Bookings are tracked per screen
 * 
 */