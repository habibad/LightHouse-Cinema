<?php
/*
Template Name: Payment
*/

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/?redirect_to=' . urlencode($_SERVER['REQUEST_URI'])));
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

// Calculate totals
$subtotal = 0;
$total_tickets = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'];
    $total_tickets++;
}

$booking_fee = $total_tickets * 2.25;
$total = $subtotal + $booking_fee;

// Get current user
$current_user = wp_get_current_user();

get_header();
?>

<div class="payment-page-wrapper">
    <div class="payment-header">
        <button class="back-btn" onclick="history.back()">
            <span>←</span> <span class="location-text">RANCHO SANTA MARGARITA</span>
        </button>
        
        <div class="cinema-logo">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                <h2>cinépolis</h2>
            </a>
        </div>
        
        <button class="close-btn" onclick="window.location.href='<?php echo home_url('/movies'); ?>'">
            ✕
        </button>
    </div>
    
    <div class="progress-steps">
        <div class="step completed">
            <span class="step-number">✓</span>
            <span class="step-label">Seats</span>
        </div>
        <div class="step completed">
            <span class="step-number">✓</span>
            <span class="step-label">Cart</span>
        </div>
        <div class="step active">
            <span class="step-number">3</span>
            <span class="step-label">Payment</span>
        </div>
    </div>
    
    <div class="payment-container">
        <div class="payment-main">
            <!-- Pricing Section -->
            <div class="payment-section pricing-section">
                <div class="section-header">
                    <span class="section-icon">$</span>
                    <h3>PRICING</h3>
                </div>
                
                <div class="section-content">
                    <div class="pricing-row">
                        <span>Booking Fee</span>
                        <span>$<?php echo number_format($booking_fee, 2); ?></span>
                    </div>
                    <div class="pricing-row total-row">
                        <span>Total</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <div class="terms-checkbox">
                        <label>
                            <input type="checkbox" id="terms-agreement" required>
                            <span>I have read and agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Gift Card Section -->
            <div class="payment-section collapsed">
                <div class="section-header collapsible">
                    <span class="section-icon">🎁</span>
                    <h3>Add Gift Card, Voucher, Promo Code</h3>
                    <button class="expand-btn">+</button>
                </div>
                <div class="section-content" style="display: none;">
                    <div class="promo-form">
                        <input type="text" placeholder="Enter code" class="promo-input">
                        <button class="btn-apply">APPLY</button>
                    </div>
                </div>
            </div>
            
            <!-- Credit Card Section -->
            <div class="payment-section collapsed">
                <div class="section-header collapsible">
                    <span class="section-icon">💳</span>
                    <h3>Add Default Credit Card</h3>
                    <button class="expand-btn">+</button>
                </div>
                <div class="section-content" style="display: none;">
                    <form id="payment-form">
                        <div class="payment-method-tabs">
                            <button type="button" class="tab-btn active" data-method="card">
                                <span class="card-icon">💳</span>
                                Card
                            </button>
                        </div>
                        
                        <div class="stripe-info">
                            <span class="lock-icon">🔒</span>
                            <span>Secure, fast checkout with Link</span>
                        </div>
                        
                        <div class="form-group">
                            <label>CARD INFORMATION</label>
                            <div id="card-element" class="stripe-input"></div>
                        </div>
                        
                        <div class="form-group">
                            <label>COUNTRY</label>
                            <select id="country" class="form-input">
                                <option value="BD">Bangladesh</option>
                                <option value="US">United States</option>
                                <option value="UK">United Kingdom</option>
                                <option value="CA">Canada</option>
                                <option value="AU">Australia</option>
                            </select>
                        </div>
                        
                        <div id="card-errors" class="error-message"></div>
                        
                        <button type="submit" class="btn-pay" id="submit-payment">
                            PAY $<?php echo number_format($total, 2); ?> WITH CARD
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Right Sidebar -->
        <aside class="payment-sidebar">
            <div class="sidebar-ticket-info">
                <div class="ticket-header">
                    <span class="ticket-icon">🎫</span>
                    <span class="ticket-label">TICKETS</span>
                </div>
                <div class="ticket-details">
                    <span><?php echo $total_tickets; ?> ticket<?php echo $total_tickets > 1 ? 's' : ''; ?></span>
                    <span class="ticket-price">$<?php echo number_format($total, 2); ?></span>
                </div>
            </div>
            
            <div class="sidebar-promo">
                <input type="text" placeholder="Add Gift Card, Voucher, Promo Code" class="promo-sidebar-input">
                <button class="btn-apply-sidebar">APPLY</button>
            </div>
        </aside>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
jQuery(document).ready(function($) {
    'use strict';

    // Initialize Stripe
    const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
    const elements = stripe.elements();
    
    // Create card element
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '14px',
                color: '#333',
                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                '::placeholder': {
                    color: '#aaa'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        },
        hidePostalCode: false
    });
    
    cardElement.mount('#card-element');
    
    // Handle real-time validation errors
    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });
    
    // Toggle sections
    $('.collapsible').on('click', function() {
        const $section = $(this).closest('.payment-section');
        const $content = $section.find('.section-content');
        const $btn = $(this).find('.expand-btn');
        
        $content.slideToggle(300);
        $btn.text($btn.text() === '+' ? '−' : '+');
        $section.toggleClass('collapsed');
    });
    
    // Handle form submission
    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-payment');
    
    form.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        // Check terms agreement
        if (!$('#terms-agreement').is(':checked')) {
            showNotification('Please accept the terms and conditions', 'error');
            return;
        }
        
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner"></span> Processing...';
        
        try {
            console.log('Step 1: Creating booking...');
            
            // Step 1: Create booking
            const bookingResponse = await createBooking({
                cart_items: <?php echo json_encode($cart_items); ?>,
                customer_name: '<?php echo esc_js($current_user->display_name); ?>',
                customer_email: '<?php echo esc_js($current_user->user_email); ?>',
                customer_phone: ''
            });
            
            console.log('Booking Response:', bookingResponse);
            
            if (!bookingResponse.success) {
                throw new Error(bookingResponse.data?.message || bookingResponse.message || 'Failed to create booking');
            }
            
            const bookingId = bookingResponse.data?.booking_id || bookingResponse.booking_id;
            
            if (!bookingId) {
                throw new Error('No booking ID received from server');
            }
            
            console.log('Booking ID:', bookingId);
            console.log('Step 2: Creating payment intent...');
            
            // Step 2: Create payment intent
            const intentResponse = await createPaymentIntent(bookingId, <?php echo $total; ?>);
            
            console.log('Intent Response:', intentResponse);
            
            if (!intentResponse.success && !intentResponse.client_secret) {
                throw new Error(intentResponse.data?.message || intentResponse.message || 'Failed to initialize payment');
            }
            
            const clientSecret = intentResponse.data?.client_secret || intentResponse.client_secret;
            
            if (!clientSecret) {
                throw new Error('No client secret received from server');
            }
            
            console.log('Step 3: Confirming card payment...');
            
            // Step 3: Confirm card payment
            const {error, paymentIntent} = await stripe.confirmCardPayment(
                clientSecret,
                {
                    payment_method: {
                        card: cardElement,
                        billing_details: {
                            name: '<?php echo esc_js($current_user->display_name); ?>',
                            email: '<?php echo esc_js($current_user->user_email); ?>'
                        }
                    }
                }
            );
            
            if (error) {
                console.error('Stripe error:', error);
                throw new Error(error.message);
            }
            
            console.log('Payment Intent Status:', paymentIntent.status);
            
            if (paymentIntent.status === 'succeeded') {
                console.log('Step 4: Confirming payment...');
                
                // Step 4: Confirm payment
                const confirmResponse = await confirmPayment(paymentIntent.id, bookingId);
                
                console.log('Confirm Response:', confirmResponse);
                
                if (confirmResponse.success) {
                    const bookingRef = confirmResponse.data?.booking_reference || confirmResponse.booking_reference;
                    showSuccessModal(bookingRef);
                } else {
                    throw new Error('Payment succeeded but booking confirmation failed');
                }
            } else {
                throw new Error('Payment not completed. Status: ' + paymentIntent.status);
            }
            
        } catch (error) {
            console.error('Payment error:', error);
            showNotification(error.message, 'error');
            submitButton.disabled = false;
            submitButton.innerHTML = 'PAY $<?php echo number_format($total, 2); ?> WITH CARD';
        }
    });
    
    // Create booking
    async function createBooking(data) {
        try {
            const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cinema_create_booking_payment',
                    nonce: '<?php echo wp_create_nonce('cinema_nonce'); ?>',
                    cart_items: JSON.stringify(data.cart_items),
                    customer_name: data.customer_name,
                    customer_email: data.customer_email,
                    customer_phone: data.customer_phone
                })
            });
            
            const result = await response.json();
            console.log('Raw booking response:', result);
            return result;
            
        } catch (error) {
            console.error('Booking creation error:', error);
            throw error;
        }
    }
    
    // Create payment intent
    async function createPaymentIntent(bookingId, amount) {
        try {
            const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cinema_create_payment_intent',
                    nonce: '<?php echo wp_create_nonce('cinema_nonce'); ?>',
                    booking_id: bookingId,
                    amount: amount
                })
            });
            
            const result = await response.json();
            console.log('Raw payment intent response:', result);
            return result;
            
        } catch (error) {
            console.error('Payment intent creation error:', error);
            throw error;
        }
    }
    
    // Confirm payment
    async function confirmPayment(paymentIntentId, bookingId) {
        try {
            const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cinema_confirm_payment',
                    nonce: '<?php echo wp_create_nonce('cinema_nonce'); ?>',
                    payment_intent_id: paymentIntentId,
                    booking_id: bookingId
                })
            });
            
            const result = await response.json();
            console.log('Raw confirm response:', result);
            return result;
            
        } catch (error) {
            console.error('Payment confirmation error:', error);
            throw error;
        }
    }
    
    // Show success modal
    function showSuccessModal(bookingReference) {
        const modalHTML = `
            <div id="payment-success-modal" class="modal" style="display: flex;">
                <div class="modal-overlay"></div>
                <div class="modal-content">
                    <div class="success-icon">✓</div>
                    <h3>Payment Successful!</h3>
                    <p>Your booking has been confirmed</p>
                    <div class="booking-ref">
                        <strong>Confirmation:</strong> ${bookingReference}
                    </div>
                    <button class="btn-primary" onclick="window.location.href='<?php echo home_url('/my-account'); ?>'">
                        View My Bookings
                    </button>
                    <button class="btn-secondary" onclick="window.location.href='<?php echo home_url('/movies'); ?>'">
                        Book Another Movie
                    </button>
                </div>
            </div>
        `;
        
        $('body').append(modalHTML);
        
        // Clear cart
        $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'cinema_clear_cart',
            nonce: '<?php echo wp_create_nonce('cinema_nonce'); ?>'
        });
    }
    
    // Show notification
    function showNotification(message, type = 'error') {
        const notification = `
            <div class="notification ${type}">
                <span>${message}</span>
                <button onclick="this.parentElement.remove()">×</button>
            </div>
        `;
        
        if (!$('.notifications-container').length) {
            $('body').append('<div class="notifications-container"></div>');
        }
        
        const $notif = $(notification);
        $('.notifications-container').append($notif);
        
        setTimeout(() => $notif.addClass('show'), 10);
        setTimeout(() => $notif.removeClass('show'), 5000);
        setTimeout(() => $notif.remove(), 5300);
    }
});
</script>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    background: #fff;
}

.payment-page-wrapper {
    background: #fff;
    min-height: 100vh;
}

/* Header */
.payment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 40px;
    background: white;
    border-bottom: 1px solid #e5e5e5;
}

.back-btn, .close-btn {
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
    color: #666;
    display: flex;
    align-items: center;
    gap: 8px;
}

.back-btn:hover, .close-btn:hover {
    color: #333;
}

.location-text {
    font-size: 14px;
}

.cinema-logo h2 {
    font-size: 28px;
    color: #333;
    font-weight: 400;
    font-style: italic;
}

.cinema-logo a {
    text-decoration: none;
    color: inherit;
}

/* Progress Steps */
.progress-steps {
    display: flex;
    justify-content: center;
    gap: 80px;
    padding: 30px;
    background: white;
    border-bottom: 1px solid #e5e5e5;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    opacity: 0.3;
}

.step.active, .step.completed {
    opacity: 1;
}

.step-number {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
    font-weight: 600;
    font-size: 14px;
    color: #999;
}

.step.active .step-number {
    background: #333;
    color: white;
}

.step.completed .step-number {
    background: #333;
    color: white;
}

.step-label {
    font-size: 14px;
    color: #666;
}

/* Main Container */
.payment-container {
    display: grid;
    grid-template-columns: 1fr 420px;
    max-width: 1400px;
    margin: 0 auto;
    min-height: calc(100vh - 200px);
}

.payment-main {
    background: white;
    border-right: 1px solid #e5e5e5;
}

/* Payment Sections */
.payment-section {
    border-bottom: 1px solid #e5e5e5;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 24px 40px;
    background: #fafafa;
}

.section-header.collapsible {
    cursor: pointer;
    transition: background 0.2s;
}

.section-header.collapsible:hover {
    background: #f5f5f5;
}

.section-icon {
    font-size: 18px;
}

.section-header h3 {
    flex: 1;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
    color: #333;
}

.expand-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.section-content {
    padding: 30px 40px;
}

/* Pricing Section */
.pricing-section .section-content {
    padding: 24px 40px 30px;
}

.pricing-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 16px;
    font-size: 15px;
    color: #666;
}

.pricing-row.total-row {
    padding-top: 16px;
    border-top: 1px solid #e5e5e5;
    margin-top: 8px;
    font-size: 16px;
    font-weight: 700;
    color: #333;
}

.terms-checkbox {
    margin-top: 24px;
    font-size: 13px;
}

.terms-checkbox label {
    display: flex;
    align-items: start;
    gap: 10px;
    cursor: pointer;
    color: #666;
}

.terms-checkbox input[type="checkbox"] {
    margin-top: 2px;
    width: 16px;
    height: 16px;
    cursor: pointer;
    flex-shrink: 0;
}

.terms-checkbox a {
    color: #0066cc;
    text-decoration: none;
}

.terms-checkbox a:hover {
    text-decoration: underline;
}

/* Promo Form */
.promo-form {
    display: flex;
    gap: 10px;
}

.promo-input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.promo-input:focus {
    outline: none;
    border-color: #0066cc;
}

.btn-apply {
    padding: 12px 24px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    letter-spacing: 0.5px;
}

.btn-apply:hover {
    background: #ebebeb;
}

/* Payment Method Tabs */
.payment-method-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.tab-btn {
    flex: 1;
    padding: 14px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 14px;
    transition: all 0.2s;
}

.tab-btn.active {
    border-color: #0066cc;
    background: #f0f7ff;
    color: #0066cc;
}

.card-icon {
    font-size: 18px;
}

/* Stripe Info */
.stripe-info {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: #f0f7ff;
    border-radius: 4px;
    margin-bottom: 24px;
    font-size: 13px;
    color: #0066cc;
}

.lock-icon {
    font-size: 14px;
}

/* Form Groups */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 11px;
    letter-spacing: 0.5px;
    color: #666;
    text-transform: uppercase;
}

.stripe-input {
    padding: 14px 16px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: white;
}

.form-input {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    background: white;
}

.form-input:focus {
    outline: none;
    border-color: #0066cc;
}

.error-message {
    color: #fa755a;
    font-size: 13px;
    margin-top: 8px;
}

/* Pay Button */
.btn-pay {
    width: 100%;
    padding: 16px;
    background: #4A90E2;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 700;
    font-size: 14px;
    letter-spacing: 0.5px;
    transition: background 0.3s;
}

.btn-pay:hover:not(:disabled) {
    background: #357ABD;
}

.btn-pay:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid #fff;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
    vertical-align: middle;
    margin-right: 8px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Sidebar */
.payment-sidebar {
    background: #fafafa;
    padding: 40px 30px;
}

.sidebar-ticket-info {
    background: white;
    padding: 20px;
    border-radius: 4px;
    margin-bottom: 20px;
    border: 1px solid #e5e5e5;
}

.ticket-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    color: #666;
}

.ticket-icon {
    font-size: 16px;
}

.ticket-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    color: #333;
}

.ticket-price {
    font-weight: 600;
    font-size: 16px;
}

.sidebar-promo {
    background: white;
    padding: 20px;
    border-radius: 4px;
    border: 1px solid #e5e5e5;
}

.promo-sidebar-input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 12px;
    font-size: 13px;
}

.promo-sidebar-input:focus {
    outline: none;
    border-color: #0066cc;
}

.btn-apply-sidebar {
    width: 100%;
    padding: 12px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    letter-spacing: 0.5px;
}

.btn-apply-sidebar:hover {
    background: #ebebeb;
}

/* Modal */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
}

.modal-content {
    position: relative;
    background: white;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    max-width: 500px;
    z-index: 1;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.success-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #28a745;
    color: white;
    font-size: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.modal-content h3 {
    font-size: 24px;
    margin-bottom: 10px;
    color: #333;
}

.modal-content p {
    color: #666;
    margin-bottom: 20px;
    font-size: 15px;
}

.booking-ref {
    background: #f5f5f5;
    padding: 16px;
    border-radius: 4px;
    margin-bottom: 24px;
    font-size: 14px;
    color: #333;
}

.booking-ref strong {
    display: block;
    margin-bottom: 4px;
    font-size: 12px;
    color: #666;
}

.btn-primary, .btn-secondary {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    margin-bottom: 12px;
    font-size: 14px;
    transition: all 0.2s;
}

.btn-primary {
    background: #0066cc;
    color: white;
}

.btn-primary:hover {
    background: #0052a3;
}

.btn-secondary {
    background: #f5f5f5;
    color: #333;
    border: 1px solid #ddd;
}

.btn-secondary:hover {
    background: #ebebeb;
}

/* Notifications */
.notifications-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10001;
}

.notification {
    background: white;
    padding: 16px 20px;
    border-radius: 4px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 15px;
    transform: translateX(400px);
    opacity: 0;
    transition: all 0.3s;
    min-width: 300px;
}

.notification.show {
    transform: translateX(0);
    opacity: 1;
}

.notification.error {
    border-left: 4px solid #dc3545;
}

.notification.success {
    border-left: 4px solid #28a745;
}

.notification button {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #999;
    padding: 0;
    line-height: 1;
}

.notification button:hover {
    color: #666;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .payment-container {
        grid-template-columns: 1fr;
    }
    
    .payment-main {
        border-right: none;
    }
    
    .payment-sidebar {
        border-top: 1px solid #e5e5e5;
    }
}

@media (max-width: 768px) {
    .payment-header {
        padding: 15px 20px;
    }
    
    .location-text {
        display: none;
    }
    
    .cinema-logo h2 {
        font-size: 24px;
    }
    
    .progress-steps {
        gap: 40px;
        padding: 20px;
    }
    
    .section-header,
    .section-content,
    .pricing-section .section-content {
        padding: 20px;
    }
    
    .payment-sidebar {
        padding: 20px;
    }
}
</style>

<?php get_footer(); ?>