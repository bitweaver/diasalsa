<?php
/**
 * required setup
 */
require_once( '../bit_setup_inc.php' );
/**
 * Initialization
 */
$gBitSystem->verifyPackage( 'diasalsa' );
require_once( DIASALSA_PKG_PATH.'lookup_action_inc.php' );

$ret = 'fail';

// check permissions to access this page
if( !$gContent->isValid() ) {
	$gBitSystem->setHttpStatus( 404 );
}else{
	/*
	$gContent->verifyViewPermission();
	*/

	// register email sent
	// param makes it a slight hassle for someone to ping this 
	if( !empty( $_REQUEST['register_letter_sent'] ) && !empty( $_REQUEST['email'] ) && $gContent->registerLetterSent( $_REQUEST ) ){
		$urlHash = array( 'override_pretty_urls' => TRUE );
		$ret = BIT_BASE_URI.$gContent->getDisplayUrl( NULL, $urlHash ).'&thankyou=y';
	}
}

print_r( $ret );
die;
