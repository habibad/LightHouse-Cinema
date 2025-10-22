<?php
/**
 * Template Name: Events Archive
 * Description: Displays all events with filtering
 */
get_header(); 
?>

<div class="cinema-container events-page">
    <div class="events-header">
        <h1 class="events-title">BROWSE UPCOMING EVENTS BELOW</h1>
    </div>

    <div class="events-filter">
        <nav class="filter-tabs">
            <button class="filter-tab active" data-filter="all">ALL</button>
            <button class="filter-tab" data-filter="special-screening">SPECIAL SCREENING</button>
            <button class="filter-tab" data-filter="events">EVENTS</button>
        </nav>
    </div>

    <div class="events-grid">
        <?php 
        $events_query = new WP_Query(array(
            'post_type' => 'events',
            'posts_per_page' => -1,
            'orderby' => 'meta_value',
            'meta_key' => '_event_date',
            'order' => 'ASC'
        ));
        
        if ($events_query->have_posts()) :
            while ($events_query->have_posts()) : $events_query->the_post();
                $event_id = get_the_ID();
                $organizer = get_post_meta($event_id, '_event_organizer', true);
                $venue = get_post_meta($event_id, '_event_venue', true);
                $duration = get_post_meta($event_id, '_event_duration', true);
                $category = get_post_meta($event_id, '_event_category', true);
                $event_date = get_post_meta($event_id, '_event_date', true);
                $event_type = get_post_meta($event_id, '_event_type', true) ?: 'event';
                $banner_image = get_post_meta($event_id, '_event_banner', true);
                $get_tickets_text = get_post_meta($event_id, '_event_tickets_text', true) ?: 'GET TICKETS';
                
                // Check if event is upcoming
                $current_date = date('Y-m-d');
                $is_upcoming = ($event_date >= $current_date);
                $status_class = $is_upcoming ? 'upcoming' : 'past';
                
                // Get banner URL
                $banner_url = '';
                if ($banner_image) {
                    $banner_url = wp_get_attachment_image_url($banner_image, 'large');
                }
        ?>
        <div class="event-card <?php echo $status_class; ?> <?php echo $event_type; ?>" 
             data-event-id="<?php echo $event_id; ?>" 
             data-type="<?php echo $event_type; ?>">
            
            <div class="event-banner">
                <?php if ($event_type === 'special_screening') : ?>
                    <span class="event-badge special-screening">Special Screening</span>
                <?php endif; ?>
                
                <?php if ($banner_url) : ?>
                    <img src="<?php echo esc_url($banner_url); ?>" 
                         alt="<?php the_title_attribute(); ?>" 
                         class="event-banner-image">
                <?php else : ?>
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('large', array('class' => 'event-banner-image')); ?>
                    <?php else : ?>
                        <div class="event-banner-placeholder">
                            <svg width="80" height="80" fill="#ccc" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="event-info">
                <h3 class="event-title"><?php the_title(); ?></h3>
                
                <?php if (has_excerpt()) : ?>
                    <p class="event-description"><?php echo wp_trim_words(get_the_excerpt(), 20); ?></p>
                <?php endif; ?>

                <div class="event-meta">
                    <?php if ($event_date) : ?>
                        <span class="event-date">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                            </svg>
                            <?php echo date('M d, Y', strtotime($event_date)); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($category) : ?>
                        <span class="event-category"><?php echo esc_html($category); ?></span>
                    <?php endif; ?>

                    <?php if ($venue) : ?>
                        <span class="event-venue">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                            <?php echo esc_html($venue); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <a href="<?php echo get_permalink(); ?>" class="btn-get-tickets">
                    <?php echo esc_html($get_tickets_text); ?> ➜
                </a>
            </div>
        </div>
        <?php 
            endwhile;
            wp_reset_postdata();
        else :
        ?>
        <div class="no-events">
            <div class="no-events-content">
                <svg width="80" height="80" fill="#ccc" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                <h3>No Events Found</h3>
                <p>Check back later for upcoming events!</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Events Page Styles */
.events-page {
    padding: 40px 20px;
}

.events-header {
    text-align: center;
    margin-bottom: 20px;
}

.events-title {
    font-size: 32px;
    font-weight: 700;
    color: #333;
    margin-bottom: 10px;
}

.events-filter {
    margin-bottom: 40px;
}

.filter-tabs {
    display: flex;
    justify-content: center;
    gap: 20px;
    /* border-bottom: 2px solid #eee; */
}

.filter-tab {
    padding: 12px 30px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
    color: #666;
}

.filter-tab:hover {
    color: #F9E281;
}

.filter-tab.active {
    color: #F9E281;
    border-bottom-color: #F9E281;
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
}

.event-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.event-banner {
    position: relative;
    width: 100%;
    height: 200px;
    overflow: hidden;
    background: #f5f5f5;
}

.event-banner-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.event-banner-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.event-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background: #0066cc;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    z-index: 2;
}

.event-badge.special-screening {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.event-info {
    padding: 20px;
    position: relative;
}

.event-title {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 10px;
    color: #333;
}

.event-description {
    color: #666;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 15px;
}

.event-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
    font-size: 13px;
    color: #666;
}

.event-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.event-meta svg {
    flex-shrink: 0;
}

.event-category {
    background: #f0f0f0;
    padding: 4px 10px;
    border-radius: 12px;
    font-weight: 600;
}

.btn-get-tickets {
    display: inline-block;
    width: 100%;
    padding: 12px 24px;
    background: #F9E281;
    color: black;
    text-align: center;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
}

.btn-get-tickets:hover {
    background: #F9E281;
    transform: scale(1.02);
}

.no-events {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
}

.no-events-content {
    max-width: 400px;
    margin: 0 auto;
}

.no-events-content h3 {
    font-size: 24px;
    margin: 20px 0 10px;
    color: #333;
}

.no-events-content p {
    color: #666;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .events-page {
        padding: 20px 15px;
    }

    .events-title {
        font-size: 24px;
    }

    .filter-tabs {
        flex-wrap: wrap;
        gap: 10px;
    }

    .filter-tab {
        padding: 10px 20px;
        font-size: 13px;
    }

    .events-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .event-banner {
        height: 180px;
    }

    .event-info {
        padding: 15px;
    }

    .event-title {
        font-size: 18px;
    }

    .event-meta {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
}

/* Tablet Responsive */
@media (min-width: 769px) and (max-width: 1024px) {
    .events-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Animation for filtered items */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.filtered-visible {
    animation: fadeInUp 0.3s ease forwards;
}

/* Event Count */
.events-count {
    text-align: center;
    color: #999;
    font-size: 14px;
    margin-top: 10px;
}

/* Loading Spinner */
.btn-spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Search Container */
.movie-search-container {
    position: relative;
    max-width: 500px;
}

.movie-search-input {
    width: 100%;
    padding: 12px 45px 12px 15px;
    border: 2px solid #ddd;
    border-radius: 25px;
    font-size: 14px;
    transition: all 0.3s;
}

.movie-search-input:focus {
    outline: none;
    border-color: #0066cc;
    box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
}

.search-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
}

/* Past Events Styling */
.event-card.past {
    opacity: 0.6;
}

.event-card.past .event-banner::after {
    content: 'PAST EVENT';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-15deg);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 10px 30px;
    font-size: 18px;
    font-weight: 700;
    border-radius: 8px;
    z-index: 1;
}

/* Upcoming Badge */
.event-card.upcoming .event-info::before {
    content: '';
    position: absolute;
    top: -10px;
    right: 20px;
    width: 0;
    height: 0;
    border-left: 10px solid transparent;
    border-right: 10px solid transparent;
    border-top: 10px solid #28a745;
}
</style>

<script>
jQuery(document).ready(function($) {
    'use strict';

    // Event Filtering
    $('.filter-tab').on('click', function() {
        const filter = $(this).data('filter');
        
        $('.filter-tab').removeClass('active');
        $(this).addClass('active');
        
        filterEvents(filter);
    });

    function filterEvents(filter) {
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

        // Check if no events visible
        if ($('.event-card:visible').length === 0) {
            if (!$('.no-events-filter').length) {
                $('.events-grid').append(`
                    <div class="no-events no-events-filter">
                        <div class="no-events-content">
                            <svg width="80" height="80" fill="#ccc" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                            <h3>No Events Found</h3>
                            <p>No events match this filter.</p>
                        </div>
                    </div>
                `);
            }
        } else {
            $('.no-events-filter').remove();
        }

        // Animate visible items
        $('.filtered-visible').each(function(index) {
            $(this).css({
                'animation-delay': (index * 0.05) + 's'
            });
        });
    }

    // Event Search
    if (!$('#event-search').length && $('.events-filter').length) {
        const searchHTML = `
            <div class="movie-search-container" style="margin-top: 20px;">
                <input type="text" id="event-search" placeholder="Search events by title, category, or venue..." class="movie-search-input">
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
            $('.no-events-filter').remove();
            return;
        }
        
        let visibleCount = 0;
        
        $eventCards.each(function() {
            const eventTitle = $(this).find('.event-title').text().toLowerCase();
            const eventCategory = $(this).find('.event-category').text().toLowerCase();
            const eventVenue = $(this).find('.event-venue').text().toLowerCase();
            const eventDescription = $(this).find('.event-description').text().toLowerCase();
            
            if (eventTitle.includes(searchTerm) || 
                eventCategory.includes(searchTerm) || 
                eventVenue.includes(searchTerm) ||
                eventDescription.includes(searchTerm)) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });

        // Show no results message
        if (visibleCount === 0) {
            if (!$('.no-events-filter').length) {
                $('.events-grid').append(`
                    <div class="no-events no-events-filter">
                        <div class="no-events-content">
                            <svg width="80" height="80" fill="#ccc" viewBox="0 0 24 24">
                                <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                            </svg>
                            <h3>No Events Found</h3>
                            <p>No events match your search for "<strong>${searchTerm}</strong>"</p>
                        </div>
                    </div>
                `);
            } else {
                $('.no-events-filter h3').text('No Events Found');
                $('.no-events-filter p').html(`No events match your search for "<strong>${searchTerm}</strong>"`);
            }
        } else {
            $('.no-events-filter').remove();
        }
    });

    // Event Card Hover Effects
    $('.event-card').hover(
        function() {
            $(this).find('.event-banner-image').css('transform', 'scale(1.05)');
        },
        function() {
            $(this).find('.event-banner-image').css('transform', 'scale(1)');
        }
    );

    // Get Tickets Button Click
    $('.btn-get-tickets').on('click', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        
        // Add loading state
        $(this).html('<span class="btn-spinner"></span> Loading...').prop('disabled', true);
        
        setTimeout(() => {
            window.location.href = url;
        }, 500);
    });

    // Lazy load images for better performance
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src || img.src;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });

        document.querySelectorAll('.event-banner-image').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // Show count of events
    function updateEventCount() {
        const totalEvents = $('.event-card').length;
        const visibleEvents = $('.event-card:visible').length;
        
        if (!$('.events-count').length && totalEvents > 0) {
            $('.events-header').append(`
                <p class="events-count">Showing ${visibleEvents} of ${totalEvents} events</p>
            `);
        } else {
            $('.events-count').text(`Showing ${visibleEvents} of ${totalEvents} events`);
        }
    }

    // Update count on page load
    updateEventCount();

    // Update count when filtering
    $(document).on('click', '.filter-tab', function() {
        setTimeout(updateEventCount, 100);
    });

    $(document).on('input', '#event-search', function() {
        setTimeout(updateEventCount, 100);
    });
});
</script>

<?php get_footer(); ?>