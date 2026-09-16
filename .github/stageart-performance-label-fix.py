from pathlib import Path
p=Path('src/Presentation/Admin/ProductionAdmin.php')
s=p.read_text()
old='<input type="hidden" name="labels_json" id="sa-labels-json" value=""><div id="sa-label-deletions">'
new='<input type="hidden" name="labels_json" id="sa-labels-json" value=""><input type="hidden" name="performance_labels_json" id="sa-performance-labels-json" value=""><div id="sa-label-deletions">'
if old not in s:
    raise SystemExit('missing form anchor')
s=s.replace(old,new,1)
old="""  const hidden=document.getElementById('sa-labels-json');
  if(hidden)hidden.value=JSON.stringify(payload);
 });"""
new="""  const hidden=document.getElementById('sa-labels-json');
  if(hidden)hidden.value=JSON.stringify(payload);
  const performancePayload={};
  document.querySelectorAll('#sa-performances tbody tr select[name*="[label_id]"]').forEach(function(select,index){
   const m=select.name.match(/performances\\[([^\\]]+)\\]\\[label_id\\]/);
   const key=m ? m[1] : String(index);
   performancePayload[key]=select.value || '';
  });
  const performanceHidden=document.getElementById('sa-performance-labels-json');
  if(performanceHidden)performanceHidden.value=JSON.stringify(performancePayload);
 });"""
if old not in s:
    raise SystemExit('missing submit anchor')
s=s.replace(old,new,1)
old="""$labelIds=$this->repo->saveLabels($id,$labelRows,(array)($_POST['deleted_labels']??[]));$performanceRows=$this->cleanRows($_POST['performances']??[]);foreach($performanceRows as&$performanceRow){$labelKey=(string)($performanceRow['label_id']??'');if($labelKey!==''&&!ctype_digit($labelKey)&&isset($labelIds[$labelKey]))$performanceRow['label_id']=$labelIds[$labelKey];}unset($performanceRow);$this->repo->savePerformances($id,$performanceRows);"""
new="""$labelIds=$this->repo->saveLabels($id,$labelRows,(array)($_POST['deleted_labels']??[]));$performanceRows=$this->cleanRows($_POST['performances']??[]);$performanceLabelJson=trim((string)wp_unslash($_POST['performance_labels_json']??''));if($performanceLabelJson!==''){$decodedPerformanceLabels=json_decode($performanceLabelJson,true);if(is_array($decodedPerformanceLabels)){foreach($performanceRows as $rowKey=>&$performanceRow){if(array_key_exists((string)$rowKey,$decodedPerformanceLabels))$performanceRow['label_id']=(string)$decodedPerformanceLabels[(string)$rowKey];}unset($performanceRow);}}foreach($performanceRows as&$performanceRow){$labelKey=(string)($performanceRow['label_id']??'');if($labelKey!==''&&!ctype_digit($labelKey)&&isset($labelIds[$labelKey]))$performanceRow['label_id']=$labelIds[$labelKey];}unset($performanceRow);$this->repo->savePerformances($id,$performanceRows);"""
if old not in s:
    raise SystemExit('missing save anchor')
s=s.replace(old,new,1)
p.write_text(s)
