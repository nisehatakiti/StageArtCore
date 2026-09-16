<?php

declare(strict_types=1);

namespace StageArtCore\Presentation\Admin;

use StageArtCore\Domain\Production\ProductionRepository;
use StageArtCore\Domain\Release\ReleaseDate;

final class PerformanceReleaseAdmin
{
    public function register(): void
    {
        add_action('admin_post_stageart_save_production', [$this, 'normalizePost'], 0);
        add_action('admin_footer', [$this, 'scripts'], 25);
    }
}
