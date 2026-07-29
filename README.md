# BlockForce WP

![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/License-GPLv2-green)
![Version](https://img.shields.io/badge/Version-1.2.0-orange)

Free, open-source WordPress security plugin for brute-force protection, login hardening, and automatic secret login URLs — with reverse-proxy support and XML-RPC/enumeration protection.

## Description

BlockForce WP is a lightweight WordPress security plugin focused entirely on login protection: stopping brute-force attacks against `wp-login.php`, the lost-password form, and XML-RPC, then hiding the login page behind a rotating secret URL when attacks persist. It correctly identifies real visitor IPs behind Cloudflare or a load balancer, blocks common username-enumeration techniques, and keeps a detailed activity log — all without a paid tier.

It isn't a full security suite — there's no malware scanner, firewall, or two-factor authentication here. If you want a small, auditable plugin that does one job (login/brute-force protection) well, rather than a large all-in-one suite, that's what BlockForce WP is for.

### Key Features

| Feature | Description |
|---------|-------------|
| 🛡️ **Brute Force Protection** | Automatically blocks IPs after failed login attempts |
| 🔑 **Forgot Password Protection** | Detects brute-force attempts on the lost password form and hides account-existence details in its error messages |
| 🌐 **Reverse Proxy / CDN Support** | Correctly resolves real visitor IPs behind Cloudflare, a load balancer, or another reverse proxy via a trusted-header + trusted-proxy allow-list |
| ⚔️ **XML-RPC Brute-Force Protection** | Blocks the `system.multicall` amplification method used to test hundreds of passwords in one request, with an option to disable XML-RPC entirely |
| 🕵️ **Username Enumeration Protection** | Hides usernames from the REST API user list and author-archive links for logged-out visitors |
| 🔒 **Persistent Blocking** | Blocks stored in custom database tables, survives cookie clears |
| 🔄 **Auto URL Change** | Automatically changes the login URL when attacks persist, rate-limited so a distributed attack can't spam rotations or flood your inbox |
| 🧭 **User-Agent Tracking** | Records readable and raw user-agent details for login attempts and blocked IPs |
| 📋 **Activity Log** | Detailed log of login attempts, IPs, user agents, and security events |
| 🗓️ **Log Retention** | Configurable auto-cleanup for logs (1-365 days) |
| 📧 **Email Alerts** | Get notified with IP and user-agent details when your login URL changes |
| 👻 **Stealth Mode** | Default wp-login.php redirects to 404 when custom URL active |
| 🔗 **Public Slug Protection** | Avoids exposing the secret login slug through public WordPress login links |
| 📊 **Dashboard Widget** | Quick security overview on your dashboard |
| ❤️ **Site Health** | Plugin status in WordPress Site Health |
| 🔧 **Granular Reset** | Reset specific components without losing all data |

### Admin Interface

```
🔒 BlockForce WP (top-level menu)
├── 📊 Overview — Login status & blocked IPs
├── 📋 Activity Log — Browse login attempts
├── 🛡️ Blocked IPs — Manage active and expired IP blocks
├── ⚙️ Settings — Configure protection options
└── 🔧 Reset & Tools — Granular reset options
```

## Installation

1. Upload the plugin to `/wp-content/plugins/blockforce-wp`
2. Activate through the 'Plugins' screen
3. Navigate to **BlockForce WP** in the admin sidebar

## Configuration

### Settings Options

| Setting | Description | Default |
|---------|-------------|---------|
| Maximum Failed Attempts | Attempts before triggering protection | 5 |
| IP Block Duration | How long to block malicious IPs | 86400 seconds (24 hours) |
| Attack Monitoring Window | Window for tracking persistent attacks | 1800 seconds (30 minutes) |
| Log Retention (Days) | How long to keep security logs | 90 days |
| Enable IP Blocking | Block IPs after failed attempts | Enabled |
| Enable Auto URL Change | Change URL on persistent attacks | Enabled |
| Disable Debug Logs | Hide PHP errors from page output (server-side logging is left untouched) | Enabled |
| Security Alert Email | Email for notifications | Admin email |
| Trusted Proxy Header | Forwarded-IP header to trust (`X-Forwarded-For`, `CF-Connecting-IP`, `X-Real-IP`) when behind a CDN/load balancer | Disabled |
| Trusted Proxy IPs | Allow-list of proxy IPs/CIDRs permitted to set the header above | Empty |
| Block XML-RPC Multicall | Removes the `system.multicall` XML-RPC method used for brute-force amplification | Enabled |
| Disable XML-RPC Entirely | Fully disables XML-RPC (turn off only if you don't use Jetpack or the WordPress mobile app) | Disabled |
| Block Username Enumeration | Hides usernames from the REST API and author archive links for logged-out visitors | Enabled |

### Reset Options

- **Clear Activity Logs** — Remove all login records
- **Clear Blocked IPs** — Unblock all IP addresses
- **Clear Attempt Tracking** — Reset login counters
- **Reset Login URL** — Restore default wp-login.php
- **Full Reset** — All of the above (settings preserved)

## FAQ

### Is BlockForce WP free?

Yes. It's fully free and open source under GPLv2, with no premium tier or upsells.

### What happens if I get locked out?

**Method 1: Wait it out**
Block duration expires automatically (default: 24 hours)

**Method 2: Unblock via database**
```sql
-- Replace {prefix} with your actual table prefix (usually wp_)
DELETE FROM wp_blockforce_blocks WHERE user_ip = 'YOUR.IP.ADDRESS';
```
Replace `YOUR.IP.ADDRESS` with your actual IP.

**Method 3: Reset secret login URL**
```sql
DELETE FROM wp_options WHERE option_name = 'blockforce_login_slug';
```
This restores the default wp-login.php

**Method 4: Disable via FTP**
Rename `/wp-content/plugins/blockforce-wp` to `blockforce-wp-disabled`

### How do I find my secret login URL?

Check your email for the notification, or go to **BlockForce WP → Overview**.

### Will my secret login slug appear in public login links?

BlockForce WP avoids rewriting public-facing WordPress login links to the secret slug. The custom URL is used in login/admin contexts while default `wp-login.php` and unauthenticated `wp-admin` access are redirected when a custom slug is active.

### Does BlockForce WP work behind Cloudflare or a load balancer?

Yes, once configured. By default the plugin only trusts `REMOTE_ADDR`, which is safe but means every visitor behind a shared proxy IP looks identical. Set **Trusted Proxy Header** and **Trusted Proxy IPs** in Settings to your CDN/load balancer's header and IP ranges so BlockForce WP can see and block the real attacker IP instead of the proxy.

### Does BlockForce WP protect against XML-RPC brute-force attacks?

Yes. `system.multicall` — the method attackers use to test hundreds of password combinations in a single XML-RPC request — is blocked by default. You can also disable XML-RPC entirely in Settings if you don't need it.

### Does BlockForce WP prevent WordPress username enumeration?

Yes, when the Block Username Enumeration setting is on (default). It hides usernames from the REST API user list and author-archive URLs (`?author=1`) for logged-out visitors, and the lost-password form no longer reveals whether a username or email exists.

### Can I see attacker user agents?

Yes. Activity Log and Blocked IPs show a readable user-agent name with the raw user-agent available on hover. Alert emails also include the detected user-agent name and raw user-agent string.

### Will this conflict with other security plugins?

BlockForce WP focuses on login protection and should work with most security plugins. Test in staging first.

## Requirements

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+ or MariaDB 10.0+

## License

GPLv2 or later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).

## Author

**RahulPalXDA**

---

⭐ If you find this plugin useful, please consider giving it a star!
