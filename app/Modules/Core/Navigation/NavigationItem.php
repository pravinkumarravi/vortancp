<?php

declare(strict_types=1);

namespace App\Modules\Core\Navigation;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;

class NavigationItem
{
    protected ?string $permission = null;
    protected ?string $role = null;
    protected ?string $moduleDependency = null;
    protected ?Closure $badgeCallback = null;
    protected ?string $activeRoutePattern = null;

    public function __construct(
        protected string $id,
        protected string $title,
        protected string $route,
        protected string $icon = 'cube',
        protected int $order = 10,
        protected array $routeParams = []
    ) {
    }

    public static function make(string $id, string $title, string $route, string $icon = 'cube', int $order = 10): static
    {
        return new static($id, $title, $route, $icon, $order);
    }

    public function permission(string $permission): static
    {
        $this->permission = $permission;
        return $this;
    }

    public function role(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function dependsOnModule(string $moduleId): static
    {
        $this->moduleDependency = $moduleId;
        return $this;
    }

    public function badge(Closure $callback): static
    {
        $this->badgeCallback = $callback;
        return $this;
    }

    public function activeWhen(string $pattern): static
    {
        $this->activeRoutePattern = $pattern;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function getUrl(): string
    {
        try {
            return route($this->route, $this->routeParams);
        } catch (\Exception) {
            return '#';
        }
    }

    public function isActive(): bool
    {
        if ($this->activeRoutePattern !== null) {
            return request()->routeIs($this->activeRoutePattern);
        }

        return request()->routeIs($this->route);
    }

    public function getBadge(): ?string
    {
        if ($this->badgeCallback !== null) {
            return (string) call_user_func($this->badgeCallback);
        }

        return null;
    }

    public function isAuthorized(?Authenticatable $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($this->role !== null && method_exists($user, 'hasRole') && !$user->hasRole($this->role)) {
            return false;
        }

        if ($this->permission !== null && method_exists($user, 'can') && !$user->can($this->permission)) {
            return false;
        }

        if ($this->moduleDependency !== null) {
            $registry = app(\App\Modules\Core\Services\ModuleRegistry::class);
            if (!$registry->isEnabled($this->moduleDependency)) {
                return false;
            }
        }

        return true;
    }
}
