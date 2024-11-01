<?php
/**
 * Global OIDCG functions.
 *
 * @package   OpenID_Connect_WP
 * @author    Jonathan Daggerhart <jonathan@daggerhart.com>
 * @copyright 2015-2020 daggerhart
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

namespace F1OpenIDConnectWP;

use F1OpenIDConnectWP\OpenID_Connect_WP;
use WP_Error;
use WP_User;

/**
 * Return a single use authentication URL.
 *
 * @return string
 */
function oidcwp_get_authentication_url() {
	return OpenID_Connect_WP::instance()->client_wrapper->get_authentication_url();
}

/**
 * Refresh a user claim and update the user metadata.
 *
 * @param \WP_User $user             The user object.
 * @param array    $token_response   The token response.
 *
 * @return \WP_Error|array
 */
function oidcgwp_refresh_user_claim( \WP_User $user, array $token_response ) {
	return OpenID_Connect_WP::instance()->client_wrapper->refresh_user_claim( $user, $token_response );
}
