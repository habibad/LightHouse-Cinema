<?php
/**
 * Square Payment Integration - With Complete Refund Support
 * Compatible with existing Stripe implementation
 */

class Cinema_Square_Payment {
    
    private $square_client;
    private $location_id;
    
    public function __construct() {
        // Initialize Square SDK
        $this->init_square();
        
        // AJAX handlers
        add_action('wp_ajax_cinema_create_square_payment', array($this, 'create_square_payment'));
        add_action('wp_ajax_nopriv_cinema_create_square_payment', array($this, 'create_square_payment'));
        
        add_action('wp_ajax_cinema_confirm_square_payment', array($this, 'confirm_square_payment'));
        add_action('wp_ajax_nopriv_cinema_confirm_square_payment', array($this, 'confirm_square_payment'));
    }
    
    /**
     * Initialize Square SDK
     */
    private function init_square() {
        require_once get_stylesheet_directory() . '/includes/vendor/autoload.php';
        
        $environment = SQUARE_ENVIRONMENT === 'production' ? 
            \Square\Environment::PRODUCTION : 
            \Square\Environment::SANDBOX;
        
        $this->square_client = new \Square\SquareClient([
            'accessToken' => SQUARE_ACCESS_TOKEN,
            'environment' => $environment,
        ]);
        
        $this->location_id = SQUARE_LOCATION_ID;
    }
    
    /**
     * Create Square payment
     */
    public function create_square_payment() {
        check_ajax_referer('cinema_nonce', 'nonce');
        
        try {
            $booking_id = intval($_POST['booking_id']);
            $amount = intval($_POST['amount']); // Already in cents from JavaScript
            $nonce = sanitize_text_field($_POST['source_id']); // Square payment token
            $currency = isset($_POST['currency']) ? sanitize_text_field($_POST['currency']) : 'USD';
            
            // Validate inputs
            if (!$amount || $amount <= 0) {
                throw new Exception('Invalid payment amount: ' . $amount . ' cents');
            }
            
            if (empty($nonce)) {
                throw new Exception('Payment token is required');
            }
            
            // Log for debugging
            error_log('Square Payment - Amount in cents: ' . $amount);
            error_log('Square Payment - Booking ID: ' . $booking_id);
            error_log('Square Payment - Currency: ' . $currency);
            error_log('Square Payment - Nonce: ' . substr($nonce, 0, 10) . '...');
            
            global $cinema_booking_manager;
            $booking = $cinema_booking_manager->get_booking($booking_id);
            
            if (!$booking) {
                throw new Exception('Booking not found');
            }
            
            // Create payment
            $payments_api = $this->square_client->getPaymentsApi();
            
            // Create Money object properly
            $amount_money = new \Square\Models\Money();
            $amount_money->setAmount((int)$amount);
            $amount_money->setCurrency($currency);
            
            // Verify the Money object was created correctly
            if (!$amount_money->getAmount() || !$amount_money->getCurrency()) {
                error_log('Square Payment - Money object validation failed');
                throw new Exception('Failed to create payment amount object');
            }
            
            error_log('Square Payment - Money object created: Amount=' . $amount_money->getAmount() . ', Currency=' . $amount_money->getCurrency());
            
            // Generate unique idempotency key
            $idempotency_key = bin2hex(random_bytes(16));
            error_log('Square Payment - Idempotency key: ' . $idempotency_key);
            
            // Create payment request
            $body = new \Square\Models\CreatePaymentRequest(
                $nonce,
                $idempotency_key
            );
            
            // Set amount money explicitly
            $body->setAmountMoney($amount_money);
            
            // Set other required fields
            $body->setAutocomplete(false); // Don't auto-complete, we'll confirm later
            $body->setLocationId($this->location_id);
            $body->setReferenceId('BOOKING-' . $booking->booking_reference);
            
            // Determine if movie or event booking for note
            $title = '';
            if (isset($booking->movie_title) && $booking->movie_title) {
                $title = $booking->movie_title;
            } elseif (isset($booking->event_title) && $booking->event_title) {
                $title = $booking->event_title;
            } else {
                $title = 'Cinema Booking';
            }
            
            $body->setNote("Cinema Ticket: {$title} - " . count($booking->seats) . " seat(s)");
            
            // Add customer info if available
            if (!empty($booking->customer_email)) {
                $body->setBuyerEmailAddress($booking->customer_email);
            }
            
            error_log('Square Payment - Sending request to API...');
            
            // Make the API call
            $api_response = $payments_api->createPayment($body);
            
            if ($api_response->isSuccess()) {
                $result = $api_response->getResult();
                $payment = $result->getPayment();
                
                error_log('Square Payment - Payment created successfully! ID: ' . $payment->getId());
                error_log('Square Payment - Status: ' . $payment->getStatus());
                
                // Update booking with Square payment ID
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'cinema_bookings',
                    array('square_payment_id' => $payment->getId()),
                    array('id' => $booking_id)
                );
                
                wp_send_json_success(array(
                    'payment_id' => $payment->getId(),
                    'status' => $payment->getStatus(),
                    'receipt_url' => $payment->getReceiptUrl()
                ));
            } else {
                $errors = $api_response->getErrors();
                error_log('Square Payment - API returned errors');
                
                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        error_log('Square Error - Category: ' . $error->getCategory());
                        error_log('Square Error - Code: ' . $error->getCode());
                        error_log('Square Error - Detail: ' . $error->getDetail());
                    }
                    $error_message = $errors[0]->getDetail();
                } else {
                    $error_message = 'Unknown error occurred';
                }
                
                throw new Exception($error_message);
            }
            
        } catch (\Square\Exceptions\ApiException $e) {
            error_log('Square API Exception: ' . $e->getMessage());
            error_log('Square API Response: ' . print_r($e->getResponseBody(), true));
            wp_send_json_error(array('message' => 'Payment failed: ' . $e->getMessage()));
        } catch (Exception $e) {
            error_log('Square Payment Exception: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Confirm Square payment and complete booking
     */
    public function confirm_square_payment() {
        check_ajax_referer('cinema_nonce', 'nonce');
        
        try {
            $payment_id = sanitize_text_field($_POST['payment_id']);
            $booking_id = intval($_POST['booking_id']);
            
            error_log('Square Confirm - Payment ID: ' . $payment_id);
            error_log('Square Confirm - Booking ID: ' . $booking_id);
            
            // Complete the payment
            $payments_api = $this->square_client->getPaymentsApi();
            
            $body = new \Square\Models\CompletePaymentRequest();
            $api_response = $payments_api->completePayment($payment_id, $body);
            
            if ($api_response->isSuccess()) {
                $result = $api_response->getResult();
                $payment = $result->getPayment();
                
                error_log('Square Confirm - Payment status: ' . $payment->getStatus());
                
                if ($payment->getStatus() === 'COMPLETED') {
                    global $cinema_booking_manager;
                    
                    // Update booking status
                    $cinema_booking_manager->update_booking_status($booking_id, 'confirmed', array(
                        'payment_status' => 'paid',
                        'payment_method' => 'square',
                        'payment_id' => $payment_id,
                        'receipt_url' => $payment->getReceiptUrl()
                    ));
                    
                    // Get booking details
                    $booking = $cinema_booking_manager->get_booking($booking_id);
                    
                    // Send confirmation email
                    $this->send_confirmation_email($booking);
                    
                    // Clear session
                    if (session_id()) {
                        unset($_SESSION['cinema_cart']);
                        unset($_SESSION['pending_booking_id']);
                    }
                    
                    wp_send_json_success(array(
                        'message' => 'Payment successful',
                        'booking_reference' => $booking->booking_reference,
                        'receipt_url' => $payment->getReceiptUrl(),
                        'booking' => $booking
                    ));
                } else {
                    throw new Exception('Payment not completed. Status: ' . $payment->getStatus());
                }
            } else {
                $errors = $api_response->getErrors();
                $error_message = isset($errors[0]) ? $errors[0]->getDetail() : 'Unknown error';
                error_log('Square Confirm Error: ' . $error_message);
                throw new Exception($error_message);
            }
            
        } catch (Exception $e) {
            error_log('Square Confirm Payment Exception: ' . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Process Square refund - FIXED VERSION
     * This is called when a booking is cancelled
     */
    public function process_refund($payment_id, $amount_cents = null, $reason = 'Customer cancellation') {
        try {
            error_log('Square Refund - Starting refund for payment: ' . $payment_id);
            error_log('Square Refund - Amount (cents): ' . ($amount_cents ? $amount_cents : 'full refund'));
            error_log('Square Refund - Reason: ' . $reason);
            
            $refunds_api = $this->square_client->getRefundsApi();
            
            // Generate unique idempotency key for refund
            $idempotency_key = 'refund_' . bin2hex(random_bytes(16));
            
            // Create refund request
            $body = new \Square\Models\RefundPaymentRequest(
                $idempotency_key,
                $payment_id
            );
            
            // If specific amount is provided, set it (otherwise full refund)
            if ($amount_cents !== null) {
                $amount_money = new \Square\Models\Money();
                $amount_money->setAmount((int)$amount_cents);
                $amount_money->setCurrency('USD');
                $body->setAmountMoney($amount_money);
            }
            
            // Set reason for refund
            $body->setReason($reason);
            
            error_log('Square Refund - Sending refund request to API...');
            
            // Make the API call
            $api_response = $refunds_api->refundPayment($body);
            
            if ($api_response->isSuccess()) {
                $result = $api_response->getResult();
                $refund = $result->getRefund();
                
                error_log('Square Refund - Refund successful! ID: ' . $refund->getId());
                error_log('Square Refund - Status: ' . $refund->getStatus());
                error_log('Square Refund - Amount: ' . $refund->getAmountMoney()->getAmount());
                
                return array(
                    'success' => true,
                    'refund_id' => $refund->getId(),
                    'status' => $refund->getStatus(),
                    'amount' => $refund->getAmountMoney()->getAmount() / 100, // Convert cents to dollars
                    'message' => 'Refund processed successfully'
                );
            } else {
                $errors = $api_response->getErrors();
                error_log('Square Refund - API returned errors');
                
                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        error_log('Square Refund Error - Category: ' . $error->getCategory());
                        error_log('Square Refund Error - Code: ' . $error->getCode());
                        error_log('Square Refund Error - Detail: ' . $error->getDetail());
                    }
                    $error_message = $errors[0]->getDetail();
                } else {
                    $error_message = 'Unknown error occurred';
                }
                
                error_log('Square Refund - Failed: ' . $error_message);
                
                return array(
                    'success' => false,
                    'message' => 'Refund failed: ' . $error_message
                );
            }
            
        } catch (\Square\Exceptions\ApiException $e) {
            error_log('Square Refund API Exception: ' . $e->getMessage());
            error_log('Square Refund API Response: ' . print_r($e->getResponseBody(), true));
            
            return array(
                'success' => false,
                'message' => 'Refund API error: ' . $e->getMessage()
            );
        } catch (Exception $e) {
            error_log('Square Refund Exception: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            return array(
                'success' => false,
                'message' => 'Refund error: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Send confirmation email
     */
    private function send_confirmation_email($booking) {
        $to = $booking->customer_email;
        $subject = "Booking Confirmation - {$booking->booking_reference}";
        
        $seat_list = array();
        if (isset($booking->seats) && is_array($booking->seats)) {
            $seat_list = array_map(function($seat) {
                if (is_object($seat)) {
                    return $seat->seat_number;
                } elseif (is_array($seat)) {
                    return isset($seat['seat_number']) ? $seat['seat_number'] : '';
                } else {
                    return (string) $seat;
                }
            }, $booking->seats);
        }
        
        $message = $this->get_email_template($booking, $seat_list);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Cinema <noreply@cinema.com>'
        );
        
        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Email template
     */
    private function get_email_template($booking, $seat_list) {
        ob_start();
        
        // Get movie/event title
        $title = '';
        if (isset($booking->movie_title) && $booking->movie_title) {
            $title = $booking->movie_title;
        } elseif (isset($booking->event_title) && $booking->event_title) {
            $title = $booking->event_title;
        } else {
            $title = 'Cinema Booking';
        }
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .header { background: #1a1a1a; color: white; padding: 20px; text-align: center; }
    .content { background: #f9f9f9; padding: 30px; }
    .booking-details { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
    .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
    .detail-label { font-weight: bold; }
    .seats { background: #e8f4f8; padding: 15px; margin: 15px 0; border-radius: 5px; }
    .qr-code { text-align: center; margin: 20px 0; }
    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    .button { display: inline-block; padding: 12px 30px; background: #4A90E2; color: white; text-decoration: none; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Cinema Booking</h1>
            <p>Booking Confirmation</p>
        </div>
        <div class="content">
            <h2>Thank you for your booking!</h2>
            <p>Dear <?php echo esc_html($booking->customer_name); ?>,</p>
            <p>Your tickets have been confirmed. Below are your booking details:</p>
            <div class="booking-details">
                <div class="detail-row">
                    <span class="detail-label">Booking Reference:</span>
                    <span><?php echo esc_html($booking->booking_reference); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Title:</span>
                    <span><?php echo esc_html($title); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span><?php echo date('l, F j, Y', strtotime($booking->show_date)); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time:</span>
                    <span><?php echo date('g:i A', strtotime($booking->show_time)); ?></span>
                </div>
                <?php if (isset($booking->screen_number) && $booking->screen_number): ?>
                <div class="detail-row">
                    <span class="detail-label">Screen:</span>
                    <span>Screen <?php echo esc_html($booking->screen_number); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($seat_list)): ?>
            <div class="seats">
                <strong>Your Seats:</strong><br>
                <?php echo implode(', ', $seat_list); ?>
                <br><small>(<?php echo count($seat_list); ?> seat<?php echo count($seat_list) > 1 ? 's' : ''; ?>)</small>
            </div>
            <?php endif; ?>
            <div class="detail-row">
                <span class="detail-label">Total Amount Paid:</span>
                <span><strong>$<?php echo number_format($booking->total_amount, 2); ?></strong></span>
            </div>
            <div class="qr-code">
                <p><strong>Show this code at the cinema:</strong></p>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($booking->booking_reference); ?>" alt="QR Code">
                <p><strong><?php echo esc_html($booking->booking_reference); ?></strong></p>
            </div>
            <center>
                <a href="<?php echo home_url('/my-account/'); ?>" class="button">View My Bookings</a>
            </center>
            <p style="margin-top: 30px;">
                <strong>Important Notes:</strong><br>
                • Please arrive at least 15 minutes before showtime<br>
                • Present this email or booking reference at the counter<br>
                • Cancellations must be made at least 2 hours before showtime
            </p>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Cinema. All rights reserved.</p>
            <p>If you have any questions, contact us at support@cinema.com</p>
        </div>
    </div>
</body>
</html>
<?php
        return ob_get_clean();
    }
    
    /**
     * Handle Square webhook events
     */
    public function handle_webhook() {
        $payload = @file_get_contents('php://input');
        $signature = isset($_SERVER['HTTP_X_SQUARE_SIGNATURE']) ? $_SERVER['HTTP_X_SQUARE_SIGNATURE'] : '';
        
        try {
            // Verify webhook signature if secret is configured
            if (defined('SQUARE_WEBHOOK_SECRET') && SQUARE_WEBHOOK_SECRET && SQUARE_WEBHOOK_SECRET !== 'YOUR_SQUARE_WEBHOOK_SECRET') {
                if (!$this->verify_webhook_signature($payload, $signature)) {
                    http_response_code(401);
                    exit('Invalid signature');
                }
            }
            
            $event = json_decode($payload, true);
            
            if (!$event) {
                http_response_code(400);
                exit('Invalid payload');
            }
            
            if (isset($event['type'])) {
                switch ($event['type']) {
                    case 'payment.updated':
                        if (isset($event['data']['object']['payment'])) {
                            $this->handle_payment_updated($event['data']['object']['payment']);
                        }
                        break;
                        
                    case 'refund.updated':
                        if (isset($event['data']['object']['refund'])) {
                            $this->handle_refund_updated($event['data']['object']['refund']);
                        }
                        break;
                }
            }
            
            http_response_code(200);
            
        } catch (Exception $e) {
            error_log('Square webhook error: ' . $e->getMessage());
            http_response_code(400);
            exit();
        }
    }
    
    /**
     * Verify webhook signature
     */
    private function verify_webhook_signature($payload, $signature) {
        if (!defined('SQUARE_WEBHOOK_SECRET') || !SQUARE_WEBHOOK_SECRET) {
            return true; // Skip verification if not configured
        }
        
        $webhook_secret = SQUARE_WEBHOOK_SECRET;
        $hmac = base64_encode(hash_hmac('sha256', $payload, $webhook_secret, true));
        return hash_equals($hmac, $signature);
    }
    
    /**
     * Handle payment updated webhook
     */
    private function handle_payment_updated($payment) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'cinema_bookings';
        
        if (!isset($payment['id'])) {
            return;
        }
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $bookings_table WHERE square_payment_id = %s",
            $payment['id']
        ));
        
        if ($booking) {
            $status = isset($payment['status']) ? $payment['status'] : '';
            
            if ($status === 'COMPLETED') {
                $wpdb->update(
                    $bookings_table,
                    array(
                        'booking_status' => 'confirmed',
                        'payment_status' => 'paid'
                    ),
                    array('id' => $booking->id)
                );
            } elseif ($status === 'FAILED' || $status === 'CANCELED') {
                $wpdb->update(
                    $bookings_table,
                    array(
                        'booking_status' => 'failed',
                        'payment_status' => 'failed'
                    ),
                    array('id' => $booking->id)
                );
            }
        }
    }
    
    /**
     * Handle refund updated webhook
     */
    private function handle_refund_updated($refund) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'cinema_bookings';
        
        if (!isset($refund['status']) || !isset($refund['payment_id'])) {
            return;
        }
        
        if ($refund['status'] === 'COMPLETED') {
            $wpdb->update(
                $bookings_table,
                array('payment_status' => 'refunded'),
                array('square_payment_id' => $refund['payment_id'])
            );
        }
    }
}

// Initialize Square Payment
$GLOBALS['cinema_square_payment'] = new Cinema_Square_Payment();

// Webhook endpoint
add_action('rest_api_init', function() {
    register_rest_route('cinema/v1', '/square-webhook', array(
        'methods' => 'POST',
        'callback' => function() {
            global $cinema_square_payment;
            $cinema_square_payment->handle_webhook();
        },
        'permission_callback' => '__return_true'
    ));
});