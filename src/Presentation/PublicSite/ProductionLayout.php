<?php

declare(strict_types=1);
namespace StageArtCore\Presentation\PublicSite;
final class ProductionLayout
{
    public const OPTION='stageart_core_production_layout';
    public const MIN_COLUMNS=1;
    public const MAX_COLUMNS=20;
    public const MAX_INDENT_LEVEL=3;
    public const LAYOUT_HORIZONTAL='horizontal';
    public const LAYOUT_VERTICAL='vertical';
    public const SLOT_LINK='link';
    public const SLOT_HEADING='heading';
    public const SLOT_NONE='none';
    public static function contentLabels():array{return['summary'=>'概要','description'=>'公演紹介','schedule'=>'公演日程','performances'=>'公演回','cast'=>'出演者','staff'=>'スタッフ','venue'=>'会場','tickets'=>'チケット料金','survey'=>'アンケート','credits'=>'公演クレジット'];}
    public static function defaults():array{$slots=[];foreach(self::contentLabels()as$id=>$label)$slots[]=['type'=>$id==='summary'?self::SLOT_LINK:self::SLOT_LINK,'ref'=>$id,'indent'=>0];return[['section_id'=>wp_generate_uuid4(),'heading'=>'','columns'=>1,'layout'=>self::LAYOUT_VERTICAL,'slots'=>$slots]];}
    public static function get(int$id):array{$v=get_post_meta($id,self::OPTION,true);if(!is_array($v)||!$v)return self::defaults();$out=[];$labels=self::contentLabels();foreach($v as$s){if(!is_array($s))continue;$slots=[];foreach((array)($s['slots']??[])as$slot){if(!is_array($slot))continue;$type=(string)($slot['type']??self::SLOT_NONE);$indent=max(0,min(self::MAX_INDENT_LEVEL,absint($slot['indent']??0)));if($type===self::SLOT_HEADING){$ref=sanitize_text_field((string)($slot['ref']??''));if($ref!=='')$slots[]=['type'=>'heading','ref'=>$ref,'indent'=>$indent];continue;}if($type===self::SLOT_NONE){$slots[]=['type'=>'none','ref'=>'','indent'=>$indent];continue;}$ref=sanitize_key((string)($slot['ref']??''));if(!isset($labels[$ref]))continue;$slots[]=['type'=>'link','ref'=>$ref,'indent'=>$indent];}$out[]=['section_id'=>sanitize_key((string)($s['section_id']??wp_generate_uuid4())),'heading'=>sanitize_text_field((string)($s['heading']??'')),'columns'=>max(1,min(20,(int)($s['columns']??1))),'layout'=>($s['layout']??'vertical')==='horizontal'?'horizontal':'vertical','slots'=>$slots];}return$out?:self::defaults();}
    public static function save(int$id,array$sections):void{$labels=self::contentLabels();$clean=[];foreach($sections as$s){if(!is_array($s))continue;$columns=max(1,min(20,(int)($s['columns']??1)));$slots=[];foreach(array_slice((array)($s['slots']??[]),0,$columns)as$slot){if(!is_array($slot))continue;$type=(string)($slot['type']??'none');$indent=max(0,min(3,absint($slot['indent']??0)));if($type==='heading'){$ref=sanitize_text_field((string)($slot['heading']??$slot['ref']??''));if($ref!=='')$slots[]=['type'=>'heading','ref'=>$ref,'indent'=>$indent];continue;}$ref=sanitize_key((string)($slot['ref']??''));if($ref===''||!isset($labels[$ref]))$slots[]=['type'=>'none','ref'=>'','indent'=>$indent];else$slots[]=['type'=>'link','ref'=>$ref,'indent'=>$indent];}$clean[]=['section_id'=>sanitize_key((string)($s['section_id']??wp_generate_uuid4())),'heading'=>sanitize_text_field((string)($s['heading']??'')),'columns'=>$columns,'layout'=>($s['layout']??'vertical')==='horizontal'?'horizontal':'vertical','slots'=>$slots];}update_post_meta($id,self::OPTION,$clean?:self::defaults());}
}
