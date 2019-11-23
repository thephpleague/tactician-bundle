<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Tests;

use function date_default_timezone_set;
use function error_reporting;
use const E_ALL;

require __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);

date_default_timezone_set('UTC');
