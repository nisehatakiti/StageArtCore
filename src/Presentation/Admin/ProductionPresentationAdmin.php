<?php
declare(strict_types=1);
namespace StageArtCore\Presentation\Admin;

final class ProductionPresentationAdmin{
 private const THEMES=['standard'=>'標準','dark'=>'ダーク','light'=>'ライト'];
 public function register():void{
  add_action('admin_post_stageart_save_production',[$this,'save'],0);
  add_action('admin_footer',[$this,'field']);
  add_action('admin_footer',[$this,'scripts'],20);
 }
 public function save():void{
  if(!current_user_can('manage_options'))return;
  $id=(int)($_POST['id']??0);
  if($id<=0)return;
  $theme=sanitize_key(wp_unslash($_POST['presentation_theme']??'standard'));
  if(!isset(self::THEMES[$theme]))$theme='standard';
  update_post_meta($id,'presentation_theme',$theme);
 }
 public function field():void{
  if(!is_admin()||($_GET['page']??'')!=='stageart-productions')return;
  $id=(int)($_GET['id']??0);
  $theme=$id?(string)get_post_meta($id,'presentation_theme',true):'standard';
  if(!isset(self::THEMES[$theme]))$theme='standard';
  $options='';
  foreach(self::THEMES as$value=>$label)$options.='<option value="'.esc_attr($value).'"'.selected($theme,$value,false).'>'.esc_html($label).'</option>';
  echo '<script>(function(){function init(){var form=document.getElementById("stageart-production-form");if(!form)return;var headings=form.querySelectorAll("h2"),basicTable=null;headings.forEach(function(h){if(h.textContent.trim()==="基本情報")basicTable=h.nextElementSibling;});if(!basicTable||basicTable.tagName!=="TABLE"||document.getElementById("sa-presentation-theme"))return;var row=document.createElement("tr");row.innerHTML="<th><label for=\"sa-presentation-theme\">公演ページ表示テーマ</label></th><td><select id=\"sa-presentation-theme\" name=\"presentation_theme\">'.esc_js($options).'</select><p class=\"description\">公開中の公演ページのデザインを切り替えます。テーマ側が対応する表示を行います。</p></td>";basicTable.appendChild(row);}if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",init);else init();})();</script>';
 }
 public function scripts():void{
  if(!is_admin()||($_GET['page']??'')!=='stageart-productions')return;
  echo <<<'JS'
<script>
(function(){
 function init(){
  var form=document.getElementById('stageart-production-form');
  if(!form)return;

  function releaseFields(){
   form.querySelectorAll('input[type="datetime-local"]').forEach(function(input){
    if(input.dataset.releaseReady==='1')return;
    var name=input.getAttribute('name')||'';
    if(!/(?:_release|release_at)$/.test(name))return;
    input.dataset.releaseReady='1';
    var wrap=document.createElement('span');
    wrap.className='sa-release-control';
    var toggle=document.createElement('input');
    toggle.type='checkbox';
    toggle.className='sa-release-toggle';
    toggle.checked=input.value!=='';
    toggle.setAttribute('aria-label','情報解禁日を設定する');
    var label=document.createElement('label');
    label.appendChild(toggle);
    label.appendChild(document.createTextNode(' 情報解禁日を設定する'));
    var parent=input.parentNode;
    parent.insertBefore(wrap,input);
    wrap.appendChild(label);
    wrap.appendChild(document.createTextNode(' '));
    wrap.appendChild(input);
    input.disabled=!toggle.checked;
    toggle.addEventListener('change',function(){
     input.disabled=!toggle.checked;
     if(toggle.checked)input.focus();
    });
   });
  }

  function nextIndex(selector){
   var max=-1;
   form.querySelectorAll(selector).forEach(function(el){
    var names=el.querySelectorAll('[name]');
    names.forEach(function(field){
     var m=field.name.match(/\[(?:new)?(\d+)\]/);
     if(m)max=Math.max(max,parseInt(m[1],10));
    });
   });
   return max+1;
  }

  function labelOptions(){
   var html='<option value="">なし</option>';
   form.querySelectorAll('#sa-labels tbody tr').forEach(function(tr){
    var id=tr.querySelector('input[name*="[id]"]');
    var symbol=tr.querySelector('input[name*="[symbol]"]');
    var name=tr.querySelector('input[name*="[name]"]');
    if(!symbol)return;
    var value=id&&id.value?String(id.value):'';
    var text=(symbol.value||'')+(name&&name.value?' '+name.value:'');
    var opt=document.createElement('option');
    opt.value=value;
    opt.textContent=text;
    html+=opt.outerHTML;
   });
   return html;
  }

  function bind(){
   releaseFields();
   form.addEventListener('input',function(e){
    if(e.target.closest('#sa-labels')){
     form.querySelectorAll('#sa-performances select[name*="[label_id]"]').forEach(function(select){
      var current=select.value;
      select.innerHTML=labelOptions();
      select.value=current;
     });
    }
   });
   form.addEventListener('click',function(e){
    var remove=e.target.closest('.sa-remove-row');
    if(remove){
     var tr=remove.closest('tr');
     if(tr)tr.remove();
     return;
    }
    var add=e.target.closest('[data-add]');
    if(!add)return;
    var type=add.getAttribute('data-add');
    if(type==='label'){
     var body=form.querySelector('#sa-labels tbody');
     if(!body)return;
     var i=nextIndex(['#sa-labels tbody tr'].join(' '));
     body.insertAdjacentHTML('beforeend','<tr class="sa-label-row" data-used="0"><td><input name="labels[new'+i+'][symbol]" required></td><td><input name="labels[new'+i+'][name]"></td><td>未使用</td><td><button type="button" class="button-link-delete sa-remove-row">削除</button></td></tr>');
     return;
    }
    if(type==='performance'){
     var body=form.querySelector('#sa-performances tbody');
     if(!body)return;
     var i=nextIndex(['#sa-performances tbody tr'].join(' '));
     body.insertAdjacentHTML('beforeend','<tr><td><input type="date" name="performances[new'+i+'][date]"></td><td><input type="time" name="performances[new'+i+'][start]"></td><td><input type="time" name="performances[new'+i+'][end]"></td><td><select name="performances[new'+i+'][label_id]">'+labelOptions()+'</select></td><td><button type="button" class="button-link-delete sa-remove-row">削除</button></td></tr>');
     return;
    }
    if(type==='ticket'){
     var body=form.querySelector('#sa-tickets tbody');
     if(!body)return;
     var i=nextIndex('#sa-tickets tbody tr');
     body.insertAdjacentHTML('beforeend','<tr><td><input name="tickets[new'+i+'][description]"></td><td><input type="number" min="0" name="tickets[new'+i+'][amount]"></td><td><input type="hidden" name="tickets[new'+i+'][show_on_reservation]" value="0"><input type="checkbox" name="tickets[new'+i+'][show_on_reservation]" value="1" checked></td><td><button type="button" class="button-link-delete sa-remove-row">削除</button></td></tr>');
     return;
    }
    if(type==='participant'){
     var kind=add.getAttribute('data-kind')||'';
     var table=form.querySelector('table.sa-participants[data-kind="'+kind+'"]');
     if(!table)return;
     var i=table.querySelectorAll('tbody tr').length;
     var memberOptions=table.querySelector('tbody tr select[name*="[member_id]"]');
     var options=memberOptions?memberOptions.innerHTML:'<option value="">（未連携）</option>';
     table.querySelector('tbody').insertAdjacentHTML('beforeend','<tr><td><input name="participants['+kind+']['+i+'][name]"></td><td><input name="participants['+kind+']['+i+'][role]"></td><td><select name="participants['+kind+']['+i+'][member_id]">'+options+'</select></td><td><input type="number" min="1" name="participants['+kind+']['+i+'][auth_user_id]"></td><td><button type="button" class="button-link-delete sa-remove-row">削除</button></td></tr>');
     return;
    }
    if(type==='credit'){
     var box=document.getElementById('sa-credits');
     if(!box)return;
     var i=box.querySelectorAll('.sa-credit').length;
     box.insertAdjacentHTML('beforeend','<div class="card sa-credit" data-index="'+i+'"><h3>クレジット区分</h3><input name="credits['+i+'][name]" placeholder="区分名" required> <input type="datetime-local" name="credits['+i+'][release_at]"><button type="button" class="button-link-delete sa-remove-credit">区分を削除</button><div class="sa-credit-items"></div><p><button type="button" class="button" data-add-credit-item>＋ 項目を追加</button></p></div>');
     releaseFields();
     return;
    }
   });
   form.addEventListener('click',function(e){
    var removeCredit=e.target.closest('.sa-remove-credit');
    if(removeCredit){
     var box=removeCredit.closest('.sa-credit');
     if(box)box.remove();
     return;
    }
    var addItem=e.target.closest('[data-add-credit-item]');
    if(addItem){
     var box=addItem.closest('.sa-credit');
     if(!box)return;
     var idx=box.getAttribute('data-index');
     var wrap=box.querySelector('.sa-credit-items');
     var j=wrap?wrap.querySelectorAll('p').length:0;
     if(wrap)wrap.insertAdjacentHTML('beforeend','<p><input name="credits['+idx+'][items]['+j+'][name]" placeholder="名称"> <input type="url" name="credits['+idx+'][items]['+j+'][url]" placeholder="リンク"> <button type="button" class="button-link-delete sa-remove-item">削除</button></p>');
     return;
    }
    var removeItem=e.target.closest('.sa-remove-item');
    if(removeItem){
     var p=removeItem.closest('p');
     if(p)p.remove();
    }
   });
  }
  bind();
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
</script>
JS;
 }
}
