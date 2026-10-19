<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Modules\Core\Contracts\ModuleInterface;
use App\Modules\Core\Exceptions\MissingModuleDependencyException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;

class ModuleRegistry
{
    /**
     * Registered module instances keyed by module ID.
     *
     * @var array<string, ModuleInterface>
     */
    protected array $modules = [];

    public function __construct(protected Application $app)
    {
    }

    /**
     * Register a module class.
     */
    public function registerModule(string|ModuleInterface $module): void
    {
        $instance = is_string($module) ? $this->app->make($module, ['app' => $this->app]) : $module;

        if (!$instance instanceof ModuleInterface) {
            throw new \InvalidArgumentException("Module must implement ModuleInterface.");
        }

        $this->modules[$instance->getId()] = $instance;
    }

    /**
     * Get all registered modules.
     *
     * @return Collection<string, ModuleInterface>
     */
    public function all(): Collection
    {
        return collect($this->modules)->sortBy(fn (ModuleInterface $m) => $m->getPriority());
    }

    /**
     * Get all currently enabled modules.
     *
     * @return Collection<string, ModuleInterface>
     */
    public function enabled(): Collection
    {
        return $this->all()->filter(fn (ModuleInterface $m) => $m->isEnabled());
    }

    /**
     * Find a module by its identifier.
     */
    public function find(string $id): ?ModuleInterface
    {
        return $this->modules[$id] ?? null;
    }

    /**
     * Check if a module exists and is enabled.
     */
    public function isEnabled(string $id): bool
    {
        $module = $this->find($id);
        return $module !== null && $module->isEnabled();
    }

    /**
     * Verify that all prerequisite dependencies for enabled modules are met.
     *
     * @throws MissingModuleDependencyException
     */
    public function validateDependencies(): void
    {
        foreach ($this->enabled() as $module) {
            foreach ($module->getDependencies() as $dependencyId) {
                if (!$this->isEnabled($dependencyId)) {
                    throw new MissingModuleDependencyException(
                        "Module '{$module->getId()}' requires '{$dependencyId}', which is missing or disabled."
                    );
                }
            }
        }
    }

    /**
     * Boot all enabled modules in priority sequence.
     */
    public function bootEnabledModules(): void
    {
        $this->validateDependencies();

        foreach ($this->enabled() as $module) {
            $module->boot();
        }
    }
}
