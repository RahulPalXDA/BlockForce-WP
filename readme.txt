=== BlockForce WP ===
Contributors: RahulPalXDA
Tags: security, login, brute force, ip blocking, firewall, blocklist
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.1.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Minimal, enhanced login security with IP blocking, automatic URL change, global blocklist, and email alerts.

== Description ==

BlockForce WP is a lightweight yet powerful security plugin designed to protect your WordPress login page from brute-force attacks. It combines persistent IP blocking, automatic login URL changing, global threat intelligence, and detailed activity logging into a simple, easy-to-use package.

**Key Features:**

= Brute Force Protection =
* Automatically blocks IPs after failed login attempts
* Configurable attempt limits and block durations
* Persistent blocking stored in database

= Global Blocklist =
* Syncs with FireHol Level 1 threat intelligence
* Blocks 4,500+ known malicious IPs and CIDR ranges
* Daily automatic updates
* Manual IP addition support

= Auto URL Change =
* Automatically changes login URL when attacks persist
* Hides login page from bots
* Email notification with new URL

= Activity Logging =
* Detailed log of all login attempts
* Success/failure status tracking
* Bulk delete capability

= Stealth Mode =
* Default wp-login.php returns 404 when custom URL active
* Protects wp-admin access

= Email Alerts =
* Notifications when login URL changes
* Test email functionality

= Site Health Integration =
* WordPress Site Health diagnostics
* Configuration validation

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/blockforce-wp` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to **Settings → BlockForce WP** to configure.

== Frequently Asked Questions ==

= What happens if I get locked out? =
1. Wait for the block duration to expire.
2. Access your database and delete the `bfwp_blocked_[your_ip]` option from the `wp_options` table.
3. Rename the plugin folder via FTP to disable it temporarily.

= How do I reset the plugin? =
Go to the **Reset** tab in the plugin settings and click "Reset Plugin".

= What IPs are blocked by the global blocklist? =
The FireHol Level 1 list includes known malicious IPs from botnets, spam networks, and attack sources. It blocks login and admin access only - visitors can still view your public content.

= Does CIDR range blocking work? =
Yes! The plugin supports CIDR notation (e.g., 192.168.1.0/24) for blocking entire IP ranges.

== Screenshots ==

1. Overview dashboard showing security status
2. Activity logs with login attempts
3. Global blocklist manager
4. Plugin settings

== Changelog ==

= 1.1.0 =
* Added Global Blocklist with FireHol Level 1 integration
* Added CIDR range support for IP blocking
* Added Site Health integration
* Added Dashboard widget
* Improved pagination with page number boxes
* Modular code architecture
* Self-healing database tables

= 1.0.0 =
* Initial release
* Persistent IP blocking
* Login activity log
* Automatic URL changing
* Email alerts

== Upgrade Notice ==

= 1.1.0 =
Major update with Global Blocklist feature, CIDR support, and Site Health integration.
