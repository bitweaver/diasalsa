<?php
require_once( '../kernel/setup_inc.php' );

$gBitSystem->verifyPackage( 'diasalsa' );

// list results will be assigned to contentList by liberty
$_REQUEST['output'] = 'raw';
$contentList = array();
include_once( LIBERTY_PKG_PATH.'list_content.php' );
$gBitSmarty->assign_by_ref('listcontent', $contentList);

$gBitSmarty->display( 'bitpackage:diasalsa/content_list_inc.tpl' );
