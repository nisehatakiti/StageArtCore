<?php
declare(strict_types=1);
namespace StageArtCore\Presentation\Admin;

final class ProductionTimeAdmin{
 public function register():void{add_action('admin_footer',[$this,'scripts'],30);}
 public function scripts():void{
  if(!is_admin()||($_GET['page']??'')!=='stageart-productions')return;
  echo <<<'JS'
<script>
(function(){
 function init(){
  var form=document.getElementById('stageart-production-form');
  if(!form)return;
  form.querySelectorAll('#sa-performances input[type="time"]').forEach(function(input){input.setAttribute('step','300');});
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
</script>
JS;
 }
}
// StageArt verified package-build trigger.
