<?php
/**
 * Template Name: Single Event
 * Description: Displays single event with showtimes
 */
get_header();

while (have_posts()) : the_post();
    $event_id = get_the_ID();
    $organizer = get_post_meta($event_id, '_event_organizer', true);
    $venue = get_post_meta($event_id, '_event_venue', true);
    $duration = get_post_meta($event_id, '_event_duration', true);
    $category = get_post_meta($event_id, '_event_category', true);
    $event_date = get_post_meta($event_id, '_event_date', true);
    $event_type = get_post_meta($event_id, '_event_type', true) ?: 'event';
    $banner_image = get_post_meta($event_id, '_event_banner', true);
    
    // Get banner URL
    $banner_url = '';
    if ($banner_image) {
        $banner_url = wp_get_attachment_image_url($banner_image, 'full');
    } elseif (has_post_thumbnail()) {
        $banner_url = get_the_post_thumbnail_url($event_id, 'full');
    }
?>

<div class="single-event-wrapper">
    <!-- Hero Section with Background -->
    <div class="event-hero-section" 
         style="background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('<?php echo esc_url($banner_url); ?>');">
        
        <?php if ($event_type === 'special_screening') : ?>
            <span class="event-hero-badge">SPECIAL SCREENING</span>
        <?php endif; ?>
    </div>

    <div class="cinema-container">
        <div class="event-content-wrapper">
            <!-- Event Info Section -->
            <div class="event-main-info">
                <h1 class="event-hero-title">
                    <?php the_title(); ?>
                </h1>

                <div class="event-hero-meta">
                    <?php if ($event_date) : ?>
                        <span class="meta-item">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                            </svg>
                            <strong class="event-meta-item-strong"><?php echo date('l, F j, Y', strtotime($event_date)); ?></strong>
                        </span>
                    <?php endif; ?>

                    <?php if ($category) : ?>
                        <span class="meta-badge"><?php echo esc_html($category); ?></span>
                    <?php endif; ?>

                    <?php if ($duration) : ?>
                        <span class="meta-badge">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                            </svg>
                            <?php echo esc_html($duration); ?> min
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Event Description -->
                <div class="event-description">
                    <h3>About This Event</h3>
                    <div class="description-text">
                        <?php 
                        if (has_excerpt()) {
                            the_excerpt();
                        } elseif (get_the_content()) {
                            the_content();
                        } else {
                            echo 'Join us for this exciting event! More details coming soon.';
                        }
                        ?>
                    </div>
                </div>

                <!-- Event Details -->
                <div class="event-details-grid">
                    <?php if ($organizer) : ?>
                        <div class="detail-block">
                            <strong>Organized By</strong>
                            <span><?php echo esc_html($organizer); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($venue) : ?>
                        <div class="detail-block">
                            <strong>Venue</strong>
                            <span>
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                </svg>
                                <?php echo esc_html($venue); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Showtimes Section -->
    <div class="showtimes-section event-showtimes">
        <div class="cinema-container">
            <div class="showtimes-header">
                <h2>Available Showtimes</h2>

                <div class="showtime-filters">
                    <button class="filter-btn active" data-date="all">ALL</button>
                    <button class="filter-btn" data-date="today">TODAY</button>
                    <button class="filter-btn" data-date="tomorrow">TOMORROW</button>
                </div>
            </div>

            <div class="showtimes-container">
                <?php
                // Get event showtimes
                $showtimes_query = new WP_Query(array(
                    'post_type' => 'event_showtimes',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => '_event_showtime_event_id',
                            'value' => $event_id,
                            'compare' => '='
                        )
                    ),
                    'meta_key' => '_event_showtime_date',
                    'orderby' => 'meta_value',
                    'order' => 'ASC'
                ));
                
                // Get today's date for comparison
                $today_date = new DateTime();
                $today_date->setTime(0, 0, 0);
                
                if ($showtimes_query->have_posts()) :
                    // Group showtimes by date
                    $grouped_showtimes = array();
                    while ($showtimes_query->have_posts()) : $showtimes_query->the_post();
                        $showtime_id = get_the_ID();
                        $show_date = get_post_meta($showtime_id, '_event_showtime_date', true);
                        
                        // Create date object for comparison
                        $showtime_date_obj = new DateTime($show_date);
                        $showtime_date_obj->setTime(0, 0, 0);
                        
                        // Only include if date is today or future
                        if ($showtime_date_obj >= $today_date) {
                            $grouped_showtimes[$show_date][] = $showtime_id;
                        }
                    endwhile;
                    wp_reset_postdata();
                    
                    // Sort dates
                    ksort($grouped_showtimes);
                    
                    if (!empty($grouped_showtimes)) :
                        foreach ($grouped_showtimes as $date => $date_showtimes) :
                            $date_obj = new DateTime($date);
                            $today = new DateTime();
                            $tomorrow = new DateTime('+1 day');
                            
                            $date_class = 'showtime-date';
                            if ($date_obj->format('Y-m-d') == $today->format('Y-m-d')) {
                                $date_class .= ' today';
                            } elseif ($date_obj->format('Y-m-d') == $tomorrow->format('Y-m-d')) {
                                $date_class .= ' tomorrow';
                            }
                ?>

                <div class="<?php echo $date_class; ?>">
                    <h3 class="showtime-date-title">
                        <?php 
                        if ($date_obj->format('Y-m-d') == $today->format('Y-m-d')) {
                            echo 'TODAY ' . strtoupper($date_obj->format('D, M j, Y'));
                        } elseif ($date_obj->format('Y-m-d') == $tomorrow->format('Y-m-d')) {
                            echo 'TOMORROW ' . strtoupper($date_obj->format('D, M j, Y'));
                        } else {
                            echo strtoupper($date_obj->format('D, M j, Y'));
                        }
                        ?>
                    </h3>

                    <div class="showtime-slots">
                        <?php foreach ($date_showtimes as $showtime_id) :
                            $show_time = get_post_meta($showtime_id, '_event_showtime_time', true);
                            $screen_number = get_post_meta($showtime_id, '_event_showtime_screen', true);
                            $ticket_price = get_post_meta($showtime_id, '_event_showtime_ticket_price', true);
                        ?>

                        <button class="showtime-slot event-showtime-slot" 
                                data-showtime-id="<?php echo $showtime_id; ?>"
                                data-screen="<?php echo $screen_number; ?>" 
                                data-price="<?php echo $ticket_price; ?>"
                                data-date="<?php echo $date; ?>" 
                                data-time="<?php echo $show_time; ?>"
                                data-event-id="<?php echo $event_id; ?>">
                            <span class="showtime-time"><?php echo date('g:i A', strtotime($show_time)); ?></span>
                            <span class="showtime-screen">Screen <?php echo $screen_number; ?></span>
                            <span class="showtime-price">$<?php echo number_format($ticket_price, 2); ?></span>
                        </button>

                        <?php endforeach; ?>
                    </div>
                </div>

                <?php 
                        endforeach;
                    else :
                ?>
                <div class="no-showtimes">
                    <svg width="60" height="60" fill="#ccc" viewBox="0 0 24 24">
                        <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                    </svg>
                    <p>No upcoming showtimes available.</p>
                </div>
                <?php 
                    endif;
                else :
                ?>
                <div class="no-showtimes">
                    <svg width="60" height="60" fill="#ccc" viewBox="0 0 24 24">
                        <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                    </svg>
                    <p>No showtimes available currently. Check back soon!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Single Event Page Styles */
.single-event-wrapper {
    background: #f9f9f9;
}

.event-hero-section {
    width: 100%;
    height: 400px;
    background-size: cover;
    background-position: center;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.event-hero-badge {
    position: absolute;
    top: 30px;
    left: 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.event-content-wrapper {
    background: white;
    margin: -50px auto 0;
    max-width: 1000px;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    position: relative;
}

.event-main-info {
    margin-bottom: 40px;
}

.event-hero-title {
    font-size: 36px;
    font-weight: 700;
    color: #333;
    margin-bottom: 20px;
}

.event-hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.meta-item {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    color: #666;
}

.meta-item svg {
    color: #0066cc;
}

.event-meta-item-strong{
    color: black !important;
}

.meta-badge {
    background: #f0f0f0;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    color: #666;
    display: flex;
    align-items: center;
    gap: 5px;
}

.event-description {
    margin-bottom: 30px;
}

.event-description h3 {
    font-size: 20px;
    margin-bottom: 15px;
    color: #333;
}

.description-text {
    line-height: 1.8;
    color: #666;
    font-size: 15px;
}

.event-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

.detail-block {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.detail-block strong {
    font-size: 14px;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-block span {
    font-size: 16px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 5px;
}

.event-showtimes {
    background: white;
    padding: 60px 0;
}

.event-showtime-slot {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}

.showtime-price {
    font-size: 12px;
    color: #28a745;
    font-weight: 600;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .event-hero-section {
        height: 250px;
    }

    .event-content-wrapper {
        margin: -30px 15px 0;
        padding: 25px;
        border-radius: 8px;
    }

    .event-hero-title {
        font-size: 24px;
    }

    .event-hero-meta {
        flex-direction: column;
        gap: 12px;
    }

    .event-details-grid {
        grid-template-columns: 1fr;
    }

    .event-hero-badge {
        top: 15px;
        left: 15px;
        padding: 8px 15px;
        font-size: 12px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Showtime filtering
    $('.filter-btn').on('click', function() {
        const filter = $(this).data('date');
        
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        
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
    });

    // Event showtime selection - redirect to seat selection
    $('.event-showtime-slot').on('click', function() {
        const showtimeId = $(this).data('showtime-id');
        const eventId = $(this).data('event-id');
        
        $(this).addClass('loading').prop('disabled', true);
        $(this).find('.showtime-time').text('Loading...');
        
        setTimeout(() => {
            window.location.href = '/event-seat-selection/?showtime=' + showtimeId + '&event=' + eventId;
        }, 800);
    });
});
</script>

<?php endwhile; ?>

<?php get_footer(); ?>