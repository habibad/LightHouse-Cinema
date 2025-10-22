<?php
/**
 * Cinema Booking Manager
 * Handles all booking operations
 */

class Cinema_Booking_Manager {
    
    private $bookings_table;
    private $seats_table;
    private $locks_table;
    
    public function __construct() {
        global $wpdb;
        $this->bookings_table = $wpdb->prefix . 'cinema_bookings';
        $this->seats_table = $wpdb->prefix . 'cinema_seats';
        $this->locks_table = $wpdb->prefix . 'cinema_seat_locks';
    }
    
    /**
     * Generate unique booking reference
     */
    public function generate_booking_reference() {
        $reference = 'CIN-' . strtoupper(substr(uniqid(), -8));
        
        // Ensure uniqueness
        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->bookings_table} WHERE booking_reference = %s",
            $reference
        ));
        
        if ($exists) {
            return $this->generate_booking_reference(); // Recursively generate new one
        }
        
        return $reference;
    }
    
    /**
     * Create new booking
     */
    public function create_booking($data) {
        global $wpdb;
        
        // Validate required fields
        if (empty($data['showtime_id']) || empty($data['seats']) || empty($data['customer_email'])) {
            return new WP_Error('invalid_data', 'Missing required booking information');
        }
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Generate booking reference
            $booking_reference = $this->generate_booking_reference();
            
            // Get showtime details (support movie showtimes and event showtimes)
            $post_type = get_post_type($data['showtime_id']);
            if ($post_type === 'event_showtimes') {
                $show_date = get_post_meta($data['showtime_id'], '_event_showtime_date', true);
                $show_time = get_post_meta($data['showtime_id'], '_event_showtime_time', true);
                $screen_number = get_post_meta($data['showtime_id'], '_event_showtime_screen', true);
            } else {
                $show_date = get_post_meta($data['showtime_id'], '_showtime_date', true);
                $show_time = get_post_meta($data['showtime_id'], '_showtime_time', true);
                $screen_number = get_post_meta($data['showtime_id'], '_showtime_screen', true);
            }
            $screen_number = $screen_number ? intval($screen_number) : 1;
            
            // Calculate totals
            $total_amount = 0;
            foreach ($data['seats'] as $seat) {
                $total_amount += floatval($seat['price']);
            }
            
            $booking_fee = count($data['seats']) * 2.25;
            $discount_amount = isset($data['discount_amount']) ? floatval($data['discount_amount']) : 0;
            $final_amount = $total_amount + $booking_fee - $discount_amount;
            
            // Insert booking
            // Allow callers to pass booking_type and event_id for events
            $booking_type = isset($data['booking_type']) ? $data['booking_type'] : 'movie';
            $event_id = isset($data['event_id']) ? intval($data['event_id']) : null;

            $booking_data = array(
                'booking_reference' => $booking_reference,
                'showtime_id' => $data['showtime_id'],
                'booking_type' => $booking_type,
                'event_id' => $event_id,
                'screen_number' => $screen_number,
                'user_id' => get_current_user_id(),
                'customer_email' => sanitize_email($data['customer_email']),
                'customer_name' => sanitize_text_field($data['customer_name']),
                'customer_phone' => sanitize_text_field($data['customer_phone'] ?? ''),
                'show_date' => $show_date,
                'show_time' => $show_time,
                'total_amount' => $final_amount,
                'booking_fee' => $booking_fee,
                'discount_amount' => $discount_amount,
                'booking_status' => 'pending',
                'payment_status' => 'pending'
            );

            // Insert booking
            // $booking_data = array(
            //     'booking_reference' => $booking_reference,
            //     'showtime_id' => $data['showtime_id'],
            //     'user_id' => get_current_user_id(), // Add this line
            //     'customer_email' => sanitize_email($data['customer_email']),
            //     'customer_name' => sanitize_text_field($data['customer_name']),
            //     'customer_phone' => sanitize_text_field($data['customer_phone'] ?? ''),
            //     'show_date' => $show_date,
            //     'show_time' => $show_time,
            //     'total_amount' => $final_amount,
            //     'booking_fee' => $booking_fee,
            //     'discount_amount' => $discount_amount,
            //     'booking_status' => 'pending',
            //     'payment_status' => 'pending'
            // );
            
            $inserted = $wpdb->insert($this->bookings_table, $booking_data);
            
            if (!$inserted) {
                throw new Exception('Failed to create booking');
            }
            
            $booking_id = $wpdb->insert_id;
            
            // Insert seats
            foreach ($data['seats'] as $seat) {
                $seat_data = array(
                    'booking_id' => $booking_id,
                    'screen_number' => $screen_number,
                    'seat_number' => $seat['number'],
                    'seat_row' => substr($seat['number'], 0, 1),
                    'seat_column' => substr($seat['number'], 1),
                    'seat_type' => $seat['type'] ?? 'regular',
                    'ticket_type' => $seat['ticket_type'] ?? 'adult',
                    'price' => $seat['price']
                );
                
                $wpdb->insert($this->seats_table, $seat_data);
            }
            
            // Release seat locks
            $this->release_seat_locks($data['showtime_id'], session_id());
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            return array(
                'success' => true,
                'booking_id' => $booking_id,
                'booking_reference' => $booking_reference,
                'total_amount' => $final_amount
            );
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('booking_failed', $e->getMessage());
        }
    }
    
    /**
     * Get booking by ID or reference
     */
    public function get_booking($identifier) {
        global $wpdb;
        
        if (is_numeric($identifier)) {
            $booking = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->bookings_table} WHERE id = %d",
                $identifier
            ));
        } else {
            $booking = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->bookings_table} WHERE booking_reference = %s",
                $identifier
            ));
        }
        
        if (!$booking) {
            return null;
        }
        
        // Get seats
        $booking->seats = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->seats_table} WHERE booking_id = %d",
            $booking->id
        ));
        
        // Get showtime details
            // Expose movie/event info depending on showtime type
            $st_post_type = get_post_type($booking->showtime_id);
            if ($st_post_type === 'event_showtimes') {
                $booking->event_id = get_post_meta($booking->showtime_id, '_event_showtime_event_id', true);
                $booking->event_title = $booking->event_id ? get_the_title($booking->event_id) : '';
                $booking->screen_number = get_post_meta($booking->showtime_id, '_event_showtime_screen', true);
            } else {
                $booking->movie_id = get_post_meta($booking->showtime_id, '_showtime_movie_id', true);
                $booking->movie_title = $booking->movie_id ? get_the_title($booking->movie_id) : '';
                $booking->screen_number = get_post_meta($booking->showtime_id, '_showtime_screen', true);
            }
        
        return $booking;
    }
    
    /**
     * Update booking status
     */
    public function update_booking_status($booking_id, $status, $payment_data = array()) {
        global $wpdb;
        
        $update_data = array('booking_status' => $status);
        
        if (!empty($payment_data)) {
            $update_data = array_merge($update_data, array(
                'payment_status' => $payment_data['payment_status'] ?? 'paid',
                'payment_method' => $payment_data['payment_method'] ?? 'stripe',
                'stripe_payment_intent' => $payment_data['payment_intent_id'] ?? '',
                'stripe_charge_id' => $payment_data['charge_id'] ?? ''
            ));
        }
        
        return $wpdb->update(
            $this->bookings_table,
            $update_data,
            array('id' => $booking_id)
        );
    }
    
    /**
     * Lock seats temporarily during selection
     */
    public function lock_seats($showtime_id, $seat_numbers, $session_id = null) {
        global $wpdb;
        
        if (!$session_id) {
            $session_id = session_id();
        }
        
    // Expire time: 2 minutes from now (keeps consistent with client-side lockDurationMinutes)
    $expires_at = date('Y-m-d H:i:s', strtotime('+2 minutes'));
        
        foreach ($seat_numbers as $seat_number) {
            // Check if seat is already locked by someone else
            $existing_lock = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->locks_table} 
                WHERE showtime_id = %d AND seat_number = %s AND expires_at > NOW()",
                $showtime_id, $seat_number
            ));
            
            if ($existing_lock && $existing_lock->session_id !== $session_id) {
                return new WP_Error('seat_locked', "Seat {$seat_number} is currently being selected by another user");
            }
            
            // Insert or update lock
            $wpdb->replace(
                $this->locks_table,
                array(
                    'showtime_id' => $showtime_id,
                    'seat_number' => $seat_number,
                    'session_id' => $session_id,
                    'expires_at' => $expires_at
                )
            );
        }
        
        return true;
    }
    
    /**
     * Release seat locks
     */
    public function release_seat_locks($showtime_id, $session_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->locks_table,
            array(
                'showtime_id' => $showtime_id,
                'session_id' => $session_id
            )
        );
    }
    
    /**
     * Clean expired locks (run via cron)
     */
    public function clean_expired_locks() {
        global $wpdb;
        
        return $wpdb->query(
            "DELETE FROM {$this->locks_table} WHERE expires_at < NOW()"
        );
    }
    
    /**
     * Get available seats for a showtime
     */
    public function get_available_seats($showtime_id) {
        global $wpdb;
        
        // Get booked seats
        $booked_seats = $wpdb->get_col($wpdb->prepare(
            "SELECT s.seat_number FROM {$this->seats_table} s
            INNER JOIN {$this->bookings_table} b ON s.booking_id = b.id
            WHERE b.showtime_id = %d AND b.booking_status IN ('confirmed', 'pending')",
            $showtime_id
        ));
        
        // Get locked seats (excluding expired and current session)
        $locked_seats = $wpdb->get_col($wpdb->prepare(
            "SELECT seat_number FROM {$this->locks_table}
            WHERE showtime_id = %d AND expires_at > NOW() AND session_id != %s",
            $showtime_id, session_id()
        ));
        
        return array(
            'booked' => $booked_seats,
            'locked' => $locked_seats,
            'unavailable' => array_merge($booked_seats, $locked_seats)
        );
    }
    
    /**
     * Get user bookings
     */
    public function get_user_bookings($user_id, $status = null) {
        global $wpdb;
        
        $where = $wpdb->prepare("user_id = %d", $user_id);
        
        if ($status) {
            $where .= $wpdb->prepare(" AND booking_status = %s", $status);
        }
        
        $bookings = $wpdb->get_results(
            "SELECT * FROM {$this->bookings_table} WHERE {$where} ORDER BY created_at DESC"
        );
        
        foreach ($bookings as &$booking) {
            $booking->seats = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->seats_table} WHERE booking_id = %d",
                $booking->id
            ));
            
            $booking->movie_id = get_post_meta($booking->showtime_id, '_showtime_movie_id', true);
            $booking->movie_title = get_the_title($booking->movie_id);
        }
        
        return $bookings;
    }
    
    /**
     * Cancel booking
     */
    public function cancel_booking($booking_id, $reason = '') {
        global $wpdb;
        
        $booking = $this->get_booking($booking_id);
        
        if (!$booking) {
            return new WP_Error('not_found', 'Booking not found');
        }
        
        // Check if cancellation is allowed (e.g., at least 2 hours before showtime)
        $show_datetime = strtotime($booking->show_date . ' ' . $booking->show_time);
        $hours_until_show = ($show_datetime - time()) / 3600;
        
        if ($hours_until_show < 2) {
            return new WP_Error('too_late', 'Bookings can only be cancelled at least 2 hours before showtime');
        }
        
        // Update booking
        $updated = $wpdb->update(
            $this->bookings_table,
            array(
                'booking_status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => current_time('mysql')
            ),
            array('id' => $booking_id)
        );
        
        if ($updated) {
            // Process refund if payment was made
            if ($booking->payment_status === 'paid' && !empty($booking->stripe_payment_intent)) {
                $this->process_refund($booking);
            }
            
            return array('success' => true, 'message' => 'Booking cancelled successfully');
        }
        
        return new WP_Error('update_failed', 'Failed to cancel booking');
    }
    
    /**
     * Process Stripe refund
     */
    private function process_refund($booking) {
        try {
            \Stripe\Refund::create([
                'payment_intent' => $booking->stripe_payment_intent,
                'reason' => 'requested_by_customer'
            ]);
            
            global $wpdb;
            $wpdb->update(
                $this->bookings_table,
                array('payment_status' => 'refunded'),
                array('id' => $booking->id)
            );
            
            return true;
        } catch (Exception $e) {
            error_log('Stripe refund failed: ' . $e->getMessage());
            return false;
        }
    }
}

// Initialize
$GLOBALS['cinema_booking_manager'] = new Cinema_Booking_Manager();