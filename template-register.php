<?php
/*
Template Name: Register
*/

// Redirect if already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/movies'));
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .auth-container {
            width: 100%;
            max-width: 550px;
        }
        
        .auth-box {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .auth-logo {
            font-size: 36px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
            text-transform: lowercase;
        }
        
        .auth-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        small {
            color: #999;
            font-size: 12px;
            display: block;
            margin-top: 4px;
        }
        
        .checkbox-label {
            display: flex;
            align-items: start;
            gap: 8px;
            cursor: pointer;
            color: #666;
            font-size: 14px;
        }
        
        .checkbox-label input[type="checkbox"] {
            cursor: pointer;
            margin-top: 3px;
        }
        
        .checkbox-label a {
            color: #667eea;
            text-decoration: none;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: #666;
        }
        
        .auth-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        /* Notifications */
        .notifications-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 400px;
        }
        
        .notification {
            background: white;
            padding: 16px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transform: translateX(400px);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .notification.notification-show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .notification.notification-hide {
            transform: translateX(400px);
            opacity: 0;
        }
        
        .notification.error {
            border-left: 4px solid #dc3545;
        }
        
        .notification.success {
            border-left: 4px solid #28a745;
        }
        
        .notification-message {
            flex: 1;
            font-size: 14px;
        }
        
        .notification-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            padding: 0;
            margin-left: 10px;
            color: #999;
        }
        
        .back-home {
            display: inline-block;
            color: white;
            text-decoration: none;
            margin-bottom: 20px;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .back-home:hover {
            opacity: 1;
            text-decoration: underline;
        }
        
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .auth-box {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>

<div class="auth-container">
    <a href="<?php echo home_url('/'); ?>" class="back-home">← Back to Home</a>
    
    <div class="auth-box">
        <div class="auth-header">
            <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/logo.png" alt="logo">
            <p>Create your account</p>
        </div>
        
        <form id="register-form" class="auth-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" class="form-control" required>
                <small>At least 8 characters</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="terms" id="terms" required>
                    <span>I agree to the <a href="#">Terms & Conditions</a></span>
                </label>
            </div>
            
            <input type="hidden" name="redirect_to" value="<?php echo esc_attr($_GET['redirect_to'] ?? home_url('/movies')); ?>">
            
            <button type="submit" class="btn btn-primary" id="register-btn">
                Create Account
            </button>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="<?php echo home_url('/login'); ?>">Sign In</a></p>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('Register form initialized');
    
    // Register Form
    $('#register-form').on('submit', function(e) {
        e.preventDefault();
        
        console.log('Register form submitted');
        
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();
        
        if (password !== confirmPassword) {
            showNotification('Passwords do not match', 'error');
            return;
        }
        
        if (password.length < 8) {
            showNotification('Password must be at least 8 characters', 'error');
            return;
        }
        
        if (!$('#terms').is(':checked')) {
            showNotification('Please accept the terms and conditions', 'error');
            return;
        }
        
        const $btn = $('#register-btn');
        const btnText = $btn.text();
        
        $btn.prop('disabled', true).text('Creating account...');
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'cinema_user_register',
                nonce: '<?php echo wp_create_nonce('cinema_auth_nonce'); ?>',
                username: $('#username').val(),
                email: $('#email').val(),
                password: password,
                first_name: $('#first_name').val(),
                last_name: $('#last_name').val(),
                redirect_to: $('input[name="redirect_to"]').val()
            },
            success: function(response) {
                console.log('Response:', response);
                
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    setTimeout(() => {
                        window.location.href = response.data.redirect_url;
                    }, 1000);
                } else {
                    showNotification(response.data.message, 'error');
                    $btn.prop('disabled', false).text(btnText);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
                $btn.prop('disabled', false).text(btnText);
            }
        });
    });
    
    // Notification function
    function showNotification(message, type = 'error') {
        const notification = `
            <div class="notification ${type}">
                <span class="notification-message">${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        if (!$('.notifications-container').length) {
            $('body').append('<div class="notifications-container"></div>');
        }
        
        const $notification = $(notification);
        $('.notifications-container').append($notification);
        
        setTimeout(() => $notification.addClass('notification-show'), 10);
        
        setTimeout(() => {
            $notification.removeClass('notification-show').addClass('notification-hide');
            setTimeout(() => $notification.remove(), 300);
        }, 5000);
        
        $notification.find('.notification-close').on('click', function() {
            $notification.removeClass('notification-show').addClass('notification-hide');
            setTimeout(() => $notification.remove(), 300);
        });
    }
});
</script>

<?php wp_footer(); ?>
</body>
</html>