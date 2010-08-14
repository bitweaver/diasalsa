<?php
/**
 * required setup
 */
require_once( '../kernel/setup_inc.php' );

// $gBitSystem->verifyPackage( 'diasalsa' );

require_once( DIASALSA_PKG_PATH.'lookup_action_inc.php' );

//must be owner or admin to edit an existing post
if( $gContent->isValid() ) {
	$gContent->verifyUpdatePermission();
} else {
	$gContent->verifyCreatePermission();
}

// user canceled out get us out of here
if( !empty( $_REQUEST['cancel'] ) ) {
	if(  $gContent->isValid() ) {
		bit_redirect( $gContent->getDisplayUrl() );
	}else{
		bit_redirect( DIASALSA_PKG_URL );
	}
}

// store
if ( !empty( $_REQUEST['store_action'] ) || !empty( $_REQUEST['store_action_continue'] ) ) {
	// Editing page needs general ticket verification
	$gBitUser->verifyTicket();
	
	// If we're storing we need to get an admin cookie from Salsa
	$gContent->loginAdmin();

	// store action
	if( $gContent->store( $_REQUEST ) ) {
		if( !empty( $_REQUEST['store_action'] ) ){
			// clean up
			$gContent->closeConnection();

			// redirect to action view
			header( "location: ".$gContent->getDisplayUrl() );
			die;
		}
		else{
			// user has requested to continue editing
			$gContent->load();
		}
	}
	else{
		$preview = TRUE;
	}

	// clean up
	$gContent->closeConnection();
}

// preview
if( !empty( $preview ) || !empty( $_REQUEST["preview"] ) ){
	$gContent->preparePreview( $_REQUEST );
	$gContent->invokeServices( 'content_preview_function' );
	$gBitSmarty->assign( 'preview', TRUE );
}
// load to edit
else{
	// salsa admin required to get data
	$gContent->loginAdmin();
	// we want remote data as well if editing an existing action
	if( $gContent->isValid() ){
		// get remote data
		$gContent->loadSalsaActionContentDetail();
	}
	// get targets and target options
	$gContent->loadSalsaActionTargets();
	// close the admin connection
	$gContent->closeConnection();

	// load up any linked content
	$gContent->loadLinkContent();

	$gContent->invokeServices( 'content_edit_function' );
}

$gBitSmarty->assign_by_ref( 'action', $gContent->mInfo );

$gBitSmarty->assign_by_ref( 'target_type_desc', $gContent->mTargetTypeDesc );

$gBitThemes->loadAjax( 'mochikit', array( 'Iter.js', 'DOM.js' ));
$gBitThemes->loadJavascript( DIASALSA_PKG_PATH.'scripts/DIASalsa.js', TRUE );
 
$gBitSmarty->assign_by_ref( 'errors', $gContent->mErrors );

// to facilitate uploading an image during action editing we include the attachment form, 
// however it must NOT use ajax since preflight storage of the action content
// is not compatible with the complexity of action storage
$gBitSystem->setConfig('liberty_attachment_style', 'standard');

// display edit form
$gBitSystem->display( 'bitpackage:diasalsa/edit_action.tpl', tra( "Edit Action" ), array( 'display_mode' => 'edit' ));
