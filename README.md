# BlockForce WP

![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/License-GPLv2-green)
![Version](https://img.shields.io/badge/Version-1.1.0-orange)

Lightweight login security with IP blocking, automatic URL change, and email alerts.

## Description

BlockForce WP is a lightweight yet powerful security plugin designed to protect your WordPress login page from brute-force attacks. It combines persistent IP blocking, automatic login URL changing, and detailed activity logging into a simple, easy-to-use package.

### Key Features

| Feature | Description |
|---------|-------------|
| 🛡️ **Brute Force Protection** | Automatically blocks IPs after failed login attempts |
| 🔑 **Forgot Password Protection** | Detects and blocks brute-force attacks on the lost password form |
| 🔒 **Persistent Blocking** | Blocks stored in custom database tables, survives cookie clears |
| 🔄 **Auto URL Change** | Automatically changes login URL when attacks persist |
| 📋 **Activity Log** | Detailed log of all login attempts and security events |
| 🗓️ **Log Retention** | Configurable auto-cleanup for logs (1-365 days) |
| 📧 **Email Alerts** | Get notified when your login URL changes |
| 👻 **Stealth Mode** | Default wp-login.php redirects to 404 when custom URL active |
| 📊 **Dashboard Widget** | Quick security overview on your dashboard |
| ❤️ **Site Health** | Plugin status in WordPress Site Health |
| 🔧 **Granular Reset** | Reset specific components without losing all data |

### Admin Interface

```
🔒 BlockForce WP (top-level menu)
├── 📊 Overview — Login status & blocked IPs
├── 📋 Activity Log — Browse login attempts
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
| Maximum Failed Attempts | Attempts before triggering protection | 2 |
| IP Block Duration | How long to block malicious IPs | 120 seconds |
| Attack Monitoring Window | Window for tracking persistent attacks | 7200 seconds |
| Log Retention (Days) | How long to keep security logs | 30 days |
| Enable IP Blocking | Block IPs after failed attempts | Enabled |
| Enable Auto URL Change | Change URL on persistent attacks | Enabled |
| Security Alert Email | Email for notifications | Admin email |

### Reset Options

- **Clear Activity Logs** — Remove all login records
- **Clear Blocked IPs** — Unblock all IP addresses
- **Clear Attempt Tracking** — Reset login counters
- **Reset Login URL** — Restore default wp-login.php
- **Full Reset** — All of the above (settings preserved)

## FAQ

### What happens if I get locked out?

**Method 1: Wait it out**
Block duration expires automatically (default: 2 minutes)

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
