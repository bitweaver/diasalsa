<?php
global $gContent;

require_once( DIASALSA_PKG_PATH.'SalsaAction.php' );

require_once( LIBERTY_PKG_PATH.'lookup_content_inc.php' );

if( empty( $gContent ) || !is_object( $gContent ) || !$gContent->isValid() ) {
	// if someone gives us a action_key we try to find it
	if( !empty( $_REQUEST['action_key'] ) && is_numeric( $_REQUEST['action_key'] ) ){
		global $gBitDb;
		$_REQUEST['action_id'] = $gBitDb->getOne( "SELECT action_id FROM `".BIT_DB_PREFIX."diasalsa_actions` a WHERE a.`key_id`=?", array($_REQUEST['action_key']) );
		if( empty( $_REQUEST['action_id'] ) ) {
		  $gBitSystem->fatalError(tra('No action found with key id: ').$_REQUEST['action_key']);
		}
	}

	// if someone gives us a action_name we try to find it
	if( !empty( $_REQUEST['action_name'] ) ){
		global $gBitDb;
		$_REQUEST['action_id'] = $gBitDb->getOne( "SELECT action_id FROM `".BIT_DB_PREFIX."diasalsa_actions` a LEFT JOIN `".BIT_DB_PREFIX."liberty_content` lc ON (a.`content_id` = lc.`content_id`) WHERE lc.`title` = ?", array($_REQUEST['action_name']) );
		if( empty( $_REQUEST['action_id'] ) ) {
		  $gBitSystem->fatalError(tra('No action found with the name: ').$_REQUEST['action_name']);
		}
	}

	// if action_id supplied, use that
	if( @BitBase::verifyId( $_REQUEST['action_id'] ) ) {
		$gContent = new SalsaAction( $_REQUEST['action_id'] );
		$gContent->load();
	} elseif( @BitBase::verifyId( $_REQUEST['content_id'] ) ) {
		$gContent = new SalsaAction( NULL, $_REQUEST['content_id'] );
		$gContent->load();
	} else {
		$gContent = new SalsaAction();
	}

	$gBitSmarty->assign_by_ref( 'gContent', $gContent );
}
