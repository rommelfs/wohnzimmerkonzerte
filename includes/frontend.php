<?php
if (!defined('ABSPATH')) exit;
function rl_date(int $timestamp): string { return wp_date(rl_site()['date_format'] ?: 'd. F Y · H:i', $timestamp); }
function rl_media(int $id, string $size='large', string $class=''): void {
    if ($id && wp_attachment_is_image($id)) { echo wp_get_attachment_image($id,$size,false,['loading'=>'lazy','class'=>$class]); return; }
    echo '<img class="'.esc_attr($class).'" src="'.esc_url(plugins_url('assets/stage.svg',RL_PLUGIN_FILE)).'" alt="'.esc_attr(rl_ui('image_alt')).'" loading="lazy">';
}
function rl_image(int $id, string $size='large', int $fallback=0): void {
    $own=[]; $status=[];
    if ($id && get_post_type($id)==='rl_concert') {
        $status=rl_concert_style($id);
        $saved=get_post_meta($id,'rl_design',true);$own=is_array($saved) ? ($saved[rl_concert_status($id)] ?? []) : [];
    }
    $image=(int)($own['image_id'] ?? 0) ?: (int)get_post_thumbnail_id($id) ?: $fallback ?: (int)($status['image_id'] ?? 0) ?: (int)rl_site()['fallback_image_id'];
    rl_media($image,$size);
}
function rl_style_attributes(array $style): string {
    $css=[];
    foreach (['background'=>'--section-bg','color'=>'--section-ink','accent'=>'--accent'] as $key=>$variable) {
        if (!empty($style[$key]) && ($color=sanitize_hex_color($style[$key]))) $css[]=$variable.':'.$color;
    }
    return implode(';',$css);
}
function rl_card(int $id, int $timestamp=0): void {
    $style=get_post_type($id)==='rl_concert' ? rl_concert_style($id) : [];
    if (!$timestamp && get_post_type($id)==='rl_concert') $timestamp=rl_timestamp((string)get_post_meta($id,'rl_start',true));
    ?>
    <article class="card rl-themed" style="<?php echo esc_attr(rl_style_attributes($style)); ?>"><a class="image" tabindex="-1" aria-hidden="true" href="<?php echo esc_url(get_permalink($id)); ?>"><?php rl_image($id,'medium_large'); ?></a>
    <div class="card-body"><p class="eyebrow"><?php echo $timestamp ? esc_html(rl_date($timestamp)) : esc_html(get_the_date('', $id)); ?></p>
    <h3><a href="<?php echo esc_url(get_permalink($id)); ?>"><?php echo esc_html(get_the_title($id)); ?></a></h3><p><?php echo esc_html(get_the_excerpt($id)); ?></p></div></article>
    <?php
}
function rl_rich_text(string $html): void { echo wp_kses_post(wpautop(do_shortcode($html))); }
function rl_header(): void { require RL_PLUGIN_DIR.'templates/header.php'; }
function rl_footer(): void { require RL_PLUGIN_DIR.'templates/footer.php'; }
function rl_shortcode_page(string $shortcode): bool {
    $post=get_queried_object(); return is_singular('page') && $post instanceof WP_Post && has_shortcode($post->post_content,$shortcode);
}
function rl_is_homepage(): bool { return rl_shortcode_page('rommelfangen_homepage'); }
function rl_is_ticket_shop(): bool { return (string)get_query_var('rl_ticket_shop')==='1' || rl_shortcode_page('rommelfangen_tickets'); }
function rl_is_commerce_page(): bool { return !empty(rl_site()['commerce_shell']) && function_exists('is_cart') && (is_cart() || is_checkout()); }
function rl_is_concert_view(): bool { return rl_is_homepage() || rl_is_ticket_shop() || rl_is_commerce_page() || is_singular('rl_concert') || is_post_type_archive('rl_concert'); }
function rl_render_home(): string {
    static $rendering=false; if ($rendering) return '';
    $rendering=true;ob_start();
    try { require RL_PLUGIN_DIR.'templates/home-content.php'; return ob_get_clean(); }
    catch (Throwable $error) { ob_end_clean(); throw $error; }
    finally { $rendering=false; }
}
add_shortcode('rommelfangen_homepage','rl_render_home');
add_shortcode('rommelfangen_tickets','rl_render_shop');
add_filter('query_vars',function ($vars) { $vars[]='rl_ticket_shop'; return $vars; });
add_filter('template_include',function ($template) {
    if ((string)get_query_var('rl_ticket_shop')==='1') return RL_PLUGIN_DIR.'templates/ticket-page.php';
    if (rl_is_commerce_page()) return RL_PLUGIN_DIR.'templates/commerce-page.php';
    if (rl_is_homepage() || rl_shortcode_page('rommelfangen_tickets')) return RL_PLUGIN_DIR.'templates/page.php';
    if (is_singular('rl_concert')) return RL_PLUGIN_DIR.'templates/single-concert.php';
    if (is_post_type_archive('rl_concert')) return RL_PLUGIN_DIR.'templates/archive-concerts.php';
    return $template;
},99);
add_filter('redirect_canonical',function ($url) { return (string)get_query_var('rl_ticket_shop')==='1' ? false : $url; });
add_action('template_redirect',function () {
    if (!rl_is_ticket_shop()) return;
    if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE',true);
    nocache_headers();
    if ((string)get_query_var('rl_ticket_shop')==='1') { global $wp_query; $wp_query->is_404=false; status_header(200); }
},0);
function rl_design_css(): string {
    $s=rl_site(); $css=[];
    foreach ($s['colors'] as $key=>$value) if ($safe=sanitize_hex_color($value)) $css[]='--'.str_replace('_','-',$key).':'.$safe;
    $fonts=['serif'=>'Georgia,"Times New Roman",serif','sans'=>'system-ui,sans-serif','mono'=>'ui-monospace,monospace'];
    $css[]='--heading-font:'.($fonts[$s['font']] ?? $fonts['serif']);
    $css[]='--page-width:'.max(960,min(1600,(int)$s['width'])).'px';
    $css[]='--radius:'.max(0,min(40,(int)$s['radius'])).'px';
    $css[]='--section-space:'.max(24,min(140,(int)$s['spacing'])).'px';
    $css[]='--image-fit:'.rl_safe_choice($s['image_fit'],['cover','contain'],'cover');
    $css[]='--hero-ratio:'.rl_safe_choice($s['hero_ratio'],['4/5','1/1','4/3','16/9'],'4/5');
    $css[]='--photo-tilt:'.max(-5,min(5,(int)$s['photo_tilt'])).'deg';
    $css[]='font-size:'.max(16,min(22,(int)$s['font_size'])).'px';
    $css[]='color-scheme:'.rl_safe_choice($s['color_scheme'],['dark','light'],'dark');
    return 'body.rl-concert-view{'.implode(';',$css).'}';
}
add_action('wp_enqueue_scripts',function () {
    if (!rl_is_concert_view()) return;
    wp_enqueue_style('rl-concerts',plugins_url('assets/frontend.css',RL_PLUGIN_FILE),[],RL_VERSION);
    wp_add_inline_style('rl-concerts',rl_design_css());
},100);
add_filter('body_class',function ($classes) { if (rl_is_concert_view()) $classes[]='rl-concert-view'; return $classes; });
function rl_section_button(array $section, string $fallback=''): void {
    $url=$section['button_url'] ?: $fallback;
    if ($section['button_label'] && $url) echo '<a class="button" href="'.esc_url(rl_nav_url($url)).'">'.esc_html($section['button_label']).'</a>';
}
function rl_nav_url(string $url): string { return str_starts_with($url,'#') ? home_url('/'.$url) : $url; }

add_filter('document_title_parts',function ($parts) {
    if (rl_is_ticket_shop()) $parts['title']=rl_ui('shop_title');
    elseif (rl_is_homepage()) $parts['title']=rl_site()['brand'].' · '.rl_site()['subtitle'];
    elseif (is_post_type_archive('rl_concert')) $parts['title']=rl_ui('archive_title');
    return $parts;
});
