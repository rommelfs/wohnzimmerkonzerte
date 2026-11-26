<?php
if (!defined('ABSPATH')) exit;

function rl_editorial(int $id): array {
    $saved=get_post_meta($id,'_rl_editorial',true);
    return array_replace(['headline'=>'','subtitle'=>'','intro'=>'','review'=>'','box_title'=>'','status_text'=>'','gallery'=>[], 'video_id'=>0,'poster_id'=>0,'video_title'=>''],is_array($saved)?$saved:[]);
}
function rl_editorial_clean(array $input): array {
    $out=[];
    foreach (['headline','subtitle','box_title','video_title'] as $key) $out[$key]=sanitize_text_field(is_scalar($input[$key]??null)?(string)$input[$key]:'');
    $out['status_text']=sanitize_textarea_field(is_scalar($input['status_text']??null)?(string)$input['status_text']:'');
    foreach (['intro','review'] as $key) $out[$key]=wp_kses_post(is_scalar($input[$key]??null)?(string)$input[$key]:'');
    $ids=is_array($input['gallery']??null)?$input['gallery']:explode(',',is_string($input['gallery']??null)?$input['gallery']:'');
    $out['gallery']=array_values(array_unique(array_filter(array_map(static fn($id)=>is_scalar($id)?rl_image_id($id):0,array_slice($ids,0,100)))));
    $video=is_scalar($input['video_id']??null)?absint($input['video_id']):0;
    $out['video_id']=$video && get_post_type($video)==='attachment' && str_starts_with((string)get_post_mime_type($video),'video/')?$video:0;
    $out['poster_id']=is_scalar($input['poster_id']??null)?rl_image_id($input['poster_id']):0;
    return $out;
}
function rl_editorial_fields($post): void {
    $data=rl_editorial($post->ID);wp_nonce_field('rl_editorial','rl_editorial_nonce');
    echo '<p>Die zwei Bilder unter „Gestaltung“ wechseln mit dem Konzertdatum. Für mehrere gleichzeitig sichtbare Fotos die Galerie verwenden. Der große Inhaltseditor bleibt für zusätzliche Texte, Bilder und bestehende Videos erhalten.</p>';
    echo '<div class="rl-fields">';
    foreach (['headline'=>'Kurzer Seitentitel (optional; sonst Beitragstitel)','subtitle'=>'Untertitel / Tour (optional)','box_title'=>'Titel der Infobox (optional; sonst Website-Text)','status_text'=>'Öffentlicher Infobox-Hinweis (optional, für alle Zustände; sonst Website-Text)'] as $key=>$label) rl_control('rl_editorial['.$key.']',$label,$data[$key],$key==='status_text'?'textarea':'text');
    echo '</div>';
    rl_control('rl_editorial[intro]','Ankündigung / Einleitung vor dem Konzert',$data['intro'],'editor');
    rl_control('rl_editorial[review]','Persönlicher Rückblick nach dem Konzert',$data['review'],'editor');
    echo '<p>Nach dem Termin erscheint der Rückblick. Ist er leer, bleibt die Einleitung sichtbar. Bestehender Inhalt darunter wird nicht überschrieben.</p>';
    echo '<h3>Fotogalerie</h3><p>Mehrere Fotos wählen, mit Pfeilen sortieren oder einzeln entfernen. Bildunterschriften und Alternativtexte werden in der Mediathek gepflegt. Die Fotos öffnen sich in voller Größe.</p><div class="rl-gallery-editor"><input type="hidden" name="rl_editorial[gallery]" value="'.esc_attr(implode(',',$data['gallery'])).'"><ul class="rl-gallery-items">';
    foreach ($data['gallery'] as $image) echo '<li data-id="'.(int)$image.'">'.wp_get_attachment_image($image,'thumbnail').'<button type="button" class="button rl-gallery-up" aria-label="Foto nach vorne">↑</button> <button type="button" class="button rl-gallery-down" aria-label="Foto nach hinten">↓</button> <button type="button" class="button rl-gallery-remove">Entfernen</button></li>';
    echo '</ul><button type="button" class="button rl-gallery-select">Fotos hinzufügen</button></div><h3>Video</h3><p>Ein Video aus der eigenen Mediathek wählen. Bereits im Inhaltseditor eingebettete Videos bleiben erhalten; bei ihnen genügt die Wahl eines Vorschaubilds hier.</p><div class="rl-video-editor"><input type="hidden" name="rl_editorial[video_id]" value="'.(int)$data['video_id'].'"><span class="rl-video-name">'.esc_html($data['video_id']?get_the_title($data['video_id']):'Kein zusätzliches Video').'</span> <button type="button" class="button rl-video-select">Video auswählen</button> <button type="button" class="button rl-video-clear">Entfernen</button></div>';
    rl_control('rl_editorial[video_title]','Videotitel / Bildunterschrift',$data['video_title']);
    rl_control('rl_editorial[poster_id]','Videovorschaubild (sonst aktuelles Konzertbild)',$data['poster_id'],'image');
    if (current_user_can('edit_others_posts')) echo '<p><a href="'.esc_url(admin_url('edit.php?post_type=rl_concert&page=rl-editorial-texts')).'">Allgemeine Beschriftungen, Meldungen und Button-Texte bearbeiten</a></p>';
}
add_action('add_meta_boxes_rl_concert',static function ($post) {
    remove_meta_box('postcustom','rl_concert','normal');
    add_meta_box('rl_editorial','Texte, Galerie & Video','rl_editorial_fields','rl_concert','normal','high');
});
add_action('edit_form_after_title',static function ($post) {
    if ($post->post_type!=='rl_concert') return;
    echo '<p><strong>Konzertinhalte bearbeiten:</strong> Im großen Editor stehen zusätzliche Inhalte. <a href="#rl_editorial">Ankündigung, Rückblick, Galerie und Videovorschau bearbeiten</a>. Der Beitragstitel bleibt der vollständige Konzertname; ein kürzerer Seitentitel ist dort optional möglich.</p>';
});
add_action('save_post_rl_concert',static function ($id) {
    if (wp_is_post_revision($id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post',$id) || !wp_verify_nonce(rl_mail_input($_POST,'rl_editorial_nonce'),'rl_editorial')) return;
    if (isset($_POST['rl_editorial']) && is_array($_POST['rl_editorial'])) update_post_meta($id,'_rl_editorial',rl_editorial_clean(wp_unslash($_POST['rl_editorial'])));
});
function rl_concert_image_id(int $id): int {
    $design=get_post_meta($id,'rl_design',true);$status=rl_concert_style($id);
    return (int)($design[rl_concert_status($id)]['image_id']??0) ?: (int)get_post_thumbnail_id($id) ?: (int)($status['image_id']??0) ?: (int)rl_site()['fallback_image_id'];
}
function rl_video_poster(int $id): string {
    $data=rl_editorial($id);
    foreach ([$data['poster_id'],rl_concert_image_id($id)] as $image) if ($image && ($url=wp_get_attachment_image_url($image,'large'))) return $url;
    return plugins_url('assets/stage.svg',RL_PLUGIN_FILE);
}
// Also cover classic video/playlist shortcodes and block videos, without changing saved content.
function rl_content_posters(string $html): string {
    if (!is_singular('rl_concert') || !in_the_loop() || !is_main_query()) return $html;
    $poster=rl_video_poster(get_the_ID());$tags=new WP_HTML_Tag_Processor($html);
    while ($tags->next_tag('video')) if (!$tags->get_attribute('poster')) $tags->set_attribute('poster',$poster);
    $html=$tags->get_updated_html();
    return preg_replace_callback('~(<script\b[^>]*class=["\']wp-playlist-script["\'][^>]*>)(.*?)(</script>)~s',static function ($m) use ($poster) {
        $data=json_decode($m[2],true);if (!is_array($data) || ($data['type']??'')!=='video' || !is_array($data['tracks']??null)) return $m[0];
        $data['images']=true;
        foreach ($data['tracks'] as &$track) if (empty($track['image']['src']) || str_contains($track['image']['src'],'/images/media/')) {
            $track['image']=['src'=>$poster,'width'=>1280,'height'=>720];
            $track['thumb']=$track['image'];
        }
        unset($track);
        return $m[1].wp_json_encode($data,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).$m[3];
    },$html) ?? $html;
}
add_filter('the_content','rl_content_posters',20);
function rl_concert_media(int $id): void {
    $data=rl_editorial($id);
    if ($data['gallery']) {
        echo '<section class="rl-photo-section" aria-label="'.esc_attr(rl_ui('gallery_title')).'"><h2>'.esc_html(rl_ui('gallery_title')).'</h2><div class="rl-photo-gallery">';
        foreach ($data['gallery'] as $image) {
            if (!wp_attachment_is_image($image)) continue;
            echo '<figure><a href="'.esc_url(wp_get_attachment_url($image)).'" aria-label="'.esc_attr(rl_ui('photo_open').': '.(get_post_meta($image,'_wp_attachment_image_alt',true)?:get_the_title($image))).'">'.wp_get_attachment_image($image,'large',false,['loading'=>'lazy']).'</a>';
            if ($caption=wp_get_attachment_caption($image)) echo '<figcaption>'.wp_kses_post($caption).'</figcaption>';
            echo '</figure>';
        }
        echo '</div></section>';
    }
    if ($data['video_id'] && ($url=wp_get_attachment_url($data['video_id']))) {
        echo '<figure class="rl-concert-video"><video controls preload="none" playsinline poster="'.esc_url(rl_video_poster($id)).'" aria-label="'.esc_attr($data['video_title']?:get_the_title($data['video_id'])).'"><source src="'.esc_url($url).'" type="'.esc_attr(get_post_mime_type($data['video_id'])).'"><a href="'.esc_url($url).'">'.esc_html(rl_ui('video_download')).'</a></video>';
        if ($data['video_title']) echo '<figcaption>'.esc_html($data['video_title']).'</figcaption>';
        echo '</figure>';
    }
}
function rl_concert_home_url(): string {
    $chosen=(int)(rl_site()['home_page_id']??0);$front=(int)get_option('page_on_front');
    foreach (array_unique([$chosen,$front]) as $id) if ($id && get_post_status($id)==='publish' && has_shortcode((string)get_post_field('post_content',$id),'rommelfangen_homepage')) return get_permalink($id);
    foreach (get_posts(['post_type'=>'page','post_status'=>'publish','s'=>'rommelfangen_homepage','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC']) as $page) if (has_shortcode($page->post_content,'rommelfangen_homepage')) return get_permalink($page);
    return home_url('/');
}
function rl_legal_url(string $key): string {
    $url=rl_setting($key);if ($url) return $url;
    if ($key==='privacy') return (string)get_privacy_policy_url();
    return '';
}
add_filter('post_row_actions',static function ($actions,$post) {
    if ($post->post_type==='rl_concert' && current_user_can('edit_post',$post->ID)) $actions['rl-content']='<a href="'.esc_url(admin_url('edit.php?post_type=rl_concert&page=rl-content&concert='.$post->ID)).'">Texte & Medien</a>';
    return $actions;
},10,2);
add_action('admin_post_rl_concert_content',static function () {
    $id=absint(rl_mail_input($_POST,'concert'));
    if (get_post_type($id)!=='rl_concert' || !current_user_can('edit_post',$id)) wp_die('Keine Berechtigung.','',['response'=>403]);
    check_admin_referer('rl_editorial','rl_editorial_nonce');
    if (isset($_POST['rl_editorial']) && is_array($_POST['rl_editorial'])) update_post_meta($id,'_rl_editorial',rl_editorial_clean(wp_unslash($_POST['rl_editorial'])));
    clean_post_cache($id);
    wp_safe_redirect(admin_url('edit.php?post_type=rl_concert&page=rl-content&concert='.$id.'&saved=1'));exit;
});
add_action('admin_menu',static function () {
    add_submenu_page('edit.php?post_type=rl_concert','Konzertinhalte','Texte & Medien','edit_posts','rl-content',static function () {
        if (!current_user_can('edit_posts')) return;
        $id=absint(rl_mail_input($_GET,'concert'));
        echo '<div class="wrap"><h1>Konzerttexte, Galerie & Video</h1><form method="get"><input type="hidden" name="post_type" value="rl_concert"><input type="hidden" name="page" value="rl-content"><label for="rl-content-concert">Konzert wählen</label> <select id="rl-content-concert" name="concert"><option value="0">Bitte wählen</option>';
        foreach (get_posts(['post_type'=>'rl_concert','post_status'=>['publish','draft','pending','future','private'],'numberposts'=>-1]) as $post) if (current_user_can('edit_post',$post->ID)) echo '<option value="'.$post->ID.'" '.selected($id,$post->ID,false).'>'.esc_html($post->post_title).'</option>';
        echo '</select> <button class="button">Konzert öffnen</button></form>';
        if ($id && get_post_type($id)==='rl_concert' && current_user_can('edit_post',$id)) {
            if (isset($_GET['saved'])) echo '<div class="notice notice-success"><p>Konzertinhalte gespeichert.</p></div>';
            echo '<h2>'.esc_html(get_the_title($id)).'</h2><p><a href="'.esc_url(get_edit_post_link($id)).'">Vollständigen Beitrag und Konzertdaten bearbeiten</a> · <a href="'.esc_url(get_permalink($id)).'">Konzert ansehen</a></p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="rl_concert_content"><input type="hidden" name="concert" value="'.$id.'">';
            rl_editorial_fields(get_post($id));submit_button('Konzertinhalte speichern');echo '</form>';
        }
        echo '</div>';
    });
    add_submenu_page('edit.php?post_type=rl_concert','Website-Texte','Website-Texte','edit_others_posts','rl-editorial-texts',static function () {
        if (!current_user_can('edit_others_posts')) return;
        echo '<div class="wrap"><h1>Beschriftungen, Meldungen & Button-Texte</h1><p>Diese Texte gelten für die Konzertwebsite. Individuelle Konzerttexte stehen im jeweiligen Konzert unter „Texte, Galerie & Video“.</p>';
        if (isset($_GET['saved'])) echo '<div class="notice notice-success"><p>Texte gespeichert.</p></div>';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="rl_editorial_texts">';wp_nonce_field('rl_editorial_texts');
        foreach (rl_ui_defaults() as $key=>$label) rl_control('texts['.$key.']',$label,rl_ui($key),'textarea');
        submit_button('Texte speichern');echo '</form></div>';
    });
});
add_action('admin_post_rl_editorial_texts',static function () {
    if (!current_user_can('edit_others_posts')) wp_die('Keine Berechtigung.','',['response'=>403]);
    check_admin_referer('rl_editorial_texts');$site=rl_site();$input=isset($_POST['texts'])&&is_array($_POST['texts'])?wp_unslash($_POST['texts']):[];
    foreach (rl_ui_defaults() as $key=>$default) if (isset($input[$key])&&is_string($input[$key])) $site['ui'][$key]=sanitize_textarea_field($input[$key]);
    update_option('rl_site',$site);wp_safe_redirect(admin_url('edit.php?post_type=rl_concert&page=rl-editorial-texts&saved=1'));exit;
});
add_action('admin_notices',static function () {
    $screen=get_current_screen();if (!current_user_can('manage_options') || !$screen || ($screen->post_type!=='rl_concert' && $screen->id!=='settings_page_rl-concerts')) return;
    $missing=[];foreach (['imprint'=>'Impressum','privacy'=>'Datenschutz'] as $key=>$label) if (!rl_legal_url($key)) $missing[]=$label;
    if ($missing) echo '<div class="notice notice-warning"><p>Konzertwebsite: '.esc_html(implode(' und ',$missing)).' noch nicht verlinkt. <a href="'.esc_url(admin_url('options-general.php?page=rl-concerts')).'">Website-Einstellungen öffnen</a>.</p></div>';
    if (get_option('woocommerce_coming_soon')==='yes') echo '<div class="notice notice-warning"><p>WooCommerce steht auf „Demnächst verfügbar“. Den Shop vor der Freigabe ausgeloggt prüfen und unter WooCommerce → Einstellungen → Sichtbarkeit der Website bewusst freigeben.</p></div>';
});
