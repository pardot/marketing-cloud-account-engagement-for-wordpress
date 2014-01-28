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
class Pardot_API {
	/**
	 * @const string The root URL for the Pardot API.
	 *
	 * @since 1.0.0
	 */
	const ROOT_URL = 'https://pi.pardot.com/api';

	/**
	 * @const string The supported version of the Pardot API, this value is embedded into the API's URLs
	 *
	 * @since 1.0.0
	 */
	const VERSION = '3';

	/**
	 * @var string Defacto constant defining the URL path template for the API.
	 * @note This classes defines and replaces the three (3) template variables %%ITEM_TYPE%%, %%VERSION%% and %%ACTION%%.
	 * 	%%ITEM_TYPE%%: One of 'login', 'account', 'campaign' or 'form'.
	 * 	%%VERSION%%: Pardto_API::VERSION
	 * 	%%ACTION%%: For %%ITEM_TYPE%% == 'account' otherwise 'query'
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
	 * @var string A user entered email address that is expected to have a valid Pardot account.
	 *
	 * @since 1.0.0
	 */
	var $email;

	/**
	 * @var string A user-entered password for the valid Pardot account indentified by $this->email.
	 *
	 * @since 1.0.0
	 */
	var $password;

	/**
	 * @var string A user-entered but Pardot-supplied key the user accesses from their Pardot account.
	 *
	 * @since 1.0.0
	 */
	var $user_key;

	/**
	 * @var string A key returned on authentication by Pardot's API based on email, password and user_key.
	 *
	 * @since 1.0.0
	 */
	var $api_key = false;

	/**
	 * @var boolean Used to flag the API for retry in case the api_key has expired.
	 *
	 * @since 1.0.0
	 */
	var $api_key_maybe_invalidated = false;

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
	 * @param array $auth Values 'email', 'password', 'user_key' and 'api_key' supported.
	 *
	 * @since 1.0.0
	 */
	function __construct( $auth = array() ) {
		if ( is_array( $auth ) && count( $auth ) )
			$this->set_auth( $auth );
	}

	/**
	 * Call Pardot API to authenticate and retrieve API Key
	 *
	 * The $auth parameters passed will be used to authenticate the login request.
	 * If successful $this->api_key will be set.
	 *
	 * @param array $auth Values 'email', 'password', 'user_key' and 'api_key' supported.
	 * @return string|bool An $api_key on success, false on failure.
	 *
	 * @since 1.0.0
	 */
	function authenticate( $auth = array() ) {
		if ( count( $auth ) )
			$this->set_auth( $auth );
		$this->api_key = false;
		if ( $response = $this->get_response( 'login', $auth, 'api_key' ) ) {
			$this->api_key = (string)$response->api_key;
		};
		return $this->api_key;
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
	function is_authenticated() {
		return ! empty( $this->api_key );
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

    function get_campaigns( $args = array() ) {
		$campaigns = false;
		if ( $response = $this->get_response( 'campaign', $args ) ) {
			$campaigns = array();
            if ( $response->result->total_results >= 200 ) {
                $limit = 200;
            } else {
                $limit = $response->result->total_results;
            }

            for( $i = 0; $i < $limit; $i++ ) {
                $campaign = (object)$response->result->campaign[$i];
                if ( isset($campaign->id) ) {
                    $campaigns[(int)$campaign->id] = $this->SimpleXMLElement_to_stdClass( $campaign );
                }
            }

            if ( $limit >= 200 ) {
	            $numpag = round($response->result->total_results/200)+1;
	            for( $j = 2; $j <= ($numpag); $j++ ) {
	                if ( $response = $this->get_response( 'campaign', $args, 'result', $j ) ) {
	                    for( $i = 0; $i < ($response->result->total_results-200); $i++ ) {
	                        $campaign = (object)$response->result->campaign[$i];
	                        if ( isset($campaign->id) ) {
	                            $campaigns[(int)$campaign->id] = $this->SimpleXMLElement_to_stdClass( $campaign );
	                        }
	                    }
	                }
	            }
            }

		}
		return $campaigns;
	}

	/*function get_campaigns( $args = array() ) {
		$campaigns = false;
		if ( $response = $this->get_response( 'campaign', $args ) ) {
			$campaigns = array();
			for( $i = 0; $i < $response->result->total_results; $i++ ) {
				$campaign = (object)$response->result->campaign[$i];
				if ( isset($campaign->id) ) {
					$campaigns[(int)$campaign->id] = $this->SimpleXMLElement_to_stdClass( $campaign );
				}
			}
		}
		return $campaigns;
	}*/

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
	function get_account( $args = array() ) {
		$account = false;
		if ( $response = $this->get_response( 'account', $args, 'account' ) ) {
			$response = $this->SimpleXMLElement_to_stdClass( $response );
			$account = $response->account;
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
	function get_forms( $args = array() ) {
		$forms = false;
		if ( $response = $this->get_response( 'form', $args ) ) {

			$forms = array();

			if ( $response->result->total_results >= 200 ) {
				$limit = 200;
			} else {
				$limit = $response->result->total_results;
			}

			for( $i = 0; $i < $limit; $i++ ) {
				$form = $response->result->form[$i];
				$forms[(int)$form->id] = $this->SimpleXMLElement_to_stdClass( $form );
			}

			if ( $limit >= 200 ) {
				$numpag = round($response->result->total_results/200)+1;
				for( $j = 2; $j <= ($numpag); $j++ ) {
					if ( $response = $this->get_response( 'form', $args, 'result', $j ) ) {
						for( $i = 0; $i < ($response->result->total_results-200); $i++ ) {
							$form = $response->result->form[$i];
							$forms[(int)$form->id] = $this->SimpleXMLElement_to_stdClass( $form );
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
	function get_dynamicContent( $args = array() ) {
		$dynamicContents = false;
		if ( $response = $this->get_response( 'dynamicContent', $args ) ) {

			$dynamicContents = array();

			if ( $response->result->total_results >= 200 ) {
				$limit = 200;
			} else {
				$limit = $response->result->total_results;
			}

			for( $i = 0; $i < $limit; $i++ ) {
				$dynamicContent = $response->result->dynamicContent[$i];
				$dynamicContents[(int)$dynamicContent->id] = $this->SimpleXMLElement_to_stdClass( $dynamicContent );
			}

			if ( $limit >= 200 ) {
				$numpag = round($response->result->total_results/200)+1;
				for( $j = 2; $j <= ($numpag); $j++ ) {
					if ( $response = $this->get_response( 'dynamicContent', $args, 'result', $j ) ) {
						for( $i = 0; $i < ($response->result->total_results-200); $i++ ) {
							$dynamicContent = $response->result->dynamicContent[$i];
							$dynamicContents[(int)$dynamicContent->id] = $this->SimpleXMLElement_to_stdClass( $dynamicContent );
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
	 *
	 * @param SimpleXMLElement $xml
	 * @return array|object A(n array of) stdClass object(s) with structure dictated by the passed SimpleXMLElement object.
	 *
	 * @note Leading and trailing space are trim()ed.
	 *
	 * @see http://www.bookofzeus.com/articles/convert-simplexml-object-into-php-array/
	 *
	 * @since 1.0.0
	 */
	function SimpleXMLElement_to_stdClass( $xml ) {
	  $array = array();
	  foreach ($xml as $element) {
	    $tag = $element->getName();
	    $e = get_object_vars($element);
	    if (!empty($e)) {
	      $array[$tag] = $element instanceof SimpleXMLElement ? $this->SimpleXMLElement_to_stdClass($element) : $e;
	    }
	    else {
	      $array[$tag] = trim($element);
	    }
	  }
	  return (object)$array;
	}

	/**
	 * Set the auth properties of the Pardot_API.
	 *
	 * Sets the properties of the object based on auth values passed via array,
	 * or in all but API_KEY based on these respective constants, if they exist:
	 *
	 * 	- PARDOT_API_EMAIL
	 *  - PARDOT_API_PASSWORD
	 *  - PARDOT_API_USER_KEY
	 *
	 * @param array $auth Values 'email', 'password', 'user_key' and 'api_key' supported.
	 * @return void
	 *
	 * @since 1.0.0
x	 */
	function set_auth( $auth = array() ) {
		/**
		 * First clear all the auth values.
		 */
		$this->email = $this->password = $this->user_key = $this->api_key = null;
		if ( ! empty( $auth['email'] ) ) {
			$this->email = $auth['email'];
		} else if ( empty( $this->email ) && defined( 'PARDOT_API_EMAIL' ) ) {
			$auth['email'] = PARDOT_API_EMAIL;
		}
		if ( ! empty( $auth['password'] ) ) {
			$this->password = $auth['password'];
		} else if ( empty( $this->password ) && defined( 'PARDOT_API_PASSWORD' )  ) {
			$auth['password'] = PARDOT_API_PASSWORD;
		}
		if ( ! empty( $auth['user_key'] ) ) {
			$this->user_key = $auth['user_key'];
		} else if ( empty( $this->user_key ) && defined( 'PARDOT_API_USER_KEY' )  ) {
			$auth['user_key'] = PARDOT_API_USER_KEY;
		}
		if ( ! empty( $auth['api_key'] ) ) {
			$this->api_key = $auth['api_key'];
		}
	}

	/**
	 * Checks if this Pardot_API object has the necessary properties set for authentication.
	 *
	 * @return boolean Returns true if this object has email, password and user_key properties.
	 *
	 * @since 1.0.0
x	 */
	function has_auth() {
		return ! empty( $this->email ) && ! empty( $this->password ) && ! empty( $this->user_key );
	}

	/**
	 * Calls Pardot_API and returns response.
	 *
	 * Checks if this object has required properties for authentication. If yes and not authenticated, authenticates.
	 * Next, build the API user and calls the API. On error, attempt to authenticate to retrieve a new API key unless
	 * this is an authentication request, to avoid infinite loops. If reauthenticated and $args['new_api_key'] is a valid
	 * callback then callsback with new API key so caller can store it.
	 *
	 * @param string $item_type One of 'login', 'account', 'campaign' or 'form'.
	 * @param array $args Query arguments (but might contain ignored auth arguments.
	 * @param string $property Property to retrieve; defaults to 'result' but can be 'api_key' or 'account'.
	 * @return bool|SimpleXMLElement Returns API response as a SimpleXMLElement if successful, false if API call fails.
	 *
	 * @since 1.0.0
	 */
	function get_response( $item_type, $args = array(), $property = 'result', $paged=1 ) {
		$this->error = false;

		if ( ! $this->has_auth() ) {
			$this->error = 'Cannot authenticate. No email, password or user_key assigned.';
			return false;
		}

		if ( ! $this->api_key && 'login' != $item_type ) {
			$this->authenticate( $args );
		}

		$args = array_merge( $args,
			array(
				'user_key' => $this->user_key,
				'api_key' => $this->api_key,
				// Here for Pardot root-level debugging only
				//'act_as_user' => 'test@example.com',
				'offset' => $paged > 1 ? ($paged-1)*200 : 0
			)
		);

		$http_response = wp_remote_request(
			$this->_get_url( $item_type, $args ),
			array_merge( array(
				'timeout' 		=> '30',
				'redirection'   => '5',
				'method' 		=> 'POST',
				'blocking'		=> true,
				'compress'		=> false,
				'decompress'	=> true,
				'sslverify' 	=> false,
				'body'          => $args
			), $args )
		);

		if ( isset($args['email']) ) {
			$args['email'] = urlencode( $args['email'] );
		}

		if ( isset($args['password']) ) {
			$args['password'] = base64_encode( $args['password'] );
		}

		$response = false;
		if( wp_remote_retrieve_response_code( $http_response ) == 200 ) {
			$response = new SimpleXMLElement( wp_remote_retrieve_body( $http_response ) );
			if ( ! empty( $response->err ) ) {
				$this->error = $response->err;
				if ( 'login' == $item_type ) {
					$this->api_key = false;
				} else {
					$auth = $this->get_auth();
					if ( isset( $args['new_api_key'] ) ) {
						$this->api_key_maybe_invalidated = true;
						$auth['new_api_key'] = $args['new_api_key'];
					}
					if ( $this->authenticate( $auth ) ) {
						/**
						 * Try again after a successful authentication
						*/
						$response = $this->get_response( $item_type, $args, $property, true );
						if ( $response )
							$this->error = false;
					}
				}
			}

			if ( $this->error )
				$response = false;

			if ( $response && empty( $response->$property ) ) {
				$response = false;
				$this->error = "HTTP Response did not contain property: {$property}.";
			}

			if ( $response && $this->api_key_maybe_invalidated && 'login' == $item_type && 'api_key' == $property ) {
				if ( isset( $args['new_api_key'] ) && is_callable( $args['new_api_key'] ) ) {
					call_user_func( $args['new_api_key'], (string)$response->api_key );
					$this->api_key_maybe_invalidated = false;
				}
			}

		}
	  return $response;
	}

	/**
	 * Returns array of auth parameter based on the auth properties of this Pardot_API object
	 *
	 * @return array containing email, password and user_key elements.
	 *
	 * @since 1.0.0
	 */
	function get_auth() {
		return array(
			'email' => $this->email,
			'password' => $this->password,
			'user_key' => $this->user_key,
		);
	}

	/**
	 * Simple helper function to return the URL required for an $item_type specific Pardot API.
	 *
	 * This function could easily require significant modification to support the complete API which probably
	 * means a significant rearchitecture.  However, it's a black box and it's $args 2nd parameter should enable
	 * it to evolve as needed assume the $item_type continues to be a central concept in the Pardot API.
	 *
	 * @param string $item_type Item type requested; 'account', 'form', 'campaign' and (special case) 'login' tested.
	 * @param array $args Authorization values ('email','password','user_key') for 'login', nothing for the rest.
	 * @return string Url for a valid API call.
	 *
	 * @since 1.0.0
	 */
	private function _get_url( $item_type, $args = array() ) {
		if ( 'login' == $item_type ) {
			$this->set_auth( $args );
			$base_url = str_replace( '%%VERSION%%', self::VERSION, self::$LOGIN_URL_PATH_TEMPLATE );
			$url = $base_url;
		} else {
			$base_url = str_replace(
				array( '%%VERSION%%', '%%ITEM_TYPE%%', '%%ACTION%%' ),
				array( self::VERSION, $item_type, 'account' == $item_type ? 'read' : 'query' ),
				self::$URL_PATH_TEMPLATE
			);
			$url = $base_url;
		}
		return self::ROOT_URL . $url;
	}
}
