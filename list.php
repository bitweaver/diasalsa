<?php
/**
 * @package diasalsa
 * @subpackage functions
 */
 
/**
 * Initial Setup
 */
require_once( '../kernel/setup_inc.php' );

// Is package installed and enabled
$gBitSystem->verifyPackage( 'diasalsa' );

require_once( DIASALSA_PKG_PATH.'lookup_action_inc.php');

// Now check permissions to access this page
$gContent->verifyViewPermission();

/*
// Use liberty to get a list
$centerModuleParams = array( 
	"layout_area" =>  "c",
	"module_rows" =>  10,
	"module_rsrc" =>  $gContent->getViewTemplate( 'list' ),
	"params" => "",
	"cache_time" => 0,
	"groups" => null,
	"pos" => 1,
	"content_type_guid" => $gContent->getContentType(),
	"module_params" => array(),
);

$centerModuleParams['module_params'] = $centerModuleParams;

// glitch in liberty requires content_type_guid to be set in $_REQUEST
// should be read in from hash above
$_REQUEST['content_type_guid'] = $gContent->getContentType();

$gCenterPieces = array();
array_push( $gCenterPieces, $centerModuleParams );
*/

$actionList = $gContent->getActionList( $_REQUEST );
// vd( $actionList );
$gBitSmarty->assign_by_ref( 'actionList', $actionList );

// getList() has now placed all the pagination information in $_REQUEST['listInfo']
$gBitSmarty->assign_by_ref( 'listInfo', $_REQUEST['listInfo'] );

// Display the template
$gBitSystem->display( 'bitpackage:diasalsa/list_actions.tpl', 'Actions' , array( 'display_mode' => 'list' ));
?>
