# Vortan Server Provisioning Agent Architecture & Security Specification

## 1. Executive Summary & Security Rationale

In legacy hosting control panels, the web control plane historically executed with elevated or `root` permissions (via setuid binaries, sudoers entries with `ALL=(ALL) NOPASSWD`, or setuid web wrappers). This architectural flaw resulted in severe privilege escalation vulnerabilities whenever an arbitrary file upload, template injection, or deserialization vulnerability occurred in the web layer.

**VortanCP establishes an uncompromising security boundary:**
1. The Laravel 13 web application executes as an unprivileged system user (`vortan:vortan`).
2. The web application **never** holds `root` credentials and **never** executes raw, unvetted shell strings.
3. All privileged Linux operations are delegated to an isolated, standalone daemon: the **Vortan Server Provisioning Agent (`vortan-agent`)**.
4. The Agent exposes an **allowlisted command interface only**. It rejects arbitrary commands, validates all input parameters against strict JSON schemas, and logs every invocation to an append-only audit log.

---

## 2. Communication Topology & Transport Protocols

Depending on deployment topology, VortanCP supports two secure communication modes:

```
MODE A: SINGLE-SERVER DEPLOYMENT (Loopback UNIX Domain Socket)
+-----------------------+                    +-----------------------+
|  Laravel 13 Web App   |                    |   Vortan Node Agent   |
|   (user: vortan)      |  /run/vortan/      |      (root / systemd) |
|                       |  agent.sock        |                       |
|  LocalSocketAdapter   +------------------->+  IPC Daemon Listener  |
|                       |  (mode: 0660       |  (strict peercred     |
|                       |   vortan:vortan)   |   UID verification)   |
+-----------------------+                    +-----------------------+

MODE B: MULTI-SERVER / CLUSTER DEPLOYMENT (Mutual TLS / gRPC or Encrypted REST)
+-----------------------+                    +-----------------------+
|  Vortan Central Panel |                    |  Remote Worker Node   |
|  (Control Plane Node) |  HTTPS / mTLS      |                       |
|                       |  Port 8443         |  Vortan Node Agent    |
|  RemoteNodeAdapter    +------------------->+  Mutual TLS Listener  |
|                       |  Client Cert +     |  (Client Cert Auth +  |
|                       |  HMAC-SHA256 Token |   Replay Nonce Check) |
+-----------------------+                    +-----------------------+
```

### 2.1 Mode A: Single-Server UNIX Domain Socket
- Path: `/run/vortan/agent.sock`
- Permissions: `0660` owned by `root:vortan`.
- Kernel-Level Authentication: Uses `SO_PEERCRED` on Linux to cryptographically verify that the connecting process PID belongs to the `vortan` UID before parsing data.

### 2.2 Mode B: Multi-Server Mutual TLS (mTLS)
- Port: `8443` (configurable).
- Authentication:
  1. **Transport Security:** Both Master and Worker present TLS certificates signed by an internal Vortan Cluster CA. Unrecognized client certificates are terminated at TLS handshake.
  2. **Application Authentication:** Each request payload includes an `X-Vortan-Signature` header computed as `HMAC-SHA256(payload, cluster_shared_secret)`.
  3. **Replay Protection:** Includes a monotonically increasing timestamp and unique UUID v4 nonce validated against a Redis cache with a 60-second expiration window.

---

## 3. Allowlisted Command Interface & Strict Schema Validation

The Agent does not provide an "exec" command. Instead, it exposes discrete, well-defined **Operation Handlers**. Every operation must match a registered schema.

### 3.1 Representative Allowlisted Operations Catalog

| Category | Operation Key | Permitted Parameters | Actions Performed by Agent |
| :--- | :--- | :--- | :--- |
| **System User** | `user.create` | `{ "username": "[a-z0-9_]{3,16}", "uid": int, "homedir": "/home/[a-z0-9_]+", "shell": "nologin|rbash" }` | Validates regex; executes `useradd` with specific flags; creates base skeleton directories with 0750 permissions. |
| **System User** | `user.modify_quota` | `{ "username": "string", "hard_bytes": int, "soft_bytes": int }` | Invokes `setquota` or `xfs_quota` on the user home filesystem. |
| **Virtual Host** | `web.vhost_apply` | `{ "domain": "fqdn", "web_server": "nginx|apache", "content": "config_string", "test_first": true }` | Writes file to `/etc/nginx/sites-available/{domain}.conf.pending`; runs `nginx -t`; on success moves to live and triggers `systemctl reload nginx`; on failure deletes pending and returns stderr. |
| **PHP-FPM** | `php.pool_apply` | `{ "pool_name": "string", "php_version": "8.3", "pool_config": "string" }` | Validates directives; writes to `/etc/php/8.3/fpm/pool.d/{pool_name}.conf`; reloads `php8.3-fpm`. |
| **Database** | `db.provision` | `{ "engine": "mariadb|postgres", "database": "db_name", "user": "db_user", "password": "...", "grants": [...] }` | Connects via local administrative UNIX socket; executes exact parameterized SQL statements; flushes privileges. |
| **DNS** | `dns.zone_write` | `{ "zone": "fqdn", "content": "zone_string", "serial": int }` | Writes zone to `/var/named/zones/{zone}.db`; validates via `named-checkzone`; reloads zone via `rndc reload {zone}`. |
| **SSL/TLS** | `ssl.install` | `{ "domain": "fqdn", "cert_pem": "...", "key_pem": "...", "chain_pem": "..." }` | Validates modulus match using OpenSSL library; writes to `/etc/ssl/vortan/{domain}/` with 0600 permissions. |
| **Service** | `service.action` | `{ "service": "nginx|apache2|php8.3-fpm|mariadb|postfix|dovecot|named", "action": "status|reload|restart" }` | Validates service against strict allowlist array; executes `systemctl {action} {service}`. |

---

## 4. Safe Execution, Rollback & Dry-Run Verification

The Agent implements a **Atomic Safe Apply** protocol for all configuration changes:

```
                     +---------------------------------------+
                     |   Agent receives `web.vhost_apply`    |
                     +-------------------+-------------------+
                                         |
                                         v
                     +---------------------------------------+
                     | 1. Backup existing `/etc/nginx/...`   |
                     |    to temporary snapshot directory    |
                     +-------------------+-------------------+
                                         |
                                         v
                     +---------------------------------------+
                     | 2. Write new config to `.test` file   |
                     +-------------------+-------------------+
                                         |
                                         v
                     +---------------------------------------+
                     | 3. Run dry-run validation binary:     |
                     |    `nginx -t -c /etc/nginx/nginx.conf`|
                     +-------------------+-------------------+
                                         |
                        +----------------+---------------+
                        |                                |
                 [Valid Syntax]                   [Syntax Error]
                        |                                |
                        v                                v
       +---------------------------------+  +---------------------------------+
       | 4. Atomically move `.test` file |  | 4. Delete `.test` file          |
       |    to live path                 |  |    Restore backup snapshot      |
       | 5. Reload daemon:               |  | 5. Return JSON error response   |
       |    `systemctl reload nginx`     |  |    containing compiler output   |
       | 6. Return HTTP 200 Success      |  | 6. Daemon continues uninterrupted|
       +---------------------------------+  +---------------------------------+
```

---

## 5. Security Sandboxing & Least Privilege

1. **Root Isolation:** The agent drops privileges where possible and delegates sub-tasks to dedicated unprivileged system users (e.g. file operations in `/home/user` execute using `seteuid(user_uid)`).
2. **Preventing Symlink Exploits:** All filesystem writes enforce `O_NOFOLLOW` flag to prevent symlink traversal attacks targeting `/etc/passwd` or `/etc/shadow`.
3. **Execution Timeouts:** Every external process spawned by the Agent is governed by a strict timeout (e.g. 10s for configuration tests, 300s for database dumps). Runaway processes are terminated with `SIGKILL`.
4. **Append-Only Audit Log:** All operations received and executed by the Agent are written to `/var/log/vortan-agent/operations.log` with timestamp, authenticated client identity, operation name, and status code.
