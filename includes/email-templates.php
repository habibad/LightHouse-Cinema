<?php
/**
 * Email Templates for Cinema Booking System
 */

class Cinema_Email_Templates {
    
    /**
     * Send booking confirmation email
     */
    public static function send_booking_confirmation($booking) {
        $to = $booking->customer_email;
        $subject = "Booking Confirmation - {$booking->booking_reference}";
        $message = self::get_confirmation_template($booking);
        $headers = self::get_email_headers();
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Send cancellation confirmation email
     */
    public static function send_cancellation_confirmation($booking) {
        $to = $booking->customer_email;
        $subject = "Booking Cancelled - {$booking->booking_reference}";
        $message = self::get_cancellation_template($booking);
        $headers = self::get_email_headers();
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Send reminder email (24 hours before showtime)
     */
    public static function send_showtime_reminder($booking) {
        $to = $booking->customer_email;
        $subject = "Reminder: Your Movie Starts Tomorrow!";
        $message = self::get_reminder_template($booking);
        $headers = self::get_email_headers();
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Get email headers
     */
    private static function get_email_headers() {
        return array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Cinépolis <noreply@' . parse_url(home_url(), PHP_URL_HOST) . '>'
        );
    }
    
    /**
     * Confirmation email template
     */
    private static function get_confirmation_template($booking) {
        $seat_list = array_map(function($seat) {
            return $seat->seat_number;
        }, $booking->seats);
        
        ob_start();
        include get_stylesheet_directory() . '/email-templates/booking-confirmation.php';
        return ob_get_clean();
    }
    
    /**
     * Cancellation email template
     */
    private static function get_cancellation_template($booking) {
        ob_start();
        include get_stylesheet_directory() . '/email-templates/booking-cancellation.php';
        return ob_get_clean();
    }
    
    /**
     * Reminder email template
     */
    private static function get_reminder_template($booking) {
        ob_start();
        include get_stylesheet_directory() . '/email-templates/showtime-reminder.php';
        return ob_get_clean();
    }
}

/**
 * Schedule reminder emails
 */
add_action('cinema_send_reminders', 'cinema_send_reminder_emails');

function cinema_send_reminder_emails() {
    global $wpdb;
    $bookings_table = $wpdb->prefix . 'cinema_bookings';
    
    // Get bookings for tomorrow
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    $bookings = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$bookings_table} 
        WHERE show_date = %s 
        AND booking_status = 'confirmed' 
        AND payment_status = 'paid'",
        $tomorrow
    ));
    
    foreach ($bookings as $booking) {
        global $cinema_booking_manager;
        $booking_full = $cinema_booking_manager->get_booking($booking->id);
        Cinema_Email_Templates::send_showtime_reminder($booking_full);
    }
}

// Schedule daily reminder check
if (!wp_next_scheduled('cinema_send_reminders')) {
    wp_schedule_event(strtotime('09:00:00'), 'daily', 'cinema_send_reminders');
}