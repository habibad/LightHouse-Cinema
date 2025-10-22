<?php
/*
Template Name: My Account
*/

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/?redirect_to=' . urlencode($_SERVER['REQUEST_URI'])));
    exit;
}

get_header();

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get all user bookings and split into movie vs event
global $cinema_booking_manager;
$all_bookings = $cinema_booking_manager->get_user_bookings($user_id);

// Keep an overall merged list for dashboard stats (recent bookings)
$bookings = $all_bookings;
?>

<Script>
    console.log('Loaded My Account Template');
    console.log(<?php echo json_encode($all_bookings); ?>);
</Script>

<?php


// Partition into movie and event bookings
$movie_bookings = array_filter($all_bookings, function($b) {
    return !isset($b->booking_type) || $b->booking_type !== 'event';
});

$event_bookings = array_filter($all_bookings, function($b) {
    return isset($b->booking_type) && $b->booking_type === 'event';
});

// Reindex arrays for consistent ordering/indexes
$movie_bookings = array_values($movie_bookings);
$event_bookings = array_values($event_bookings);

?>

<Script>
    console.log('Movie Bookings:', <?php echo json_encode($movie_bookings); ?>);
    console.log('Event Bookings:', <?php echo json_encode($event_bookings); ?>);
</Script>
<?php

// Separate bookings by status
// Dashboard aggregates (all bookings)
$upcoming_bookings = array_filter($bookings, function($booking) {
    return in_array($booking->booking_status, ['confirmed', 'pending']) && 
           strtotime($booking->show_date . ' ' . $booking->show_time) > time();
});

$past_bookings = array_filter($bookings, function($booking) {
    return in_array($booking->booking_status, ['confirmed']) &&
           strtotime($booking->show_date . ' ' . $booking->show_time) <= time();
});

$cancelled_bookings = array_filter($bookings, function($booking) {
    return $booking->booking_status === 'cancelled';
});

$pending_bookings = array_filter($bookings, function($booking) {
    return $booking->booking_status === 'pending';
});

// Movie-specific filtered lists for the Movie Bookings tab
$movie_upcoming = array_filter($movie_bookings, function($booking) {
    return in_array($booking->booking_status, ['confirmed', 'pending']) && strtotime($booking->show_date . ' ' . $booking->show_time) > time();
});

$movie_past = array_filter($movie_bookings, function($booking) {
    return in_array($booking->booking_status, ['confirmed']) && strtotime($booking->show_date . ' ' . $booking->show_time) <= time();
});

$movie_cancelled = array_filter($movie_bookings, function($booking) {
    return $booking->booking_status === 'cancelled';
});

$movie_pending = array_filter($movie_bookings, function($booking) {
    return $booking->booking_status === 'pending';
});

// Event-specific filtered lists for the Event Bookings tab
$event_upcoming = array_filter($event_bookings, function($booking) {
    return in_array($booking->booking_status, ['confirmed', 'pending']) && strtotime($booking->show_date . ' ' . $booking->show_time) > time();
});

$event_past = array_filter($event_bookings, function($booking) {
    return in_array($booking->booking_status, ['confirmed']) && strtotime($booking->show_date . ' ' . $booking->show_time) <= time();
});

$event_cancelled = array_filter($event_bookings, function($booking) {
    return $booking->booking_status === 'cancelled';
});

$event_pending = array_filter($event_bookings, function($booking) {
    return $booking->booking_status === 'pending';
});
?>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
        <div class="user-profile-card">
            <div class="user-avatar-large">
                <?php echo get_avatar($user_id, 120); ?>
            </div>
            <h3><?php echo esc_html($current_user->display_name); ?></h3>
            <p class="user-email"><?php echo esc_html($current_user->user_email); ?></p>
        </div>

        <nav class="dashboard-nav">
            <a href="#" class="nav-item active" data-tab="dashboard">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" />
                </svg>
                <span>Dashboard</span>
            </a>
            <a href="#" class="nav-item" data-tab="bookings">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M22 10V6c0-1.11-.9-2-2-2H4c-1.11 0-2 .89-2 2v4c1.11 0 2 .89 2 2s-.89 2-2 2v4c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2v-4c-1.11 0-2-.89-2-2s.89-2 2-2z" />
                </svg>
                <span>Movie Bookings</span>
            </a>
            <a href="#" class="nav-item" data-tab="event-bookings">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                </svg>
                <span>Event Bookings</span>
            </a>
            <a href="#" class="nav-item" data-tab="profile">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                </svg>
                <span>Profile Settings</span>
            </a>
            <a href="#" class="nav-item" data-tab="logout">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z" />
                </svg>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <!-- Dashboard Tab -->
        <div id="dashboard-tab" class="tab-panel active">
            <div class="page-header">
                <h1>Dashboard</h1>
                <a href="<?php echo home_url('/movies'); ?>" class="btn btn-primary">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                    Book New Ticket
                </a>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon">
                        <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M22 10V6c0-1.11-.9-2-2-2H4c-1.11 0-2 .89-2 2v4c1.11 0 2 .89 2 2s-.89 2-2 2v4c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2v-4c-1.11 0-2-.89-2-2s.89-2 2-2z" />
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h2><?php echo count($upcoming_bookings); ?></h2>
                        <p>Upcoming</p>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">
                        <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h2><?php echo count($past_bookings); ?></h2>
                        <p>Completed</p>
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-icon">
                        <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z" />
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h2><?php echo count($pending_bookings); ?></h2>
                        <p>Pending</p>
                    </div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-icon">
                        <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z" />
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h2>$<?php 
                            $total_spent = array_sum(array_map(function($b) { 
                                return $b->total_amount; 
                            }, $bookings));
                            echo number_format($total_spent, 2);
                        ?></h2>
                        <p>Total Spent</p>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="section-card">
                <div class="section-header">
                    <h2>Recent Bookings</h2>
                    <a href="#" class="view-all-link" data-tab-trigger="bookings">View All</a>
                </div>

                <div class="bookings-grid">
                    <?php if (!empty($upcoming_bookings)) : ?>
                    <?php foreach (array_slice($upcoming_bookings, 0, 3) as $booking) : ?>
                    <?php echo render_modern_booking_card($booking, true); ?>
                    <?php endforeach; ?>
                    <?php else : ?>
                    <div class="empty-state">
                        <svg width="80" height="80" fill="#ddd" viewBox="0 0 24 24">
                            <path
                                d="M22 10V6c0-1.11-.9-2-2-2H4c-1.11 0-2 .89-2 2v4c1.11 0 2 .89 2 2s-.89 2-2 2v4c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2v-4c-1.11 0-2-.89-2-2s.89-2 2-2z" />
                        </svg>
                        <h3>No Upcoming Bookings</h3>
                        <p>Ready to watch a movie?</p>
                        <a href="<?php echo home_url('/movies'); ?>" class="btn btn-primary">Browse Movies</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- My Bookings Tab -->
        <div id="bookings-tab" class="tab-panel">
            <div class="page-header">
                <h1>My Bookings</h1>
                <div class="booking-filters">
                    <button class="filter-chip active" data-filter="all">All</button>
                    <button class="filter-chip" data-filter="upcoming">Upcoming</button>
                    <button class="filter-chip" data-filter="past">Past</button>
                    <button class="filter-chip" data-filter="cancelled">Cancelled</button>
                </div>
            </div>

            <div class="bookings-container">
                <div class="bookings-list" data-section="all">
                    <?php if (!empty($movie_bookings)) : ?>
                        <?php foreach ($movie_bookings as $booking) : ?>
                            <?php echo render_modern_booking_card($booking, true); ?>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="empty-state">
                            <p>No movie bookings found</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="bookings-list" data-section="upcoming" style="display: none;">
                    <?php if (!empty($movie_upcoming)) : ?>
                        <?php foreach ($movie_upcoming as $booking) : ?>
                            <?php echo render_modern_booking_card($booking, true); ?>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="empty-state">
                            <p>No upcoming movie bookings</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="bookings-list" data-section="past" style="display: none;">
                    <?php if (!empty($movie_past)) : ?>
                        <?php foreach ($movie_past as $booking) : ?>
                            <?php echo render_modern_booking_card($booking, false); ?>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="empty-state">
                            <p>No past movie bookings</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="bookings-list" data-section="cancelled" style="display: none;">
                    <?php if (!empty($movie_cancelled)) : ?>
                        <?php foreach ($movie_cancelled as $booking) : ?>
                            <?php echo render_modern_booking_card($booking, false); ?>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="empty-state">
                            <p>No cancelled movie bookings</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Event Bookings Tab -->
        <div id="event-bookings-tab" class="tab-panel">
            <div class="page-header">
                <h1>Event Bookings</h1>
                <div class="booking-filters">
                    <button class="filter-chip active" data-filter="all">All Events</button>
                    <button class="filter-chip" data-filter="upcoming">Upcoming</button>
                    <button class="filter-chip" data-filter="past">Past</button>
                    <button class="filter-chip" data-filter="cancelled">Cancelled</button>
                </div>
            </div>

            <div class="bookings-container">
                <div class="events-list" data-section="all">
                    <?php if (!empty($event_bookings)) : ?>
                        <?php foreach ($event_bookings as $booking) : ?>
                            <?php echo render_modern_booking_card($booking, true); ?>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="no-bookings">
                            <svg width="80" height="80" fill="#ccc" viewBox="0 0 24 24">
                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                            </svg>
                            <h3>No event bookings found</h3>
                            <p>You haven't made any event bookings yet.</p>
                            <a href="<?php echo home_url('/events/'); ?>" class="btn btn-primary">Browse Events</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Profile Settings Tab -->
        <div id="profile-tab" class="tab-panel">
            <div class="page-header">
                <h1>Profile Settings</h1>
            </div>

            <div class="profile-grid">
                <div class="section-card">
                    <h3>Personal Information</h3>
                    <form id="profile-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" id="first_name" class="form-input"
                                    value="<?php echo esc_attr($current_user->first_name); ?>">
                            </div>
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" id="last_name" class="form-input"
                                    value="<?php echo esc_attr($current_user->last_name); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Display Name</label>
                            <input type="text" id="display_name" class="form-input"
                                value="<?php echo esc_attr($current_user->display_name); ?>">
                        </div>

                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" id="user_email" class="form-input"
                                value="<?php echo esc_attr($current_user->user_email); ?>">
                        </div>

                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" id="phone" class="form-input"
                                value="<?php echo esc_attr(get_user_meta($user_id, 'phone', true)); ?>">
                        </div>

                        <button type="submit" class="btn btn-primary" id="update-profile-btn">Save Changes</button>
                    </form>
                </div>

                <div class="section-card">
                    <h3>Change Password</h3>
                    <form id="password-form">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" id="current_password" class="form-input">
                        </div>

                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" id="new_password" class="form-input">
                            <small>At least 8 characters</small>
                        </div>

                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" id="confirm_new_password" class="form-input">
                        </div>

                        <button type="submit" class="btn btn-primary" id="change-password-btn">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php
function render_modern_booking_card($booking, $show_actions) {
    // $is_event = isset($booking->booking_type) && $booking->booking_type === 'event';
    
    $is_event = isset($booking->booking_type) && $booking->booking_type === 'event';
    
    // Safety checks for event bookings
    if ($is_event) {
        if (!isset($booking->event_title) && isset($booking->event_id)) {
            $booking->event_title = get_the_title($booking->event_id);
        }
        if (!isset($booking->venue_name) && isset($booking->event_id)) {
            $booking->venue_name = get_post_meta($booking->event_id, '_event_venue', true);
        }
    }
    
    // Safety checks for movie bookings
    if (!$is_event) {
        if (!isset($booking->movie_title) && isset($booking->movie_id)) {
            $booking->movie_title = get_the_title($booking->movie_id);
        }
    }
    
    // Rest of your existing function code...
    if ($is_event) {
        // Try to get event banner first, fallback to featured image
        $event_banner = get_post_meta($booking->event_id, '_event_banner', true);
        
        if (!empty($event_banner)) {
            // If it's an attachment ID
            if (is_numeric($event_banner)) {
                $banner = wp_get_attachment_image_url((int) $event_banner, 'medium');
            } else {
                // If it's a URL
                $banner = esc_url($event_banner);
            }
        } else {
            // Fallback to featured image
            $banner = get_the_post_thumbnail_url($booking->event_id, 'medium');
        }
    } 
    else {
        // Try to get movie trailer banner first, fallback to featured image
        $movie_banner = get_post_meta($booking->movie_id, '_movie_trailer_banner', true);
        
        if (!empty($movie_banner)) {
            // If it's an attachment ID
            if (is_numeric($movie_banner)) {
                $banner = wp_get_attachment_image_url((int) $movie_banner, 'medium');
            } else {
                // If it's a URL
                $banner = esc_url($movie_banner);
            }
        } else {
            // Fallback to featured image
            $banner = get_the_post_thumbnail_url($booking->movie_id, 'medium');
        }
    }
    
    $seat_numbers = array_map(function($seat) { return $seat->seat_number; }, $booking->seats);
    
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
    
    $can_cancel = $booking->booking_status === 'confirmed' && $is_upcoming;
    
    ob_start();
    ?>
<div class="booking-card-modern <?php echo $is_event ? 'event-booking' : 'movie-booking'; ?>">
    <div class="booking-poster">
        <?php if ($banner) : ?>
        <img src="<?php echo $banner; ?>" alt="<?php echo esc_attr($is_event ? $booking->event_title : $booking->movie_title); ?>">
        <?php else : ?>
        <div class="poster-placeholder">
            <svg width="60" height="60" fill="#999" viewBox="0 0 24 24">
                <path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z" />
            </svg>
        </div>
        <?php endif; ?>
        <div class="status-badge status-<?php echo $status_class; ?>"><?php echo $status_text; ?></div>
        <?php if ($is_event) : ?>
        <div class="booking-type-badge">Event</div>
        <?php endif; ?>
    </div>

    <div class="booking-info">

        <h3><?php echo esc_html($is_event ? $booking->event_title : $booking->movie_title); ?></h3>
        <div class="booking-meta">
            <span class="meta-item">
                <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z" />
                </svg>
                <?php echo date('M d, Y', strtotime($booking->show_date)); ?>
            </span>
            <span class="meta-item">
                <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z" />
                </svg>
                <?php echo date('g:i A', strtotime($booking->show_time)); ?>
            </span>
            <?php if ($is_event && isset($booking->venue_name)) : ?>
            <span class="meta-item">
                <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
                <?php echo esc_html($booking->venue_name); ?>
            </span>
            <?php endif; ?>
        </div>

        <div class="booking-details-row">
            <span>Seats:
                <?php echo implode(', ', array_slice($seat_numbers, 0, 3)); ?><?php echo count($seat_numbers) > 3 ? '...' : ''; ?></span>
            <span class="booking-price">$<?php echo number_format($booking->total_amount, 2); ?></span>
        </div>

        <div class="booking-reference">Ref: <?php echo esc_html($booking->booking_reference); ?></div>

        <?php if ($show_actions) : ?>
        <div class="booking-actions">
            <button class="btn-icon view-booking" data-booking-id="<?php echo $booking->id; ?>" title="View Details">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                    fill="#667eea">
                    <path
                        d="M480-320q75 0 127.5-52.5T660-500q0-75-52.5-127.5T480-680q-75 0-127.5 52.5T300-500q0 75 52.5 127.5T480-320Zm0-72q-45 0-76.5-31.5T372-500q0-45 31.5-76.5T480-608q45 0 76.5 31.5T588-500q0 45-31.5 76.5T480-392Zm0 192q-146 0-266-81.5T40-500q54-137 174-218.5T480-800q146 0 266 81.5T920-500q-54 137-174 218.5T480-200Zm0-300Zm0 220q113 0 207.5-59.5T832-500q-50-101-144.5-160.5T480-720q-113 0-207.5 59.5T128-500q50 101 144.5 160.5T480-280Z" />
                </svg>
            </button>
            <?php if ($can_cancel) : ?>
            <button class="btn-icon cancel-booking" data-booking-id="<?php echo $booking->id; ?>"
                title="Cancel Booking">
                <svg width="18" height="24" viewBox="0 0 24 24" fill="#000000">
                    <path
                        d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                </svg>
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php
    return ob_get_clean();
}
?>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: #f5f7fa;
}

.dashboard-wrapper {
    display: grid;
    /* grid-template-columns: 280px 4fr; */
    min-height: 100vh;
}

/* Sidebar */
.dashboard-sidebar {
    background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px 20px;
    position: fixed;
    width: 280px;
    height: 100vh;
    overflow-y: auto;
}

.user-profile-card {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 25px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.user-avatar-large {
    margin-bottom: 15px;
}

.user-avatar-large img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 4px solid rgba(255, 255, 255, 0.3);
}

.user-profile-card h3 {
    font-size: 20px;
    margin-bottom: 5px;
}

.user-email {
    font-size: 13px;
    opacity: 0.9;
}

.dashboard-nav {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s;
    font-size: 15px;
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.nav-item.active {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    font-weight: 600;
}

.nav-item.logout {
    margin-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    padding-top: 20px;
}

/* Main Content */
.dashboard-content {
    margin-left: 280px;
    padding: 30px;
}

.tab-panel {
    display: none;
}

.tab-panel.active {
    display: block;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 32px;
    color: #2d3748;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s, box-shadow 0.3s;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.stat-card.blue .stat-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card.green .stat-icon {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.stat-card.orange .stat-icon {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-card.purple .stat-icon {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-content h2 {
    font-size: 32px;
    color: #2d3748;
    margin-bottom: 4px;
}

.stat-content p {
    color: #718096;
    font-size: 14px;
}

/* Section Card */
.section-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-header h2 {
    font-size: 20px;
    color: #2d3748;
}

.view-all-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
}

.view-all-link:hover {
    text-decoration: underline;
}

/* Bookings Grid */
.bookings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.booking-card-modern {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    transition: all 0.3s;
    position: relative;
}

.booking-card-modern:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.booking-type-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 4px;
    z-index: 2;
}

.event-booking .booking-poster {
    background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%);
}

.event-booking .meta-item svg {
    color: #e83e8c;
}

.booking-poster {
    position: relative;
    height: 180px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.booking-poster img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.poster-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    /* Continuing from the poster-placeholder */
    align-items: center;
    justify-content: center;
}

.status-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-confirmed {
    background: #d4edda;
    color: #155724;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-cancelled {
    background: #f8d7da;
    color: #721c24;
}

.status-past {
    background: #e2e3e5;
    color: #383d41;
}

.booking-info {
    padding: 20px;
}

.booking-info h3 {
    font-size: 16px;
    color: #2d3748;
    margin-bottom: 12px;
    font-weight: 600;
}

.booking-meta {
    display: flex;
    gap: 15px;
    margin-bottom: 12px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #718096;
}

.booking-details-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    font-size: 14px;
    color: #4a5568;
}

.booking-price {
    font-weight: 700;
    color: #667eea;
    font-size: 18px;
}

.booking-reference {
    font-size: 12px;
    color: #a0aec0;
    margin-bottom: 15px;
}

.booking-actions {
    display: flex;
    gap: 10px;
}

.btn-icon {
    border: 1px solid #e2e8f0;
    padding: 8px 12px;
    background: white;
    border-radius: 8px;
    display: inline-block;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-icon:hover {
    background: #667eea;
    border-color: #667eea;
    color: white;
}

.btn-icon svg {
    transition: all 0.3s;
}

.btn-icon:hover svg {
    fill: white;
}

/* Booking Filters */
.booking-filters {
    display: flex;
    gap: 10px;
}

.filter-chip {
    padding: 8px 20px;
    border: 2px solid #e2e8f0;
    background: white;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
    color: #4a5568;
}

.filter-chip:hover {
    border-color: #667eea;
    color: #667eea;
}

.filter-chip.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: transparent;
}

/* Profile Settings */
.profile-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.section-card h3 {
    margin-bottom: 20px;
    color: #2d3748;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #4a5568;
    font-size: 14px;
}

.form-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #a0aec0;
    font-size: 12px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #a0aec0;
}

.empty-state h3 {
    color: #4a5568;
    margin: 15px 0 10px;
}

/* Notifications */
.notifications-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
    max-width: 400px;
}

.notification {
    background: white;
    padding: 16px 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 15px;
    transform: translateX(400px);
    opacity: 0;
    transition: all 0.3s;
}

.notification.notification-show {
    transform: translateX(0);
    opacity: 1;
}

.notification.error {
    border-left: 4px solid #dc3545;
}

.notification.success {
    border-left: 4px solid #28a745;
}

.notification-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #999;
}

/* Responsive */
@media (max-width: 1024px) {
    .dashboard-wrapper {
        grid-template-columns: 1fr;
    }

    .dashboard-sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .dashboard-content {
        margin-left: 0;
    }

    .profile-grid {
        grid-template-columns: 1fr;
    }

    .bookings-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
}


.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}

.modal-overlay.active {
    display: flex;
}

/* Modal Container */
.modal-container {
    background: white;
    border-radius: 20px;
    width: 90%;
    max-width: 700px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Modal Header */
.modal-header {
    padding: 24px 30px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.modal-header h2 {
    font-size: 24px;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 32px;
    cursor: pointer;
    color: white;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(90deg);
}

/* Modal Body */
.modal-body {
    padding: 30px;
    max-height: calc(90vh - 180px);
    overflow-y: auto;
}

/* Loading State */
.modal-loading {
    text-align: center;
    padding: 40px 20px;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Movie Section */
.modal-movie-section {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 2px solid #f7fafc;
}

.modal-movie-poster {
    flex-shrink: 0;
}

.modal-movie-poster img {
    width: 120px;
    height: 180px;
    object-fit: cover;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.modal-movie-info {
    flex: 1;
}

.modal-movie-info h3 {
    font-size: 22px;
    margin-bottom: 12px;
    color: #2d3748;
}

.modal-booking-ref {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 15px;
    background: #f7fafc;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 12px;
    color: #4a5568;
}

.modal-status-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Details Grid */
.modal-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.detail-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    background: #f7fafc;
    border-radius: 12px;
}

.detail-item svg {
    flex-shrink: 0;
    color: #667eea;
    margin-top: 2px;
}

.detail-item label {
    display: block;
    font-size: 12px;
    color: #718096;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-item span {
    display: block;
    font-size: 15px;
    color: #2d3748;
    font-weight: 600;
}

/* Modal Sections */
.modal-section {
    margin-bottom: 25px;
}

.modal-section h4 {
    font-size: 16px;
    color: #2d3748;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-section h4 svg {
    color: #667eea;
}

/* Seats Display */
.seats-display {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.seat-badge {
    padding: 8px 16px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
}

.seat-type-label {
    display: inline-block;
    margin-left: 8px;
    font-size: 11px;
    font-weight: 500;
    opacity: 0.9;
    color: rgba(255,255,255,0.9);
}

/* Payment Summary */
.payment-summary {
    background: #f7fafc;
    padding: 20px;
    border-radius: 12px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #e2e8f0;
    font-size: 15px;
    color: #4a5568;
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-row.total {
    margin-top: 10px;
    padding-top: 15px;
    border-top: 2px solid #cbd5e0;
    font-size: 18px;
    font-weight: 700;
    color: #2d3748;
}

.summary-row.total span:last-child {
    color: #667eea;
}

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.info-item {
    padding: 12px;
    background: #f7fafc;
    border-radius: 8px;
}

.info-item label {
    display: block;
    font-size: 12px;
    color: #718096;
    margin-bottom: 4px;
}

.info-item span {
    display: block;
    font-size: 14px;
    color: #2d3748;
    font-weight: 600;
}

/* Modal Footer */
.modal-footer {
    padding: 20px 30px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    background: #f7fafc;
}

.btn-secondary {
    padding: 12px 24px;
    border: 2px solid #e2e8f0;
    background: white;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    color: #4a5568;
}

.btn-secondary:hover {
    border-color: #cbd5e0;
    background: #f7fafc;
}

/* Responsive */
@media (max-width: 768px) {
    .modal-container {
        width: 95%;
        max-height: 95vh;
    }
    
    .modal-movie-section {
        flex-direction: column;
    }
    
    .modal-movie-poster img {
        width: 100%;
        height: auto;
    }
    
    .modal-details-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-footer {
        flex-direction: column-reverse;
    }
    
    .modal-footer button {
        width: 100%;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    'use strict';

    // Small date/time helpers used by event bookings renderer
    function formatDate(dateStr) {
        if (!dateStr) return '';
        // Try creating a Date from date-only strings (YYYY-mm-dd)
        var d = new Date(dateStr);
        if (isNaN(d.getTime())) {
            // Fallback: return original string
            return dateStr;
        }
        return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function formatTime(timeStr) {
        if (!timeStr) return '';
        // If time is provided as HH:MM:SS or HH:MM, format accordingly
        var parts = timeStr.split(':');
        if (parts.length >= 2) {
            var d = new Date();
            d.setHours(parseInt(parts[0], 10));
            d.setMinutes(parseInt(parts[1], 10));
            return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        }
        var parsed = new Date(timeStr);
        if (isNaN(parsed.getTime())) return timeStr;
        return parsed.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    }

    function loadEventBookings(status) {
        $('.events-list').html(
            '<div class="loading-spinner">' +
            '<div class="spinner"></div>' +
            '<p>Loading your event bookings...</p>' +
            '</div>'
        );

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            method: 'POST',
            data: {
                action: 'cinema_get_event_bookings',
                nonce: '<?php echo wp_create_nonce('cinema_nonce'); ?>',
                status: status === 'all' ? null : status
            },
            success: function(response) {
                if (response.success && response.data.bookings) {
                    displayEventBookings(response.data.bookings);
                } else {
                    $('.events-list').html(
                        '<div class="no-bookings">' +
                        '<svg width="80" height="80" fill="#ccc" viewBox="0 0 24 24">' +
                        '<path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>' +
                        '</svg>' +
                        '<h3>No event bookings found</h3>' +
                        '<p>You haven\'t made any event bookings yet.</p>' +
                        '<a href="<?php echo home_url('/events/'); ?>" class="btn btn-primary">Browse Events</a>' +
                        '</div>'
                    );
                }
            },
            error: function() {
                $('.events-list').html(
                    '<div class="error-message">' +
                    '<p>Failed to load event bookings. Please try again.</p>' +
                    '</div>'
                );
            }
        });
    }

    function displayEventBookings(bookings) {
        try {
            if (!bookings || bookings.length === 0) {
                $('.events-list').html(
                    '<div class="no-bookings">' +
                    '<svg width="80" height="80" fill="#ccc" viewBox="0 0 24 24">' +
                    '<path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>' +
                    '</svg>' +
                    '<h3>No event bookings found</h3>' +
                    '<p>You haven\'t made any event bookings yet.</p>' +
                    '<a href="<?php echo home_url('/events/'); ?>" class="btn btn-primary">Browse Events</a>' +
                    '</div>'
                );
                return;
            }

            let html = '';
            bookings.forEach(booking => {
                // Ensure booking fields exist
                const showDateStr = booking.show_date || '';
                const showTimeStr = booking.show_time || '';

                const showDate = new Date((showDateStr + ' ' + showTimeStr).trim());
                const now = new Date();
                const isUpcoming = !isNaN(showDate.getTime()) ? (showDate > now) : false;
                const isCancelled = booking.booking_status === 'cancelled';

                const statusClass = isCancelled ? 'cancelled' : (isUpcoming ? 'upcoming' : 'past');
                const statusText = isCancelled ? 'Cancelled' : (isUpcoming ? 'Upcoming' : 'Past');

                let seatList = '';
                try {
                    if (Array.isArray(booking.seats)) {
                        seatList = booking.seats.map(s => (s.seat_number || s)).join(', ');
                    } else if (typeof booking.seats === 'string') {
                        seatList = booking.seats;
                    }
                } catch (e) {
                    seatList = '';
                }

                html += `
                    <div class="booking-card ${statusClass}">
                        <div class="booking-card-header">
                            <div class="booking-movie-info">
                                <h3>${booking.event_title || ''}</h3>
                                <p class="booking-reference">Ref: ${booking.booking_reference || ''}</p>
                            </div>
                            <div class="booking-status-badge ${statusClass}">${statusText}</div>
                        </div>
                        
                        <div class="booking-card-body">
                            <div class="booking-info-grid">
                                <div class="info-item">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                                    </svg>
                                    <span>${formatDate(booking.show_date)}</span>
                                </div>
                                
                                <div class="info-item">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                                    </svg>
                                    <span>${formatTime(booking.show_time)}</span>
                                </div>
                                
                                <div class="info-item">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                    </svg>
                                    <span>${booking.event_venue || ''}</span>
                                </div>
                                
                                <div class="info-item">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/>
                                    </svg>
                                    <span>Seats: ${seatList}</span>
                                </div>
                            </div>
                            
                            <div class="booking-amount">
                                <span>Total Paid:</span>
                                <strong>$${parseFloat(booking.total_amount || 0).toFixed(2)}</strong>
                            </div>
                        </div>
                        
                        <div class="booking-card-footer">
                            <button class="btn btn-secondary btn-sm view-booking-details" data-booking-id="${booking.id}">
                                View Details
                            </button>
                            ${isUpcoming && !isCancelled ? `
                                <button class="btn btn-danger btn-sm cancel-booking" data-booking-id="${booking.id}">
                                    Cancel Booking
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            });

            $('.events-list').html(html);
        } catch (err) {
            console.error('Error rendering event bookings:', err);
            $('.events-list').html(
                '<div class="error-message"><p>Failed to render event bookings. Please refresh the page.</p></div>'
            );
        }
    }

    // Tab Navigation
    $('.nav-item[data-tab]').on('click', function(e) {
        e.preventDefault();
        const tab = $(this).data('tab');

        $('.nav-item').removeClass('active');
        $(this).addClass('active');

        $('.tab-panel').removeClass('active');
        $('#' + tab + '-tab').addClass('active');

        // Load event bookings when switching to event bookings tab
        if (tab === 'event-bookings') {
            loadEventBookings('all');
        }
    });

    // View All Link
    $('[data-tab-trigger]').on('click', function(e) {
        e.preventDefault();
        const tab = $(this).data('tab-trigger');
        $(`.nav-item[data-tab="${tab}"]`).trigger('click');
    });

    // Booking Filters
    $('#bookings-tab .filter-chip').on('click', function() {
        const filter = $(this).data('filter');
        $('.filter-chip', '#bookings-tab').removeClass('active');
        $(this).addClass('active');
        $('.bookings-list').hide();
        $(`.bookings-list[data-section="${filter}"]`).show();
    });

    // Event Booking Filters
    $('#event-bookings-tab .filter-chip').on('click', function() {
        const filter = $(this).data('filter');
        $('.filter-chip', '#event-bookings-tab').removeClass('active');
        $(this).addClass('active');
        loadEventBookings(filter);
    });



    // Universal View Booking Handler - works on all tabs
    $(document).on('click', '.view-booking, .view-booking-details', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const bookingId = $(this).data('booking-id');
        console.log('Opening modal for booking:', bookingId); // Debug log

        // Close any existing modals
        $('#booking-details-modal').hide();

        // Show universal modal with loading state
        $('#booking-modal').addClass('active');
        $('.modal-loading').show();
        $('.modal-content').hide();
        $('#modal-print-btn').hide();

        // Fetch booking details
        $.ajax({
            url: cinema_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cinema_get_booking_details',
                nonce: cinema_ajax.nonce,
                booking_id: bookingId,
                for_modal: 'true'
            },
            success: function(response) {
                console.log('AJAX Response:', response); // Debug log

                if (response.success) {
                    populateModal(response.data);
                    $('.modal-loading').hide();
                    $('.modal-content').show();
                    $('#modal-print-btn').show();
                } else {
                    showNotification(response.data.message ||
                        'Failed to load booking details', 'error');
                    $('#booking-modal').removeClass('active');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                showNotification('An error occurred while loading booking details',
                'error');
                $('#booking-modal').removeClass('active');
            }
        });
    });

    // Populate Modal with Data
    function populateModal(data) {
        console.log('Populating modal with:', data); // Debug

        $('#modal-movie-poster').attr('src', data.movie_poster || '');
        $('#modal-movie-title').text(data.movie_title || 'Unknown Movie');
        $('#modal-booking-ref').text(data.booking_reference || 'N/A');

        const statusBadge = $('#modal-status-badge');
        statusBadge.removeClass().addClass('modal-status-badge');
        statusBadge.addClass('status-' + (data.status_class || 'confirmed'));
        statusBadge.text(data.status_text || 'Confirmed');

        $('#modal-show-date').text(data.show_date || 'N/A');
        $('#modal-show-time').text(data.show_time || 'N/A');
        $('#modal-theater').text(data.theater || 'Main Theater');
        $('#modal-screen').text(data.screen || 'Screen 1');

        // FIXED: Handle seats array properly
        const seatsContainer = $('#modal-seats');
        seatsContainer.empty();
        if (data.seats && Array.isArray(data.seats) && data.seats.length > 0) {
            data.seats.forEach(function(seatObj) {
                // seatObj can be a string (old format) or an object with seat_number and seat_type
                let seatNumber = '';
                let seatType = 'regular';
                if (typeof seatObj === 'string') {
                    seatNumber = seatObj;
                } else if (typeof seatObj === 'object') {
                    seatNumber = seatObj.seat_number || seatObj.seat || '';
                    seatType = seatObj.seat_type || seatObj.type || 'regular';
                }

                const typeLabel = seatType === 'wheelchair' ? ' (Wheelchair)' : ' (Regular)';
                seatsContainer.append('<span class="seat-badge">' + seatNumber + '<small class="seat-type-label">' + typeLabel + '</small></span>');
            });
        } else {
            seatsContainer.append('<span class="seat-badge">No seats</span>');
        }

        $('#modal-ticket-price').text('$' + (data.ticket_price || '0.00'));
        $('#modal-ticket-count').text(data.ticket_count || 0);
        $('#modal-service-fee').text('$' + (data.service_fee || '0.00'));
        $('#modal-total-amount').text('$' + (data.total_amount || '0.00'));

        $('#modal-booking-date').text(data.booking_date || 'N/A');
        $('#modal-payment-method').text(data.payment_method || 'Credit Card');
        $('#modal-transaction-id').text(data.transaction_id || 'N/A');
    }

    // Close Modal
    $('.modal-close').on('click', function(e) {
        e.preventDefault();
        $('#booking-modal').removeClass('active');
    });

    $('.modal-overlay').on('click', function(e) {
        if (e.target === this) {
            $('#booking-modal').removeClass('active');
        }
    });

    // Prevent modal close when clicking inside
    $('.modal-container').on('click', function(e) {
        e.stopPropagation();
    });

    // Print Ticket
    $('#modal-print-btn').on('click', function() {
        window.print();
    });


    // Cancel Booking
    $(document).on('click', '.cancel-booking', function() {
        const bookingId = $(this).data('booking-id');

        if (!confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
            return;
        }

        $(this).prop('disabled', true);

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'cinema_cancel_booking',
                nonce: '<?php echo wp_create_nonce('cinema_nonce'); ?>',
                booking_id: bookingId
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Booking cancelled successfully', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(response.data.message || 'Failed to cancel', 'error');
                }
            },
            error: function() {
                showNotification('An error occurred', 'error');
            }
        });
    });

    // Update Profile
    $('#profile-form').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#update-profile-btn');
        $btn.prop('disabled', true).text('Updating...');

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'cinema_update_profile',
                nonce: '<?php echo wp_create_nonce('cinema_nonce'); ?>',
                first_name: $('#first_name').val(),
                last_name: $('#last_name').val(),
                display_name: $('#display_name').val(),
                user_email: $('#user_email').val(),
                phone: $('#phone').val()
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Profile updated successfully', 'success');
                } else {
                    showNotification(response.data.message, 'error');
                }
                $btn.prop('disabled', false).text('Save Changes');
            }
        });
    });

    // Change Password
    $('#password-form').on('submit', function(e) {
        e.preventDefault();

        const newPass = $('#new_password').val();
        const confirmPass = $('#confirm_new_password').val();

        if (newPass !== confirmPass) {
            showNotification('Passwords do not match', 'error');
            return;
        }

        if (newPass.length < 8) {
            showNotification('Password must be at least 8 characters', 'error');
            return;
        }

        const $btn = $('#change-password-btn');
        $btn.prop('disabled', true).text('Updating...');

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'cinema_change_password',
                nonce: '<?php echo wp_create_nonce('cinema_nonce'); ?>',
                current_password: $('#current_password').val(),
                new_password: newPass
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Password updated successfully', 'success');
                    $('#password-form')[0].reset();
                } else {
                    showNotification(response.data.message, 'error');
                }
                $btn.prop('disabled', false).text('Update Password');
            }
        });
    });

    function showNotification(message, type) {
        const notif = $(`
            <div class="notification ${type}">
                <span class="notification-message">${message}</span>
                <button class="notification-close">×</button>
            </div>
        `);

        if (!$('.notifications-container').length) {
            $('body').append('<div class="notifications-container"></div>');
        }

        $('.notifications-container').append(notif);
        setTimeout(() => notif.addClass('notification-show'), 10);
        setTimeout(() => notif.removeClass('notification-show'), 5000);
        setTimeout(() => notif.remove(), 5300);

        notif.find('.notification-close').on('click', () => notif.remove());
    }
});
</script>

<!-- Booking Details Modal - Add before get_footer() -->
<div id="booking-modal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h2>Booking Details</h2>
            <button class="modal-close">&times;</button>
        </div>
        
        <div class="modal-body">
            <div class="modal-loading">
                <div class="spinner"></div>
                <p>Loading booking details...</p>
            </div>
            
            <div class="modal-content" style="display: none;">
                <!-- Movie Info Section -->
                <div class="modal-movie-section">
                    <div class="modal-movie-poster">
                        <img id="modal-movie-poster" src="" alt="Movie Poster">
                    </div>
                    <div class="modal-movie-info">
                        <h3 id="modal-movie-title"></h3>
                        <div class="modal-booking-ref">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                            </svg>
                            Reference: <strong id="modal-booking-ref"></strong>
                        </div>
                        <div class="modal-status-badge" id="modal-status-badge"></div>
                    </div>
                </div>
                
                <!-- Booking Details Grid -->
                <div class="modal-details-grid">
                    <div class="detail-item">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/>
                        </svg>
                        <div>
                            <label>Show Date</label>
                            <span id="modal-show-date"></span>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                        </svg>
                        <div>
                            <label>Show Time</label>
                            <span id="modal-show-time"></span>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                        </svg>
                        <div>
                            <label>Theater</label>
                            <span id="modal-theater"></span>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M7 1h10v6H7zm12 6h2v2h-2V7zm0 4h2v2h-2v-2zM7 21h10v-6H7v6zM5 7H3v2h2V7zm0 4H3v2h2v-2zm0 4H3v2h2v-2zm0 4H3v2h2v-2zm14 0h2v2h-2v-2z"/>
                        </svg>
                        <div>
                            <label>Screen</label>
                            <span id="modal-screen"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Seats Section -->
                <div class="modal-section">
                    <h4>
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M4 18v3h3v-3h10v3h3v-6H4v3zm15-8h3v3h-3v-3zM2 10h3v3H2v-3zm15 3H7V5c0-1.1.9-2 2-2h6c1.1 0 2 .9 2 2v8z"/>
                        </svg>
                        Selected Seats
                    </h4>
                    <div id="modal-seats" class="seats-display"></div>
                </div>
                
                <!-- Payment Summary -->
                <div class="modal-section payment-summary">
                    <h4>Payment Summary</h4>
                    <div class="summary-row">
                        <span>Ticket Price</span>
                        <span id="modal-ticket-price"></span>
                    </div>
                    <div class="summary-row">
                        <span>Number of Tickets</span>
                        <span id="modal-ticket-count"></span>
                    </div>
                    <div class="summary-row">
                        <span>Service Fee</span>
                        <span id="modal-service-fee"></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total Amount</span>
                        <span id="modal-total-amount"></span>
                    </div>
                </div>
                
                <!-- Additional Info -->
                <div class="modal-section">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Booking Date:</label>
                            <span id="modal-booking-date"></span>
                        </div>
                        <div class="info-item">
                            <label>Payment Method:</label>
                            <span id="modal-payment-method"></span>
                        </div>
                        <div class="info-item">
                            <label>Transaction ID:</label>
                            <span id="modal-transaction-id"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="btn-secondary modal-close">Close</button>
            <button id="modal-print-btn" class="btn-primary" style="display: none;">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/>
                </svg>
                Print Ticket
            </button>
        </div>
    </div>
</div>

<?php get_footer(); ?>