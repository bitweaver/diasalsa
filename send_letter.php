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

// check permissions to access this page
if( !$gContent->isValid() ) {
	$gBitSystem->setHttpStatus( 404 );
	$gBitSystem->fatalError( "The action you requested could not be found." );
}
$gContent->verifyViewPermission();

// send letter
if( $gContent->sendLetter( $_REQUEST ) ) {
	$gBitSmarty->assign( 'thankyou', TRUE );
}

