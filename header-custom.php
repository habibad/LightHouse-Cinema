<div class="user-menu">
    <?php if (is_user_logged_in()) : ?>
        <a href="<?php echo home_url('/my-account'); ?>">
            <?php echo get_avatar(get_current_user_id(), 32); ?>
            <?php echo wp_get_current_user()->display_name; ?>
        </a>
        <a href="<?php echo home_url('/my-account'); ?>">My Bookings</a>
        <a href="<?php echo wp_logout_url(home_url('/')); ?>">Logout</a>
    <?php else : ?>
        <a href="<?php echo home_url('/login'); ?>">Login</a>
        <a href="<?php echo home_url('/register'); ?>">Sign Up</a>
    <?php endif; ?>
</div>