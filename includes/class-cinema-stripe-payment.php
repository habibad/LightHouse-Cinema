<?php
/**
 * Stripe Payment Integration
 */

class Cinema_Stripe_Payment {
    
    public function __construct() {
        add_action('wp_ajax_cinema_create_payment_intent', array($this, 'create_payment_intent'));
        add_action('wp_ajax_nopriv_cinema_create_payment_intent', array($this, 'create_payment_intent'));
        
        add_action('wp_ajax_cinema_confirm_payment', array($this, 'confirm_payment'));
        add_action('wp_ajax_nopriv_cinema_confirm_payment', array($this, 'confirm_payment'));
    }

    public function create_payment_intent() {
    check_ajax_referer('cinema_nonce', 'nonce');
    
    try {
        $booking_id = intval($_POST['booking_id']);
        $amount = floatval($_POST['amount']);
        
        global $cinema_booking_manager;
        $booking = $cinema_booking_manager->get_booking($booking_id);
        
        if (!$booking) {
            throw new Exception('Booking not found');
        }
        
        // Create payment intent
        $intent = \Stripe\PaymentIntent::create([
            'amount' => $amount * 100, // Convert to cents
            'currency' => 'usd',
            'metadata' => [
                'booking_id' => $booking_id,
                'booking_reference' => $booking->booking_reference,
                'customer_email' => $booking->customer_email,
                'movie_title' => $booking->movie_title,
                'showtime_id' => $booking->showtime_id
            ],
            'description' => "Movie Ticket: {$booking->movie_title} - " . count($booking->seats) . " seat(s)"
        ]);
        
        // Update booking with payment intent ID
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'cinema_bookings',
            array('stripe_payment_intent' => $intent->id),
            array('id' => $booking_id)
        );
        
        wp_send_json_success([
            'client_secret' => $intent->client_secret,  // Return the client secret
            'payment_intent_id' => $intent->id
        ]);
        
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

    
    /**
     * Confirm payment and update booking
     */
    public function confirm_payment() {
        check_ajax_referer('cinema_nonce', 'nonce');
        
        try {
            $payment_intent_id = sanitize_text_field($_POST['payment_intent_id']);
            $booking_id = intval($_POST['booking_id']);
            
            // Retrieve payment intent from Stripe
            $intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
            
            if ($intent->status === 'succeeded') {
                global $cinema_booking_manager;
                
                // Update booking status
                $cinema_booking_manager->update_booking_status($booking_id, 'confirmed', [
                    'payment_status' => 'paid',
                    'payment_method' => 'stripe',
                    'payment_intent_id' => $payment_intent_id,
                    'charge_id' => $intent->charges->data[0]->id ?? ''
                ]);
                
                // Get booking details
                $booking = $cinema_booking_manager->get_booking($booking_id);
                
                // Send confirmation email
                $this->send_confirmation_email($booking);
                
                // Clear session
                if (session_id()) {
                    unset($_SESSION['cinema_booking']);
                    unset($_SESSION['pending_booking_id']);
                }
                
                wp_send_json_success([
                    'message' => 'Payment successful',
                    'booking_reference' => $booking->booking_reference,
                    'booking' => $booking
                ]);
            } else {
                throw new Exception('Payment not completed');
            }
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Send confirmation email
     */
    private function send_confirmation_email($booking) {
        $to = $booking->customer_email;
        $subject = "Booking Confirmation - {$booking->booking_reference}";
        
        $seat_list = array_map(function($seat) {
            return $seat->seat_number;
        }, $booking->seats);
        
        $message = $this->get_email_template($booking, $seat_list);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Cinépolis <noreply@cinepolis.com>'
        );
        
        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Email template
     */
    private function get_email_template($booking, $seat_list) {
        ob_start();
        ?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        color: #333;
    }

    .container {
        max-width: 600px;
        margin: 0 auto;
        padding: 20px;
    }

    .header {
        background: #1a1a1a;
        color: white;
        padding: 20px;
        text-align: center;
    }

    .content {
        background: #f9f9f9;
        padding: 30px;
    }

    .booking-details {
        background: white;
        padding: 20px;
        margin: 20px 0;
        border-radius: 8px;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }

    .detail-label {
        font-weight: bold;
    }

    .seats {
        background: #e8f4f8;
        padding: 15px;
        margin: 15px 0;
        border-radius: 5px;
    }

    .qr-code {
        text-align: center;
        margin: 20px 0;
    }

    .footer {
        text-align: center;
        padding: 20px;
        color: #666;
        font-size: 12px;
    }

    .button {
        display: inline-block;
        padding: 12px 30px;
        background: #e50914;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        margin: 15px 0;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>LightHouse Cinema</h1>
            <p>Booking Confirmation</p>
        </div>

        <div class="content">
            <h2>Thank you for your booking!</h2>
            <p>Dear <?php echo esc_html($booking->customer_name); ?>,</p>
            <p>Your movie tickets have been confirmed. Below are your booking details:</p>

            <div class="booking-details">
                <div class="detail-row">
                    <span class="detail-label">Booking Reference:</span>
                    <span><?php echo esc_html($booking->booking_reference); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Movie:</span>
                    <span><?php echo esc_html($booking->movie_title); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span><?php echo date('l, F j, Y', strtotime($booking->show_date)); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time:</span>
                    <span><?php echo date('g:i A', strtotime($booking->show_time)); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Screen:</span>
                    <span><?php echo esc_html($booking->screen_number); ?></span>
                </div>
            </div>

            <div class="seats">
                <strong>Your Seats:</strong><br>
                <?php echo implode(', ', $seat_list); ?>
                <br><small>(<?php echo count($seat_list); ?>
                    seat<?php echo count($seat_list) > 1 ? 's' : ''; ?>)</small>
            </div>

            <div class="detail-row">
                <span class="detail-label">Total Amount Paid:</span>
                <span><strong>$<?php echo number_format($booking->total_amount, 2); ?></strong></span>
            </div>

            <div class="qr-code">
                <p><strong>Show this code at the cinema:</strong></p>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($booking->booking_reference); ?>"
                    alt="QR Code">
                <p><?php echo esc_html($booking->booking_reference); ?></p>
            </div>

            <center>
                <a href="<?php echo home_url('/my-bookings/'); ?>" class="button">View My Bookings</a>
            </center>

            <p style="margin-top: 30px;">
                <strong>Important Notes:</strong><br>
                • Please arrive at least 15 minutes before showtime<br>
                • Present this email or booking reference at the counter<br>
                • Cancellations must be made at least 2 hours before showtime
            </p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Cinépolis. All rights reserved.</p>
            <p>If you have any questions, contact us at support@cinepolis.com</p>
        </div>
    </div>
</body>

</html>
<?php
        return ob_get_clean();
    }
    
    /**
     * Handle Stripe webhook events
     */
    public function handle_webhook() {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                STRIPE_WEBHOOK_SECRET
            );
            
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handle_payment_succeeded($event->data->object);
                    break;
                    
                case 'payment_intent.payment_failed':
                    $this->handle_payment_failed($event->data->object);
                    break;
                    
                case 'charge.refunded':
                    $this->handle_refund($event->data->object);
                    break;
            }
            
            http_response_code(200);
            
        } catch (Exception $e) {
            http_response_code(400);
            exit();
        }
    }
    
    private function handle_payment_succeeded($payment_intent) {
        $booking_id = $payment_intent->metadata->booking_id;
        
        global $cinema_booking_manager;
        $cinema_booking_manager->update_booking_status($booking_id, 'confirmed', [
            'payment_status' => 'paid',
            'payment_method' => 'stripe',
            'payment_intent_id' => $payment_intent->id
        ]);
    }
    
    private function handle_payment_failed($payment_intent) {
        $booking_id = $payment_intent->metadata->booking_id;
        
        global $cinema_booking_manager;
        $cinema_booking_manager->update_booking_status($booking_id, 'failed', [
            'payment_status' => 'failed'
        ]);
    }
    
    private function handle_refund($charge) {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'cinema_bookings',
            array('payment_status' => 'refunded'),
            array('stripe_charge_id' => $charge->id)
        );
    }
}

// Initialize
new Cinema_Stripe_Payment();

// Webhook endpoint
add_action('rest_api_init', function() {
    register_rest_route('cinema/v1', '/stripe-webhook', array(
        'methods' => 'POST',
        'callback' => function() {
            $payment = new Cinema_Stripe_Payment();
            $payment->handle_webhook();
        },
        'permission_callback' => '__return_true'
    ));
});