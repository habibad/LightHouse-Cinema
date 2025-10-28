/**
 * Payment Page JavaScript - Dual Gateway Support (Stripe & Square)
 * Compatible with existing cinema booking system
 */

(function ($) {
    'use strict';

    const PaymentPage = {
        paymentProcessingSteps: ['step-1', 'step-2', 'step-3'],
        currentStep: 0,
        currentPaymentMethod: 'stripe',
        stripeInitialized: false,
        squareInitialized: false,
        stripe: null,
        stripeCardElement: null,
        squarePayments: null,
        squareCard: null,

        init: function () {
            if (!$('.payment-container').length) return;

            this.initPaymentSections();
            this.initPaymentMethodSelector();
            this.initFormValidation();
            this.initPromoCode();

            console.log('💳 Payment page initialized (Dual Gateway)');
        },

        // ===== PAYMENT METHOD SELECTOR =====
        initPaymentMethodSelector: function () {
            const self = this;

            // Handle payment method radio button change
            $('input[name="payment_method"]').on('change', function () {
                self.currentPaymentMethod = $(this).val();
                console.log('Payment method changed to:', self.currentPaymentMethod);

                // Hide all payment sections
                $('#stripe-payment-section, #square-payment-section').addClass('collapsed');
                $('#stripe-payment-section .section-content, #square-payment-section .section-content').slideUp();

                // Show selected payment section
                if (self.currentPaymentMethod === 'stripe') {
                    $('#stripe-payment-section').removeClass('collapsed');
                    $('#stripe-payment-section .section-content').slideDown();
                    $('#stripe-payment-section .expand-btn').text('−');
                    if (!self.stripeInitialized) self.initStripe();
                } else if (self.currentPaymentMethod === 'square') {
                    $('#square-payment-section').removeClass('collapsed');
                    $('#square-payment-section .section-content').slideDown();
                    $('#square-payment-section .expand-btn').text('−');
                    if (!self.squareInitialized) self.initSquare();
                }
            });

            // Initialize default payment method (Stripe)
            const defaultMethod = $('input[name="payment_method"]:checked').val() || 'stripe';
            $('input[name="payment_method"][value="' + defaultMethod + '"]').trigger('change');
        },

        // ===== STRIPE INITIALIZATION =====
        initStripe: function () {
            const self = this;

            // Check if Stripe is loaded
            if (typeof Stripe === 'undefined') {
                console.error('Stripe.js not loaded');
                self.showNotification('Payment system not available. Please refresh the page.', 'error');
                return;
            }

            try {
                // Get publishable key from localized script or page
                const stripeKey = typeof cinema_stripe !== 'undefined' ? cinema_stripe.publishable_key :
                    (typeof STRIPE_PUBLISHABLE_KEY !== 'undefined' ? STRIPE_PUBLISHABLE_KEY : null);

                if (!stripeKey) {
                    console.error('Stripe publishable key not found');
                    self.showNotification('Payment configuration error. Please contact support.', 'error');
                    return;
                }

                self.stripe = Stripe(stripeKey);
                const elements = self.stripe.elements();

                // Clear the container first to avoid child nodes warning
                const cardContainer = document.getElementById('stripe-card-element');
                if (cardContainer) {
                    cardContainer.innerHTML = ''; // Clear any existing content
                }

                self.stripeCardElement = elements.create('card', {
                    style: {
                        base: {
                            fontSize: '14px',
                            color: '#333',
                            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                            '::placeholder': { color: '#aaa' }
                        },
                        invalid: {
                            color: '#fa755a',
                            iconColor: '#fa755a'
                        }
                    },
                    hidePostalCode: false
                });

                self.stripeCardElement.mount('#stripe-card-element');

                self.stripeCardElement.on('change', function (event) {
                    const displayError = document.getElementById('stripe-card-errors');
                    if (displayError) {
                        displayError.textContent = event.error ? event.error.message : '';
                    }
                });

                self.stripeInitialized = true;
                console.log('✓ Stripe initialized successfully');

            } catch (error) {
                console.error('Stripe initialization error:', error);
                self.showNotification('Failed to initialize Stripe payment. Please refresh the page.', 'error');
            }
        },

        // ===== SQUARE INITIALIZATION =====
        initSquare: async function () {
            const self = this;

            // Check if Square is loaded
            if (typeof Square === 'undefined') {
                console.error('Square.js not loaded');
                self.showNotification('Payment system not available. Please refresh the page.', 'error');
                return;
            }

            try {
                const squareAppId = typeof cinema_square !== 'undefined' ? cinema_square.application_id : null;
                const squareLocationId = typeof cinema_square !== 'undefined' ? cinema_square.location_id : null;

                if (!squareAppId || !squareLocationId) {
                    console.error('Square credentials not found');
                    self.showNotification('Payment configuration error. Please contact support.', 'error');
                    return;
                }

                // Clear the container first
                const cardContainer = document.getElementById('square-card-container');
                if (cardContainer) {
                    cardContainer.innerHTML = ''; // Clear any existing content
                }

                self.squarePayments = Square.payments(squareAppId, squareLocationId);
                self.squareCard = await self.squarePayments.card();
                await self.squareCard.attach('#square-card-container');

                self.squareInitialized = true;
                console.log('✓ Square initialized successfully');

            } catch (error) {
                console.error('Square initialization error:', error);
                self.showNotification('Failed to initialize Square payment. Please refresh the page.', 'error');
            }
        },

        // ===== PAYMENT SECTIONS =====
        initPaymentSections: function () {
            $(document).on('click', '.collapsible', function () {
                const $section = $(this).closest('.payment-section');
                const $content = $section.find('.section-content');
                const $btn = $(this).find('.expand-btn');

                $content.slideToggle(300);
                $btn.text($btn.text() === '+' ? '−' : '+');
                $section.toggleClass('collapsed');
            });
        },

        // ===== FORM VALIDATION =====
        initFormValidation: function () {
            const self = this;

            // Stripe form submission
            $('#stripe-payment-form').on('submit', function (e) {
                e.preventDefault();
                self.processStripePayment();
            });

            // Square form submission
            $('#square-payment-form').on('submit', function (e) {
                e.preventDefault();
                self.processSquarePayment();
            });

            // Terms checkbox validation
            $('#terms-agreement').on('change', function () {
                const isChecked = $(this).is(':checked');
                $('#stripe-submit-payment, #square-submit-payment').prop('disabled', !isChecked);
            });
        },

        // ===== STRIPE PAYMENT PROCESSING =====
        processStripePayment: async function () {
            const self = this;

            if (!$('#terms-agreement').is(':checked')) {
                self.showNotification('Please accept the terms and conditions', 'error');
                return;
            }

            if (!self.stripeInitialized || !self.stripe || !self.stripeCardElement) {
                self.showNotification('Payment system not ready. Please refresh the page.', 'error');
                return;
            }

            const submitButton = $('#stripe-submit-payment');
            submitButton.prop('disabled', true).html('<span class="spinner"></span> Processing...');

            try {
                console.log('Step 1: Creating booking...');

                // Create booking
                const bookingResponse = await self.createBooking();

                if (!bookingResponse.success) {
                    throw new Error(bookingResponse.data?.message || bookingResponse.message || 'Failed to create booking');
                }

                const bookingId = bookingResponse.data?.booking_id || bookingResponse.booking_id;

                if (!bookingId) {
                    throw new Error('No booking ID received from server');
                }

                console.log('Booking created with ID:', bookingId);
                console.log('Step 2: Creating payment intent...');

                // Get total amount from page
                const totalAmount = self.getTotalAmount();

                // Create payment intent
                const intentResponse = await self.createStripePaymentIntent(bookingId, totalAmount);

                if (!intentResponse.success && !intentResponse.client_secret) {
                    throw new Error(intentResponse.data?.message || intentResponse.message || 'Failed to initialize payment');
                }

                const clientSecret = intentResponse.data?.client_secret || intentResponse.client_secret;

                if (!clientSecret) {
                    throw new Error('No client secret received from server');
                }

                console.log('Step 3: Confirming card payment...');

                // Get customer details
                const customerName = self.getCustomerName();
                const customerEmail = self.getCustomerEmail();

                // Confirm card payment
                const { error, paymentIntent } = await self.stripe.confirmCardPayment(clientSecret, {
                    payment_method: {
                        card: self.stripeCardElement,
                        billing_details: {
                            name: customerName,
                            email: customerEmail
                        }
                    }
                });

                if (error) {
                    console.error('Stripe error:', error);
                    throw new Error(error.message);
                }

                console.log('Payment Intent Status:', paymentIntent.status);

                if (paymentIntent.status === 'succeeded') {
                    console.log('Step 4: Confirming payment...');

                    // Confirm payment
                    const confirmResponse = await self.confirmStripePayment(paymentIntent.id, bookingId);

                    if (confirmResponse.success) {
                        const bookingRef = confirmResponse.data?.booking_reference || confirmResponse.booking_reference;
                        self.showPaymentSuccessModal(bookingRef);
                    } else {
                        throw new Error('Payment succeeded but booking confirmation failed');
                    }
                } else {
                    throw new Error('Payment not completed. Status: ' + paymentIntent.status);
                }

            } catch (error) {
                console.error('Stripe payment error:', error);
                self.showNotification(error.message, 'error');
                submitButton.prop('disabled', false).html('PAY WITH STRIPE');
            }
        },

        // ===== SQUARE PAYMENT PROCESSING =====
        processSquarePayment: async function () {
            const self = this;
            console.log("square payment initiated");

            if (!$('#terms-agreement').is(':checked')) {
                self.showNotification('Please accept the terms and conditions', 'error');
                return;
            }

            if (!self.squareInitialized || !self.squareCard) {
                self.showNotification('Payment system not ready. Please refresh the page.', 'error');
                return;
            }

            const submitButton = $('#square-submit-payment');
            submitButton.prop('disabled', true).html('<span class="spinner"></span> Processing...');

            try {
                console.log('Step 1: Creating booking...');

                // Create booking
                const bookingResponse = await self.createBooking();
                console.log("booking response:", bookingResponse);

                if (!bookingResponse.success) {
                    throw new Error(bookingResponse.data?.message || bookingResponse.message || 'Failed to create booking');
                }

                const bookingId = bookingResponse.data?.booking_id || bookingResponse.booking_id;

                if (!bookingId) {
                    throw new Error('No booking ID received from server');
                }

                console.log('Booking created with ID:', bookingId);
                console.log('Step 2: Tokenizing card...');

                // Tokenize card
                const result = await self.squareCard.tokenize();
                console.log("Tokenization result:", result);

                if (result.status !== 'OK') {
                    let errorMessage = 'Payment failed';
                    if (result.errors && result.errors.length > 0) {
                        errorMessage = result.errors[0].message;
                    }
                    throw new Error(errorMessage);
                }

                console.log('Step 3: Creating Square payment...');

                // Get total amount - Convert to cents for Square
                const totalAmount = self.getTotalAmount();

                if (!totalAmount || totalAmount <= 0) {
                    throw new Error('Invalid payment amount: ' + totalAmount);
                }

                // Convert dollars to cents (Square requires amount in smallest currency unit)
                const amountInCents = Math.round(totalAmount * 100);
                console.log('Payment amount (dollars):', totalAmount);
                console.log('Payment amount (cents):', amountInCents);

                // Create Square payment
                const paymentResponse = await self.createSquarePayment(bookingId, amountInCents, result.token);
                console.log("Square payment response:", paymentResponse);
                if (!paymentResponse.success) {
                    throw new Error(paymentResponse.data?.message || paymentResponse.message || 'Payment failed');
                }

                const paymentId = paymentResponse.data?.payment_id || paymentResponse.payment_id;

                if (!paymentId) {
                    throw new Error('No payment ID received');
                }

                console.log('Step 4: Confirming Square payment...');

                // Confirm Square payment
                const confirmResponse = await self.confirmSquarePayment(paymentId, bookingId);

                if (confirmResponse.success) {
                    const bookingRef = confirmResponse.data?.booking_reference || confirmResponse.booking_reference;
                    const receiptUrl = confirmResponse.data?.receipt_url;
                    self.showPaymentSuccessModal(bookingRef, receiptUrl);
                } else {
                    throw new Error('Payment confirmation failed');
                }

            } catch (error) {
                console.error('Square payment error:', error);
                self.showNotification(error.message, 'error');
                submitButton.prop('disabled', false).html('PAY WITH SQUARE');
            }
        },

        // ===== API CALLS =====
        createBooking: async function () {
            const self = this;
            // Get cart items from session/page
            const cartItems = self.getCartItems();
            const customerName = self.getCustomerName();
            const customerEmail = self.getCustomerEmail();

            const response = await fetch(cinema_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cinema_create_booking_payment',
                    nonce: cinema_ajax.nonce,
                    cart_items: JSON.stringify(cartItems),
                    customer_name: customerName,
                    customer_email: customerEmail,
                    customer_phone: ''
                })
            });

            return await response.json();
        },

        createSquarePayment: async function (bookingId, amountInCents, sourceId) {
            // Debug logging
            console.log('Creating Square payment with:', {
                bookingId,
                amountInCents,
                sourceId: sourceId.substring(0, 10) + '...',
                type: typeof amountInCents
            });

            // Ensure amount is an integer
            const amount = parseInt(amountInCents, 10);

            if (isNaN(amount) || amount <= 0) {
                console.error('Invalid amount:', amountInCents);
                throw new Error('Invalid payment amount');
            }

            const response = await fetch(cinema_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cinema_create_square_payment',
                    nonce: cinema_ajax.nonce,
                    booking_id: bookingId,
                    amount: amount,
                    source_id: sourceId,
                    currency: 'USD'
                })
            });

            const result = await response.json();
            console.log('Square payment response:', result);
            return result;
        },

        confirmStripePayment: async function (paymentIntentId, bookingId) {
            const response = await fetch(cinema_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cinema_confirm_payment',
                    nonce: cinema_ajax.nonce,
                    payment_intent_id: paymentIntentId,
                    booking_id: bookingId
                })
            });

            return await response.json();
        },

        createSquarePayment: async function (bookingId, amountInCents, sourceId) {
            const response = await fetch(cinema_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cinema_create_square_payment',
                    nonce: cinema_ajax.nonce,
                    booking_id: bookingId,
                    amount: amountInCents,
                    source_id: sourceId,
                    currency: 'USD'
                })
            });

            return await response.json();
        },

        confirmSquarePayment: async function (paymentId, bookingId) {
            const response = await fetch(cinema_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cinema_confirm_square_payment',
                    nonce: cinema_ajax.nonce,
                    payment_id: paymentId,
                    booking_id: bookingId
                })
            });

            return await response.json();
        },

        // ===== HELPER FUNCTIONS =====
        getCartItems: function () {
            // Try to get from page data attribute or global variable
            if (typeof cinema_cart_items !== 'undefined') {
                return cinema_cart_items;
            }

            // Try to parse from page element
            const cartData = $('#cart-data').data('items');
            if (cartData) {
                return cartData;
            }

            // Default empty array
            return [];
        },

        getTotalAmount: function () {
            // Try multiple selectors to get total amount
            let amount = 0;

            // Try from pricing row
            const totalRow = $('.pricing-row.total-row span:last').text();
            if (totalRow) {
                amount = parseFloat(totalRow.replace(/[^0-9.]/g, ''));
            }

            // If not found, try from sidebar
            if (!amount || amount === 0) {
                const sidebarTotal = $('.total-amount').text();
                if (sidebarTotal) {
                    amount = parseFloat(sidebarTotal.replace(/[^0-9.]/g, ''));
                }
            }

            // If still not found, try from cart data attribute
            if (!amount || amount === 0) {
                const cartData = $('#cart-data').data('total');
                if (cartData) {
                    amount = parseFloat(cartData);
                }
            }

            console.log('Total amount detected:', amount);
            return amount || 0;
        },

        getCustomerName: function () {
            return typeof cinema_user !== 'undefined' ? cinema_user.name : 'Guest';
        },

        getCustomerEmail: function () {
            return typeof cinema_user !== 'undefined' ? cinema_user.email : '';
        },

        // ===== PROMO CODE =====
        initPromoCode: function () {
            $(document).on('click', '.btn-apply, .btn-apply-sidebar', function () {
                const $btn = $(this);
                const $input = $btn.siblings('input').length ?
                    $btn.siblings('input').first() :
                    $btn.parent().find('input').first();
                const promoCode = $input.val().trim();

                if (promoCode) {
                    PaymentPage.applyPromoCode(promoCode, $input, $btn);
                } else {
                    PaymentPage.showNotification('Please enter a promo code.', 'error');
                }
            });
        },

        applyPromoCode: function (code, $input, $btn) {
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
                } else {
                    PaymentPage.showNotification('Invalid promo code.', 'error');
                    $input.focus().select();
                }
            }, 1000);
        },

        applyDiscount: function (percentage) {
            // Implementation depends on your pricing structure
            console.log('Applying discount:', percentage);
        },

        // ===== SUCCESS MODAL =====
        showPaymentSuccessModal: function (bookingReference, receiptUrl) {
            let receiptButton = '';
            if (receiptUrl) {
                receiptButton = `<a href="${receiptUrl}" target="_blank" class="btn-secondary" style="margin-top: 10px;">View Receipt</a>`;
            }

            const modalHTML = `
                <div id="payment-success-modal" class="modal" style="display: flex;">
                    <div class="modal-overlay"></div>
                    <div class="modal-content">
                        <div class="success-icon">
                            <svg width="60" height="60" fill="#28a745" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                        <h3>Payment Successful!</h3>
                        <p>Your booking has been confirmed</p>
                        <div class="booking-ref">
                            <strong>Confirmation:</strong> ${bookingReference}
                        </div>
                        <button class="btn-primary" onclick="window.location.href='${cinema_ajax.site_url}/my-account'">
                            View My Bookings
                        </button>
                        <button class="btn-secondary" onclick="window.location.href='${cinema_ajax.site_url}/movies'">
                            Book Another Movie
                        </button>
                        ${receiptButton}
                    </div>
                </div>
            `;

            $('body').append(modalHTML);

            // Clear cart
            $.post(cinema_ajax.ajax_url, {
                action: 'cinema_clear_cart',
                nonce: cinema_ajax.nonce
            });

            this.clearBookingData();
        },

        // ===== NOTIFICATIONS =====
        showNotification: function (message, type = 'info') {
            const icons = {
                success: '<svg width="20" height="20" fill="#28a745" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>',
                error: '<svg width="20" height="20" fill="#dc3545" viewBox="0 0 24 24"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>',
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

            setTimeout(() => {
                $notification.removeClass('notification-show').addClass('notification-hide');
                setTimeout(() => $notification.remove(), 300);
            }, 5000);

            $notification.find('.notification-close').on('click', function () {
                $notification.removeClass('notification-show').addClass('notification-hide');
                setTimeout(() => $notification.remove(), 300);
            });
        },

        clearBookingData: function () {
            // Only remove items that don't violate the localStorage restriction
            if (typeof localStorage !== 'undefined') {
                try {
                    localStorage.removeItem('cinema_temp_booking');
                    localStorage.removeItem('cinema_booking_backup');
                    localStorage.removeItem('cinema_booking_data');
                } catch (e) {
                    console.log('LocalStorage not available or restricted');
                }
            }
        }
    };

    $(document).ready(function () {
        PaymentPage.init();
    });

})(jQuery);