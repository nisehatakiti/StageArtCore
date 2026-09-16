<?php

declare(strict_types=1);

namespace StageArtCore\Domain\Site;

use StageArtCore\Domain\Release\ReleaseDate;

final class RepresentativeGreeting
{
    public const OPTION_MEMBER = 'stageart_core_representative_member_id';
    public const OPTION_BODY = 'stageart_core_representative_greeting';
    public const OPTION_RELEASE = 'stageart_core_representative_release_at';

    public static function get(): array
    {
        return [
            'member_id' => (int) get_option(self::OPTION_MEMBER, 0),
            'body' => (string) get_option(self::OPTION_BODY, ''),
            'release_at' => (string) get_option(self::OPTION_RELEASE, ''),
        ];
    }

    public static function save(int $memberId, string $body, ?string $releaseAt): void
    {
        update_option(self::OPTION_MEMBER, max(0, $memberId), false);
        update_option(self::OPTION_BODY, wp_kses_post($body), false);
        if ($releaseAt === null || trim($releaseAt) === '') delete_option(self::OPTION_RELEASE);
        else update_option(self::OPTION_RELEASE, $releaseAt, false);
    }

    public static function isReleased(): bool
    {
        return ReleaseDate::isReleased(self::get()['release_at']);
    }
}
