/**
 * Payment Page JavaScript
 * Handles payment form validation, card processing, and payment modals
 */

(function($) {
    'use strict';

    const PaymentPage = {
        paymentProcessingSteps: ['step-1', 'step-2', 'step-3'],
        currentStep: 0,

        init: function() {
            if (!$('.payment-container').length) return;

            this.initPaymentSections();
            this.initFormValidation();
            this.initPaymentProcessing();
            this.initPromoCode();

            console.log('💳 Payment page initialized');
        },

        // ===== PAYMENT SECTIONS =====
        initPaymentSections: function() {
            $(document).on('click', '.payment-header', function() {
                const $section = $(this).parent();
                const $form = $section.find('.payment-form');
                const $expandBtn = $(this).find('.btn-expand');
                
                if ($section.hasClass('active')) {
                    $section.removeClass('active');
                    $form.slideUp();
                    $expandBtn.text('+');
                } else {
                    $('.payment-section').removeClass('active');
                    $('.payment-form').slideUp();
                    $('.btn-expand').text('+');
                    
                    $section.addClass('active');
                    $form.slideDown();
                    $expandBtn.text('-');
                }
            });

            $('.payment-section.active .payment-form').show();
            $('.payment-section.active .btn-expand').text('-');
        },

        // ===== FORM VALIDATION =====
        initFormValidation: function() {
            // Card number formatting
            $(document).on('input', '#card_number', function() {
                let value = $(this).val().replace(/\s/g, '').replace(/[^0-9]/gi, '');
                let formatted = value.match(/.{1,4}/g)?.join(' ') || value;
                $(this).val(formatted);
                PaymentPage.updateCardIcons(value);
                PaymentPage.validatePaymentForm();
            });

            // Expiry date formatting
            $(document).on('input', '#expiry_date', function() {
                let value = $(this).val().replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.substring(0, 2) + ' / ' + value.substring(2, 4);
                }
                $(this).val(value);
                PaymentPage.validatePaymentForm();
            });

            // Security code
            $(document).on('input', '#security_code', function() {
                $(this).val($(this).val().replace(/[^0-9]/g, ''));
                PaymentPage.validatePaymentForm();
            });

            // Terms and country
            $(document).on('change', '#terms-agreement, #country', function() {
                PaymentPage.validatePaymentForm();
            });
        },

        updateCardIcons: function(cardNumber) {
            const $icons = $('.card-icons img');
            $icons.css('opacity', '0.3');
            
            if (cardNumber.startsWith('4')) {
                $icons.filter('[alt="Visa"]').css('opacity', '1');
            } else if (cardNumber.startsWith('5') || cardNumber.startsWith('2')) {
                $icons.filter('[alt="Mastercard"]').css('opacity', '1');
            } else if (cardNumber.startsWith('3')) {
                $icons.filter('[alt*="American"], [alt*="Amex"]').css('opacity', '1');
            }
        },

        validatePaymentForm: function() {
            const cardNumber = $('#card_number').val().replace(/\s/g, '');
            const expiryDate = $('#expiry_date').val();
            const securityCode = $('#security_code').val();
            const country = $('#country').val();
            const termsAccepted = $('#terms-agreement').is(':checked');
            
            const isValid = cardNumber.length >= 13 && 
                           expiryDate.length >= 7 && 
                           securityCode.length >= 3 && 
                           country && 
                           termsAccepted;
            
            const $payBtn = $('#complete-payment');
            $payBtn.prop('disabled', !isValid);
            
            if (isValid) {
                $payBtn.removeClass('btn-disabled');
            } else {
                $payBtn.addClass('btn-disabled');
            }
            
            return isValid;
        },

        // ===== PAYMENT PROCESSING =====
        initPaymentProcessing: function() {
            $(document).on('click', '#complete-payment', function() {
                if (!PaymentPage.validatePaymentForm()) {
                    PaymentPage.showNotification('Please fill in all required fields and accept the terms.', 'error');
                    return;
                }
                
                PaymentPage.processPayment();
            });
        },

        processPayment: function() {
            this.showPaymentProcessingModal();
            this.simulatePaymentProcessing();
            
            setTimeout(() => {
                this.hidePaymentProcessingModal();
                
                if (Math.random() > 0.1) {
                    this.showPaymentSuccessModal();
                } else {
                    this.showPaymentErrorModal('Card declined. Please check your card details.');
                }
            }, 4000);
        },

        showPaymentProcessingModal: function() {
            const modalHTML = `
                <div id="payment-processing-modal" class="modal">
                    <div class="modal-content">
                        <div class="modal-body text-center">
                            <div class="loading-spinner"></div>
                            <h3>Processing Payment...</h3>
                            <p>Please wait while we securely process your payment.</p>
                            <div class="processing-steps">
                                <div class="step active" id="step-1">
                                    <span class="step-icon">1</span>
                                    <span>Validating card details</span>
                                </div>
                                <div class="step" id="step-2">
                                    <span class="step-icon">2</span>
                                    <span>Authorizing payment</span>
                                </div>
                                <div class="step" id="step-3">
                                    <span class="step-icon">3</span>
                                    <span>Confirming booking</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHTML);
            $('#payment-processing-modal').show();
        },

        simulatePaymentProcessing: function() {
            this.currentStep = 0;
            
            const stepInterval = setInterval(() => {
                if (this.currentStep < this.paymentProcessingSteps.length) {
                    if (this.currentStep > 0) {
                        $(`#${this.paymentProcessingSteps[this.currentStep - 1]}`)
                            .removeClass('active')
                            .addClass('completed')
                            .find('.step-icon')
                            .text('✓');
                    }
                    
                    $(`#${this.paymentProcessingSteps[this.currentStep]}`).addClass('active');
                    this.currentStep++;
                } else {
                    clearInterval(stepInterval);
                }
            }, 1200);
        },

        hidePaymentProcessingModal: function() {
            $('#payment-processing-modal').hide().remove();
        },

        showPaymentSuccessModal: function() {
            const modalHTML = `
                <div id="payment-success-modal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Payment Successful!</h3>
                        </div>
                        <div class="modal-body text-center">
                            <div class="success-icon">
                                <svg width="60" height="60" fill="#28a745" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                </svg>
                            </div>
                            <h4>Booking Confirmed!</h4>
                            <p>Your tickets have been booked successfully.</p>
                            <div class="booking-confirmation">
                                <p><strong>Confirmation Number:</strong> <span id="confirmation-number">CIN-${Math.random().toString(36).substr(2, 9).toUpperCase()}</span></p>
                            </div>
                            <p class="confirmation-note">You will receive a confirmation email shortly with your tickets.</p>
                            <div class="success-actions">
                                <button class="btn btn-primary" id="book-another">Book Another Movie</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHTML);
            $('#payment-success-modal').show();
            
            // Clear booking data
            PaymentPage.clearBookingData();

            $('#book-another').on('click', function() {
                const baseSite = (typeof cinema_ajax !== 'undefined' && cinema_ajax.site_url) ? cinema_ajax.site_url.replace(/\/$/, '') : `${location.protocol}//${location.host}`;
                window.location.href = baseSite + '/movies/';
            });
        },

        showPaymentErrorModal: function(message) {
            const modalHTML = `
                <div id="payment-error-modal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Payment Failed</h3>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body text-center">
                            <div class="error-icon">
                                <svg width="60" height="60" fill="#dc3545" viewBox="0 0 24 24">
                                    <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/>
                                </svg>
                            </div>
                            <h4>Payment could not be processed</h4>
                            <p id="error-message">${message}</p>
                            <div class="error-actions">
                                <button class="btn btn-primary" onclick="$('#payment-error-modal').hide().remove()">Try Again</button>
                                <button class="btn btn-secondary" onclick="(function(){var baseSite=(typeof cinema_ajax!=='undefined'&&cinema_ajax.site_url)?cinema_ajax.site_url.replace(/\/$/,''):window.location.origin;window.location.href=baseSite + '/cart/';})()">Back to Cart</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHTML);
            $('#payment-error-modal').show();
        },

        // ===== PROMO CODE =====
        initPromoCode: function() {
            $(document).on('click', '.btn-apply, .btn-apply-widget, .apply-btn', function() {
                const $btn = $(this);
                const $input = $btn.siblings('input').first();
                if (!$input.length) {
                    $input = $btn.parent().find('input').first();
                }
                const promoCode = $input.val().trim();
                
                if (promoCode) {
                    PaymentPage.applyPromoCode(promoCode, $input, $btn);
                } else {
                    PaymentPage.showNotification('Please enter a promo code.', 'error');
                }
            });
        },

        applyPromoCode: function(code, $input, $btn) {
            const originalText = $btn.text();
            $btn.prop('disabled', true).text('Applying...');
            
            setTimeout(() => {
                $btn.prop('disabled', false).text(originalText);
                
                const lowerCode = code.toLowerCase();
                if (lowerCode === 'discount10' || lowerCode === '10off') {
                    PaymentPage.showNotification('10% discount applied!', 'success');
                    this.applyDiscount(0.1);
                    $input.val('').prop('disabled', true);
                    $btn.text('Applied').prop('disabled', true);
                } else if (lowerCode === 'student' || lowerCode === 'student15') {
                    PaymentPage.showNotification('Student discount applied!', 'success');
                    this.applyDiscount(0.15);
                    $input.val('').prop('disabled', true);
                    $btn.text('Applied').prop('disabled', true);
                } else if (lowerCode === 'freebooking' || lowerCode === 'nobooking') {
                    PaymentPage.showNotification('Booking fee waived!', 'success');
                    this.removeBookingFee();
                    $input.val('').prop('disabled', true);
                    $btn.text('Applied').prop('disabled', true);
                } else {
                    PaymentPage.showNotification('Invalid promo code. Please check and try again.', 'error');
                    $input.focus().select();
                }
            }, 1500);
        },

        applyDiscount: function(percentage) {
            const currentSubtotal = parseFloat($('.pricing-row:not(.total)').first().find('span:last').text().replace('$', ''));
            const discountAmount = currentSubtotal * percentage;
            const newSubtotal = currentSubtotal - discountAmount;
            const bookingFee = parseFloat($('.pricing-row:not(.total)').eq(1).find('span:last').text().replace('$', ''));
            const newTotal = newSubtotal + bookingFee;
            
            const discountRow = `
                <div class="pricing-row discount">
                    <span>Discount (${(percentage * 100).toFixed(0)}%)</span>
                    <span>-${discountAmount.toFixed(2)}</span>
                </div>
            `;
            $('.pricing-row.total').before(discountRow);
            
            $('.pricing-row.total span:last').text(`${newTotal.toFixed(2)}`);
            $('.ticket-summary').text($('.ticket-summary').text().replace(/\$[\d.]+/, `${newTotal.toFixed(2)}`));
            $('#complete-payment').text(`PAY ${newTotal.toFixed(2)} WITH CARD`);
        },

        removeBookingFee: function() {
            $('.pricing-row:not(.total)').eq(1).find('span:last').text('$0.00');
            
            const subtotal = parseFloat($('.pricing-row:not(.total)').first().find('span:last').text().replace('$', ''));
            const discountAmount = parseFloat($('.pricing-row.discount span:last').text().replace(/[^0-9.]/g, '')) || 0;
            const newTotal = subtotal - discountAmount;
            
            $('.pricing-row.total span:last').text(`${newTotal.toFixed(2)}`);
            $('.ticket-summary').text($('.ticket-summary').text().replace(/\$[\d.]+/, `${newTotal.toFixed(2)}`));
            $('#complete-payment').text(`PAY ${newTotal.toFixed(2)} WITH CARD`);
        },

        // ===== UTILITY FUNCTIONS =====
        showNotification: function(message, type = 'info') {
            const icons = {
                success: '<svg width="20" height="20" fill="#28a745" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>',
                error: '<svg width="20" height="20" fill="#dc3545" viewBox="0 0 24 24"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>',
                warning: '<svg width="20" height="20" fill="#ffc107" viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>',
                info: '<svg width="20" height="20" fill="#17a2b8" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>'
            };
            
            const notification = `
                <div class="notification ${type}">
                    <div class="notification-icon">${icons[type] || icons.info}</div>
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
            
            // Auto remove
            setTimeout(() => {
                $notification.removeClass('notification-show').addClass('notification-hide');
                setTimeout(() => $notification.remove(), 300);
            }, 5000);
            
            // Manual close
            $notification.find('.notification-close').on('click', function() {
                $notification.removeClass('notification-show').addClass('notification-hide');
                setTimeout(() => $notification.remove(), 300);
            });
        },

        clearBookingData: function() {
            localStorage.removeItem('cinema_temp_booking');
            localStorage.removeItem('cinema_booking_backup');
            localStorage.removeItem('cinema_booking_data');
            
            if (typeof cinema_ajax !== 'undefined') {
                $.ajax({
                    url: cinema_ajax.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'cinema_clear_booking',
                        nonce: cinema_ajax.nonce
                    }
                });
            }
        }
    };

    $(document).ready(function() {
        PaymentPage.init();
    });

})(jQuery);