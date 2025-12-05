# BlockForce WP

Minimal, enhanced login security with IP blocking, automatic URL change, global blocklist, and email alerts.

## Description

BlockForce WP is a lightweight yet powerful security plugin designed to protect your WordPress login page from brute-force attacks. It combines persistent IP blocking, automatic login URL changing, global threat intelligence, and detailed activity logging into a simple, easy-to-use package.

## Key Features

### Brute Force Protection
- Automatically blocks IPs after failed login attempts
- Configurable attempt limits and block durations
- Persistent blocking stored in database

### Auto URL Change
- Automatically changes login URL when attacks persist
- Hides login page from bots
- Email notification with new URL

### Global Blocklist
- Syncs with FireHol Level 1 threat intelligence
- Blocks 4,500+ known malicious IPs/CIDR ranges
- Daily automatic updates
- Manual IP addition support

### Activity Logging
- Detailed log of all login attempts
- Success/failure status tracking
- Bulk delete capability
- Configurable retention period

### Stealth Mode
- Default `wp-login.php` returns 404 when custom URL active
- Protects `wp-admin` access

### Email Alerts
- Notifications when login URL changes
- Test email functionality
- Customizable alert email address

### Site Health Integration
- WordPress Site Health diagnostics
- Configuration validation
- Rewrite rules verification

## Installation

1. Upload the plugin files to `/wp-content/plugins/blockforce-wp`
2. Activate through the 'Plugins' screen in WordPress
3. Navigate to **Settings → BlockForce WP** to configure

## Frequently Asked Questions

### What happens if I get locked out?
1. Wait for block duration to expire
2. Delete `bfwp_blocked_[your_ip]` from `wp_options` table via database
3. Rename plugin folder via FTP to disable temporarily

### How do I reset the plugin?
Go to the **Reset** tab and click "Reset Plugin".

### What IPs are blocked by the global blocklist?
The FireHol Level 1 list includes known malicious IPs from botnets, spam networks, and attack sources. It blocks login and admin access only - visitors can still view your public content.

### Does CIDR range blocking work?
Yes! The plugin supports CIDR notation (e.g., `192.168.1.0/24`) for blocking entire IP ranges.

## Requirements

- WordPress 5.0+
- PHP 7.2+

## Changelog

### 1.1.0
- Added Global Blocklist with FireHol integration
- Added CIDR range support for IP blocking
- Added Site Health integration
- Added Dashboard widget
- Improved pagination with page number boxes
- Modular code architecture
- Self-healing database tables

### 1.0.0
- Initial release
- Persistent IP blocking
- Login activity log
- Automatic URL changing
- Email alerts

## License

GPLv2 or later - https://www.gnu.org/licenses/gpl-2.0.html
