<?php
/**
 * Cinema Admin Panel
 */

// Add admin menu
add_action('admin_menu', 'cinema_add_admin_menu');

function cinema_add_admin_menu() {
    add_menu_page(
        'Cinema Bookings',
        'Cinema Bookings',
        'manage_options',
        'cinema-bookings',
        'cinema_bookings_page',
        'dashicons-tickets-alt',
        30
    );
    
    add_submenu_page(
        'cinema-bookings',
        'All Bookings',
        'All Bookings',
        'manage_options',
        'cinema-bookings',
        'cinema_bookings_page'
    );
    
    add_submenu_page(
        'cinema-bookings',
        'Seat Layouts',
        'Seat Layouts',
        'manage_options',
        'cinema-seat-layouts',
        'cinema_seat_layouts_page'
    );
    
    add_submenu_page(
        'cinema-bookings',
        'Reports',
        'Reports',
        'manage_options',
        'cinema-reports',
        'cinema_reports_page'
    );
    
    add_submenu_page(
        'cinema-bookings',
        'Settings',
        'Settings',
        'manage_options',
        'cinema-settings',
        'cinema_settings_page'
    );
}

/**
 * Bookings Page
 */
function cinema_bookings_page() {
    global $wpdb;
    
    $bookings_table = $wpdb->prefix . 'cinema_bookings';
    $seats_table = $wpdb->prefix . 'cinema_seats';
    
    // Handle status filter
    $status_filter = isset($_GET['booking_status']) ? sanitize_text_field($_GET['booking_status']) : 'all';
    
    // Pagination
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    // Build query
    $where = "1=1";
    if ($status_filter !== 'all') {
        $where .= $wpdb->prepare(" AND booking_status = %s", $status_filter);
    }
    
    // Search
    if (isset($_GET['s']) && !empty($_GET['s'])) {
        $search = '%' . $wpdb->esc_like($_GET['s']) . '%';
        $where .= $wpdb->prepare(" AND (booking_reference LIKE %s OR customer_email LIKE %s OR customer_name LIKE %s)", $search, $search, $search);
    }
    
    // Get bookings
    $bookings = $wpdb->get_results(
        "SELECT * FROM {$bookings_table} 
        WHERE {$where} 
        ORDER BY created_at DESC 
        LIMIT {$per_page} OFFSET {$offset}"
    );
    
    // Get total count
    $total_bookings = $wpdb->get_var("SELECT COUNT(*) FROM {$bookings_table} WHERE {$where}");
    $total_pages = ceil($total_bookings / $per_page);
    
    // Get stats
    $total_revenue = $wpdb->get_var("SELECT SUM(total_amount) FROM {$bookings_table} WHERE payment_status = 'paid'");
    $confirmed_bookings = $wpdb->get_var("SELECT COUNT(*) FROM {$bookings_table} WHERE booking_status = 'confirmed'");
    $pending_bookings = $wpdb->get_var("SELECT COUNT(*) FROM {$bookings_table} WHERE booking_status = 'pending'");
    $cancelled_bookings = $wpdb->get_var("SELECT COUNT(*) FROM {$bookings_table} WHERE booking_status = 'cancelled'");
    
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Cinema Bookings</h1>
        <a href="#" class="page-title-action">Export to CSV</a>
        
        <div class="cinema-stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0;">
            <div class="cinema-stat-card" style="background: white; padding: 20px; border-left: 4px solid #2271b1;">
                <h3 style="margin: 0; font-size: 14px; color: #666;">Total Revenue</h3>
                <p style="margin: 10px 0 0; font-size: 28px; font-weight: bold;">$<?php echo number_format($total_revenue, 2); ?></p>
            </div>
            <div class="cinema-stat-card" style="background: white; padding: 20px; border-left: 4px solid #00a32a;">
                <h3 style="margin: 0; font-size: 14px; color: #666;">Confirmed</h3>
                <p style="margin: 10px 0 0; font-size: 28px; font-weight: bold;"><?php echo $confirmed_bookings; ?></p>
            </div>
            <div class="cinema-stat-card" style="background: white; padding: 20px; border-left: 4px solid #dba617;">
                <h3 style="margin: 0; font-size: 14px; color: #666;">Pending</h3>
                <p style="margin: 10px 0 0; font-size: 28px; font-weight: bold;"><?php echo $pending_bookings; ?></p>
            </div>
            <div class="cinema-stat-card" style="background: white; padding: 20px; border-left: 4px solid #d63638;">
                <h3 style="margin: 0; font-size: 14px; color: #666;">Cancelled</h3>
                <p style="margin: 10px 0 0; font-size: 28px; font-weight: bold;"><?php echo $cancelled_bookings; ?></p>
            </div>
        </div>
        
        <ul class="subsubsub">
            <li><a href="?page=cinema-bookings&booking_status=all" <?php echo $status_filter === 'all' ? 'class="current"' : ''; ?>>All</a> |</li>
            <li><a href="?page=cinema-bookings&booking_status=confirmed" <?php echo $status_filter === 'confirmed' ? 'class="current"' : ''; ?>>Confirmed</a> |</li>
            <li><a href="?page=cinema-bookings&booking_status=pending" <?php echo $status_filter === 'pending' ? 'class="current"' : ''; ?>>Pending</a> |</li>
            <li><a href="?page=cinema-bookings&booking_status=cancelled" <?php echo $status_filter === 'cancelled' ? 'class="current"' : ''; ?>>Cancelled</a></li>
        </ul>
        
        <form method="get" style="margin: 20px 0;">
            <input type="hidden" name="page" value="cinema-bookings">
            <input type="search" name="s" value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>" placeholder="Search bookings...">
            <input type="submit" class="button" value="Search">
        </form>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Movie</th>
                    <th>Date & Time</th>
                    <th>Seats</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)) : ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px;">No bookings found</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($bookings as $booking) : 
                        $movie_id = get_post_meta($booking->showtime_id, '_showtime_movie_id', true);
                        $movie_title = get_the_title($movie_id);
                        $screen = get_post_meta($booking->showtime_id, '_showtime_screen', true);
                        
                        $seats = $wpdb->get_results($wpdb->prepare(
                            "SELECT * FROM {$seats_table} WHERE booking_id = %d",
                            $booking->id
                        ));
                        $seat_numbers = array_map(function($s) { return $s->seat_number; }, $seats);
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($booking->booking_reference); ?></strong></td>
                        <td>
                            <?php echo esc_html($booking->customer_name); ?><br>
                            <small><?php echo esc_html($booking->customer_email); ?></small>
                        </td>
                        <td><?php echo esc_html($movie_title); ?><br><small>Screen <?php echo $screen; ?></small></td>
                        <td>
                            <?php echo date('M j, Y', strtotime($booking->show_date)); ?><br>
                            <small><?php echo date('g:i A', strtotime($booking->show_time)); ?></small>
                        </td>
                        <td><?php echo implode(', ', $seat_numbers); ?></td>
                        <td><strong>$<?php echo number_format($booking->total_amount, 2); ?></strong></td>
                        <td>
                            <span class="cinema-status-badge cinema-status-<?php echo $booking->booking_status; ?>">
                                <?php echo ucfirst($booking->booking_status); ?>
                            </span>
                        </td>
                        <td>
                            <span class="cinema-payment-badge cinema-payment-<?php echo $booking->payment_status; ?>">
                                <?php echo ucfirst($booking->payment_status); ?>
                            </span>
                        </td>
                        <td>
                            <a href="?page=cinema-bookings&action=view&booking_id=<?php echo $booking->id; ?>" class="button button-small">View</a>
                            <?php if ($booking->booking_status === 'confirmed' && $booking->payment_status === 'paid') : ?>
                                <a href="#" class="button button-small" onclick="printTicket(<?php echo $booking->id; ?>)">Print</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1) : ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <style>
        .cinema-status-badge, .cinema-payment-badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .cinema-status-confirmed { background: #d4edda; color: #155724; }
        .cinema-status-pending { background: #fff3cd; color: #856404; }
        .cinema-status-cancelled { background: #f8d7da; color: #721c24; }
        .cinema-payment-paid { background: #d4edda; color: #155724; }
        .cinema-payment-pending { background: #fff3cd; color: #856404; }
        .cinema-payment-failed { background: #f8d7da; color: #721c24; }
        .cinema-payment-refunded { background: #d1ecf1; color: #0c5460; }
    </style>
    <?php
}

/**
 * Seat Layouts Page
 */
function cinema_seat_layouts_page() {
    global $wpdb;
    $layouts_table = $wpdb->prefix . 'cinema_seat_layouts';
    
    // Handle form submission
    if (isset($_POST['save_layout']) && check_admin_referer('cinema_save_layout')) {
        $screen_number = intval($_POST['screen_number']);
        $layout_name = sanitize_text_field($_POST['layout_name']);
        $seat_configuration = $_POST['seat_configuration']; // JSON from visual editor
        $total_seats = intval($_POST['total_seats']);
        
        $wpdb->insert($layouts_table, array(
            'screen_number' => $screen_number,
            'layout_name' => $layout_name,
            'seat_configuration' => $seat_configuration,
            'total_seats' => $total_seats,
            'is_active' => 1
        ));
        
        echo '<div class="notice notice-success"><p>Seat layout saved successfully!</p></div>';
    }
    
    // Get existing layouts
    $layouts = $wpdb->get_results("SELECT * FROM {$layouts_table} ORDER BY screen_number, created_at DESC");
    
    ?>
    <div class="wrap">
        <h1>Seat Layout Management</h1>
        
        <div class="cinema-admin-tabs">
            <button class="cinema-tab-btn active" data-tab="layouts">Current Layouts</button>
            <button class="cinema-tab-btn" data-tab="editor">Create New Layout</button>
        </div>
        
        <div id="layouts-tab" class="cinema-tab-content">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Screen</th>
                        <th>Layout Name</th>
                        <th>Total Seats</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($layouts as $layout) : ?>
                    <tr>
                        <td><?php echo $layout->screen_number; ?></td>
                        <td><?php echo esc_html($layout->layout_name); ?></td>
                        <td><?php echo $layout->total_seats; ?></td>
                        <td>
                            <?php if ($layout->is_active) : ?>
                                <span class="cinema-status-badge cinema-status-confirmed">Active</span>
                            <?php else : ?>
                                <span class="cinema-status-badge">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($layout->created_at)); ?></td>
                        <td>
                            <a href="#" class="button button-small view-layout" data-layout='<?php echo esc_attr($layout->seat_configuration); ?>'>View</a>
                            <a href="#" class="button button-small edit-layout" data-id="<?php echo $layout->id; ?>">Edit</a>
                            <a href="#" class="button button-small button-link-delete">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div id="editor-tab" class="cinema-tab-content" style="display: none;">
            <form method="post" action="">
                <?php wp_nonce_field('cinema_save_layout'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="screen_number">Screen Number</label></th>
                        <td><input type="number" name="screen_number" id="screen_number" min="1" required></td>
                    </tr>
                    <tr>
                        <th><label for="layout_name">Layout Name</label></th>
                        <td><input type="text" name="layout_name" id="layout_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label>Seat Configuration</label></th>
                        <td>
                            <div id="seat-layout-editor" style="background: #f5f5f5; padding: 20px; min-height: 400px; border: 1px solid #ddd;">
                                <p>Visual seat layout editor coming here. For now, use JSON format below.</p>
                            </div>
                            <textarea name="seat_configuration" id="seat_configuration" rows="10" class="large-text code"></textarea>
                            <p class="description">Enter seat configuration in JSON format</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="total_seats">Total Seats</label></th>
                        <td><input type="number" name="total_seats" id="total_seats" min="1" required></td>
                    </tr>
                </tr>
                
                <?php submit_button('Save Layout', 'primary', 'save_layout'); ?>
            </form>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('.cinema-tab-btn').on('click', function() {
            $('.cinema-tab-btn').removeClass('active');
            $(this).addClass('active');
            
            const tab = $(this).data('tab');
            $('.cinema-tab-content').hide();
            $(`#${tab}-tab`).show();
        });
        
        $('.view-layout').on('click', function(e) {
            e.preventDefault();
            const layout = $(this).data('layout');
            alert('Layout preview: ' + JSON.stringify(layout, null, 2));
        });
    });
    </script>
    
    <style>
        .cinema-admin-tabs {
            margin: 20px 0;
            border-bottom: 1px solid #ccc;
        }
        .cinema-tab-btn {
            background: none;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
        }
        .cinema-tab-btn.active {
            border-bottom-color: #2271b1;
            font-weight: 600;
        }
        .cinema-tab-content {
            padding: 20px 0;
        }
    </style>
    <?php
}

/**
 * Reports Page
 */
function cinema_reports_page() {
    ?>
    <div class="wrap">
        <h1>Cinema Reports</h1>
        <p>Reports and analytics dashboard coming soon...</p>
    </div>
    <?php
}

/**
 * Settings Page
 */
function cinema_settings_page() {
    // Save settings
    if (isset($_POST['save_settings']) && check_admin_referer('cinema_settings')) {
        update_option('cinema_booking_fee', floatval($_POST['booking_fee']));
        update_option('cinema_cancellation_hours', intval($_POST['cancellation_hours']));
        update_option('cinema_max_seats_per_booking', intval($_POST['max_seats']));
        update_option('cinema_seat_lock_duration', intval($_POST['lock_duration']));
        update_option('cinema_email_notifications', isset($_POST['email_notifications']));
        update_option('cinema_stripe_test_mode', isset($_POST['stripe_test_mode']));
        
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }
    
    $booking_fee = get_option('cinema_booking_fee', 2.25);
    $cancellation_hours = get_option('cinema_cancellation_hours', 2);
    $max_seats = get_option('cinema_max_seats_per_booking', 8);
    $lock_duration = get_option('cinema_seat_lock_duration', 10);
    $email_notifications = get_option('cinema_email_notifications', true);
    $stripe_test_mode = get_option('cinema_stripe_test_mode', true);
    
    ?>
    <div class="wrap">
        <h1>Cinema Settings</h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('cinema_settings'); ?>
            
            <h2>Booking Settings</h2>
            <table class="form-table">
                <tr>
                    <th><label for="booking_fee">Booking Fee per Ticket ($)</label></th>
                    <td><input type="number" name="booking_fee" id="booking_fee" step="0.01" value="<?php echo $booking_fee; ?>"></td>
                </tr>
                <tr>
                    <th><label for="cancellation_hours">Cancellation Window (hours)</label></th>
                    <td>
                        <input type="number" name="cancellation_hours" id="cancellation_hours" value="<?php echo $cancellation_hours; ?>">
                        <p class="description">Minimum hours before showtime that bookings can be cancelled</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="max_seats">Maximum Seats per Booking</label></th>
                    <td><input type="number" name="max_seats" id="max_seats" value="<?php echo $max_seats; ?>"></td>
                </tr>
                <tr>
                    <th><label for="lock_duration">Seat Lock Duration (minutes)</label></th>
                    <td>
                        <input type="number" name="lock_duration" id="lock_duration" value="<?php echo $lock_duration; ?>">
                        <p class="description">How long seats remain locked during selection</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="email_notifications">Email Notifications</label></th>
                    <td>
                        <input type="checkbox" name="email_notifications" id="email_notifications" <?php checked($email_notifications); ?>>
                        <label for="email_notifications">Send confirmation emails to customers</label>
                    </td>
                </tr>
            </table>
            
            <h2>Payment Settings</h2>
            <table class="form-table">
                <tr>
                    <th><label for="stripe_test_mode">Stripe Test Mode</label></th>
                    <td>
                        <input type="checkbox" name="stripe_test_mode" id="stripe_test_mode" <?php checked($stripe_test_mode); ?>>
                        <label for="stripe_test_mode">Use Stripe test keys</label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Save Settings', 'primary', 'save_settings'); ?>
        </form>
    </div>
    <?php
}

// Include admin panel in functions.php
require_once get_stylesheet_directory() . '/admin/cinema-admin-panel.php';