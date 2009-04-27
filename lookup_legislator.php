<?php
/**
 * REST proxy to 
 * http://warehouse.democracyinaction.org/o/0/p/salsa/warehouse/public/lookupLegislator.sjs
 **/

require_once( '../bit_setup_inc.php' );

$gBitSystem->verifyPackage( 'diasalsa' );

require_once( DIASALSA_PKG_PATH.'SalsaAction.php' );

$obj = new SalsaAction();

$xml = $obj->getLegislator( $_REQUEST );

// Since we are returning xml we must report so in the header
// we also need to tell the browser not to cache the page
// see: http://mapki.com/index.php?title=Dynamic_XML
// Date in the past
header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
// always modified
header( "Last-Modified: ".gmdate( "D, d M Y H:i:s" )." GMT" );
// HTTP/1.1
header( "Cache-Control: no-store, no-cache, must-revalidate" );
header( "Cache-Control: post-check=0, pre-check=0", FALSE );
// HTTP/1.0
header( "Pragma: no-cache" );
//XML Header
header( "content-type:text/xml" );

/* 
 * print_r( '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>' );
 */
print_r( $xml );

// die;
