<?php
get_header(); 
?>

<div class="cinema-container">
    <div class="movies-header">
        <h1 class="movies-title">MOVIES</h1>
        <p class="movies-subtitle">Browse the latest movies out now, advanced tickets, and movies coming soon to
            Cinépolis.</p>
    </div>

    <div class="movies-filter">
        <nav class="filter-tabs">
            <button class="filter-tab active" data-filter="now-playing">Now Playing</button>
            <button class="filter-tab" data-filter="coming-soon">Coming Soon</button>
            <button class="filter-tab" data-filter="all">All</button>
            <button class="filter-tab" data-filter="my-movies">My Movies</button>
        </nav>
    </div>

    <div class="movies-grid">
        <?php 
        if (have_posts()) :
            while (have_posts()) : the_post();
                $movie_id = get_the_ID();
                $director = get_post_meta($movie_id, '_movie_director', true);
                $duration = get_post_meta($movie_id, '_movie_duration', true);
                $rating = get_post_meta($movie_id, '_movie_rating', true);
                $release_date = get_post_meta($movie_id, '_movie_release_date', true);
                $genre = get_post_meta($movie_id, '_movie_genre', true);
                
                // Check if movie is now playing or coming soon
                $current_date = date('Y-m-d');
                $is_now_playing = ($release_date <= $current_date);
                $status_class = $is_now_playing ? 'now-playing' : 'coming-soon';
                
                // Get movie background image
                $movie_background = get_post_meta($movie_id, '_movie_background', true);
                $background_style = '';
                if ($movie_background) {
                    $background_url = wp_get_attachment_image_url($movie_background, 'full');
                    if ($background_url) {
                        $background_style = ' style="background-image: url(\'' . esc_url($background_url) . '\'); background-size: cover; background-position: center;"';
                    }
                }
        ?>
        <div class="movie-container">

            <div class="movie-card <?php echo $status_class; ?>" data-movie-id="<?php echo $movie_id; ?>"
                <?php echo $background_style; ?>>
                <div class="movie-poster">
                    <a href="<?php echo get_permalink(); ?>">
                        <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('medium_large', array('class' => 'poster-image')); ?>
                       
                        <?php endif; ?>

                        <div class="movie-overlay">
                            <div class="play-button">
                                <svg width="24" height="24" fill="white" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </div>
                            <div class="movie-actions">
                                <button class="action-btn add-to-watchlist" title="Add to Watch List"
                                    data-movie-id="<?php echo $movie_id; ?>">
                                    <svg width="20" height="20" fill="white" viewBox="0 0 24 24">
                                        <path
                                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                    </svg>
                                </button>
                                <button class="action-btn share-movie" title="Share"
                                    data-movie-id="<?php echo $movie_id; ?>">
                                    <svg width="20" height="20" fill="white" viewBox="0 0 24 24">
                                        <path
                                            d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="movie-info">
                    <h3 class="movie-title">
                        <a href="<?php echo get_permalink(); ?>">
                            <?php the_title(); ?>
                        </a>
                    </h3>
                    <div>

                    </div>

                    <div class="movie-card-details">
                        <div class="movie-meta">
                            <?php if ($rating) : ?>
                            <?php echo get_movie_rating_badge($rating); ?>
                            <?php endif; ?>

                            <?php if ($duration) : ?>
                            <span class="duration"><?php echo $duration; ?> min</span>
                            <?php endif; ?>
                        </div>

                        <div>
                            <?php if ($genre) : ?>
                            <p class="movie-genre"><?php echo $genre; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>


                    <div class="movie-status">
                        <?php if ($is_now_playing) : ?>
                        <span class="status-badge now-playing">Now Playing</span>
                        <?php else : ?>
                        <span class="status-badge coming-soon">Coming Soon</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>


        <?php 
            endwhile;
        else :
        ?>
        <div class="no-movies">
            <div class="no-movies-content">
                <svg width="80" height="80" fill="#ccc" viewBox="0 0 24 24">
                    <path
                        d="M18 4V3c0-.55-.45-1-1-1H5c-.55 0-1 .45-1 1v4c0 .55.45 1 1 1s1-.45 1-1V5h10v10.5c0 .83-.67 1.5-1.5 1.5S13 16.33 13 15.5V10c0-.55-.45-1-1-1H7c-.55 0-1 .45-1 1v7c0 1.1.9 2 2 2h8c1.66 0 3-1.34 3-3V4z" />
                </svg>
                <h3>No Movies Found</h3>
                <p>We couldn't find any movies matching your criteria. Please try adjusting your filters or check back
                    later.</p>
                <button class="btn btn-primary" onclick="window.location.reload()">Refresh Page</button>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (have_posts()) : ?>
    <div class="movies-pagination">
        <?php
            echo paginate_links(array(
                'prev_text' => '<svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg> Previous',
                'next_text' => 'Next <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>',
                'mid_size' => 2,
                'end_size' => 1,
                'type' => 'list',
                'before_page_number' => '<span class="sr-only">Page </span>',
            ));
            ?>
    </div>
    <?php endif; ?>
</div>

<script>
// Add search functionality
function addMovieSearch() {
    const searchHTML = `
        <div class="movie-search-container">
            <input type="text" id="movie-search" placeholder="Search movies..." class="movie-search-input">
            <svg class="search-icon" width="20" height="20" fill="#666" viewBox="0 0 24 24">
                <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
            </svg>
        </div>
    `;

    document.querySelector('.movies-filter').insertAdjacentHTML('beforeend', searchHTML);
}

// Initialize search when DOM is loaded
document.addEventListener('DOMContentLoaded', addMovieSearch);
</script>

<style>
/* Add some CSS to ensure the background image displays properly */
.movie-card {
    position: relative;
    background-color: #222;
    /* Fallback background color */
}

.movie-info {
    position: relative;
    z-index: 2;
    background-color: rgba(0, 0, 0, 0.7);
    /* Semi-transparent background for better text readability */
    padding: 15px;
    border-radius: 0 0 8px 8px;
}

.movie-poster {
    position: relative;
    z-index: 1;
}
</style>

<?php get_footer(); ?>