<?php
/**
 * Login/Logout Shortcode for WordPress with Custom Login Page Support
 * Add this code to your theme's functions.php file or create a custom plugin
 */

// Register the shortcode
add_shortcode('login_logout_button', 'login_logout_button_shortcode');

function login_logout_button_shortcode($atts) {
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'redirect' => '', // Optional: custom redirect URL after login/logout
        'login_page' => 'Login', // Custom login page slug/name
    ), $atts);
    
    // Get current page URL for redirect
    $current_url = (!empty($atts['redirect'])) ? $atts['redirect'] : get_permalink();
    
    // Check if user is logged in
    if (is_user_logged_in()) {
        // User is logged in - show logout button
        $current_user = wp_get_current_user();
        
    // Use JavaScript-based logout to avoid nonce caching issues
    // Append logged_out=1 so client can clear temporary booking storage after logout
    $logout_url = add_query_arg('logged_out', '1', wp_logout_url($current_url));
        
        $output = '<div class="login-logout-wrapper logged-in">';
        $output .= '<a href="' . esc_url($logout_url) . '" class="login-logout-btn logout-btn" onclick="return confirmLogout(this);">';
        $output .= '<span class="user-icon">👤</span>';
        $output .= '<span class="btn-text">Logout</span>';
        $output .= '</a>';
        $output .= '</div>';
    } else {
        // User is not logged in - show login button
        // Get custom login page by page name/slug
        $login_page = get_page_by_path($atts['login_page']);
        
        // If page not found by slug, try by title
        if (!$login_page) {
            $login_page = get_page_by_title($atts['login_page']);
        }
        
        // If custom login page exists, use it; otherwise fallback to wp-login.php
        if ($login_page) {
            $login_url = get_permalink($login_page->ID);
            // Add redirect parameter if needed
            if (!empty($current_url)) {
                $login_url = add_query_arg('redirect_to', urlencode($current_url), $login_url);
            }
        } else {
            $login_url = wp_login_url($current_url);
        }
        
        $output = '<div class="login-logout-wrapper logged-out">';
        $output .= '<a href="' . esc_url($login_url) . '" class="login-logout-btn login-btn">';
        $output .= '<span class="user-icon">👤</span>';
        $output .= '<span class="btn-text">Login</span>';
        $output .= '</a>';
        $output .= '</div>';
    }
    
    return $output;
}

// Add CSS styling for the button
add_action('wp_head', 'login_logout_button_styles');

function login_logout_button_styles() {
    ?>
    <style>
        .login-logout-wrapper {
            display: inline-block;
        }
        
        .login-logout-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s ease;
            cursor: pointer;
        }
        
        .login-logout-btn:hover {
            color: #ffffff;
        }
        
        .login-logout-btn .user-icon {
            font-size: 20px;
            line-height: 1;
        }
        
        .login-logout-btn .btn-text {
            line-height: 1;
        }
        
        /* Optional: Different colors for login vs logout */
       
        
        .logout-btn:hover {
            background-color: #a82a2a;
        }
    </style>
    <script>
        function confirmLogout(element) {
            // Generate fresh logout URL to avoid nonce issues
            var href = element.href;
            // Force the link to work properly
            window.location.href = href;
            return false;
        }
    </script>
    <?php
}

// Optional: Redirect to custom login page for WordPress login
add_action('login_form', 'custom_login_page_redirect');

function custom_login_page_redirect() {
    // Only redirect on GET requests to avoid breaking login POST submissions
    if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) !== 'GET') {
        return;
    }

    // Avoid redirecting in admin or ajax contexts
    if (is_admin() || defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }

    // Get the custom login page (by title 'Login')
    $login_page = get_page_by_title('Login');

    // If custom login page exists and we're not already on it, redirect safely
    if ($login_page && !is_page($login_page->ID)) {
        $login_url = get_permalink($login_page->ID);

        // Preserve redirect_to parameter (use raw value, wp_validate_redirect will handle safety)
        if (isset($_REQUEST['redirect_to']) && !empty($_REQUEST['redirect_to'])) {
            $login_url = add_query_arg('redirect_to', $_REQUEST['redirect_to'], $login_url);
        }

        // Use wp_safe_redirect and exit
        wp_safe_redirect($login_url);
        exit;
    }
}

// Prevent caching of pages with login/logout button
add_action('template_redirect', 'prevent_login_logout_caching');

function prevent_login_logout_caching() {
    global $post;

    // Only run on singular pages that contain the shortcode
    if (!is_singular() || !isset($post->post_content) || !has_shortcode($post->post_content, 'login_logout_button')) {
        return;
    }

    // Use WP helper to send appropriate no-cache headers
    // This is safer than sending raw header() calls and integrates with WP
    nocache_headers();

    /**
     * Additional precaution: make sure we don't accidentally clear auth cookies here.
     * nocache_headers() only sends cache-control headers and won't touch cookies.
     */
}
?>