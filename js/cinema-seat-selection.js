/**
 * Enhanced Seat Selection with Real-time Updates
 */

(function($) {
    'use strict';

    const SeatSelection = {
        selectedSeats: [],
        currentZoom: 1,
        showtimeId: null,
        bookingData: {},
        updateInterval: null,

        init: function() {
            if (!$('.seat-map').length) return;

            this.showtimeId = $('.seat-map').data('showtime-id');
            // Default per-ticket price (from server-rendered data attribute)
            this.defaultTicketPrice = parseFloat($('.seat-map').data('ticket-price')) || 0;

            // Initialize ticket & cart summary to zero (JS will update when seats selected)
            try {
                const $summary = $('.ticket-summary');
                if ($summary.length) {
                    $summary.find('.ticket-count').text('0 ticket');
                    $summary.find('.ticket-price').text(`$${(0).toFixed(2)}`);
                }
                $('.summary-count').text('0 ticket');
                $('.cart-total').text(`$${(0).toFixed(2)}`);
            } catch (e) {
                // ignore
            }
            this.loadSavedSeatSelection();
            this.initSeatClicks();
            this.initSeatControls();
            this.initContinueButton();
            this.initModalActions();
            this.startAutoSave();
            this.startRealTimeUpdates(); // New
            this.preventAccidentalLeave();

            console.log('Seat selection initialized with real-time updates');
        },

        // New: Real-time seat availability updates
        startRealTimeUpdates: function() {
            this.updateSeatAvailability();
            
            // Check every 5 seconds
            this.updateInterval = setInterval(() => {
                this.updateSeatAvailability();
            }, 5000);
        },

        updateSeatAvailability: function() {
            $.ajax({
                url: cinema_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'cinema_check_seat_availability',
                    nonce: cinema_ajax.nonce,
                    showtime_id: this.showtimeId
                },
                success: (response) => {
                    if (response.success) {
                        this.markUnavailableSeats(response.data.unavailable);
                    }
                }
            });
        },

        markUnavailableSeats: function(unavailableSeats) {
            $('.seat').each(function() {
                const seatNumber = $(this).data('seat');
                
                if (unavailableSeats.includes(seatNumber)) {
                    if (!$(this).hasClass('selected')) {
                        $(this).removeClass('available').addClass('unavailable').prop('disabled', true);
                    }
                } else {
                    if ($(this).hasClass('unavailable') && !$(this).hasClass('reserved')) {
                        $(this).removeClass('unavailable').addClass('available').prop('disabled', false);
                    }
                }
            });
        },

        initSeatClicks: function() {
            $(document).on('click', '.seat.available', (e) => {
                const $seat = $(e.currentTarget);
                const seatNumber = $seat.data('seat');
                const seatPrice = parseFloat($seat.data('price')) || 19.00;
                const seatType = $seat.data('type') || 'regular';
                
                if ($seat.hasClass('selected')) {
                    this.deselectSeat($seat, seatNumber);
                    this.releaseSeatLock([seatNumber]);
                } else {
                    if (this.selectedSeats.length >= 8) {
                        Cinema.showNotification('Maximum 8 seats can be selected at once.', 'warning');
                        return;
                    }
                    
                    // Lock seat on server
                    this.lockSeat(seatNumber, () => {
                        this.selectSeat($seat, seatNumber, seatPrice, seatType);
                    });
                }
                
                this.updateSeatSummary();
            });
        },

        lockSeat: function(seatNumber, callback) {
            $.ajax({
                url: cinema_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'cinema_lock_seats',
                    nonce: cinema_ajax.nonce,
                    showtime_id: this.showtimeId,
                    seat_numbers: [seatNumber]
                },
                success: (response) => {
                    if (response.success) {
                        callback();
                    } else {
                        Cinema.showNotification(response.data.message || 'Seat is already taken', 'error');
                    }
                }
            });
        },

        releaseSeatLock: function(seatNumbers) {
            $.ajax({
                url: cinema_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'cinema_release_seat_locks',
                    nonce: cinema_ajax.nonce,
                    showtime_id: this.showtimeId
                }
            });
        },

        selectSeat: function($seat, seatNumber, seatPrice, seatType) {
            $seat.addClass('selected seat-animation');
            this.selectedSeats.push({
                number: seatNumber,
                price: seatPrice,
                type: seatType
            });
            
            setTimeout(() => {
                $seat.removeClass('seat-animation');
            }, 300);
        },

        deselectSeat: function($seat, seatNumber) {
            $seat.removeClass('selected');
            this.selectedSeats = this.selectedSeats.filter(seat => seat.number !== seatNumber);
        },

        updateSeatSummary: function() {
            const seatCount = this.selectedSeats.length;
            const seatNumbers = this.selectedSeats.map(seat => seat.number).join(', ');
            
            $('#seat-count').text(seatCount);
            $('#selected-seat-numbers').text(seatNumbers || 'None selected');
            
            const $continueBtn = $('#continue-to-cart');
            if (seatCount > 0) {
                $continueBtn.prop('disabled', false)
                           .removeClass('btn-disabled')
                           .html(`NEXT: CART <small>(${seatCount} seat${seatCount !== 1 ? 's' : ''})</small>`);
            } else {
                $continueBtn.prop('disabled', true)
                           .addClass('btn-disabled')
                           .text('SELECT A SEAT TO CONTINUE');
            }

            $('#form-selected-seats').val(seatNumbers);

            // Update ticket summary (dynamic count and price)
            try {
                const $summary = $('.ticket-summary');
                if ($summary.length) {
                    const total = this.selectedSeats.reduce((acc, s) => acc + (parseFloat(s.price) || 0), 0);
                    const priceText = `$${total.toFixed(2)}`;
                    $summary.find('.ticket-count').text(`${seatCount} ticket${seatCount !== 1 ? 's' : ''}`);
                    $summary.find('.ticket-price').text(priceText);
                }
            } catch (e) {
                console.error('Error updating ticket summary:', e);
            }
        },

        initSeatControls: function() {
            $('.btn-zoom-in').on('click', () => {
                this.zoomSeatMap(1.2);
            });

            $('.btn-zoom-out').on('click', () => {
                this.zoomSeatMap(0.8);
            });

            $('.btn-fullscreen').on('click', () => {
                this.toggleFullscreen();
            });
        },

        zoomSeatMap: function(factor) {
            const $seatMap = $('.seat-map');
            this.currentZoom = Math.max(0.5, Math.min(2.5, this.currentZoom * factor));
            
            $seatMap.css({
                'transform': `scale(${this.currentZoom})`,
                'transform-origin': 'center center'
            });
            
            $('.btn-zoom-out').prop('disabled', this.currentZoom <= 0.5);
            $('.btn-zoom-in').prop('disabled', this.currentZoom >= 2.5);
        },

        toggleFullscreen: function() {
            const element = $('.seat-selection-main')[0];
            
            if (!document.fullscreenElement) {
                if (element.requestFullscreen) {
                    element.requestFullscreen().catch(() => {
                        Cinema.showNotification('Fullscreen not supported on this device.', 'info');
                    });
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        },

        initContinueButton: function() {
            $(document).on('click', '#continue-to-cart', (e) => {
                e.preventDefault();
                
                if (this.selectedSeats.length === 0) {
                    Cinema.showNotification('Please select at least one seat to continue.', 'error');
                    return;
                }
                
                this.showSeatSelectionModal();
            });
        },

        showSeatSelectionModal: function() {
            const selectedSeatNumbers = this.selectedSeats.map(seat => seat.number).join(', ');
            
            if (!$('#seat-selection-modal').length) {
                this.createSeatSelectionModal();
            }
            
            $('#modal-selected-seats').text(selectedSeatNumbers + '/Recliner Seat');
            $('#seat-selection-modal').show();
        },

        createSeatSelectionModal: function() {
            const modalHTML = `
                <div id="seat-selection-modal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>SELECT YOUR TICKET</h3>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="selected-seat-info">
                                <p><strong>Seats Selected:</strong> <span id="modal-selected-seats"></span></p>
                                <p class="booking-fee-notice">A $2.25 booking fee per ticket is included in the price of the ticket.</p>
                            </div>
                            <div class="ticket-options">
                                <div class="ticket-type ora-member">
                                    <div class="ticket-details">
                                        <div class="member-badge">
                                            <span>Ora</span>
                                            <small>Cinépolis Rewards Member</small>
                                        </div>
                                        <div class="ticket-info">
                                            <select class="ticket-select">
                                                <option value="adult">Adult</option>
                                                <option value="child">Child</option>
                                                <option value="senior">Senior</option>
                                            </select>
                                            <small>2 ticket types available</small>
                                        </div>
                                    </div>
                                    <div class="ticket-price">
                                        <span class="price">$21.25</span>
                                        <small>($19.00 + $2.25 Booking Fee)</small>
                                        <button class="btn-add" data-type="ora-adult" data-price="21.25">ADD</button>
                                    </div>
                                </div>
                                <div class="ticket-type">
                                    <div class="ticket-details">
                                        <div class="ticket-info">
                                            <span class="ticket-label">Adult</span>
                                        </div>
                                    </div>
                                    <div class="ticket-price">
                                        <span class="price">$21.25</span>
                                        <small>($19.00 + $2.25 Booking Fee)</small>
                                        <button class="btn-add" data-type="adult" data-price="21.25">ADD</button>
                                    </div>
                                </div>
                                <div class="ticket-type">
                                    <div class="ticket-details">
                                        <div class="ticket-info">
                                            <span class="ticket-label">Senior (60+)</span>
                                        </div>
                                    </div>
                                    <div class="ticket-price">
                                        <span class="price">$19.25</span>
                                        <small>($17.00 + $2.25 Booking Fee)</small>
                                        <button class="btn-add" data-type="senior" data-price="19.25">ADD</button>
                                    </div>
                                </div>
                            </div>
                            <div class="member-options">
                                <h4>WOULD YOU LIKE TO PURCHASE TICKETS FOR OTHER MEMBERS?</h4>
                                <p>Link a member</p>
                                <div class="link-buttons">
                                    <button class="btn btn-dark" data-action="link">LINK</button>
                                    <button class="btn btn-dark" data-action="card">CARD</button>
                                    <button class="btn btn-dark" data-action="email">EMAIL</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;$('body').append(modalHTML);
        },

        initModalActions: function() {
            $(document).on('click', '.btn-add', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const $btn = $(e.currentTarget);
                const ticketType = $btn.data('type') || 'adult';
                const ticketPrice = parseFloat($btn.data('price')) || 21.25;
                
                this.bookingData = {
                    showtime_id: this.showtimeId,
                    seats: this.selectedSeats,
                    ticket_type: ticketType,
                    ticket_price: ticketPrice,
                    total: this.selectedSeats.length * ticketPrice
                };
                
                $('#seat-selection-modal').hide();
                this.proceedToCart();
            });

            $(document).on('click', '.link-buttons .btn-dark', function() {
                const action = $(this).data('action');
                Cinema.showNotification(`${action.toUpperCase()} member linking is not available in demo mode.`, 'info');
            });

            $(document).on('click', '#seat-selection-modal .modal-close', function() {
                $('#seat-selection-modal').hide();
            });
        },

        proceedToCart: function() {
            const $continueBtn = $('#continue-to-cart');
            const originalText = $continueBtn.html();
            
            $continueBtn.prop('disabled', true).html('<div class="btn-spinner"></div> Processing...');
            
            const saveData = {
                showtime_id: this.showtimeId,
                seats: this.selectedSeats.map(seat => seat.number).join(','),
                booking_data: JSON.stringify(this.bookingData)
            };
            
                    Cinema.saveBookingData(saveData)
                .then(() => {
                    localStorage.removeItem('cinema_temp_booking');
                    const baseSite = (typeof cinema_ajax !== 'undefined' && cinema_ajax.site_url) ? cinema_ajax.site_url.replace(/\/$/, '') : `${location.protocol}//${location.host}`;
                    window.location.href = baseSite + '/cart/';
                })
                .catch((error) => {
                    console.error('Save failed:', error);
                    localStorage.setItem('cinema_booking_backup', JSON.stringify(saveData));
                    const baseSite = (typeof cinema_ajax !== 'undefined' && cinema_ajax.site_url) ? cinema_ajax.site_url.replace(/\/$/, '') : `${location.protocol}//${location.host}`;
                    window.location.href = baseSite + '/cart/';
                })
                .finally(() => {
                    $continueBtn.prop('disabled', false).html(originalText);
                });
        },

        startAutoSave: function() {
            setInterval(() => {
                this.autoSaveSeatSelection();
            }, 30000);
        },

        autoSaveSeatSelection: function() {
            if (this.selectedSeats.length > 0) {
                const tempData = {
                    showtime_id: this.showtimeId,
                    seats: this.selectedSeats,
                    timestamp: Date.now()
                };
                localStorage.setItem('cinema_temp_booking', JSON.stringify(tempData));
            }
        },

        loadSavedSeatSelection: function() {
            const savedBooking = localStorage.getItem('cinema_temp_booking');
            if (savedBooking) {
                try {
                    const bookingData = JSON.parse(savedBooking);
                    
                    if (bookingData.showtime_id == this.showtimeId && 
                        (Date.now() - bookingData.timestamp) < 1800000) {
                        
                        this.selectedSeats = bookingData.seats || [];
                        this.selectedSeats.forEach(seat => {
                            $(`.seat[data-seat="${seat.number}"]`).addClass('selected');
                        });
                        this.updateSeatSummary();
                        
                        if (this.selectedSeats.length > 0) {
                            Cinema.showNotification(`${this.selectedSeats.length} seat(s) restored from previous session.`, 'info');
                        }
                    }
                } catch (e) {
                    console.error('Error loading saved booking:', e);
                }
            }
        },

        preventAccidentalLeave: function() {
            $(window).on('beforeunload', () => {
                if (this.selectedSeats.length > 0) {
                    return 'You have selected seats. Are you sure you want to leave?';
                }
            });
        },

        destroy: function() {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
            }
            this.releaseSeatLock(this.selectedSeats.map(s => s.number));
        }
    };

    $(document).ready(function() {
        SeatSelection.init();
    });

    // Clean up when leaving page
    $(window).on('unload', function() {
        SeatSelection.destroy();
    });

})(jQuery);