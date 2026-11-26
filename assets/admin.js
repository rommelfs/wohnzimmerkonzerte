jQuery(function ($) {
  function colors(root) { $(root).find('.rl-color').not('.wp-color-picker').wpColorPicker(); }
  colors(document);
  $(document).on('input change','.rl-range-control',function () { $(this).attr('aria-valuetext',this.value+' Pixel').closest('.rl-range').find('output').text(this.value+' px'); });
  function suspendEditors(item) {
    if (!window.tinyMCE) return;
    $(item).find('textarea').each(function () { const editor=tinyMCE.get(this.id); if (editor) { editor.save(); editor.remove(); this.dataset.rlEditor='1'; } });
  }
  function restoreEditors(item) {
    if (!wp.editor) return;
    $(item).find('textarea[data-rl-editor]').each(function () { wp.editor.initialize(this.id,{tinymce:{wpautop:true},quicktags:true,mediaButtons:true}); delete this.dataset.rlEditor; });
  }
  $('.meta-box-sortables').on('sortstart',function(e,ui){suspendEditors(ui.item);}).on('sortstop',function(e,ui){restoreEditors(ui.item);});
  $('#rl-sections').sortable({handle: '.rl-drag', items: '> li', placeholder: 'rl-sort-placeholder', start: function (e,ui) { suspendEditors(ui.item); }, stop: function(e,ui) { restoreEditors(ui.item); }});
  let serial = Date.now();
  $('#rl-add-section').on('click', function () {
    const template = document.getElementById('rl-template-' + $('#rl-new-type').val());
    if (!template) return;
    const item = $(template.innerHTML.replaceAll('__INDEX__', String(++serial)));
    item.find('details').prop('open', true);
    $('#rl-sections').append(item); colors(item); item.find('textarea').each(function () { this.dataset.rlEditor='1'; }); restoreEditors(item); item.find('summary').trigger('focus');
  });
  $('#rl-add-navigation').on('click', function () {
    $('#rl-navigation').append(document.getElementById('rl-navigation-template').innerHTML.replaceAll('__INDEX__', String(++serial)));
  });
  $(document).on('click', '.rl-remove-section', function () { $(this).closest('.rl-section-item').remove(); });
  $(document).on('click', '.rl-remove-navigation', function () { $(this).closest('.rl-nav-item').remove(); });
  $(document).on('click', '.rl-move-up,.rl-move-down', function () {
    const item = $(this).closest('.rl-section-item,.rl-nav-item');
    suspendEditors(item);
    if ($(this).hasClass('rl-move-up')) item.prev().before(item); else item.next().after(item);
    restoreEditors(item); $(this).trigger('focus');
  });
  $(document).on('click', '.rl-media-select', function () {
    const box = $(this).closest('.rl-media');
    const frame = wp.media({title: 'Bild auswählen', button: {text: 'Bild verwenden'}, library: {type: 'image'}, multiple: false});
    frame.on('select', function () {
      const media = frame.state().get('selection').first().toJSON();
      box.find('.rl-image-id').val(media.id);
      box.find('.rl-media-preview').empty().append($('<img>', {src: media.sizes?.thumbnail?.url || media.url, alt: media.alt || ''}));
    });
    frame.open();
  });
  $(document).on('click', '.rl-media-clear', function () { const box=$(this).closest('.rl-media');box.find('input').val('0');box.find('.rl-media-preview').empty(); });
  // Browser submits entries in visual DOM order. Reindex only after WordPress has saved editor contents.
  $('.rl-admin form').on('submit', function () {
    if (window.tinyMCE) tinyMCE.triggerSave();
    $('#rl-sections > li').each(function (i) { $(this).find('[name]').each(function () { this.name=this.name.replace(/rl_site\[sections\]\[[^\]]+\]/, 'rl_site[sections]['+i+']'); }); });
    $('#rl-navigation > div').each(function (i) { $(this).find('[name]').each(function () { this.name=this.name.replace(/rl_site\[navigation\]\[[^\]]+\]/, 'rl_site[navigation]['+i+']'); }); });
    // Submit one validated JSON field so larger layouts do not hit PHP max_input_vars.
    const payload={sections:[],navigation:[]};
    $(this).find('[name^="rl_site["]').serializeArray().forEach(function (field) {
      const keys=field.name.match(/[^\[\]]+/g).slice(1); let target=payload;
      keys.forEach(function (key,index) { if (index===keys.length-1) target[key]=field.value; else { target[key] ||= {}; target=target[key]; } });
    });
    $(this).find('[name^="rl_site["]').removeAttr('name');
    $('<input>',{type:'hidden',name:'rl_site',value:JSON.stringify(payload)}).appendTo(this);
  });
});

jQuery(function ($) {
  function syncGallery(box) {
    box.find('input[name="rl_editorial[gallery]"]').val(box.find('li[data-id]').map(function () { return this.dataset.id; }).get().join(','));
  }
  $('.rl-gallery-select').on('click', function () {
    const box=$(this).closest('.rl-gallery-editor');
    const frame=wp.media({title:'Galeriefotos hinzufügen',button:{text:'Fotos hinzufügen'},library:{type:'image'},multiple:true});
    frame.on('select',function () {
      frame.state().get('selection').each(function (attachment) {
        const image=attachment.toJSON();
        if (box.find('li[data-id="'+image.id+'"]').length) return;
        const item=$('<li>').attr('data-id',image.id).append($('<img>',{src:image.sizes?.thumbnail?.url || image.url,alt:image.alt || ''}));
        item.append(' ', $('<button>',{type:'button',class:'button rl-gallery-up','aria-label':'Foto nach vorne',text:'↑'}),' ', $('<button>',{type:'button',class:'button rl-gallery-down','aria-label':'Foto nach hinten',text:'↓'}),' ', $('<button>',{type:'button',class:'button rl-gallery-remove',text:'Entfernen'}));
        box.find('ul').append(item);
      });
      syncGallery(box);
    });frame.open();
  });
  $(document).on('click','.rl-gallery-up,.rl-gallery-down,.rl-gallery-remove',function () {
    const item=$(this).closest('li'),box=item.closest('.rl-gallery-editor');
    if ($(this).hasClass('rl-gallery-remove')) item.remove();
    else if ($(this).hasClass('rl-gallery-up')) item.prev().before(item);
    else item.next().after(item);
    syncGallery(box);
  });
  $('.rl-video-select').on('click',function () {
    const box=$(this).closest('.rl-video-editor');
    const frame=wp.media({title:'Video auswählen',button:{text:'Video verwenden'},library:{type:'video'},multiple:false});
    frame.on('select',function () { const video=frame.state().get('selection').first().toJSON();box.find('input').val(video.id);box.find('.rl-video-name').text(video.title || video.filename); });frame.open();
  });
  $('.rl-video-clear').on('click',function () { const box=$(this).closest('.rl-video-editor');box.find('input').val('0');box.find('.rl-video-name').text('Kein zusätzliches Video'); });
});
