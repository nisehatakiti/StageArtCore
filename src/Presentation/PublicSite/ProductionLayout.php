<?php

declare(strict_types=1);
namespace StageArtCore\Presentation\PublicSite;

final class ProductionLayout
{
    public const OPTION = 'stageart_core_production_layout';

    /** @return array<int,array<string,mixed>> */
    public static function defaults(): array
    {
        return [
            ['id'=>'summary','label'=>'概要','enabled'=>true,'columns'=>1],
            ['id'=>'description','label'=>'公演紹介','enabled'=>true,'columns'=>1],
            ['id'=>'schedule','label'=>'公演日程','enabled'=>true,'columns'=>1],
            ['id'=>'performances','label'=>'公演回','enabled'=>true,'columns'=>1],
            ['id'=>'cast','label'=>'出演者','enabled'=>true,'columns'=>1],
            ['id'=>'staff','label'=>'スタッフ','enabled'=>true,'columns'=>1],
            ['id'=>'venue','label'=>'会場','enabled'=>true,'columns'=>1],
            ['id'=>'tickets','label'=>'チケット料金','enabled'=>true,'columns'=>1],
            ['id'=>'survey','label'=>'アンケート','enabled'=>true,'columns'=>1],
            ['id'=>'credits','label'=>'公演クレジット','enabled'=>true,'columns'=>1],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public static function get(int $productionId): array
    {
        $value = get_post_meta($productionId, self::OPTION, true);
        if (!is_array($value) || !$value) return self::defaults();
        $byId = [];
        foreach (self::defaults() as $default) $byId[$default['id']] = $default;
        $result = [];
        foreach ($value as $row) {
            if (!is_array($row) || empty($row['id']) || !isset($byId[$row['id']])) continue;
            $id = sanitize_key((string)$row['id']);
            $result[] = [
                'id'=>$id,
                'label'=>sanitize_text_field((string)($row['label'] ?? $byId[$id]['label'])),
                'enabled'=>!empty($row['enabled']),
                'columns'=>in_array((int)($row['columns'] ?? 1), [1,2], true) ? (int)$row['columns'] : 1,
            ];
            unset($byId[$id]);
        }
        foreach ($byId as $row) $result[] = $row;
        return $result;
    }

    public static function save(int $productionId, array $rows): void
    {
        $allowed = array_column(self::defaults(), null, 'id');
        $clean = [];
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            $id = sanitize_key((string)($row['id'] ?? ''));
            if (!$id || !isset($allowed[$id])) continue;
            $clean[] = [
                'id'=>$id,
                'label'=>sanitize_text_field((string)($row['label'] ?? $allowed[$id]['label'])),
                'enabled'=>!empty($row['enabled']),
                'columns'=>in_array((int)($row['columns'] ?? 1), [1,2], true) ? (int)$row['columns'] : 1,
            ];
        }
        if (!$clean) $clean = self::defaults();
        update_post_meta($productionId, self::OPTION, $clean);
    }
}
