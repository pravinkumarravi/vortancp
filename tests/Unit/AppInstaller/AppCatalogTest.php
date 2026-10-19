<?php

declare(strict_types=1);

namespace Tests\Unit\AppInstaller;

use App\Modules\AppInstaller\Services\AppCatalogService;
use Tests\TestCase;

class AppCatalogTest extends TestCase
{
    protected AppCatalogService $catalog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->catalog = new AppCatalogService();
    }

    public function test_catalog_contains_standard_recipes(): void
    {
        $packages = $this->catalog->getAllPackages();
        $this->assertNotEmpty($packages);

        $slugs = array_column($packages, 'slug');
        $this->assertContains('laravel', $slugs);
        $this->assertContains('phpmyadmin', $slugs);
        $this->assertContains('roundcube', $slugs);
        $this->assertContains('nextcloud', $slugs);
    }

    public function test_can_retrieve_specific_package(): void
    {
        $laravel = $this->catalog->getPackage('laravel');
        $this->assertNotNull($laravel);
        $this->assertEquals('Laravel Framework', $laravel['name']);
        $this->assertTrue($laravel['requires_db']);
        $this->assertEquals('8.2', $laravel['min_php']);
    }

    public function test_returns_null_for_unknown_package(): void
    {
        $unknown = $this->catalog->getPackage('non_existent_recipe');
        $this->assertNull($unknown);
    }
}
