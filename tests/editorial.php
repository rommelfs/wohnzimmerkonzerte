<?php
if (!defined('WP_CLI') || !WP_CLI || !in_array(wp_get_environment_type(),['local','development'],true)) throw new RuntimeException('Disposable test site required.');
function rl_editorial_expect($ok,string $message): void { if (!$ok) throw new RuntimeException($message);echo 'PASS: '.$message."\n"; }
$site=get_option('rl_site',null);$settings=get_option('rl_settings',null);$privacy=get_option('wp_page_for_privacy_policy',0);$ids=[];$files=[];
$old_query=$GLOBALS['wp_query'];$old_main=$GLOBALS['wp_the_query'];$old_user=get_current_user_id();
ob_start();
try {
    $image=wp_upload_bits('rl-editorial-test.png',null,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a5CkAAAAASUVORK5CYII='));
    if ($image['error']) throw new RuntimeException($image['error']);$files[]=$image['file'];
    $photo=wp_insert_attachment(['post_mime_type'=>'image/png','post_title'=>'Konzertfoto','post_excerpt'=>'Eine echte Bildunterschrift'],$image['file']);$ids[]=$photo;
    $video=wp_insert_attachment(['post_mime_type'=>'video/mp4','post_title'=>'Konzertfilm','guid'=>'https://example.test/film.mp4']);$ids[]=$video;update_post_meta($video,'_wp_attached_file','film.mp4');
    $landing=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Konzertstartseite','post_content'=>'[rommelfangen_homepage]']);$ids[]=$landing;
    $event=wp_insert_post(['post_type'=>'rl_concert','post_status'=>'publish','post_title'=>'Ein langer ursprünglicher Titel','post_content'=>'<p>Erhaltener Inhalt</p>[playlist type="video" ids="'.$video.'"]']);$ids[]=$event;
    update_post_meta($event,'rl_start',wp_date('Y-m-d\TH:i',time()-DAY_IN_SECONDS));update_post_meta($event,'rl_doors','17:00');update_post_meta($event,'rl_private_address','GEHEIME ADRESSE');
    update_option('rl_site',array_replace(rl_site_defaults(),['home_page_id'=>$landing]));
    $clean=rl_editorial_clean(['headline'=>'Toby Beard','subtitle'=>'Autumn Lights Tour','intro'=>'<p>Ankündigung</p>','review'=>'<p>Unser Rückblick</p><script>alert(1)</script>','gallery'=>[$photo,$photo,$video,999999],'poster_id'=>$photo,'video_id'=>$video,'video_title'=>'Unser Konzertfilm']);
    rl_editorial_expect($clean['gallery']===[$photo] && !str_contains($clean['review'],'<script>') && $clean['video_id']===$video,'Editorial input rejects scripts and non-image gallery entries while preserving image order');
    rl_editorial_expect(rl_editorial_clean(['video_id'=>$photo])['video_id']===0,'Video selector rejects image attachments');
    update_post_meta($event,'_rl_editorial',array_replace($clean,['poster_id'=>999999]));
    rl_editorial_expect(str_ends_with(rl_video_poster($event),'assets/stage.svg'),'Deleted poster attachments fall back to a local preview instead of an empty video');
    update_post_meta($event,'_rl_editorial',$clean);
    rl_editorial_expect(rl_nav_url('#ueber-uns')===get_permalink($landing).'#ueber-uns' && rl_concert_home_url()===get_permalink($landing),'Brand and anchor links resolve to the selected concert subpage');
    update_option('rl_site',rl_site_defaults());rl_editorial_expect(has_shortcode((string)get_post_field('post_content',url_to_postid(rl_concert_home_url())),'rommelfangen_homepage'),'Concert homepage is discovered when no page is configured');
    $GLOBALS['wp_query']=new WP_Query(['post_type'=>'rl_concert','p'=>$event]);$GLOBALS['wp_the_query']=$GLOBALS['wp_query'];
    ob_start();require RL_PLUGIN_DIR.'templates/single-concert.php';$html=ob_get_clean();
    rl_editorial_expect(str_contains($html,'<h1>Toby Beard</h1>') && str_contains($html,'Autumn Lights Tour') && str_contains($html,'Unser Rückblick') && str_contains($html,'Erhaltener Inhalt') && !str_contains($html,'<p>Ankündigung</p>'),'Retrospective uses its own heading and text while retaining existing content');
    rl_editorial_expect(!str_contains($html,'Im Kalender speichern') && !str_contains($html,'Einlass:') && !str_contains($html,'GEHEIME ADRESSE') && str_contains($html,'rl-review'),'Past concert hides booking-only controls and private address');
    rl_editorial_expect(str_contains($html,'Eine echte Bildunterschrift') && str_contains($html,'rl-photo-gallery') && str_contains($html,'Unser Konzertfilm') && str_contains($html,'poster="'.esc_url(rl_video_poster($event)).'"'),'Gallery captions and the dedicated video poster are rendered');
    rl_editorial_expect(str_contains($html,'class="rl-mail-signup"') && str_contains($html,esc_url(get_post_type_archive_link('rl_concert'))),'Retrospective offers newsletter signup and the concert archive rather than an empty ticket shop');
    rl_editorial_expect(str_contains($html,'"image":{"src":') && !str_contains($html,'video.svg'),'Existing video playlist receives a real poster in its track data');
    $GLOBALS['wp_query']->rewind_posts();$GLOBALS['wp_query']->the_post();
    $explicit=rl_content_posters('<video poster="https://example.test/own.jpg"></video><video></video>');
    rl_editorial_expect(str_contains($explicit,'poster="https://example.test/own.jpg"') && substr_count($explicit,'poster=')===2,'Existing explicit video posters are retained; missing posters are added');
    update_post_meta($event,'rl_start',wp_date('Y-m-d\TH:i',time()+DAY_IN_SECONDS));$GLOBALS['wp_query']->rewind_posts();
    ob_start();require RL_PLUGIN_DIR.'templates/single-concert.php';$future=ob_get_clean();
    rl_editorial_expect(str_contains($future,'Ankündigung') && !str_contains($future,'Unser Rückblick') && str_contains($future,'Im Kalender speichern') && str_contains($future,'Einlass:'),'Upcoming concerts retain announcement text, calendar and admission time');
    update_option('rl_settings',[]);$policy=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Datenschutz']);$ids[]=$policy;update_option('wp_page_for_privacy_policy',$policy);
    rl_editorial_expect(rl_legal_url('privacy')===get_permalink($policy) && rl_legal_url('imprint')==='','Published WordPress privacy page is used without inventing an imprint URL');
    $user=wp_insert_user(['user_login'=>'rl-editorial-'.wp_generate_password(8,false),'user_pass'=>wp_generate_password(),'role'=>'editor']);$ids_user=$user;wp_set_current_user($user);
    rl_editorial_expect(current_user_can('edit_others_posts') && !current_user_can('manage_options') && current_user_can('edit_post',$event),'Editorial users can edit concert content and website texts without administrator rights');
    wp_set_current_user($old_user);require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user($user);unset($ids_user);
    echo "Editorial and media integration checks passed.\n";
} finally {
    wp_set_current_user($old_user);if(isset($ids_user)){require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user($ids_user);}
    foreach(array_reverse($ids) as $id) { if(get_post_type($id)==='attachment')wp_delete_attachment($id,true);else wp_delete_post($id,true); }
    foreach($files as $file)if(file_exists($file))unlink($file);
    if($site===null)delete_option('rl_site');else update_option('rl_site',$site);if($settings===null)delete_option('rl_settings');else update_option('rl_settings',$settings);update_option('wp_page_for_privacy_policy',$privacy);
    $GLOBALS['wp_query']=$old_query;$GLOBALS['wp_the_query']=$old_main;wp_reset_postdata();ob_end_flush();
}
