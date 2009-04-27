<?php
/**
 * @package diasalsa
 * @subpackage functions
 */

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

// include display services
$displayHash = array( 'perm_name' => $gContent->mViewContentPerm );
$gContent->invokeServices( 'content_display_function', $displayHash );

// parse all data
$gContent->parseAllData();

// assign content to smarty
$gBitSmarty->assign_by_ref('action', $gContent->mInfo);

// check if expired
$now = $gBitSystem->getUTCTime();	
if( $gContent->mInfo['expire_date'] < $now ){
	$expired = TRUE;
	$gBitSmarty->assign( 'expired', $expired );
}

// handle send request
/* we cant do it this way - save this in case the world ever changes
if( !empty( $_REQUEST['send_letter'] ) && !$expired ){
	if( $gContent->sendLetter( $_REQUEST ) ){
		// no errors display thank you
		$gBitSystem->display( 'bitpackage:diasalsa/view_thankyou.tpl' , ('Action: '.$gContent->getTitle()) , array( 'display_mode' => 'display' ));
		die;
	}else{
		$letterPreview = $gContent->prepareSendLetterPreview( $_REQUEST );
		$gBitSmarty->assign_by_ref( 'letterPreview', $letterPreview );
	}
}
*/

// display the template
if( empty( $_REQUEST['thankyou'] ) ){
	// log a hit
	$gContent->addHit();

	$gBitThemes->loadAjax( 'mochikit', array( 'Iter.js', 'DOM.js' ));
	$gBitThemes->loadJavascript( DIASALSA_PKG_PATH.'scripts/DIASalsa.js', TRUE );

	$gBitSystem->display( 'bitpackage:diasalsa/view_action.tpl' , ('Action: '.$gContent->getTitle()) , array( 'display_mode' => 'display' ));
}else{
	$gBitSmarty->assign( 'gHideModules', TRUE );
	$gBitSystem->display( 'bitpackage:diasalsa/view_thankyou.tpl' , ('Action: '.$gContent->getTitle()) , array( 'display_mode' => 'display' ));
}
