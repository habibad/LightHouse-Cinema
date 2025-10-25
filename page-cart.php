<?php
/*
Template Name: Cart
*/

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

if (!session_id()) {
    session_start();
}

$cart_data = isset($_SESSION['cinema_cart']) ? $_SESSION['cinema_cart'] : null;

if (!$cart_data) {
    wp_redirect(home_url('/movies'));
    exit;
}

$cart_items = $cart_data['items'];

// Group items by showtime
$grouped_items = array();
foreach ($cart_items as $item) {
    $showtime_id = $item['showtime_id'];
    if (!isset($grouped_items[$showtime_id])) {
        $grouped_items[$showtime_id] = array(
            'showtime_id' => $showtime_id,
            'items' => array()
        );
    }
    $grouped_items[$showtime_id]['items'][] = $item;
}

// Calculate totals
$subtotal = 0;
$total_tickets = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'];
    $total_tickets++;
}

$booking_fee = $total_tickets * 2.25;
$total = $subtotal + $booking_fee;

get_header();
?>

<div class="cart-page-container">
    <!-- Header -->
    <div class="cart-top-header">
        <button class="back-link" onclick="history.back()">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
            </svg>
            <span>525 Lighthouse Ave, Pacific Grove, CA</span>
        </button>

        <div class="cart-logo">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/logo.png" alt="logo">
            </a>
        </div>

        <button class="close-cart-btn" onclick="window.location.href='<?php echo home_url('/movies'); ?>'">
            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
            </svg>
        </button>
    </div>

    <!-- Progress Bar -->
    <div class="progress-bar-section">
        <div class="progress-step completed">
            <div class="step-circle">
                <svg width="16" height="16" fill="white" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                </svg>
            </div>
            <span class="step-text">Seats</span>
        </div>

        <div class="progress-line completed"></div>

        <div class="progress-step active">
            <div class="step-circle">2</div>
            <span class="step-text">Cart</span>
        </div>

        <div class="progress-line"></div>

        <div class="progress-step">
            <div class="step-circle">3</div>
            <span class="step-text">Payment</span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="cart-content-wrapper">
        <div class="cart-left-panel">
            <!-- Redemptions Section -->
            <div class="cart-accordion-section">
                <button class="accordion-header" onclick="toggleAccordion(this)">
                    <div class="accordion-left">
                        <svg class="section-icon" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                        </svg>
                        <div>
                            <h3>REDEMPTIONS AVAILABLE</h3>
                            <p class="section-subtitle">0 Points available to spend</p>
                        </div>
                    </div>
                    <svg class="chevron-icon" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M16.59 8.59L12 13.17 7.41 8.59 6 10l6 6 6-6z" />
                    </svg>
                </button>
            </div>

            <!-- Tickets Section -->
            <div class="cart-accordion-section active">
                <button class="accordion-header" onclick="toggleAccordion(this)">
                    <div class="accordion-left">
                        <svg class="section-icon" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M22 10V6c0-1.11-.9-2-2-2H4c-1.11 0-2 .89-2 2v4c1.11 0 2 .89 2 2s-.89 2-2 2v4c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2v-4c-1.11 0-2-.89-2-2s.89-2 2-2z" />
                        </svg>
                        <div>
                            <h3>TICKETS</h3>
                        </div>
                    </div>
                    <div class="accordion-right">
                        <span class="tickets-summary"><?php echo $total_tickets; ?> tickets -
                            $<?php echo number_format($total, 2); ?></span>
                        <svg class="chevron-icon" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M16.59 8.59L12 13.17 7.41 8.59 6 10l6 6 6-6z" />
                        </svg>
                    </div>
                </button>

                <div class="accordion-content">
                    <?php foreach ($grouped_items as $group) : 
                        $showtime_id = $group['showtime_id'];
                        $items = $group['items'];
                        $post_type = get_post_type($showtime_id);
                        $is_event = ($post_type === 'event_showtimes');
                        
                        if ($is_event) {
                            $event_id = get_post_meta($showtime_id, '_event_showtime_event_id', true);
                            $title = get_the_title($event_id);
                            $thumbnail = get_post_meta($event_id, '_event_thumbnail', true);
                            $venue = get_post_meta($event_id, '_event_venue', true);
                            $show_date = get_post_meta($showtime_id, '_event_showtime_date', true);
                            $show_time = get_post_meta($showtime_id, '_event_showtime_time', true);
                            $location_text = $venue;
                        } else {
                            $movie_id = get_post_meta($showtime_id, '_showtime_movie_id', true);
                            $title = get_the_title($movie_id);
                            $thumbnail = get_post_meta($movie_id, '_movie_trailer_banner', true);
                            $show_date = get_post_meta($showtime_id, '_showtime_date', true);
                            $show_time = get_post_meta($showtime_id, '_showtime_time', true);
                            $screen = get_post_meta($showtime_id, '_showtime_screen', true);
                            $location_text = 'RANCHO SANTA MARGARITA · SCREEN ' . $screen;
                        }
                        
                        $formatted_date = date('l \a\t g:i a', strtotime($show_date . ' ' . $show_time));
                    ?>
                    <div class="movie-booking-group">
                        <button class="movie-group-header" onclick="toggleMovieGroup(this)">
                            <div class="movie-info-section">
                                <div class="movie-thumbnail-wrapper">
                                    <?php
                                    if (!empty($thumbnail)) {
                                        // Determine which ID to use for alt text
                                        $alt_title = $is_event ? get_the_title($event_id) : get_the_title($movie_id);
                                        
                                        // Check if thumbnail is an attachment ID or URL
                                        if (is_numeric($thumbnail)) {
                                            // It's an attachment ID - use wp_get_attachment_image
                                            echo wp_get_attachment_image(
                                                (int) $thumbnail,
                                                'medium',
                                                false,
                                                array(
                                                    'class' => 'movie-poster-img', 
                                                    'alt' => esc_attr($alt_title)
                                                )
                                            );
                                        } else {
                                            // It's a direct URL
                                            $banner_url = esc_url($thumbnail);
                                            echo '<img src="' . $banner_url . '" class="movie-poster-img" alt="' . esc_attr($alt_title) . '">';
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="movie-details">
                                    <h4 class="movie-title"><?php echo esc_html($title); ?></h4>
                                    <p class="movie-datetime"><?php echo esc_html($formatted_date); ?></p>
                                    <p class="movie-location">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                                        </svg>
                                        <?php echo esc_html($location_text); ?>
                                    </p>
                                </div>
                            </div>
                            <svg class="collapse-icon" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 8l-6 6 1.41 1.41L12 10.83l4.59 4.58L18 14z" />
                            </svg>
                        </button>

                        <div class="tickets-list-container">
                            <?php 
                            // Group tickets by type
                            $tickets_by_type = array();
                            foreach ($items as $item) {
                                // echo json_encode($item);
                                $type = $is_event ? 'Event Ticket' : (isset($item['ticket_type']) ? $item['ticket_type'] : 'Adult');
                                if (!isset($tickets_by_type[$type])) {
                                    $tickets_by_type[$type] = array(
                                        'tickets' => array(),
                                        'price' => $item['price']
                                    );
                                }
                                $tickets_by_type[$type]['tickets'][] = $item;
                            }
                            
                            foreach ($tickets_by_type as $type => $data) :
                                $ticket_count = count($data['tickets']);
                                $total_price = $ticket_count * $data['price'];
                                $seats = array_map(function($t) { return $t['seat']; }, $data['tickets']);
                            ?>
                            <div class="ticket-type-group">
                                <div class="ticket-type-header">
                                    <div class="ticket-type-info">
                                        <h5><?php echo esc_html($type); ?></h5>
                                        <p class="seat-numbers">Seat<?php echo $ticket_count > 1 ? 's' : ''; ?>
                                            <?php echo implode(', ', $seats); ?></p>
                                    </div>
                                    <div class="ticket-type-actions">
                                        <button class="remove-ticket-btn"
                                            data-seats="<?php echo implode(',', $seats); ?>"
                                            data-showtime="<?php echo $showtime_id; ?>">
                                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" />
                                            </svg>
                                        </button>
                                        <span class="ticket-quantity"><?php echo $ticket_count; ?></span>
                                    </div>
                                </div>
                                <div class="ticket-type-price">$<?php echo number_format($total_price, 2); ?></div>
                            </div>

                            <?php if ($type !== 'Event Ticket') : ?>
                            <div class="ticket-type-breakdown">
                                <?php foreach ($data['tickets'] as $ticket) : ?>
                                <div class="individual-ticket">
                                    <span><?php echo esc_html($type); ?></span>
                                    <span class="seat-label">Seat <?php echo esc_html($ticket['seat']); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="cart-right-panel">
            <div class="pricing-card">
                <div class="pricing-card-header">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z" />
                    </svg>
                    <h3>PRICING</h3>
                </div>

                <div class="pricing-breakdown">
                    <div class="pricing-item">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="pricing-item">
                        <span><?php echo $total_tickets; ?>x Booking Fee</span>
                        <span>$<?php echo number_format($booking_fee, 2); ?></span>
                    </div>
                    <div class="pricing-total">
                        <span>TOTAL</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>

                <div class="promo-code-section">
                    <div class="promo-input-wrapper">
                        <input type="text" placeholder="Add Gift Card, Voucher, Promo Code" class="promo-input"
                            id="promo-code">
                        <button class="apply-promo-btn">APPLY</button>
                    </div>
                </div>

                <button class="next-payment-btn" id="proceed-payment">
                    NEXT: PAYMENT
                </button>
            </div>
        </div>
    </div>
</div>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f5f5f5;
}

.cart-page-container {
    background: #f8f8f8;
    min-height: 100vh;
}

/* Header */
.cart-top-header {
    background: white;
    padding: 16px 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e5e5e5;
}

.back-link {
    display: flex;
    align-items: center;
    gap: 8px;
    background: none;
    border: none;
    color: #666;
    font-size: 14px;
    cursor: pointer;
    transition: color 0.3s;
}

.back-link:hover {
    color: #000;
}

.cart-logo h1 {
    font-size: 26px;
    font-weight: 300;
    color: #0a3d62;
    letter-spacing: 1px;
    text-transform: lowercase;
    font-style: italic;
}

.close-cart-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: #999;
    padding: 8px;
    transition: color 0.3s;
}

.close-cart-btn:hover {
    color: #000;
}

/* Progress Bar */
.progress-bar-section {
    background: white;
    padding: 24px 32px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 40px;
    border-bottom: 1px solid #e5e5e5;
}

.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #d8d8d8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: #999;
    font-size: 15px;
    transition: all 0.3s;
}

.progress-step.completed .step-circle {
    background: #5a5a5a;
    color: white;
}

.progress-step.active .step-circle {
    background: #5a5a5a;
    color: white;
}

.step-text {
    font-size: 13px;
    color: #666;
}

.progress-line {
    width: 80px;
    height: 2px;
    background: #d8d8d8;
    margin-bottom: 28px;
}

.progress-line.completed {
    background: #5a5a5a;
}

/* Main Content */
.cart-content-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 0;
}

.cart-left-panel {
    background: white;
}

/* Accordion Sections */
.cart-accordion-section {
    border-bottom: 1px solid #e8e8e8;
}

.accordion-header {
    width: 100%;
    padding: 20px 32px;
    background: #fafafa;
    border: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: background 0.3s;
}

.accordion-header:hover {
    background: #f5f5f5;
}

.accordion-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.section-icon {
    color: #666;
}

.accordion-left h3 {
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.5px;
    color: #333;
    margin-bottom: 4px;
}

.section-subtitle {
    font-size: 12px;
    color: #999;
}

.accordion-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.tickets-summary {
    font-size: 13px;
    color: #666;
    font-weight: 500;
}

.chevron-icon {
    color: #999;
    transition: transform 0.3s;
}

.cart-accordion-section.active .chevron-icon {
    transform: rotate(180deg);
}

.accordion-content {
    display: none;
    background: white;
}

.cart-accordion-section.active .accordion-content {
    display: block;
}

/* Movie Booking Group */
.movie-booking-group {
    border-bottom: 1px solid #f0f0f0;
}

.movie-group-header {
    width: 100%;
    padding: 20px 32px;
    background: white;
    border: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: background 0.3s;
}

.movie-group-header:hover {
    background: none;
}

.movie-info-section {
    display: flex;
    gap: 16px;
    align-items: flex-start;
}

.movie-thumbnail-wrapper {
    width: 200px;
    height: 100px;
    flex-shrink: 0;
}

.movie-poster-img{
    height: 118px;
    width: 300px;
}

.movie-thumbnail {
    width: 60px;
    height: 90px;
    object-fit: cover;
    border-radius: 4px;
}

.movie-details {
    text-align: left;
    align-self: center;
}

.movie-title {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 6px;
}

.movie-datetime {
    font-size: 13px;
    color: #666;
    margin-bottom: 4px;
}

.movie-location {
    font-size: 12px;
    color: #999;
    display: flex;
    align-items: center;
    gap: 4px;
}

.collapse-icon {
    color: #999;
    transition: transform 0.3s;
}

.movie-booking-group.collapsed .collapse-icon {
    transform: rotate(180deg);
}

/* Tickets List */
.tickets-list-container {
    padding: 0px 10px;
}

.movie-booking-group.collapsed .tickets-list-container {
    display: none;
}

.ticket-type-group {
    background: #fafafa;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.ticket-type-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex: 1;
}

.ticket-type-info h5 {
    font-size: 15px;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 4px;
}

.seat-numbers {
    font-size: 13px;
    color: #666;
}

.ticket-type-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.remove-ticket-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: #999;
    padding: 4px;
    transition: color 0.3s;
}

.remove-ticket-btn:hover {
    color: #e74c3c;
}

.ticket-quantity {
    font-size: 14px;
    color: #666;
    min-width: 20px;
    text-align: center;
}

.ticket-type-price {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a1a;
    margin-left: 20px;
}

.ticket-type-breakdown {
    margin-top: 8px;
    padding-top: 12px;
    border-top: 1px solid #e8e8e8;
}

.individual-ticket {
    display: none;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 13px;
    color: #666;
}

.seat-label {
    color: #999;
}

/* Right Panel - Pricing */
.cart-right-panel {
    background: #f8f8f8;
    padding: 32px 24px;
}

.pricing-card {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    position: sticky;
    top: 20px;
}

.pricing-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e8e8e8;
}

.pricing-card-header svg {
    color: #0a3d62;
}

.pricing-card-header h3 {
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.5px;
    color: #333;
}

.pricing-breakdown {
    margin-bottom: 20px;
}

.pricing-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    font-size: 14px;
    color: #666;
}

.pricing-total {
    display: flex;
    justify-content: space-between;
    padding-top: 16px;
    margin-top: 16px;
    border-top: 2px solid #e8e8e8;
    font-size: 16px;
    font-weight: 700;
    color: #1a1a1a;
}

.promo-code-section {
    margin-bottom: 20px;
}

.promo-input-wrapper {
    display: flex;
    gap: 8px;
}

.promo-input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #d8d8d8;
    border-radius: 4px;
    font-size: 13px;
    color: #666;
}

.promo-input:focus {
    outline: none;
    border-color: #0a3d62;
}

.apply-promo-btn {
    padding: 12px 20px;
    background: #e8e8e8;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.5px;
    color: #666;
    cursor: pointer;
    transition: background 0.3s;
}

.apply-promo-btn:hover {
    background: #d8d8d8;
}

.next-payment-btn {
    width: 100%;
    padding: 16px;
    background: #0a3d62;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.5px;
    cursor: pointer;
    transition: background 0.3s;
}

.next-payment-btn:hover {
    background: #083152;
}

/* Responsive */
@media (max-width: 1024px) {
    .cart-content-wrapper {
        grid-template-columns: 1fr;
    }

    .cart-right-panel {
        border-top: 1px solid #e5e5e5;
    }

    .pricing-card {
        position: static;
    }
}

@media (max-width: 768px) {
    .cart-top-header {
        padding: 12px 16px;
    }

    .back-link span {
        display: none;
    }

    .progress-bar-section {
        padding: 20px 16px;
        gap: 20px;
    }

    .progress-line {
        width: 40px;
    }

    .accordion-header,
    .movie-group-header {
        padding: 16px;
    }

    .tickets-list-container {
        padding: 0 16px 16px 76px;
    }

    .cart-right-panel {
        padding: 20px 16px;
    }
}
</style>

<script>
function toggleAccordion(button) {
    const section = button.closest('.cart-accordion-section');
    section.classList.toggle('active');
}

function toggleMovieGroup(button) {
    const group = button.closest('.movie-booking-group');
    group.classList.toggle('collapsed');
}

jQuery(document).ready(function($) {
    'use strict';

    // Remove ticket
    $('.remove-ticket-btn').on('click', function(e) {
        e.stopPropagation();

        const seats = $(this).data('seats').toString().split(',');
        const showtimeId = $(this).data('showtime');

        if (!confirm('Remove these tickets from your cart?')) {
            return;
        }

        // Remove each seat
        const removePromises = seats.map(seat => {
            return $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'cinema_remove_cart_item',
                    nonce: '<?php echo wp_create_nonce('cinema_nonce'); ?>',
                    seat: seat.trim(),
                    showtime_id: showtimeId
                }
            });
        });

        Promise.all(removePromises).then(() => {
            location.reload();
        });
    });

    // Proceed to payment
    $('#proceed-payment').on('click', function() {
        $(this).prop('disabled', true).text('Processing...');
        window.location.href = '<?php echo home_url('/payment'); ?>';
    });

    // Apply promo code
    $('.apply-promo-btn').on('click', function() {
        const promoCode = $('#promo-code').val().trim();

        if (!promoCode) {
            alert('Please enter a promo code');
            return;
        }

        $(this).prop('disabled', true).text('APPLYING...');

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'cinema_apply_promo_code',
                nonce: '<?php echo wp_create_nonce('cinema_nonce'); ?>',
                promo_code: promoCode
            },
            success: function(response) {
                if (response.success) {
                    alert('Promo code applied successfully!');
                    location.reload();
                } else {
                    alert(response.data.message || 'Invalid promo code');
                    $('.apply-promo-btn').prop('disabled', false).text('APPLY');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $('.apply-promo-btn').prop('disabled', false).text('APPLY');
            }
        });
    });

    // Allow Enter key for promo code
    $('#promo-code').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('.apply-promo-btn').click();
        }
    });
});
</script>

<?php get_footer(); ?>