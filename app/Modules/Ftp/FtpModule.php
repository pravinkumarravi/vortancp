<?php

declare(strict_types=1);

namespace App\Modules\Ftp;

use App\Modules\Core\AbstractModule;

class FtpModule extends AbstractModule
{
    protected string $id = 'ftp';
    protected string $name = 'FTP & SFTP Management';
    protected string $version = '1.0.0';
    protected string $description = 'Virtual user FTP and SFTP access, Pure-FTPd/ProFTPd configuration compilation, directory chrooting, and transfer bandwidth limits.';
    protected int $priority = 16;
    protected array $dependencies = ['core', 'identity', 'servers', 'accounts', 'hosting_plans', 'filemanager'];
}
