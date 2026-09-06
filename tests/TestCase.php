<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests render Blade views that use @vite; skip the manifest
        // lookup so tests don't require a built front-end bundle.
        $this->withoutVite();
    }
}
