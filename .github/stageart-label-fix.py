from pathlib import Path

admin = Path('src/Presentation/Admin/ProductionAdmin.php')
s = admin.read_text(encoding='utf-8')

marker = '<input type="hidden" name="labels_form_present" value="1"><div id="sa-label-deletions"></div><table class="widefat" id="sa-labels">'
replacement = '<input type="hidden" name="labels_form_present" value="1"><input type="hidden" name="labels_json" id="sa-labels-json" value=""><div id="sa-label-deletions"></div><table class="widefat" id="sa-labels">'
if marker in s and 'id="sa-labels-json"' not in s:
    s = s.replace(marker, replacement, 1)

submit_old = " f.addEventListener('click',function(e){"
submit_new = ''' f.addEventListener('submit',function(){
  const payload={};
  document.querySelectorAll('#sa-labels tbody tr.sa-label-row').forEach(function(row,index){
   const id=row.querySelector('input[name*="[id]"]')?.value || '0';
   const symbol=row.querySelector('input[name*="[symbol]"]')?.value || '';
   const name=row.querySelector('input[name*="[name]"]')?.value || '';
   const m=row.querySelector('input[name*="[id]"]')?.name.match(/labels\\[([^\\]]+)\\]\\[id\\]/);
   const key=row.dataset.newKey || (m ? m[1] : String(index));
   payload[key]={id:id,symbol:symbol,name:name};
  });
  const hidden=document.getElementById('sa-labels-json');
  if(hidden)hidden.value=JSON.stringify(payload);
 });
 f.addEventListener('click',function(e){'''
if 'id="sa-labels-json"' in s and 'JSON.stringify(payload)' not in s:
    if submit_old not in s:
        raise SystemExit('submit anchor not found')
    s = s.replace(submit_old, submit_new, 1)

placeholder_old = "  $script=str_replace('__LABEL_OPTIONS__', $labelOptions, $script);"
placeholder_new = "  $script=str_replace('__LABEL_OPTIONS__', wp_json_encode($labelOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $script);"
if placeholder_old in s:
    s = s.replace(placeholder_old, placeholder_new, 1)

label_parse_old = "$labelRows=$this->cleanRows($_POST['labels']??[]);$labelIds=$this->repo->saveLabels($id,$labelRows,(array)($_POST['deleted_labels']??[]));"
label_parse_new = "$rawLabels=[];$labelsJson=trim((string)wp_unslash($_POST['labels_json']??''));if($labelsJson!==''){$decoded=json_decode($labelsJson,true);if(is_array($decoded))$rawLabels=$decoded;}if(!$rawLabels)$rawLabels=isset($_POST['labels'])?(array)$_POST['labels']:[];$labelRows=[];foreach($rawLabels as $rowKey=>$row){if(!is_array($row))continue;$labelRows[$rowKey]=['id'=>isset($row['id'])?(int)$row['id']:0,'symbol'=>sanitize_text_field(wp_unslash((string)($row['symbol']??''))),'name'=>sanitize_text_field(wp_unslash((string)($row['name']??'')))];}$labelIds=$this->repo->saveLabels($id,$labelRows,(array)($_POST['deleted_labels']??[]));"
if label_parse_old in s:
    s = s.replace(label_parse_old, label_parse_new, 1)

admin.write_text(s, encoding='utf-8')

repo = Path('src/Domain/Production/ProductionRepository.php')
s = repo.read_text(encoding='utf-8')
needle = '''        return $newIds;
    }

    public function savePerformances'''
block = '''        if ($newIds) {
            $savedById = [];
            foreach ($this->labels($id) as $label) $savedById[(int) $label['id']] = $label;
            foreach ($rows as $rowKey => $r) {
                $rid = (int) ($newIds[(string) $rowKey] ?? 0);
                if ($rid <= 0) continue;
                $expectedSymbol = sanitize_text_field((string) ($r['symbol'] ?? ''));
                $expectedName = sanitize_text_field((string) ($r['name'] ?? ''));
                $actual = $savedById[$rid] ?? null;
                if (!$actual || (string) ($actual['symbol'] ?? '') !== $expectedSymbol || (string) ($actual['name'] ?? '') !== $expectedName) {
                    $this->db->query($this->db->prepare("UPDATE {$this->labels} SET symbol=%s,name=%s,updated_at=%s WHERE id=%d AND production_id=%d", $expectedSymbol, $expectedName, $now, $rid, $id));
                }
            }
            $savedById = [];
            foreach ($this->labels($id) as $label) $savedById[(int) $label['id']] = $label;
            foreach ($rows as $rowKey => $r) {
                $rid = (int) ($newIds[(string) $rowKey] ?? 0);
                if ($rid <= 0) continue;
                $expectedSymbol = sanitize_text_field((string) ($r['symbol'] ?? ''));
                $expectedName = sanitize_text_field((string) ($r['name'] ?? ''));
                $actual = $savedById[$rid] ?? null;
                if (!$actual || (string) ($actual['symbol'] ?? '') !== $expectedSymbol || (string) ($actual['name'] ?? '') !== $expectedName) wp_die('公演スケジュールラベルの保存結果を確認できませんでした。');
            }
        }
        return $newIds;
    }

    public function savePerformances'''
if '公演スケジュールラベルの保存結果を確認できませんでした。' not in s:
    if needle not in s:
        raise SystemExit('repository anchor not found')
    s = s.replace(needle, block, 1)
repo.write_text(s, encoding='utf-8')
