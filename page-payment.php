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
            <span>←</span> 525 Lighthouse Ave, Pacific Grove, CA
        </button>
        
        <div class="cinema-logo">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><h2>Adeebdds</h2></a>
        </div>
        
        <button class="close-btn" onclick="window.location.href='<?php echo home_url('/movies'); ?>'">
            ✕
        </button>
        
        <div class="tickets-summary">
            <span class="ticket-icon">🎫</span>
            <span>TICKETS</span>
            <span><?php echo $total_tickets; ?> tickets · $<?php echo number_format($total, 2); ?></span>
            <input type="text" placeholder="Add Gift Card, Voucher, Promo Code" class="promo-header-input">
            <button class="btn-apply-header">APPLY</button>
        </div>
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
            <div class="payment-section">
                <div class="section-header">
                    <span class="section-icon">$</span>
                    <h3>PRICING</h3>
                </div>
                
                <div class="pricing-content">
                    <div class="pricing-row">
                        <span>Booking Fee</span>
                        <span>$<?php echo number_format($booking_fee, 2); ?></span>
                    </div>
                    <div class="pricing-row total">
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
            <div class="payment-section">
                <div class="section-header collapsible">
                    <span class="section-icon">💳</span>
                    <h3>Add Default Credit Card</h3>
                    <button class="expand-btn">+</button>
                </div>
                <div class="section-content">
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
                            <label>Card information</label>
                            <div id="card-element" class="stripe-input"></div>
                        </div>
                        
                        <div class="form-group">
                            <label>Country</label>
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
                fontSize: '16px',
                color: '#32325d',
                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                '::placeholder': {
                    color: '#aab7c4'
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
/* Copy all CSS from the second code - keeping it exactly the same */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.payment-page-wrapper {
    background: #f5f5f5;
    min-height: 100vh;
}

.payment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 30px;
    background: white;
    border-bottom: 1px solid #e0e0e0;
    position: relative;
}

.back-btn, .close-btn {
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
    color: #666;
}

.back-btn {
    display: flex;
    align-items: center;
    gap: 5px;
}

.cinema-logo h2 {
    font-size: 24px;
    text-transform: lowercase;
    color: #333;
    font-weight: 600;
}

.tickets-summary {
    position: absolute;
    right: 80px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 14px;
}

.ticket-icon {
    font-size: 18px;
}

.promo-header-input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
    width: 250px;
}

.btn-apply-header {
    padding: 8px 16px;
    background: #f0f0f0;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
}

.progress-steps {
    display: flex;
    justify-content: center;
    gap: 100px;
    padding: 25px;
    background: white;
    border-bottom: 1px solid #e0e0e0;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    opacity: 0.4;
}

.step.active, .step.completed {
    opacity: 1;
}

.step-number {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #d0d0d0;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
}

.step.active .step-number {
    background: #666;
    color: white;
}

.step.completed .step-number {
    background: #666;
    color: white;
}

.step-label {
    font-size: 13px;
}

.payment-container {
    display: grid;
    grid-template-columns: 1fr 420px;
    max-width: 1400px;
    margin: 0 auto;
    gap: 0;
}

.payment-main {
    background: white;
}

.payment-section {
    border-bottom: 1px solid #e0e0e0;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 25px 40px;
    background: #fafafa;
}

.section-header.collapsible {
    cursor: pointer;
}

.section-header.collapsible:hover {
    background: #f5f5f5;
}

.section-icon {
    font-size: 20px;
}

.section-header h3 {
    flex: 1;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.expand-btn {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #666;
    padding: 5px 10px;
}

.section-content {
    padding: 30px 40px;
}

.pricing-content {
    padding: 20px 40px;
}

.pricing-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    font-size: 15px;
}

.pricing-row.total {
    padding-top: 15px;
    border-top: 1px solid #e0e0e0;
    margin-top: 10px;
    font-size: 17px;
    font-weight: 700;
}

.terms-checkbox {
    margin-top: 20px;
    font-size: 14px;
}

.terms-checkbox label {
    display: flex;
    align-items: start;
    gap: 10px;
    cursor: pointer;
}

.terms-checkbox input[type="checkbox"] {
    margin-top: 3px;
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.terms-checkbox a {
    color: #0066cc;
    text-decoration: none;
}

.promo-form {
    display: flex;
    gap: 10px;
}

.promo-input {
    flex: 1;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
}

.btn-apply {
    padding: 12px 24px;
    background: #f0f0f0;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

.payment-method-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.tab-btn {
    flex: 1;
    padding: 12px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.tab-btn.active {
    border-color: #0066cc;
    background: #f0f7ff;
}

.card-icon {
    font-size: 18px;
}

.stripe-info {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px;
    background: #f0f7ff;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 14px;
    color: #0066cc;
}

.lock-icon {
    font-size: 16px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    font-size: 14px;
}

.stripe-input {
    padding: 14px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: white;
}

.form-input {
    width: 100%;
    padding: 14px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.form-input:focus {
    outline: none;
    border-color: #0066cc;
}

.error-message {
    color: #fa755a;
    font-size: 13px;
    margin-top: 10px;
}

.btn-pay {
    width: 100%;
    padding: 16px;
    background: #4A90E2;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 700;
    font-size: 15px;
    letter-spacing: 0.5px;
    transition: background 0.3s;
}

.btn-pay:hover {
    background: #357ABD;
}

.btn-pay:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #fff;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.payment-sidebar {
    background: #f9f9f9;
    padding: 40px 30px;
    border-left: 1px solid #e0e0e0;
}

.sidebar-promo {
    background: white;
    padding: 20px;
    border-radius: 8px;
}

.promo-sidebar-input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    margin-bottom: 10px;
}

.btn-apply-sidebar {
    width: 100%;
    padding: 12px;
    background: #f0f0f0;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

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
    border-radius: 12px;
    padding: 40px;
    text-align: center;
    max-width: 500px;
    z-index: 1;
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
}

.modal-content p {
    color: #666;
    margin-bottom: 20px;
}

.booking-ref {
    background: #f5f5f5;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.btn-primary, .btn-secondary {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    margin-bottom: 10px;
}

.btn-primary {
    background: #0066cc;
    color: white;
}

.btn-secondary {
    background: #f0f0f0;
    color: #333;
}

.notifications-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10001;
}

.notification {
    background: white;
    padding: 16px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 15px;
    transform: translateX(400px);
    opacity: 0;
    transition: all 0.3s;
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
}

@media (max-width: 1024px) {
    .payment-container {
        grid-template-columns: 1fr;
    }
    
    .tickets-summary {
        position: static;
        transform: none;
        margin-top: 15px;
    }
}
</style>

<?php get_footer(); ?>