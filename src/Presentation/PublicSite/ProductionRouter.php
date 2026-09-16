<?php

declare(strict_types=1);

namespace StageArtCore\Presentation\PublicSite;

use StageArtCore\Domain\Production\ProductionRepository;
use StageArtCore\Domain\Production\ProductionCreditRepository;
use StageArtCore\Domain\Member\MemberRepository;
use StageArtCore\Domain\Release\ReleaseDate;
use StageArtCore\Domain\Survey\SurveyRepository;

final class ProductionRouter
{
    public function register(): void { add_action('init', [$this, 'rewrite']); add_filter('query_vars', [$this, 'vars']); add_action('template_redirect', [$this, 'render'], 2); }
    public function rewrite(): void { add_rewrite_rule('^production/([^/]+)/?$', 'index.php?stageart_production_slug=$matches[1]', 'top'); }
    public function vars(array $v): array { $v[] = 'stageart_production_slug'; return $v; }
    private function released($v): bool { return ReleaseDate::isReleased($v ?: null); }
    private function placeholder(string $text): void { echo '<p class="stageart-release-placeholder">' . esc_html($text) . '</p>'; }
    private function mapsUrl(string $value): string { return preg_match('#^https?://#i', $value) ? $value : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($value); }
    private function performanceCell(array $x, string $display, string $marker): string
    {
        if ($display === 'both' && !empty($x['symbol'])) return trim((string) $x['symbol'] . ' ' . (string) $x['label_name']);
        return $display === 'symbol' && !empty($x['symbol']) ? (string) $x['symbol'] : $marker;
    }
    private function performanceLegend(array $labels): void
    {
        if (!$labels) return;
        echo '<div class="stageart-performance-legend">';
        foreach ($labels as $l) echo '<span>' . esc_html((string) $l['symbol'] . ' ' . $l['name']) . '</span>　';
        echo '</div>';
    }
    private function performanceTable(array $ps, array $labels, string $display, string $marker, string $legend): void
    {
        $dates=[];$times=[];$map=[];
        foreach($ps as$x){$d=(string)$x['performance_date'];$t=substr((string)$x['start_time'],0,5);$dates[$d]=true;$times[$t]=true;$map[$d][$t][]=$x;}
        $dates=array_keys($dates);$times=array_keys($times);sort($dates);sort($times);
        if($legend==='above')$this->performanceLegend($labels);
        echo '<div class="stageart-performance-table-wrap"><table class="stageart-performance-table stageart-performance-table--compact"><thead><tr><th>開演</th>';
        foreach($dates as$d)echo'<th>'.esc_html(wp_date('n/j',strtotime($d))).'</th>';
        echo'</tr></thead><tbody>';
        foreach($times as$t){echo'<tr><th>'.esc_html($t).'</th>';foreach($dates as$d){echo'<td>';if(!empty($map[$d][$t]))foreach($map[$d][$t]as$x){$cell=$display==='none'?$marker:$this->performanceCell($x,$display,$marker);echo'<span class="stageart-performance-cell">'.esc_html($cell).'</span>';}echo'</td>';}echo'</tr>';}
        echo'</tbody></table></div>';if($legend==='below')$this->performanceLegend($labels);
    }
    private function performanceList(array $ps, string $display, string $marker): void
    {
        $groups=[];
        foreach($ps as$x){$date=(string)$x['performance_date'];$start=substr((string)$x['start_time'],0,5);$cell=$display==='none'?$marker:$this->performanceCell($x,$display,$marker);$groups[$date][]=['time'=>$start,'cell'=>$cell];}
        ksort($groups);
        echo'<div class="stageart-performance-list">';
        foreach($groups as$date=>$items){usort($items,static fn(array$a,array$b):int=>strcmp($a['time'],$b['time']));$times=[];foreach($items as$item)$times[]=$display==='none'?esc_html($item['time']):esc_html($item['time']).' '.esc_html($item['cell']);echo'<div class="stageart-performance-list-row"><span class="stageart-performance-list-date">'.esc_html(wp_date('n/j',strtotime($date))).'</span><span class="stageart-performance-list-time">'.implode(' / ',$times).'</span></div>';}
        echo'</div>';
    }
    private function performanceTimeline(array $ps, string $display, string $marker, string $style): void
    {
        $dates=[];$times=[];$map=[];
        foreach($ps as$x){$d=(string)$x['performance_date'];$t=substr((string)$x['start_time'],0,5);$dates[$d]=true;$times[$t]=true;$map[$d][$t][]=$x;}
        $dates=array_keys($dates);$times=array_keys($times);sort($dates);sort($times);
        $modifier=$style==='timeline_grid'?'stageart-performance-timeline--grid':'stageart-performance-timeline--line';
        echo'<div class="stageart-performance-timeline '.esc_attr($modifier).'"><table><thead><tr><th></th>';foreach($dates as$d)echo'<th>'.esc_html(wp_date('n/j',strtotime($d))).'</th>';echo'</tr></thead><tbody>';
        foreach($times as$t){echo'<tr><th>'.esc_html($t).'</th>';foreach($dates as$d){$items=$map[$d][$t]??[];echo'<td><span class="stageart-performance-timeline-line">';foreach($items as$x){$cell=$display==='none'?$marker:$this->performanceCell($x,$display,$marker);echo'<b>'.esc_html($cell).'</b>';}echo'</span></td>';}echo'</tr>';}
        echo'</tbody></table></div>';
    }
    private function performanceView(array $ps,array$labels,string$display,string$marker,string$legend,string$view):void
    {
        if($view==='list'){$this->performanceList($ps,$display,$marker);return;}
        if($view==='timeline_line'||$view==='timeline_grid'){$this->performanceTimeline($ps,$display,$marker,$view);return;}
        $this->performanceTable($ps,$labels,$display,$marker,$legend);
    }
    public function render(): void
    {
        $slug=get_query_var('stageart_production_slug');if(!is_string($slug)||$slug==='')return;
        $r=new ProductionRepository();$q=new \WP_Query(['post_type'=>'stageart_production','name'=>$slug,'post_status'=>'publish','posts_per_page'=>1]);
        if(!$q->have_posts()){$historical=$r->productionIdByHistoricalSlug($slug);if($historical){$old=get_post($historical);if($old&&$old->post_status==='publish'&&$old->post_name!==$slug){wp_safe_redirect(home_url('/production/'.rawurlencode($old->post_name).'/'),301);exit;}}global$wp_query;$wp_query->set_404();status_header(404);$t=get_404_template();if($t)include$t;exit;}
        $p=$q->posts[0];$m=get_post_meta($p->ID);$g=fn($k,$d='')=>(string)($m[$k][0]??$d);$theme=sanitize_key($g('presentation_theme','standard'));if(!in_array($theme,['standard','dark','light'],true))$theme='standard';$c=new ProductionCreditRepository();get_header();
        echo'<main class="stageart-production stageart-production--'.esc_attr($theme).'" data-stageart-presentation-theme="'.esc_attr($theme).'"><article>';
        $layout=ProductionLayout::get($p->ID);foreach($layout as$section){echo'<section class="stageart-production-layout-section">';if(!empty($section['heading']))echo'<h2>'.esc_html($section['heading']).'</h2>';echo'<div class="stageart-production-layout-slots stageart-production-layout-slots--'.esc_attr($section['layout']).'" style="--stageart-layout-columns:'.(int)$section['columns'].'">';foreach((array)$section['slots']as$slot){$type=(string)($slot['type']??'link');$ref=(string)($slot['ref']??'');if($type==='heading'){echo'<h3>'.esc_html($ref).'</h3>';continue;}if(!$ref)continue;$this->renderBlock($ref,$p,$g,$r,$c,true);}echo'</div></section>';}
        echo'</article></main>';get_footer();exit;
    }
    private function renderBlock(string$id,\WP_Post$p,callable$g,ProductionRepository$r,ProductionCreditRepository$c,bool$inner=false):void
    {
        if(!$inner)echo'<section class="stageart-production-section stageart-production-section--'.esc_attr($id).'">';
        switch($id){
            case'main_image':if($this->released($g('main_image_release'))&&$g('main_image_id'))echo wp_get_attachment_image((int)$g('main_image_id'),'large');break;
            case'title':echo'<h1>'.esc_html($p->post_title).'</h1>';break;
            case'summary':if($this->released($g('summary_release'))){if($g('summary'))echo'<p>'.esc_html($g('summary')).'</p>';}else$this->placeholder('近日公開');break;
            case'description':if(!$inner)echo'<h2>公演紹介</h2>';if($this->released($g('description_release'))){if($g('description'))echo wp_kses_post($g('description'));else$this->placeholder('公演紹介はありません。');}else$this->placeholder('近日公開');break;
            case'schedule':if(!$inner)echo'<h2>公演日程</h2>';if($this->released($g('schedule_release'))){if($g('schedule_start')||$g('schedule_end'))echo'<p>'.esc_html($g('schedule_start')).($g('schedule_end')?' ～ '.esc_html($g('schedule_end')):'').'</p>';else$this->placeholder('公演日程は登録されていません。');}else$this->placeholder('近日公開');break;
            case'performances':
                if(!$inner)echo'<h2>公演回</h2>';$ps=$r->performances($p->ID);if(!$ps)$this->placeholder('現在登録されている公演回はありません。');else{$released=array_values(array_filter($ps,fn(array$x):bool=>$this->released($x['release_at']??null)));if(!$released)$this->placeholder('公演回は後日公開');else{$display=$g('use_labels','1')==='1'?$g('label_display','symbol'):'none';$view=sanitize_key($g('performance_view','table'));if(!in_array($view,['table','list','timeline_line','timeline_grid'],true))$view='table';$this->performanceView($released,$r->labels($p->ID),$display,$g('performance_marker','●'),$g('legend','none'),$view);}}break;
            case'venue':if(!$inner)echo'<h2>会場</h2>';if(!$this->released($g('venue_release')))$this->placeholder('近日公開');else{if($g('venue_name'))echo'<p>'.esc_html($g('venue_name')).'</p>';else$this->placeholder('会場は登録されていません。');if($g('venue_map')){if($this->released($g('venue_map_release')))echo'<p><a href="'.esc_url($this->mapsUrl($g('venue_map'))).'" target="_blank" rel="noopener">Google Mapsで見る</a></p>';else$this->placeholder('地図は後日公開');}}break;
            case'cast':$this->participants($p,$g,$r,'cast','出演者');break;
            case'staff':$this->participants($p,$g,$r,'staff','スタッフ');break;
            case'tickets':if(!$inner)echo'<h2>チケット料金</h2>';if(!$this->released($g('ticket_release')))$this->placeholder('料金は後日公開');else{$ts=$r->tickets($p->ID);if(!$ts)$this->placeholder('チケット料金は登録されていません。');else{echo'<ul>';foreach($ts as$x){$tax=$g('tax_display','included')==='included'?'（税込）':($g('tax_display')==='excluded'?'（税別）':'');echo'<li>'.esc_html($x['description']).'：'.number_format($x['amount']).'円'.esc_html($tax).'</li>';}echo'</ul>';if($g('ticket_comment'))echo wp_kses_post(wpautop($g('ticket_comment')));}}break;
            case'survey':if(!$inner)echo'<h2>アンケート</h2>';$survey=(new SurveyRepository())->findByProduction($p->ID);if($survey&&(string)$survey['status']==='publish')echo'<p><a href="'.esc_url(home_url('/production/'.$p->post_name.'/'.rawurlencode((string)$survey['slug']).'/')).'">アンケートに回答する</a></p>';break;
            case'credits':foreach($c->sections($p->ID,true)as$s){if(!$s['items'])continue;if(!$inner)echo'<h2>'.esc_html($s['name']).'</h2>';echo'<h3>'.esc_html($s['name']).'</h3><ul>';foreach($s['items']as$x)echo'<li>'.($x['url']?'<a href="'.esc_url($x['url']).'" target="_blank" rel="noopener">'.esc_html($x['name']).'</a>':esc_html($x['name'])).'</li>';echo'</ul>';}break;
        }
        if(!$inner)echo'</section>';
    }
    private function participants(\WP_Post$p,callable$g,ProductionRepository$r,string$kind,string$title):void{echo'<h3>'.esc_html($title).'</h3>';$release=$kind==='cast'?$g('cast_release'):$g('staff_release');if(!$this->released($release)){$this->placeholder('近日公開');return;}$rows=$r->participants($p->ID,$kind);if(!$rows){$this->placeholder($title.'は登録されていません。');return;}echo'<ul>';foreach($rows as$x){$n=esc_html($x['name']);if(!empty($x['member_id'])){$mem=(new MemberRepository())->find((int)$x['member_id']);if($mem&&$mem['status']==='published'&&$this->released($mem['release_at']??null))$n='<a href="'.esc_url(home_url('/member/'.rawurlencode($mem['slug']).'/')).'">'.$n.'</a>';}echo'<li>'.$n.($x['role']?'　'.esc_html($x['role']):'').'</li>';}echo'</ul>';}
}
