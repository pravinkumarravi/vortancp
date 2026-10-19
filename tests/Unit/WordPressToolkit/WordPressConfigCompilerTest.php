<?php

declare(strict_types=1);

namespace Tests\Unit\WordPressToolkit;

use App\Modules\WordPressToolkit\Services\WordPressConfigCompilerService;
use Tests\TestCase;

class WordPressConfigCompilerTest extends TestCase
{
    protected WordPressConfigCompilerService $compiler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compiler = new WordPressConfigCompilerService();
    }

    public function test_compiles_wp_config_with_salts_and_directives(): void
    {
        $config = $this->compiler->compileWpConfig(
            dbName: 'vortan_wp123',
            dbUser: 'vortan_usr123',
            dbPassword: 'SecretPassword99!',
            dbHost: '127.0.0.1',
            tablePrefix: 'wp_cust_',
            disallowFileEdit: true,
            debug: false,
            autoUpdateCore: true
        );

        $this->assertStringContainsString("define('DB_NAME', 'vortan_wp123');", $config);
        $this->assertStringContainsString("define('DB_USER', 'vortan_usr123');", $config);
        $this->assertStringContainsString("define('DB_PASSWORD', 'SecretPassword99!');", $config);
        $this->assertStringContainsString("define('DB_HOST', '127.0.0.1');", $config);
        $this->assertStringContainsString("\$table_prefix = 'wp_cust_';", $config);
        $this->assertStringContainsString("define('DISALLOW_FILE_EDIT', true);", $config);
        $this->assertStringContainsString("define('WP_DEBUG', false);", $config);
        $this->assertStringContainsString("define('WP_AUTO_UPDATE_CORE', true);", $config);
        $this->assertStringContainsString("AUTH_KEY", $config);
        $this->assertStringContainsString("SECURE_AUTH_SALT", $config);
    }

    public function test_compiles_maintenance_file(): void
    {
        $content = $this->compiler->compileMaintenanceFile();

        $this->assertStringContainsString('<?php $upgrading =', $content);
    }
}
