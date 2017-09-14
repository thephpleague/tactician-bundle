<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

final class LocatorCacheWarmer extends CacheWarmer
{
    public function isOptional()
    {
        return true;
    }

    public function warmUp($cacheDir)
    {
        $this->writeCacheFile($cacheDir.'/tactician.php', []);
    }
}