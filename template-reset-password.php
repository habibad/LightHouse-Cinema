<?php
/*
Template Name: Reset Password
*/

$key = isset($_GET['key']) ? $_GET['key'] : '';
$login = isset($_GET['login']) ? $_GET['login'] : '';

if (empty($key) || empty($login)) {
    wp_redirect(home_url('/login'));
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php bloginfo('name'); ?></title>
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
        
        small {
            color: #999;
            font-size: 12px;
            display: block;
            margin-top: 4px;
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
            <h2>Set New Password</h2>
            <p>Enter your new password below</p>
        </div>
        
        <form id="reset-password-form" class="auth-form">
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
                <small>At least 8 characters</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
            
            <input type="hidden" name="key" value="<?php echo esc_attr($key); ?>">
            <input type="hidden" name="login" value="<?php echo esc_attr($login); ?>">
            
            <button type="submit" class="btn btn-primary" id="reset-btn">
                Reset Password
            </button>
        </form>
    </div>
</div>


<script>
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('Reset password form initialized');
    
    // Reset Password Form
    $('#reset-password-form').on('submit', function(e) {
        e.preventDefault();
        
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
        
        const $btn = $('#reset-btn');
        const btnText = $btn.text();
        
        $btn.prop('disabled', true).text('Resetting...');
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'cinema_reset_password',
                nonce: '<?php echo wp_create_nonce('cinema_auth_nonce'); ?>',
                password: password,
                key: $('input[name="key"]').val(),
                login: $('input[name="login"]').val()
            },
            success: function(response) {
                console.log('Response:', response);
                
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    setTimeout(() => {
                        window.location.href = response.data.redirect_url;
                    }, 2000);
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