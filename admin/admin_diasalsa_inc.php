<?php
require_once( DIASALSA_PKG_PATH.'SalsaAction.php' );

if( !empty( $_REQUEST['diasalsa_preferences'] )) {
	if( !empty($_REQUEST['diasalsa_admin_password']) && !empty($_REQUEST['diasalsa_admin_password_confirm']) && ($_REQUEST['diasalsa_admin_password'] == $_REQUEST['diasalsa_admin_password_confirm'] )){
		$gBitSystem->storeConfig( 'diasalsa_admin_email', $_REQUEST['diasalsa_admin_email'], DIASALSA_PKG_NAME );
		$gBitSystem->storeConfig( 'diasalsa_admin_password', $_REQUEST['diasalsa_admin_password'], DIASALSA_PKG_NAME );
		$gBitSystem->storeConfig( 'diasalsa_organization_key', $_REQUEST['diasalsa_organization_key'], DIASALSA_PKG_NAME );
		$gBitSmarty->assign( 'successMsg', 'DIA Salsa administration settings updated.' );
	}
	else{
		$gBitSmarty->assign( 'errorMsg', 'Password confirmation did not match. Please reenter your password.' );
	}
}


