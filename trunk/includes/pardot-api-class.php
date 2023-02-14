<?php

/**
 * PHP class for interacting with the Pardot API.
 *
 * Developed for the Pardot WordPress Plugin.
 *
 * @note $URL_PATH_TEMPLATE and $LOGIN_URL_PATH_TEMPLATE are private static rather than const because const cannot be made private
 * and both these are "convenience" constants to ensure they are placed close to the top of the file but their
 * architecture is not robust enough to expose for external use as an update would create a breaking change.
 *
 * @note Requires WordPress API because of its use of wp_remote_request(), wp_remote_retrieve_response_code() and
 * wp_remote_retrieve_body() but otherwise independent of WordPress. Could be made standalone if these functions
 * replaced with CURL equivalents.
 *
 * @author Mike Schinkel <mike@newclarity.net>
 * @version 1.0.0
 *
 */
class Pardot_API
{
	/**
	 * @const string The root URL for the Pardot API.
	 *
	 * @since 1.0.0
	 */
	const API_ROOT_URL = Pardot_Settings::PI_PARDOT_URL . '/api';

	/**
	 * @const string The URL used to refresh the Salesforce OAUTH token
	 *
	 * @since 1.5.0
	 */
	const OAUTH_URL = 'https://login.salesforce.com/services/oauth2/token';

	/**
	 * @const string The supported version of the Pardot API, this value is embedded into the API's URLs
	 *
	 * @since 1.0.0
	 */
	const VERSION = '4';

	/**
	 * @var string Defacto constant defining the URL path template for the API.
	 * @note This classes defines and replaces the three (3) template variables %%ITEM_TYPE%%, %%VERSION%% and %%ACTION%%.
	 *    %%ITEM_TYPE%%: One of 'login', 'account', 'campaign' or 'form'.
	 *    %%VERSION%%: Pardot_API::VERSION
	 *    %%ACTION%%: For %%ITEM_TYPE%% == 'account' otherwise 'query'
	 * @note This is defined as a variable because it made need to change and thus should be internal to Pardot_API.
	 *
	 * @since 1.0.0
	 */
	private static $URL_PATH_TEMPLATE = '/%%ITEM_TYPE%%/version/%%VERSION%%/do/%%ACTION%%';

	/**
	 * @var string Defacto constant defining the URL path template for the API's login URL.
	 * @note This class defines and transforms the template variable %%VERSION%% which Pardto_API::VERSION replaces.
	 * @note This is defined as a variable because it made need to change and thus should be internal to Pardot_API.
	 *
	 * @since 1.0.0
	 */
	private static $LOGIN_URL_PATH_TEMPLATE = '/login/version/%%VERSION%%';

	/**
	 * @var string A user-entered client ID.
	 *
	 * @since 1.5.0
	 */
	var $client_id;

	/**
	 * @var string A user-entered client secret.
	 *
	 * @since 1.5.0
	 */
	var $client_secret;

	/**
	 * @var string A user-entered business unit ID.
	 *
	 * @since 1.5.0
	 */
	var $business_unit_id;

	/**
	 * @var string A key returned on authentication by SSO
	 *
	 * @since 1.5.0
	 */
	var $api_key = false;

	/**
	 * @var string A refresh token returned on authentication by SSO; used to get new api_key
	 *
	 * @since 1.5.0
	 */
	var $refresh_token = false;

	/**
	 * @var string Flag to indicate an API request failed.
	 *
	 * @since 1.0.0
	 */
	var $error = false;


	/**
	 * Creates a Pardot API object.
	 *
	 * If more than one value is passed for $auth it will pass to set_auth() to save the auth parameters
	 * into the object's same named properties.
	 *
	 * @param array $auth Values 'client_id', 'client_secret', 'business_unit_id', and 'api_key' supported.
	 *
	 * @since 1.0.0
	 */
	function __construct(array $auth = [])
	{
		if (is_array($auth) && count($auth))
			$this->set_auth($auth);
	}

	/**
	 * Call Salesforce OAuth API to authenticate and retrieve API Key
	 *
	 * The $auth parameters passed will be used to authenticate the login request.
	 * If successful $this->api_key will be set.
	 *
	 * @param array $auth Values 'client_id', 'client_secret', 'business_unit_id', and 'api_key' supported.
	 * @return string|bool An $api_key on success, false on failure.
	 *
	 * @since 1.0.0
	 */
	function authenticate(array $auth = [])
	{
		if (count($auth)) {
			$this->set_auth($auth);
		}
		if ($this->refresh_token) {
			$response = $this->refresh_API_key();
			if ($response) {
				$this->api_key = $response;
				Pardot_Settings::set_setting( 'api_key', $this->api_key);
			}
		}
		return $this->api_key;
	}

	/**
	 * Calls Salesforce OAuth API to get a new API token from the refresh token
	 *
	 * @return string|bool And $api_key on success, false on failure
	 * @since 1.5.0
	 */
	function refresh_API_key()
	{
		$url = self::OAUTH_URL;
		$body = [
			'grant_type' => 'refresh_token',
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'refresh_token' => $this->refresh_token,
		];

		$args = [
			'body' => $body,
			'timeout' => '5',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => ["Content-type: application/json"],
			'cookies' => [],
		];

		$response = wp_remote_post($url, $args);

		$response = json_decode(wp_remote_retrieve_body($response));
		if (property_exists($response, 'access_token')) {
			return $response->{'access_token'};
		}
		return null;
	}

	/**
	 * Determine is the API has authenticated.
	 *
	 * Authenticated will be determine by having a non-empty api_key property.
	 *
	 * @return bool True if there is an non-empty API key.
	 *
	 * @since 1.0.0
	 */
	function is_authenticated()
	{
		return !empty($this->api_key);
	}

	/**
	 * Returns an array of campaign objects from Pardot's API
	 *
	 * The structure of the campaign objects are based on Pardot's API returned XML format captured using PHP's SimpleXML
	 * and converted to an array of stdClass objects.
	 *
	 * @param array $args Combined authorization parameters and query arguments.
	 * @return array|bool Array of campaign objects, or false if API call failed.
	 *
	 * @since 1.0.0
	 */

	function get_campaigns(array $args = [])
	{

		$campaigns = false;

		if ($response = $this->get_response('campaign', $args)) {

			$campaigns = [];

			if ($response->result->total_results >= 200) {
				$limit = 200;
			} else {
				$limit = $response->result->total_results;
			}

			for ($i = 0; $i < $limit; $i++) {
				$campaign = (object)$response->result->campaign[$i];

				if (isset($campaign->id)) {
					$campaigns[(int)$campaign->id] = $this->SimpleXMLElement_to_stdClass($campaign);
				}
			}

			if ($limit >= 200) {
				$numpag = round($response->result->total_results / 200) + 1;

				for ($j = 2; $j <= ($numpag); $j++) {

					if ($response = $this->get_response('campaign', $args, 'result', $j)) {

						for ($i = 0; $i < ($response->result->total_results - 200); $i++) {
							$campaign = (object)$response->result->campaign[$i];

							if (isset($campaign->id)) {
								$campaigns[(int)$campaign->id] = $this->SimpleXMLElement_to_stdClass($campaign);
							}
						}
					}
				}
			}
		}

		return $campaigns;
	}

	/**
	 * Returns an account object from Pardot's API
	 *
	 * The structure of the campaign objects are based on Pardot's API returned XML format captured using PHP's SimpleXML
	 * and converted to an array of stdClass objects.
	 *
	 * @param array $args Combined authorization parameters and query arguments.
	 * @return object A stdClass account object, or false if API call failed.
	 *
	 * @since 1.0.0
	 */
	function get_account(array $args = [])
	{
		$account = false;
		if ($response = $this->get_response('account', $args, 'account')) {
			$response = $this->SimpleXMLElement_to_stdClass($response);
			if (property_exists($response, 'account')) {
				$account = $response->account;
			}
		};
		return $account;
	}

	/**
	 * Returns an array of form objects from Pardot's API
	 *
	 * The structure of the form objects are based on Pardot's API returned XML format captured using PHP's SimpleXML
	 * and converted to an array of stdClass objects.
	 *
	 * @param array $args Combined authorization parameters and query arguments.
	 * @return array|bool Array of form objects, or false if API call failed.
	 *
	 * @since 1.0.0
	 */
	function get_forms(array $args = [])
	{
		$forms = false;
		if ($response = $this->get_response('form', $args)) {

			$forms = [];

			if ($response->result->total_results >= 200) {
				$limit = 200;
			} else {
				$limit = $response->result->total_results;
			}

			for ($i = 0; $i < $limit; $i++) {
				$form = $response->result->form[$i];
				$forms[(int)$form->id] = $this->SimpleXMLElement_to_stdClass($form);
			}

			if ($limit >= 200) {
				$numpag = round($response->result->total_results / 200) + 1;
				for ($j = 2; $j <= ($numpag); $j++) {
					if ($response = $this->get_response('form', $args, 'result', $j)) {
						$count = count($response->result->form);
						for ($i = 0; $i < $count; $i++) {
							$form = $response->result->form[$i];
							$forms[(int)$form->id] = $this->SimpleXMLElement_to_stdClass($form);
						}
					}
				}
			}

		};
		return $forms;
	}

	/**
	 * Returns an dynamic content from Pardot's API
	 *
	 * The structure of the dynamic content objects are based on Pardot's API returned XML format captured using PHP's SimpleXML
	 * and converted to an array of stdClass objects.
	 *
	 * @param array $args Combined authorization parameters and query arguments.
	 * @return array|bool Array of form objects, or false if API call failed.
	 *
	 * @since 1.1.0
	 */
	function get_dynamicContent(array $args = [])
	{
		$dynamicContents = false;

		if ($response = $this->get_response('dynamicContent', $args)) {

			$dynamicContents = [];

			if ($response->result->total_results >= 200) {
				$limit = 200;
			} else {
				$limit = $response->result->total_results;
			}

			for ($i = 0; $i < $limit; $i++) {
				$dynamicContent = $response->result->dynamicContent[$i];
				$dynamicContents[(int)$dynamicContent->id] = $this->SimpleXMLElement_to_stdClass($dynamicContent);
			}

			if ($limit >= 200) {
				$numpag = round($response->result->total_results / 200) + 1;

				for ($j = 2; $j <= ($numpag); $j++) {

					if ($response = $this->get_response('dynamicContent', $args, 'result', $j)) {

						for ($i = 0; $i < ($response->result->total_results - 200); $i++) {
							$dynamicContent = $response->result->dynamicContent[$i];
							$dynamicContents[(int)$dynamicContent->id] = $this->SimpleXMLElement_to_stdClass($dynamicContent);
						}
					}
				}
			}

		};

		return $dynamicContents;
	}

	/**
	 * Returns an object or array of stdClass objects from an SimpleXMLElement
	 *
	 * @note Leading and trailing space are trim()ed.
	 * @see http://www.bookofzeus.com/articles/convert-simplexml-object-into-php-array/
	 * @since 1.0.0
	 *
	 * @param SimpleXMLElement $xml
	 *
	 * @return object
	 */
	function SimpleXMLElement_to_stdClass($xml)
	{

		$array = [];

		foreach ($xml as $element) {

			$tag = $element->getName();

			$array[$tag] = (0 === count($element->children()))
				? trim((string)$element)
				: $this->SimpleXMLElement_to_stdClass($element);
		}

		return (object)$array;
	}

	/**
	 * Set the auth properties of the Pardot_API.
	 *
	 * @param array $auth Values 'client_id', 'client_secret', 'business_unit_id', 'api_key' and 'refresh_token' supported.
	 * @return void
	 *
	 * @since 1.0.0
	 * x     */
	function set_auth(array $auth = [])
	{
		/**
		 * First clear all the auth values.
		 */
		$this->api_key = $this->client_id = $this->client_secret = $this->business_unit_id = $this->refresh_token = null;
		if (!empty($auth['client_id'])) {
			$this->client_id = $auth['client_id'];
		}
		if (!empty($auth['client_secret'])) {
			$this->client_secret = $auth['client_secret'];
		}
		if (!empty($auth['business_unit_id'])) {
			$this->business_unit_id = $auth['business_unit_id'];
		}
		if (!empty($auth['api_key'])) {
			$this->api_key = $auth['api_key'];
		}
		if (!empty($auth['refresh_token'])) {
			$this->refresh_token = $auth['refresh_token'];
		}

	}

	/**
	 * Checks if this Pardot_API object has the necessary properties set for authentication.
	 *
	 * @return boolean Returns true if this object has 'client_id', 'client_secret', 'business_unit_id'
	 *
	 * @since 1.0.0
	 * x     */
	function has_auth()
	{
		return !empty($this->client_id) && !empty($this->client_secret) && !empty($this->business_unit_id);
	}

	/**
	 * Calls Pardot_API and returns response.
	 *
	 * Checks if this object has required properties for authentication. If yes and not authenticated, authenticates.
	 * Next, build the API user and calls the API. On error, attempt to authenticate to retrieve a new API key unless
	 * this is an authentication request, to avoid infinite loops.
	 *
	 * @param string $item_type One of 'login', 'account', 'campaign' or 'form'.
	 * @param array $args Query arguments (but might contain ignored auth arguments.
	 * @param string $property Property to retrieve; defaults to 'result' but can be 'api_key' or 'account'.
     * @param int $paged
     * @param int $retries Number of retries when encountering auth error code 184
	 * @return bool|SimpleXMLElement Returns API response as a SimpleXMLElement if successful, false if API call fails.
	 *
	 * @since 1.0.0
	 */
	function get_response($item_type, $args = [], $property = 'result', $paged = 1, $retries = 0)
	{
		$this->error = false;
		if (!$this->has_auth()) {
			$this->error = 'Cannot authenticate. Missing credentials.';
			return false;
		}

		if (!$this->api_key && !$item_type != 'login') {
			$this->authenticate($args);
		}

		$headers = [
			'Authorization' => 'Bearer ' . $this->api_key,
			'Pardot-Business-Unit-Id' => $this->business_unit_id,
		];

		$body = [
			'offset' => $paged > 1 ? ($paged - 1) * 200 : 0,
		];

		$http_response = wp_remote_post(
			$this->_get_url($item_type, $args),
			array_merge([
				'timeout' => '30',
				'redirection' => '5',
				'method' => 'POST',
				'blocking' => true,
				'compress' => false,
				'decompress' => true,
				'sslverify' => false,
				'headers' => $headers,
				'body' => $body,
			], $body)
		);

		$response = false;
		if (wp_remote_retrieve_response_code($http_response) == 200) {
			// Add a check for disabled accounts: https://wordpress.org/support/topic/if-the-account-gets-disabled-this-plugin-throws-a-fatal-error/
			if (is_string(wp_remote_retrieve_body($http_response)) && strpos(wp_remote_retrieve_body($http_response), 'Your account has been disabled') !== false) {
				$this->error = true;
			}
			$response = new SimpleXMLElement(wp_remote_retrieve_body($http_response));
			if (!empty($response->err)) {
				if ('Your account is unable to use version 4 of the API.' == $response->err) {
					Pardot_Settings::set_setting('version', '3');
				} elseif ('Your account must use version 4 of the API.' == $response->err) {
					Pardot_Settings::set_setting('version', '4');
				}
				$this->error = $response->err;
				if ('login' == $item_type) {
					$this->api_key = false;
				} else {
					$auth = $this->get_auth();

					if ($this->authenticate($auth) && $response->err != 'Daily API rate limit met.' && $response->err != 'This API user lacks sufficient permissions for the requested operation') {
						/**
						 * Try again after a successful authentication
						 */
						$response = $this->get_response($item_type, $args, $property);
						if ($response)
							$this->error = false;
					}
				}
			}

			if ($this->error)
				$response = false;

			if ($response && empty($response->$property)) {
				$response = false;
				$this->error = "HTTP Response did not contain property: {$property}.";
			}

		} elseif (wp_remote_retrieve_response_code($http_response) >= 400 && wp_remote_retrieve_response_code($http_response) <= 499) {
			$response = new SimpleXMLElement(wp_remote_retrieve_body($http_response));
			if ($response->children()->err->attributes()->code == 184 && $retries < 3) {
				$retries += 1;
				$this->api_key = '';
				$response = $this->get_response($item_type, [], $property, 1, $retries);
			} else {
				$this->error = 'Authentication failed. Please reset settings and try again (Error: ' . $response->err . ')';
				$response = false;
			}
		}

		return $response;
	}

	/**
	 * Returns array of auth parameter based on the auth properties of this Pardot_API object
	 *
	 * @return array containing client_id, client_secret, business_unit_id, and refresh_token
	 *
	 * @since 1.0.0
	 */
	function get_auth()
	{
		return [
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'business_unit_id' => $this->business_unit_id,
			'refresh_token' => $this->refresh_token,
		];
	}

	/**
	 * Simple helper function to return the URL required for an $item_type specific Pardot API.
	 *
	 * This function could easily require significant modification to support the complete API which probably
	 * means a significant rearchitecture.  However, it's a black box and it's $args 2nd parameter should enable
	 * it to evolve as needed assume the $item_type continues to be a central concept in the Pardot API.
	 *
	 * @param string $item_type Item type requested; 'account', 'form', 'campaign' and (special case) 'login' tested.
	 * @param array $args Authorization values ('client_id', 'client_secret', 'business_unit_id', and 'api_key') for 'login', nothing for the rest.
	 * @return string Url for a valid API call.
	 *
	 * @since 1.0.0
	 */
	private function _get_url($item_type, $args = [])
	{
		if ('login' == $item_type) {
			$this->set_auth($args);
			$url = str_replace('%%VERSION%%', self::_get_version(), self::$LOGIN_URL_PATH_TEMPLATE);
		} else {
			$url = str_replace(
				['%%VERSION%%', '%%ITEM_TYPE%%', '%%ACTION%%'],
				[self::_get_version(), $item_type, 'account' == $item_type ? 'read' : 'query'],
				self::$URL_PATH_TEMPLATE
			);
		}
		return self::API_ROOT_URL . $url;
	}

	/**
	 * Update to the correct API version when necessary
	 *
	 * @param boolean $override_version Look for overriding API version string
	 * @return string Url for a valid API call.
	 *
	 * @since 1.4.1
	 */
	private function _get_version()
	{
		if (Pardot_Settings::get_setting('version')) {
			return Pardot_Settings::get_setting('version');
		} else {
			return self::VERSION;
		}
	}
}
