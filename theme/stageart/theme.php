<?php
declare(strict_types=1);
if(!defined('ABSPATH'))exit;

use StageArtCore\Presentation\PublicSite\ThemeRenderer;

function stageart_theme_print_theme_css():void{
    if(is_admin()||!class_exists(ThemeRenderer::class))return;
    $productionId=0;
    $slug=get_query_var('stageart_production_slug');
    if(is_string($slug)&&$slug!==''){
        $q=new WP_Query(['post_type'=>'stageart_production','name'=>$slug,'post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids']);
        if($q->posts)$productionId=(int)$q->posts[0];
    }
    $css=$productionId?ThemeRenderer::productionCss($productionId):ThemeRenderer::organizationCss();
    if($css)echo'<style id="stageart-theme-vars">'.$css.'</style>';
}
add_action('wp_head','stageart_theme_print_theme_css',20);

function stageart_theme_print_org_favicon():void{if(is_admin())return;$logoId=(int)get_option('stageart_org_logo_id',0);if(!$logoId)return;$url=wp_get_attachment_image_url($logoId,'full');if(!$url)return;echo'<link rel="icon" href="'.esc_url($url).'"><link rel="apple-touch-icon" href="'.esc_url($url).'">';}
add_action('wp_head','stageart_theme_print_org_favicon',1);
