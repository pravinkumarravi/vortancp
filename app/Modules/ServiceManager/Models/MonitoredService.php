<?php

declare(strict_types=1);

namespace App\Modules\ServiceManager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoredService extends Model
{
    use HasFactory;

    protected $table = 'monitored_services';

    public const STATUS_RUNNING = 'running';
    public const STATUS_STOPPED = 'stopped';
    public const STATUS_FAILED = 'failed';
    public const STATUS_UNKNOWN = 'unknown';

    public const CATEGORY_WEB = 'web';
    public const CATEGORY_DATABASE = 'database';
    public const CATEGORY_DNS = 'dns';
    public const CATEGORY_MAIL = 'mail';
    public const CATEGORY_FTP = 'ftp';
    public const CATEGORY_SECURITY = 'security';
    public const CATEGORY_PHP = 'php';
    public const CATEGORY_AGENT = 'agent';

    protected $fillable = [
        'name',
        'display_name',
        'category',
        'description',
        'is_monitored',
        'auto_restart',
        'status',
        'last_checked_at',
        'last_restarted_at',
        'restart_count',
    ];

    protected $casts = [
        'is_monitored' => 'boolean',
        'auto_restart' => 'boolean',
        'last_checked_at' => 'datetime',
        'last_restarted_at' => 'datetime',
        'restart_count' => 'integer',
    ];

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Pre-populate default web hosting cluster services if table is empty.
     */
    public static function seedDefaults(): void
    {
        $defaults = [
            [
                'name' => 'nginx',
                'display_name' => 'Nginx HTTP / Reverse Proxy Server',
                'category' => self::CATEGORY_WEB,
                'description' => 'High-performance web server handling HTTP/HTTPS traffic and reverse proxying to PHP-FPM.',
            ],
            [
                'name' => 'php8.3-fpm',
                'display_name' => 'PHP 8.3 FastCGI Process Manager',
                'category' => self::CATEGORY_PHP,
                'description' => 'FastCGI process manager executing customer PHP web applications.',
            ],
            [
                'name' => 'mariadb',
                'display_name' => 'MariaDB SQL Database Server',
                'category' => self::CATEGORY_DATABASE,
                'description' => 'Relational database engine hosting customer databases and WordPress installations.',
            ],
            [
                'name' => 'named',
                'display_name' => 'BIND9 DNS Authoritative Nameserver',
                'category' => self::CATEGORY_DNS,
                'description' => 'Authoritative nameserver answering public DNS queries for hosted zones.',
            ],
            [
                'name' => 'postfix',
                'display_name' => 'Postfix Mail Transfer Agent (MTA)',
                'category' => self::CATEGORY_MAIL,
                'description' => 'SMTP mail routing daemon sending and receiving inbound and outbound emails.',
            ],
            [
                'name' => 'dovecot',
                'display_name' => 'Dovecot IMAP / POP3 Server',
                'category' => self::CATEGORY_MAIL,
                'description' => 'Secure mailbox daemon serving customer mailboxes via IMAP and POP3.',
            ],
            [
                'name' => 'pure-ftpd',
                'display_name' => 'Pure-FTPd Virtual User Daemon',
                'category' => self::CATEGORY_FTP,
                'description' => 'FTP service providing chrooted file transfer access for hosting accounts.',
            ],
            [
                'name' => 'ufw',
                'display_name' => 'Uncomplicated Firewall (UFW / IPTables)',
                'category' => self::CATEGORY_SECURITY,
                'description' => 'Host packet filter and network security layer restricting open ports.',
            ],
            [
                'name' => 'fail2ban',
                'display_name' => 'Fail2ban Intrusion Prevention Daemon',
                'category' => self::CATEGORY_SECURITY,
                'description' => 'Automated log scanner banning malicious IPs attempting brute-force attacks.',
            ],
            [
                'name' => 'clamav-daemon',
                'display_name' => 'ClamAV Antivirus & Malware Scanner',
                'category' => self::CATEGORY_SECURITY,
                'description' => 'On-access and scheduled malware scanner inspecting user uploads.',
            ],
            [
                'name' => 'vortan-agent',
                'display_name' => 'VortanCP Privileged Root Agent',
                'category' => self::CATEGORY_AGENT,
                'description' => 'Cryptographically secured local daemon executing privileged system configurations.',
            ],
        ];

        foreach ($defaults as $def) {
            static::firstOrCreate(
                ['name' => $def['name']],
                [
                    'display_name' => $def['display_name'],
                    'category' => $def['category'],
                    'description' => $def['description'],
                    'is_monitored' => true,
                    'auto_restart' => true,
                    'status' => self::STATUS_RUNNING,
                    'last_checked_at' => now(),
                    'restart_count' => 0,
                ]
            );
        }
    }
}
