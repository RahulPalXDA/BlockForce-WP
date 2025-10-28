# BlockForce WP

BlockForce WP is a simple, effective login security plugin for WordPress. It protects your site by blocking malicious IPs and automatically changing your login URL when an attack is detected, all while keeping you informed with clear email alerts.

![BlockForce WP Admin Notification email](https://raw.githubusercontent.com/RahulPalXDA/BlockForce-WP/refs/heads/main/screenshots/Screenshot.png)

## Key Features

* **Automatic Login URL Change:** Automatically moves `wp-login.php` to a secret, random URL (e.g., `/a1b2c3d4e5`) after too many persistent failed attempts, making it impossible for bots to find.
* **Instant Email Alert:** When the URL changes, it immediately sends an HTML email to your **specified alert email address** (or the site administrator's email by default) with the **new** login URL and the attacker's IP address.
* **IP Blocking:** Temporarily blocks an attacker's IP address after a configurable number of *recent* failed logins.
* **Login Error Obfuscation:** Prevents username enumeration by displaying a generic "Invalid username or password" message on all failed attempts.
* **Easy Configuration:** A simple settings page (`Settings > BlockForce WP`) lets you control all thresholds and features.
* **Status Dashboard:** An "Overview" tab shows you your **current** login URL at all times, so you're never locked out.
* **Reset Functionality:** A "Reset" tab allows you to instantly clear all IP blocks or reset your login URL back to the default `wp-login.php`.
* **Clean Uninstall:** Properly removes all options, transients, and scheduled events from your database upon uninstallation.

## How It Works

BlockForce WP monitors failed login attempts using two separate triggers:

1.  **IP Blocking (Short-Term Defense):**
    This trigger is for fast, repetitive attacks.
    * **If an IP fails to log in** `X` times (default: 2)
    * **within** `Y` seconds (default: 120)
    * **Then:** That IP address is **blocked** from accessing the site for `Y` seconds.

2.  **Login URL Change (Long-Term Defense):**
    This trigger is for slow, persistent bot attacks.
    * **If an IP accumulates** `X` total failed attempts (default: 2)
    * **within** `Z` seconds (default: 7200, or 2 hours)
    * **Then:** The plugin assumes it's a persistent attack. It **generates a new secret login URL** (e.g., `/1a7c3e9f2b`), emails it to the **configured alert email address**, and deactivates the default `wp-login.php` page.

Both IP Blocking and URL Change can be independently enabled or disabled from the settings page.

## Installation

1.  Navigate to the **"Code"** button on this GitHub repository page.
2.  Click **"Download ZIP"** and save the `blockforce-wp.zip` file to your computer.
3.  Log in to your WordPress dashboard.
4.  Go to `Plugins` > `Add New`.
5.  Click the **"Upload Plugin"** button at the top of the page.
6.  Choose the `blockforce-wp.zip` file you downloaded.
7.  Click **"Install Now"** and then **"Activate Plugin"**.
8.  Once activated, go to `Settings` > `BlockForce WP` to configure the plugin.

## Configuration

All settings are located in your WordPress dashboard under `Settings` > `BlockForce WP`.

* **Overview:** This tab shows the current status, including your active login link. **Bookmark this page!**
* **Security Settings:** This tab lets you configure the core logic:
    * **Maximum Failed Attempts Allowed:** The number of failed attempts (`X`) to trigger a block or URL change.
    * **Time to Block IP (in seconds):** The duration (`Y`) for an IP block.
    * **Time to Watch for Attacks (in seconds):** The monitoring window (`Z`) for persistent attacks.
    * **Block Attacker's IP:** Enable/disable the IP Blocking feature.
    * **Auto-Change Login Link:** Enable/disable the automatic Login URL Change feature.
    * **Alert Email Address:** Specify a dedicated email address for security alerts. If left blank, alerts are sent to the site's main administrator email.
* **Reset:** This tab provides tools to manually control the plugin:
    * **Clear IP Blocks:** Immediately unblocks all currently blocked IPs.
    * **Reset Login Link:** Deactivates the secret URL and restores the default `/wp-login.php`.

## Frequently Asked Questions

**HELP! The login URL changed, and I'm locked out!**

Don't panic. The plugin automatically sent an email to the **Alert Email Address** you configured in the plugin's settings. If you left that field blank, it was sent to your site's default administrator email address (set in `Settings > General`). This email contains your new, secret login link.

**I didn't get the email. How do I log in?**

If your site's email isn't working, you have two options:

1.  **Database:** Access your site's database (e.g., via phpMyAdmin). In the `wp_options` table, find the `option_name` called `blockforce_login_slug`. The `option_value` is your new login slug (e.g., `1a7c3e9f2b`). You can then log in at `your-site.com/1a7c3e9f2b`.
2.  **Disable Plugin:** Access your server via FTP or a file manager. Navigate to `/wp-content/plugins/` and rename the `blockforce-wp` folder. This will deactivate the plugin, and `wp-login.php` will work again.

**How do I get `wp-login.php` back permanently?**

Log in using your secret URL, then go to `Settings > BlockForce WP > Reset` and click the **"Reset Login Link"** button. This will restore the default login page.

## License

This plugin is licensed under MIT License.