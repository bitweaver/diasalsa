<?php
$tables = array(

'diasalsa_actions' => "
	action_id I4 PRIMARY,
	key_id I4 UNIQUE,
	content_id I4 NOTNULL,
	expire_date I4,
	thankyou_data X
	CONSTRAINT '
		, CONSTRAINT `diasalsa_content_ref` FOREIGN KEY (`content_id`) REFERENCES `".BIT_DB_PREFIX."liberty_content` (`content_id`)'
",

'diasalsa_action_content_map' => "
	content_id I4 PRIMARY,
	action_c_key_id I4 NOTNULL,
	action_c_detail_key_id I4 NOTNULL
	CONSTRAINT '
		, CONSTRAINT `diasalsa_action_ref` FOREIGN KEY (`content_id`) REFERENCES `".BIT_DB_PREFIX."liberty_content` (`content_id`)'
",

'diasalsa_action_cnxn_map' => "
	to_content_id I4 PRIMARY,
	action_content_id I4 NOTNULL
	CONSTRAINT '
		, CONSTRAINT `dissalsa_action_cnxn_a_c_id_ref` FOREIGN KEY (`action_content_id`) REFERENCES `".BIT_DB_PREFIX."liberty_content` (`content_id`)
		, CONSTRAINT `diasalsa_action_cnxn_to_c_id_ref` FOREIGN KEY (`to_content_id`) REFERENCES `".BIT_DB_PREFIX."liberty_content` (`content_id`)'
",

'diasalsa_track' => "
	action_id I4 NOTNULL,
	email C(200) NOTNULL
	CONSTRAINT '
		, CONSTRAINT `diasalsa_track_ref` FOREIGN KEY (`action_id`) REFERENCES `".BIT_DB_PREFIX."diasalsa_actions` (`action_id`)'
",

);

global $gBitInstaller;

foreach( array_keys( $tables ) AS $tableName ) {
	$gBitInstaller->registerSchemaTable( DIASALSA_PKG_NAME, $tableName, $tables[$tableName] );
}

$gBitInstaller->registerPackageInfo( DIASALSA_PKG_NAME, array(
	'description' => "Proxy interface to DIA Salsa's Actions tool",
) );

// ### Sequences
$sequences = array (
	'diasalsa_action_id_seq'      => array( 'start' => 1 ),
);
$gBitInstaller->registerSchemaSequences( DIASALSA_PKG_NAME, $sequences );

// ### Default UserPermissions
$gBitInstaller->registerUserPermissions( DIASALSA_PKG_NAME, array(
	array('p_actions_create', 'Can create a action', 'registered', DIASALSA_PKG_NAME),
	array('p_actions_update', 'Can update actions', 'editors', DIASALSA_PKG_NAME),
	array('p_actions_view', 'Can read actions', 'basic', DIASALSA_PKG_NAME),
	array('p_actions_admin', 'Can admin actions', 'admin', DIASALSA_PKG_NAME),
) );

// ### Register content types
$gBitInstaller->registerContentObjects( DIASALSA_PKG_NAME, array( 
	'SalsaAction'=>DIASALSA_PKG_PATH.'SalsaAction.php',
));

// Package Requirements
$gBitInstaller->registerRequirements( USERS_PKG_NAME, array(
	'liberty'   => array( 'min' => '2.1.4' ),
));


