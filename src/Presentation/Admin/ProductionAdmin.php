<?php

declare(strict_types=1);
namespace StageArtCore\Presentation\Admin;
use StageArtCore\Domain\Production\ProductionRepository;
use StageArtCore\Domain\Production\ProductionCreditRepository;
use StageArtCore\Domain\Member\MemberRepository;
use StageArtCore\Domain\Site\Theme;
final class ProductionAdmin{
 private ProductionRepository $repo;private ProductionCreditRepository $credits;
 public function __construct(){$this->repo=new ProductionRepository();$this->credits=new ProductionCreditRepository();add_action('admin_post_stageart_save_production',[$this,'save']);}
 public function register():void{add_action('admin_head',[$this,'creditStyles'],30);register_post_type('stageart_production',['labels'=>['name'=>'公演','singular_name'=>'公演','add_new'=>'公演を追加','edit_item'=>'公演を編集'],'public'=>false,'show_ui'=>false,'show_in_menu'=>false,'supports'=>['title'],'rewrite'=>false]);add_submenu_page('stageart-plugin','公演','公演','manage_options','stageart-productions',[$this,'render']);add_action('admin_enqueue_scripts',[$this,'assets']);}
 public function assets(string$hook):void{if(str_contains($hook,'stageart-productions')){wp_enqueue_media();wp_enqueue_editor();}}
 private function guard():void{if(!current_user_can('manage_options'))wp_die('権限がありません。');check_admin_referer('stageart_production_action');}
 private function utc(?string$v):?string{if(!$v)return null;$d=\DateTimeImmutable::createFromFormat('Y-m-d H:i',str_replace('T',' ',trim($v)),new \DateTimeZone('Asia/Tokyo'));return$d?$d->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'):null;}
 private function jst(?string$v):string{if(!$v)return'';try{$d=new \DateTimeImmutable($v,new \DateTimeZone('UTC'));return$d->setTimezone(new \DateTimeZone('Asia/Tokyo'))->format('Y-m-d\\TH:i');}catch(\Throwable){return'';}}
 private function meta(int$id,string$key,?string$value):void{if($value===null||$value==='')delete_post_meta($id,$key);else update_post_meta($id,$key,$value);}
 public function render():void{$id=(int)($_GET['id']??0);if(isset($_GET['new']))$id=0;if($id){$p=get_post($id);if(!$p||$p->post_type!=='stageart_production')$id=0;}echo'<div class="wrap"><h1>公演</h1>';if(isset($_GET['saved']))echo'<div class="notice notice-success is-dismissible"><p>保存しました。</p></div>';if($id||isset($_GET['new'])){$this->form($id);echo'</div>';return;}echo'<p><a class="button button-primary" href="'.esc_url(admin_url('admin.php?page=stageart-productions&new=1')).'">＋ 公演を追加</a></p><table class="widefat striped"><thead><tr><th>公演名</th><th>状態</th><th>URL</th><th>操作</th></tr></thead><tbody>';$posts=get_posts(['post_type'=>'stageart_production','post_status'=>['publish','draft','private'],'numberposts'=>-1,'orderby'=>'date','order'=>'DESC']);foreach($posts as$p)echo'<tr><td><strong><a href="'.esc_url(admin_url('admin.php?page=stageart-productions&id='.$p->ID)).'">'.esc_html($p->post_title).'</a></strong></td><td>'.esc_html($p->post_status==='publish'?'公開':'下書き').'</td><td><code>'.esc_html(home_url('/production/'.($p->post_name?:$p->ID).'/')).'</code></td><td><a href="'.esc_url(admin_url('admin.php?page=stageart-productions&id='.$p->ID)).'">編集</a></td></tr>';if(!$posts)echo'<tr><td colspan="4">公演はまだ登録されていません。</td></tr>';echo'</tbody></table></div>';}
 private function input(string$label,string$name,string$value,string$type='text'):void{echo'<tr><th><label for="sa-'.esc_attr($name).'">'.esc_html($label).'</label></th><td><input id="sa-'.esc_attr($name).'" class="regular-text" type="'.esc_attr($type).'" name="'.esc_attr($name).'" value="'.esc_attr($value).'" /></td></tr>';}
 private function form(int$id):void{$p=$id?get_post($id):null;$m=get_post_meta($id);$g=fn($k,$d='')=>(string)($m[$k][0]??$d);$members=(new MemberRepository())->all(false);$parts=$this->repo->participants($id);$cast=array_values(array_filter($parts,fn($x)=>$x['kind']==='cast'));$staff=array_values(array_filter($parts,fn($x)=>$x['kind']==='staff'));$labels=$this->repo->labels($id);$perfs=$this->repo->performances($id);$tickets=$this->repo->tickets($id);$sections=$this->credits->sections($id,false);$used=[];foreach($perfs as$x)if(!empty($x['label_id']))$used[(int)$x['label_id']]=($used[(int)$x['label_id']]??0)+1;$creditLayout=in_array($g('credit_layout','vertical'),['vertical','horizontal'],true)?$g('credit_layout','vertical'):'vertical';$themeSaved=get_post_meta($id,Theme::PRODUCTION_META,true);$themeSaved=is_array($themeSaved)?$themeSaved:[];$themePreset=(string)($themeSaved['preset']??'light_standard');$themeCustom=array_merge(Theme::customDefaults(),is_array($themeSaved['custom']??null)?$themeSaved['custom']:[]);$themeHtml='<h3>テーマ</h3><table class="form-table"><tr><th>テーマ</th><td><select name="production_theme[preset]">';foreach(Theme::presets('production') as$tk=>$tv)$themeHtml.='<option value="'.esc_attr($tk).'"'.selected($themePreset,$tk,false).'>'.esc_html($tv['label']).'</option>';$themeHtml.='</select><p class="description">この公演ページに適用するテーマです。</p></td></tr><tr><th>カスタム</th><td><label><input type="checkbox" name="production_theme[mode]" value="custom"'.checked(($themeSaved['mode']??'preset'),'custom',false).'> カスタム配色・フォントを使用する</label></td></tr></table><h4>カスタム配色</h4><table class="form-table">';foreach(['bg'=>'背景色','surface'=>'カード・面の色','text'=>'本文色','primary'=>'メイン色','accent'=>'アクセント色','border'=>'境界線色']as$tk=>$tl)$themeHtml.='<tr><th>'.esc_html($tl).'</th><td><input type="color" name="production_theme[custom]['.esc_attr($tk).']" value="'.esc_attr($themeCustom[$tk]).'"></td></tr>';foreach(['heading_font'=>'見出しフォント','body_font'=>'本文フォント']as$tk=>$tl){$themeHtml.='<tr><th>'.esc_html($tl).'</th><td><select name="production_theme[custom]['.esc_attr($tk).']">';foreach(['system'=>'システム標準','sans'=>'ゴシック系','serif'=>'明朝・セリフ系']as$fk=>$fl)$themeHtml.='<option value="'.esc_attr($fk).'"'.selected($themeCustom[$tk],$fk,false).'>'.esc_html($fl).'</option>';$themeHtml.='</select></td></tr>';}$themeHtml.='</table>';echo'<form id="stageart-production-form" method="post" action="'.esc_url(admin_url('admin-post.php')).'">';wp_nonce_field('stageart_production_action');echo'<input type="hidden" name="action" value="stageart_save_production"><input type="hidden" name="id" value="'.(int)$id.'"><h2>基本情報</h2><table class="form-table">';$this->input('公演名','title',$p?$p->post_title:'');$this->input('Slug','slug',$p?$p->post_name:'');echo'<tr><th>メイン画像</th><td><input type="hidden" id="sa-main-image-id" name="main_image_id" value="'.esc_attr($g('main_image_id')).'"><button type="button" class="button" id="sa-media-picker">画像を選択</button> <button type="button" class="button" id="sa-media-clear">クリア</button><div id="sa-media-preview" style="margin-top:8px">'.($g('main_image_id')?wp_get_attachment_image((int)$g('main_image_id'),'medium'):'').'</div></td></tr><tr><th>メイン画像 情報解禁</th><td><input type="datetime-local" name="main_image_release" value="'.esc_attr($this->jst($g('main_image_release'))).'" /></td></tr>';$this->input('概要','summary',$g('summary'));echo'<tr><th>概要 情報解禁</th><td><input type="datetime-local" name="summary_release" value="'.esc_attr($this->jst($g('summary_release'))).'" /></td></tr><tr><th>公演紹介</th><td>';wp_editor($g('description'),'stageart_production_description',['textarea_name'=>'description','textarea_rows'=>10,'media_buttons'=>true]);echo'</td></tr><tr><th>紹介 情報解禁</th><td><input type="datetime-local" name="description_release" value="'.esc_attr($this->jst($g('description_release'))).'" /></td></tr><tr><th>公開状態</th><td><select name="post_status"><option value="draft" '.selected($p->post_status??'draft','draft',false).'>下書き</option><option value="publish" '.selected($p->post_status??'draft','publish',false).'>公開</option></select></td></tr></table><h2>公演日程</h2><table class="form-table">';$this->input('開始日','schedule_start',$g('schedule_start'),'date');$this->input('終了日','schedule_end',$g('schedule_end'),'date');echo'<tr><th>情報解禁</th><td><input type="datetime-local" name="schedule_release" value="'.esc_attr($this->jst($g('schedule_release'))).'" /></td></tr></table><h2>公演スケジュール</h2><table class="form-table"><tr><th>情報解禁</th><td><input type="datetime-local" name="performance_release" value="'.esc_attr($this->jst($g('performance_release'))).'" /></td></tr><tr><th>表示方式</th><td><select name="performance_view"><option value="table" '.selected($g('performance_view','table'),'table',false).'>表形式</option><option value="list" '.selected($g('performance_view'),'list',false).'>一覧形式</option><option value="timeline_line" '.selected($g('performance_view'),'timeline_line',false).'>タイムライン（線）</option><option value="timeline_grid" '.selected($g('performance_view'),'timeline_grid',false).'>タイムライン（区切り）</option></select></td></tr><tr><th>表示サイズ</th><td><label><input type="radio" name="performance_size" value="l" '.checked($g('performance_size','l'),'l',false).'> L</label> <label><input type="radio" name="performance_size" value="m" '.checked($g('performance_size','l'),'m',false).'> M</label> <label><input type="radio" name="performance_size" value="s" '.checked($g('performance_size','l'),'s',false).'> S</label></td></tr><tr><th>ラベル</th><td><label><input type="checkbox" name="use_labels" value="1" '.checked($g('use_labels','1'),'1',false).'> 公演スケジュールラベルを使用する</label></td></tr><tr><th>表示形式</th><td><select name="label_display"><option value="symbol" '.selected($g('label_display','symbol'),'symbol',false).'>記号のみ</option></select></td></tr><tr><th>凡例</th><td><select name="legend"><option value="none" '.selected($g('legend','none'),'none',false).'>表示しない</option><option value="above" '.selected($g('legend'),'above',false).'>公演スケジュール表の上</option><option value="below" '.selected($g('legend'),'below',false).'>公演スケジュール表の下</option></select></td></tr><tr><th>ラベル未使用時マーカー</th><td><input name="performance_marker" value="'.esc_attr($g('performance_marker','●')).'" maxlength="10" /></td></tr></table><h3>公演スケジュールラベル</h3><input type="hidden" name="labels_form_present" value="1"><input type="hidden" name="labels_json" id="sa-labels-json" value=""><div id="sa-label-deletions"></div><table class="widefat" id="sa-labels"><thead><tr><th>記号</th><th>ラベル</th><th>使用中</th><th></th></tr></thead><tbody>';foreach($labels as$i=>$l){$n=$used[(int)$l['id']]??0;echo'<tr class="sa-label-row" data-used="'.(int)$n.'"><td><input type="hidden" name="labels['.$i.'][id]" value="'.(int)$l['id'].'"><input name="labels['.$i.'][symbol]" value="'.esc_attr($l['symbol']).'" required></td><td><input name="labels['.$i.'][name]" value="'.esc_attr($l['name']).'"></td><td>'.($n?'公演スケジュール '.$n.' 件':'未使用').'</td><td><button type="button" class="button-link-delete sa-remove-row">削除</button></td></tr>';}echo'</tbody></table><p><button type="button" class="button" data-add="label">＋ ラベルを追加</button></p><h3>公演スケジュール</h3><table class="widefat" id="sa-performances"><thead><tr><th>開催日</th><th>開演</th><th>終演予定</th><th>ラベル</th><th></th></tr></thead><tbody>';foreach($perfs as$i=>$x)$this->performanceRow($i,$x,$labels);echo'</tbody></table><p><button type="button" class="button" data-add="performance">＋ 公演スケジュールを追加</button></p><h2>会場</h2><table class="form-table">';$this->input('会場名','venue_name',$g('venue_name'));echo'<tr><th>会場 情報解禁</th><td><input type="datetime-local" name="venue_release" value="'.esc_attr($this->jst($g('venue_release'))).'" /></td></tr>';$this->input('Google Maps検索名/URL','venue_map',$g('venue_map'));echo'<tr><th>Google Maps 情報解禁</th><td><input type="datetime-local" name="venue_map_release" value="'.esc_attr($this->jst($g('venue_map_release'))).'" /></td></tr></table>';$this->participantsForm('出演者',$cast,$members,'cast',$g('cast_release'));$this->participantsForm('スタッフ',$staff,$members,'staff',$g('staff_release'));echo'<h2>チケット料金</h2><table class="form-table"><tr><th>表示サイズ</th><td><label><input type="radio" name="ticket_size" value="l" '.checked($g('ticket_size','l'),'l',false).'> L</label> <label><input type="radio" name="ticket_size" value="m" '.checked($g('ticket_size','l'),'m',false).'> M</label> <label><input type="radio" name="ticket_size" value="s" '.checked($g('ticket_size','l'),'s',false).'> S</label></td></tr><tr><th>情報解禁</th><td><input type="datetime-local" name="ticket_release" value="'.esc_attr($this->jst($g('ticket_release'))).'" /></td></tr><tr><th>税表示</th><td><select name="tax_display"><option value="included" '.selected($g('tax_display','included'),'included',false).'>税込</option><option value="excluded" '.selected($g('tax_display'),'excluded',false).'>税別</option><option value="none" '.selected($g('tax_display'),'none',false).'>表示しない</option></select></td></tr></table><table class="widefat" id="sa-tickets"><thead><tr><th>チケット種別</th><th>金額（円）</th><th>予約画面に表示</th><th></th></tr></thead><tbody>';foreach($tickets as$i=>$t)$this->ticketRow($i,$t);echo'</tbody></table><p><button type="button" class="button" data-add="ticket">＋ チケット料金を追加</button></p><textarea class="large-text" rows="5" name="ticket_comment" placeholder="チケットに関するコメント">'.esc_textarea($g('ticket_comment')).'</textarea><div id="stageart-production-display-settings" style="display:none"><div id="stageart-production-hero-mount"></div>'.$themeHtml.'<h3>コンテンツ配置</h3><div id="stageart-production-layout-mount"></div></div><h2>公演クレジット</h2><p class="description">公演ページに掲載する協賛先・協力先・後援などを「区分」ごとに登録します。出演者・スタッフは上の専用設定で管理するため、ここには登録しません。</p><table class="form-table"><tr><th>項目の表示形式</th><td><label><input type="radio" name="credit_layout" value="vertical" '.checked($creditLayout,'vertical',false).'> 縦並び</label> <label><input type="radio" name="credit_layout" value="horizontal" '.checked($creditLayout,'horizontal',false).'> 横並び</label><p class="description">公演ページの各クレジット区分内で、項目を縦または横に並べて表示します。</p></td></tr></table><div id="sa-credits">';foreach($sections as$i=>$s)$this->creditSection($i,$s);echo'</div><p><button type="button" class="button" data-add="credit">＋ 区分を追加</button></p><p><button class="button button-primary button-large" type="submit">保存</button> <a class="button" href="'.esc_url(admin_url('admin.php?page=stageart-productions')).'">キャンセル</a></p></form>';$this->scripts($labels);}
 private function performanceRow(int$i,array$x,array$labels):void{echo'<tr><td><input type="hidden" name="performances['.$i.'][id]" value="'.(int)($x['id']??0).'"><input type="date" name="performances['.$i.'][date]" value="'.esc_attr($x['performance_date']??'').'"></td><td><input type="time" name="performances['.$i.'][start]" value="'.esc_attr(substr((string)($x['start_time']??''),0,5)).'"></td><td><input type="time" name="performances['.$i.'][end]" value="'.esc_attr($x['end_time']?substr($x['end_time'],0,5):'').'"></td><td><select name="performances['.$i.'][label_id]"><option value="">なし</option>';foreach($labels as$l)echo'<option value="'.(int)$l['id'].'" '.selected((int)($x['label_id']??0),(int)$l['id'],false).'>'.esc_html($l['symbol'].' '.$l['name']).'</option>';echo'</select></td><td><button type="button" class="button-link-delete sa-remove-row">削除</button></td></tr>';}
 private function participantsForm(string$title,array$rows,array$members,string$kind,string$release):void{echo'<h2>'.esc_html($title).'</h2><p>情報解禁 <input type="datetime-local" name="'.esc_attr($kind).'_release" value="'.esc_attr($this->jst($release)).'" /></p><table class="widefat sa-participants" data-kind="'.esc_attr($kind).'" data-next="'.count($rows).'"><thead><tr><th>表示名</th><th>役割</th><th>メンバー</th><th>外部ユーザーID（任意）</th><th></th></tr></thead><tbody>';foreach($rows as$i=>$r)$this->participantRow($i,$r,$members,$kind);echo'</tbody></table><p><button type="button" class="button" data-add="participant" data-kind="'.esc_attr($kind).'">＋ 追加</button></p>';}
 private function participantRow(int$i,array$r,array$members,string$kind):void{echo'<tr><td><input type="hidden" name="participants['.$kind.']['.$i.'][id]" value="'.(int)($r['id']??0).'"><input name="participants['.$kind.']['.$i.'][name]" value="'.esc_attr($r['name']??'').'"></td><td><input name="participants['.$kind.']['.$i.'][role]" value="'.esc_attr($r['role']??'').'"></td><td><select name="participants['.$kind.']['.$i.'][member_id]"><option value="0">未紐付け</option>';foreach($members as$m)echo'<option value="'.(int)$m['id'].'" '.selected((int)($r['member_id']??0),(int)$m['id'],false).'>'.esc_html($m['name']).'</option>';echo'</select></td><td><input type="number" min="1" name="participants['.$kind.']['.$i.'][auth_user_id]" value="'.esc_attr((string)($r['auth_user_id']??'')).'"></td><td><button type="button" class="button-link-delete sa-remove-row">削除</button></td></tr>';}
 private function ticketRow(int$i,array$x):void{echo'<tr><td><input type="hidden" name="tickets['.$i.'][id]" value="'.(int)($x['id']??0).'"><input name="tickets['.$i.'][description]" value="'.esc_attr($x['description']??'').'"></td><td><input type="number" min="0" name="tickets['.$i.'][amount]" value="'.(int)($x['amount']??0).'" /></td><td><label><input type="checkbox" name="tickets['.$i.'][show_on_reservation]" value="1" '.checked(!empty($x['show_on_reservation']),true,false).'> 表示</label></td><td><button type="button" class="button-link-delete sa-remove-row">削除</button></td></tr>';}
 private function creditSection(int $i,array $section):void
 {
  $releaseEnabled=!empty($section['release_at']);
  echo '<div class="sa-credit" data-index="'.(int)$i.'">';
  echo '<p><input type="hidden" data-credit-field="id" name="credits['.(int)$i.'][id]" value="'.(int)$section['id'].'">';
  echo '<input data-credit-field="name" name="credits['.(int)$i.'][name]" value="'.esc_attr($section['name']).'" placeholder="例：協賛">';
  echo ' <label><input type="checkbox" class="sa-credit-release-enabled" data-credit-field="release_enabled" name="credits['.(int)$i.'][release_enabled]" value="1" '.checked($releaseEnabled,true,false).'> 情報解禁日を設定する</label>';
  echo ' <input type="datetime-local" class="sa-credit-release-at" data-credit-field="release_at" name="credits['.(int)$i.'][release_at]" value="'.esc_attr($this->jst($section['release_at']??'')).'" '.disabled(!$releaseEnabled,true,false).'>';
  echo ' <button type="button" class="button-link-delete sa-remove-credit">区分を削除</button></p>';
  echo '<div class="sa-credit-items"><div class="sa-credit-item-list">';
  foreach($section['items'] as $j=>$item){
   $itemName=trim((string)($item['name']??''));
   if($itemName==='') continue;
   $itemId=(int)($item['id']??0);
   $itemKey=$itemId>0?(string)$itemId:'legacy_'.(int)$i.'_'.(int)$j;
   echo '<div class="sa-credit-item" data-credit-item-key="'.esc_attr($itemKey).'"><input type="text" data-credit-item-field="name" name="credits['.(int)$i.'][items]['.esc_attr($itemKey).'][name]" value="'.esc_attr($itemName).'" placeholder="名称"><input type="url" data-credit-item-field="url" name="credits['.(int)$i.'][items]['.esc_attr($itemKey).'][url]" value="'.esc_attr($item['url']??'').'" placeholder="リンクURL（任意）"><button type="button" class="button-link-delete sa-remove-item">削除</button></div>';
  }
  echo '</div><p class="sa-credit-item-add"><button type="button" class="button" data-add-credit-item="1">＋ 項目を追加</button></p></div></div>';
 }
 public function creditStyles():void{if(($_GET['page']??'')==='stageart-productions')echo '<style>
.sa-credit-items{margin:10px 0}.sa-credit-item-list{display:flex;flex-direction:column;gap:8px}.sa-credit-item{display:flex;align-items:center;gap:8px}.sa-credit-item input:not([type=hidden]){width:min(520px,100%)}.sa-credit-item input[data-credit-item-field="url"]{width:min(520px,100%)}.sa-credit-item .sa-remove-item{white-space:nowrap}.sa-credit-item-add{margin-top:8px}
</style>';}
 private function scripts(array $labels):void
 {
  echo '<style>
  #stageart-production-tabs{display:flex;gap:0;margin:20px 0 0;border-bottom:1px solid #c3c4c7}
  #stageart-production-tabs button{border:1px solid #c3c4c7;border-bottom:0;background:#f6f7f7;padding:10px 18px;font-weight:600;cursor:pointer;margin-right:4px;border-radius:4px 4px 0 0}
  #stageart-production-tabs button.is-active{background:#fff;color:#2271b1;box-shadow:0 -1px 0 #fff}
  .sa-production-pane{background:#fff;padding:20px;border:1px solid #c3c4c7;border-top:0;margin-bottom:20px}
  #stageart-production-display-settings{display:block}
  #stageart-production-display-settings .sa-production-display-card{padding:16px 0;border-bottom:1px solid #ddd}
  #stageart-production-display-settings .sa-production-display-card:last-child{border-bottom:0}
  </style>';
  echo '<div id="stageart-production-tabs" role="tablist"><button type="button" data-sa-tab="basic">基本情報</button><button type="button" data-sa-tab="content">コンテンツ管理</button><button type="button" data-sa-tab="display">表示管理</button></div>';
  echo '<script>
  document.addEventListener("DOMContentLoaded",function(){
    setTimeout(function(){
      const form=document.getElementById("stageart-production-form");if(!form)return;
      const tabs=document.getElementById("stageart-production-tabs");if(!tabs)return;
      const panes={basic:document.createElement("div"),content:document.createElement("div"),display:document.createElement("div")};
      Object.keys(panes).forEach(function(k){panes[k].className="sa-production-pane";panes[k].dataset.saPane=k;});
      const children=Array.from(form.children),skip=new Set(["stageart-production-tabs"]);
      let group="basic";
      children.forEach(function(el){
        if(el===tabs||el.tagName==="SCRIPT"||el.tagName==="STYLE")return;
        if(el.tagName==="H2"){
          const t=el.textContent.trim();
          if(t==="公演スケジュール"||t==="公演クレジット")group="content";
        }
        if(el.id==="stageart-production-display-settings"){panes.display.appendChild(el);group="content";return;}
        if(el.id==="stageart-production-layout-mount"){panes.display.appendChild(el);return;}
        if(group==="content"&&el.id==="stageart-production-layout-mount")return;
        panes[group].appendChild(el);
      });
      form.insertBefore(panes.basic,tabs.nextSibling);
      form.insertBefore(panes.content,panes.basic.nextSibling);
      form.insertBefore(panes.display,panes.content.nextSibling);
      function moveHero(){
        const mount=document.getElementById("stageart-production-hero-mount");
        if(!mount)return;
        const hero=document.getElementById("stageart-production-hero-settings");
        if(hero&&!mount.contains(hero)){
          const row=hero.closest("tr");
          if(row){const wrap=document.createElement("div");wrap.className="sa-production-display-card";wrap.appendChild(row);mount.appendChild(wrap);}
          else mount.appendChild(hero);
        }
      }
      moveHero();
      setTimeout(moveHero,50);
      function show(key){
        Object.keys(panes).forEach(function(k){panes[k].style.display=k===key?"block":"none";});
        tabs.querySelectorAll("button").forEach(function(b){b.classList.toggle("is-active",b.dataset.saTab===key);});
        try{localStorage.setItem("stageart-production-tab",key);}catch(e){}
      }
      tabs.querySelectorAll("button").forEach(function(b){b.addEventListener("click",function(){show(b.dataset.saTab);});});
      let initial="basic";try{initial=localStorage.getItem("stageart-production-tab")||"basic";}catch(e){}
      if(!panes[initial])initial="basic";show(initial);
    },0);
  });
  </script>';
  $labelOptions='';
  foreach($labels as $l)$labelOptions.='<option value="'.(int)$l['id'].'">'.esc_html($l['symbol'].' '.$l['name']).'</option>';
  $script=<<<JS
<script>
(function(){
 const f=document.getElementById('stageart-production-form');if(!f||f.dataset.saProductionScriptsBound==='1')return;f.dataset.saProductionScriptsBound='1';
 let labelN=document.querySelectorAll('#sa-labels tbody tr').length;
 let perfN=document.querySelectorAll('#sa-performances tbody tr').length;
 let ticketN=document.querySelectorAll('#sa-tickets tbody tr').length;
 let creditItemN=0;
 f.addEventListener('submit',function(){
  document.querySelectorAll('#sa-credits .sa-credit').forEach(function(box,si){
   box.dataset.index=String(si);
   const sectionId=box.querySelector('input[data-credit-field="id"]');
   const sectionName=box.querySelector('input[data-credit-field="name"]');
   const releaseEnabled=box.querySelector('input[data-credit-field="release_enabled"]');
   const releaseAt=box.querySelector('input[data-credit-field="release_at"]');
   if(sectionId)sectionId.name='credits['+si+'][id]';
   if(sectionName)sectionName.name='credits['+si+'][name]';
   if(releaseEnabled)releaseEnabled.name='credits['+si+'][release_enabled]';
   if(releaseAt)releaseAt.name='credits['+si+'][release_at]';
   box.querySelectorAll('.sa-credit-item').forEach(function(item){
    const key=item.dataset.creditItemKey;
    const itemName=item.querySelector('input[data-credit-item-field="name"]');
    const itemUrl=item.querySelector('input[data-credit-item-field="url"]');
    if(!key)return;
    if(itemName)itemName.name='credits['+si+'][items]['+key+'][name]';
    if(itemUrl)itemUrl.name='credits['+si+'][items]['+key+'][url]';
   });
  });
 });
 f.addEventListener('click',function(e){
  const remove=e.target.closest('.sa-remove-row');
  if(remove){remove.closest('tr')?.remove();return;}
  const add=e.target.closest('[data-add]');
  if(add){
   const type=add.dataset.add;
   if(type==='label'){
    const key='new'+labelN++;
    const tr=document.createElement('tr');tr.className='sa-label-row';tr.dataset.newKey=key;
    tr.innerHTML='<td><input name="labels['+key+'][symbol]" required></td><td><input name="labels['+key+'][name]"></td><td>未使用</td><td><button type="button" class="button-link-delete sa-remove-row">削除</button></td>';
    document.querySelector('#sa-labels tbody').appendChild(tr);
   }
   if(type==='performance'){
    const i=perfN++;const tr=document.createElement('tr');
    tr.innerHTML='<td><input type="hidden" name="performances['+i+'][id]" value="0"><input type="date" name="performances['+i+'][date]"></td><td><input type="time" name="performances['+i+'][start]"></td><td><input type="time" name="performances['+i+'][end]"></td><td><select name="performances['+i+'][label_id]"><option value="">なし</option>'+LABEL_OPTIONS+'</select></td><td><button type="button" class="button-link-delete sa-remove-row">削除</button></td>';
    document.querySelector('#sa-performances tbody').appendChild(tr);
   }
   if(type==='ticket'){
    const i=ticketN++;const tr=document.createElement('tr');
    tr.innerHTML='<td><input type="hidden" name="tickets['+i+'][id]" value="0"><input name="tickets['+i+'][description]"></td><td><input type="number" min="0" name="tickets['+i+'][amount]" value="0"></td><td><label><input type="checkbox" name="tickets['+i+'][show_on_reservation]" value="1" checked> 表示</label></td><td><button type="button" class="button-link-delete sa-remove-row">削除</button></td>';
    document.querySelector('#sa-tickets tbody').appendChild(tr);
   }
   if(type==='participant'){
    const kind=add.dataset.kind,n=document.querySelectorAll('.sa-participants[data-kind="'+kind+'"] tbody tr').length;
    const source=document.querySelector('.sa-participants[data-kind="'+kind+'"] select');
    const memberOptions=source?source.innerHTML:'<option value="0">未紐付け</option>';
    const tr=document.createElement('tr');
    tr.innerHTML='<td><input type="hidden" name="participants['+kind+']['+n+'][id]" value="0"><input name="participants['+kind+']['+n+'][name]"></td><td><input name="participants['+kind+']['+n+'][role]"></td><td><select name="participants['+kind+']['+n+'][member_id]">'+memberOptions+'</select></td><td><input type="number" min="1" name="participants['+kind+']['+n+'][auth_user_id]"></td><td><button type="button" class="button-link-delete sa-remove-row">削除</button></td>';
    document.querySelector('.sa-participants[data-kind="'+kind+'"] tbody').appendChild(tr);
   }
  }
  if(add&&add.dataset.add==='credit'){
   const credits=document.getElementById('sa-credits'),idx=credits.querySelectorAll('.sa-credit').length;
   const box=document.createElement('div');box.className='sa-credit';box.dataset.index=String(idx);
   box.innerHTML='<p><input type="hidden" data-credit-field="id" name="credits['+idx+'][id]" value="0"><input data-credit-field="name" name="credits['+idx+'][name]" placeholder="例：協賛"> <label><input type="checkbox" class="sa-credit-release-enabled" data-credit-field="release_enabled" name="credits['+idx+'][release_enabled]" value="1"> 情報解禁日を設定する</label> <input type="datetime-local" class="sa-credit-release-at" data-credit-field="release_at" name="credits['+idx+'][release_at]" disabled> <button type="button" class="button-link-delete sa-remove-credit">区分を削除</button></p><div class="sa-credit-items"><div class="sa-credit-item-list"></div><p><button type="button" class="button" data-add-credit-item="1">＋ 項目を追加</button></p></div></div>';
   credits.appendChild(box);
  }
  const addItem=e.target.closest('[data-add-credit-item]');
  if(addItem){
   e.preventDefault();
   const box=addItem.closest('.sa-credit');
   const list=box?box.querySelector('.sa-credit-item-list'):null;
   if(!box||!list)return;
   const idx=box.dataset.index||'0';
   const key='new_'+(creditItemN++);
   const item=document.createElement('div');
   item.className='sa-credit-item';
   item.dataset.creditItemKey=key;
   item.innerHTML='<input type="text" data-credit-item-field="name" value="" placeholder="名称"><input type="url" data-credit-item-field="url" value="" placeholder="リンクURL（任意）"><button type="button" class="button-link-delete sa-remove-item">削除</button>';
   list.appendChild(item);
   const itemName=item.querySelector('input[data-credit-item-field="name"]');
   const itemUrl=item.querySelector('input[data-credit-item-field="url"]');
   if(itemName)itemName.name='credits['+idx+'][items]['+key+'][name]';
   if(itemUrl)itemUrl.name='credits['+idx+'][items]['+key+'][url]';
   if(itemName)itemName.focus();
   return;
  }
  const removeCredit=e.target.closest('.sa-remove-credit');if(removeCredit){removeCredit.closest('.sa-credit')?.remove();return;}
  const removeItem=e.target.closest('.sa-remove-item');if(removeItem){removeItem.closest('.sa-credit-item')?.remove();return;}
  const release=e.target.closest('.sa-credit-release-enabled');
  if(release){const date=release.closest('.sa-credit').querySelector('.sa-credit-release-at');if(date){date.disabled=!release.checked;if(!release.checked)date.value='';}}
  const picker=e.target.closest('#sa-media-picker');
  if(picker){const frame=wp.media({title:'画像を選択',button:{text:'使用する'},multiple:false});frame.on('select',function(){const x=frame.state().get('selection').first().toJSON();document.getElementById('sa-main-image-id').value=x.id;document.getElementById('sa-media-preview').innerHTML='<img src="'+x.url+'" style="max-width:180px;height:auto">';});frame.open();}
  const clear=e.target.closest('#sa-media-clear');if(clear){document.getElementById('sa-main-image-id').value='';document.getElementById('sa-media-preview').innerHTML='';}
 });
 document.querySelectorAll('.sa-credit-release-enabled').forEach(function(cb){
  const date=cb.closest('.sa-credit').querySelector('.sa-credit-release-at');if(date)date.disabled=!cb.checked;
 });
})();
</script>
JS;
  $script=str_replace('LABEL_OPTIONS',wp_json_encode($labelOptions,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$script);
  echo $script;
 }
 public function save():void{$this->guard();$id=(int)($_POST['id']??0);$title=sanitize_text_field(wp_unslash($_POST['title']??''));if($title==='')wp_die('公演名を入力してください。');$requested=sanitize_title(wp_unslash($_POST['slug']??''));if($requested==='')$requested=sanitize_title($title);$existing=$id?get_post($id):null;if($existing&&$existing->post_type!=='stageart_production')wp_die('不正な公演です。');$oldSlug=$existing?(string)$existing->post_name:'';$historical=$this->repo->productionIdByHistoricalSlug($requested);if($historical&&$historical!==$id){$base=$requested;$n=2;while($this->repo->productionIdByHistoricalSlug($requested))$requested=$base.'-'.$n++;}$post=['post_type'=>'stageart_production','post_title'=>$title,'post_status'=>($_POST['post_status']??'draft')==='publish'?'publish':'draft','post_name'=>$requested];$id=$id?wp_update_post(array_merge(['ID'=>$id],$post),true):wp_insert_post($post,true);if(is_wp_error($id))wp_die(esc_html($id->get_error_message()));$id=(int)$id;if($oldSlug&&$oldSlug!==$requested)$this->repo->addSlugHistory($id,$oldSlug);$this->repo->addSlugHistory($id,$requested);foreach(['main_image_id','summary','description','schedule_start','schedule_end','venue_name','venue_map','performance_marker','ticket_comment']as$k){$v=isset($_POST[$k])?wp_unslash($_POST[$k]):'';$this->meta($id,$k,$k==='description'?wp_kses_post((string)$v):sanitize_textarea_field((string)$v));}if(isset($_POST['main_image_id']))update_post_meta($id,'main_image_id',max(0,(int)$_POST['main_image_id']));foreach(['main_image_release','summary_release','description_release','schedule_release','performance_release','venue_release','venue_map_release','cast_release','staff_release','ticket_release']as$k)$this->meta($id,$k,$this->utc(isset($_POST[$k])?(string)wp_unslash($_POST[$k]):null));update_post_meta($id,'use_labels',!empty($_POST['use_labels'])?'1':'0');update_post_meta($id,'label_display',in_array($_POST['label_display']??'symbol',['symbol','both'],true)?$_POST['label_display']:'symbol');update_post_meta($id,'legend',in_array($_POST['legend']??'none',['none','above','below'],true)?$_POST['legend']:'none');$performanceView=sanitize_key(wp_unslash($_POST['performance_view']??'table'));if(!in_array($performanceView,['table','list','timeline_line','timeline_grid'],true))$performanceView='table';update_post_meta($id,'performance_view',$performanceView);$performanceSize=sanitize_key(wp_unslash($_POST['performance_size']??'l'));if(!in_array($performanceSize,['l','m','s'],true))$performanceSize='l';update_post_meta($id,'performance_size',$performanceSize);$creditLayout=sanitize_key(wp_unslash($_POST['credit_layout']??'vertical'));if(!in_array($creditLayout,['vertical','horizontal'],true))$creditLayout='vertical';update_post_meta($id,'credit_layout',$creditLayout);$productionTheme=is_array($_POST['production_theme']??null)?wp_unslash($_POST['production_theme']):[];$themeMode=($productionTheme['mode']??'preset')==='custom'?'custom':'preset';$themePreset=sanitize_key((string)($productionTheme['preset']??'light_standard'));$themePresets=Theme::presets('production');if(!isset($themePresets[$themePreset]))$themePreset='light_standard';$themeCustom=Theme::customDefaults();foreach($themeCustom as$tk=>$tv){if(str_ends_with($tk,'_font'))$themeCustom[$tk]=sanitize_text_field((string)($productionTheme['custom'][$tk]??$tv));else{$candidate=sanitize_hex_color((string)($productionTheme['custom'][$tk]??$tv));$themeCustom[$tk]=$candidate?:$tv;}}update_post_meta($id,Theme::PRODUCTION_META,['mode'=>$themeMode,'preset'=>$themePreset,'custom'=>$themeCustom]);$ticketSize=sanitize_key(wp_unslash($_POST['ticket_size']??'l'));if(!in_array($ticketSize,['l','m','s'],true))$ticketSize='l';update_post_meta($id,'ticket_size',$ticketSize);update_post_meta($id,'tax_display',in_array($_POST['tax_display']??'included',['included','excluded','none'],true)?$_POST['tax_display']:'included');$rawLabels=[];$labelsJson=trim((string)wp_unslash($_POST['labels_json']??''));if($labelsJson!==''){$decoded=json_decode($labelsJson,true);if(is_array($decoded))$rawLabels=$decoded;}if(!$rawLabels)$rawLabels=isset($_POST['labels'])?(array)$_POST['labels']:[];$labelRows=[];foreach($rawLabels as$rowKey=>$row){if(!is_array($row))continue;$labelRows[$rowKey]=['id'=>isset($row['id'])?(int)$row['id']:0,'symbol'=>sanitize_text_field(wp_unslash((string)($row['symbol']??''))),'name'=>sanitize_text_field(wp_unslash((string)($row['name']??'')))];}$labelIds=$this->repo->saveLabels($id,$labelRows,(array)($_POST['deleted_labels']??[]));$performanceRows=$this->cleanRows($_POST['performances']??[]);foreach($performanceRows as&$performanceRow){$labelKey=(string)($performanceRow['label_id']??'');if($labelKey!==''&&!ctype_digit($labelKey)&&isset($labelIds[$labelKey]))$performanceRow['label_id']=$labelIds[$labelKey];}unset($performanceRow);$this->repo->savePerformances($id,$performanceRows);$this->repo->saveTickets($id,$this->cleanRows($_POST['tickets']??[]));foreach(['cast','staff']as$kind)$this->repo->saveParticipants($id,$kind,$this->cleanRows((array)($_POST['participants'][$kind]??[])));$submittedSections=[];
foreach((array)($_POST['credits']??[]) as $s){
 if(!is_array($s))continue;
 $name=sanitize_text_field(wp_unslash((string)($s['name']??'')));
 if($name==='')continue;
 $items=[];
 foreach((array)($s['items']??[]) as $itemKey=>$it){
  if(!is_array($it))continue;
  $itemName=sanitize_text_field(wp_unslash((string)($it['name']??'')));
  if($itemName==='')continue;
  $key=(string)$itemKey;
  $items[$key]=['id'=>ctype_digit($key)?(int)$key:0,'name'=>$itemName,'url'=>esc_url_raw(wp_unslash((string)($it['url']??'')))];
 }
 $submittedSections[]=['id'=>(int)($s['id']??0),'name'=>$name,'release_enabled'=>!empty($s['release_enabled']),'release_at'=>(string)($s['release_at']??''),'items'=>$items];
}
$oldSections=$this->credits->sections($id,false);
$keepSections=[];
foreach(array_values($submittedSections)as$order=>$s){
 $sid=(int)$s['id'];
 $release=!empty($s['release_enabled'])?$this->utc((string)$s['release_at']):null;
 if($sid){
  $ok=$this->credits->updateSection($sid,$id,$s['name'],$release,$order);
  if(!$ok)wp_die('公演クレジット区分の更新に失敗しました。DB更新エラー');
 }else{
  $sid=$this->credits->createSection($id,$s['name'],$release,$order);
  if($sid<=0)wp_die('公演クレジット区分の追加に失敗しました。DB更新エラー');
 }
 $keepSections[]=$sid;
 $existingItems=$this->credits->items($sid);
 $existingIds=[];
 foreach($existingItems as $existing)$existingIds[(int)$existing['id']]=true;
 $keepItems=[];
 $itemOrder=0;
 foreach($s['items'] as $itemKey=>$item){
  $itemId=ctype_digit((string)$itemKey)?(int)$itemKey:(int)($item['id']??0);
  $j=$itemOrder++;
  if($itemId>0&&isset($existingIds[$itemId])){
   if(!$this->credits->updateItem($itemId,$sid,$item['name'],$item['url']??null,$j))wp_die('公演クレジット項目の更新に失敗しました。DB更新エラー');
   $keepItems[]=$itemId;
  }else{
   $newItemId=$this->credits->createItem($sid,$item['name'],$item['url']??null,$j);
   if($newItemId<=0)wp_die('公演クレジット項目の追加に失敗しました。DB更新エラー');
   $keepItems[]=$newItemId;
  }
 }
 foreach($existingItems as $existing){
  $existingId=(int)$existing['id'];
  if(!in_array($existingId,$keepItems,true)&&!$this->credits->deleteItem($existingId,$sid))wp_die('公演クレジット項目の削除に失敗しました。DB更新エラー');
 }
 $savedItems=$this->credits->items($sid);
 if(count($savedItems)!==count($s['items']))wp_die('公演クレジット項目の保存件数を確認できませんでした。');
}
foreach($oldSections as$s)if(!in_array((int)$s['id'],$keepSections,true))$this->credits->deleteSection((int)$s['id'],$id);

wp_safe_redirect(admin_url('admin.php?page=stageart-productions&id='.$id.'&saved=1'));exit;}
 private function cleanRows(array$rows):array{$out=[];foreach($rows as$rowKey=>$r){if(!is_array($r))continue;$x=[];foreach($r as$k=>$v)$x[$k]=is_string($v)?sanitize_text_field(wp_unslash($v)):$v;$out[$rowKey]=$x;}return$out;}
}
