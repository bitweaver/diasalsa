<?php

require_once( '../kernel/setup_inc.php' );

$gBitSystem->verifyPackage( 'diasalsa' );
require_once( DIASALSA_PKG_PATH.'lookup_action_inc.php' );

// check permissions to access this page
if( !$gContent->isValid() ) {
	$gBitSystem->setHttpStatus( 404 );
	$gBitSystem->fatalError( "The action you requested could not be found." );
}
$gContent->verifyAdminPermission();

// include display services - required to get the proper group related layout
$displayHash = array( 'perm_name' => $gContent->mViewContentPerm );
$gContent->invokeServices( 'content_display_function', $displayHash );

// assign content to smarty
$gBitSmarty->assign_by_ref('action', $gContent->mInfo);

// check if expired
$now = $gBitSystem->getUTCTime();	
if( $gContent->mInfo['expire_date'] < $now ){
	$expired = TRUE;
	$gBitSmarty->assign( 'expired', $expired );
}

// get lists of who has and has not sent a letter ( group pkg dependency )
if( $gBitSystem->isPackageActive('group') ){
	// get non group members who have sent letters
	$listHash = array( 'exclude_group' => true );
	$nonGroupSenders = $gContent->getSendersList( $listHash );
	$gBitSmarty->assign( 'nonGroupSenders', $nonGroupSenders );
	// vd( $nonGroupSenders );

	// get group members who have sent letters
	$groupSenders = $gContent->getSendersList();
	$gBitSmarty->assign( 'groupSenders', $groupSenders );
	// vd( $groupSenders );

	// get group members who have NOT sent letters
	$groupNonSenders = $gContent->getNonSendersList();
	$gBitSmarty->assign( 'groupNonSenders', $groupNonSenders );
	// vd( $groupNonSenders );

	// if we have a request to send an email process it
	if ( isset($_REQUEST['send_email']) && !empty( $_REQUEST['email_targets'] ) && $gBitSystem->isPackageActive('switchboard') ) {
		$subject = $_REQUEST['email_subject'];
		$body = $_REQUEST['email_body'];
		$bodyHash['message'] = $body;
		$usersHash = array( 'email', 'real_name', 'login', 'user_id' );
		switch( $_REQUEST['email_targets'] ){
			case "all":
				$emailHash['recipients'] = $gContent->getMembers();
				break;
			case "senders":
				$emailHash['recipients'] = $groupSenders;
				break;
			case "nonsenders":
				$emailHash['recipients'] = $groupNonSenders;
				break;
		}
		$emailHash['subject'] = $subject;
		$emailHash['message'] = $bodyHash;
		$gSwitchboardSystem->sendEmail( $emailHash );
		$gBitSmarty->assign( 'successEmailMsg', 'Email sent!' );
	}
}

// handle send request
$gBitSystem->display( 'bitpackage:diasalsa/manage_action.tpl' , ('Action: '.$gContent->getTitle()) , array( 'display_mode' => 'display' ));
