/**
 * FIXED SEAT SELECTION JAVASCRIPT
 * Handles proper seat locking, unlocking, and permanent booking
 * Save as: /js/cinema-multi-screen.js
 */

(function($) {
    'use strict';

    const MultiScreenCinema = {
        selectedSeats: [],
        // lock duration in minutes (must match server-side expiry)
        lockDurationMinutes: 2,
    autoSaveTimer: null,
    seatTimers: {},
        currentZoom: 1,
        showtimeId: null,
        screenNumber: null,
        seatLayout: null,
        bookingData: {},
        updateInterval: null,
        lockExtendInterval: null,
        sessionId: null,

        init: function() {
            if (!$('#seat-map').length) return;

            this.showtimeId = $('#seat-map').data('showtime-id');
            this.loadSeatLayout();
            this.initEventHandlers();
            this.startRealTimeUpdates();
            this.startLockExtension();

            // Persist selection on page unload using sendBeacon (so reload keeps selection)
            window.addEventListener('unload', () => {
                if (this.selectedSeats.length > 0) {
                    // Try to save the cart server-side so reloading restores selection
                    // Include ticket_type when available so restored seats remember their ticket selection
                    const cartItems = this.selectedSeats.map(s => ({ seat: s.number, price: s.price, type: s.type, ticket_type: s.ticket_type || 'adult' }));
                    const payload = new FormData();
                    payload.append('action', 'cinema_save_cart');
                    payload.append('nonce', cinema_ajax.nonce);
                    payload.append('showtime_id', this.showtimeId);
                    payload.append('cart_items', JSON.stringify(cartItems));

                    if (navigator.sendBeacon) {
                        navigator.sendBeacon(cinema_ajax.ajax_url, payload);
                    } else {
                        // Fallback (synchronous XHR) - not ideal but better than nothing
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', cinema_ajax.ajax_url, false);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        const params = `action=cinema_save_cart&nonce=${encodeURIComponent(cinema_ajax.nonce)}&showtime_id=${encodeURIComponent(this.showtimeId)}&cart_items=${encodeURIComponent(JSON.stringify(cartItems))}`;
                        try { xhr.send(params); } catch (e) { /* ignore */ }
                    }
                }
            });

            console.log('Multi-screen cinema with fixed locking initialized');
        },

        loadSeatLayout: function() {
            const $seatMap = $('#seat-map');
            $seatMap.html('<div class="loading-seats">Loading seats...</div>');

            $.ajax({
                url: cinema_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'cinema_get_seat_map',
                    nonce: cinema_ajax.nonce,
                    showtime_id: this.showtimeId
                },
                success: (response) => {
                    if (response.success) {
                        this.screenNumber = response.data.screen_number;
                        this.seatLayout = response.data;
                        this.sessionId = response.data.session_id;
                        this.renderSeatMap(response.data);

                        // If there's a saved cart for this showtime, try to re-select those seats
                        if (response.data.saved_cart && Array.isArray(response.data.saved_cart) && response.data.saved_cart.length > 0) {
                            const seatsToRestore = Array.from(new Set(response.data.saved_cart.map(i => i.seat)));
                            // Attempt to lock and select each seat sequentially
                            (async () => {
                                for (const seatNumber of seatsToRestore) {
                                    // Only try to re-lock if it's currently available
                                    const $seatEl = $(`.seat[data-seat="${seatNumber}"]`);
                                    if ($seatEl.length && $seatEl.hasClass('available') && !$seatEl.hasClass('selected')) {
                                        // Try to lock via AJAX (synchronous sequential attempts)
                                        await new Promise((resolve) => {
                                            $.ajax({
                                                url: cinema_ajax.ajax_url,
                                                method: 'POST',
                                                data: {
                                                    action: 'cinema_lock_seats',
                                                    nonce: cinema_ajax.nonce,
                                                    showtime_id: this.showtimeId,
                                                    seat_numbers: [seatNumber]
                                                },
                                                success: (resp) => {
                                                    if (resp.success) {
                                                        // Mark selected and add to client state
                                                        this.selectSeat($seatEl, seatNumber, $seatEl.data('type'));
                                                        // If saved cart provided more details, restore ticket_type and price
                                                        const savedItem = response.data.saved_cart.find(i => i.seat === seatNumber);
                                                        if (savedItem) {
                                                            const sel = this.selectedSeats.find(s => s.number === seatNumber);
                                                            if (sel) {
                                                                sel.ticket_type = savedItem.ticket_type || sel.ticket_type;
                                                                sel.price = typeof savedItem.price !== 'undefined' ? parseFloat(savedItem.price) : sel.price;
                                                            }
                                                        }
                                                        // Start per-seat timer so it will auto-save/deselect after expiry
                                                        this.startSeatTimer(seatNumber);
                                                    }
                                                    resolve();
                                                },
                                                error: () => resolve()
                                            });
                                        });
                                    }
                                }

                                // Update UI after restoration
                                this.updateCartUI();
                                this.resetAutoSaveTimer();
                            })();
                        }
                        
                        $('.screen-info h3').text(`SCREEN ${this.screenNumber}`);
                        
                        // Show availability info
                        const totalSeats = response.data.total_seats;
                        const bookedCount = response.data.booked_seats.length;
                        const lockedCount = response.data.locked_seats.length;
                        const available = totalSeats - bookedCount - lockedCount;
                        
                        this.showNotification(
                            `${available} of ${totalSeats} seats available`, 
                            'info'
                        );
                    } else {
                        $seatMap.html('<div class="error-message">Failed to load seats</div>');
                    }
                },
                error: () => {
                    $seatMap.html('<div class="error-message">Failed to load seats</div>');
                }
            });
        },

        renderSeatMap: function(data) {
            const $seatMap = $('#seat-map');
            const config = data.seat_configuration;
            const bookedSeats = data.booked_seats || []; // PERMANENTLY BOOKED
            const lockedSeats = data.locked_seats || []; // TEMPORARILY LOCKED
            
            let html = '';
            
            if (config.rows) {
                Object.keys(config.rows).forEach(rowLetter => {
                    const rowData = config.rows[rowLetter];
                    html += this.renderSeatRow(rowLetter, rowData, bookedSeats, lockedSeats, config.aisles);
                });
            }
            
            $seatMap.html(html);
            // Ensure no lingering title/tooltips on available seats from other code paths
            $seatMap.find('.seat.available').removeAttr('title');
        },

        renderSeatRow: function(rowLetter, rowData, bookedSeats, lockedSeats, aisles) {
            let html = '<div class="seat-row">';
            html += `<span class="row-label">${rowLetter}</span>`;
            
            const seats = rowData.seats;
            const types = rowData.types || {};
            const verticalAisles = aisles?.vertical || [];
            
            seats.forEach((seat, index) => {
                if (verticalAisles.includes(index)) {
                    html += '<span class="seat-gap"></span>';
                }
                
                // Wheelchair seats
                if (typeof seat === 'string' && seat.startsWith('W')) {
                    const seatNumber = `${rowLetter}-${seat}`;
                    const isBooked = bookedSeats.includes(seatNumber);
                    const isLocked = lockedSeats.includes(seatNumber);
                    
                    let seatClass = 'seat wheelchair';
                    let seatTitle = '';
                    
                    if (isBooked) {
                        seatClass += ' reserved permanently-booked';
                        seatTitle = 'Already Booked';
                    } else if (isLocked) {
                        seatClass += ' unavailable temporarily-locked';
                        seatTitle = 'Selected by another user';
                    } else {
                        seatClass += ' available';
                        // Do not set a tooltip for available seats to avoid repeated text
                        seatTitle = '';
                    }
                    
                    html += `<div class="${seatClass}" 
                                  data-seat="${seatNumber}" 
                                  data-row="${rowLetter}" 
                                  data-type="wheelchair"
                                  title="${seatTitle}">
                        <span class="wheelchair-icon">♿</span>
                    </div>`;
                } else {
                    // Regular seats
                    const seatNumber = `${rowLetter}${seat}`;
                    const seatType = types[seat] || rowData.type || 'regular';
                    const isBooked = bookedSeats.includes(seatNumber);
                    const isLocked = lockedSeats.includes(seatNumber);
                    
                    let seatClass = 'seat';
                    let seatTitle = '';
                    
                    if (isBooked) {
                        seatClass += ' reserved permanently-booked';
                        seatTitle = 'Already Booked';
                    } else if (isLocked) {
                        seatClass += ' unavailable temporarily-locked';
                        seatTitle = 'Selected by another user';
                    } else {
                        seatClass += ' available';
                        // Avoid showing the generic 'Available - Click to select' tooltip repeatedly
                        seatTitle = '';
                    }
                    
                    if (seatType === 'wheelchair') {
                        seatClass += ' wheelchair';
                    }
                    
                    html += `<div class="${seatClass}" 
                                  data-seat="${seatNumber}" 
                                  data-row="${rowLetter}" 
                                  data-type="${seatType}"
                                  title="${seatTitle}">
                        ${seat}
                    </div>`;
                }
            });
            
            html += '</div>';
            return html;
        },

        initEventHandlers: function() {
            // Seat selection - per-seat modal + per-seat timer
            $(document).on('click', '.seat.available', (e) => {
                const $seat = $(e.currentTarget);
                const seatNumber = $seat.data('seat');
                const seatType = $seat.data('type');

                if ($seat.hasClass('selected')) {
                    // DESELECT - Immediate unlock
                    this.deselectSeat($seat, seatNumber);
                    this.releaseSeatLocks([seatNumber]);
                    this.clearSeatTimer(seatNumber);
                    this.resetAutoSaveTimer();
                    this.updateCartUI();
                    return;
                }

                if (this.selectedSeats.length >= 8) {
                    this.showNotification('Maximum 8 seats can be selected at once.', 'warning');
                    return;
                }

                // Lock first to avoid race, then show the ticket selection modal for this seat.
                // The seat will only be added to the cart after the user picks Adult/Child/Senior.
                this.lockSeat(seatNumber, () => {
                    this.showTicketSelectionForSeat($seat, seatNumber, seatType);
                });
            });

            // Continue to cart - save current cart and redirect to cart page (no modal)
            $('#select-seats-btn-mobile, #next-cart-btn').on('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (this.selectedSeats.length === 0) return;

                // Prepare cart items in expected shape
                const cartItems = this.selectedSeats.map(s => ({ seat: s.number, price: s.price, type: s.type }));

                $.ajax({
                    url: cinema_ajax.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'cinema_save_cart',
                        nonce: cinema_ajax.nonce,
                        showtime_id: this.showtimeId,
                        cart_items: JSON.stringify(cartItems)
                    },
                    success: (response) => {
                        if (response.success) {
                            // Redirect to cart page using localized site URL (supports WP in subdirectory)
                            const baseSite = (typeof cinema_ajax !== 'undefined' && cinema_ajax.site_url) ? cinema_ajax.site_url.replace(/\/$/, '') : `${location.protocol}//${location.host}`;
                            window.location.href = baseSite + '/cart/?showtime=' + encodeURIComponent(this.showtimeId);
                        } else {
                            this.showNotification(response.data?.message || 'Failed to save cart', 'error');
                        }
                    },
                    error: () => this.showNotification('Failed to save cart', 'error')
                });
            });

            // Modal handlers
            $('.modal-close').on('click', function() {
                $(this).closest('.modal').hide();
            });

            // Zoom controls
            $('.zoom-in').on('click', () => this.zoomSeatMap(1.2));
            $('.zoom-out').on('click', () => this.zoomSeatMap(0.8));
            $('.fullscreen').on('click', () => this.toggleFullscreen());

            // Mobile cart
            $('#mobile-view-cart').on('click', () => {
                $('#mobile-cart-drawer').addClass('active');
            });

            $('.close-drawer').on('click', () => {
                $('#mobile-cart-drawer').removeClass('active');
            });

            // Showtime change
            $('.time-slot:not(.active)').on('click', (e) => {
                const newShowtimeId = parseInt($(e.currentTarget).data('showtime-id'));
                
                if (this.selectedSeats.length > 0) {
                    this.showShowtimeChangeModal(newShowtimeId);
                } else {
                    this.changeShowtime(newShowtimeId);
                }
            });
        },

        selectSeat: function($seat, seatNumber, seatType) {
            $seat.addClass('selected seat-animation');
            
            const ticketPrice = parseFloat($('#seat-map').data('ticket-price')) || 19.00;
            // Avoid duplicates
            if (!this.selectedSeats.find(s => s.number === seatNumber)) {
                this.selectedSeats.push({
                    number: seatNumber,
                    type: seatType,
                    price: ticketPrice
                });
            }
            
            setTimeout(() => $seat.removeClass('seat-animation'), 300);
        },

        deselectSeat: function($seat, seatNumber) {
            $seat.removeClass('selected');
            this.selectedSeats = this.selectedSeats.filter(seat => seat.number !== seatNumber);
            // Remove from server session cart if present
            $.ajax({
                url: cinema_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'cinema_remove_cart_item',
                    nonce: cinema_ajax.nonce,
                    seat: seatNumber,
                    showtime_id: this.showtimeId
                }
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
                        this.showNotification(`Seat ${seatNumber} locked for ${this.lockDurationMinutes} minutes`, 'success');
                    } else {
                        this.showNotification(response.data.message || 'Seat is not available', 'error');
                        
                        // Refresh seat map to show current status
                        this.loadSeatLayout();
                    }
                },
                error: () => {
                    this.showNotification('Failed to lock seat. Please try again.', 'error');
                }
            });
        },

        releaseSeatLocks: function(seatNumbers) {
            $.ajax({
                url: cinema_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'cinema_release_seat_locks',
                    nonce: cinema_ajax.nonce,
                    showtime_id: this.showtimeId,
                    seat_numbers: seatNumbers
                },
                success: (response) => {
                    if (response.success && seatNumbers.length > 0) {
                        this.showNotification('Seats unlocked', 'info');
                    }
                }
            });
        },

        updateCartUI: function() {
            const seatCount = this.selectedSeats.length;
            const seatNumbers = this.selectedSeats.map(s => s.number).join(', ');
            const total = this.selectedSeats.reduce((sum, seat) => sum + seat.price, 0);
            
            const $cartHeader = $('.cart-header h3');
            const $cartItems = $('#cart-items');
            const $mobileCartContent = $('#mobile-cart-content');
            const $selectBtnMobile = $('#select-seats-btn-mobile');
            const $nextBtn = $('#next-cart-btn');
            const $cartSummary = $('#cart-summary');
            
            if (seatCount === 0) {
                $cartHeader.text('Your Cart is Empty');
                $cartItems.html('');
                $mobileCartContent.html('<p class="empty-cart-message">Your cart is empty</p>');
                $selectBtnMobile.prop('disabled', true).removeClass('active').text('SELECT A SEAT TO CONTINUE');
                $nextBtn.hide();
                $cartSummary.hide();
                return;
            }
            
            $cartHeader.text('TICKETS');
            
            let html = '';
            this.selectedSeats.forEach(seat => {
                const wheelchairLabel = seat.type === 'wheelchair' ? ' (Wheelchair)' : '';
                const ticketLabel = seat.ticket_type ? ` - ${seat.ticket_type.charAt(0).toUpperCase() + seat.ticket_type.slice(1)}` : '';
                html += `
                    <div class="cart-item">
                        <div class="cart-item-header">
                            <span class="ticket-type">Seat ${seat.number}${wheelchairLabel}${ticketLabel}</span>
                            <button class="ticket-remove" data-seat="${seat.number}">🗑</button>
                        </div>
                        <div class="cart-item-details">
                            <div>Screen ${this.screenNumber} • Locked for ${this.lockDurationMinutes} min</div>
                            <div>${seat.price.toFixed(2)}</div>
                        </div>
                    </div>
                `;
            });
            
            $cartItems.html(html);
            $mobileCartContent.html(html);
            $('.ticket-count').text(`${seatCount} ticket${seatCount > 1 ? 's' : ''}`);
            $('.cart-total').text(`${total.toFixed(2)}`);
            
            $selectBtnMobile.prop('disabled', false).addClass('active').text(`NEXT: CART (${seatCount})`);
            $nextBtn.show();
            $cartSummary.show();
            
            // Handle remove
            $('.ticket-remove').off('click').on('click', (e) => {
                const seatNumber = $(e.currentTarget).data('seat');
                this.deselectSeat($(`.seat[data-seat="${seatNumber}"]`), seatNumber);
                this.releaseSeatLocks([seatNumber]);
                this.updateCartUI();
            });
        },

        // batch ticket modal removed; use showTicketSelectionForSeat for per-seat flow

        // Show ticket options for a single seat before adding to cart
        showTicketSelectionForSeat: function($seat, seatNumber, seatType) {
            const ticketPrice = parseFloat($('#seat-map').data('ticket-price')) || 19.00;
            const bookingFee = 2.25;
            const totalPerTicket = ticketPrice + bookingFee;
            const wheelchairLabel = seatType === 'wheelchair' ? 'Wheelchair seat' : 'Regular seat';

            const modalHTML = `
                <div id="ticket-modal-single" class="modal" style="display: flex;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>SELECT TICKET FOR ${seatNumber}</h3>
                            <button class="modal-close">✕</button>
                        </div>
                        <div class="modal-body">
                            <p class="seat-type-info">${wheelchairLabel}</p>
                            <p class="booking-fee-notice">A ${bookingFee.toFixed(2)} booking fee per ticket is included. Seat will be locked for ${this.lockDurationMinutes} minutes.</p>

                            <div class="ticket-types">
                                <div class="ticket-type-item">
                                    <div class="ticket-type-info">
                                        <h4>Adult</h4>
                                        <p>Standard ticket</p>
                                    </div>
                                    <div class="ticket-type-price">
                                        <div>
                                            <div class="price">${totalPerTicket.toFixed(2)}</div>
                                            <div class="price-breakdown">(${ticketPrice.toFixed(2)} + ${bookingFee.toFixed(2)} Fee)</div>
                                        </div>
                                        <button class="btn-add-ticket-single" data-type="adult" data-price="${totalPerTicket}">ADD</button>
                                    </div>
                                </div>

                                <div class="ticket-type-item">
                                    <div class="ticket-type-info">
                                        <h4>Child (12 & Under)</h4>
                                        <p>Discounted ticket</p>
                                    </div>
                                    <div class="ticket-type-price">
                                        <div>
                                            <div class="price">${(totalPerTicket - 2).toFixed(2)}</div>
                                            <div class="price-breakdown">(${(ticketPrice - 2).toFixed(2)} + ${bookingFee.toFixed(2)} Fee)</div>
                                        </div>
                                        <button class="btn-add-ticket-single" data-type="child" data-price="${(totalPerTicket - 2)}">ADD</button>
                                    </div>
                                </div>

                                <div class="ticket-type-item">
                                    <div class="ticket-type-info">
                                        <h4>Senior (60+)</h4>
                                        <p>Senior discount</p>
                                    </div>
                                    <div class="ticket-type-price">
                                        <div>
                                            <div class="price">${(totalPerTicket - 2).toFixed(2)}</div>
                                            <div class="price-breakdown">(${(ticketPrice - 2).toFixed(2)} + ${bookingFee.toFixed(2)} Fee)</div>
                                        </div>
                                        <button class="btn-add-ticket-single" data-type="senior" data-price="${(totalPerTicket - 2)}">ADD</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('#ticket-modal-single').remove();
            $('body').append(modalHTML);

            // Close handler: if modal closed without selecting, release the lock and remove modal
            const closeAndRelease = () => {
                $('#ticket-modal-single').hide().remove();
                // Release lock and remove visual selection if we didn't add it
                this.releaseSeatLocks([seatNumber]);
            };

            $('#ticket-modal-single .modal-close').on('click', closeAndRelease);
            // Clicking on backdrop (modal container) but not modal-content should cancel
            $('#ticket-modal-single').on('click', function(ev) {
                if (ev.target === this) {
                    closeAndRelease();
                }
            });

            // When user adds ticket for this seat
            $('.btn-add-ticket-single').on('click', (e) => {
                const ticketType = $(e.currentTarget).data('type');
                const price = parseFloat($(e.currentTarget).data('price'));

                // Add the seat locally with the chosen ticket type and price
                this.selectSeat($seat, seatNumber, seatType);
                // attach ticket type and price to the selected seat object
                const sel = this.selectedSeats.find(s => s.number === seatNumber);
                if (sel) {
                    sel.ticket_type = ticketType;
                    sel.price = price;
                }

                // Start per-seat timer
                this.startSeatTimer(seatNumber);
                this.resetAutoSaveTimer();
                this.updateCartUI();

                // Close modal
                $('#ticket-modal-single').hide().remove();
            });
        },

        

        // Start a per-seat timeout which deselects the seat after lockDurationMinutes
        startSeatTimer: function(seatNumber) {
            this.clearSeatTimer(seatNumber);
            const ms = this.lockDurationMinutes * 60 * 1000;
            this.seatTimers[seatNumber] = setTimeout(() => {
                this.seatTimeoutHandler(seatNumber);
            }, ms);
        },

        clearSeatTimer: function(seatNumber) {
            if (this.seatTimers[seatNumber]) {
                clearTimeout(this.seatTimers[seatNumber]);
                delete this.seatTimers[seatNumber];
            }
        },

        seatTimeoutHandler: function(seatNumber) {
            // On timeout, ensure seat is removed from session cart, release lock, and deselect in UI
            $.ajax({
                url: cinema_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'cinema_remove_cart_item',
                    nonce: cinema_ajax.nonce,
                    seat: seatNumber,
                    showtime_id: this.showtimeId
                },
                complete: () => {
                    // Release lock and deselect locally
                    this.releaseSeatLocks([seatNumber]);
                    this.deselectSeat($(`.seat[data-seat="${seatNumber}"]`), seatNumber);
                    this.clearSeatTimer(seatNumber);
                    this.updateCartUI();
                }
            });
        },

        proceedToCart: function(ticketType, price) {
            const cartData = {
                showtime_id: this.showtimeId,
                screen_number: this.screenNumber,
                seats: this.selectedSeats.map(s => ({
                    ...s,
                    ticket_type: ticketType,
                    price: price
                }))
            };
            
            $.ajax({
                url: cinema_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'cinema_save_cart',
                    nonce: cinema_ajax.nonce,
                    showtime_id: this.showtimeId,
                    cart_items: JSON.stringify(cartData.seats)
                },
                success: () => {
                    // Stop lock extension since we're leaving
                    this.stopLockExtension();
                    const baseSite = (typeof cinema_ajax !== 'undefined' && cinema_ajax.site_url) ? cinema_ajax.site_url.replace(/\/$/, '') : `${location.protocol}//${location.host}`;
                    window.location.href = baseSite + '/cart/?showtime=' + encodeURIComponent(this.showtimeId);
                },
                error: () => {
                    this.showNotification('Failed to save cart. Please try again.', 'error');
                }
            });
        },

        showShowtimeChangeModal: function(newShowtimeId) {
            const modalHTML = `
                <div id="showtime-change-modal" class="modal" style="display: flex;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Change Showtime?</h3>
                            <button class="modal-close">✕</button>
                        </div>
                        <div class="modal-body">
                            <p>You have ${this.selectedSeats.length} seat(s) locked. Changing showtimes will release your current selection.</p>
                            <div class="modal-actions">
                                <button class="btn btn-secondary modal-close">CANCEL</button>
                                <button class="btn btn-primary" id="confirm-showtime-change">CHANGE SHOWTIME</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('#showtime-change-modal').remove();
            $('body').append(modalHTML);
            
            $('#confirm-showtime-change').on('click', () => {
                // Release locks before changing
                this.releaseSeatLocks(this.selectedSeats.map(s => s.number));
                this.selectedSeats = [];
                $('.seat.selected').removeClass('selected');
                this.updateCartUI();
                this.changeShowtime(newShowtimeId);
                $('#showtime-change-modal').hide();
            });
        },

        changeShowtime: function(showtimeId) {
            this.showtimeId = showtimeId;
            $('.time-slot').removeClass('active');
            $(`.time-slot[data-showtime-id="${showtimeId}"]`).addClass('active');
            this.loadSeatLayout();
            
            const newUrl = window.location.pathname + '?showtime=' + showtimeId;
            window.history.pushState({}, '', newUrl);
        },

        startRealTimeUpdates: function() {
            this.updateSeatAvailability();
            
            // Check every 5 seconds
            this.updateInterval = setInterval(() => {
                this.updateSeatAvailability();
            }, 5000);
        },

        // Auto-save behavior: if user does not proceed to payment within 2 minutes,
        // save the current selection to the session/cart and clear the selection on the UI.
        resetAutoSaveTimer: function() {
            // Clear any existing timer
            if (this.autoSaveTimer) {
                clearTimeout(this.autoSaveTimer);
            }
            // Only start timer if there are selected seats
            if (this.selectedSeats.length > 0) {
                this.autoSaveTimer = setTimeout(() => {
                    this.autoSaveCart();
                }, 2 * 60 * 1000); // 2 minutes
            }
        },

        autoSaveCart: function() {
            // Instead of saving automatically, trigger per-seat timeout handler which removes seats from session
            if (this.selectedSeats.length === 0) return;
            const seatNumbers = this.selectedSeats.map(s => s.number);
            seatNumbers.forEach(sn => this.seatTimeoutHandler(sn));
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
                        this.markUnavailableSeats(
                            response.data.permanently_booked,
                            response.data.temporarily_locked
                        );
                    }
                }
            });
        },

        markUnavailableSeats: function(permanentlyBooked, temporarilyLocked) {
            $('.seat').each(function() {
                const $seat = $(this);
                const seatNumber = $seat.data('seat');
                
                // Skip seats selected by current user
                if ($seat.hasClass('selected')) return;
                
                if (permanentlyBooked.includes(seatNumber)) {
                    // PERMANENTLY BOOKED - cannot be selected
                    $seat.removeClass('available temporarily-locked')
                         .addClass('reserved permanently-booked')
                         .attr('title', 'Already Booked');
                } else if (temporarilyLocked.includes(seatNumber)) {
                    // TEMPORARILY LOCKED - by another user
                    $seat.removeClass('available permanently-booked reserved')
                         .addClass('unavailable temporarily-locked')
                         .attr('title', 'Selected by another user');
                } else {
                    // AVAILABLE
                    $seat.removeClass('reserved permanently-booked unavailable temporarily-locked')
                         .addClass('available')
                         .attr('title', '');
                }
            });
        },

        startLockExtension: function() {
            // Extend lock periodically before expiry (80% of lock duration)
            const intervalMs = Math.max(30000, Math.floor(this.lockDurationMinutes * 0.8 * 60 * 1000));
            this.lockExtendInterval = setInterval(() => {
                if (this.selectedSeats.length > 0) {
                    $.ajax({
                        url: cinema_ajax.ajax_url,
                        method: 'POST',
                        data: {
                            action: 'cinema_extend_seat_lock',
                            nonce: cinema_ajax.nonce,
                            showtime_id: this.showtimeId
                        },
                        success: (response) => {
                            if (response.success) {
                                console.log('Seat locks extended');
                                // Refresh client-side per-seat timers so they reflect the new expiry
                                this.selectedSeats.forEach(s => this.startSeatTimer(s.number));
                            }
                        }
                    });
                }
            }, intervalMs);
        },

        stopLockExtension: function() {
            if (this.lockExtendInterval) {
                clearInterval(this.lockExtendInterval);
            }
        },

        zoomSeatMap: function(factor) {
            this.currentZoom = Math.max(0.5, Math.min(2.5, this.currentZoom * factor));
            
            $('#seat-map').css({
                'transform': `scale(${this.currentZoom})`,
                'transform-origin': 'top center'
            });
            
            $('.zoom-out').prop('disabled', this.currentZoom <= 0.5).css('opacity', this.currentZoom <= 0.5 ? 0.5 : 1);
            $('.zoom-in').prop('disabled', this.currentZoom >= 2.5).css('opacity', this.currentZoom >= 2.5 ? 0.5 : 1);
        },

        toggleFullscreen: function() {
            const elem = $('.seat-map-container')[0];
            
            if (!document.fullscreenElement) {
                if (elem.requestFullscreen) {
                    elem.requestFullscreen();
                } else if (elem.webkitRequestFullscreen) {
                    elem.webkitRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        },

        showNotification: function(message, type = 'info') {
            const icons = {
                success: '✓',
                error: '✕',
                warning: '⚠',
                info: 'ℹ'
            };
            
            const notification = `
                <div class="notification ${type}">
                    <span class="notification-icon">${icons[type]}</span>
                    <span class="notification-message">${message}</span>
                </div>
            `;
            
            if (!$('.notifications-container').length) {
                $('body').append('<div class="notifications-container"></div>');
            }
            
            const $notification = $(notification);
            $('.notifications-container').append($notification);
            
            setTimeout(() => $notification.addClass('notification-show'), 10);
            setTimeout(() => {
                $notification.removeClass('notification-show');
                setTimeout(() => $notification.remove(), 300);
            }, 3000);
        },

        destroy: function() {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
            }
            if (this.lockExtendInterval) {
                clearInterval(this.lockExtendInterval);
            }
            // Release all locks
            if (this.selectedSeats.length > 0) {
                this.releaseSeatLocks(this.selectedSeats.map(s => s.number));
            }
        }
    };

    // Initialize
    $(document).ready(function() {
        MultiScreenCinema.init();
    });

    // Cleanup on unload
    $(window).on('unload', function() {
        MultiScreenCinema.destroy();
    });

})(jQuery);