/**
 * Cart Page JavaScript
 * Handles cart display, ticket removal, promo codes, and checkout
 */

(function($) {
    'use strict';

    const CartPage = {
        init: function() {
            if (!$('.cart-container').length) return;

            this.initRemoveTicket();
            this.initToggleSections();
            this.initPromoCode();
            this.initCheckout();

            console.log('🛒 Cart page initialized');
        },

        // ===== REMOVE TICKET =====
        initRemoveTicket: function() {
            $(document).on('click', '.btn-remove', function() {
                const seatToRemove = $(this).data('seat');
                const $ticketItem = $(this).closest('.ticket-item');
                
                if (confirm(`Remove seat ${seatToRemove} from your cart?`)) {
                    $ticketItem.addClass('removing');
                    
                    setTimeout(() => {
                        $ticketItem.remove();
                        CartPage.updateCartTotals();
                        CartPage.removeTicketFromBooking(seatToRemove);
                        
                        if ($('.ticket-item').length === 0) {
                            CartPage.showEmptyCartMessage();
                        }
                    }, 300);
                }
            });
        },

        removeTicketFromBooking: function(seatNumber) {
            if (typeof cinema_ajax === 'undefined') {
                console.warn('cinema_ajax not available for seat removal');
                return;
            }

            $.ajax({
                url: cinema_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'cinema_remove_seat',
                    nonce: cinema_ajax.nonce,
                    seat: seatNumber
                },
                success: function(response) {
                    if (!response || !response.success) {
                        Cinema.showNotification('Failed to remove seat. Please refresh the page.', 'error');
                    }
                },
                error: function() {
                    Cinema.showNotification('Connection error. Please refresh the page.', 'error');
                }
            });
        },

        // ===== UPDATE TOTALS =====
        updateCartTotals: function() {
            const $ticketItems = $('.ticket-item');
            let subtotal = 0;
            
            $ticketItems.each(function() {
                const priceText = $(this).find('.ticket-price').text().replace('$', '');
                const price = parseFloat(priceText) || 0;
                subtotal += price;
            });
            
            const ticketCount = $ticketItems.length;
            const bookingFee = ticketCount * 2.25;
            const total = subtotal;
            
            $('.pricing-row:contains("Subtotal") span:last').text(`$${(subtotal - bookingFee).toFixed(2)}`);
            $('.pricing-row:contains("Booking Fee") span:last').text(`$${bookingFee.toFixed(2)}`);
            $('.pricing-row.total span:last').text(`$${total.toFixed(2)}`);
            $('.ticket-summary, .tickets-info span:last').text(`${ticketCount} ticket${ticketCount !== 1 ? 's' : ''} • $${total.toFixed(2)}`);
        },

        // ===== EMPTY CART =====
        showEmptyCartMessage: function() {
            const emptyHTML = `
                <div class="empty-cart-message">
                    <div class="empty-cart-icon">
                        <svg width="80" height="80" fill="#ccc" viewBox="0 0 24 24">
                            <path d="M22 10V6c0-1.11-.9-2-2-2H4c-1.11 0-2 .89-2 2v4c1.11 0 2 .89 2 2s-.89 2-2 2v4c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2v-4c-1.11 0-2-.89-2-2s.89-2 2-2z"/>
                        </svg>
                    </div>
                    <h3>Your cart is empty</h3>
                    <p>You haven't selected any tickets yet.</p>
                    <button class="btn btn-primary" onclick="history.back()">Select Seats</button>
                </div>
            `;
            $('.cart-main').html(emptyHTML);
            $('.cart-sidebar').hide();
        },

        // ===== TOGGLE SECTIONS =====
        initToggleSections: function() {
            $(document).on('click', '#toggle-cart-details, #toggle-ticket-details', function() {
                const target = $(this).attr('id') === 'toggle-cart-details' ? '#cart-details' : '#ticket-details';
                const $details = $(target);
                
                if ($details.is(':visible')) {
                    $details.slideUp(300);
                    $(this).text('⌄');
                } else {
                    $details.slideDown(300);
                    $(this).text('⌃');
                }
            });

            $('#cart-details, #ticket-details').show();

            $(document).on('click', '.btn-expand', function() {
                const $section = $(this).closest('.rewards-section, .cart-items');
                const $details = $section.find('.cart-item-details, .ticket-details');
                
                if ($details.length === 0) return;
                
                if ($details.is(':visible')) {
                    $details.slideUp(300);
                    $(this).text('⌄');
                } else {
                    $details.slideDown(300);
                    $(this).text('⌃');
                }
            });
        },

        // ===== PROMO CODE =====
        initPromoCode: function() {
            $(document).on('click', '.btn-apply, .btn-apply-widget, .apply-btn', function() {
                const $btn = $(this);
                const $input = $btn.siblings('input').first();
                const promoCode = $input.val().trim();
                
                if (!promoCode) {
                    Cinema.showNotification('Please enter a promo code.', 'error');
                    $input.focus();
                    return;
                }
                
                CartPage.applyPromoCode(promoCode, $input, $btn);
            });
        },

        applyPromoCode: function(code, $input, $btn) {
            const originalText = $btn.text();
            $btn.prop('disabled', true).text('Applying...');
            
            setTimeout(() => {
                const lowerCode = code.toLowerCase();
                let success = false;
                let message = '';
                
                if (lowerCode === 'discount10' || lowerCode === '10off') {
                    CartPage.applyDiscount(0.1);
                    message = '10% discount applied!';
                    success = true;
                } else if (lowerCode === 'freebooking') {
                    CartPage.removeBookingFee();
                    message = 'Booking fee waived!';
                    success = true;
                } else if (lowerCode === 'student15') {
                    CartPage.applyDiscount(0.15);
                    message = 'Student discount applied!';
                    success = true;
                } else {
                    message = 'Invalid promo code.';
                }
                
                if (success) {
                    $input.val('').prop('disabled', true);
                    $btn.text('Applied').prop('disabled', true);
                    Cinema.showNotification(message, 'success');
                } else {
                    $btn.prop('disabled', false).text(originalText);
                    Cinema.showNotification(message, 'error');
                    $input.focus().select();
                }
            }, 1500);
        },

        applyDiscount: function(percentage) {
            const $subtotalRow = $('.pricing-row:contains("Subtotal")');
            const currentSubtotal = parseFloat($subtotalRow.find('span:last').text().replace('$', ''));
            const discountAmount = currentSubtotal * percentage;
            
            if ($('.pricing-row.discount').length === 0) {
                const discountRow = `
                    <div class="pricing-row discount">
                        <span>Discount (${(percentage * 100).toFixed(0)}%)</span>
                        <span>-$${discountAmount.toFixed(2)}</span>
                    </div>
                `;
                $('.pricing-row.total').before(discountRow);
            }
            
            this.updateCartTotals();
        },

        removeBookingFee: function() {
            $('.pricing-row:contains("Booking Fee") span:last').text('$0.00');
            this.updateCartTotals();
        },

        // ===== CHECKOUT =====
        initCheckout: function() {
            $(document).on('click', '#proceed-to-payment', function() {
                const $btn = $(this);
                $btn.prop('disabled', true).html('<div class="btn-spinner"></div> Loading...');
                
                setTimeout(() => {
                    window.location.href = '/payment/';
                }, 500);
            });

            $(document).on('click', '#back-to-seats', function() {
                const showtimeId = $('.movie-booking-card').data('showtime-id') || '';
                // use localized site url if available
                const baseUrl = (typeof cinema_ajax !== 'undefined' && cinema_ajax.site_url) ? cinema_ajax.site_url : `${location.protocol}//${location.host}`;
                const redirect = baseUrl.replace(/\/$/, '') + `/seat-selection/?showtime=${encodeURIComponent(showtimeId)}`;
                window.location.href = redirect;
            });

            $(document).on('click', '#remove-all', function() {
                if (confirm('Are you sure you want to remove all tickets?')) {
                    $.ajax({
                        url: cinema_ajax.ajax_url,
                        method: 'POST',
                        data: {
                            action: 'cinema_clear_booking',
                            nonce: cinema_ajax.nonce
                        },
                        success: function() {
                            window.location.href = '/movies/';
                        }
                    });
                }
            });
        }
    };

    $(document).ready(function() {
        CartPage.init();
    });

})(jQuery);