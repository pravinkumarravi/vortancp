<?php

declare(strict_types=1);

namespace App\Modules\Core\Contracts;

interface ModuleInterface
{
    /**
     * Unique machine identifier for the module (e.g. 'core', 'identity', 'domains').
     */
    public function getId(): string;

    /**
     * Human-readable display name.
     */
    public function getName(): string;

    /**
     * Semver version string.
     */
    public function getVersion(): string;

    /**
     * Brief functional description of the module.
     */
    public function getDescription(): string;

    /**
     * Priority order for registration (lower numbers execute earlier).
     */
    public function getPriority(): int;

    /**
     * Array of prerequisite module IDs required before this module can be enabled.
     *
     * @return array<string>
     */
    public function getDependencies(): array;

    /**
     * Whether the module is active and should register routes, views, and navigation.
     */
    public function isEnabled(): bool;

    /**
     * Register services, bindings, and configurations.
     */
    public function register(): void;

    /**
     * Bootstrap views, routes, migrations, and event listeners.
     */
    public function boot(): void;
}
