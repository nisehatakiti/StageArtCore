<?php
get_header();
$org=stageart_theme_org('name',get_bloginfo('name'));
$desc=stageart_theme_org('description');
$sections=stageart_theme_home_sections();
?>
<main>
<?php $hero=get_option('stageart_core_hero',[]); if(!is_array($hero))$hero=[]; if(!empty($hero['enabled'])): ?><section class="stageart-hero"><div class="stageart-container stageart-hero-copy"><h1><?php echo esc_html($org);?></h1><?php if($desc):?><p class="stageart-hero-description"><?php echo esc_html($desc);?></p><?php endif;?></div></section><?php endif; ?>
<?php foreach($sections as $section):$id='';foreach((array)['productions'=>'productions','members'=>'members','representative_greeting'=>'representative-greeting','about'=>'about','news'=>'news'] as $key=>$anchor){foreach((array)($section['slots']??[]) as $slot){if(($slot['type']??'')==='system'&&($slot['ref']??'')===$key){$id=$anchor;break 2;}}}?>
<section class="stageart-container stageart-section stageart-home-section stageart-home-section--<?php echo esc_attr($section['layout']);?>"<?php if($id):?> id="<?php echo esc_attr($id);?>"<?php endif;?>><div class="stageart-section-header"><div><?php if($section['heading']!==''):?><h2 class="stageart-section-title"><?php echo esc_html($section['heading']);?></h2><div class="stageart-section-rule"></div><?php endif;?></div></div><div class="stageart-home-slots" style="--stageart-columns:<?php echo (int)$section['columns'];?>"><?php foreach($section['slots'] as $slot):?><div class="stageart-home-slot stageart-indent-<?php echo max(0,min(3,(int)($slot['indent']??0)));?>"><?php stageart_theme_render_home_slot($slot);?></div><?php endforeach;?></div></section>
<?php endforeach;?>
</main>
<?php get_footer();
