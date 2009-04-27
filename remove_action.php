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
$gContent->verifyAdminPermission();

// nuke it if request confirmed
if( isset( $_REQUEST["confirm"] ) ) {
	$gBitUser->verifyTicket();

	// If we're expunging we need to get an admin cookie from Salsa
	$gContent->loginAdmin();

	if( $gContent->expunge() ) {
		bit_redirect( DIASALSA_PKG_URL );
	} else {
		$feedback['error'] = $gContent->mErrors;
		$gBitSmarty->assign( 'feedback', $feedback );
		$gBitSystem->setBrowserTitle( 'There were errors trying to remove '.$gContent->getTitle() );		
		vd( $gContent->mErrors );
		$gBitSystem->fatalError( $gContent->mErrors );
	}

	// clean up
	$gContent->closeConnection();
}
else{
	// confirm request
	$gBitSystem->setBrowserTitle( 'Confirm removal of '.$gContent->getTitle() );		
	$msgHash = array(
		'label' => tra('Remove Action'),
		'confirm_item' => $gContent->getTitle(),
		'warning' => tra( 'This will remove the action.' ),
		'error' => tra( 'This cannot be undone!' ),
	);

	$formHash['action_id'] = $gContent->mActionId;

	$gBitSystem->confirmDialog( $formHash, $msgHash );
}


