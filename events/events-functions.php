<?php
/**
 * ========================================================================
 * EVENT BOOKING SYSTEM - STEP 1: REGISTER POST TYPE & TABLES
 * Add this to your functions.php (after the movies post type registration)
 * ========================================================================
 */

// Register Events Custom Post Type
function cinema_register_event_post_type() {
    register_post_type('events', array(
        'labels' => array(
            'name' => 'Events',
            'singular_name' => 'Event',
            'add_new' => 'Add New Event',
            'add_new_item' => 'Add New Event',
            'edit_item' => 'Edit Event',
            'new_item' => 'New Event',
            'view_item' => 'View Event',
            'search_items' => 'Search Events',
            'not_found' => 'No events found',
            'not_found_in_trash' => 'No events found in trash'
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'menu_icon' => 'dashicons-tickets-alt',
        'rewrite' => array('slug' => 'events')
    ));

    // Event Showtimes Custom Post Type
    register_post_type('event_showtimes', array(
        'labels' => array(
            'name' => 'Event Showtimes',
            'singular_name' => 'Event Showtime',
        ),
        'public' => true,
        'supports' => array('title', 'custom-fields'),
        'menu_icon' => 'dashicons-calendar-alt',
        'show_in_menu' => 'edit.php?post_type=events'
    ));
}
add_action('init', 'cinema_register_event_post_type');

/**
 * Create Event Booking Tables (uses same booking tables with event identification)
 * Events will share the cinema_bookings and cinema_seats tables
 * We'll add a booking_type column to differentiate
 */
function cinema_create_event_tables() {
    global $wpdb;
    
    $bookings_table = $wpdb->prefix . 'cinema_bookings';
    
    // Check if booking_type column exists, if not add it
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $bookings_table LIKE 'booking_type'");
    
    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE $bookings_table ADD COLUMN booking_type VARCHAR(20) DEFAULT 'movie' AFTER showtime_id");
    }
    
    // Check if event_id column exists
    $event_column = $wpdb->get_results("SHOW COLUMNS FROM $bookings_table LIKE 'event_id'");
    
    if (empty($event_column)) {
        $wpdb->query("ALTER TABLE $bookings_table ADD COLUMN event_id mediumint(9) DEFAULT NULL AFTER booking_type");
    }
}
add_action('after_switch_theme', 'cinema_create_event_tables');

/**
 * ========================================================================
 * EVENT META BOXES
 * ========================================================================
 */
function cinema_add_event_meta_boxes() {
    add_meta_box('event_details', 'Event Details', 'event_details_callback', 'events', 'normal', 'high');
    add_meta_box('event_showtime_details', 'Event Showtime Details', 'event_showtime_details_callback', 'event_showtimes', 'normal', 'high');
}
add_action('add_meta_boxes', 'cinema_add_event_meta_boxes');

// Event Details Meta Box
function event_details_callback($post) {
    wp_nonce_field('event_details_nonce', 'event_details_nonce');
    
    $organizer = get_post_meta($post->ID, '_event_organizer', true);
    $venue = get_post_meta($post->ID, '_event_venue', true);
    $duration = get_post_meta($post->ID, '_event_duration', true);
    $category = get_post_meta($post->ID, '_event_category', true);
    $event_date = get_post_meta($post->ID, '_event_date', true);
    $event_type = get_post_meta($post->ID, '_event_type', true);
    $banner_image = get_post_meta($post->ID, '_event_banner', true);
    $thumbnail_image = get_post_meta($post->ID, '_event_thumbnail', true);
    $get_tickets_text = get_post_meta($post->ID, '_event_tickets_text', true) ?: 'GET TICKETS';
    
    echo '<table class="form-table">';
    
    // Event Type (Special Screening or Regular Event)
    echo '<tr><th><label for="event_type">Event Type</label></th>';
    echo '<td><select id="event_type" name="event_type">';
    echo '<option value="event"' . selected($event_type, 'event', false) . '>Regular Event</option>';
    echo '<option value="special_screening"' . selected($event_type, 'special_screening', false) . '>Special Screening</option>';
    echo '</select>';
    echo '<p class="description">Choose "Special Screening" for movie-related events like the Handpicked series</p>';
    echo '</td></tr>';
    
    echo '<tr><th><label for="event_organizer">Organizer</label></th>';
    echo '<td><input type="text" id="event_organizer" name="event_organizer" value="' . esc_attr($organizer) . '" size="50" /></td></tr>';
    
    echo '<tr><th><label for="event_venue">Venue</label></th>';
    echo '<td><input type="text" id="event_venue" name="event_venue" value="' . esc_attr($venue) . '" size="50" /></td></tr>';
    
    echo '<tr><th><label for="event_duration">Duration (minutes)</label></th>';
    echo '<td><input type="number" id="event_duration" name="event_duration" value="' . esc_attr($duration) . '" /></td></tr>';
    
    echo '<tr><th><label for="event_category">Category</label></th>';
    echo '<td><input type="text" id="event_category" name="event_category" value="' . esc_attr($category) . '" size="50" placeholder="e.g., Concert, Comedy, Theater" /></td></tr>';
    
    echo '<tr><th><label for="event_date">Event Date</label></th>';
    echo '<td><input type="date" id="event_date" name="event_date" value="' . esc_attr($event_date) . '" /></td></tr>';
    
    echo '<tr><th><label for="event_tickets_text">Button Text</label></th>';
    echo '<td><input type="text" id="event_tickets_text" name="event_tickets_text" value="' . esc_attr($get_tickets_text) . '" size="50" />';
    echo '<p class="description">Text shown on the tickets button (e.g., "GET TICKETS", "BOOK NOW", "REGISTER")</p>';
    echo '</td></tr>';
    
    // Event Banner Image (Large promotional image)
    echo '<tr><th><label for="event_banner">Event Banner Image</label></th><td>';
    echo '<input type="hidden" id="event_banner" name="event_banner" value="' . esc_attr($banner_image) . '" />';
    echo '<button type="button" class="button" id="upload_event_banner">Choose Banner Image</button>';
    echo '<button type="button" class="button" id="remove_event_banner" style="margin-left: 10px; display: ' . ($banner_image ? 'inline-block' : 'none') . ';">Remove</button>';
    echo '<div id="event_banner_preview" style="margin-top: 10px;">';
    if ($banner_image) echo '<img src="' . esc_url(wp_get_attachment_image_url($banner_image, 'large')) . '" style="max-width: 600px;" />';
    echo '</div>';
    echo '<p class="description">Main promotional banner (recommended: 1200x600px)</p>';
    echo '</td></tr>';
    
    // Event Thumbnail
    echo '<tr><th><label for="event_thumbnail">Event Thumbnail</label></th><td>';
    echo '<input type="hidden" id="event_thumbnail" name="event_thumbnail" value="' . esc_attr($thumbnail_image) . '" />';
    echo '<button type="button" class="button" id="upload_event_thumbnail">Choose Thumbnail</button>';
    echo '<button type="button" class="button" id="remove_event_thumbnail" style="margin-left: 10px; display: ' . ($thumbnail_image ? 'inline-block' : 'none') . ';">Remove</button>';
    echo '<div id="event_thumbnail_preview" style="margin-top: 10px;">';
    if ($thumbnail_image) echo '<img src="' . esc_url(wp_get_attachment_image_url($thumbnail_image, 'medium')) . '" style="max-width: 300px;" />';
    echo '</div>';
    echo '<p class="description">Listing page thumbnail (recommended: 400x600px)</p>';
    echo '</td></tr>';
    
    echo '</table>';
    
    // Media uploader scripts
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Banner uploader
        var mediaUploaderBanner;
        $('#upload_event_banner').click(function(e) {
            e.preventDefault();
            if (mediaUploaderBanner) {
                mediaUploaderBanner.open();
                return;
            }
            mediaUploaderBanner = wp.media({
                title: 'Choose Event Banner',
                button: { text: 'Choose Image' },
                multiple: false,
                library: { type: 'image' }
            });
            mediaUploaderBanner.on('select', function() {
                var attachment = mediaUploaderBanner.state().get('selection').first().toJSON();
                $('#event_banner').val(attachment.id);
                $('#event_banner_preview').html('<img src="' + attachment.url + '" style="max-width: 600px;" />');
                $('#remove_event_banner').show();
            });
            mediaUploaderBanner.open();
        });

        $('#remove_event_banner').click(function(e) {
            e.preventDefault();
            $('#event_banner').val('');
            $('#event_banner_preview').html('');
            $(this).hide();
        });

        // Thumbnail uploader
        var mediaUploaderThumb;
        $('#upload_event_thumbnail').click(function(e) {
            e.preventDefault();
            if (mediaUploaderThumb) {
                mediaUploaderThumb.open();
                return;
            }
            mediaUploaderThumb = wp.media({
                title: 'Choose Event Thumbnail',
                button: { text: 'Choose Image' },
                multiple: false,
                library: { type: 'image' }
            });
            mediaUploaderThumb.on('select', function() {
                var attachment = mediaUploaderThumb.state().get('selection').first().toJSON();
                $('#event_thumbnail').val(attachment.id);
                $('#event_thumbnail_preview').html('<img src="' + attachment.url + '" style="max-width: 300px;" />');
                $('#remove_event_thumbnail').show();
            });
            mediaUploaderThumb.open();
        });

        $('#remove_event_thumbnail').click(function(e) {
            e.preventDefault();
            $('#event_thumbnail').val('');
            $('#event_thumbnail_preview').html('');
            $(this).hide();
        });
    });
    </script>
    <?php
}

// Event Showtime Details Meta Box
function event_showtime_details_callback($post) {
    wp_nonce_field('event_showtime_details_nonce', 'event_showtime_details_nonce');
    
    $event_id = get_post_meta($post->ID, '_event_showtime_event_id', true);
    $screen_number = get_post_meta($post->ID, '_event_showtime_screen', true);
    $show_date = get_post_meta($post->ID, '_event_showtime_date', true);
    $show_time = get_post_meta($post->ID, '_event_showtime_time', true);
    $ticket_price = get_post_meta($post->ID, '_event_showtime_ticket_price', true);
    
    $events = get_posts(array('post_type' => 'events', 'numberposts' => -1));
    
    echo '<table class="form-table">';
    echo '<tr><th><label for="event_showtime_event_id">Event</label></th>';
    echo '<td><select id="event_showtime_event_id" name="event_showtime_event_id"><option value="">Select Event</option>';
    foreach($events as $event) {
        echo '<option value="' . $event->ID . '"' . selected($event_id, $event->ID, false) . '>' . $event->post_title . '</option>';
    }
    echo '</select></td></tr>';
    
    echo '<tr><th><label for="event_showtime_screen">Screen Number</label></th>';
    echo '<td><select id="event_showtime_screen" name="event_showtime_screen" required>';
    echo '<option value="">Select Screen</option>';
    echo '<option value="1"' . selected($screen_number, '1', false) . '>Screen 1 (AUDI-A1 - 133 seats)</option>';
    echo '<option value="2"' . selected($screen_number, '2', false) . '>Screen 2 (AUDI-A2 - 154 seats)</option>';
    echo '<option value="3"' . selected($screen_number, '3', false) . '>Screen 3 (AUDI-A3 - 213 seats)</option>';
    echo '<option value="4"' . selected($screen_number, '4', false) . '>Screen 4 (AUDI-A4 - 216 seats)</option>';
    echo '</select></td></tr>';
    
    echo '<tr><th><label for="event_showtime_date">Show Date</label></th>';
    echo '<td><input type="date" id="event_showtime_date" name="event_showtime_date" value="' . esc_attr($show_date) . '" /></td></tr>';
    
    echo '<tr><th><label for="event_showtime_time">Show Time</label></th>';
    echo '<td><input type="time" id="event_showtime_time" name="event_showtime_time" value="' . esc_attr($show_time) . '" /></td></tr>';
    
    echo '<tr><th><label for="event_showtime_ticket_price">Ticket Price ($)</label></th>';
    echo '<td><input type="number" id="event_showtime_ticket_price" name="event_showtime_ticket_price" value="' . esc_attr($ticket_price) . '" step="0.01" /></td></tr>';
    
    echo '</table>';
}

// Save Event Meta Boxes
function cinema_save_event_meta_boxes($post_id) {
    // Event meta
    if (isset($_POST['event_details_nonce']) && wp_verify_nonce($_POST['event_details_nonce'], 'event_details_nonce')) {
        $fields = array('organizer', 'venue', 'duration', 'category', 'date', 'type', 'banner', 'thumbnail', 'tickets_text');
        foreach($fields as $field) {
            if (isset($_POST['event_' . $field])) {
                update_post_meta($post_id, '_event_' . $field, sanitize_text_field($_POST['event_' . $field]));
            }
        }
    }
    
    // Event Showtime meta
    if (isset($_POST['event_showtime_details_nonce']) && wp_verify_nonce($_POST['event_showtime_details_nonce'], 'event_showtime_details_nonce')) {
        if (isset($_POST['event_showtime_event_id'])) {
            update_post_meta($post_id, '_event_showtime_event_id', intval($_POST['event_showtime_event_id']));
        }
        if (isset($_POST['event_showtime_screen'])) {
            $screen = intval($_POST['event_showtime_screen']);
            if ($screen >= 1 && $screen <= 4) {
                update_post_meta($post_id, '_event_showtime_screen', $screen);
            }
        }
        if (isset($_POST['event_showtime_date'])) {
            update_post_meta($post_id, '_event_showtime_date', sanitize_text_field($_POST['event_showtime_date']));
        }
        if (isset($_POST['event_showtime_time'])) {
            update_post_meta($post_id, '_event_showtime_time', sanitize_text_field($_POST['event_showtime_time']));
        }
        if (isset($_POST['event_showtime_ticket_price'])) {
            update_post_meta($post_id, '_event_showtime_ticket_price', floatval($_POST['event_showtime_ticket_price']));
        }
    }
}
add_action('save_post', 'cinema_save_event_meta_boxes');




/**
 * ========================================================================
 * EVENT BOOKING AJAX HANDLERS
 * Add these to your functions.php
 * ========================================================================
 */

/**
 * Create Event Booking (Modified from movie booking)
 */
add_action('wp_ajax_cinema_create_event_booking', 'cinema_create_event_booking_ajax');
add_action('wp_ajax_nopriv_cinema_create_event_booking', 'cinema_create_event_booking_ajax');

function cinema_create_event_booking_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    if (!session_id()) {
        session_start();
    }
    
    $showtime_id = intval($_POST['showtime_id']);
    $event_id = intval($_POST['event_id']);
    $seat_numbers = isset($_POST['seat_numbers']) ? $_POST['seat_numbers'] : array();
    $customer_name = sanitize_text_field($_POST['customer_name']);
    $customer_email = sanitize_email($_POST['customer_email']);
    $customer_phone = sanitize_text_field($_POST['customer_phone']);
    $total_amount = floatval($_POST['total_amount']);
    
    if (!$showtime_id || !$event_id || empty($seat_numbers)) {
        wp_send_json_error(array('message' => 'Invalid booking data'));
    }
    
    $screen_number = get_post_meta($showtime_id, '_event_showtime_screen', true);
    $show_date = get_post_meta($showtime_id, '_event_showtime_date', true);
    $show_time = get_post_meta($showtime_id, '_event_showtime_time', true);
    $user_id = is_user_logged_in() ? get_current_user_id() : 0;
    
    global $wpdb;
    $bookings_table = $wpdb->prefix . 'cinema_bookings';
    $seats_table = $wpdb->prefix . 'cinema_seats';
    $locks_table = $wpdb->prefix . 'cinema_seat_locks';
    
    // Start transaction
    $wpdb->query('START TRANSACTION');
    
    try {
        // Check seats availability
        foreach ($seat_numbers as $seat_number) {
            if (cinema_is_seat_permanently_booked($showtime_id, $screen_number, $seat_number)) {
                throw new Exception('Seat ' . $seat_number . ' is already booked');
            }
        }
        
        // Generate booking reference
        $booking_reference = 'EV' . strtoupper(uniqid());
        
        // Insert booking record
        $wpdb->insert($bookings_table, array(
            'booking_reference' => $booking_reference,
            'showtime_id' => $showtime_id,
            'booking_type' => 'event',
            'event_id' => $event_id,
            'screen_number' => $screen_number,
            'user_id' => $user_id,
            'customer_email' => $customer_email,
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone,
            'show_date' => $show_date,
            'show_time' => $show_time,
            'total_amount' => $total_amount,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
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
        
        // Release temporary locks
        $session_id = session_id();
        $wpdb->delete($locks_table, array(
            'showtime_id' => $showtime_id,
            'session_id' => $session_id
        ));
        
        // Commit transaction
        $wpdb->query('COMMIT');
        
        // Send confirmation email
        cinema_send_event_booking_email($booking_id, $booking_reference, $customer_email, $customer_name, $event_id);
        
        wp_send_json_success(array(
            'message' => 'Event booking confirmed successfully',
            'booking_id' => $booking_id,
            'booking_reference' => $booking_reference
        ));
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => $e->getMessage()));
    }
}

/**
 * Send Event Booking Confirmation Email
 */
function cinema_send_event_booking_email($booking_id, $booking_reference, $email, $name, $event_id) {
    global $wpdb;
    $bookings_table = $wpdb->prefix . 'cinema_bookings';
    $seats_table = $wpdb->prefix . 'cinema_seats';
    
    $booking = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $bookings_table WHERE id = %d",
        $booking_id
    ));
    
    $seats = $wpdb->get_col($wpdb->prepare(
        "SELECT seat_number FROM $seats_table WHERE booking_id = %d",
        $booking_id
    ));
    
    $event_title = get_the_title($event_id);
    $venue = get_post_meta($event_id, '_event_venue', true);
    
    $subject = 'Event Booking Confirmation - ' . $event_title;
    
    $message = '<html><body style="font-family: Arial, sans-serif;">';
    $message .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9;">';
    $message .= '<h1 style="color: #0066cc;">Event Booking Confirmed!</h1>';
    $message .= '<p>Hi ' . esc_html($name) . ',</p>';
    $message .= '<p>Your booking for <strong>' . esc_html($event_title) . '</strong> has been confirmed.</p>';
    
    $message .= '<div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">';
    $message .= '<h3 style="margin-top: 0;">Booking Details</h3>';
    $message .= '<p><strong>Booking Reference:</strong> ' . esc_html($booking_reference) . '</p>';
    $message .= '<p><strong>Event:</strong> ' . esc_html($event_title) . '</p>';
    $message .= '<p><strong>Date:</strong> ' . date('l, F j, Y', strtotime($booking->show_date)) . '</p>';
    $message .= '<p><strong>Time:</strong> ' . date('g:i A', strtotime($booking->show_time)) . '</p>';
    if ($venue) {
        $message .= '<p><strong>Venue:</strong> ' . esc_html($venue) . '</p>';
    }
    $message .= '<p><strong>Screen:</strong> ' . $booking->screen_number . '</p>';
    $message .= '<p><strong>Seats:</strong> ' . implode(', ', $seats) . '</p>';
    $message .= '<p><strong>Total Amount:</strong> $' . number_format($booking->total_amount, 2) . '</p>';
    $message .= '</div>';
    
    $message .= '<p style="color: #666; font-size: 12px;">Please arrive 15 minutes before the event starts. Show this email at the entrance.</p>';
    $message .= '<p>Thank you for booking with ' . get_bloginfo('name') . '!</p>';
    $message .= '</div></body></html>';
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    wp_mail($email, $subject, $message, $headers);
}

/**
 * Get Event Bookings for User Account Page
 */
add_action('wp_ajax_cinema_get_event_bookings', 'cinema_get_event_bookings_ajax');

function cinema_get_event_bookings_ajax() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Please log in'));
    }
    
    $user_id = get_current_user_id();
    
    global $wpdb;
    $bookings_table = $wpdb->prefix . 'cinema_bookings';
    $seats_table = $wpdb->prefix . 'cinema_seats';
    
    $bookings = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $bookings_table 
        WHERE user_id = %d 
        AND booking_type = 'event'
        ORDER BY booking_date DESC",
        $user_id
    ));
    
    $formatted_bookings = array();
    
    foreach ($bookings as $booking) {
        $seats = $wpdb->get_col($wpdb->prepare(
            "SELECT seat_number FROM $seats_table WHERE booking_id = %d",
            $booking->id
        ));
        
        $event_title = get_the_title($booking->event_id);
        $event_venue = get_post_meta($booking->event_id, '_event_venue', true);
        
        $formatted_bookings[] = array(
            'booking_id' => $booking->id,
            'booking_reference' => $booking->booking_reference,
            'event_title' => $event_title,
            'event_venue' => $event_venue,
            'show_date' => $booking->show_date,
            'show_time' => $booking->show_time,
            'screen_number' => $booking->screen_number,
            'seats' => $seats,
            'total_amount' => $booking->total_amount,
            'booking_status' => $booking->booking_status,
            'payment_status' => $booking->payment_status,
            'booking_date' => $booking->booking_date
        );
    }
    
    wp_send_json_success(array('bookings' => $formatted_bookings));
}

/**
 * Helper function to get event showtimes
 */
function get_event_showtimes($event_id, $date = '') {
    $args = array(
        'post_type' => 'event_showtimes',
        'meta_query' => array(
            array(
                'key' => '_event_showtime_event_id',
                'value' => $event_id,
                'compare' => '='
            )
        ),
        'posts_per_page' => -1
    );
    
    if ($date) {
        $args['meta_query'][] = array(
            'key' => '_event_showtime_date',
            'value' => $date,
            'compare' => '='
        );
    }
    
    return get_posts($args);
}

/**
 * Add custom columns to event showtimes list in admin
 */
add_filter('manage_event_showtimes_posts_columns', 'cinema_event_showtime_columns');
function cinema_event_showtime_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['event'] = 'Event';
    $new_columns['screen'] = 'Screen';
    $new_columns['date'] = 'Date';
    $new_columns['time'] = 'Time';
    $new_columns['price'] = 'Price';
    return $new_columns;
}

add_action('manage_event_showtimes_posts_custom_column', 'cinema_event_showtime_column_content', 10, 2);
function cinema_event_showtime_column_content($column, $post_id) {
    switch ($column) {
        case 'event':
            $event_id = get_post_meta($post_id, '_event_showtime_event_id', true);
            if ($event_id) {
                $event = get_post($event_id);
                echo $event ? $event->post_title : 'N/A';
            }
            break;
        case 'screen':
            $screen = get_post_meta($post_id, '_event_showtime_screen', true);
            echo $screen ? 'Screen ' . $screen : 'Not Set';
            break;
        case 'date':
            $date = get_post_meta($post_id, '_event_showtime_date', true);
            echo $date ? date('M d, Y', strtotime($date)) : 'N/A';
            break;
        case 'time':
            $time = get_post_meta($post_id, '_event_showtime_time', true);
            echo $time ? date('g:i A', strtotime($time)) : 'N/A';
            break;
        case 'price':
            $price = get_post_meta($post_id, '_event_showtime_ticket_price', true);
            echo $price !== '' ? '$' . number_format((float)$price, 2) : 'N/A';
            break;
    }
}