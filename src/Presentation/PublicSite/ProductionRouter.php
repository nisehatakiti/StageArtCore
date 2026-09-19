<?php

declare(strict_types=1);

namespace StageArtCore\Presentation\PublicSite;

use StageArtCore\Domain\Production\ProductionRepository;
use StageArtCore\Domain\Production\ProductionCreditRepository;
use StageArtCore\Domain\Member\MemberRepository;
use StageArtCore\Domain\Release\ReleaseDate;
use StageArtCore\Domain\Survey\SurveyRepository;
use StageArtCore\Presentation\Admin\FreeContentAdmin;

final class ProductionRouter
{
    public function register(): void
    {
        add_action('init', [$this, 'rewrite']);
        add_filter('query_vars', [$this, 'vars']);
        add_action('template_redirect', [$this, 'render'], 2);
    }

    public function rewrite(): void { add_rewrite_rule('^production/([^/]+)/?$', 'index.php?stageart_production_slug=$matches[1]', 'top'); }
    public function vars(array $v): array { $v[] = 'stageart_production_slug'; return $v; }
    private function released($v): bool { return ReleaseDate::isReleased($v ?: null); }
    private function placeholder(string $text): void { echo '<p class="stageart-release-placeholder">' . esc_html($text) . '</p>'; }
    private function mapsUrl(string $value): string { return preg_match('#^https?://#i', $value) ? $value : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($value); }
    private function performanceCell(array $x, string $display, string $marker): string { return !empty($x['symbol']) ? (string) $x['symbol'] : $marker; }
    private function performanceLegend(array $labels): void { if (!$labels) return; echo '<div class="stageart-performance-legend">'; foreach ($labels as $l) echo '<span>' . esc_html((string) $l['symbol'] . ' ' . $l['name']) . '</span>　'; echo '</div>'; }
    private function performanceTable(array $ps, array $labels, string $display, string $marker, string $legend): void
    {
        $dates=[];$times=[];$map=[];foreach($ps as$x){$d=(string)$x['performance_date'];$t=substr((string)$x['start_time'],0,5);$dates[$d]=true;$times[$t]=true;$map[$d][$t][]=$x;} $dates=array_keys($dates);$times=array_keys($times);sort($dates);sort($times);
        if($legend==='above')$this->performanceLegend($labels);echo '<div class="stageart-performance-table-wrap"><table class="stageart-performance-table"><thead><tr><th>開演</th>';foreach($dates as$d)echo '<th>'.esc_html(wp_date('n/j',strtotime($d))).'</th>';echo '</tr></thead><tbody>';foreach($times as$t){echo '<tr><th>'.esc_html($t).'</th>';foreach($dates as$d){echo '<td>';if(!empty($map[$d][$t]))foreach($map[$d][$t] as$x){$cell=$display==='none'?$marker:$this->performanceCell($x,$display,$marker);echo '<span class="stageart-performance-cell">'.esc_html($cell).'</span>';}echo '</td>';}echo '</tr>';}echo '</tbody></table></div>';if($legend==='below')$this->performanceLegend($labels);
    }
    private function performanceList(array $ps, string $display, string $marker): void
    {
        usort($ps,static function(array $a,array $b): int {
            $ad=(string)$a['performance_date'];$bd=(string)$b['performance_date'];
            return $ad===$bd?strcmp(substr((string)$a['start_time'],0,5),substr((string)$b['start_time'],0,5)):strcmp($ad,$bd);
        });
        echo '<div class="stageart-performance-list">';
        foreach($ps as$x){
            $date=(string)$x['performance_date'];$start=substr((string)$x['start_time'],0,5);
            $hasLabel=!empty($x['symbol']);$cell=$display==='none'||!$hasLabel?'':$this->performanceCell($x,$display,$marker);
            echo '<div class="stageart-performance-list-row"><span class="stageart-performance-list-date">'.esc_html(wp_date('n/j',strtotime($date))).'</span><span class="stageart-performance-list-time">'.esc_html($start).'</span><span class="stageart-performance-list-label">'.($cell!==''?esc_html($cell):'').'</span></div>';
        }
        echo '</div>';
    }
    private function performanceTimeline(array $ps, string $display, string $marker, string $style): void
    {
        $dates=[];$times=[];$map=[];foreach($ps as$x){$d=(string)$x['performance_date'];$t=substr((string)$x['start_time'],0,5);$dates[$d]=true;$times[$t]=true;$map[$d][$t][]=$x;}$dates=array_keys($dates);$times=array_keys($times);sort($dates);sort($times);$modifier=$style==='timeline_grid'?'stageart-performance-timeline--grid':'stageart-performance-timeline--line';echo '<div class="stageart-performance-timeline '.$modifier.'"><table><thead><tr><th></th>';foreach($dates as$d)echo '<th>'.esc_html(wp_date('n/j',strtotime($d))).'</th>';echo '</tr></thead><tbody>';foreach($times as$t){echo '<tr><th>'.esc_html($t).'</th>';foreach($dates as$d){$items=$map[$d][$t]??[];$hasMarker=!empty($items);echo '<td><span class="stageart-performance-timeline-line'.($hasMarker?' has-marker':'').'">';if($hasMarker){foreach($items as$x){$cell=$display==='none'?$marker:$this->performanceCell($x,$display,$marker);echo '<b>'.esc_html($cell).'</b>';}}echo '</span></td>';}echo '</tr>';}echo '</tbody></table></div>';
    }
    private function performanceView(array $ps,array $labels,string $display,string $marker,string $legend,string $view):void{
        if($legend==='above')$this->performanceLegend($labels);
        if($view==='list')$this->performanceList($ps,$display,$marker);
        elseif($view==='timeline_line'||$view==='timeline_grid')$this->performanceTimeline($ps,$display,$marker,$view);
        else $this->performanceTable($ps,$labels,$display,$marker,'none');
        if($legend==='below')$this->performanceLegend($labels);
    }

    public function render(): void
    {
        $slug=get_query_var('stageart_production_slug');if(!is_string($slug)||$slug==='')return;$r=new ProductionRepository();$q=new \WP_Query(['post_type'=>'stageart_production','name'=>$slug,'post_status'=>'publish','posts_per_page'=>1]);
        if(!$q->have_posts()){ $historical=$r->productionIdByHistoricalSlug($slug);if($historical){$old=get_post($historical);if($old&&$old->post_status==='publish'&&$old->post_name!==$slug){wp_safe_redirect(home_url('/production/'.rawurlencode($old->post_name).'/'),301);exit;}}global $wp_query;$wp_query->set_404();status_header(404);$t=get_404_template();if($t)include$t;exit; }
        $p=$q->posts[0];$m=get_post_meta($p->ID);$g=fn($k,$d='')=>(string)($m[$k][0]??$d);$theme=sanitize_key($g('presentation_theme','standard'));if(!in_array($theme,['standard','dark','light'],true))$theme='standard';$performanceSize=sanitize_key($g('performance_size','l'));if(!in_array($performanceSize,['l','m','s'],true))$performanceSize='l';$ticketSize=sanitize_key($g('ticket_size','l'));if(!in_array($ticketSize,['l','m','s'],true))$ticketSize='l';
        $c=new ProductionCreditRepository();get_header();
        echo '<style>.stageart-production-page{padding:0 0 100px}.stageart-production-hero{position:relative;min-height:500px;display:grid;grid-template-columns:minmax(0,1.1fr) minmax(320px,.9fr);align-items:stretch;overflow:hidden;background:var(--ink);color:#fff}.stageart-production-hero-copy{display:flex;flex-direction:column;justify-content:center;padding:80px max(30px,calc((100vw - 1180px)/2)) 80px max(30px,calc((100vw - 1180px)/2));padding-right:40px}.stageart-production-hero-kicker{margin:0 0 16px;color:var(--gold);font-size:.76rem;font-weight:700;letter-spacing:.25em;text-transform:uppercase}.stageart-production-hero-title{font-family:Georgia,"Yu Mincho",serif;font-size:clamp(2.8rem,6vw,6.2rem);font-weight:400;line-height:1.08;margin:0}.stageart-production-hero-summary{max-width:650px;margin:28px 0 0;color:#e2dbd2;font-size:1.05rem;white-space:pre-line}.stageart-production-hero-image{min-height:500px;background:var(--paper2);display:flex;align-items:center;justify-content:center;overflow:hidden}.stageart-production-hero-image img{width:100%;height:100%;object-fit:cover;display:block}.stageart-production-hero-image-placeholder{color:var(--muted);font-size:.9rem;letter-spacing:.12em}.stageart-production-content{width:min(calc(100% - 48px),1180px);margin:auto;padding-top:70px}.stageart-production-content-block{margin:0 0 72px}.stageart-production-content-block:last-child{margin-bottom:0}.stageart-production-layout-section{margin:0 0 56px}.stageart-production-layout-section>h2{font-family:Georgia,"Yu Mincho",serif;font-size:2.2rem;font-weight:400;margin:0 0 24px}.stageart-production-layout-slots--horizontal{display:grid;grid-template-columns:repeat(var(--stageart-layout-columns),minmax(0,1fr));gap:28px;align-items:stretch}.stageart-production-layout-slots--vertical{display:flex;flex-direction:column;gap:28px}.stageart-production-layout-slot{min-width:0;background:var(--white);border:1px solid var(--line);padding:28px}.stageart-production-layout-slot>h2,.stageart-production-layout-slot>h3{margin-top:0}.stageart-production-layout-slot--main_image{padding:0;overflow:hidden}.stageart-production-layout-slot--main_image img{width:100%;height:auto;display:block}.stageart-production-layout-slot--title{display:flex;align-items:center;min-height:180px}.stageart-production-layout-slot--title h1{font-family:Georgia,"Yu Mincho",serif;font-weight:400;font-size:clamp(3.2rem,6vw,5.2rem);line-height:1.12;margin:0}.stageart-production-layout-slot--title .stageart-production-crown{display:block;font-size:.95rem;letter-spacing:.16em;color:var(--muted);margin:0 0 14px;font-weight:600}.stageart-production-layout-slot--summary p{font-size:1.05rem;margin:0}.stageart-production-layout-slot--description{line-height:1.9}.stageart-production-layout-slot--performances,.stageart-production-layout-slot--tickets{width:100%;max-width:none}.stageart-production-layout-slot--venue,.stageart-production-layout-slot--cast,.stageart-production-layout-slot--staff,.stageart-production-layout-slot--credits,.stageart-production-layout-slot--survey{min-height:100%}.stageart-credit-list{margin:0;padding:0;list-style:none}.stageart-credit-list--horizontal{display:flex;flex-wrap:wrap;gap:10px 28px;align-items:baseline}.stageart-credit-list--horizontal li{margin:0}.stageart-credit-list--vertical li{margin:0 0 8px}@media(max-width:800px){.stageart-production-hero{grid-template-columns:1fr}.stageart-production-hero-copy{padding:65px 24px}.stageart-production-hero-image{min-height:320px}.stageart-production-content{width:min(calc(100% - 28px),1180px);padding-top:45px}.stageart-production-layout-slots--horizontal{grid-template-columns:1fr!important}.stageart-production-layout-slot{padding:22px}} </style>';
        echo '<main class="stageart-production stageart-production--'.esc_attr($theme).' stageart-production-page" data-stageart-presentation-theme="'.esc_attr($theme).'">';
        $heroEnabled = (bool) get_post_meta($p->ID, 'hero_image_type', true);
        $heroTitle = trim((string) get_post_meta($p->ID, 'hero_title', true));
        $heroDescription = trim((string) get_post_meta($p->ID, 'hero_description', true));
        if ($heroEnabled) {
            echo '<section class="stageart-production-hero"><div class="stageart-production-hero-copy"><h1 class="stageart-production-hero-title">'.esc_html($heroTitle !== '' ? $heroTitle : $p->post_title).'</h1>';
            if ($heroDescription !== '') echo '<p class="stageart-production-hero-summary">'.esc_html($heroDescription).'</p>';
            elseif ($this->released($g('summary_release')) && $g('summary')) echo '<p class="stageart-production-hero-summary">'.esc_html($g('summary')).'</p>';
            elseif (!$this->released($g('summary_release'))) echo '<p class="stageart-production-hero-summary">近日公開</p>';
            echo '</div><div class="stageart-production-hero-image">';
            $heroImageId=(int)$g('hero_image_id');$heroRelease=$g('hero_image_release');
            if($heroImageId&&$this->released($heroRelease))echo wp_get_attachment_image($heroImageId,'large');else echo '<span class="stageart-production-hero-image-placeholder">公演Hero画像</span>';
            echo '</div></section>';
        }
        echo '<div class="stageart-production-content"><article>';
        $layout=ProductionLayout::get($p->ID);foreach($layout as$block){echo '<div class="stageart-production-content-block">';foreach((array)($block['sections']??[])as$section){echo '<section class="stageart-production-layout-section">';if(!empty($section['heading']))echo '<h2>'.esc_html($section['heading']).'</h2>';echo '<div class="stageart-production-layout-slots stageart-production-layout-slots--'.esc_attr($section['layout']).'" style="--stageart-layout-columns:'.(int)$section['columns'].'">';foreach((array)$section['slots']as$slot){$type=(string)($slot['type']??'link');$ref=(string)($slot['ref']??'');if($type==='none'||!$ref)continue;if($type==='heading'){echo '<div class="stageart-production-layout-slot stageart-production-layout-slot--heading"><h3>'.esc_html($ref).'</h3></div>';continue;} $size='l';if($ref==='performances')$size=$performanceSize;if($ref==='tickets')$size=$ticketSize;echo '<div class="stageart-production-layout-slot stageart-production-layout-slot--'.esc_attr($ref).' stageart-size-'.esc_attr($size).'">';$this->renderBlock($ref,$p,$g,$r,$c,true);echo '</div>'; }echo '</div></section>';}echo '</div>';}
        echo '</article></div></main>';get_footer();exit;
    }

    private function renderBlock(string $id, \WP_Post $p, callable $g, ProductionRepository $r, ProductionCreditRepository $c, bool $inner=false): void
    {
        if(!$inner)echo '<section class="stageart-production-section stageart-production-section--'.esc_attr($id).'">';
        switch($id){
            case 'main_image':if($this->released($g('main_image_release'))&&$g('main_image_id'))echo wp_get_attachment_image((int)$g('main_image_id'),'large');break;
            case 'title':$crown=(string)$g('production_crown');echo '<div class="stageart-production-title-content">'.($crown!==''?'<span class="stageart-production-crown">'.esc_html($crown).'</span>':'').'<h1>'.esc_html($p->post_title).'</h1></div>';break;
            case 'summary':if($this->released($g('summary_release'))){if($g('summary'))echo '<p>'.esc_html($g('summary')).'</p> ';}else $this->placeholder('近日公開');break;
            case 'description':if(!$inner)echo '<h2>公演紹介</h2>';if($this->released($g('description_release'))){if($g('description'))echo wp_kses_post($g('description'));else $this->placeholder('公演紹介はありません。');}else $this->placeholder('近日公開');break;
            case 'schedule':if(!$inner)echo '<h2>公演日程</h2>';if($this->released($g('schedule_release'))){if($g('schedule_start')||$g('schedule_end'))echo '<p>'.esc_html($g('schedule_start')).($g('schedule_end')?' ～ '.esc_html($g('schedule_end')):'').'</p>';else $this->placeholder('公演日程は登録されていません。');}else $this->placeholder('近日公開');break;
            case 'performances':echo '<h2>公演スケジュール</h2>';$ps=$r->performances($p->ID);if(!$ps)$this->placeholder('現在登録されている公演スケジュールはありません。');else{$released=array_values(array_filter($ps,fn(array$x):bool=>$this->released($x['release_at']??null)));if(!$released)$this->placeholder('公演スケジュールは後日公開');else{$display=$g('use_labels','1')==='1'?$g('label_display','symbol'):'none';$view=sanitize_key($g('performance_view','table'));if(!in_array($view,['table','list','timeline_line','timeline_grid'],true))$view='table';$this->performanceView($released,$r->labels($p->ID),$display,$g('performance_marker','●'),$g('legend','none'),$view);}}break;
            case 'venue':echo '<h2>会場</h2>';if(!$this->released($g('venue_release')))$this->placeholder('近日公開');else{if($g('venue_name'))echo '<p>'.esc_html($g('venue_name')).'</p>';else $this->placeholder('会場は登録されていません。');if($g('venue_map')){if($this->released($g('venue_map_release')))echo '<p><a href="'.esc_url($this->mapsUrl($g('venue_map'))).'" target="_blank" rel="noopener">Google Mapsで見る</a></p>';else $this->placeholder('地図は後日公開');}}break;
            case 'cast':$this->participants($p,$g,$r,'cast','出演者');break;
            case 'staff':$this->participants($p,$g,$r,'staff','スタッフ');break;
            case 'tickets':echo '<h2>チケット料金</h2>';if(!$this->released($g('ticket_release')))$this->placeholder('料金は後日公開');else{$ts=$r->tickets($p->ID);if(!$ts)$this->placeholder('チケット料金は登録されていません。');else{echo '<ul class="stageart-ticket-list">';foreach($ts as$x){$tax=$g('tax_display','included')==='included'?'（税込）':($g('tax_display')==='excluded'?'（税別）':'');echo '<li>'.esc_html($x['description']).'：'.number_format($x['amount']).'円'.esc_html($tax).'</li>';}echo '</ul>';if($g('ticket_comment'))echo '<div class="stageart-ticket-comment">'.wp_kses_post(wpautop($g('ticket_comment'))).'</div>';}}break;
            case 'survey':echo '<h2>アンケート</h2>';$survey=(new SurveyRepository())->findByProduction($p->ID);if($survey&&(string)$survey['status']==='publish')echo '<p><a href="'.esc_url(home_url('/production/'.$p->post_name.'/'.rawurlencode((string)$survey['slug']).'/')).'">アンケートに回答する</a></p>';break;
            case 'free_content':
                $this->renderFreeContent((int) $id, $p);
                break;
            case 'credits':$creditLayout=$g('credit_layout','vertical');if(!in_array($creditLayout,['vertical','horizontal'],true))$creditLayout='vertical';foreach($c->sections($p->ID,true)as$s){if(!$s['items'])continue;if(!$inner)echo '<h2>'.esc_html($s['name']).'</h2>';echo '<h3>'.esc_html($s['name']).'</h3><ul class="stageart-credit-list stageart-credit-list--'.esc_attr($creditLayout).'">';foreach($s['items']as$x)echo '<li>'.($x['url']?'<a href="'.esc_url($x['url']).'" target="_blank" rel="noopener">'.esc_html($x['name']).'</a>':esc_html($x['name'])).'</li>';echo '</ul>';}break;
        }
        if(!$inner)echo '</section>';
    }
    private function renderFreeContent(int $id, \WP_Post $production): void
    {
        $fc = get_post($id);
        if (!$fc || $fc->post_type !== FreeContentAdmin::POST_TYPE || $fc->post_status !== 'publish') {
            return;
        }

        // ProductionRouter is the final public rendering boundary. Re-check
        // ownership here even if the layout data was already normalized.
        if (!FreeContentAdmin::canReference($fc->ID, $production->ID)) {
            return;
        }

        echo '<h2>' . esc_html($fc->post_title) . '</h2>';
        echo '<div class="stageart-free-content-body">' . wp_kses_post($fc->post_content) . '</div>';
    }

    private function participants(\WP_Post $p, callable $g, ProductionRepository $r, string $kind, string $title): void
    {echo '<h3>'.esc_html($title).'</h3>';$release=$kind==='cast'?$g('cast_release'):$g('staff_release');if(!$this->released($release)){$this->placeholder('近日公開');return;}$rows=$r->participants($p->ID,$kind);if(!$rows){$this->placeholder($title.'は登録されていません。');return;}echo '<ul>';foreach($rows as$x){$n=esc_html($x['name']);if(!empty($x['member_id'])){$mem=(new MemberRepository())->find((int)$x['member_id']);if($mem&&$mem['status']==='published'&&$this->released($mem['release_at']??null))$n='<a href="'.esc_url(home_url('/member/'.rawurlencode($mem['slug']).'/')).'">'.$n.'</a>';}echo '<li>'.$n.($x['role']?'　'.esc_html($x['role']):'').'</li>';}echo '</ul>';}
}
