=== BlockForce WP ===
Contributors: RahulPalXDA
Tags: security, login, brute force, ip blocking, xmlrpc
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.2.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free WordPress security plugin for brute-force protection, login hardening, IP blocking, and automatic secret login URLs.

== Description ==

BlockForce WP is a lightweight WordPress security plugin focused entirely on login protection: stopping brute-force attacks against wp-login.php, the lost-password form, and XML-RPC, then hiding the login page behind a rotating secret URL when attacks persist. It correctly identifies real visitor IPs behind Cloudflare or a load balancer, blocks common username-enumeration techniques, and keeps a detailed activity log — all in a free, open-source package with no premium tier.

It isn't a full security suite: there's no malware scanner, firewall, or two-factor authentication. It's built for site owners who want a small, focused plugin that does brute-force and login protection well, without the overhead of a large all-in-one suite.

= Key Features =

* **Brute Force Protection** – Automatically blocks IPs after failed login attempts
* **Forgot Password Protection** – Detects brute-force attempts on the lost password form and hides account-existence details in its error messages
* **Reverse Proxy / CDN Support** – Correctly resolves real visitor IPs behind Cloudflare, a load balancer, or another reverse proxy via a trusted-header and trusted-proxy allow-list
* **XML-RPC Brute-Force Protection** – Blocks the system.multicall amplification method used to test hundreds of passwords in one request, with an option to disable XML-RPC entirely
* **Username Enumeration Protection** – Hides usernames from the REST API user list and author-archive links for logged-out visitors
* **Persistent Blocking** – Blocks stored in custom database tables, protection survives cookie clears
* **Auto URL Change** – Automatically changes login URL when attacks persist, rate-limited so a distributed attack can't spam rotations or flood your inbox
* **User-Agent Tracking** – Records readable and raw user-agent details for login attempts and blocked IPs
* **Activity Log** – Detailed log of login attempts, IPs, user agents, and security events
* **Log Retention** – Configurable auto-cleanup for logs (1-365 days)
* **Email Alerts** – Get notified with IP and user-agent details when your login URL changes
* **Stealth Mode** – Default wp-login.php and wp-admin redirect to 404 when custom URL is active
* **Public Slug Protection** – Avoids exposing the secret login slug through public WordPress login links
* **Dashboard Widget** – Quick overview of security status on your dashboard
* **Site Health Integration** – Plugin status appears in WordPress Site Health
* **Granular Reset Options** – Reset specific components without losing all data

= Admin Interface =

* **Overview** – View login status and manage blocked IPs
* **Activity Log** – Browse login attempts with pagination
* **Blocked IPs** – Manage active and expired IP blocks
* **Settings** – Configure protection options, reverse-proxy trust, XML-RPC hardening, and email alerts
* **Reset & Tools** – Granular reset options for logs, IPs, attempts, or full reset

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/blockforce-wp` directory, or install through WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to **BlockForce WP** in the admin sidebar to configure settings

== Frequently Asked Questions ==

= Is BlockForce WP free? =
Yes. It's fully free and open source under GPLv2, with no premium tier or upsells.

= What happens if I get locked out? =
If you get locked out, try these methods:

**Method 1: Wait it out**
Block duration expires automatically (default: 24 hours, configurable in settings)

**Method 2: Use phpMyAdmin or database tool**
Run this SQL query to unblock your IP (replace `wp_` with your actual table prefix):
`DELETE FROM wp_blockforce_blocks WHERE user_ip = 'YOUR.IP.ADDRESS';`
Replace `YOUR.IP.ADDRESS` with your actual IP.

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

= Will my secret login slug appear in public login links? =
BlockForce WP avoids rewriting public-facing WordPress login links to the secret slug. The custom URL is used in login/admin contexts while default `wp-login.php` and unauthenticated `wp-admin` access are redirected when a custom slug is active.

= Does BlockForce WP work behind Cloudflare or a load balancer? =
Yes, once configured. By default the plugin only trusts REMOTE_ADDR, which is safe but means every visitor behind a shared proxy IP looks identical. Set the Trusted Proxy Header and Trusted Proxy IPs options to your CDN/load balancer's header and IP ranges so BlockForce WP can see and block the real attacker IP instead of the proxy.

= Does BlockForce WP protect against XML-RPC brute-force attacks? =
Yes. system.multicall — the method attackers use to test hundreds of password combinations in a single XML-RPC request — is blocked by default. You can also disable XML-RPC entirely in Settings if you don't need it.

= Does BlockForce WP prevent WordPress username enumeration? =
Yes, when the Block Username Enumeration setting is on (default). It hides usernames from the REST API user list and author-archive URLs (?author=1) for logged-out visitors, and the lost-password form no longer reveals whether a username or email exists.

= Can I see attacker user agents? =
Yes. Activity Log and Blocked IPs show a readable user-agent name with the raw user-agent available on hover. Alert emails also include the detected user-agent name and raw user-agent string.

= Will this conflict with other security plugins? =
BlockForce WP is designed to be lightweight and focused on login protection. It should work alongside most security plugins, but we recommend testing in a staging environment first.

== Changelog ==

= 1.2.0 =
* Added reverse proxy / CDN support (trusted proxy header + IP allow-list) so real visitor IPs are detected correctly behind Cloudflare or a load balancer
* Added XML-RPC hardening: blocks the system.multicall brute-force amplification method, with an option to disable XML-RPC entirely
* Added username enumeration protection for the REST API and author archives
* Generalized lost-password error messages to prevent account enumeration
* Rate-limited automatic login URL rotation and alert emails to prevent notification flooding from distributed attacks
* Hardened brute-force tracking so it can no longer be bypassed by requests that appear to originate from localhost
* Scoped debug-log suppression to page output only, so server-side error logs are no longer disabled
* Destructive reset actions now require a POST request instead of a GET link

= 1.1.0 =
* See project commit history for details.
