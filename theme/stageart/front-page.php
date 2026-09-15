<?php
get_header();
$org=stageart_theme_org('name',get_bloginfo('name'));
$desc=stageart_theme_org('description');
$sections=stageart_theme_home_sections();
?>
<main>
<section class="stageart-hero"><div class="stageart-container stageart-hero-copy"><p class="stageart-kicker">Theatre / Stage Art</p><h1><?php echo esc_html($org);?></h1><?php if($desc):?><p class="stageart-hero-description"><?php echo esc_html($desc);?></p><?php endif;?><div class="stageart-hero-actions"><a class="stageart-button stageart-button--light" href="#productions">公演を見る</a><a class="stageart-button stageart-button--accent" href="#about">私たちについて</a></div></div><div class="stageart-hero-mark" aria-hidden="true">幕</div></section>
<?php foreach($sections as $section):$id='';if($section['heading']==='公演')$id='productions';elseif($section['heading']==='私たちについて')$id='about';elseif($section['heading']==='お知らせ')$id='news';?>
<section class="stageart-container stageart-section stageart-home-section stageart-home-section--<?php echo esc_attr($section['layout']);?>"<?php if($id):?> id="<?php echo esc_attr($id);?>"<?php endif;?>><div class="stageart-section-header"><div><p class="stageart-kicker">StageArt</p><?php if($section['heading']!==''):?><h2 class="stageart-section-title"><?php echo esc_html($section['heading']);?></h2><div class="stageart-section-rule"></div><?php endif;?></div></div><div class="stageart-home-slots" style="--stageart-columns:<?php echo (int)$section['columns'];?>"><?php foreach($section['slots'] as $slot):?><div class="stageart-home-slot stageart-indent-<?php echo max(0,min(3,(int)($slot['indent']??0)));?>"><?php stageart_theme_render_home_slot($slot);?></div><?php endforeach;?></div></section>
<?php endforeach;?>
</main>
<?php get_footer();
