<?php
if (!defined('ABSPATH')) exit;
$events=rl_events(); $highlight=rl_select_highlight($events,time());
$future=array_values(array_filter($events,static fn($e)=>!$e['cancelled'] && $e['start']>=time()));
$past=array_values(array_filter($events,static fn($e)=>!$e['cancelled'] && $e['start']<time()));
usort($future,static fn($a,$b)=>$a['start']<=>$b['start']); usort($past,static fn($a,$b)=>$b['start']<=>$a['start']);
$heading_count=0;
?>
<main id="main">
<?php foreach (rl_site()['sections'] as $section): if (empty($section['enabled'])) continue;
    $heading=++$heading_count===1 ? 'h1' : 'h2';
    $type=$section['type']; $style=$section; $layout=$section['layout'];
    if ($type==='hero' && $highlight) {
        $concert_style=rl_concert_style($highlight['id']);
        foreach (['background','color','accent'] as $key) if (!empty($concert_style[$key])) $style[$key]=$concert_style[$key];
        if ($concert_style['layout']) $layout=$concert_style['layout'];
    }
?>
<section id="<?php echo esc_attr($section['id']); ?>" class="rl-section section rl-themed rl-type-<?php echo esc_attr($type); ?> layout-<?php echo esc_attr($layout); ?>" style="<?php echo esc_attr(rl_style_attributes($style)); ?>"><div class="wrap">
<?php if ($type==='hero'): ?>
    <div class="hero"><div class="hero-copy"><p class="eyebrow"><?php echo esc_html($section['eyebrow']); ?></p>
    <?php if ($highlight): $id=$highlight['id']; $upcoming=$highlight['start']>=time(); ?>
        <p class="pill"><?php echo esc_html(rl_ui($upcoming?'next':'last')); ?></p>
        <<?php echo $heading; ?>><?php echo esc_html(get_the_title($id)); ?></<?php echo $heading; ?>><p class="lead"><?php echo esc_html(get_the_excerpt($id)); ?></p>
        <p class="date"><?php echo esc_html(rl_date($highlight['start'])); ?></p>
        <div class="button-row"><a class="button" href="<?php echo esc_url(get_permalink($id)); ?>"><?php echo esc_html(rl_ui($upcoming?'concert_button':'past_button')); ?></a>
        <?php if ($upcoming): ?><a class="button secondary" href="<?php echo esc_url(rl_shop_url($id)); ?>"><?php echo esc_html(rl_ui('ticket_button')); ?></a><?php endif; ?></div>
    <?php else: ?>
        <<?php echo $heading; ?>><?php echo esc_html($section['title']); ?></<?php echo $heading; ?>><div class="lead"><?php rl_rich_text($section['body']); ?></div><?php rl_section_button($section); ?>
    <?php endif; ?></div>
    <div class="hero-visual"><?php rl_image($highlight['id'] ?? 0,'large',(int)$section['image_id']); ?><span class="image-caption"><?php echo esc_html(rl_ui('image_caption')); ?></span></div></div>
<?php else: ?>
    <div class="section-heading"><div><p class="eyebrow"><?php echo esc_html($section['eyebrow']); ?></p><<?php echo $heading; ?>><?php echo esc_html($section['title']); ?></<?php echo $heading; ?></div>
    <?php if (in_array($type,['upcoming','past'],true)): ?><a href="<?php echo esc_url(get_post_type_archive_link('rl_concert')); ?>"><?php echo esc_html(rl_ui('all_concerts')); ?></a><?php endif; ?></div>
    <?php if (in_array($type,['text','about','gift','tickets'],true)): ?>
        <div class="content-split <?php echo $section['image_id'] ? '' : 'without-image'; ?>"><div class="prose"><?php rl_rich_text($section['body']); ?>
        <?php if ($type==='gift') { rl_section_button($section,rl_setting('gift')); if (!$section['button_url'] && !rl_setting('gift')) echo '<p>'.esc_html(rl_ui('gift_pending')).'</p>'; }
        elseif ($type==='tickets') rl_section_button($section,rl_shop_url()); else rl_section_button($section); ?></div>
        <?php if ($section['image_id']): ?><div class="section-image"><?php rl_media((int)$section['image_id']); ?></div><?php endif; ?></div>
    <?php else: ?>
        <div class="section-intro"><?php rl_rich_text($section['body']); ?><?php if ($section['image_id']): ?><div class="section-banner"><?php rl_media((int)$section['image_id']); ?></div><?php endif; ?></div>
        <div class="grid columns-<?php echo (int)$section['columns']; ?>">
        <?php $items=[];
        if ($type==='upcoming' || $type==='past') { $items=array_slice($type==='upcoming' ? $future : $past,0,(int)$section['limit']); foreach ($items as $event) rl_card($event['id'],$event['start']); }
        elseif ($type==='stories') { $items=get_posts(['post_type'=>'post','numberposts'=>(int)$section['limit'],'post_status'=>'publish']); foreach ($items as $post_item) rl_card($post_item->ID); }
        elseif ($type==='testimonials') { $items=get_posts(['post_type'=>'rl_testimonial','numberposts'=>(int)$section['limit'],'post_status'=>'publish']); foreach ($items as $voice) echo '<figure><blockquote>'.wp_kses_post(wpautop($voice->post_content)).'</blockquote><figcaption>'.esc_html($voice->post_title).'</figcaption></figure>'; }
        ?></div>
        <?php if (!$items) echo '<div class="empty"><p>'.esc_html(rl_ui(['upcoming'=>'empty_upcoming','past'=>'empty_past','stories'=>'empty_stories','testimonials'=>'empty_voices'][$type] ?? 'archive_empty')).'</p></div>'; ?>
        <?php rl_section_button($section); ?>
    <?php endif; ?>
<?php endif; ?>
</div></section>
<?php endforeach; ?>
<section id="benachrichtigungen" class="section wrap"><h2>Keinen Konzertabend verpassen</h2><p>Wir informieren dich, wenn neue Wohnzimmerkonzerte angekündigt werden.</p><?php echo rl_mail_signup_form(); ?></section>
</main>
