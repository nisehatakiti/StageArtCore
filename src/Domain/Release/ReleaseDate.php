<?php

declare(strict_types=1);

namespace StageArtCore\Domain\Release;

final class ReleaseDate
{
    public const JST = 'Asia/Tokyo';
    public const UTC = 'UTC';

    public static function isReleased(?string $releaseAt): bool
    {
        if ($releaseAt === null || trim($releaseAt) === '') {
            return true;
        }
        try {
            $now = new \DateTimeImmutable('now', new \DateTimeZone(self::UTC));
            $release = new \DateTimeImmutable($releaseAt, new \DateTimeZone(self::UTC));
            return $now >= $release;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function toUtc(?string $jstDateTime): ?string
    {
        if ($jstDateTime === null || trim($jstDateTime) === '') {
            return null;
        }
        $value = str_replace('T', ' ', trim($jstDateTime));
        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $value, new \DateTimeZone(self::JST));
        if (!$date) {
            return null;
        }
        return $date->setTimezone(new \DateTimeZone(self::UTC))->format('Y-m-d H:i:s');
    }

    public static function fromUtc(?string $utcDateTime): string
    {
        if ($utcDateTime === null || trim($utcDateTime) === '') {
            return '';
        }
        try {
            $date = new \DateTimeImmutable($utcDateTime, new \DateTimeZone(self::UTC));
            return $date->setTimezone(new \DateTimeZone(self::JST))->format('Y-m-d\\TH:i');
        } catch (\Throwable) {
            return '';
        }
    }
}
