from pathlib import Path

p = Path('src/Presentation/Admin/ProductionAdmin.php')
s = p.read_text(encoding='utf-8')

old = "$labelIds=$this->repo->saveLabels($id,$this->cleanRows($_POST['labels']??[]),(array)($_POST['deleted_labels']??[]));"
new = "$rawLabels=[];$labelsJson=trim((string)wp_unslash($_POST['labels_json']??''));if($labelsJson!==''){$decoded=json_decode($labelsJson,true);if(is_array($decoded))$rawLabels=$decoded;}if(!$rawLabels)$rawLabels=isset($_POST['labels'])?(array)$_POST['labels']:[];$labelRows=[];foreach($rawLabels as $rowKey=>$row){if(!is_array($row))continue;$labelRows[$rowKey]=['id'=>isset($row['id'])?(int)$row['id']:0,'symbol'=>sanitize_text_field(wp_unslash((string)($row['symbol']??''))),'name'=>sanitize_text_field(wp_unslash((string)($row['name']??'')))];}$labelIds=$this->repo->saveLabels($id,$labelRows,(array)($_POST['deleted_labels']??[]));"
if old not in s:
    raise SystemExit('label save anchor not found')
s = s.replace(old, new, 1)

old_remove = "    tr.remove();"
new_remove = "    if(label){const id=tr.querySelector('input[name*=\"[id]\"]')?.value||'';if(id){document.getElementById('sa-label-deletions')?.insertAdjacentHTML('beforeend','<input type=\"hidden\" name=\"deleted_labels[]\" value=\"'+id+'\">');}}\n    tr.remove();"
if old_remove in s and 'name=\\"deleted_labels[]\\"' not in s:
    s = s.replace(old_remove, new_remove, 1)

p.write_text(s, encoding='utf-8')
