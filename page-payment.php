<?php
/*
Template Name: Payment (Stripe & Square - Complete Working Version)
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
            <span>←</span> <span class="location-text">BACK</span>
        </button>
        
        <div class="cinema-logo">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <h2><?php bloginfo('name'); ?></h2>
                <?php endif; ?>
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
                    <span class="section-icon">💰</span>
                    <h3>PRICING</h3>
                </div>
                
                <div class="section-content">
                    <div class="pricing-row">
                        <span>Subtotal (<?php echo $total_tickets; ?> ticket<?php echo $total_tickets > 1 ? 's' : ''; ?>)</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
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
                            <span>I have read and agree to the <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Privacy Policy</a></span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Gift Card/Promo Section -->
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
                    <p style="margin-top: 10px; font-size: 12px; color: #666;">
                        Try: <code>DISCOUNT10</code>, <code>STUDENT15</code>
                    </p>
                </div>
            </div>
            
            <!-- Payment Method Selection -->
            <div class="payment-section">
                <div class="section-header">
                    <span class="section-icon">💳</span>
                    <h3>SELECT PAYMENT METHOD</h3>
                </div>
                <div class="section-content">
                    <div class="payment-method-selector">
                        <label class="payment-method-option">
                            <input type="radio" name="payment_method" value="stripe" checked>
                            <div class="method-card">
                                <div class="method-icon">💳</div>
                                <span class="method-name">Credit/Debit Card</span>
                                <span class="method-provider">Powered by Stripe</span>
                            </div>
                        </label>
                        
                        <label class="payment-method-option">
                            <input type="radio" name="payment_method" value="square">
                            <div class="method-card">
                                <div class="method-icon">💳</div>
                                <span class="method-name">Credit/Debit Card</span>
                                <span class="method-provider">Powered by Square</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Stripe Payment Section -->
            <div class="payment-section collapsed" id="stripe-payment-section">
                <div class="section-header collapsible">
                    <span class="section-icon">🔒</span>
                    <h3>Stripe Payment Details</h3>
                    <button class="expand-btn">+</button>
                </div>
                <div class="section-content" style="display: none;">
                    <form id="stripe-payment-form">
                        <div class="payment-info-box">
                            <span class="lock-icon">🔒</span>
                            <span>Secure checkout powered by Stripe</span>
                        </div>
                        
                        <div class="form-group">
                            <label>CARD INFORMATION</label>
                            <div id="stripe-card-element"></div>
                        </div>
                        
                        <div class="form-group">
                            <label>BILLING COUNTRY</label>
                            <select id="stripe-country" class="form-input">
                                <option value="">Select Country</option>
                                <option value="US">United States</option>
                                <option value="BD">Bangladesh</option>
                                <option value="GB">United Kingdom</option>
                                <option value="CA">Canada</option>
                                <option value="AU">Australia</option>
                                <option value="IN">India</option>
                                <option value="PK">Pakistan</option>
                            </select>
                        </div>
                        
                        <div id="stripe-card-errors" class="error-message"></div>
                        
                        <button type="submit" class="btn-pay" id="stripe-submit-payment" disabled>
                            PAY $<?php echo number_format($total, 2); ?> WITH STRIPE
                        </button>
                        
                        <div class="payment-test-info">
                            <strong>Test Card:</strong> 4242 4242 4242 4242 | CVV: 123 | Expiry: Any future date
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Square Payment Section -->
            <div class="payment-section collapsed" id="square-payment-section">
                <div class="section-header collapsible">
                    <span class="section-icon">🔒</span>
                    <h3>Square Payment Details</h3>
                    <button class="expand-btn">+</button>
                </div>
                <div class="section-content" style="display: none;">
                    <form id="square-payment-form">
                        <div class="payment-info-box">
                            <span class="lock-icon">🔒</span>
                            <span>Secure checkout powered by Square</span>
                        </div>
                        
                        <div class="form-group">
                            <label>CARD INFORMATION</label>
                            <div id="square-card-container"></div>
                        </div>
                        
                        <div id="square-card-errors" class="error-message"></div>
                        
                        <button type="submit" class="btn-pay" id="square-submit-payment" disabled>
                            PAY $<?php echo number_format($total, 2); ?> WITH SQUARE
                        </button>
                        
                        <div class="payment-test-info">
                            <strong>Test Card:</strong> 4111 1111 1111 1111 | CVV: 111 | Postal: 12345
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Right Sidebar -->
        <aside class="payment-sidebar">
            <div class="sidebar-ticket-info">
                <div class="ticket-header">
                    <span class="ticket-icon">🎫</span>
                    <span class="ticket-label">ORDER SUMMARY</span>
                </div>
                
                <div class="order-items">
                    <?php foreach ($cart_items as $item) : ?>
                    <div class="order-item">
                        <div class="item-details">
                            <div class="item-seat"><?php echo esc_html($item['seat']); ?></div>
                            <div class="item-type"><?php echo esc_html($item['type']); ?></div>
                        </div>
                        <div class="item-price">$<?php echo number_format($item['price'], 2); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="sidebar-total">
                    <span>Total Amount</span>
                    <span class="total-amount">$<?php echo number_format($total, 2); ?></span>
                </div>
            </div>
            
            <div class="sidebar-promo">
                <input type="text" placeholder="Enter promo code" class="promo-sidebar-input">
                <button class="btn-apply-sidebar">APPLY</button>
            </div>
            
            <div class="sidebar-help">
                <h4>Need Help?</h4>
                <p>Contact our support team if you have any questions about your booking.</p>
                <a href="mailto:support@cinema.com" class="help-link">support@cinema.com</a>
            </div>
        </aside>
    </div>
</div>

<!-- Hidden data for JavaScript -->
<div id="cart-data" 
     data-items='<?php echo esc_attr(json_encode($cart_items)); ?>'
     data-total="<?php echo esc_attr($total); ?>"
     style="display: none;">
</div>

<script>
// Make cart items and user data available globally
var cinema_cart_items = <?php echo json_encode($cart_items); ?>;
var cinema_user = {
    name: '<?php echo esc_js($current_user->display_name); ?>',
    email: '<?php echo esc_js($current_user->user_email); ?>',
    id: <?php echo intval($current_user->ID); ?>
};
</script>

<style>
/* Reset and Base Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    background: #f8f9fa;
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
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
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
    padding: 8px 16px;
    border-radius: 4px;
    transition: background 0.2s;
}

.back-btn:hover, .close-btn:hover {
    background: #f5f5f5;
    color: #333;
}

.location-text {
    font-size: 14px;
    font-weight: 500;
}

.cinema-logo img {
    max-height: 40px;
}

.cinema-logo h2 {
    font-size: 24px;
    color: #333;
    font-weight: 600;
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
    opacity: 0.4;
    transition: opacity 0.3s;
}

.step.active, .step.completed {
    opacity: 1;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
    font-weight: 600;
    font-size: 16px;
    color: #999;
    transition: all 0.3s;
}

.step.active .step-number {
    background: #4A90E2;
    color: white;
}

.step.completed .step-number {
    background: #28a745;
    color: white;
}

.step-label {
    font-size: 14px;
    color: #666;
    font-weight: 500;
}

/* Main Container */
.payment-container {
    display: grid;
    grid-template-columns: 1fr 400px;
    max-width: 1400px;
    margin: 0 auto;
    gap: 30px;
    padding: 30px;
    min-height: calc(100vh - 200px);
}

.payment-main {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

/* Payment Sections */
.payment-section {
    border-bottom: 1px solid #e5e5e5;
}

.payment-section:last-child {
    border-bottom: none;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 24px 30px;
    background: #fafafa;
}

.section-header.collapsible {
    cursor: pointer;
    transition: background 0.2s;
}

.section-header.collapsible:hover {
    background: #f0f0f0;
}

.section-icon {
    font-size: 20px;
}

.section-header h3 {
    flex: 1;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.5px;
    color: #333;
    text-transform: uppercase;
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
    transition: transform 0.3s;
}

.section-content {
    padding: 30px;
}

/* Pricing Section */
.pricing-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    font-size: 15px;
    color: #666;
    border-bottom: 1px solid #f0f0f0;
}

.pricing-row:last-of-type {
    border-bottom: none;
}

.pricing-row.total-row {
    padding-top: 16px;
    margin-top: 8px;
    border-top: 2px solid #333;
    font-size: 18px;
    font-weight: 700;
    color: #333;
}

.terms-checkbox {
    margin-top: 24px;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 6px;
    font-size: 13px;
}

.terms-checkbox label {
    display: flex;
    align-items: start;
    gap: 12px;
    cursor: pointer;
    color: #666;
}

.terms-checkbox input[type="checkbox"] {
    margin-top: 2px;
    width: 18px;
    height: 18px;
    cursor: pointer;
    flex-shrink: 0;
}

.terms-checkbox a {
    color: #4A90E2;
    text-decoration: none;
    font-weight: 500;
}

.terms-checkbox a:hover {
    text-decoration: underline;
}

/* Payment Method Selector */
.payment-method-selector {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.payment-method-option {
    cursor: pointer;
}

.payment-method-option input[type="radio"] {
    display: none;
}

.method-card {
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s;
    background: white;
}

.method-card:hover {
    border-color: #4A90E2;
    box-shadow: 0 4px 12px rgba(74,144,226,0.15);
    transform: translateY(-2px);
}

.payment-method-option input[type="radio"]:checked + .method-card {
    border-color: #4A90E2;
    background: #f0f7ff;
}

.method-icon {
    font-size: 32px;
    margin-bottom: 10px;
}

.method-name {
    display: block;
    font-size: 15px;
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
}

.method-provider {
    display: block;
    font-size: 12px;
    color: #999;
}

/* Payment Forms */
.payment-info-box {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: #e8f4f8;
    border-radius: 6px;
    margin-bottom: 24px;
    font-size: 13px;
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
    font-weight: 600;
    font-size: 11px;
    letter-spacing: 0.5px;
    color: #666;
    text-transform: uppercase;
}

#stripe-card-element,
#square-card-container {
    padding: 14px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: white;
    min-height: 45px;
    transition: border-color 0.3s;
}

#stripe-card-element:focus-within,
#square-card-container:focus-within {
    border-color: #4A90E2;
    box-shadow: 0 0 0 3px rgba(74,144,226,0.1);
}

.form-input {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    background: white;
    transition: border-color 0.3s;
}

.form-input:focus {
    outline: none;
    border-color: #4A90E2;
    box-shadow: 0 0 0 3px rgba(74,144,226,0.1);
}

.error-message {
    color: #dc3545;
    font-size: 13px;
    margin-top: 8px;
    min-height: 20px;
}

/* Pay Button */
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
    transition: all 0.3s;
    margin-top: 10px;
}

.btn-pay:hover:not(:disabled) {
    background: #357ABD;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(74,144,226,0.3);
}

.btn-pay:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
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

.payment-test-info {
    margin-top: 12px;
    padding: 12px;
    background: #fff3cd;
    border-radius: 6px;
    font-size: 12px;
    color: #856404;
    text-align: center;
}

/* Promo Form */
.promo-form {
    display: flex;
    gap: 10px;
}

.promo-input,
.promo-sidebar-input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.promo-input:focus,
.promo-sidebar-input:focus {
    outline: none;
    border-color: #4A90E2;
}

.btn-apply,
.btn-apply-sidebar {
    padding: 12px 24px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    letter-spacing: 0.5px;
    transition: background 0.3s;
}

.btn-apply:hover,
.btn-apply-sidebar:hover {
    background: #218838;
}

/* Sidebar */
.payment-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.sidebar-ticket-info {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.ticket-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f0f0f0;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
    color: #666;
}

.ticket-icon {
    font-size: 18px;
}

.order-items {
    margin-bottom: 20px;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.item-details {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.item-seat {
    font-weight: 600;
    color: #333;
    font-size: 15px;
}

.item-type {
    font-size: 12px;
    color: #999;
    text-transform: capitalize;
}

.item-price {
    font-weight: 600;
    color: #4A90E2;
    font-size: 15px;
}

.sidebar-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 16px;
    border-top: 2px solid #333;
    font-weight: 700;
}

.total-amount {
    font-size: 24px;
    color: #4A90E2;
}

.sidebar-promo {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.promo-sidebar-input {
    width: 100%;
    margin-bottom: 10px;
}

.btn-apply-sidebar {
    width: 100%;
}

.sidebar-help {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.sidebar-help h4 {
    font-size: 14px;
    color: #333;
    margin-bottom: 8px;
}

.sidebar-help p {
    font-size: 13px;
    color: #666;
    margin-bottom: 12px;
    line-height: 1.6;
}

.help-link {
    color: #4A90E2;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
}

.help-link:hover {
    text-decoration: underline;
}

/* Modal Styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 20px;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
}

.modal-content {
    position: relative;
    background: white;
    border-radius: 12px;
    padding: 40px;
    text-align: center;
    max-width: 500px;
    width: 100%;
    z-index: 1;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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
    font-size: 26px;
    margin-bottom: 10px;
    color: #333;
}

.modal-content p {
    color: #666;
    margin-bottom: 20px;
    font-size: 15px;
}

.booking-ref {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 24px;
    font-size: 14px;
}

.booking-ref strong {
    display: block;
    margin-bottom: 8px;
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-primary, .btn-secondary {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    margin-bottom: 12px;
    font-size: 14px;
    transition: all 0.3s;
}

.btn-primary {
    background: #4A90E2;
    color: white;
}

.btn-primary:hover {
    background: #357ABD;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #f5f5f5;
    color: #333;
    border: 1px solid #ddd;
}

.btn-secondary:hover {
    background: #e0e0e0;
}

/* Notifications */
.notifications-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10001;
    max-width: 400px;
}

.notification {
    background: white;
    padding: 16px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 15px;
    transform: translateX(450px);
    opacity: 0;
    transition: all 0.3s;
    min-width: 300px;
}

.notification.notification-show {
    transform: translateX(0);
    opacity: 1;
}

.notification.notification-hide {
    transform: translateX(450px);
    opacity: 0;
}

.notification.error {
    border-left: 4px solid #dc3545;
}

.notification.success {
    border-left: 4px solid #28a745;
}

.notification.info {
    border-left: 4px solid #17a2b8;
}

.notification-icon svg {
    display: block;
}

.notification-message {
    flex: 1;
    color: #333;
    font-size: 14px;
}

.notification-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #999;
    padding: 0;
    line-height: 1;
    width: 24px;
    height: 24px;
}

.notification-close:hover {
    color: #666;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .payment-container {
        grid-template-columns: 1fr;
    }
    
    .payment-sidebar {
        order: -1;
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
        font-size: 20px;
    }
    
    .progress-steps {
        gap: 40px;
        padding: 20px;
    }
    
    .payment-container {
        padding: 15px;
    }
    
    .section-header,
    .section-content {
        padding: 20px;
    }
    
    .payment-method-selector {
        grid-template-columns: 1fr;
    }
    
    .notifications-container {
        left: 20px;
        right: 20px;
        max-width: none;
    }
    
    .notification {
        min-width: auto;
    }
}

@media (max-width: 480px) {
    .progress-steps {
        gap: 20px;
    }
    
    .step-label {
        font-size: 12px;
    }
    
    .step-number {
        width: 32px;
        height: 32px;
        font-size: 14px;
    }
}
</style>

<?php get_footer(); ?>