<?php

declare(strict_types=1);

namespace App\Modules\Core\Navigation;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

class NavigationRegistry
{
    /**
     * Navigation sections grouped by panel identifier ('admin', 'reseller', 'customer').
     *
     * @var array<string, array<string, NavigationSection>>
     */
    protected array $panels = [
        'admin' => [],
        'reseller' => [],
        'customer' => [],
    ];

    /**
     * Register or retrieve a navigation section for a specific panel.
     */
    public function section(string $panel, string $sectionId, string $title, int $order = 10): NavigationSection
    {
        if (!isset($this->panels[$panel])) {
            $this->panels[$panel] = [];
        }

        if (!isset($this->panels[$panel][$sectionId])) {
            $this->panels[$panel][$sectionId] = NavigationSection::make($sectionId, $title, $order);
        }

        return $this->panels[$panel][$sectionId];
    }

    /**
     * Register a single item into a specific panel and section.
     */
    public function registerItem(string $panel, string $sectionId, NavigationItem $item, string $sectionTitle = 'General', int $sectionOrder = 10): void
    {
        $this->section($panel, $sectionId, $sectionTitle, $sectionOrder)->addItem($item);
    }

    /**
     * Get all authorized navigation sections and items for the authenticated user on the requested panel.
     *
     * @return Collection<int, NavigationSection>
     */
    public function getNavigationForUser(string $panel, ?Authenticatable $user): Collection
    {
        if (!isset($this->panels[$panel])) {
            return collect();
        }

        return collect($this->panels[$panel])
            ->sortBy(fn (NavigationSection $section) => $section->getOrder())
            ->filter(fn (NavigationSection $section) => $section->getAuthorizedItems($user)->isNotEmpty())
            ->values();
    }
}
