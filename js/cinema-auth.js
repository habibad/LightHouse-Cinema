jQuery(document).ready(function($) {
    'use strict';
    
    // Login Form
    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $('#login-btn');
        const btnText = $btn.text();
        
        $btn.prop('disabled', true).text('Signing in...');
        
        $.ajax({
            url: cinema_auth_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cinema_user_login',
                nonce: cinema_auth_ajax.nonce,
                username: $('#username').val(),
                password: $('#password').val(),
                remember: $('#remember').is(':checked'),
                redirect_to: $('input[name="redirect_to"]').val()
            },
            success: function(response) {
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
            error: function() {
                showNotification('An error occurred. Please try again.', 'error');
                $btn.prop('disabled', false).text(btnText);
            }
        });
    });
    
    // Register Form
    $('#register-form').on('submit', function(e) {
        e.preventDefault();
        
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();
        
        if (password !== confirmPassword) {
            showNotification('Passwords do not match', 'error');
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
            url: cinema_auth_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cinema_user_register',
                nonce: cinema_auth_ajax.nonce,
                username: $('#username').val(),
                email: $('#email').val(),
                password: password,
                first_name: $('#first_name').val(),
                last_name: $('#last_name').val(),
                redirect_to: $('input[name="redirect_to"]').val()
            },
            success: function(response) {
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
            error: function() {
                showNotification('An error occurred. Please try again.', 'error');
                $btn.prop('disabled', false).text(btnText);
            }
        });
    });
    
    // Forgot Password Form
    $('#forgot-password-form').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $('#forgot-btn');
        const btnText = $btn.text();
        
        $btn.prop('disabled', true).text('Sending...');
        
        $.ajax({
            url: cinema_auth_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cinema_forgot_password',
                nonce: cinema_auth_ajax.nonce,
                email: $('#email').val()
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    $('#email').val('');
                } else {
                    showNotification(response.data.message, 'error');
                }
                $btn.prop('disabled', false).text(btnText);
            },
            error: function() {
                showNotification('An error occurred. Please try again.', 'error');
                $btn.prop('disabled', false).text(btnText);
            }
        });
    });
    
    // Reset Password Form
    $('#reset-password-form').on('submit', function(e) {
        e.preventDefault();
        
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();
        
        if (password !== confirmPassword) {
            showNotification('Passwords do not match', 'error');
            return;
        }
        
        const $btn = $('#reset-btn');
        const btnText = $btn.text();
        
        $btn.prop('disabled', true).text('Resetting...');
        
        $.ajax({
            url: cinema_auth_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cinema_reset_password',
                nonce: cinema_auth_ajax.nonce,
                password: password,
                key: $('input[name="key"]').val(),
                login: $('input[name="login"]').val()
            },
            success: function(response) {
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
            error: function() {
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