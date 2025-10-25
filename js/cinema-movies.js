/**
 * Movies Archive Page JavaScript
 * Handles movie filtering, search, and showtime selection
 */

(function($) {
    'use strict';

    const MoviesPage = {
        init: function() {
            if (!$('.movies-grid').length && !$('.showtimes-section').length) return;

            this.initMovieFilters();
            this.initMovieSearch();
            this.initShowtimeFilters();
            this.initShowtimeSelection();
            this.initMovieActions();

            // Refresh stats for visible movie cards and load per-user state
            $('.movie-card[data-movie-id]').each(function() {
                const movieId = $(this).data('movie-id');
                const $card = $(this);
                MoviesPage.refreshMovieStats(movieId, $card);

                // If user is logged in, load their interactions for this movie
                if (typeof cinema_ajax !== 'undefined' && cinema_ajax.is_logged_in) {
                    $.post(cinema_ajax.ajax_url, { action: 'cinema_get_user_interaction', nonce: cinema_ajax.nonce, movie_id: movieId }).done(function(res) {
                        if (!res || !res.success) return;
                        const interactions = res.data;
                        if (interactions.watchlist) {
                            $card.find('.add-to-watchlist').addClass('added').html('Added to Watchlist');
                        }
                        if (interactions.favorite) {
                            $card.find('.add-to-favorites').addClass('added').html('Added to Favorites');
                        }
                        if (interactions.rating) {
                            $card.find('.rate-movie').addClass('rated').html('Rated ' + interactions.rating.value + '/5');
                            // If on single movie page, populate user review
                            if ($('.single-movie-wrapper[data-movie-id="' + movieId + '"]').length) {
                                const $wrapper = $('.single-movie-wrapper[data-movie-id="' + movieId + '"]');
                                const review = interactions.rating.review || '';
                                if (review.trim().length) {
                                    $wrapper.find('.user-review').html('<strong>Your review:</strong><div style="margin-top:6px; color:#ddd;">' + review + '</div>');
                                }
                            }
                        }
                    });
                }
            });

            // If on single-movie page, also refresh stats and user interaction for the main wrapper
            if ($('.single-movie-wrapper[data-movie-id]').length) {
                const $main = $('.single-movie-wrapper[data-movie-id]').first();
                const movieId = $main.data('movie-id');
                MoviesPage.refreshMovieStats(movieId, $main);
                if (typeof cinema_ajax !== 'undefined' && cinema_ajax.is_logged_in) {
                    $.post(cinema_ajax.ajax_url, { action: 'cinema_get_user_interaction', nonce: cinema_ajax.nonce, movie_id: movieId }).done(function(res) {
                        if (!res || !res.success) return;
                        const interactions = res.data;
                        if (interactions.watchlist) $main.find('.add-to-watchlist').addClass('added').html('Added to Watchlist');
                        if (interactions.favorite) $main.find('.add-to-favorites').addClass('added').html('Added to Favorites');
                        if (interactions.rating) {
                            $main.find('.rate-movie').addClass('rated').html('Rated ' + interactions.rating.value + '/5');
                            const review = interactions.rating.review || '';
                            if (review.trim().length) {
                                $main.find('.user-review').html('<strong>Your review:</strong><div style="margin-top:6px; color:#ddd;">' + review + '</div>');
                            }
                        }
                    });
                }
            }

            console.log('📽️ Movies page initialized');
        },

        // ===== MOVIE FILTERING =====
        initMovieFilters: function() {
            $('.filter-tab').on('click', function() {
                const filter = $(this).data('filter');
                
                $('.filter-tab').removeClass('active');
                $(this).addClass('active');
                
                MoviesPage.filterMovies(filter);
            });
        },

        filterMovies: function(filter) {
            const $movies = $('.movie-card');
            
            $movies.hide().removeClass('filtered-visible');
            
            switch(filter) {
                case 'now-playing':
                    $movies.filter('.now-playing').show().addClass('filtered-visible');
                    break;
                case 'coming-soon':
                    $movies.filter('.coming-soon').show().addClass('filtered-visible');
                    break;
                case 'my-movies':
                    $movies.filter('[data-favorite="true"], [data-watchlist="true"]').show().addClass('filtered-visible');
                    break;
                case 'all':
                default:
                    $movies.show().addClass('filtered-visible');
                    break;
            }

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

        // ===== MOVIE SEARCH =====
        initMovieSearch: function() {
            if (!$('#movie-search').length && $('.movies-filter').length) {
                const searchHTML = `
                    <div class="movie-search-container">
                        <input type="text" id="movie-search" placeholder="Search movies..." class="movie-search-input">
                        <svg class="search-icon" width="20" height="20" fill="#666" viewBox="0 0 24 24">
                            <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                        </svg>
                    </div>
                `;
                $('.movies-filter').append(searchHTML);
            }

            $(document).on('input', '#movie-search, .movie-search-input', function() {
                const searchTerm = $(this).val().toLowerCase();
                const $movieCards = $('.movie-card');
                
                if (searchTerm.length === 0) {
                    $movieCards.show();
                    return;
                }
                
                $movieCards.each(function() {
                    const movieTitle = $(this).find('.movie-title a').text().toLowerCase();
                    const movieGenre = $(this).find('.movie-genre').text().toLowerCase();
                    
                    if (movieTitle.includes(searchTerm) || movieGenre.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        },

        // ===== SHOWTIME FILTERING =====
        initShowtimeFilters: function() {
            $('.filter-btn').on('click', function() {
                const dateFilter = $(this).data('date');
                
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');
                
                MoviesPage.filterShowtimes(dateFilter);
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
        },

        // ===== SHOWTIME SELECTION =====
        initShowtimeSelection: function() {
            $(document).on('click', '.showtime-slot', function() {
                const $slot = $(this);
                const showtimeId = $slot.data('showtime-id');
                const screen = $slot.data('screen');
                const price = $slot.data('price');
                const date = $slot.data('date');
                const time = $slot.data('time');
                
                $slot.addClass('loading').prop('disabled', true);
                const originalText = $slot.find('.showtime-time').text();
                $slot.find('.showtime-time').text('Loading...');
                
                setTimeout(() => {
                    // ensure we have a showtime id
                    if (!showtimeId) {
                        console.error('Showtime ID missing, cannot redirect to seat selection');
                        $slot.removeClass('loading').prop('disabled', false);
                        $slot.find('.showtime-time').text(originalText);
                        Cinema.showNotification('Unable to start booking: invalid showtime.', 'error');
                        return;
                    }

                    // Build URL using localized site URL to support subdirectory installs
                    const baseUrl = (typeof cinema_ajax !== 'undefined' && cinema_ajax.site_url) ? cinema_ajax.site_url : `${location.protocol}//${location.host}`;
                    // ensure trailing slash
                    const base = new URL(baseUrl.replace(/\/$/, '') + '/');
                    const url  = new URL('seat-selection/', base);
                    url.searchParams.set('showtime', showtimeId);
                    location.href = url.toString();
                }, 800);

            });
        },

        // ===== MOVIE ACTIONS =====
        initMovieActions: function() {
            // Add/remove watchlist (toggle)
            $(document).on('click', '.add-to-watchlist', function() {
                const movieId = $(this).data('movie-id');
                const $btn = $(this);
                
                if ($btn.hasClass('added')) {
                    // remove
                    $btn.prop('disabled', true).html('<div class="btn-spinner"></div> Removing...');
                    $.post(cinema_ajax.ajax_url, { action: 'cinema_remove_interaction', nonce: cinema_ajax.nonce, movie_id: movieId, type: 'watchlist' }).done(function(res) {
                        if (!res || !res.success) {
                            if (res && res.data && res.data.code === 'not_logged_in') {
                                window.location.href = cinema_ajax.login_url + '?redirect_to=' + encodeURIComponent(window.location.href);
                                return;
                            }
                            Cinema.showNotification((res && res.data && res.data.message) ? res.data.message : 'Could not remove from watchlist', 'error');
                            $btn.prop('disabled', false).html('Added to Watchlist');
                            return;
                        }
                        $btn.removeClass('added').prop('disabled', false).html('Add to Watchlist');
                        Cinema.showNotification('Removed from your watchlist', 'success');
                        MoviesPage.refreshMovieStats(movieId, $btn.closest('.movie-card'));
                    });
                    return;
                }

                MoviesPage.addToWatchlist(movieId, $btn);
            });

            // Add/remove favorites (toggle)
            $(document).on('click', '.add-to-favorites', function() {
                const movieId = $(this).data('movie-id');
                const $btn = $(this);

                if ($btn.hasClass('added')) {
                    $btn.prop('disabled', true).html('<div class="btn-spinner"></div> Removing...');
                    $.post(cinema_ajax.ajax_url, { action: 'cinema_remove_interaction', nonce: cinema_ajax.nonce, movie_id: movieId, type: 'favorite' }).done(function(res) {
                        if (!res || !res.success) {
                            if (res && res.data && res.data.code === 'not_logged_in') {
                                window.location.href = cinema_ajax.login_url + '?redirect_to=' + encodeURIComponent(window.location.href);
                                return;
                            }
                            Cinema.showNotification((res && res.data && res.data.message) ? res.data.message : 'Could not remove from favorites', 'error');
                            $btn.prop('disabled', false).html('Added to Favorites');
                            return;
                        }
                        $btn.removeClass('added').prop('disabled', false).html('Add to Favorites');
                        Cinema.showNotification('Removed from your favorites', 'success');
                        MoviesPage.refreshMovieStats(movieId, $btn.closest('.movie-card'));
                    });
                    return;
                }

                MoviesPage.addToFavorites(movieId, $btn);
            });

            // Rate movie
            $(document).on('click', '.rate-movie', function() {
                const movieId = $(this).data('movie-id');
                MoviesPage.showRatingModal(movieId);
            });

            // Share movie
            $(document).on('click', '.share-movie', function() {
                MoviesPage.shareMovie();
            });

            // Play trailer
            $(document).on('click', '.play-trailer-btn, .play-button', function() {
                const trailerUrl = $(this).data('trailer-url') || $('[data-trailer-url]').data('trailer-url');
                
                if (trailerUrl) {
                    MoviesPage.showTrailerModal(trailerUrl);
                } else {
                    Cinema.showNotification('Trailer not available for this movie.', 'info');
                }
            });
        },

        addToWatchlist: function(movieId, $btn) {
            $btn.prop('disabled', true).html('<div class="btn-spinner"></div> Adding...');

            $.post(cinema_ajax.ajax_url, {
                action: 'cinema_add_interaction',
                nonce: cinema_ajax.nonce,
                movie_id: movieId,
                type: 'watchlist'
            }).done(function(res) {
                if (!res || !res.success) {
                    if (res && res.data && res.data.code === 'not_logged_in') {
                        window.location.href = '/login/?redirect_to=' + encodeURIComponent(window.location.href);
                        return;
                    }
                    Cinema.showNotification((res && res.data && res.data.message) ? res.data.message : 'Could not add to watchlist', 'error');
                    $btn.prop('disabled', false).html('Add to Watchlist');
                    return;
                }

                $btn.removeClass('btn-loading').addClass('added').prop('disabled', false).html(`
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    Added to Watchlist
                `);
                Cinema.showNotification('Movie added to your watchlist!', 'success');
                MoviesPage.refreshMovieStats(movieId, $btn.closest('.movie-card'));
            }).fail(function() {
                Cinema.showNotification('Request failed', 'error');
                $btn.prop('disabled', false).html('Add to Watchlist');
            });
        },

        addToFavorites: function(movieId, $btn) {
            $btn.prop('disabled', true).html('<div class="btn-spinner"></div> Adding...');

            $.post(cinema_ajax.ajax_url, {
                action: 'cinema_add_interaction',
                nonce: cinema_ajax.nonce,
                movie_id: movieId,
                type: 'favorite'
            }).done(function(res) {
                if (!res || !res.success) {
                    if (res && res.data && res.data.code === 'not_logged_in') {
                        window.location.href = '/login/?redirect_to=' + encodeURIComponent(window.location.href);
                        return;
                    }
                    Cinema.showNotification((res && res.data && res.data.message) ? res.data.message : 'Could not add to favorites', 'error');
                    $btn.prop('disabled', false).html('Add to Favorites');
                    return;
                }

                $btn.removeClass('btn-loading').addClass('added').prop('disabled', false).html(`
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                    Added to Favorites
                `);
                Cinema.showNotification('Movie added to your favorites!', 'success');
                MoviesPage.refreshMovieStats(movieId, $btn.closest('.movie-card'));
            }).fail(function() {
                Cinema.showNotification('Request failed', 'error');
                $btn.prop('disabled', false).html('Add to Favorites');
            });
        },

        shareMovie: function() {
            const movieTitle = $('.movie-hero-title, .movie-title a').first().text() || 'This Movie';
            const movieUrl = window.location.href;
            
            if (navigator.share) {
                navigator.share({
                    title: movieTitle,
                    text: `Check out "${movieTitle}" - now playing at Lighthouse Cinemma!`,
                    url: movieUrl
                }).catch(err => console.log('Share cancelled'));
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(movieUrl).then(() => {
                    Cinema.showNotification('Movie link copied to clipboard!', 'success');
                });
            } else {
                Cinema.showNotification('Sharing not supported on this device.', 'info');
            }
        },

        showRatingModal: function(movieId) {
            
            const modalHTML = `
                <div id="rating-modal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Rate This Movie</h3>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body text-center">
                            <div class="star-rating">
                                <span class="star" data-rating="1">★</span>
                                <span class="star" data-rating="2">★</span>
                                <span class="star" data-rating="3">★</span>
                                <span class="star" data-rating="4">★</span>
                                <span class="star" data-rating="5">★</span>
                            </div>
                            <textarea class="form-control" placeholder="Write a review (optional)" style="margin: 20px 0; width: 100%; height: 100px; resize: vertical;"></textarea>
                            <button class="btn btn-primary" id="submit-rating" style="width: 100%;">Submit Rating</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHTML);
            $('#rating-modal').show();

            let selectedRating = 0;
            
            $('.star').on('mouseenter', function() {
                const rating = $(this).data('rating');
                $('.star').removeClass('hover');
                for (let i = 0; i < rating; i++) {
                    $('.star').eq(i).addClass('hover');
                }
            }).on('mouseleave', function() {
                $('.star').removeClass('hover');
                for (let i = 0; i < selectedRating; i++) {
                    $('.star').eq(i).addClass('selected');
                }
            }).on('click', function() {
                selectedRating = $(this).data('rating');
                $('.star').removeClass('selected');
                for (let i = 0; i < selectedRating; i++) {
                    $('.star').eq(i).addClass('selected');
                }
            });

            $('#submit-rating').on('click', function() {
                if (selectedRating === 0) {
                    Cinema.showNotification('Please select a rating.', 'error');
                    return;
                }

                const reviewText = $('#rating-modal').find('textarea').val();

                $(this).prop('disabled', true).html('<div class="btn-spinner"></div> Submitting...');

                $.post(cinema_ajax.ajax_url, {
                    action: 'cinema_add_interaction',
                    nonce: cinema_ajax.nonce,
                    movie_id: movieId,
                    type: 'rating',
                    value: selectedRating,
                    review: reviewText
                }).done(function(res) {
                    if (!res || !res.success) {
                        if (res && res.data && res.data.code === 'not_logged_in') {
                            window.location.href = '/login/?redirect_to=' + encodeURIComponent(window.location.href);
                            return;
                        }
                        Cinema.showNotification((res && res.data && res.data.message) ? res.data.message : 'Could not submit rating', 'error');
                        $('#submit-rating').prop('disabled', false).html('Submit Rating');
                        return;
                    }

                    $('#rating-modal').hide().remove();
                    $('.rate-movie').addClass('rated').html(`
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        Rated ${selectedRating}/5
                    `);
                    Cinema.showNotification('Thank you for your rating!', 'success');
                    MoviesPage.refreshMovieStats(movieId, $('.movie-card[data-movie-id="' + movieId + '"]'));
                    // Update single-movie page user review if present
                    const $main = $('.single-movie-wrapper[data-movie-id="' + movieId + '"]');
                    if ($main.length && reviewText && reviewText.trim().length) {
                        $main.find('.user-review').html('<strong>Your review:</strong><div style="margin-top:6px; color:#ddd;">' + reviewText + '</div>');
                    }
                }).fail(function() {
                    Cinema.showNotification('Request failed', 'error');
                    $('#submit-rating').prop('disabled', false).html('Submit Rating');
                });
            });
        },

        showTrailerModal: function(url) {
            console.log('Opening trailer with URL:', url);

            if (!url) {
                Cinema.showNotification('Trailer not available for this movie.', 'info');
                return;
            }

            let videoElement = '';

            // Check if it's a YouTube URL
            if (url.includes('youtube.com') || url.includes('youtu.be')) {
                // Convert YouTube URL to embed URL
                let videoId = '';
                if (url.includes('youtube.com/watch?v=')) {
                    videoId = url.split('v=')[1].split('&')[0];
                } else if (url.includes('youtu.be/')) {
                    videoId = url.split('youtu.be/')[1].split('?')[0];
                }

                if (videoId) {
                    videoElement = `
                        <iframe width="100%" height="500" style="max-height: 70vh;" 
                                src="https://www.youtube.com/embed/${videoId}?autoplay=1" 
                                frameborder="0" allowfullscreen></iframe>
                    `;
                }
            } else {
                // Direct video file
                videoElement = `
                    <video controls autoplay style="width: 100%; height: 500px; max-height: 70vh;">
                        <source src="${url}" type="video/mp4">
                        <source src="${url}" type="video/webm">
                        Your browser does not support the video tag.
                    </video>
                `;
            }

            const modalHTML = `
                <div id="trailer-modal" class="modal trailer-modal">
                    <div class="modal-content trailer-modal-content">
                        <button class="modal-close trailer-close">&times;</button>
                        <div class="trailer-container">
                            ${videoElement}
                        </div>
                    </div>
                </div>
            `;

            // Remove existing trailer modal
            $('#trailer-modal').remove();

            $('body').append(modalHTML);
            $('#trailer-modal').show();
        },

        refreshMovieStats: function(movieId, $card) {
            $.get(cinema_ajax.ajax_url, { action: 'cinema_get_movie_stats', movie_id: movieId }).done(function(res) {
                if (!res || !res.success) return;
                const data = res.data;
                if ($card && $card.length) {
                    $card.attr('data-average-rating', data.rating.average);
                    $card.attr('data-rating-count', data.rating.count);
                    $card.find('.rating-average').text(data.rating.average || '0');
                    $card.find('.rating-count').text(data.rating.count || '0');
                    $card.find('.watchlist-count').text(data.watchlist_count || '0');
                    $card.find('.favorite-count').text(data.favorite_count || '0');
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        MoviesPage.init();
    });

})(jQuery);


// #######################################################


