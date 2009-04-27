<?php
/**
 * @package diasalsa
 */

global $gBitSystem, $gBitUser, $gBitSmarty;
	
define( 'LIBERTY_SERVICE_DIASALSA_ACTION', 'diasalsa_action' );

$registerHash = array(
	'package_name' => 'diasalsa',
	'package_path' => dirname( __FILE__ ).'/',
	'service'	   => LIBERTY_SERVICE_DIASALSA_ACTION,
);

$gBitSystem->registerPackage( $registerHash );

if( $gBitSystem->isPackageActive( 'diasalsa' ) ) {

	$menuHash = array(
		'package_name'       => DIASALSA_PKG_NAME,
		'index_url'          => DIASALSA_PKG_URL.'index.php',
		'menu_template'      => 'bitpackage:diasalsa/menu_diasalsa.tpl',
	);

	require_once( DIASALSA_PKG_PATH.'SalsaAction.php' );

	$gLibertySystem->registerService( LIBERTY_SERVICE_DIASALSA_ACTION, DIASALSA_PKG_NAME, array(
		'content_load_sql_function' => 'diasalsa_content_load_sql',
		'content_edit_function' 	=> 'diasalsa_content_edit',
		'content_store_function'  	=> 'diasalsa_content_store',
		'content_preview_function'  => 'diasalsa_content_preview',
		'content_expunge_function'  => 'diasalsa_content_expunge',
		'content_body_tpl'        => 'bitpackage:diasalsa/service_content_inc.tpl',
		'content_edit_mini_tpl'		=> 'bitpackage:diasalsa/edit_diasalsa_mini_inc.tpl',
	));

	$gBitSystem->registerAppMenu( $menuHash );
}
