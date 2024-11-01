=== OpenID Connect for WP ===
Contributors: daggerhart, tnolte, forum1
Tags: security, login, oauth2, openidconnect, apps, authentication, autologin, sso
Requires at least: 5.0
Tested up to: 6.6.2
Stable tag: 4.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A simple client that provides SSO or opt-in authentication against a generic OAuth2 Server implementation.

**NOTE:** _This is a supported hard fork of the OpenID Connect Generic plugin in coordination with [Jonathan Daggerhart](https://github.com/daggerhart)._

== Description ==

This plugin allows to authenticate users against OpenID Connect OAuth2 API with Authorization Code Flow.
Once installed, it can be configured to automatically authenticate users (SSO), or provide a "Login with OpenID Connect"
button on the login form. After consent has been obtained, an existing user is automatically logged into WordPress, while
new users are created in WordPress database.

Much of the documentation can be found on the Settings > OpenID Connect for WP dashboard page.

Please submit issues to the Github repo: https://github.com/forumone/openid-connect-wp-dist

== Installation ==

= Manual =

1. Upload to the `/wp-content/plugins/` directory.
2. Activate the plugin.
3. Visit "Settings" -> "OpenID Connect" and configure to meet your needs.

= Git Updater =

1. Download the free version, or purchase the premium version, of the [Git Updater plugin](https://git-updater.com/).
2. Upload to the `/wp-content/plugins/` directory.
3. Activate the plugin.
4. Follow the plugin [installation instructions](https://git-updater.com/knowledge-base/remote-installation/) on the Git Updater website.

= Composer =

1. Run `composer require forumone/openid-connect-wp` to add to your Composer-based site.

== Frequently Asked Questions ==

= What is the client's Redirect URI? =

Most OAuth2 servers will require whitelisting a set of redirect URIs for security purposes. The Redirect URI provided
by this client is like so:  https://example.com/wp-admin/admin-ajax.php?action=openid-connect-authorize

Replace `example.com` with your domain name and path to WordPress.

= Can I change the client's Redirect URI? =

Some OAuth2 servers do not allow for a client redirect URI to contain a query string. The default URI provided by
this module leverages WordPress's `admin-ajax.php` endpoint as an easy way to provide a route that does not include
HTML, but this will naturally involve a query string. Fortunately, this plugin provides a setting that will make use of
an alternate redirect URI that does not include a query string.

On the settings page for this plugin (Dashboard > Settings > OpenID Connect for WP) there is a checkbox for
**Alternate Redirect URI**. When checked, the plugin will use the Redirect URI
`https://example.com/openid-connect-authorize`.


== Changelog ==

[See the full changelog here](https://github.com/forumone/openid-connect-wp-dist/blob/main/CHANGELOG.md#changelog)
