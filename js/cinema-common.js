/**
 * Common utilities and shared functions
 * Used across all cinema booking pages
 */

(function($) {
    'use strict';

    // Create global cinema namespace
    window.Cinema = window.Cinema || {};

    // ===== NOTIFICATION SYSTEM =====
    Cinema.showNotification = function(message, type = 'info') {
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
        
        setTimeout(() => {
            $notification.removeClass('notification-show').addClass('notification-hide');
            setTimeout(() => $notification.remove(), 300);
        }, 5000);
        
        $notification.find('.notification-close').on('click', function() {
            $notification.removeClass('notification-show').addClass('notification-hide');
            setTimeout(() => $notification.remove(), 300);
        });
    };

    // ===== MODAL SYSTEM =====
    Cinema.initModalSystem = function() {
        $(document).on('click', '.modal-close', function() {
            $(this).closest('.modal').hide();
        });

        $(document).on('click', '.modal', function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.modal:visible').hide();
            }
        });

        $(document).on('hide', '.modal', function() {
            const modalId = $(this).attr('id');
            if (modalId !== 'seat-selection-modal') {
                setTimeout(() => {
                    $(this).remove();
                }, 300);
            }
        });
    };

    // ===== AJAX UTILITIES =====
    Cinema.saveBookingData = function(data) {
        return new Promise((resolve, reject) => {
            if (typeof cinema_ajax === 'undefined') {
                console.warn('cinema_ajax not available, using localStorage backup');
                localStorage.setItem('cinema_booking_data', JSON.stringify(data));
                resolve({ success: true });
                return;
            }

            $.ajax({
                url: cinema_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'cinema_seat_selection',
                    nonce: cinema_ajax.nonce,
                    ...data
                },
                timeout: 10000,
                success: function(response) {
                    if (response && response.success) {
                        resolve(response.data);
                    } else {
                        reject(response ? response.data : 'Unknown error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    reject(error);
                }
            });
        });
    };

    Cinema.clearBookingData = function() {
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
    };

    // ===== TOOLTIP SYSTEM =====
    Cinema.initTooltips = function() {
        $(document).on('mouseenter', '[title]', function() {
            const $elem = $(this);
            const title = $elem.attr('title');
            
            if (!title) return;
            
            $elem.removeAttr('title');
            
            const tooltip = $(`<div class="tooltip">${title}</div>`);
            $('body').append(tooltip);
            
            const offset = $elem.offset();
            tooltip.css({
                top: offset.top - tooltip.outerHeight() - 8,
                left: offset.left + ($elem.outerWidth() / 2) - (tooltip.outerWidth() / 2)
            }).fadeIn(200);
            
            $elem.data('tooltip', tooltip).data('original-title', title);
        }).on('mouseleave', function() {
            const $elem = $(this);
            const tooltip = $elem.data('tooltip');
            const originalTitle = $elem.data('original-title');
            
            if (tooltip) {
                tooltip.fadeOut(200, function() {
                    $(this).remove();
                });
            }
            
            if (originalTitle) {
                $elem.attr('title', originalTitle);
            }
        });
    };

    // ===== MOBILE OPTIMIZATIONS =====
    Cinema.handleMobileOptimizations = function() {
        let resizeTimeout;
        $(window).on('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                if ($(window).width() < 768 && $('.seat-map').length) {
                    const currentZoom = Math.min(Cinema.currentZoom || 1, 0.8);
                    $('.seat-map').css('transform', `scale(${currentZoom})`);
                }
            }, 250);
        });

        if ('ontouchstart' in window) {
            $('.seat.available').on('touchstart', function() {
                $(this).addClass('touch-active');
            }).on('touchend', function() {
                $(this).removeClass('touch-active');
            });
        }
    };

    // ===== ACCESSIBILITY =====
    Cinema.initAccessibility = function() {
        $(document).on('keydown', '.seat.available, .showtime-slot', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this).click();
            }
        });
    };

    // ===== GLOBAL ERROR HANDLING =====
    $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
        if (jqXHR.status === 0) return;
        console.error('Global AJAX Error:', thrownError);
    });

    // Initialize common functionality
    $(document).ready(function() {
        Cinema.initModalSystem();
        Cinema.initTooltips();
        Cinema.handleMobileOptimizations();
        Cinema.initAccessibility();

        // Welcome message for first-time visitors
        setTimeout(() => {
            if (!localStorage.getItem('cinema_visited')) {
                Cinema.showNotification('Welcome to Adeebdds! Select your movie and enjoy the show.', 'info');
                localStorage.setItem('cinema_visited', 'true');
            }
        }, 2000);

        console.log('🎬 Cinema common utilities loaded');
    });

})(jQuery);