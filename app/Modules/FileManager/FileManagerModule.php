<?php

declare(strict_types=1);

namespace App\Modules\FileManager;

use App\Modules\Core\AbstractModule;

class FileManagerModule extends AbstractModule
{
    protected string $id = 'filemanager';
    protected string $name = 'File Manager';
    protected string $version = '1.0.0';
    protected string $description = 'Secure in-jail file browser, code editor, archive manager, upload handler, and path traversal protection.';
    protected int $priority = 13;
    protected array $dependencies = ['core', 'identity', 'servers', 'accounts'];
}
