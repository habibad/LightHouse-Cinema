/**
 * Event Page JavaScript
 * Save as: /js/cinema-events.js
 */

(function($) {
    'use strict';

    const EventsPage = {
        init: function() {
            if (!$('.events-grid').length && !$('.event-showtimes').length) return;

            this.initEventFilters();
            this.initEventSearch();
            this.initShowtimeFilters();

            console.log('📅 Events page initialized');
        },

        // Event Filtering
        initEventFilters: function() {
            $('.filter-tab').on('click', function() {
                const filter = $(this).data('filter');
                
                $('.filter-tab').removeClass('active');
                $(this).addClass('active');
                
                EventsPage.filterEvents(filter);
            });
        },

        filterEvents: function(filter) {
            const $events = $('.event-card');
            
            $events.hide().removeClass('filtered-visible');
            
            switch(filter) {
                case 'special-screening':
                    $events.filter('.special_screening').show().addClass('filtered-visible');
                    break;
                case 'events':
                    $events.filter('.event').show().addClass('filtered-visible');
                    break;
                case 'all':
                default:
                    $events.show().addClass('filtered-visible');
                    break;
            }

            // Animate visible items
            $('.filtered-visible').each(function(index) {
                $(this).css({
                    opacity: 0,
                    transform: 'translateY(20px)'
                }).delay(index * 50).animate({
                    opacity: 1
                }, 300, function() {
                    $(this).css('transform', 'translateY(0)');
                });
            });
        },

        // Event Search
        initEventSearch: function() {
            if (!$('#event-search').length && $('.events-filter').length) {
                const searchHTML = `
                    <div class="movie-search-container">
                        <input type="text" id="event-search" placeholder="Search events..." class="movie-search-input">
                        <svg class="search-icon" width="20" height="20" fill="#666" viewBox="0 0 24 24">
                            <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                        </svg>
                    </div>
                `;
                $('.events-filter').append(searchHTML);
            }

            $(document).on('input', '#event-search', function() {
                const searchTerm = $(this).val().toLowerCase();
                const $eventCards = $('.event-card');
                
                if (searchTerm.length === 0) {
                    $eventCards.show();
                    return;
                }
                
                $eventCards.each(function() {
                    const eventTitle = $(this).find('.event-title').text().toLowerCase();
                    const eventCategory = $(this).find('.event-category').text().toLowerCase();
                    const eventVenue = $(this).find('.event-venue').text().toLowerCase();
                    
                    if (eventTitle.includes(searchTerm) || 
                        eventCategory.includes(searchTerm) || 
                        eventVenue.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        },

        // Showtime Filtering (for single event page)
        initShowtimeFilters: function() {
            $('.filter-btn').on('click', function() {
                const dateFilter = $(this).data('date');
                
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');
                
                EventsPage.filterShowtimes(dateFilter);
            });
        },

        filterShowtimes: function(filter) {
            const $showtimes = $('.showtime-date');
            
            $showtimes.hide();
            
            switch(filter) {
                case 'today':
                    $showtimes.filter('.today').show();
                    break;
                case 'tomorrow':
                    $showtimes.filter('.tomorrow').show();
                    break;
                case 'all':
                default:
                    $showtimes.show();
                    break;
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        EventsPage.init();
    });

})(jQuery);