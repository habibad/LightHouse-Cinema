<?php
/**
 * Cinema Movies Tabbed Swiper Slider Shortcode
 * Tab-based interface for Now Playing and Coming Soon movies
 * 
 * INSTALLATION INSTRUCTIONS:
 * 
 * 1. Add this code to your theme's functions.php file
 * 2. Use shortcode: [cinema_movies_slider]
 */

// Enqueue Swiper JS and CSS
function cinema_slider_enqueue_scripts() {
    wp_enqueue_style(
        'swiper-css',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
        array(),
        '11.0.0'
    );
    
    wp_enqueue_script(
        'swiper-js',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
        array(),
        '11.0.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'cinema_slider_enqueue_scripts');

// Register the shortcode
function cinema_movies_slider_shortcode($atts) {
    $atts = shortcode_atts(array(
        'posts_per_section' => 10,
    ), $atts);
    
    // Get current date
    $current_date = date('Y-m-d');
    
    // Fetch ALL movies
    $args = array(
        'post_type' => 'movies',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    );
    
    $all_movies_query = new WP_Query($args);
    
    // Group movies by status
    $grouped_movies = array(
        'now_playing' => array(),
        'coming_soon' => array()
    );
    
    if ($all_movies_query->have_posts()) {
        while ($all_movies_query->have_posts()) {
            $all_movies_query->the_post();
            $movie_id = get_the_ID();
            $release_date = get_post_meta($movie_id, '_movie_release_date', true);
            
            if ($release_date) {
                $is_now_playing = ($release_date <= $current_date);
                
                if ($is_now_playing) {
                    $grouped_movies['now_playing'][] = array(
                        'id' => $movie_id,
                        'release_date' => $release_date
                    );
                } else {
                    $grouped_movies['coming_soon'][] = array(
                        'id' => $movie_id,
                        'release_date' => $release_date
                    );
                }
            }
        }
        wp_reset_postdata();
    }
    
    // Sort and limit movies
    usort($grouped_movies['now_playing'], function($a, $b) {
        return strcmp($b['release_date'], $a['release_date']);
    });
    
    usort($grouped_movies['coming_soon'], function($a, $b) {
        return strcmp($a['release_date'], $b['release_date']);
    });
    
    $grouped_movies['now_playing'] = array_slice($grouped_movies['now_playing'], 0, $atts['posts_per_section']);
    $grouped_movies['coming_soon'] = array_slice($grouped_movies['coming_soon'], 0, $atts['posts_per_section']);
    
    ob_start();
    ?>

<div class="cinema-movies-slider-wrapper">
    <div class="cinema-section-header" style="text-align: center; margin-bottom: 40px; color: #fff;">
        <h2>WHAT'S PLAYING AT CINÉPOLIS</h2>
    </div>
    <!-- Tab Navigation -->
    <div class="cinema-tabs-wrapper">
        <div class="cinema-tabs-nav">
            <button class="cinema-tab-btn active" data-tab="now-playing">
                Now Playing
            </button>
            <button class="cinema-tab-btn" data-tab="coming-soon">
                Coming Soon
            </button>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="cinema-tabs-content">
        
        <!-- Now Playing Tab -->
        <div class="cinema-tab-pane active" id="now-playing-tab">
            <div class="swiper cinema-swiper now-playing-swiper">
                <div class="swiper-wrapper">
                    <?php
                    if (!empty($grouped_movies['now_playing'])) :
                        foreach ($grouped_movies['now_playing'] as $movie_data) :
                            cinema_render_movie_slide($movie_data['id'], 'now_playing');
                        endforeach;
                    else:
                        echo '<div class="swiper-slide"><div class="no-movies-message"><p>No movies currently playing</p></div></div>';
                    endif;
                    ?>
                </div>
                <div class="swiper-button-next cinema-swiper-next"></div>
                <div class="swiper-button-prev cinema-swiper-prev"></div>
            </div>
        </div>

        <!-- Coming Soon Tab -->
        <div class="cinema-tab-pane" id="coming-soon-tab">
            <div class="swiper cinema-swiper coming-soon-swiper">
                <div class="swiper-wrapper">
                    <?php
                    if (!empty($grouped_movies['coming_soon'])) :
                        foreach ($grouped_movies['coming_soon'] as $movie_data) :
                            cinema_render_movie_slide($movie_data['id'], 'coming_soon');
                        endforeach;
                    else:
                        echo '<div class="swiper-slide"><div class="no-movies-message"><p>No upcoming movies</p></div></div>';
                    endif;
                    ?>
                </div>
                <div class="swiper-button-next cinema-swiper-next"></div>
                <div class="swiper-button-prev cinema-swiper-prev"></div>
            </div>
        </div>

    </div>
</div>

<style>
.cinema-movies-slider-wrapper {
    width: 100%;
    background: #000;
    padding: 60px 40px;
    overflow: hidden;
}
 .cinema-section-header h2 {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
    color: white;
}

/* Tab Navigation Styles */
.cinema-tabs-wrapper {
    display: flex;
    justify-content: center;
    margin-bottom: 50px;
}

.cinema-tabs-nav {
    display: inline-flex;
    gap: 0;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50px;
    padding: 4px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.cinema-tab-btn {
    padding: 14px 40px;
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.6);
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    border-radius: 50px;
    text-transform: capitalize;
    position: relative;
    z-index: 1;
}

.cinema-tab-btn:hover {
    color: rgba(255, 255, 255, 0.9);
}

.cinema-tab-btn.active {
    background: #fff;
    color: #000;
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
}

/* Tab Content */
.cinema-tabs-content {
    position: relative;
    min-height: 500px;
}

.cinema-tab-pane {
    display: none;
    animation: fadeIn 0.4s ease;
}

.cinema-tab-pane.active {
    display: block;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Swiper Styles */
.cinema-swiper {
    position: relative;
    padding: 20px 0;
    height: 500px;
}

.cinema-swiper .swiper-wrapper {
    align-items: stretch;
}

.no-movies-message {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 400px;
    width: 100%;
}

.no-movies-message p {
    color: rgba(255, 255, 255, 0.6);
    font-size: 18px;
    text-align: center;
}

/* Movie Card Styles */
.cinema-movie-slide {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.4s ease;
    cursor: pointer;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.cinema-movie-slide:hover {
    transform: translateY(-10px) scale(1.02);
    z-index: 10;
}

.cinema-movie-poster {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
    background: #1a1a1a;
}

.cinema-movie-poster img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.cinema-movie-slide:hover .cinema-movie-poster img {
    transform: scale(1.1);
}

.cinema-movie-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.95) 0%, transparent 100%);
    padding: 40px 20px 20px;
    transform: translateY(100%);
    transition: transform 0.4s ease;
}

.cinema-movie-slide:hover .cinema-movie-overlay {
    transform: translateY(0);
}

.cinema-movie-title {
    font-size: 18px;
    font-weight: 700;
    color: #fff;
    margin: 0 0 8px 0;
    line-height: 1.3;
}

.cinema-movie-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}

.cinema-meta-badge {
    display: inline-block;
    padding: 4px 10px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 4px;
    font-size: 11px;
    color: #fff;
    font-weight: 600;
    text-transform: uppercase;
}

.cinema-meta-badge.rating {
    background: #f59e0b;
    color: #000;
}

.cinema-movie-genres {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 15px;
    line-height: 1.4;
}

.cinema-movie-actions {
    display: flex;
    gap: 10px;
}

.cinema-btn {
    flex: 1;
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    text-align: center;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.cinema-btn-primary {
    background: #e50914;
    color: #fff;
}

.cinema-btn-primary:hover {
    background: #f40612;
    transform: scale(1.05);
}

.cinema-release-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background: #e50914;
    color: #fff;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    z-index: 2;
    box-shadow: 0 4px 12px rgba(229, 9, 20, 0.4);
}

/* Navigation Buttons */
.cinema-swiper-next,
.cinema-swiper-prev {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.cinema-swiper-next:hover,
.cinema-swiper-prev:hover {
    background: rgba(255, 255, 255, 0.9);
    transform: scale(1.1);
}

.cinema-swiper-next::after,
.cinema-swiper-prev::after {
    font-size: 20px;
    font-weight: 900;
    color: #fff;
}

.cinema-swiper-next:hover::after,
.cinema-swiper-prev:hover::after {
    color: #000;
}

.swiper-button-disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .cinema-tab-btn {
        padding: 12px 35px;
        font-size: 15px;
    }
}

@media (max-width: 768px) {
    .cinema-movies-slider-wrapper {
        padding: 40px 20px;
    }

    .cinema-tabs-wrapper {
        margin-bottom: 30px;
    }

    .cinema-tabs-nav {
        width: 100%;
        max-width: 100%;
    }

    .cinema-tab-btn {
        flex: 1;
        padding: 12px 20px;
        font-size: 14px;
    }

    .cinema-swiper {
        height: 450px;
    }

    .cinema-swiper-next,
    .cinema-swiper-prev {
        width: 40px;
        height: 40px;
    }

    .cinema-movie-title {
        font-size: 16px;
    }
}

@media (max-width: 480px) {
    .cinema-tab-btn {
        padding: 10px 15px;
        font-size: 13px;
    }

    .cinema-swiper {
        height: 400px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    var nowPlayingSwiper;
    var comingSoonSwiper;

    // Initialize Swiper configuration
    var swiperConfig = {
        slidesPerView: 1.2,
        spaceBetween: 20,
        loop: false,
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        breakpoints: {
            480: {
                slidesPerView: 2,
                spaceBetween: 20
            },
            768: {
                slidesPerView: 2,
                spaceBetween: 25
            },
            1024: {
                slidesPerView: 3,
                spaceBetween: 30
            },
            1280: {
                slidesPerView: 5,
                spaceBetween: 30
            }
        }
    };

    // Initialize Now Playing Swiper
    if ($('.now-playing-swiper').length) {
        nowPlayingSwiper = new Swiper('.now-playing-swiper', {
            ...swiperConfig,
            navigation: {
                nextEl: '.now-playing-swiper .swiper-button-next',
                prevEl: '.now-playing-swiper .swiper-button-prev',
            }
        });
    }

    // Initialize Coming Soon Swiper
    if ($('.coming-soon-swiper').length) {
        comingSoonSwiper = new Swiper('.coming-soon-swiper', {
            ...swiperConfig,
            navigation: {
                nextEl: '.coming-soon-swiper .swiper-button-next',
                prevEl: '.coming-soon-swiper .swiper-button-prev',
            }
        });
    }

    // Tab switching functionality
    $('.cinema-tab-btn').on('click', function() {
        var tabId = $(this).data('tab');
        
        // Update active tab button
        $('.cinema-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        // Update active tab content
        $('.cinema-tab-pane').removeClass('active');
        $('#' + tabId + '-tab').addClass('active');
        
        // Update swiper when tab is shown
        setTimeout(function() {
            if (tabId === 'now-playing' && nowPlayingSwiper) {
                nowPlayingSwiper.update();
            } else if (tabId === 'coming-soon' && comingSoonSwiper) {
                comingSoonSwiper.update();
            }
        }, 100);
    });
});
</script>

<?php
    return ob_get_clean();
}
add_shortcode('cinema_movies_slider', 'cinema_movies_slider_shortcode');

// Helper function to render individual movie slide
function cinema_render_movie_slide($movie_id, $status) {
    $movie_title = get_the_title($movie_id);
    $movie_link = get_permalink($movie_id);
    
    $poster = get_the_post_thumbnail_url($movie_id, 'large');
    
    if (!$poster) {
        $trailer_banner = get_post_meta($movie_id, '_movie_trailer_banner', true);
        if ($trailer_banner) {
            if (is_numeric($trailer_banner)) {
                $poster = wp_get_attachment_image_url((int) $trailer_banner, 'large');
            } else {
                $poster = esc_url($trailer_banner);
            }
        }
    }
    
    $director = get_post_meta($movie_id, '_movie_director', true);
    $duration = get_post_meta($movie_id, '_movie_duration', true);
    $rating = get_post_meta($movie_id, '_movie_rating', true);
    $release_date = get_post_meta($movie_id, '_movie_release_date', true);
    $genre = get_post_meta($movie_id, '_movie_genre', true);
    
    $current_date = date('Y-m-d');
    $is_now_playing = ($release_date <= $current_date);
    $status_class = $is_now_playing ? 'now-playing' : 'coming-soon';
    
    if (empty($genre)) {
        $genre_terms = wp_get_post_terms($movie_id, 'movie_genre', array('fields' => 'names'));
        $genre = !empty($genre_terms) ? implode(', ', array_slice($genre_terms, 0, 3)) : 'Drama';
    }
    
    $release_text = '';
    if ($release_date) {
        $release_text = date('M d, Y', strtotime($release_date));
    }
    ?>
<div class="swiper-slide">
    <div class="cinema-movie-slide <?php echo $status_class; ?>">
        <?php if (!$is_now_playing && $release_date): ?>
        <div class="cinema-release-badge">
            <?php echo date('M d', strtotime($release_date)); ?>
        </div>
        <?php endif; ?>

        <div class="cinema-movie-poster">
            <?php if ($poster): ?>
            <img src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($movie_title); ?>" loading="lazy">
            <?php else: ?>
            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #1a1a1a; color: #666;">
                <svg width="80" height="80" fill="currentColor" viewBox="0 0 24 24" style="opacity: 0.3;">
                    <path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z" />
                </svg>
            </div>
            <?php endif; ?>

            <div class="cinema-movie-overlay">
                <h3 class="cinema-movie-title"><?php echo esc_html($movie_title); ?></h3>

                <div class="cinema-movie-meta">
                    <?php if ($rating): ?>
                    <span class="cinema-meta-badge rating"><?php echo esc_html($rating); ?></span>
                    <?php endif; ?>
                    <?php if ($duration): ?>
                    <span class="cinema-meta-badge"><?php echo esc_html($duration); ?> min</span>
                    <?php endif; ?>
                    <?php if ($release_text): ?>
                    <span class="cinema-meta-badge"><?php echo esc_html($release_text); ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($genre): ?>
                <div class="cinema-movie-genres">
                    <?php echo esc_html($genre); ?>
                </div>
                <?php endif; ?>

                <div class="cinema-movie-actions">
                    <?php if ($is_now_playing): ?>
                    <a href="<?php echo esc_url($movie_link); ?>" class="cinema-btn cinema-btn-primary">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M22 10V6c0-1.11-.9-2-2-2H4c-1.11 0-2 .89-2 2v4c1.11 0 2 .89 2 2s-.89 2-2 2v4c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2v-4c-1.11 0-2-.89-2-2s.89-2 2-2z" />
                        </svg>
                        Book Now
                    </a>
                    <?php else: ?>
                    <a href="<?php echo esc_url($movie_link); ?>" class="cinema-btn cinema-btn-primary">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z" />
                        </svg>
                        View Details
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
}
?>