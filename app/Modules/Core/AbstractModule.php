<?php

declare(strict_types=1);

namespace App\Modules\Core;

use App\Modules\Core\Contracts\ModuleInterface;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

abstract class AbstractModule extends ServiceProvider implements ModuleInterface
{
    protected string $id;
    protected string $name;
    protected string $version = '1.0.0';
    protected string $description = '';
    protected int $priority = 10;
    protected array $dependencies = [];
    protected bool $enabled = true;

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function isEnabled(): bool
    {
        return config("vortan.modules.{$this->id}.enabled", $this->enabled);
    }

    public function getBasePath(): string
    {
        $reflector = new \ReflectionClass(static::class);
        return dirname($reflector->getFileName());
    }

    public function register(): void
    {
        $configPath = $this->getBasePath() . '/Config/module.php';
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, "modules.{$this->id}");
        }
    }

    public function boot(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        // Register module database migrations
        $migrationPath = $this->getBasePath() . '/Database/Migrations';
        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }

        // Register module views
        $viewsPath = $this->getBasePath() . '/Resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, $this->id);
        }

        // Register routes
        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        $webRoutes = $this->getBasePath() . '/Routes/web.php';
        if (file_exists($webRoutes)) {
            Route::middleware('web')->group($webRoutes);
        }

        $apiRoutes = $this->getBasePath() . '/Routes/api.php';
        if (file_exists($apiRoutes)) {
            Route::prefix('api/v1')
                ->middleware('api')
                ->group($apiRoutes);
        }
    }
}
