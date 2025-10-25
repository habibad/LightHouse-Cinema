<?php
/**
 * Cinema Promotions/Events Slider - Exact Match to Cinépolis Style
 * Displays promotional cards with images, titles, descriptions, and CTA buttons
 * 
 * USAGE: [cinema_promotions_slider]
 */

function cinema_promotions_slider_shortcode($atts) {
    $atts = shortcode_atts(array(
        'posts_per_page' => 10,
        'category' => 'all', // 'promotion', 'special_screening', 'all'
    ), $atts);
    
    // Fetch events
    $args = array(
        'post_type' => 'events',
        'posts_per_page' => $atts['posts_per_page'],
        'meta_key' => '_event_date',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'meta_query' => array(
            array(
                'key' => '_event_date',
                'value' => date('Y-m-d'),
                'compare' => '>=',
                'type' => 'DATE'
            )
        )
    );
    
    // Filter by event type if specified
    if ($atts['category'] !== 'all') {
        $args['meta_query'][] = array(
            'key' => '_event_type',
            'value' => $atts['category'],
            'compare' => '='
        );
    }
    
    $events_query = new WP_Query($args);
    
    ob_start();
    ?>

<div class="cinema-promotions-slider-wrapper">
    <div class="promotions-header">
        <h2>CURRENT PROMOTIONS:</h2>
    </div>

    <div class="swiper promotions-swiper">
        <div class="swiper-wrapper">
            <?php
            if ($events_query->have_posts()) :
                while ($events_query->have_posts()) : $events_query->the_post();
                    $event_id = get_the_ID();
                    cinema_render_promotion_card($event_id);
                endwhile;
                wp_reset_postdata();
            else:
                // Show default placeholder cards if no events
                cinema_render_placeholder_cards();
            endif;
            ?>
        </div>
        
        <!-- Navigation Arrows -->
        <div class="swiper-button-next promotions-next"></div>
        <div class="swiper-button-prev promotions-prev"></div>
    </div>
</div>

<style>
/* Promotions Slider Container */
.cinema-promotions-slider-wrapper {
    width: 100%;
    background: #3a3a3a;
    padding: 40px 0;
    overflow: hidden;
}

/* Header */
.promotions-header {
    text-align: center;
    margin-bottom: 30px;
    padding: 0 20px;
}

.promotions-header h2 {
    font-size: 22px;
    font-weight: 700;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 0;
}

/* Swiper Container */
.promotions-swiper {
    position: relative;
    padding: 0 60px 20px;
}

.promotions-swiper .swiper-wrapper {
    align-items: stretch;
}

/* Promotion Card */
.promotion-card {
    background: #2a2a2a;
    border-radius: 8px;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.promotion-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.4);
}

/* Card Image */
.promotion-image {
    position: relative;
    width: 100%;
    height: 260px;
    overflow: hidden;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.promotion-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.promotion-card:hover .promotion-image img {
    transform: scale(1.05);
}

/* Card Content */
.promotion-content {
    padding: 25px 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #2a2a2a;
}

.promotion-title {
    font-size: 20px;
    font-weight: 700;
    color: #fff;
    margin: 0 0 12px 0;
    line-height: 1.3;
    min-height: 52px;
}

.promotion-description {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.7);
    line-height: 1.6;
    margin-bottom: 20px;
    flex: 1;
}

/* CTA Button */
.promotion-cta {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: transparent;
    color: #fff;
    border: 2px solid #fff;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    text-decoration: none;
    transition: all 0.3s ease;
    align-self: flex-start;
    letter-spacing: 0.5px;
}

.promotion-cta:hover {
    background: #fff;
    color: #2a2a2a;
    transform: translateX(5px);
}

.promotion-cta svg {
    width: 16px;
    height: 16px;
    transition: transform 0.3s ease;
}

.promotion-cta:hover svg {
    transform: translateX(3px);
}

/* Navigation Buttons */
.promotions-next,
.promotions-prev {
    width: 44px;
    height: 44px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 50%;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.promotions-next:hover,
.promotions-prev:hover {
    background: #fff;
    transform: scale(1.1);
}

.promotions-next::after,
.promotions-prev::after {
    font-size: 18px;
    font-weight: 900;
    color: #2a2a2a;
}

.promotions-prev {
    left: 10px;
}

.promotions-next {
    right: 10px;
}

.swiper-button-disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

/* No Promotions Message */
.no-promotions {
    text-align: center;
    padding: 60px 20px;
    color: rgba(255, 255, 255, 0.6);
}

.no-promotions svg {
    opacity: 0.3;
    margin-bottom: 20px;
}

.no-promotions h3 {
    font-size: 20px;
    color: #fff;
    margin-bottom: 10px;
}

.no-promotions p {
    font-size: 14px;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .promotions-swiper {
        padding: 0 50px 20px;
    }
}

@media (max-width: 768px) {
    .cinema-promotions-slider-wrapper {
        padding: 30px 0;
    }

    .promotions-header h2 {
        font-size: 18px;
    }

    .promotions-swiper {
        padding: 0 40px 20px;
    }

    .promotion-image {
        height: 220px;
    }

    .promotion-content {
        padding: 20px 16px;
    }

    .promotion-title {
        font-size: 18px;
        min-height: 48px;
    }

    .promotion-description {
        font-size: 13px;
    }

    .promotions-next,
    .promotions-prev {
        width: 36px;
        height: 36px;
    }

    .promotions-next::after,
    .promotions-prev::after {
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .promotions-swiper {
        padding: 0 10px 20px;
    }

    .promotions-next,
    .promotions-prev {
        display: none;
    }

    .promotion-image {
        height: 200px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    if (typeof Swiper !== 'undefined' && $('.promotions-swiper').length) {
        var promotionsSwiper = new Swiper('.promotions-swiper', {
            slidesPerView: 1.2,
            spaceBetween: 20,
            loop: false,
            navigation: {
                nextEl: '.promotions-next',
                prevEl: '.promotions-prev',
            },
            breakpoints: {
                480: {
                    slidesPerView: 1.5,
                    spaceBetween: 20
                },
                640: {
                    slidesPerView: 2,
                    spaceBetween: 20
                },
                768: {
                    slidesPerView: 2.5,
                    spaceBetween: 25
                },
                1024: {
                    slidesPerView: 3,
                    spaceBetween: 25
                },
                1280: {
                    slidesPerView: 4,
                    spaceBetween: 30
                }
            }
        });
    }
});
</script>

<?php
    return ob_get_clean();
}
add_shortcode('cinema_promotions_slider', 'cinema_promotions_slider_shortcode');

/**
 * Render individual promotion card
 */
function cinema_render_promotion_card($event_id) {
    $event_title = get_the_title($event_id);
    $event_link = get_permalink($event_id);
    $event_description = get_the_excerpt($event_id);
    
    if (empty($event_description)) {
        $event_description = wp_trim_words(get_post_field('post_content', $event_id), 20);
    }
    
    // Get banner image
    $banner = get_post_meta($event_id, '_event_banner', true);
    if ($banner) {
        if (is_numeric($banner)) {
            $banner_url = wp_get_attachment_image_url((int) $banner, 'large');
        } else {
            $banner_url = esc_url($banner);
        }
    } else {
        $banner_url = get_the_post_thumbnail_url($event_id, 'large');
    }
    
    // Get CTA button text
    $cta_text = get_post_meta($event_id, '_event_tickets_text', true);
    if (empty($cta_text)) {
        $cta_text = 'GET TICKETS';
    }
    
    ?>
<div class="swiper-slide">
    <div class="promotion-card">
        <div class="promotion-image">
            <?php if ($banner_url): ?>
            <img src="<?php echo esc_url($banner_url); ?>" alt="<?php echo esc_attr($event_title); ?>"
                loading="lazy">
            <?php else: ?>
            <div
                style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.3);">
                <svg width="80" height="80" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                </svg>
            </div>
            <?php endif; ?>
        </div>

        <div class="promotion-content">
            <h3 class="promotion-title"><?php echo esc_html($event_title); ?></h3>

            <?php if ($event_description): ?>
            <p class="promotion-description">
                <?php echo esc_html($event_description); ?>
            </p>
            <?php endif; ?>

            <a href="<?php echo esc_url($event_link); ?>" class="promotion-cta">
                <?php echo esc_html($cta_text); ?>
                <svg fill="currentColor" viewBox="0 0 24 24">
                    <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z" />
                </svg>
            </a>
        </div>
    </div>
</div>
<?php
}

/**
 * Render placeholder cards when no events exist
 */
function cinema_render_placeholder_cards() {
    $placeholders = array(
        array(
            'title' => 'Happy Hour at Lighthouse Cinema',
            'description' => 'Enjoy Happy Hour specials Monday through Friday until 6PM and ALL DAY on Tuesdays!',
            'link' => '#',
            'bg' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
        ),
        array(
            'title' => 'Daily Deals',
            'description' => 'Enjoy great deals, every day of the week! Including Discounted Tickets, HALF OFF Candy, & More!',
            'link' => '#',
            'bg' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'
        ),
        array(
            'title' => 'Lighthouse Cinema Rewards',
            'description' => 'Sign up for Lighthouse Cinema Rewards and start earning points towards FREE tickets!',
            'link' => '#',
            'bg' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'
        ),
        array(
            'title' => 'Lighthouse Cinema Handpicked',
            'description' => 'Check out our $5 movies every week! See what\'s coming soon!',
            'link' => '#',
            'bg' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'
        )
    );
    
    foreach ($placeholders as $placeholder) {
        ?>
<div class="swiper-slide">
    <div class="promotion-card">
        <div class="promotion-image" style="background: <?php echo $placeholder['bg']; ?>">
            <div
                style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.3);">
                <svg width="100" height="100" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z" />
                </svg>
            </div>
        </div>

        <div class="promotion-content">
            <h3 class="promotion-title"><?php echo esc_html($placeholder['title']); ?></h3>
            <p class="promotion-description"><?php echo esc_html($placeholder['description']); ?></p>
            <a href="<?php echo esc_url($placeholder['link']); ?>" class="promotion-cta">
                SIGN UP NOW
                <svg fill="currentColor" viewBox="0 0 24 24">
                    <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z" />
                </svg>
            </a>
        </div>
    </div>
</div>
<?php
    }
}
?>