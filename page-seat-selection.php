<?php
/*
Template Name: Seat Selection
*/

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/?redirect_to=' . urlencode($_SERVER['REQUEST_URI'])));
    exit;
}

// Get showtime ID from URL
$showtime_id = isset($_GET['showtime']) ? intval($_GET['showtime']) : 0;

if (!$showtime_id) {
    wp_redirect(home_url('/movies'));
    exit;
}

// Get showtime details
$showtime = get_post($showtime_id);
if (!$showtime) {
    wp_redirect(home_url('/movies'));
    exit;
}

$movie_id = get_post_meta($showtime_id, '_showtime_movie_id', true);
$movie = get_post($movie_id);
$show_date = get_post_meta($showtime_id, '_showtime_date', true);
$show_time = get_post_meta($showtime_id, '_showtime_time', true);
$screen_number = get_post_meta($showtime_id, '_showtime_screen', true);
$ticket_price = get_post_meta($showtime_id, '_showtime_ticket_price', true);

// Get all showtimes for this movie
$all_showtimes = get_posts(array(
    'post_type' => 'showtime',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => '_showtime_movie_id',
            'value' => $movie_id
        ),
        array(
            'key' => '_showtime_date',
            'value' => $show_date
        )
    )
));

get_header();
?>

<div class="seat-selection-container">
    <!-- Mobile Header -->
    <div class="mobile-header">
        <button class="back-btn" onclick="history.back()">
            <svg width="24" height="24" fill="white" viewBox="0 0 24 24">
                <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z" />
            </svg>
        </button>
        <div class="cinema-logo">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <h2>Adeebdds</h2>
            </a>
        </div>
        <button class="close-btn" onclick="window.location.href='<?php echo home_url('/movies'); ?>'">
            <svg width="24" height="24" fill="white" viewBox="0 0 24 24">
                <path
                    d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
            </svg>
        </button>
    </div>

    <!-- Mobile Step Indicator -->
    <div class="mobile-step-indicator">
        <span class="step-text">Step 1 of 3</span>
        <button class="view-cart-btn" id="mobile-view-cart">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z" />
            </svg>
            VIEW CART
        </button>
    </div>

    <!-- Left Sidebar: Movie Info & Showtimes (Desktop) -->
    <aside class="movie-sidebar">
        <div class="sidebar-header">
            <button class="location-btn">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                </svg>
                525 Lighthouse Ave, Pacific Grove, CA
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M7 10l5 5 5-5z" />
                </svg>
            </button>
        </div>

        <div class="movie-poster">
            <?php
            $trailer_banner = get_post_meta($movie_id, '_movie_trailer_banner', true);
            if (!empty($trailer_banner)) {
                if (is_numeric($trailer_banner)) {
                    echo wp_get_attachment_image(
                        (int) $trailer_banner,
                        'medium',
                        false,
                        array('class' => 'movie-poster-img', 'alt' => esc_attr(get_the_title($movie_id)))
                    );
                } else {
                    $banner_url = esc_url($trailer_banner);
                    echo '<img src="' . $banner_url . '" class="movie-poster-img" alt="' . esc_attr(get_the_title($movie_id)) . '">';
                }
            } elseif (has_post_thumbnail($movie_id)) {
                echo get_the_post_thumbnail($movie_id, 'medium', array('class' => 'movie-poster-img'));
            }
            ?>
        </div>

        <div class="movie-info">
            <div class="movie-info-header">
                <h2><?php echo get_the_title($movie_id); ?></h2>
                <span class="rating-badge"><?php echo get_post_meta($movie_id, '_movie_rating', true); ?></span>
            </div>
            <div class="movie-meta">

                <span class="genre"><?php echo get_post_meta($movie_id, '_movie_genre', true); ?></span>
                <span class="duration"><?php echo get_post_meta($movie_id, '_movie_duration', true); ?> min</span>
            </div>
        </div>

        <div class="showtimes-section">
            <h3><?php echo date('l, M d', strtotime($show_date)); ?></h3>
            <div class="time-slots-grid">
                <?php foreach ($all_showtimes as $st) : 
                $st_time = get_post_meta($st->ID, '_showtime_time', true); // ✅ FIXED
                $st_screen = get_post_meta($st->ID, '_showtime_screen', true);
                $is_active = ($st->ID == $showtime_id) ? 'active' : '';
            ?>
                <button class="time-slot-btn <?php echo $is_active; ?>" data-showtime-id="<?php echo $st->ID; ?>"
                    data-screen="<?php echo $st_screen; ?>" data-time="<?php echo $st_time; ?>">
                    <?php echo date('g:i A', strtotime($st_time)); ?>
                </button>
                <?php endforeach; ?>
            </div>

            <div class="theater-info">
                <h4>525 Lighthouse Ave, Pacific Grove, CA</h4>
                <p>Screen <?php echo $screen_number; ?></p>
            </div>
        </div>
    </aside>

    <!-- Center: Seat Map -->
    <main class="seat-map-main">
        <div class="seat-header desktop-only">
            <div class="header-left">
                <button class="nav-btn" onclick="history.back()">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z" />
                    </svg>
                </button>
            </div>

            <div class="header-center">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="logo-link">
                    <h1>cinépolis</h1>
                </a>
            </div>

            <div class="header-right">
                <button class="nav-btn" onclick="window.location.href='<?php echo home_url('/movies'); ?>'">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="progress-bar desktop-only">
            <div class="progress-steps">
                <div class="progress-step active">
                    <div class="step-circle">1</div>
                    <span class="step-label">Seats</span>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step">
                    <div class="step-circle">2</div>
                    <span class="step-label">Cart</span>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step">
                    <div class="step-circle">3</div>
                    <span class="step-label">Payment</span>
                </div>
            </div>
        </div>

        <div class="seat-legend-bar">
            <div class="legend-items">
                <div class="legend-item">
                    <span class="seat-demo recliner"></span>
                    <span>Recliner</span>
                </div>
                <div class="legend-item">
                    <span class="seat-demo wheelchair-seat">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12 4c0-1.1-.9-2-2-2s-2 .9-2 2 .9 2 2 2 2-.9 2-2zm-2 18c-2.76 0-5-2.24-5-5H3c0 3.87 3.13 7 7 7v-2zm10.92-5H22c0-5.14-3.93-9.37-8.95-9.93-.1-.01-.21-.02-.31-.03L10.52 9.3C11.28 9.75 11.92 10.42 12.38 11.22l7.54 5.32zM8 17c0-1.66 1.34-3 3-3h.61l1.89-2.64C12.78 9.91 11.54 9 10 9c-2.21 0-4 1.79-4 4s1.79 4 4 4v-2c-1.1 0-2-.9-2-2z" />
                        </svg>
                    </span>
                    <span>Wheelchair</span>
                </div>
                <div class="legend-item">
                    <span class="seat-demo companion-seat">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" />
                        </svg>
                    </span>
                    <span>Companion</span>
                </div>
                <div class="legend-item">
                    <span class="seat-demo reserved-seat"></span>
                    <span>Reserved</span>
                </div>
                <div class="legend-item">
                    <span class="seat-demo unavailable-seat"></span>
                    <span>Unavailable</span>
                </div>
                <div class="legend-item">
                    <span class="seat-demo" style="background: #4ade80; border-color: #22c55e;"></span>
                    <span>SEAT LEGEND</span>
                </div>
            </div>
        </div>

        <div class="screen-section">
            <h3 class="screen-title">SCREEN <?php echo $screen_number; ?></h3>
            <div class="screen-display">
                <div class="screen-bar"></div>
            </div>
        </div>

        <div class="seat-map-wrapper">
            <div id="seat-map" class="seat-grid-area" data-showtime-id="<?php echo $showtime_id; ?>"
                data-ticket-price="<?php echo $ticket_price; ?>">
                <!-- Seats will be loaded via AJAX -->
            </div>
        </div>

        <div class="zoom-controls">
            <button class="zoom-btn zoom-out" title="Zoom Out">
                <svg width="20" height="20" fill="black" viewBox="0 0 24 24">
                    <path d="M19 13H5v-2h14v2z" />
                </svg>
            </button>
            <button class="zoom-btn zoom-in" title="Zoom In">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                </svg>
            </button>
            <button class="zoom-btn fullscreen" title="Fullscreen">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z" />
                </svg>
            </button>
        </div>

        <!-- Mobile Bottom Button -->
        <div class="mobile-bottom-action">
            <button class="btn-select-seats-mobile" id="select-seats-btn-mobile" disabled>
                SELECT A SEAT TO CONTINUE
            </button>
        </div>
    </main>

    <!-- Right Sidebar: Cart (Desktop) -->
    <aside class="cart-sidebar">
        <div class="cart-header-section">
            <div class="cart-icon-header">
                <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M15.55 13c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.37-.66-.11-1.48-.87-1.48H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.48 17 7 17h12v-2H7l1.1-2h7.45zM6.16 6h12.15l-2.76 5H8.53L6.16 6zM7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z" />
                </svg>
                <h3>TICKETS</h3>
            </div>
            <div class="ticket-summary">
                <span class="summary-count">1 Ticket</span>
                <span
                    class="ticket-price"><?php echo get_post_meta($showtime_id, '_showtime_ticket_price', true); ?></span>
                <button class="expand-btn">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M7 10l5 5 5-5z" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="cart-content" id="cart-items">
            <p class="empty-cart-text">Your cart is empty</p>
        </div>

        <div class="cart-summary-section" id="cart-summary" style="display: none;">
            <div class="summary-line">
                <span>Tickets</span>
                <span class="ticket-count">0 ticket</span>
            </div>
            <div class="summary-line total-line">
                <span>Total</span>
                <span class="cart-total">$0.00</span>
            </div>
        </div>

        <div class="promo-section">
            <input type="text" placeholder="Add Gift Card, Voucher, Promo Code" class="promo-input">
            <button class="btn-apply-promo">APPLY</button>
        </div>

        <button class="btn-next-step" id="next-cart-btn" style="display: none;">
            NEXT: CART
        </button>
    </aside>

    <!-- Mobile Cart Drawer -->
    <div class="mobile-cart-drawer" id="mobile-cart-drawer">
        <div class="cart-drawer-header">
            <h3>Your Cart</h3>
            <button class="close-drawer">✕</button>
        </div>
        <div class="cart-drawer-content" id="mobile-cart-content">
            <p class="empty-cart-message">Your cart is empty</p>
        </div>
    </div>
</div>

<!-- Showtime Change Modal -->
<div id="showtime-change-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>New Showtime Selected</h3>
            <button class="modal-close">✕</button>
        </div>

        <div class="modal-body">
            <p>You have tickets in your cart for another showtime</p>
            <ul id="showtime-comparison">
                <!-- Showtime comparison will be added here -->
            </ul>

            <div class="modal-actions">
                <button class="btn btn-secondary" id="keep-tickets">KEEP THEM</button>
                <button class="btn btn-primary" id="remove-tickets">REMOVE THEM</button>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================
   MODERN CINEMA SEAT SELECTION STYLES
   ============================================ */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.seat-selection-container {
    display: grid;
    grid-template-columns: 285px 1fr 380px;
    height: 100vh;
    background: #f8f9fa;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

/* ============================================
   LEFT SIDEBAR - MOVIE INFO
   ============================================ */

.movie-sidebar {
    background: #fafafa;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    border-right: 1px solid #e5e7eb;
}

.sidebar-header {
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.location-btn {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    background: #f8f9fa;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    transition: all 0.2s;
}

.location-btn:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.movie-poster {
    padding: 16px;
}

.movie-poster-img {
    width: 100%;
    height: 100%;
    object-fit: fill;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.movie-info {
    padding: 0 16px 16px;
}

.movie-info h2 {
    font-size: 1rem;
    font-weight: 700;
    color: #111827;
    text-transform: capitalize;
    line-height: 1.5rem;
}

.movie-info-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.movie-meta {
    width: 100%;
    max-width: 250px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    font-size: 13px;
}

.rating-badge {
    background: transparent;
    color: black;
    padding: 2px 8px;
    border: 1.5px solid black;
    border-radius: 4px;
    font-weight: 700;
    font-size: 12px;
}

.genre,
.duration {
    color: #6b7280;
}

.showtimes-section {
    padding: 16px;
    border-top: 1px solid #e5e7eb;
}

.showtimes-section h3 {
    font-size: 14px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 12px;
}

.time-slots-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-bottom: 20px;
}

.time-slot-btn {
    padding: 10px 8px;
    background: #ffffff;
    border: 1.5px solid #d1d5db;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}

.time-slot-btn:hover {
    border-color: #2563eb;
    background: #eff6ff;
    color: #2563eb;
}

.time-slot-btn.active {
    background: #2563eb;
    border-color: #2563eb;
    color: #ffffff;
}

.theater-info {
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
}

.theater-info h4 {
    font-size: 14px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 4px;
}

.theater-info p {
    font-size: 13px;
    color: #6b7280;
}

/* ============================================
   CENTER - SEAT MAP
   ============================================ */

.seat-map-main {
    background: #fafafa;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.seat-header {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    padding: 16px 24px;
    background: #fafafa;
}

.header-left {
    justify-self: start;
}

.header-center {
    justify-self: center;
}

.header-right {
    justify-self: end;
}

.nav-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    border-radius: 6px;
    color: #6b7280;
    transition: all 0.2s;
}

.nav-btn:hover {
    background: #f3f4f6;
    color: #111827;
}

.logo-link {
    text-decoration: none;
}

.logo-link h1 {
    font-size: 28px;
    font-weight: 300;
    color: #2563eb;
    letter-spacing: -0.5px;
    font-style: italic;
}

.progress-bar {
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    background: #fafafa;
}

.progress-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    max-width: 600px;
    margin: 0 auto;
}

.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    position: relative;
}

.step-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #e5e7eb;
    color: #9ca3af;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    transition: all 0.3s;
}

.progress-step.active .step-circle {
    background: #2563eb;
    color: #ffffff;
}

.step-label {
    font-size: 13px;
    color: #6b7280;
    font-weight: 500;
}

.progress-step.active .step-label {
    color: #111827;
    font-weight: 600;
}

.progress-line {
    width: 80px;
    height: 2px;
    background: #e5e7eb;
    margin: 0 12px;
    margin-bottom: 28px;
}

.seat-legend-bar {
    padding: 14px 24px;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.legend-items {
    display: flex;
    justify-content: center;
    gap: 28px;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #4b5563;
}

.seat-demo {
    width: 24px;
    height: 24px;
    border: 2px solid #3b82f6;
    border-radius: 6px 6px 2px 2px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
}

.seat-demo.wheelchair-seat {
    border-color: #3b82f6;
}

.seat-demo.companion-seat {
    border-color: #3b82f6;
    background: #dbeafe;
}

.seat-demo.reserved-seat {
    background: repeating-linear-gradient(45deg,
            #ef4444,
            #ef4444 3px,
            #ffffff 3px,
            #ffffff 6px);
    border-color: #dc2626;
}

.seat-demo.unavailable-seat {
    background: #d1d5db;
    border-color: #9ca3af;
}

.screen-section {
    padding: 24px 24px 16px;
    text-align: center;
}

.screen-title {
    font-size: 16px;
    font-weight: 700;
    color: #6b7280;
    margin-bottom: 16px;
    letter-spacing: 1px;
}

.screen-display {
    max-width: 80%;
    margin: 0 auto;
}

.screen-bar {
    height: 40px;
    background: linear-gradient(to bottom, #9ca3af 0%, transparent 100%);
    border-radius: 50% 50% 0 0 / 100% 100% 0 0;
    opacity: 0.5;
}

.seat-map-wrapper {
    flex: 1;
    overflow: auto;
    padding: 20px 40px 80px;
}

.seat-grid-area {
    max-width: 900px;
    margin: 0 auto;
    transform-origin: top center;
    transition: transform 0.3s ease;
}

.seat-row {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
    gap: 8px;
}

.row-label {
    width: 28px;
    text-align: center;
    font-weight: 700;
    font-size: 13px;
    color: #6b7280;
}

.seat {
    width: 36px;
    height: 36px;
    border: 2.5px solid #3b82f6;
    border-radius: 8px 8px 3px 3px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 600;
    color: #6b7280;
}

.seat:hover:not(.reserved):not(.unavailable) {
    background: #dbeafe;
    border-color: #2563eb;
    transform: scale(1.08);
}

.seat.selected {
    background: #4ade80;
    border-color: #22c55e;
    color: #ffffff;
}

.seat.reserved {
    background: repeating-linear-gradient(45deg,
            #ef4444,
            #ef4444 3px,
            #ffffff 3px,
            #ffffff 6px);
    border-color: #dc2626;
    cursor: not-allowed;
    opacity: 0.7;
}

.seat.unavailable {
    background: #d1d5db;
    border-color: #9ca3af;
    cursor: not-allowed;
    opacity: 0.6;
}

.seat.wheelchair::before {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%233b82f6'%3E%3Cpath d='M12 4c0-1.1-.9-2-2-2s-2 .9-2 2 .9 2 2 2 2-.9 2-2zm-2 18c-2.76 0-5-2.24-5-5H3c0 3.87 3.13 7 7 7v-2zm10.92-5H22c0-5.14-3.93-9.37-8.95-9.93-.1-.01-.21-.02-.31-.03L10.52 9.3C11.28 9.75 11.92 10.42 12.38 11.22l7.54 5.32z'/%3E%3C/svg%3E") no-repeat center;
}

.zoom-controls {
    position: fixed;
    bottom: 20px;
    right: 50%;
    display: flex;
    gap: 8px;
    z-index: 100;
    background: #ffffff;
    padding: 8px;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.zoom-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: #f3f4f6;
    color: #374151;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.zoom-btn:hover {
    background: #2563eb;
    color: #ffffff;
    transform: scale(1.05);
}

.zoom-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* ============================================
   RIGHT SIDEBAR - CART
   ============================================ */

.cart-sidebar {
    background: white;
    display: flex;
    flex-direction: column;
    border-left: 1px solid #e5e7eb;
    overflow-y: auto;
}

.cart-header-section {
    padding: 20px;
    background: #fafafa;
    border-bottom: 1px solid #e5e7eb;
}

.cart-icon-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.cart-icon-header svg {
    color: #6b7280;
}

.cart-icon-header h3 {
    font-size: 16px;
    font-weight: 700;
    color: #111827;
    letter-spacing: 0.5px;
}

.ticket-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.ticket-count {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
}

.ticket-price {
    font-size: 16px;
    font-weight: 700;
    color: #111827;
}

.expand-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    color: #6b7280;
}

.cart-content {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
}

.empty-cart-text {
    text-align: center;
    color: #9ca3af;
    font-size: 14px;
    padding: 40px 20px;
}

.cart-item {
    padding: 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 12px;
    background: #ffffff;
    transition: all 0.2s;
}

.cart-item:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.cart-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.ticket-type {
    font-weight: 700;
    font-size: 14px;
    color: #22c55e;
}

.ticket-remove {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    color: #9ca3af;
    padding: 4px;
    transition: color 0.2s;
}

.ticket-remove:hover {
    color: #ef4444;
}

.cart-item-details {
    font-size: 13px;
    color: #6b7280;
    display: flex;
    justify-content: space-between;
}

.cart-summary-section {
    padding: 20px;
    border-top: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
}

.summary-line {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 14px;
    color: #6b7280;
}

.summary-line.total-line {
    font-weight: 700;
    color: #111827;
    font-size: 16px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #e5e7eb;
}

.promo-section {
    padding: 20px;
}

.promo-input {
    width: 100%;
    padding: 12px 14px;
    border: 1.5px solid #d1d5db;
    border-radius: 8px;
    margin-bottom: 10px;
    font-size: 13px;
    transition: all 0.2s;
}

.promo-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.btn-apply-promo {
    width: 100%;
    padding: 12px;
    background: #f3f4f6;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
    font-size: 13px;
    color: #374151;
    transition: all 0.2s;
}

.btn-apply-promo:hover {
    background: #e5e7eb;
}

.btn-next-step {
    margin: 20px;
    padding: 16px;
    background: #2563eb;
    color: #ffffff;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    letter-spacing: 0.5px;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.btn-next-step:hover {
    background: #1d4ed8;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
}

/* ============================================
   MOBILE STYLES
   ============================================ */

.mobile-header,
.mobile-step-indicator,
.mobile-movie-info,
.mobile-bottom-action,
.mobile-cart-drawer {
    display: none;
}

/* ============================================
   MODAL STYLES
   ============================================ */

.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    backdrop-filter: blur(4px);
}

.modal-content {
    background: #ffffff;
    border-radius: 16px;
    width: 90%;
    max-width: 550px;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #9ca3af;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    transition: all 0.2s;
}

.modal-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.modal-body {
    padding: 24px;
}

.modal-body p {
    color: #6b7280;
    margin-bottom: 16px;
}

#showtime-comparison {
    background: #f9fafb;
    padding: 16px;
    border-radius: 8px;
    list-style: none;
    margin-bottom: 20px;
}

#showtime-comparison li {
    padding: 8px 0;
    color: #374151;
    font-size: 14px;
}

.modal-actions {
    display: flex;
    gap: 12px;
}

.modal-actions button {
    flex: 1;
    padding: 14px;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
    letter-spacing: 0.3px;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

.btn-primary {
    background: #2563eb;
    color: #ffffff;
}

.btn-primary:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

/* ============================================
   UTILITY CLASSES
   ============================================ */

/* .desktop-only {
    display: block;
} */

/* ============================================
   SCROLLBAR STYLES
   ============================================ */

.seat-map-wrapper::-webkit-scrollbar,
.cart-sidebar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.seat-map-wrapper::-webkit-scrollbar-track,
.cart-sidebar::-webkit-scrollbar-track {
    background: #f3f4f6;
}

.seat-map-wrapper::-webkit-scrollbar-thumb,
.cart-sidebar::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 4px;
}

.seat-map-wrapper::-webkit-scrollbar-thumb:hover,
.cart-sidebar::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

/* ============================================
   RESPONSIVE - TABLET
   ============================================ */

@media (max-width: 1024px) {
    .seat-selection-container {
        grid-template-columns: 260px 1fr 320px;
    }

    .zoom-controls {
        right: 340px;
    }
}

/* ============================================
   RESPONSIVE - MOBILE
   ============================================ */

@media (max-width: 768px) {
    .seat-selection-container {
        display: block;
        height: auto;
        min-height: 100vh;
    }

    .movie-sidebar,
    .cart-sidebar,
    .desktop-only {
        display: none !important;
    }

    /* Mobile Header */
    .mobile-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        background: #1f2937;
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .mobile-header .back-btn,
    .mobile-header .close-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 8px;
    }

    .mobile-header .cinema-logo h2 {
        font-size: 20px;
        color: #ffffff;
        margin: 0;
    }

    /* Mobile Step Indicator */
    .mobile-step-indicator {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        background: #ffffff;
        border-bottom: 1px solid #e5e7eb;
    }

    .step-text {
        font-size: 13px;
        font-weight: 600;
        color: #6b7280;
    }

    .view-cart-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        background: #ffffff;
        border: 1.5px solid #d1d5db;
        border-radius: 8px;
        cursor: pointer;
        font-size: 11px;
        font-weight: 700;
        color: #374151;
        transition: all 0.2s;
    }

    .view-cart-btn:hover {
        background: #f9fafb;
    }

    /* Seat Map */
    .seat-map-main {
        min-height: calc(100vh - 180px);
        padding-bottom: 100px;
    }

    .seat-legend-bar {
        padding: 12px 16px;
        overflow-x: auto;
    }

    .legend-items {
        justify-content: flex-start;
        gap: 20px;
        flex-wrap: nowrap;
    }

    .legend-item {
        flex-shrink: 0;
        font-size: 11px;
    }

    .seat-demo {
        width: 22px;
        height: 22px;
    }

    .screen-section {
        padding: 20px 16px 12px;
    }

    .screen-title {
        font-size: 14px;
    }

    .seat-map-wrapper {
        padding: 16px 12px 120px;
    }

    .seat-row {
        margin-bottom: 8px;
        gap: 6px;
    }

    .row-label {
        width: 24px;
        font-size: 12px;
    }

    .seat {
        width: 30px;
        height: 30px;
        font-size: 9px;
        border-width: 2px;
    }

    /* Zoom Controls Mobile */
    .zoom-controls {
        position: fixed;
        bottom: 90px;
        right: 16px;
        flex-direction: row;
        gap: 6px;
    }

    .zoom-btn {
        width: 42px;
        height: 42px;
    }

    /* Mobile Bottom Action */
    .mobile-bottom-action {
        display: block;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #ffffff;
        padding: 16px;
        border-top: 1px solid #e5e7eb;
        z-index: 999;
        box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.08);
    }

    .btn-select-seats-mobile {
        width: 100%;
        padding: 16px;
        background: #d1d5db;
        color: #ffffff;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 700;
        cursor: not-allowed;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .btn-select-seats-mobile.active {
        background: #2563eb;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    /* Mobile Cart Drawer */
    .mobile-cart-drawer {
        display: block;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: #ffffff;
        z-index: 9999;
        transform: translateY(100%);
        transition: transform 0.3s ease;
    }

    .mobile-cart-drawer.active {
        transform: translateY(0);
    }

    .cart-drawer-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px;
        border-bottom: 1px solid #e5e7eb;
        background: #ffffff;
    }

    .cart-drawer-header h3 {
        font-size: 18px;
        font-weight: 700;
        margin: 0;
    }

    .close-drawer {
        background: none;
        border: none;
        font-size: 28px;
        cursor: pointer;
        color: #9ca3af;
        width: 36px;
        height: 36px;
    }

    .cart-drawer-content {
        padding: 16px;
        overflow-y: auto;
        max-height: calc(100vh - 70px);
    }

    .empty-cart-message {
        text-align: center;
        color: #9ca3af;
        padding: 60px 20px;
        font-size: 14px;
    }

    /* Mobile Modal */
    .modal-content {
        width: 95%;
        border-radius: 16px 16px 0 0;
        position: fixed;
        bottom: 0;
        margin: 0;
    }
}

/* Small Mobile */
@media (max-width: 375px) {
    .seat {
        width: 26px;
        height: 26px;
        font-size: 8px;
    }

    .row-label {
        width: 20px;
        font-size: 11px;
    }

    .seat-row {
        gap: 4px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    'use strict';

    let selectedSeats = [];
    let currentShowtimeId = parseInt($('#seat-map').data('showtime-id'));
    let ticketPrice = parseFloat($('#seat-map').data('ticket-price'));
    const bookingFee = 2.25;
    let cartItems = [];

    // Load seat map
    loadSeatMap(currentShowtimeId);

    // function loadSeatMap(showtimeId) {
    //     $.ajax({
    //         url: '<?php echo admin_url('admin-ajax.php'); ?>',
    //         type: 'POST',
    //         data: {
    //             action: 'cinema_get_seat_map',
    //             showtime_id: showtimeId,
    //             nonce: '<?php echo wp_create_nonce('cinema_nonce'); ?>'
    //         },
    //         success: function(response) {
    //             if (response.success) {
    //                 renderSeatMap(response.data.seats, response.data.booked);
    //             }
    //         }
    //     });
    // }

    function loadSeatMap(showtimeId) {
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'cinema_get_seat_map',
                showtime_id: showtimeId,
                nonce: '<?php echo wp_create_nonce('cinema_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Ensure booked seats is always an array
                    const bookedSeats = response.data.booked || [];
                    renderSeatMap(response.data.seats, bookedSeats);
                } else {
                    console.error('Failed to load seat map:', response);
                    renderSeatMap(0, []);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                renderSeatMap(0, []);
            }
        });
    }

    function renderSeatMap(totalSeats, bookedSeats) {
        const rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
        const seatsPerRow = 14;
        let html = '';

        rows.forEach((row, rowIndex) => {
            html += `<div class="seat-row">`;
            html += `<span class="row-label">${row}</span>`;

            for (let i = 1; i <= seatsPerRow; i++) {
                const seatNumber = row + i;
                const isBooked = bookedSeats.includes(seatNumber);

                // Add spacing for aisle
                if (i === 3 || i === 12) {
                    html += `<div style="width: 20px;"></div>`;
                }

                let seatClass = isBooked ? 'reserved' : '';

                // Add wheelchair seats (example: row C and I, seats 2 and 7)
                if ((row === 'C' || row === 'I') && (i === 2 || i === 7 || i === 13)) {
                    seatClass += ' wheelchair';
                }

                html += `<div class="seat ${seatClass}" data-seat="${seatNumber}">${i}</div>`;
            }

            html += `</div>`;
        });

        $('#seat-map').html(html);
    }

    function removeFromCart(seatNumber) {
        cartItems = cartItems.filter(item => item.seat !== seatNumber);
    }

    function updateCartUI() {
        const $cartItems = $('#cart-items');
        const $mobileCartContent = $('#mobile-cart-content');
        const $selectBtnMobile = $('#select-seats-btn-mobile');
        const $nextBtn = $('#next-cart-btn');
        const $cartSummary = $('#cart-summary');
        const $ticketCount = $('.ticket-count');
        const $ticketPrice = $('.ticket-price');

        if (cartItems.length === 0) {
            $cartItems.html('<p class="empty-cart-text">Your cart is empty</p>');
            $mobileCartContent.html('<p class="empty-cart-message">Your cart is empty</p>');
            $selectBtnMobile.prop('disabled', true).removeClass('active').text('SELECT A SEAT TO CONTINUE');
            $nextBtn.hide();
            $cartSummary.hide();
            $ticketCount.text('0 tickets');
            $ticketPrice.text('$0.00');
            return;
        }

        let html = '';
        let total = 0;

        cartItems.forEach(item => {
            total += item.price;
            html += `
                <div class="cart-item">
                    <div class="cart-item-header">
                        <span class="ticket-type">${item.type_name}</span>
                        <button class="ticket-remove" data-seat="${item.seat}">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                            </svg>
                        </button>
                    </div>
                    <div class="cart-item-details">
                        <div>Seat ${item.seat}</div>
                        <div>${item.price.toFixed(2)}</div>
                    </div>
                </div>
            `;
        });

        $cartItems.html(html);
        $mobileCartContent.html(html);
        $ticketCount.text(`${cartItems.length} ticket${cartItems.length > 1 ? 's' : ''}`);
        $ticketPrice.text(`${total.toFixed(2)}`);
        $('.summary-count').text(`${cartItems.length} ticket${cartItems.length > 1 ? 's' : ''}`);
        $('.cart-total').text(`${total.toFixed(2)}`);

        $selectBtnMobile.prop('disabled', false).addClass('active').text(`NEXT: CART (${cartItems.length})`);
        $nextBtn.show();
        $cartSummary.show();
    }

    // Remove ticket from cart
    $(document).on('click', '.ticket-remove', function() {
        const seatNumber = $(this).data('seat');
        removeFromCart(seatNumber);
        $(`.seat[data-seat="${seatNumber}"]`).removeClass('selected');
        updateCartUI();
    });

    // Mobile View Cart Button
    $('#mobile-view-cart').on('click', function() {
        $('#mobile-cart-drawer').addClass('active');
    });

    $('.close-drawer').on('click', function() {
        $('#mobile-cart-drawer').removeClass('active');
    });

    // Showtime change
    $('.time-slot-btn:not(.active)').on('click', function() {
        const newShowtimeId = parseInt($(this).data('showtime-id'));
        const screen = $(this).data('screen');
        const time = $(this).data('time');

        if (cartItems.length > 0) {
            showShowtimeChangeModal(null, newShowtimeId, screen, time);
        } else {
            changeShowtime(newShowtimeId);
        }
    });

    function showShowtimeChangeModal(seatNumber, newShowtimeId, screen, time) {
        const currentTime = '<?php echo date('g:i A', strtotime($show_time)); ?>';
        const currentSeats = cartItems.map(item => item.seat).join(', ');

        let html = `
            <li><strong>Current Showtime:</strong> ${currentTime} (${currentSeats})</li>
            <li><strong>New Showtime:</strong> ${time} (Screen ${screen})</li>
        `;

        $('#showtime-comparison').html(html);
        $('#showtime-change-modal').show();
        $('#showtime-change-modal').data('newShowtimeId', newShowtimeId);
    }

    $('#keep-tickets').on('click', function() {
        $('#showtime-change-modal').hide();
    });

    $('#remove-tickets').on('click', function() {
        const newShowtimeId = $('#showtime-change-modal').data('newShowtimeId');
        cartItems = [];
        $('.seat.selected').removeClass('selected');
        updateCartUI();
        changeShowtime(newShowtimeId);
        $('#showtime-change-modal').hide();
    });

    function changeShowtime(showtimeId) {
        currentShowtimeId = showtimeId;
        $('.time-slot-btn').removeClass('active');
        $(`.time-slot-btn[data-showtime-id="${showtimeId}"]`).addClass('active');
        loadSeatMap(showtimeId);
        const newUrl = window.location.pathname + '?showtime=' + showtimeId;
        window.history.pushState({}, '', newUrl);
    }

    // Modal close
    $('.modal-close').on('click', function() {
        $(this).closest('.modal').hide();
    });

    // Next: Cart button
    $('#select-seats-btn-mobile, #next-cart-btn').on('click', function() {
        if (cartItems.length === 0) return;

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'cinema_save_cart',
                nonce: '<?php echo wp_create_nonce('cinema_nonce'); ?>',
                showtime_id: currentShowtimeId,
                cart_items: JSON.stringify(cartItems)
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = '<?php echo home_url('/cart'); ?>?showtime=' +
                        currentShowtimeId;
                }
            }
        });
    });

    // Zoom functionality
    let zoomLevel = 1;
    const minZoom = 0.7;
    const maxZoom = 1.8;
    const zoomStep = 0.15;

    $('.zoom-in').on('click', function() {
        if (zoomLevel < maxZoom) {
            zoomLevel = Math.min(zoomLevel + zoomStep, maxZoom);
            applyZoom();
        }
    });

    $('.zoom-out').on('click', function() {
        if (zoomLevel > minZoom) {
            zoomLevel = Math.max(zoomLevel - zoomStep, minZoom);
            applyZoom();
        }
    });

    function applyZoom() {
        $('#seat-map').css('transform', `scale(${zoomLevel})`);
        $('.zoom-in').prop('disabled', zoomLevel >= maxZoom);
        $('.zoom-out').prop('disabled', zoomLevel <= minZoom);
    }

    // Fullscreen
    $('.fullscreen').on('click', function() {
        const elem = $('.seat-map-main')[0];

        if (!document.fullscreenElement) {
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.webkitRequestFullscreen) {
                elem.webkitRequestFullscreen();
            } else if (elem.mozRequestFullScreen) {
                elem.mozRequestFullScreen();
            } else if (elem.msRequestFullscreen) {
                elem.msRequestFullscreen();
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }
    });

    // Mouse wheel zoom
    $('#seat-map').on('wheel', function(e) {
        if (e.ctrlKey) {
            e.preventDefault();

            if (e.originalEvent.deltaY < 0) {
                if (zoomLevel < maxZoom) {
                    zoomLevel = Math.min(zoomLevel + 0.1, maxZoom);
                    applyZoom();
                }
            } else {
                if (zoomLevel > minZoom) {
                    zoomLevel = Math.max(zoomLevel - 0.1, minZoom);
                    applyZoom();
                }
            }
        }
    });
});
</script>

<?php get_footer(); ?>