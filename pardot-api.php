<?php
/*
    All requests to the Pardot API must be made via SSL encrypted connection.
    Authentication requests must use HTTP POST.
    Obtain the email, password, and user_key (available in the application under My Settings) for the Pardot user account that will be submitting API requests. If you need assistance in acquiring your user key, contact your Pardot support representative.
*/

if ( !defined('PARDOT_API_EMAIL') )
    define('PARDOT_API_EMAIL', PARDOT::get_setting(PARDOT::settings_account_key, 'email') ); // The email address of your user account

if ( !defined('PARDOT_API_PASSWORD') )
    define('PARDOT_API_PASSWORD', PARDOT::get_setting(PARDOT::settings_account_key, 'password') ); // The password of your user account

if ( !defined('PARDOT_API_USER_KEY') )
    define('PARDOT_API_USER_KEY', PARDOT::get_setting(PARDOT::settings_account_key, 'user_key') ); // The 32-bit hexadecimal user key for your user account


/*
Authentication
A few prerequisites must be met to successfully authenticate a connection to the API.
    All requests to the Pardot API must be made via SSL encrypted connection.
    Authentication requests must use HTTP POST.
    Obtain the email, password, and user_key (available in the application under My Settings) for the Pardot user account that will be submitting API requests. If you need assistance in acquiring your user key, contact your Pardot support representative.
With these requirements met, an API key must be acquired. Both User and API keys are unique to individual users. API keys are valid for 60 minutes. In contrast, user keys are valid indefinitely. To authenticate, issue the following request (having replaced the values denoted by <carets> with values for your account):
Parameter 	Required 	Description
email 	X 	The email address of your user account
password 	X 	The password of your user account
user_key 	X 	The 32-bit hexadecimal user key for your user account
If authentication was successful, a 32-character hexadecimal API key will be returned in the following format:
<rsp stat="ok" version="1.0">
    <api_key>5a1698a233e73d7c8ccd60d775fbc68a</api_key>
</rsp>
Otherwise, the response will contain the following:
<rsp stat="fail" version="1.0">
    <err code="15">Login failed</err>
</rsp>
Subsequent authentication requests will return either the current valid API key or a newly generated API key if the previous one had expired.
@since      2012-04-25
https://pi.pardot.com/api/login/version/3?email={email}&password={password}&user_key={user_key}

 */
function get_pardot_api_key($email = '', $password = '', $user_key = '') {

	if ($email == '')
		$email = PARDOT_API_EMAIL;
	if ($password == '')
		$password = PARDOT_API_PASSWORD;
	if ($user_key == '')
		$user_key = PARDOT_API_USER_KEY;
		

    $url = 	'https://pi.pardot.com/api/login/version/3' . '?email=' . $email . '&password=' . $password . '&user_key=' . $user_key;

    $response = wp_remote_request( $url, array(
        'timeout' 		=> '30',
        'redirection' 	=> '5',
        'method' => 'POST',
        'blocking'		=> true,
        'compress'		=> false,
        'decompress'	=> true,
        'sslverify' => false,
    ));

    if( wp_remote_retrieve_response_code( $response ) == 200 ){
        $obj = new SimpleXMLElement( html_entity_decode( wp_remote_retrieve_body( $response ) ) );
        if (@$obj->api_key != '') {
            return (string) $obj->api_key;
        } else {
            return false;
        }
    } else{
        return false;
    }
}

/*
To get a list of forms, use the query method: https://pi.pardot.com/api/form/version/3/do/query?user_key=X&api_key=Y

*/

function get_pardot_forms() {
    $url = 	'https://pi.pardot.com/api/form/version/3/do/query?user_key=' . PARDOT_API_USER_KEY . '&api_key=' . get_pardot_api_key();
    $response = wp_remote_request( $url, array(
        'timeout' 		=> '30',
        'redirection' 	=> '5',
        'method' => 'POST',
        'blocking'		=> true,
        'compress'		=> false,
        'decompress'	=> true,
        'sslverify' => false,
    ));
	
    if( wp_remote_retrieve_response_code( $response ) == 200 ){
        $obj = new SimpleXMLElement( wp_remote_retrieve_body( $response ) );
        if (@$obj->result != '') {
			$result = array();
			for ($i = 0; $i < $obj->result->total_results; $i++) {
				$form = $obj->result->form[$i];
				$result[(int)$form->id] = $form;
			}
            return $result;
        } else {
            return false;
        }
    } else{
        return false;
    }
}

// https://pi.pardot.com/api/account/version/3/do/read?api_k... 

function get_pardot_account() {
    $url = 	'https://pi.pardot.com/api/account/version/3/do/read?user_key=' . PARDOT_API_USER_KEY . '&api_key=' . get_pardot_api_key();
    $response = wp_remote_request( $url, array(
        'timeout' 		=> '30',
        'redirection' 	=> '5',
        'method' => 'POST',
        'blocking'		=> true,
        'compress'		=> false,
        'decompress'	=> true,
        'sslverify' => false,
    ));
	
    if( wp_remote_retrieve_response_code( $response ) == 200 ){
        $obj = new SimpleXMLElement( wp_remote_retrieve_body( $response ) );
        if (@$obj->account != '') {
            return $obj->account;
        } else {
            return false;
        }
    } else{
        return false;
    }
}

// https://pi.pardot.com/api/campaign/version/3/do/query?user_key=...&api_key=...
function get_pardot_campaign() {
    $url = 	'https://pi.pardot.com/api/campaign/version/3/do/query?user_key=' . PARDOT_API_USER_KEY . '&api_key=' . get_pardot_api_key();
    $response = wp_remote_request( $url, array(
        'timeout' 		=> '30',
        'redirection' 	=> '5',
        'method' => 'POST',
        'blocking'		=> true,
        'compress'		=> false,
        'decompress'	=> true,
        'sslverify' => false,
    ));
	
    if( wp_remote_retrieve_response_code( $response ) == 200 ){
        $obj = new SimpleXMLElement( wp_remote_retrieve_body( $response ) );
        if (@$obj->result != '') {
			$result = array();
			for ($i = 0; $i < $obj->result->total_results; $i++) {
				$campaign = $obj->result->campaign[$i];
				$result[(int)$campaign->id] = $campaign;
			}
            return $result;
        } else {
            return false;
        }
    } else{
        return false;
    }
}

?>