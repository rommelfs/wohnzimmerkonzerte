<?php if (!defined('ABSPATH')) exit; ?>
<footer class="site-footer wrap"><div><a class="brand" href="<?php echo esc_url(home_url('/')); ?>">Rommelfangen<span>Living Room Concerts</span></a><p>Ein Wohnzimmer. Musik. Und du mittendrin.</p></div>
<nav aria-label="Social Media und Rechtliches"><?php foreach (['instagram' => 'Instagram', 'youtube' => 'YouTube', 'facebook' => 'Facebook', 'imprint' => 'Impressum', 'privacy' => 'Datenschutz'] as $key => $label) { $url = get_theme_mod('rl_' . $key); if ($url) echo '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>'; } ?><?php wp_nav_menu(['theme_location' => 'footer', 'container' => false, 'fallback_cb' => false]); ?></nav>
</footer><?php wp_footer(); ?></body></html>
