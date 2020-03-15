<?php
/**
 * Plugin Name: Flush Azure Cache & Deploy hook
 * Description: Enables purge Azure CDN Cache action from WP UI;+automatically touches file when WP2Static deployment is finished.
 * Version: 1.0.0
 * Author: Vladimir Smitka, Vaclav Jirovsky
 * Author URI: https://lynt.cz, https://vjirovsky.cz
 * License: MIT
*/

// If this file is called directly, abort.
if (! defined("WPINC")) {
  die;
   } 


function lynt_azure_cache_flush( $wp_admin_bar ) {

	if ( ! function_exists( 'current_user_can' ) || ! is_user_logged_in() ) {
		return false;
	}
        $prefix = '';
	if (isset($_GET['flush_result']) && $_GET['flush_result'] === 'ok') $prefix = '✔️';
	if (isset($_GET['flush_result']) && $_GET['flush_result'] === 'fail') $prefix = '❌';
	$wp_admin_bar->add_menu( array(
					'parent' => '',
					'id' => 'delete-azure-cache',
					'title' => $prefix . 'Flush Azure CDN',
					'meta' => array( 'title' =>  'Purges Azure CDN cache. Changes will take effect within 15 minutes.' ),
					'href' => wp_nonce_url( admin_url( 'index.php?action=deleteazurecache'), 'delete-azure-cache' )
					) );
	
}


function lynt_delete_azure_cache()
{
	//SETUP variables
	//TODO: hardcoded variables
	$AZ_CLIENT_ID = '--AZURE-AAD-APP-CLIENT-ID--';
	$AZ_CLIENT_SECRET = '--AZURE-AAD-APP-SECRET-ID--';
	$AZ_CDN_SUBSCRIPTION_ID = '--AZURE-SUBSCRIPTION-ID-WHERE-IS-CDN-DEPLOYED--';
	$AZ_CDN_RG = '--AZURE-RESOURCE-GROUP-NAME-WHERE-IS-CDN-DEPLOYED--';
	$AZ_CDN_PROFILE_NAME = '--AZURE-CDN-PROFILE-NAME--';
	$AZ_CDN_ENDPOINT_NAME = '--AZURE-CDN-ENDPOINT-NAME--';


	$az_login_resource = 'https://management.azure.com/';
	$az_login_url = 'https://login.microsoftonline.com/' . $AZ_CDN_SUBSCRIPTION_ID . '/oauth2/token';
	$grant_type='client_credentials';
	$scope_to_purge = '*';
	$purgeUrl = 'https://management.azure.com/subscriptions/' . $AZ_CDN_SUBSCRIPTION_ID . '/resourceGroups/'. $AZ_CDN_RG .'/providers/Microsoft.Cdn/profiles/' . $AZ_CDN_PROFILE_NAME . '/endpoints/' . $AZ_CDN_ENDPOINT_NAME . '/purge?api-version=2019-04-15';

    if (!wp_verify_nonce($_GET['_wpnonce'], 'delete-azure-cache')) {
        wp_die('neověřeno');
    }

	// SECTION - get login access token
	$fields_for_login = array(
		'grant_type' => urlencode($grant_type),
		'client_id' => urlencode($AZ_CLIENT_ID),
		'client_secret' => urlencode($AZ_CLIENT_SECRET),
		'resource' => urlencode($az_login_resource),
	);
	$fields_for_login_string = "";

	//POST format
	foreach($fields_for_login as $key=>$value) { 
		$fields_for_login_string .= $key.'='.$value.'&'; 
	}
	rtrim($fields_for_login_string, '&');

	$ch = curl_init();

	curl_setopt($ch,CURLOPT_URL, $az_login_url);
	curl_setopt($ch,CURLOPT_POST, count($fields_for_login));
	curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_for_login_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$result = curl_exec($ch);
	if($result === false)
	{
		wp_die('Failed to get AZ access token.');
	}

	$login_data = json_decode($result);
	$access_token = $login_data->access_token;
	curl_close($ch);

	// SECTION - purge CDN

	//POST 
	$fields_for_purge = array(
		'contentPaths' => array(
			'/*',
		),
	);
	$fields_for_purge_string = json_encode($fields_for_purge);

	$ch = curl_init();

	curl_setopt($ch,CURLOPT_URL, $purgeUrl);
	curl_setopt($ch,CURLOPT_POST, 1);
	curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_for_purge_string);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
		'Content-Type: application/json',                                                                                
		'Content-Length: ' . strlen($fields_for_purge_string),
		'Authorization: Bearer '. $access_token,
	)); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$result = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if($result === false)
	{
		wp_die('Failed to trigger purge - HTTP code: '. $httpcode );
	}
	curl_close($ch);
	if($httpcode == 202){
		$return_url = $_SERVER['HTTP_REFERER'];
		$return_url .= (parse_url($return_url, PHP_URL_QUERY) ? '&' : '?') . 'flush_result=ok';
		wp_redirect( $return_url );
		
	}
	else{
		wp_die("CDN invalidation failed with response HTTP code " . $httpcode . ".");
	}

    exit();
}

function lynt_deploy_hook($data) {
  file_put_contents(WP_CONTENT_DIR . "/uploads/deploy.txt", time());
}


add_action( 'admin_bar_menu', 'lynt_azure_cache_flush', 100 );
add_action( 'admin_action_deleteazurecache', 'lynt_delete_azure_cache' );
add_filter( 'wp2static_post_deploy_trigger', 'lynt_deploy_hook' );
