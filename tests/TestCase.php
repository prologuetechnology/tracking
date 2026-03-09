<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\InteractsWithAppFixtures;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithAppFixtures;
}
