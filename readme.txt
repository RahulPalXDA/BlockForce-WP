=== BlockForce WP ===
Contributors: RahulPalXDA
Tags: security, login, brute force, ip blocking, protection
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight login security with IP blocking, automatic URL change, and email alerts.

== Description ==

BlockForce WP is a lightweight yet powerful security plugin designed to protect your WordPress login page from brute-force attacks. It combines persistent IP blocking, automatic login URL changing, and detailed activity logging into a simple, easy-to-use package.

= Key Features =

* **Brute Force Protection** – Automatically blocks IPs after failed login attempts
* **Persistent Blocking** – Blocks stored in database, protection survives cookie clears
* **Auto URL Change** – Automatically changes login URL when attacks persist
* **Activity Log** – Detailed log of all login attempts with pagination and bulk delete
* **Email Alerts** – Get notified when your login URL changes
* **Stealth Mode** – Default wp-login.php and wp-admin redirect to 404 when custom URL is active
* **Dashboard Widget** – Quick overview of security status on your dashboard
* **Site Health Integration** – Plugin status appears in WordPress Site Health
* **Granular Reset Options** – Reset specific components without losing all data

= Admin Interface =

* **Overview** – View login status and manage blocked IPs
* **Activity Log** – Browse login attempts with pagination
* **Settings** – Configure protection options and email alerts
* **Reset & Tools** – Granular reset options for logs, IPs, attempts, or full reset

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/blockforce-wp` directory, or install through WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to **BlockForce WP** in the admin sidebar to configure settings

== Frequently Asked Questions ==

= What happens if I get locked out? =
If you get locked out, try these methods:

**Method 1: Wait it out**
Block duration expires automatically (default: 2 minutes, configurable in settings)

**Method 2: Use phpMyAdmin or database tool**
Run this SQL query to unblock your IP:
`DELETE FROM wp_options WHERE option_name = 'bfwp_blocked_YOUR.IP.ADDRESS';`
Replace `YOUR.IP.ADDRESS` with your actual IP (e.g., `192.168.1.100`)

**Method 3: Reset the secret login URL**
If you forgot your secret login URL, run:
`DELETE FROM wp_options WHERE option_name = 'blockforce_login_slug';`
This restores the default wp-login.php

**Method 4: Disable the plugin via FTP**
Rename `/wp-content/plugins/blockforce-wp` to `blockforce-wp-disabled`

= How do I reset the plugin? =
Go to **BlockForce WP → Reset & Tools** and choose from individual reset options or perform a full reset. Your configuration settings are preserved during reset.

= How do I find my secret login URL? =
Check your email for the notification, or go to **BlockForce WP → Overview** to see your current login URL.

= Will this conflict with other security plugins? =
BlockForce WP is designed to be lightweight and focused on login protection. It should work alongside most security plugins, but we recommend testing in a staging environment first.