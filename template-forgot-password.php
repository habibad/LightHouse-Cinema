<?php
/*
Template Name: Forgot Password
*/
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php bloginfo('name'); ?></title>
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
        }
        
        .auth-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
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
        
        .auth-header h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .auth-header p {
            color: #666;
            font-size: 14px;
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
        }
        
        .auth-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
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
    </style>
</head>
<body>

<div class="auth-container">
    <a href="<?php echo home_url('/'); ?>" class="back-home">← Back to Home</a>
    
    <div class="auth-box">
        <div class="auth-header">
            <h2>Reset Password</h2>
            <p>Enter your email to receive a reset link</p>
        </div>
        
        <form id="forgot-password-form" class="auth-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary" id="forgot-btn">
                Send Reset Link
            </button>
            
            <div class="auth-footer">
                <p><a href="<?php echo home_url('/login'); ?>">← Back to Login</a></p>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('Forgot password form initialized');
    
    // Forgot Password Form
    $('#forgot-password-form').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $('#forgot-btn');
        const btnText = $btn.text();
        
        $btn.prop('disabled', true).text('Sending...');
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'cinema_forgot_password',
                nonce: '<?php echo wp_create_nonce('cinema_auth_nonce'); ?>',
                email: $('#email').val()
            },
            success: function(response) {
                console.log('Response:', response);
                
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    $('#email').val('');
                } else {
                    showNotification(response.data.message, 'error');
                }
                $btn.prop('disabled', false).text(btnText);
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