<?php

declare(strict_types=1);

namespace StageArtCore\Presentation\PublicSite;

use StageArtCore\Presentation\Admin\FreeContentAdmin;

final class ProductionLayout
{
    public const OPTION = 'stageart_core_production_layout';
    public const MIN_COLUMNS = 1;
    public const MAX_COLUMNS = 20;
    public const MAX_INDENT_LEVEL = 3;
    public const LAYOUT_HORIZONTAL = 'horizontal';
    public const LAYOUT_VERTICAL = 'vertical';
    public const SLOT_LINK = 'link';
    public const SLOT_HEADING = 'heading';
    public const SLOT_NONE = 'none';

    public static function contentLabels(): array
    {
        return ['main_image'=>'公演画像','title'=>'公演タイトル','summary'=>'概要','description'=>'公演紹介','schedule'=>'公演日程','performances'=>'公演スケジュール','cast'=>'出演者','staff'=>'スタッフ','venue'=>'会場','tickets'=>'チケット料金','survey'=>'アンケート','credits'=>'公演クレジット','free_content'=>'自由コンテンツ'];
    }

    public static function defaults(): array { return []; }

    public static function get(int $id): array
    {
        $value=get_post_meta($id,self::OPTION,true);
        if(!is_array($value)) return [];
        return self::normalizeBlocks($value,$id);
    }

    public static function save(int $id,array $blocks): void
    {
        update_post_meta($id,self::OPTION,self::normalizeBlocks($blocks,$id));
    }

    private static function normalizeBlocks(array $value,?int $productionId=null): array
    {
        // Backward compatibility: older versions stored sections directly.
        if ($value===[] || isset($value[0]['section_id'])) {
            $value=[['block_id'=>wp_generate_uuid4(),'sections'=>$value]];
        }
        $out=[];$usedBlocks=[];
        foreach(array_values($value) as $block){
            if(!is_array($block))continue;
            $blockId=sanitize_key((string)($block['block_id']??''));
            if($blockId===''||isset($usedBlocks[$blockId]))$blockId=sanitize_key(wp_generate_uuid4());
            $usedBlocks[$blockId]=true;
            $sections=[];
            foreach(array_values((array)($block['sections']??[])) as $section){
                if(!is_array($section))continue;
                $sectionId=sanitize_key((string)($section['section_id']??''));
                $columns=max(self::MIN_COLUMNS,min(self::MAX_COLUMNS,(int)($section['columns']??1)));
                $layout=($section['layout']??self::LAYOUT_VERTICAL)===self::LAYOUT_HORIZONTAL?self::LAYOUT_HORIZONTAL:self::LAYOUT_VERTICAL;
                $slots=[];
                foreach(array_slice((array)($section['slots']??[]),0,self::MAX_COLUMNS) as $slot){
                    if(!is_array($slot))continue;
                    $indent=max(0,min(self::MAX_INDENT_LEVEL,absint($slot['indent']??0)));
                    $type=(string)($slot['type']??self::SLOT_NONE);$rawRef=(string)($slot['ref']??'');
                    if($type===self::SLOT_HEADING){$heading=sanitize_text_field((string)($slot['heading']??$rawRef));if($heading!=='')$slots[]=['type'=>self::SLOT_HEADING,'ref'=>$heading,'indent'=>$indent];continue;}
                    if($type===self::SLOT_LINK&&str_starts_with($rawRef,'free_content:')){$refId=absint(substr($rawRef,13));if(!$refId||!FreeContentAdmin::canReference($refId,$productionId)){continue;}$slots[]=['type'=>'free_content','ref'=>(string)$refId,'indent'=>$indent];continue;}
                    if($type==='free_content'){$refId=absint($rawRef);if(!$refId||!FreeContentAdmin::canReference($refId,$productionId)){continue;}$slots[]=['type'=>'free_content','ref'=>(string)$refId,'indent'=>$indent];continue;}
                    if($type!==self::SLOT_LINK){$slots[]=['type'=>self::SLOT_NONE,'ref'=>'','indent'=>$indent];continue;}
                    $ref=sanitize_key($rawRef);if($ref===''||!isset(self::contentLabels()[$ref])){$slots[]=['type'=>self::SLOT_NONE,'ref'=>'','indent'=>$indent];continue;}
                    $slots[]=['type'=>self::SLOT_LINK,'ref'=>$ref,'indent'=>$indent];
                }
                $sections[]=['section_id'=>$sectionId!==''?$sectionId:sanitize_key(wp_generate_uuid4()),'heading'=>sanitize_text_field((string)($section['heading']??'')),'columns'=>$columns,'layout'=>$layout,'slots'=>array_slice($slots,0,$columns)];
            }
            $out[]=['block_id'=>$blockId,'sections'=>$sections];
        }
        return $out;
    }
}
