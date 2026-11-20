<?php

namespace Tests\Feature;

use Tests\TestCase;

class ToolchainTest extends TestCase
{
    public function test_root_redirects_to_instances_and_build_assets_exist(): void
    {
        $this->get('/')->assertRedirect('/instances');
        $this->assertFileExists(public_path('build/manifest.json'));
    }
}
