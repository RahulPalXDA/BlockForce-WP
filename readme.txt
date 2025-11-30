=== BlockForce WP ===
Contributors: RahulPalXDA
Tags: security, login, brute force, ip blocking, protection
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Minimal, enhanced login security with IP blocking, automatic URL change, and email alerts.

== Description ==

BlockForce WP is a lightweight yet powerful security plugin designed to protect your WordPress login page from brute-force attacks. It combines persistent IP blocking, automatic login URL changing, and detailed activity logging into a simple, easy-to-use package.

**Key Features:**
*   **Brute Force Protection**: Automatically blocks IPs after a specified number of failed login attempts.
*   **Persistent Blocking**: Blocks are stored in the database, ensuring protection even if the attacker clears their cookies.
*   **Auto URL Change**: Automatically changes your login URL (slug) if an attack persists, hiding the login page from bots.
*   **Activity Log**: detailed log of all successful and failed login attempts with bulk delete capability.
*   **Email Alerts**: Get notified when an IP is blocked or your login URL is changed.
*   **Stealth Mode**: Accessing the default `wp-login.php` or `wp-admin` redirects to a 404 page when a custom slug is active.

== Installation ==

1.  Upload the plugin files to the `/wp-content/plugins/blockforce-wp` directory, or install the plugin through the WordPress plugins screen directly.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Navigate to **BlockForce WP** in the admin menu to configure your settings.

== Frequently Asked Questions ==

= What happens if I get locked out? =
If you get locked out, you can:
1.  Wait for the block duration to expire (default 60 minutes).
2.  Access your database and delete the `bfwp_blocked_[your_ip]` option from the `wp_options` table.
3.  Rename the plugin folder via FTP to disable it temporarily.

= How do I reset the plugin? =
Go to the **Reset** tab in the plugin settings and click "Reset All Settings".

== Changelog ==

= 1.0.0 =
*   Initial release.
*   Added persistent IP blocking.
*   Added login activity log.
*   Added automatic URL changing.
