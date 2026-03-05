<?php

declare(strict_types=1);

namespace Modules\User\Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Modules\Shared\Infrastructure\Tests\Concerns\AdaptiveDatabase;

abstract class UserTestCase extends BaseTestCase
{
    use AdaptiveDatabase;
}
