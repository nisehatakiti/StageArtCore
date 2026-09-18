<?php
declare(strict_types=1);
namespace StageArtCore\Presentation\PublicSite;
use StageArtCore\Presentation\Admin\FreeContentAdmin;
final class FreeContentRouter
{
 public function register():void{add_action('init',[$this,'rewrite']);add_filter('query_vars',[$this,'vars']);add_action('template_redirect',[$this,'render'],3);}
 public function rewrite():void{add_rewrite_rule('^content/([^/]+)/?$','index.php?stageart_free_content_slug=$matches[1]','top');}
 public function vars(array $v):array{$v[]='stageart_free_content_slug';return$v;}
 public function render():void{$slug=get_query_var('stageart_free_content_slug');if(!is_string($slug)||$slug==='')return;$q=new \WP_Query(['post_type'=>FreeContentAdmin::POST_TYPE,'name'=>$slug,'post_status'=>'publish','posts_per_page'=>1]);if(!$q->have_posts()){global $wp_query;$wp_query->set_404();status_header(404);$t=get_404_template();if($t)include$t;exit;}$p=$q->posts[0];get_header();echo '<main class="stageart-free-content-page"><div class="stageart-container"><article class="stageart-free-content"><h1>'.esc_html($p->post_title).'</h1><div class="stageart-free-content-body">'.wp_kses_post($p->post_content).'</div></article></div></main>';get_footer();exit;}
}