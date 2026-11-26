<?php
if (!defined('ABSPATH')) exit;

function rl_ui_defaults(): array {
    return [
        'gallery_close'=>'Bildansicht schließen', 'gallery_previous'=>'Vorheriges Bild', 'gallery_next'=>'Nächstes Bild', 'gallery_count'=>'Bild {current} von {total}', 'gallery_original'=>'Original öffnen', 'gallery_error'=>'Das Bild konnte nicht geladen werden. Bitte versuche es erneut oder öffne das Original.',
        'calendar_save'=>'Im Kalender speichern', 'review_box'=>'Dieser Konzertabend', 'review_newsletter'=>'Beim nächsten Abend dabei sein', 'gallery_title'=>'Bilder vom Konzert', 'photo_open'=>'Foto in voller Größe öffnen', 'video_download'=>'Video herunterladen',
        'skip'=>'Zum Inhalt', 'nav_upcoming'=>'Konzerte', 'nav_stories'=>'Stories', 'nav_about'=>'Über uns', 'nav_gift'=>'Gutscheine', 'nav_shop'=>'Tickets', 'nav_cart'=>'Warenkorb', 'nav_newsletter'=>'Neuigkeiten', 'nav_account'=>'Mein Konto', 'newsletter_title'=>'Keinen Konzertabend verpassen', 'newsletter_intro'=>'Konzertankündigungen und Neuigkeiten aus unserem Wohnzimmer.', 'newsletter_button'=>'Nächsten Termin erfahren',
        'next'=>'Der nächste Abend', 'last'=>'Unser letzter Konzertabend', 'upcoming'=>'Bald im Wohnzimmer', 'past'=>'Rückblick', 'cancelled'=>'Konzert abgesagt',
        'concert_button'=>'Konzert entdecken', 'past_button'=>'Diesen Abend entdecken', 'ticket_button'=>'Tickets buchen', 'all_concerts'=>'Alle Konzerte & Rückblicke',
        'empty_upcoming'=>'Gerade ist kein Konzert angekündigt. Sobald es weitergeht, findest du hier alle Termine.', 'empty_past'=>'Hier entstehen bald unsere Rückblicke.',
        'empty_stories'=>'Hier entsteht unser Konzerttagebuch mit Fotos, Videos und Momenten hinter den Kulissen.', 'empty_voices'=>'Hier ist Platz für eure Gästestimmen.',
        'image_alt'=>'Illustration einer kleinen Wohnzimmerbühne', 'image_caption'=>'Musik, die näher kommt.', 'gift_pending'=>'Geschenkgutscheine sind bald erhältlich.',
        'archive_title'=>'Konzerte & Rückblicke', 'archive_empty'=>'Hier findest du bald unsere Konzertabende.', 'ticket_box'=>'Dein Abend', 'doors'=>'Einlass',
        'cancelled_text'=>'Dieser Abend findet leider nicht statt. Bei bestehenden Buchungen melden wir uns bei euch.', 'past_text'=>'Dieser Abend liegt hinter uns. Danke an alle, die dabei waren.',
        'next_concerts'=>'Nächste Konzerte entdecken', 'ticket_hint'=>'Tickets nach Verfügbarkeit. Du kannst Tickets für mehrere Konzerte gemeinsam bestellen.',
        'sold_out'=>'Ausverkauft', 'sold_out_text'=>'Für diesen Abend sind alle Plätze vergeben.', 'not_open'=>'Der Ticketverkauf ist noch nicht geöffnet.',
        'shop_title'=>'Dein Platz im Wohnzimmer', 'shop_intro'=>'Wähle einen Konzertabend und lege deine Tickets in den gemeinsamen Warenkorb.',
        'shop_select'=>'Konzert auswählen', 'shop_change'=>'Konzert anzeigen', 'shop_quantity'=>'Anzahl Tickets', 'shop_add'=>'In den Warenkorb',
        'shop_empty'=>'Momentan sind keine kommenden Konzerte verfügbar.', 'shop_unavailable'=>'Für dieses Konzert sind aktuell keine Tickets buchbar.',
        'shop_inactive'=>'Der Ticketshop wird gerade vorbereitet.', 'shop_other'=>'Weitere Konzerte entdecken', 'shop_selection_invalid'=>'Das ausgewählte Konzert ist nicht mehr verfügbar. Bitte wähle einen anderen Abend.',
        'checkout'=>'Zur Kasse', 'cart_empty'=>'Dein Warenkorb ist noch leer.', 'cart_summary'=>'Dein Warenkorb', 'cart_count'=>'Tickets im Warenkorb',
        'instagram'=>'Instagram', 'youtube'=>'YouTube', 'facebook'=>'Facebook', 'imprint'=>'Impressum', 'privacy'=>'Datenschutz',
    ];
}
function rl_section_defaults(string $type, string $id = ''): array {
    $base = ['id'=>$id ?: $type, 'type'=>$type, 'enabled'=>true, 'eyebrow'=>'', 'title'=>'', 'body'=>'', 'image_id'=>0,
        'button_label'=>'', 'button_url'=>'', 'hide_empty'=>true, 'layout'=>'split', 'background'=>'', 'color'=>'', 'accent'=>'', 'columns'=>3, 'limit'=>6];
    $presets = [
        'hero'=>['eyebrow'=>'Rommelfangen · Live & ganz nah', 'title'=>'Große Musik. Kleiner Raum.', 'body'=>'Besondere Stimmen, leise Gänsehaut und Abende, die bleiben. Willkommen bei unseren Wohnzimmerkonzerten in Rommelfangen.', 'button_label'=>'Unser Wohnzimmer entdecken', 'button_url'=>'#ueber-uns'],
        'upcoming'=>['eyebrow'=>'Wir sehen uns im Wohnzimmer', 'title'=>'Die nächsten Abende'],
        'past'=>['eyebrow'=>'Was für Abende', 'title'=>'Erinnerungen, die bleiben'],
        'text'=>['eyebrow'=>'Näher dran', 'title'=>'Keine große Bühne. Ein besonderer Abend.', 'body'=>'Bei uns wird aus einem Wohnzimmer ein Ort für Live-Musik. Für Begegnungen. Für Geschichten zwischen zwei Songs. Und für das gute Gefühl, dabei gewesen zu sein.'],
        'about'=>['eyebrow'=>'Die Menschen hinter den Abenden', 'title'=>'Über uns', 'body'=>'Wir öffnen unser Wohnzimmer für Musik und Begegnungen. Hier erzählen wir bald mehr über uns und die Geschichte unserer Konzertreihe.'],
        'stories'=>['eyebrow'=>'Vor, auf und hinter der Bühne', 'title'=>'Geschichten aus dem Wohnzimmer'],
        'testimonials'=>['eyebrow'=>'Was bleibt', 'title'=>'Stimmen aus dem Wohnzimmer'],
        'gift'=>['eyebrow'=>'Ein Abend zum Verschenken', 'title'=>'Verschenke Vorfreude.', 'body'=>'Ein Geschenkgutschein für Musik, Begegnungen und einen Platz im Wohnzimmer.', 'button_label'=>'Geschenkgutschein kaufen'],
        'newsletter'=>['eyebrow'=>'In Verbindung bleiben', 'title'=>'Keinen Konzertabend verpassen', 'body'=>'Konzertankündigungen und Neuigkeiten aus unserem Wohnzimmer.'],
        'faq'=>['eyebrow'=>'Gut zu wissen', 'title'=>'Dein erster Wohnzimmerabend', 'body'=>'<p>Tickets für mehrere Abende können gemeinsam gebucht werden. Die Kasse zeigt die verfügbaren Gast- und Kontofunktionen. Datum und Einlass findest du beim jeweiligen Konzert und in deiner Buchung.</p><p>Bitte beachte die Informationen zu Anreise, Zugang und Veranstalterkontakt auf der jeweiligen Konzertseite.</p>'],
        'tickets'=>['eyebrow'=>'Live dabei', 'title'=>'Ein Ticket. Ein besonderer Abend.', 'body'=>'Finde deinen nächsten Konzertabend und buche deine Plätze.', 'button_label'=>'Zum Ticketshop'],
    ];
    return array_replace($base, $presets[$type] ?? []);
}
function rl_section_types(): array {
    return ['hero'=>'Konzert-Highlight', 'upcoming'=>'Kommende Konzerte', 'past'=>'Vergangene Konzerte', 'text'=>'Freier Text / Bild', 'about'=>'Über uns', 'stories'=>'Stories', 'testimonials'=>'Gästestimmen', 'gift'=>'Geschenkgutscheine', 'tickets'=>'Ticketshop-Verweis', 'newsletter'=>'Newsletter-Anmeldung', 'faq'=>'Fragen / Hinweise'];
}
function rl_site_defaults(): array {
    $sections=[];
    foreach (['hero','upcoming','newsletter','text','about','past','stories','testimonials','tickets','gift','faq'] as $type) {
        $sections[] = rl_section_defaults($type, ['upcoming'=>'konzerte','about'=>'ueber-uns','past'=>'rueckblicke','gift'=>'gutscheine','testimonials'=>'stimmen','newsletter'=>'benachrichtigungen','faq'=>'fragen'][$type] ?? $type);
    }
    return [
        'brand'=>'Rommelfangen', 'subtitle'=>'Living Room Concerts', 'logo_id'=>0, 'footer_logo_id'=>0, 'logo_mode'=>'combined', 'logo_width'=>240, 'logo_height'=>140, 'logo_mobile_width'=>200, 'footer_logo_width'=>200, 'fallback_image_id'=>0,
        'background_style'=>'atmosphere', 'home_page_id'=>0, 'shop_page_id'=>0, 'commerce_shell'=>true, 'footer_text'=>'Ein Wohnzimmer. Musik. Und du mittendrin.', 'font'=>'serif', 'image_fit'=>'cover', 'hero_ratio'=>'4/5', 'photo_tilt'=>2, 'color_scheme'=>'dark', 'font_size'=>16, 'width'=>1240, 'radius'=>5, 'spacing'=>72,
        'colors'=>['bg'=>'#151918','surface'=>'#202725','ink'=>'#f5f1e9','muted'=>'#bfc7c1','accent'=>'#f5ac7b','button_text'=>'#1d201d','line'=>'#414a45'],
        'styles'=>[
            'upcoming'=>['background'=>'','color'=>'','accent'=>'','image_id'=>0,'layout'=>''],
            'past'=>['background'=>'#202725','color'=>'','accent'=>'#cfba9b','image_id'=>0,'layout'=>'image-first'],
        ],
        'date_format'=>'d. F Y · H:i', 'sections'=>$sections, 'ui'=>rl_ui_defaults(),
        'navigation'=>[
            ['label'=>'Konzerte','url'=>'#konzerte','enabled'=>true], ['label'=>'Über uns','url'=>'#ueber-uns','enabled'=>true],
            ['label'=>'Stories','url'=>'#stories','enabled'=>true], ['label'=>'Gutscheine','url'=>'#gutscheine','enabled'=>true],
        ],
    ];
}
function rl_site(): array {
    $defaults=rl_site_defaults(); $saved=get_option('rl_site', []);
    if (!is_array($saved)) return $defaults;
    $result=array_replace($defaults, $saved);
    foreach (['colors','ui'] as $key) $result[$key]=array_replace($defaults[$key], is_array($saved[$key] ?? null) ? $saved[$key] : []);
    foreach (['upcoming','past'] as $status) $result['styles'][$status]=array_replace($defaults['styles'][$status], is_array($saved['styles'][$status] ?? null) ? $saved['styles'][$status] : []);
    return $result;
}
function rl_ui(string $key): string { return (string)(rl_site()['ui'][$key] ?? $key); }
function rl_safe_choice($value, array $allowed, string $default): string { return is_string($value) && in_array($value, $allowed, true) ? $value : $default; }
function rl_image_id($value): int { $id=is_scalar($value) ? absint($value) : 0; return $id && wp_attachment_is_image($id) ? $id : 0; }
function rl_safe_url($value): string {
    if (!is_string($value)) return '';
    return str_starts_with($value, '#') ? '#' . sanitize_title(substr($value,1)) : esc_url_raw($value, ['http','https']);
}
function rl_clean_style($value): array {
    $value=is_array($value) ? $value : [];
    $result=[];
    foreach (['background','color','accent'] as $key) $result[$key]=is_string($value[$key] ?? null) ? (sanitize_hex_color($value[$key]) ?: '') : '';
    $result['image_id']=rl_image_id($value['image_id'] ?? 0);
    $result['layout']=rl_safe_choice($value['layout'] ?? '', ['','split','image-first','stacked'], '');
    return $result;
}
function rl_sanitize_site($input): array {
    if (is_string($input)) $input=json_decode($input,true);
    if (!is_array($input)) return rl_site();
    $d=rl_site_defaults(); $out=$d;
    foreach (['brand','subtitle','footer_text','date_format'] as $key) $out[$key]=sanitize_text_field(is_string($input[$key] ?? null) ? $input[$key] : $d[$key]);
    foreach (['logo_id','footer_logo_id','fallback_image_id'] as $key) $out[$key]=rl_image_id($input[$key] ?? 0);
    $out['logo_mode']=rl_safe_choice($input['logo_mode'] ?? 'combined',['combined','logo-only','text-only'],'combined');
    foreach (['logo_width'=>[80,480],'logo_height'=>[40,240],'logo_mobile_width'=>[80,320],'footer_logo_width'=>[80,400]] as $key=>[$min,$max]) $out[$key]=max($min,min($max,(int)($input[$key]??$d[$key])));
    $out['commerce_shell']=!empty($input['commerce_shell']);
    $out['home_page_id']=is_scalar($input['home_page_id'] ?? null) ? absint($input['home_page_id']) : 0;
    $out['shop_page_id']=is_scalar($input['shop_page_id'] ?? null) ? absint($input['shop_page_id']) : 0;
    $out['background_style']=rl_safe_choice($input['background_style'] ?? 'atmosphere',['atmosphere','plain'],'atmosphere');
    $out['font']=rl_safe_choice($input['font'] ?? '', ['serif','sans','mono'], 'serif');
    $out['image_fit']=rl_safe_choice($input['image_fit'] ?? '', ['cover','contain'], 'cover');
    $out['hero_ratio']=rl_safe_choice($input['hero_ratio'] ?? '', ['4/5','1/1','4/3','16/9'], '4/5');
    $out['color_scheme']=rl_safe_choice($input['color_scheme'] ?? '', ['dark','light'], 'dark');
    foreach (['width'=>[960,1600],'radius'=>[0,40],'spacing'=>[24,140],'photo_tilt'=>[-5,5],'font_size'=>[16,22]] as $key=>[$min,$max]) $out[$key]=max($min,min($max, (int)($input[$key] ?? $d[$key])));
    foreach ($d['colors'] as $key=>$default) $out['colors'][$key]=is_string($input['colors'][$key] ?? null) ? (sanitize_hex_color($input['colors'][$key]) ?: $default) : $default;
    foreach (['upcoming','past'] as $status) $out['styles'][$status]=rl_clean_style($input['styles'][$status] ?? []);
    foreach ($d['ui'] as $key=>$default) $out['ui'][$key]=sanitize_textarea_field(is_string($input['ui'][$key] ?? null) ? $input['ui'][$key] : $default);
    $out['navigation']=[];
    foreach (array_slice(is_array($input['navigation'] ?? null) ? $input['navigation'] : $d['navigation'], 0, 20) as $item) {
        if (!is_array($item)) continue;
        $out['navigation'][]=['label'=>sanitize_text_field(is_string($item['label'] ?? null) ? $item['label'] : ''), 'url'=>rl_safe_url($item['url'] ?? ''), 'enabled'=>!empty($item['enabled'])];
    }
    $out['sections']=[]; $ids=[];
    foreach (array_slice(is_array($input['sections'] ?? null) ? $input['sections'] : rl_site()['sections'], 0, 30) as $section) {
        if (!is_array($section) || !is_string($section['type'] ?? null) || !isset(rl_section_types()[$section['type']])) continue;
        $type=$section['type']; $item=rl_section_defaults($type);
        $id=sanitize_title(is_string($section['id'] ?? null) ? $section['id'] : $type) ?: $type;
        $base=$id; $n=2; while(isset($ids[$id])) $id=$base.'-'.$n++;
        $ids[$id]=true; $item['id']=$id; $item['enabled']=!empty($section['enabled']);
        foreach (['eyebrow','title','button_label'] as $key) $item[$key]=sanitize_text_field(is_string($section[$key] ?? null) ? $section[$key] : '');
        $item['hide_empty']=!isset($section['hide_empty']) || !empty($section['hide_empty']);
        $item['body']=wp_kses_post(is_string($section['body'] ?? null) ? $section['body'] : '');
        $item['button_url']=rl_safe_url($section['button_url'] ?? '');
        $item['image_id']=rl_image_id($section['image_id'] ?? 0);
        foreach (['background','color','accent'] as $key) $item[$key]=is_string($section[$key] ?? null) ? (sanitize_hex_color($section[$key]) ?: '') : '';
        $item['layout']=rl_safe_choice($section['layout'] ?? '', ['split','image-first','stacked'], 'split');
        $item['columns']=max(1,min(4,(int)($section['columns'] ?? 3)));
        $item['limit']=max(1,min(50,(int)($section['limit'] ?? 6)));
        $out['sections'][]=$item;
    }
    return $out;
}

// Add the movable signup once to existing layouts without resetting their content or order.
function rl_layout_upgrade(): void {
    if (get_option('rl_layout_version') === '2') return;
    $site=get_option('rl_site',null);
    if (is_array($site) && isset($site['sections']) && is_array($site['sections'])) {
        $types=array_column($site['sections'],'type');
        if (!in_array('newsletter',$types,true)) {
            $position=array_search('upcoming',$types,true);
            array_splice($site['sections'],$position===false ? min(1,count($site['sections'])) : $position+1,0,[rl_section_defaults('newsletter','benachrichtigungen')]);
            update_option('rl_site',$site,false);
        }
    }
    update_option('rl_layout_version','2',false);
}
add_action('init','rl_layout_upgrade',18);
function rl_newsletter_anchor(): string {
    foreach (rl_site()['sections'] as $section) if ($section['type']==='newsletter' && !empty($section['enabled'])) return $section['id'];
    return '';
}
