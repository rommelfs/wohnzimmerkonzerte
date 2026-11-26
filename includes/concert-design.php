<?php
if (!defined('ABSPATH')) exit;
function rl_concert_status(int $id): string { return rl_timestamp((string)get_post_meta($id,'rl_start',true)) < time() ? 'past' : 'upcoming'; }
function rl_concert_style(int $id): array {
    $status=rl_concert_status($id); $global=rl_site()['styles'][$status];
    $saved=get_post_meta($id,'rl_design',true); $own=is_array($saved) && is_array($saved[$status] ?? null) ? $saved[$status] : [];
    foreach (['background','color','accent','image_id','layout'] as $key) if (!empty($own[$key])) $global[$key]=$own[$key];
    return $global;
}
function rl_sanitize_concert_design($input): array {
    $result=[];
    foreach (['upcoming','past'] as $status) {
        $value=is_array($input) && is_array($input[$status] ?? null) ? $input[$status] : [];
        $result[$status]=rl_clean_style($value);
        $result[$status]['layout']=rl_safe_choice($value['layout'] ?? '', ['','split','image-first','stacked'], '');
    }
    return $result;
}
add_action('add_meta_boxes', function () {
    add_meta_box('rl_design','Gestaltung dieses Konzerts',function ($post) {
        wp_nonce_field('rl_save_design','rl_design_nonce');
        $saved=get_post_meta($post->ID,'rl_design',true); $saved=is_array($saved) ? $saved : [];
        echo '<p>Leer lassen, um das Grunddesign der Website zu verwenden. Die beiden Bilder sind Alternativen: Bis zum Beginn erscheint das Ankündigungsbild, danach das Rückblickbild. Beide werden nicht gleichzeitig angezeigt. Mehrere Fotos unter „Texte, Galerie & Video“ hinzufügen. Die Bilder gelten auch für Highlight und Konzertkarten. Das Beitragsbild ist der gemeinsame Standard, falls kein eigenes Statusbild gesetzt ist.</p><div class="rl-fields">';
        foreach (['upcoming'=>'Vor dem Konzert','past'=>'Nach dem Konzert / Rückblick'] as $status=>$label) {
            $value=array_replace(['background'=>'','color'=>'','accent'=>'','image_id'=>0,'layout'=>''], $saved[$status] ?? []);
            rl_control('rl_design['.$status.'][image_id]',$label.' · Bild',$value['image_id'],'image');
            rl_control('rl_design['.$status.'][layout]',$label.' · Anordnung',$value['layout'],'select',[''=>'Website-Vorgabe','split'=>'Text links / Bild rechts','image-first'=>'Bild links / Text rechts','stacked'=>'Untereinander']);
            foreach (['background'=>'Hintergrund','color'=>'Textfarbe','accent'=>'Akzent'] as $key=>$field) rl_control('rl_design['.$status.']['.$key.']',$label.' · '.$field,$value[$key],'color');
        }
        echo '</div>';
    },'rl_concert','normal','default');
});
add_action('save_post_rl_concert', function ($id) {
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($id)) return;
    if (!isset($_POST['rl_design_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rl_design_nonce'])),'rl_save_design') || !current_user_can('edit_post',$id)) return;
    update_post_meta($id,'rl_design',rl_sanitize_concert_design(wp_unslash($_POST['rl_design'] ?? [])));
});
