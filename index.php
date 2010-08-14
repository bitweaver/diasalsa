<?php
/**
 * required setup
 */
require_once( '../kernel/setup_inc.php' );

// if we have a action_id, we display the correct action - otherwise we simply display recent posts
if( @BitBase::verifyId( $_REQUEST['action_id'] )) {
	include_once( DIASALSA_PKG_PATH.'display_action_inc.php' );
} else {
	include_once( DIASALSA_PKG_PATH.'list.php' );
}
?>
