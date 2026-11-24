<?php if (!defined('ABSPATH')) exit; rl_header(); ?>
<main id="main" class="wrap section"><h1>Konzerte & Rückblicke</h1>
<?php if (have_posts()): ?><div class="grid"><?php while (have_posts()): the_post(); rl_card(get_the_ID()); endwhile; ?></div><?php the_posts_pagination(); else: ?><p>Hier findest du bald unsere Konzertabende.</p><?php endif; ?>
</main><?php rl_footer(); ?>
