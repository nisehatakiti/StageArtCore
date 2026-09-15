<?php

declare(strict_types=1);
namespace StageArtCore\Presentation\PublicSite;

final class ProductionLayout
{
    public const OPTION = 'stageart_core_production_layout';
    public const MIN_COLUMNS = 1;
    public const MAX_COLUMNS = 20;
    public const LAYOUT_HORIZONTAL = 'horizontal';
    public const LAYOUT_VERTICAL = 'vertical';
    public const SLOT_LINK = 'link';
    public const SLOT_HEADING = 'heading';

    public static function contentLabels(): array
    {
        return ['summary'=>'概要','description'=>'公演紹介','schedule'=>'公演日程','performances'=>'公演回','cast'=>'出演者','staff'=>'スタッフ','venue'=>'会場','tickets'=>'チケット料金','survey'=>'アンケート','credits'=>'公演クレジット'];
    }

    public static function defaults(): array
    {
        $slots=[];foreach(self::contentLabels() as $id=>$label)$slots[]=['type'=>self::SLOT_LINK,'ref'=>$id];
        return [['section_id'=>wp_generate_uuid4(),'heading'=>'','columns'=>1,'layout'=>self::LAYOUT_VERTICAL,'slots'=>$slots]];
    }

    public static function get(int $productionId): array
    {
        $value=get_post_meta($productionId,self::OPTION,true);
        if(!is_array($value)||!$value)return self::defaults();
        if(isset($value[0]['id'])&&!isset($value[0]['section_id'])){
            $slots=[];foreach($value as $row){if(!is_array($row)||empty($row['id']))continue;$id=sanitize_key((string)$row['id']);if(isset(self::contentLabels()[$id]))$slots[]=['type'=>self::SLOT_LINK,'ref'=>$id];}
            return [['section_id'=>wp_generate_uuid4(),'heading'=>'','columns'=>1,'layout'=>self::LAYOUT_VERTICAL,'slots'=>$slots?:self::defaults()[0]['slots']]];
        }
        $out=[];$allowed=self::contentLabels();
        foreach($value as $section){
            if(!is_array($section))continue;
            $slots=[];foreach((array)($section['slots']??[]) as $slot){if(!is_array($slot))continue;$type=($slot['type']??self::SLOT_LINK)===self::SLOT_HEADING?self::SLOT_HEADING:self::SLOT_LINK;$ref=sanitize_text_field((string)($slot['ref']??''));if($type===self::SLOT_LINK&&!isset($allowed[$ref]))continue;if($ref==='')continue;$slots[]=['type'=>$type,'ref'=>$ref];}
            $out[]=['section_id'=>sanitize_key((string)($section['section_id']??wp_generate_uuid4())),'heading'=>sanitize_text_field((string)($section['heading']??'')),'columns'=>max(self::MIN_COLUMNS,min(self::MAX_COLUMNS,(int)($section['columns']??1))),'layout'=>($section['layout']??self::LAYOUT_VERTICAL)===self::LAYOUT_HORIZONTAL?self::LAYOUT_HORIZONTAL:self::LAYOUT_VERTICAL,'slots'=>$slots];
        }
        return $out?:self::defaults();
    }

    public static function save(int $productionId,array $sections): void
    {
        $allowed=self::contentLabels();$clean=[];
        foreach($sections as $section){
            if(!is_array($section))continue;$slots=[];
            foreach((array)($section['slots']??[]) as $slot){
                if(!is_array($slot))continue;$type=($slot['type']??self::SLOT_LINK)===self::SLOT_HEADING?self::SLOT_HEADING:self::SLOT_LINK;$ref=sanitize_text_field((string)($slot['ref']??''));if($type===self::SLOT_LINK&&!isset($allowed[$ref]))continue;if($ref==='')continue;$slots[]=['type'=>$type,'ref'=>$ref];
            }
            $clean[]=['section_id'=>sanitize_key((string)($section['section_id']??wp_generate_uuid4())),'heading'=>sanitize_text_field((string)($section['heading']??'')),'columns'=>max(self::MIN_COLUMNS,min(self::MAX_COLUMNS,(int)($section['columns']??1))),'layout'=>($section['layout']??self::LAYOUT_VERTICAL)===self::LAYOUT_HORIZONTAL?self::LAYOUT_HORIZONTAL:self::LAYOUT_VERTICAL,'slots'=>$slots];
        }
        update_post_meta($productionId,self::OPTION,$clean?:self::defaults());
    }
}
