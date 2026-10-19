<?php

declare(strict_types=1);

namespace App\Modules\Core\Navigation;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

class NavigationSection
{
    /**
     * @var array<string, NavigationItem>
     */
    protected array $items = [];

    public function __construct(
        protected string $id,
        protected string $title,
        protected int $order = 10
    ) {
    }

    public static function make(string $id, string $title, int $order = 10): static
    {
        return new static($id, $title, $order);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function addItem(NavigationItem $item): static
    {
        $this->items[$item->getId()] = $item;
        return $this;
    }

    /**
     * Get authorized items for the given user, sorted by order.
     *
     * @return Collection<int, NavigationItem>
     */
    public function getAuthorizedItems(?Authenticatable $user): Collection
    {
        return collect($this->items)
            ->filter(fn (NavigationItem $item) => $item->isAuthorized($user))
            ->sortBy(fn (NavigationItem $item) => $item->getOrder())
            ->values();
    }
}
