<?php
/**
 * REST API Endpoints for Cinema Booking System
 */

add_action('rest_api_init', function() {
    
    // Get available movies
    register_rest_route('cinema/v1', '/movies', array(
        'methods' => 'GET',
        'callback' => 'cinema_api_get_movies',
        'permission_callback' => '__return_true'
    ));
    
    // Get movie details
    register_rest_route('cinema/v1', '/movies/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'cinema_api_get_movie',
        'permission_callback' => '__return_true'
    ));
    
    // Get showtimes for a movie
    register_rest_route('cinema/v1', '/movies/(?P<id>\d+)/showtimes', array(
        'methods' => 'GET',
        'callback' => 'cinema_api_get_showtimes',
        'permission_callback' => '__return_true'
    ));
    
    // Get seat availability
    register_rest_route('cinema/v1', '/showtimes/(?P<id>\d+)/seats', array(
        'methods' => 'GET',
        'callback' => 'cinema_api_get_seats',
        'permission_callback' => '__return_true'
    ));
    
    // Create booking
    register_rest_route('cinema/v1', '/bookings', array(
        'methods' => 'POST',
        'callback' => 'cinema_api_create_booking',
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ));
    
    // Get user bookings
    register_rest_route('cinema/v1', '/bookings/my', array(
        'methods' => 'GET',
        'callback' => 'cinema_api_get_my_bookings',
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ));
});

function cinema_api_get_movies($request) {
    $args = array(
        'post_type' => 'movies',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    );
    
    $movies = get_posts($args);
    $response = array();
    
    foreach ($movies as $movie) {
        $response[] = array(
            'id' => $movie->ID,
            'title' => $movie->post_title,
            'description' => $movie->post_content,
            'poster' => get_the_post_thumbnail_url($movie->ID, 'medium_large'),
            'rating' => get_post_meta($movie->ID, '_movie_rating', true),
            'duration' => get_post_meta($movie->ID, '_movie_duration', true),
            'genre' => get_post_meta($movie->ID, '_movie_genre', true),
            'release_date' => get_post_meta($movie->ID, '_movie_release_date', true)
        );
    }
    
    return rest_ensure_response($response);
}

function cinema_api_get_movie($request) {
    $movie_id = $request['id'];
    $movie = get_post($movie_id);
    
    if (!$movie || $movie->post_type !== 'movies') {
        return new WP_Error('not_found', 'Movie not found', array('status' => 404));
    }
    
    $response = array(
        'id' => $movie->ID,
        'title' => $movie->post_title,
        'description' => $movie->post_content,
        'poster' => get_the_post_thumbnail_url($movie->ID, 'full'),
        'rating' => get_post_meta($movie->ID, '_movie_rating', true),
        'duration' => get_post_meta($movie->ID, '_movie_duration', true),
        'genre' => get_post_meta($movie->ID, '_movie_genre', true),
        'director' => get_post_meta($movie->ID, '_movie_director', true),
        'cast' => get_post_meta($movie->ID, '_movie_cast', true),
        'release_date' => get_post_meta($movie->ID, '_movie_release_date', true),
        'trailer_url' => get_post_meta($movie->ID, '_movie_trailer_url', true)
    );
    
    return rest_ensure_response($response);
}

function cinema_api_get_showtimes($request) {
    $movie_id = $request['id'];
    
    $showtimes = get_posts(array(
        'post_type' => 'showtimes',
        'meta_query' => array(
            array(
                'key' => '_showtime_movie_id',
                'value' => $movie_id
            )
        ),
        'posts_per_page' => -1
    ));
    
    $response = array();
    
    foreach ($showtimes as $showtime) {
        $response[] = array(
            'id' => $showtime->ID,
            'date' => get_post_meta($showtime->ID, '_showtime_date', true),
            'time' => get_post_meta($showtime->ID, '_showtime_time', true),
            'screen' => get_post_meta($showtime->ID, '_showtime_screen', true),
            'price' => get_post_meta($showtime->ID, '_showtime_ticket_price', true)
        );
    }
    
    return rest_ensure_response($response);
}

function cinema_api_get_seats($request) {
    $showtime_id = $request['id'];
    
    global $cinema_booking_manager;
    $availability = $cinema_booking_manager->get_available_seats($showtime_id);
    
    return rest_ensure_response($availability);
}

function cinema_api_create_booking($request) {
    $params = $request->get_json_params();
    
    $booking_data = array(
        'showtime_id' => intval($params['showtime_id']),
        'seats' => $params['seats'],
        'customer_email' => get_userdata(get_current_user_id())->user_email,
        'customer_name' => $params['customer_name'],
        'customer_phone' => $params['customer_phone'] ?? ''
    );
    
    global $cinema_booking_manager;
    $result = $cinema_booking_manager->create_booking($booking_data);
    
    if (is_wp_error($result)) {
        return new WP_Error('booking_failed', $result->get_error_message(), array('status' => 400));
    }
    
    return rest_ensure_response($result);
}

function cinema_api_get_my_bookings($request) {
    $user_id = get_current_user_id();
    
    global $cinema_booking_manager;
    $bookings = $cinema_booking_manager->get_user_bookings($user_id);
    
    return rest_ensure_response($bookings);
}