<?php
/**
 * OpenID Connect for WP
 *
 * This plugin provides the ability to authenticate users with Identity
 * Providers using the OpenID Connect OAuth2 API with Authorization Code Flow.
 *
 * @package   OpenID_Connect_WP
 * @category  General
 * @author    Forum One <wordpress@forumone.com>
 * @copyright 2024 forum1
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 * @link      https://github.com/forumone
 *
 * @wordpress-plugin
 * Plugin Name:       OpenID Connect for WP
 * Plugin URI:        https://github.com/forumone/openid-connect-wp-dist
 * Description:       Connect to an OpenID Connect identity provider using Authorization Code Flow.
 * Version:           4.0.0-develop.2
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            forum1
 * Author URI:        http://www.forumone.com
 * Text Domain:       openid-connect-wp
 * Domain Path:       /languages
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/forumone/openid-connect-wp-dist
 * Primary Branch:    main
 * Update URI:        https://github.com/forumone/openid-connect-wp-dist
 */

/*
Notes
  Spec Doc - http://openid.net/specs/openid-connect-basic-1_0-32.html

  Filters
  - openid-connect-wp-alter-request       - 3 args: request array, plugin settings, specific request op
  - openid-connect-wp-settings-fields     - modify the fields provided on the settings page
  - openid-connect-wp-login-button-text   - modify the login button text
  - openid-connect-wp-cookie-redirect-url - modify the redirect url stored as a cookie
  - openid-connect-wp-user-login-test     - (bool) should the user be logged in based on their claim
  - openid-connect-wp-user-creation-test  - (bool) should the user be created based on their claim
  - openid-connect-wp-auth-url            - modify the authentication url
  - openid-connect-wp-alter-user-claim    - modify the user_claim before a new user is created
  - openid-connect-wp-alter-user-data     - modify user data before a new user is created
  - openid-connect-modify-token-response-before-validation - modify the token response before validation
  - openid-connect-modify-id-token-claim-before-validation - modify the token claim before validation

  Actions
  - openid-connect-wp-user-create                     - 2 args: fires when a new user is created by this plugin
  - openid-connect-wp-user-update                     - 1 arg: user ID, fires when user is updated by this plugin
  - openid-connect-wp-update-user-using-current-claim - 2 args: fires every time an existing user logs in and the claims are updated.
  - openid-connect-wp-redirect-user-back              - 2 args: $redirect_url, $user. Allows interruption of redirect during login.
  - openid-connect-wp-user-logged-in                  - 1 arg: $user, fires when user is logged in.
  - openid-connect-wp-cron-daily                      - daily cron action
  - openid-connect-wp-state-not-found                 - the given state does not exist in the database, regardless of its expiration.
  - openid-connect-wp-state-expired                   - the given state exists, but expired before this login attempt.

  Callable actions

  User Meta
  - openid-connect-wp-subject-identity    - the identity of the user provided by the idp
  - openid-connect-wp-last-id-token-claim - the user's most recent id_token claim, decoded
  - openid-connect-wp-last-user-claim     - the user's most recent user_claim
  - openid-connect-wp-last-token-response - the user's most recent token response

  Options
  - openid_connect_generic_settings     - plugin settings
  - openid-connect-wp-valid-states - locally stored generated states
*/

namespace F1OpenIDConnectWP;

use F1OpenIDConnectWP\OpenID_Connect_WP_Client;
use F1OpenIDConnectWP\OpenID_Connect_WP_Client_Wrapper;
use F1OpenIDConnectWP\OpenID_Connect_WP_Option_Logger;
use F1OpenIDConnectWP\OpenID_Connect_WP_Option_Settings;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * OpenID_Connect_WP class.
 *
 * Defines plugin initialization functionality.
 *
 * @package OpenID_Connect_WP
 * @category  General
 */
class OpenID_Connect_WP {

	/**
	 * Singleton instance of self
	 *
	 * @var OpenID_Connect_WP
	 */
	protected static $_instance = null;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '4.0.0-develop.2';

	/**
	 * Plugin settings.
	 *
	 * @var OpenID_Connect_WP_Option_Settings
	 */
	private $settings;

	/**
	 * Plugin logs.
	 *
	 * @var OpenID_Connect_WP_Option_Logger
	 */
	private $logger;

	/**
	 * Openid Connect Generic client
	 *
	 * @var OpenID_Connect_WP_Client
	 */
	private $client;

	/**
	 * Client wrapper.
	 *
	 * @var OpenID_Connect_WP_Client_Wrapper
	 */
	public $client_wrapper;

	/**
	 * Setup the plugin
	 *
	 * @param OpenID_Connect_WP_Option_Settings $settings The settings object.
	 * @param OpenID_Connect_WP_Option_Logger   $logger   The loggin object.
	 *
	 * @return void
	 */
	public function __construct( OpenID_Connect_WP_Option_Settings $settings, OpenID_Connect_WP_Option_Logger $logger ) {
		$this->settings = $settings;
		$this->logger = $logger;
		self::$_instance = $this;
	}

	// @codeCoverageIgnoreStart

	/**
	 * WordPress Hook 'init'.
	 *
	 * @return void
	 */
	public function init() {

		$this->client = new OpenID_Connect_WP_Client(
			$this->settings->client_id,
			$this->settings->client_secret,
			$this->settings->scope,
			$this->settings->endpoint_login,
			$this->settings->endpoint_userinfo,
			$this->settings->endpoint_token,
			$this->get_redirect_uri( $this->settings ),
			$this->settings->acr_values,
			$this->get_state_time_limit( $this->settings ),
			$this->logger
		);

		$this->client_wrapper = OpenID_Connect_WP_Client_Wrapper::register( $this->client, $this->settings, $this->logger );
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return;
		}

		OpenID_Connect_WP_Login_Form::register( $this->settings, $this->client_wrapper );

		// Add a shortcode to get the auth URL.
		add_shortcode( 'openid_connect_generic_auth_url', array( $this->client_wrapper, 'get_authentication_url' ) );

		// Add actions to our scheduled cron jobs.
		add_action( 'openid-connect-wp-cron-daily', array( $this, 'cron_states_garbage_collection' ) );

		$this->upgrade();

		if ( is_admin() ) {
			OpenID_Connect_WP_Settings_Page::register( $this->settings, $this->logger );
		}
	}

	/**
	 * Get the default redirect URI.
	 *
	 * @param OpenID_Connect_WP_Option_Settings $settings The settings object.
	 *
	 * @return string
	 */
	public function get_redirect_uri( OpenID_Connect_WP_Option_Settings $settings ) {
		$redirect_uri = admin_url( 'admin-ajax.php?action=openid-connect-authorize' );

		if ( $settings->alternate_redirect_uri ) {
			$redirect_uri = site_url( '/openid-connect-authorize' );
		}

		return $redirect_uri;
	}

	/**
	 * Get the default state time limit.
	 *
	 * @param OpenID_Connect_WP_Option_Settings $settings The settings object.
	 *
	 * @return int
	 */
	public function get_state_time_limit( OpenID_Connect_WP_Option_Settings $settings ) {
		$state_time_limit = 180;
		// State time limit cannot be zero.
		if ( $settings->state_time_limit ) {
			$state_time_limit = intval( $settings->state_time_limit );
		}

		return $state_time_limit;
	}

	/**
	 * Check if privacy enforcement is enabled, and redirect users that aren't
	 * logged in.
	 *
	 * @return void
	 */
	public function enforce_privacy_redirect() {
		if ( $this->settings->enforce_privacy && ! is_user_logged_in() ) {
			// The client endpoint relies on the wp-admin ajax endpoint.
			if (
				! defined( 'DOING_AJAX' ) ||
				! boolval( constant( 'DOING_AJAX' ) ) ||
				! isset( $_GET['action'] ) ||
				'openid-connect-authorize' != $_GET['action'] ) {
				auth_redirect();
			}
		}
	}

	/**
	 * Enforce privacy settings for rss feeds.
	 *
	 * @param string $content The content.
	 *
	 * @return mixed
	 */
	public function enforce_privacy_feeds( $content ) {
		if ( $this->settings->enforce_privacy && ! is_user_logged_in() ) {
			$content = __( 'Private site', 'openid-connect-wp' );
		}
		return $content;
	}

	/**
	 * Handle plugin upgrades
	 *
	 * @return void
	 */
	public function upgrade() {
		$last_version = get_option( 'openid-connect-wp-plugin-version', 0 );
		$settings = $this->settings;

		if ( version_compare( self::VERSION, $last_version, '>' ) ) {
			// An upgrade is required.
			self::setup_cron_jobs();

			// @todo move this to another file for upgrade scripts
			if ( isset( $settings->ep_login ) ) {
				$settings->endpoint_login = $settings->ep_login;
				$settings->endpoint_token = $settings->ep_token;
				$settings->endpoint_userinfo = $settings->ep_userinfo;

				unset( $settings->ep_login, $settings->ep_token, $settings->ep_userinfo );
				$settings->save();
			}

			// Update the stored version number.
			update_option( 'openid-connect-wp-plugin-version', self::VERSION );
		}
	}

	/**
	 * Expire state transients by attempting to access them and allowing the
	 * transient's own mechanisms to delete any that have expired.
	 *
	 * @return void
	 */
	public function cron_states_garbage_collection() {
		global $wpdb;
		$states = $wpdb->get_col( "SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE '_transient_openid-connect-wp-state--%'" );

		if ( ! empty( $states ) ) {
			foreach ( $states as $state ) {
				$transient = str_replace( '_transient_', '', $state );
				get_transient( $transient );
			}
		}
	}

	/**
	 * Ensure cron jobs are added to the schedule.
	 *
	 * @return void
	 */
	public static function setup_cron_jobs() {
		if ( ! wp_next_scheduled( 'openid-connect-wp-cron-daily' ) ) {
			wp_schedule_event( time(), 'daily', 'openid-connect-wp-cron-daily' );
		}
	}

	/**
	 * Activation hook.
	 *
	 * @return void
	 */
	public static function activation() {
		self::setup_cron_jobs();
	}

	/**
	 * Deactivation hook.
	 *
	 * @return void
	 */
	public static function deactivation() {
		wp_clear_scheduled_hook( 'openid-connect-wp-cron-daily' );
	}

	/**
	 * Simple autoloader.
	 *
	 * @param string $class The class name.
	 *
	 * @return void
	 */
	public static function autoload( $class ) {
		$prefix = 'OpenID_Connect_WP_';

		if ( stripos( $class, $prefix ) !== 0 ) {
			return;
		}

		$filename = $class . '.php';

		// Internal files are all lowercase and use dashes in filenames.
		if ( false === strpos( $filename, '\\' ) ) {
			$filename = strtolower( str_replace( '_', '-', $filename ) );
		} else {
			$filename  = str_replace( '\\', DIRECTORY_SEPARATOR, $filename );
		}

		$filepath = __DIR__ . '/includes/' . $filename;

		if ( file_exists( $filepath ) ) {
			require_once $filepath;
		}
	}

	/**
	 * Instantiate the plugin and hook into WordPress.
	 *
	 * @return void
	 */
	public static function bootstrap() {
		/**
		 * This is a documented valid call for spl_autoload_register.
		 *
		 * @link https://www.php.net/manual/en/function.spl-autoload-register.php#71155
		 */
		spl_autoload_register( array( 'F1OpenIDConnectWP\OpenID_Connect_WP', 'autoload' ) );

		$settings = new OpenID_Connect_WP_Option_Settings(
			// Default settings values.
			array(
				// OAuth client settings.
				'login_type'           => defined( 'OIDC_LOGIN_TYPE' ) ? OIDC_LOGIN_TYPE : 'button',
				'client_id'            => defined( 'OIDC_CLIENT_ID' ) ? OIDC_CLIENT_ID : '',
				'client_secret'        => defined( 'OIDC_CLIENT_SECRET' ) ? OIDC_CLIENT_SECRET : '',
				'scope'                => defined( 'OIDC_CLIENT_SCOPE' ) ? OIDC_CLIENT_SCOPE : '',
				'endpoint_login'       => defined( 'OIDC_ENDPOINT_LOGIN_URL' ) ? OIDC_ENDPOINT_LOGIN_URL : '',
				'endpoint_userinfo'    => defined( 'OIDC_ENDPOINT_USERINFO_URL' ) ? OIDC_ENDPOINT_USERINFO_URL : '',
				'endpoint_token'       => defined( 'OIDC_ENDPOINT_TOKEN_URL' ) ? OIDC_ENDPOINT_TOKEN_URL : '',
				'endpoint_end_session' => defined( 'OIDC_ENDPOINT_LOGOUT_URL' ) ? OIDC_ENDPOINT_LOGOUT_URL : '',
				'acr_values'           => defined( 'OIDC_ACR_VALUES' ) ? OIDC_ACR_VALUES : '',

				// Non-standard settings.
				'no_sslverify'           => 0,
				'http_request_timeout'   => 5,
				'identity_key'           => 'preferred_username',
				'nickname_key'           => 'preferred_username',
				'email_format'           => '{email}',
				'displayname_format'     => '',
				'identify_with_username' => false,
				'state_time_limit'       => 180,

				// Plugin settings.
				'enforce_privacy'          => defined( 'OIDC_ENFORCE_PRIVACY' ) ? intval( OIDC_ENFORCE_PRIVACY ) : 0,
				'alternate_redirect_uri'   => 0,
				'token_refresh_enable'     => 1,
				'link_existing_users'      => defined( 'OIDC_LINK_EXISTING_USERS' ) ? intval( OIDC_LINK_EXISTING_USERS ) : 0,
				'create_if_does_not_exist' => defined( 'OIDC_CREATE_IF_DOES_NOT_EXIST' ) ? intval( OIDC_CREATE_IF_DOES_NOT_EXIST ) : 1,
				'redirect_user_back'       => defined( 'OIDC_REDIRECT_USER_BACK' ) ? intval( OIDC_REDIRECT_USER_BACK ) : 0,
				'redirect_on_logout'       => defined( 'OIDC_REDIRECT_ON_LOGOUT' ) ? intval( OIDC_REDIRECT_ON_LOGOUT ) : 1,
				'enable_logging'           => defined( 'OIDC_ENABLE_LOGGING' ) ? intval( OIDC_ENABLE_LOGGING ) : 0,
				'log_limit'                => defined( 'OIDC_LOG_LIMIT' ) ? intval( OIDC_LOG_LIMIT ) : 1000,
			)
		);

		$logger = new OpenID_Connect_WP_Option_Logger( 'error', $settings->enable_logging, $settings->log_limit );

		$plugin = new self( $settings, $logger );

		add_action( 'init', array( $plugin, 'init' ) );

		// Privacy hooks.
		add_action( 'template_redirect', array( $plugin, 'enforce_privacy_redirect' ), 0 );
		add_filter( 'the_content_feed', array( $plugin, 'enforce_privacy_feeds' ), 999 );
		add_filter( 'the_excerpt_rss', array( $plugin, 'enforce_privacy_feeds' ), 999 );
		add_filter( 'comment_text_rss', array( $plugin, 'enforce_privacy_feeds' ), 999 );
	}

	/**
	 * Create (if needed) and return a singleton of self.
	 *
	 * @return OpenID_Connect_WP
	 */
	public static function instance() {
		if ( null === self::$_instance ) {
			self::bootstrap();
		}
		return self::$_instance;
	}
}

OpenID_Connect_WP::instance();

register_activation_hook( __FILE__, array( 'F1OpenIDConnectWP\OpenID_Connect_WP', 'activation' ) );
register_deactivation_hook( __FILE__, array( 'F1OpenIDConnectWP\OpenID_Connect_WP', 'deactivation' ) );

// Provide publicly accessible plugin helper functions.
require_once 'includes/functions.php';
