<?php
if (!defined('ABSPATH')) exit;

$events = function_exists('rl_events') ? rl_events() : [];
$highlight = function_exists('rl_select_highlight') ? rl_select_highlight($events, time()) : null;
$future = array_values(array_filter($events, static fn($e) => !$e['cancelled'] && $e['start'] >= time()));
usort($future, static fn($a, $b) => $a['start'] <=> $b['start']);
?>
<main id="main">
<section class="hero wrap">
<div class="hero-copy"><p class="eyebrow">Rommelfangen · Live & ganz nah</p>
<?php if ($highlight): $id = $highlight['id']; $upcoming = $highlight['start'] >= time(); ?>
<p class="pill"><?php echo $upcoming ? 'Der nächste Abend' : 'Unser letzter Konzertabend'; ?></p>
<h1><?php echo esc_html(get_the_title($id)); ?></h1><p class="lead"><?php echo esc_html(get_the_excerpt($id)); ?></p>
<p class="date"><?php echo esc_html(rl_date($highlight['start'])); ?></p>
<a class="button" href="<?php echo esc_url(get_permalink($id)); ?>"><?php echo $upcoming ? 'Konzert & Tickets' : 'Diesen Abend entdecken'; ?></a>
<?php else: ?><h1>Große Musik.<br>Kleiner Raum.</h1><p class="lead">Besondere Stimmen, leise Gänsehaut und Abende, die bleiben. Willkommen bei unseren Wohnzimmerkonzerten in Rommelfangen.</p><p>Der nächste Termin wird bald hier bekanntgegeben.</p><a class="button" href="#stories">Unser Wohnzimmer entdecken</a><?php endif; ?>
</div><div class="hero-visual"><?php rl_image($highlight['id'] ?? 0); ?><span class="image-caption">Musik, die näher kommt.</span></div>
</section>
<section id="konzerte" class="section wrap"><div class="section-heading"><div><p class="eyebrow">Wir sehen uns im Wohnzimmer</p><h2>Die nächsten Abende</h2></div><?php if (function_exists('rl_events')): ?><a href="<?php echo esc_url(get_post_type_archive_link('rl_concert')); ?>">Alle Konzerte & Rückblicke</a><?php endif; ?></div>
<?php if ($future): ?><div class="grid"><?php foreach ($future as $event) rl_card($event['id'], $event['start']); ?></div><?php else: ?><div class="empty"><p>Vorfreude braucht manchmal ein bisschen Geduld.</p><p>Gerade ist kein Konzert angekündigt. Sobald es weitergeht, findest du hier alle Termine.</p></div><?php endif; ?></section>
<section class="manifesto"><div class="wrap"><p class="eyebrow">Näher dran</p><h2>Keine große Bühne.<br>Ein besonderer Abend.</h2><p>Bei uns wird aus einem Wohnzimmer ein Ort für Live-Musik. Für Begegnungen. Für Geschichten zwischen zwei Songs. Und für das gute Gefühl, dabei gewesen zu sein.</p></div></section>
<section id="stories" class="section wrap"><div class="section-heading"><div><p class="eyebrow">Vor, auf und hinter der Bühne</p><h2>Geschichten aus dem Wohnzimmer</h2></div></div>
<?php $stories = new WP_Query(['post_type' => 'post', 'posts_per_page' => 6]); if ($stories->have_posts()): ?><div class="grid"><?php while ($stories->have_posts()): $stories->the_post(); rl_card(get_the_ID()); endwhile; ?></div><?php wp_reset_postdata(); else: ?><div class="empty"><p>Hier entsteht unser kleines Konzerttagebuch.</p><p>Fotos, Videos, Begegnungen und die Momente hinter den Kulissen.</p></div><?php endif; ?></section>
<?php $voices = get_posts(['post_type' => 'rl_testimonial', 'numberposts' => 6]); if ($voices): ?><section class="voices section wrap"><p class="eyebrow">Was bleibt</p><h2>Stimmen aus dem Wohnzimmer</h2><div class="grid"><?php foreach ($voices as $voice): ?><figure><blockquote><?php echo wp_kses_post(wpautop($voice->post_content)); ?></blockquote><figcaption><?php echo esc_html($voice->post_title); ?></figcaption></figure><?php endforeach; ?></div></section><?php endif; ?>
<section id="gutscheine" class="section wrap"><div class="gift"><div><p class="eyebrow">Ein Abend zum Verschenken</p><h2>Verschenke Vorfreude.</h2><p>Ein Geschenkgutschein für Musik, Begegnungen und einen Platz im Wohnzimmer.</p></div><div><?php if ($url = rl_setting('gift')): ?><a class="button" href="<?php echo esc_url($url); ?>">Geschenkgutschein kaufen</a><?php else: ?><p>Geschenkgutscheine sind bald erhältlich.</p><?php endif; ?></div></div></section>
</main>
