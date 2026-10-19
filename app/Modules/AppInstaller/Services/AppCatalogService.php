<?php

declare(strict_types=1);

namespace App\Modules\AppInstaller\Services;

class AppCatalogService
{
    /**
     * Catalog of supported production-ready web applications.
     */
    protected array $catalog = [
        'laravel' => [
            'slug' => 'laravel',
            'name' => 'Laravel Framework',
            'version' => '12.0',
            'category' => 'Framework',
            'description' => 'The PHP Framework for Web Artisans with elegant syntax and robust tooling.',
            'min_php' => '8.2',
            'requires_db' => true,
            'icon' => 'code-bracket',
            'color' => 'rose',
        ],
        'phpmyadmin' => [
            'slug' => 'phpmyadmin',
            'name' => 'phpMyAdmin',
            'version' => '5.2.1',
            'category' => 'Database Tools',
            'description' => 'Web interface for managing MySQL and MariaDB databases with ease.',
            'min_php' => '7.4',
            'requires_db' => false,
            'icon' => 'server-stack',
            'color' => 'amber',
        ],
        'roundcube' => [
            'slug' => 'roundcube',
            'name' => 'Roundcube Webmail',
            'version' => '1.6.9',
            'category' => 'Webmail',
            'description' => 'Browser-based multilingual IMAP client with an application-like user interface.',
            'min_php' => '8.0',
            'requires_db' => true,
            'icon' => 'envelope',
            'color' => 'blue',
        ],
        'nextcloud' => [
            'slug' => 'nextcloud',
            'name' => 'Nextcloud Hub',
            'version' => '30.0.4',
            'category' => 'Cloud & Productivity',
            'description' => 'Self-hosted content collaboration platform for file sharing, calendar, and contacts.',
            'min_php' => '8.2',
            'requires_db' => true,
            'icon' => 'cloud-arrow-up',
            'color' => 'indigo',
        ],
        'drupal' => [
            'slug' => 'drupal',
            'name' => 'Drupal CMS',
            'version' => '11.1.0',
            'category' => 'CMS',
            'description' => 'Open source content management platform supporting enterprise and government websites.',
            'min_php' => '8.3',
            'requires_db' => true,
            'icon' => 'document-text',
            'color' => 'sky',
        ],
        'joomla' => [
            'slug' => 'joomla',
            'name' => 'Joomla!',
            'version' => '5.2.2',
            'category' => 'CMS',
            'description' => 'Award-winning CMS which enables building web sites and powerful online applications.',
            'min_php' => '8.1',
            'requires_db' => true,
            'icon' => 'globe-alt',
            'color' => 'emerald',
        ],
    ];

    public function getAllPackages(): array
    {
        return array_values($this->catalog);
    }

    public function getPackage(string $slug): ?array
    {
        return $this->catalog[strtolower($slug)] ?? null;
    }

    public function getCategories(): array
    {
        return array_unique(array_column($this->catalog, 'category'));
    }
}
