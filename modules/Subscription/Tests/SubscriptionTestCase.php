<?php

declare(strict_types=1);

namespace Modules\Subscription\Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Modules\Shared\Infrastructure\Tests\Concerns\AdaptiveDatabase;

abstract class SubscriptionTestCase extends BaseTestCase
{
    use AdaptiveDatabase;
}
