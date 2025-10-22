<?php
get_header();

while (have_posts()) : the_post();
    $movie_id = get_the_ID();
    $director = get_post_meta($movie_id, '_movie_director', true);
    $producer = get_post_meta($movie_id, '_movie_producer', true);
    $cast = get_post_meta($movie_id, '_movie_cast', true);
    $duration = get_post_meta($movie_id, '_movie_duration', true);
    $rating = get_post_meta($movie_id, '_movie_rating', true);
    $release_date = get_post_meta($movie_id, '_movie_release_date', true);
    $genre = get_post_meta($movie_id, '_movie_genre', true);
    $trailer_url = get_post_meta($movie_id, '_movie_trailer_url', true);
    $trailer_banner = get_post_meta($movie_id, '_movie_trailer_banner', true);
    $movie_background = get_post_meta($movie_id, '_movie_background', true);


    $background_url = wp_get_attachment_image_url($movie_background, 'full');
    // echo ($background_url);
    
    // Get poster image
    $poster_url = '';
    if ($trailer_banner) {
        $poster_url = is_numeric($trailer_banner) 
            ? wp_get_attachment_image_url($trailer_banner, 'large') 
            : $trailer_banner;
    } 
    else if (has_post_thumbnail()) {
        $poster_url = get_the_post_thumbnail_url($movie_id, 'large');
    }
?>

<div class="single-movie-wrapper" data-movie-id="<?php echo $movie_id; ?>">
    <!-- Hero Section with Background -->
    <div class="movie-hero-section"
        style="background-image: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('<?php echo esc_url($poster_url); ?>');">

        <!-- Play Button Overlay -->
        <?php if ($trailer_url): ?>
        <button class="play-trailer-btn" data-trailer-url="<?php echo esc_url($trailer_url); ?>">
            <svg width="40" height="40" fill="white" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z" />
            </svg>
        </button>
        <?php endif; ?>


    </div>

    <div>
        <div class="cinema-container">
            <div class="hero-content-wrapper">
                <!-- Movie Poster (Left Side) -->
                <div class="hero-poster">
                    <?php if ($poster_url): ?>
                    <img src="<?php echo esc_url($background_url); ?>" alt="<?php the_title_attribute(); ?>">
                    <?php else: ?>
                    <div class="poster-placeholder">
                        <svg width="80" height="80" fill="#666" viewBox="0 0 24 24">
                            <path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2z" />
                        </svg>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Movie Info (Right Side) -->
                <div class="hero-movie-info">
                    <h1 class="hero-title">
                        <?php the_title(); ?>
                        <?php if ($rating): ?>
                        <span class="rating-badge"><?php echo esc_html($rating); ?></span>
                        <?php endif; ?>
                    </h1>

                    <div class="hero-meta">
                        <?php if ($release_date): ?>
                        <span class="meta-item">
                            <strong>Release Date <?php echo date('l, F j, Y', strtotime($release_date)); ?></strong>

                        </span>
                        <?php endif; ?>

                        <?php if ($genre): ?>
                        <span class="meta-badge"><?php echo esc_html($genre); ?></span>
                        <?php endif; ?>

                        <?php if ($duration): ?>
                        <span class="meta-badge"><?php echo esc_html($duration); ?> min</span>
                        <?php endif; ?>
                    </div>




                    <div class="hero-extra-info">

                        <div>
                            <!-- Overview -->
                            <div class="hero-overview">
                                <strong>Overview</strong>
                                <div class="overview-text">
                                    <?php 
                            if (get_the_content()) {
                                echo wp_trim_words(get_the_content(), 10, '...');
                            } else {
                                echo 'Terror strikes when a promising young football player gets invited to train at a team\'s isolated compound.';
                            }
                            ?>
                                </div>
                            </div>

                            <!-- Credits -->
                            <div class="hero-credits">
                                <?php if ($director): ?>
                                <div class="credit-block">
                                    <strong>Director</strong>
                                    <span><?php echo esc_html($director); ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if ($producer): ?>
                                <div class="credit-block">
                                    <strong>Producer</strong>
                                    <span><?php echo esc_html($producer); ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if ($cast): ?>
                                <div class="credit-block">
                                    <strong>Cast</strong>
                                    <span><?php echo esc_html($cast); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="hero-actions">
                            <div class="action-group">
                                <button class="action-btn add-to-watchlist" data-movie-id="<?php echo $movie_id; ?>" aria-label="Add to Watch List">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                                <span>(<strong class="watchlist-count">0</strong>)</span>
                            </div>

                            <div>
                                <button class="action-btn rate-movie" data-movie-id="<?php echo $movie_id; ?>" aria-label="Rate this movie">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                    </svg>
                                </button>
                                <span style="margin:0 8px;">(<span class="rating-count">0</span> reviews)</span>
                            </div>

                            <button class="action-btn add-to-favorites" data-movie-id="<?php echo $movie_id; ?>" aria-label="Add to Favorites">
                                <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                                </svg>
                            </button>
                        </div>

                        <!-- Live movie stats -->
                        <div class="movie-stats" style="margin-top:12px; font-size:14px; color:#ddd;">
                            <span>Average: <strong class="rating-average">0</strong></span>
                            <span style="margin:0 8px;">(<span class="rating-count">0</span> reviews)</span>
                            &middot;
                            <span style="margin-left:8px;">Watchlist: <strong class="watchlist-count">0</strong></span>
                            <span style="margin:0 8px;">&middot;</span>
                            <span>Favorites: <strong class="favorite-count">0</strong></span>
                        </div>

                        <!-- Current user's review (if any) -->
                        <div class="user-review" style="margin-top:10px; color:#fff;">
                            <!-- Filled via AJAX if user has submitted a review -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Showtimes Section -->
    <div class="showtimes-section">
        <div class="cinema-container">
            <div class="showtimes-header">
                <h2>Showtimes</h2>

                <div class="showtime-filters">
                    <button class="filter-btn active" data-date="all">ALL</button>
                    <button class="filter-btn" data-date="today">TODAY</button>
                    <button class="filter-btn" data-date="tomorrow">TOMORROW</button>
                </div>
            </div>

            <div class="showtimes-container">
                <?php
            $showtimes = get_movie_showtimes($movie_id);
            
            // Get today's date for comparison
            $today_date = new DateTime();
            $today_date->setTime(0, 0, 0); // Set to start of day
            
            if ($showtimes) :
                // Filter and group showtimes - only future dates
                $grouped_showtimes = array();
                foreach ($showtimes as $showtime) {
                    $show_date = get_post_meta($showtime->ID, '_showtime_date', true);
                    
                    // Create date object for comparison
                    $showtime_date_obj = new DateTime($show_date);
                    $showtime_date_obj->setTime(0, 0, 0);
                    
                    // Only include if date is today or future
                    if ($showtime_date_obj >= $today_date) {
                        $grouped_showtimes[$show_date][] = $showtime;
                    }
                }
                
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
                        <?php foreach ($date_showtimes as $showtime) :
                        $show_time = get_post_meta($showtime->ID, '_showtime_time', true);
                        $screen_number = get_post_meta($showtime->ID, '_showtime_screen', true);
                        $ticket_price = get_post_meta($showtime->ID, '_showtime_ticket_price', true);
                    ?>

                        <button class="showtime-slot" data-showtime-id="<?php echo $showtime->ID; ?>"
                            data-screen="<?php echo $screen_number; ?>" data-price="<?php echo $ticket_price; ?>"
                            data-date="<?php echo $date; ?>" data-time="<?php echo $show_time; ?>">
                            <span class="showtime-time"><?php echo format_showtime($show_time); ?></span>
                            <span class="showtime-screen">Screen <?php echo $screen_number; ?></span>
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
                        <path
                            d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z" />
                    </svg>
                    <p>No upcoming showtimes available.</p>
                </div>
                <?php 
                endif;
            else :
            ?><?php
               $name = '<div class="no-showtimes">
                    <svg width="60" height="60" fill="#ccc" viewBox="0 0 24 24">
                        <path
                            d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z" />
                    </svg>
                    <p>No showtimes available currently.</p>
                </div>' ?>
                <?php echo $name; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php endwhile; ?>

<?php get_footer(); ?>