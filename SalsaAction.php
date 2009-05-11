<?php
/**
 * DIA Salsa Action database table description
 * https://hq-wfc2.wiredforchange.com/o/8001/p/salsa/web/wiki/public/?reference=API%20action
 **/

/**
 * Initialize
 */
require_once( LIBERTY_PKG_PATH.'LibertyMime.php' );

define( 'SALSAACTION_CONTENT_TYPE_GUID', 'bitaction' );

class SalsaAction extends LibertyMime {
	var $mActionId;
	var $mKeyId;
	var $mConnection;
	
	function SalsaAction( $pActionId=NULL, $pContentId=NULL ) {
		LibertyMime::LibertyMime();
		$this->registerContentType( SALSAACTION_CONTENT_TYPE_GUID, array(
			'content_type_guid' => SALSAACTION_CONTENT_TYPE_GUID,
			'content_description' => 'Action',
			'handler_class' => 'SalsaAction',
			'handler_package' => 'diasalsa',
			'handler_file' => 'SalsaAction.php',
			'maintainer_url' => 'http://www.tekimaki.com'
		) );
		$this->mActionId = (int)$pActionId;
		$this->mContentId = (int)$pContentId;
		$this->mContentTypeGuid = SALSAACTION_CONTENT_TYPE_GUID;

		// Permission setup
		$this->mViewContentPerm  = 'p_actions_view';
		$this->mCreateContentPerm  = 'p_actions_create';
		$this->mUpdateContentPerm  = 'p_actions_update';
		$this->mAdminContentPerm = 'p_actions_admin';

		// some basic data we need whether content is valid or not
		$this->getTargetDesc();
	}

	/* action handlers */
	function load(){
		if( $this->verifyId( $this->mActionId ) || $this->verifyId( $this->mContentId ) ) {
			global $gBitSystem, $gBitUser, $gLibertySystem;

			$bindVars = array(); $selectSql = ''; $joinSql = ''; $whereSql = '';
			$lookupColumn = $this->verifyId( $this->mActionId )? 'action_id' : 'content_id';
			$lookupId = $this->verifyId( $this->mActionId )? $this->mActionId : $this->mContentId;
			array_push( $bindVars, $lookupId );
			$this->getServicesSql( 'content_load_sql_function', $selectSql, $joinSql, $whereSql, $bindVars );

			$query = "
				SELECT sa.*, lc.*, 
					acm.`action_c_key_id` AS `action_content_key`, 
					acm.`action_c_detail_key_id` AS `action_content_detail_key`, 
					lcds.`data` AS `summary`, lch.`hits`, uu.`login`, uu.`real_name`,
					lfp.storage_path AS `image_attachment_path` 
					$selectSql
				FROM `".BIT_DB_PREFIX."diasalsa_actions` sa
					INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON (lc.`content_id` = sa.`content_id`)
					INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON( uu.`user_id` = lc.`user_id` )
					LEFT JOIN `".BIT_DB_PREFIX."diasalsa_action_content_map` acm ON (acm.`content_id` = sa.`content_id`)
					LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_content_data` lcds ON (lc.`content_id` = lcds.`content_id` AND lcds.`data_type`='summary')
					LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_content_hits` lch ON (lc.`content_id` = lch.`content_id`)
					LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_attachments` la ON( la.`content_id` = lc.`content_id` AND la.`is_primary` = 'y' )
					LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_files` lfp ON( lfp.`file_id` = la.`foreign_id` )
					$joinSql
				WHERE sa.`$lookupColumn`=? $whereSql ";

			if( $this->mInfo = $this->mDb->getRow( $query, $bindVars ) ) {
				$this->mActionId = $this->mInfo['action_id'];
				$this->mKeyId = $this->mInfo['key_id'];
				$this->mContentId = $this->mInfo['content_id'];
				LibertyMime::load();
				$this->mInfo['salsa_display_url'] = $this->getSalsaDisplayUrl();
				$this->mInfo['display_url'] = $this->getDisplayUrl();
				$this->mInfo['thumbnail_url'] = $this->getImageThumbnails( $this->mInfo );
			} else {
				$this->mActionId = NULL;
				$this->mKeyId = NULL;
				$this->mContentId = NULL;
			}

			// base array to hold shit - we want to know null stuff too, to make our html setup easier
			$this->mInfo['targets']['auto'] = array();
			foreach( $this->mTargetTypeDesc as $key=>$value){ 
				$this->mInfo['targets'][$key] = array();
			}
		}
		return( count( $this->mInfo ) );
	}

	function parseAllData(){
		$format_guid = !empty($this->mInfo['format_guid']) ? $this->mInfo['format_guid'] : 'tikiwiki';

		if( !empty( $this->mInfo['data'] ) ){
			$this->mInfo['parsed_data'] = $this->parseData( $this->mInfo['data'], $format_guid );
		/*
		}else{
			$this->mErrors['parsed_data'] = tra( 'Data param not set' );
		*/
		}

		if( !empty( $this->mInfo['thankyou_data'] ) ){
			$this->mInfo['parsed_thankyou_data'] = $this->parseData( $this->mInfo['thankyou_data'], $format_guid );
		/*
		}else{
			$this->mErrors['parsed_thankyou_data'] = tra( 'ThankYou Data param not set' );
		*/
		}

		return( count( $this->mInfo ) );
	}

	function preparePreview( $pParamHash ){
		$this->verify( $pParamHash );
		$this->preparePreviewContentLinks( $pParamHash );
		$this->preparePreviewTargets( $pParamHash );
		$this->mInfo = $pParamHash;
		$this->mInfo['data'] = !empty( $pParamHash['edit'] )?$pParamHash['edit']:NULL;
		$this->parseAllData();
	}

	function preparePreviewContentLinks( &$pParamHash ){
		if( !empty( $pParamHash['content_links'] ) ){
			$content_ids = implode( ",", $pParamHash['content_links'] );
			$sort_mode = 'lc.' . $this->mDb->convertSortmode( 'title_asc' );
			$query = "
				SELECT
					lc.*, lct.`content_description`
				FROM `".BIT_DB_PREFIX."liberty_content` lc
					LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_content_types` lct ON ( lct.`content_type_guid` = lc.`content_type_guid` )
				WHERE lc.`content_id` IN ( $content_ids )
				ORDER BY $sort_mode";
			$result = $this->mDb->query( $query );

			$ret = array();

			while ($res = $result->fetchRow()) {
				$ret[] = $res;
			}

			$pParamHash['content_links'] = $ret;
		}
	}

	function preparePreviewTargets( &$pParamHash ){
		// base array to hold shit - we want to know null stuff too, to make our html setup easier
		$pParamHash['targets']['auto'] = array();
		foreach( $this->mTargetTypeDesc as $key=>$value){ 
			$pParamHash['targets'][$key] = array();
		}

		if( !empty( $pParamHash['action_targets'] ) ){
			// sort the auto from the individuals
			foreach( $pParamHash['action_targets'] as $target_id ){
				if( !is_numeric( $target_id ) ){
					$pParamHash['targets']['auto'][$target_id] = array(	'action_target_key' => NULL, 
																		'target' => $target_id,
																		'target_desc' => $this->getTargetDesc( $target_id ),
																		'target_keys' => array(),
																		);
				}else{
					$keyIds[] = $target_id; 
				}
			}

			// group individual targets
			if( !empty( $keyIds ) ){
				$targetData = $this->getTargetsByIds( implode(",", $keyIds ) );
				foreach( $targetData as $target_id => $data ){
					if( empty( $pParamHash['targets'][ $data['district_type'] ][0] ) ){
						$pParamHash['targets'][ $data['district_type'] ][0] = array( 'action_target_key' => NULL, 
																			'target' => $data['district_type'],
																			'target_desc' => $this->getTargetDesc( $data['district_type'] ),
																			'target_keys' => array(),
																			);
					}
					$pParamHash['targets'][ $data['district_type'] ][0]['target_keys'][$target_id] = $data;
				}
			}
		}
	}


	function store( &$pParamHash ){
		global $gBitSystem;
		$this->mDb->StartTrans();
		// verify 
		if( $this->verify( $pParamHash ) && LibertyMime::store( $pParamHash ) ) {
			// store remotely
			/* note: we store remotely first because in new cases we need to get salsa's key identifiers */
			if( $this->storeSalsaAction( $pParamHash ) ){
				// then we store the content
				if( $this->verifyActionContent( $pParamHash ) && $this->storeSalsaActionContent( $pParamHash ) ){
					// and content detail
					if( $this->verifyActionContentDetail( $pParamHash ) && $this->storeSalsaActionContentDetail( $pParamHash ) ){
						// and content detail
						if( $this->verifyTargets( $pParamHash ) && $this->storeSalsaTargets( $pParamHash ) ){
							// sync locally
							// store the action object
							$table = BIT_DB_PREFIX."diasalsa_actions";
							// update existing
							if( $this->isValid() ) {
								$id = array( "content_id" => $this->mContentId );
								$result = $this->mDb->associateUpdate( $table, $pParamHash['action_store'], $id );
							// store new 
							} else {
								$pParamHash['action_store']['content_id'] = $pParamHash['content_id'];
								// key_id comes from diasalsa key param
								$pParamHash['action_store']['key_id'] = $pParamHash['key_id'];
								if( @$this->verifyId( $pParamHash['action_id'] ) ) {
									// if pParamHash['action_id'] is set, someone is requesting a particular action_id. Use with caution!
									$pParamHash['action_store']['action_id'] = $pParamHash['action_id'];
								} else {
									$pParamHash['action_store']['action_id'] = $this->mDb->GenID( 'diasalsa_action_id_seq' );
								}
								$this->mActionId = $pParamHash['action_store']['action_id'];
								//store the new action
								$result = $this->mDb->associateInsert( $table, $pParamHash['action_store'] );

								//if its new we need to store in salsa AGAIN because we need to store the redirect url to get the user back to bw
								$this->verify( $pParamHash );
								$this->storeSalsaAction( $pParamHash );

								// store the action content detail ref
								// we only store once when new since its just a one to one map
								// if ever want to support more than one content detail then this is one of
								// many things that would have to change/be enhanced
								// yes, fucking verify again
								if( $this->verifyActionContentMap( $pParamHash ) ){
									$table2 = BIT_DB_PREFIX."diasalsa_action_content_map";
									$result2 = $this->mDb->associateInsert( $table2, $pParamHash['action_content_map_store'] );
								}else{
									// something is fucked up so at least keep our database clean
									$this->mDb->RollbackTrans();
								}
							}

							// if we have content to link do it
							if( $this->verifyLinkContent( $pParamHash ) ){
								$this->linkContentMixed( $pParamHash );
							}
							// done
							$this->mDb->CompleteTrans();
						}else{
							// remote storage of targets fail rollback sql transaction
							$this->mDb->RollbackTrans();
						}
					}else{
						// remote storage of action content detail fail rollback sql transaction
						$this->mDb->RollbackTrans();
					}
				}else{
					// remote storage of action content fail rollback sql transaction
					$this->mDb->RollbackTrans();
				}
			}else{
				// remote storage of action fail rollback sql transaction
				$this->mDb->RollbackTrans();
			}
		}
		return ( count( $this->mErrors ) == 0 );
	}

	/**
	 * Check that the class has a valid blog loaded
	 */
	function isValid() {
		return( $this->verifyId( $this->mActionId ) && is_numeric( $this->mActionId ) && $this->mActionId > 0 );
	}

	function verify( &$pParamHash ){
		// prep hash for local storage
		// $pParamHash['action_store']
		global $gBitUser, $gBitSystem, $gLibertySystem;

		if( @$this->verifyId( $this->mInfo['content_id'] ) ) {
			$pParamHash['content_id'] = $this->mInfo['content_id'];
		}

		if( @$this->verifyId( $this->mInfo['action_id'] ) ) {
			$pParamHash['action_id'] = $this->mInfo['action_id'];
		}

		if( @$this->verifyId( $this->mInfo['key_id'] ) ) {
			$pParamHash['key_id'] = $this->mInfo['key_id'];
		}

		if( @$this->verifyId( $pParamHash['content_id'] ) ) {
			$pParamHash['action_store']['content_id'] = $pParamHash['content_id'];
		}

		if( $this->isValid() && empty( $pParamHash['key_id'] ) ){ 
			$this->mErrors['key_id'] = tra( 'Key Id is missing, please check the database and load process.' );
		}
		elseif( @$this->verifyId( $pParamHash['key_id'] ) ) {
			$pParamHash['action_store']['key_id'] = $pParamHash['key_id'];
			// for dia
			$pParamHash['key'] = $pParamHash['key_id'];
		}

		if( @$this->verifyId( $pParamHash['action_id'] ) ) {
			$pParamHash['action_store']['action_id'] = $pParamHash['action_id'];
		}
		
		if( !empty( $pParamHash['edit'] ) ) {
			$pParamHash['data'] = $pParamHash['edit'];
			// for dia
			$pParamHash['Description'] = $pParamHash['data'];
		}

		if( !empty( $pParamHash['title'] ) ) {
			// for dia
			$pParamHash['Title'] = $pParamHash['title'];
			$pParamHash['Reference_Name'] = $pParamHash['title'];
		}
		else{
			$this->mErrors['title'] = tra( 'You did not provide a title for your action.' );
		}
		
		// spot check of related data so that we dont bother storing an action just to have these parts fail
		if( empty( $pParamHash['recommended_subject'] ) ) {
			$this->mErrors['recommended_subject'] = tra( 'You did not provide a subject for your letter.' );
		}

		if( empty( $pParamHash['recommended_content'] ) ) {
			$this->mErrors['recommended_content'] = tra( 'You did not provide any text for your letter.' );
		}

		if( empty( $pParamHash['action_targets'] ) ){
			$this->mErrors['action_targets'] = tra( 'You must select targets for your letter. Please click the Targets tab and select at least one target for your letter.' );
		}
		// end spot check

		if( !empty( $pParamHash['thankyou_data'] ) ){
			$pParamHash['action_store']['thankyou_data'] = $pParamHash['thankyou_data'];
			// for dia
			// deprecated because losers dont suport html anymore $pParamHash['Thank_You_Text'] = $pParamHash['thankyou_data'];
		}	

		// expire date
		if( !empty( $pParamHash['expire'] ) && !empty( $pParamHash['expire_Month'] ) ) {
			$dateString = $pParamHash['expire_Year'].'-'.$pParamHash['expire_Month'].'-'.$pParamHash['expire_Day'].' '.$pParamHash['expire_Hour'].':'.$pParamHash['expire_Minute'];

			$offset = $gBitSystem->get_display_offset();
			$this->mDate = new BitDate($offset);

			$dateString = $this->mDate->gmmktime(
				$pParamHash['expire_Hour'],
				$pParamHash['expire_Minute'],
				isset($pParamHash['expire_Second']) ? $pParamHash['expire_Second'] : 0,
				$pParamHash['expire_Month'],
				$pParamHash['expire_Day'],
				$pParamHash['expire_Year']
			);

			$timestamp = $this->mDate->getUTCFromDisplayDate( $dateString );

			if( $timestamp !== -1 ) {
				$pParamHash['expire_date'] = $timestamp;
			}
		}
		$pParamHash['action_store']['expire_date'] = !empty( $pParamHash['expire_date'] )?$pParamHash['expire_date']:NULL;

		// target data
		// national
		if( !empty( $pParamHash['targets_ids'] ) ){
			if( !is_array( $_REQUEST['targets_ids'] )) {
				$pParamHash['person_legislator_IDS'] = $_REQUEST['targets_ids'];
			}else{
				$pParamHash['person_legislator_IDS'] = implode( ",", $pParamHash['targets_ids'] );
			}
		}else{
			// this deletes all from the salsa database
			$pParamHash['person_legislator_IDS'] = '';
		}

		// force values in diasalsa
		if( $gBitSystem->getConfig( 'diasalsa_organization_key' ) ){
			$pParamHash['organization_KEY'] = $gBitSystem->getConfig( 'diasalsa_organization_key' ); 
		}else{
			$this->mErrors['organization_key'] = tra( 'The Salsa organization key is not set, please report this error to an administrator' ); 
		}
		$pParamHash['object'] = 'action';
		$pParamHash['Style'] = 'Targeted';
		$pParamHash['Suppress_Automatic_Response_Email'] = TRUE;
		$pParamHash['Status'] = 'Active';
		$pParamHash['Allow_Emails'] = TRUE;
		$pParamHash['Allow_Faxes'] = FALSE;
		$pParamHash['Hide_Message_Type_Options'] = TRUE;
		$pParamHash['alternate_action_path'] = '/o/'.$gBitSystem->getConfig( 'diasalsa_organization_key' ).'/p/d/tekimaki/action/public/preaction.sjs';
		// this is the map4change template in the salsa account
		// $pParamHash['template_KEY'] = 3655;
		if( $this->isValid() ){
			// $pParamHash['redirect_path'] = BIT_ROOT_URI.substr( $this->getDisplayUrl(), 1 ).'&thankyou=y';
			// redirect path is to a special script at salsa - the url is relative
			$pParamHash['redirect_path'] = '/o/'.$gBitSystem->getConfig( 'diasalsa_organization_key' ).'/p/d/tekimaki/action/public/postaction.sjs';
		}
		// Max_Number_Of_Faxes
		
		// prep hash for storage at diasalsa
		$actionParams = array( 'organization_KEY', 'object', 'Style', 'key', 
								'Reference_Name', 'Title', 'Description', 'redirect_path', 'alternate_action_path', 
								'Allow_Emails', 'Allow_Faxes', 'Hide_Message_Type_Options', 
								'Thank_You_Text',
								);

		foreach( $pParamHash as $key => $value ){
			if( in_array( $key, $actionParams ) ){
				$pParamHash['salsa_action_store'][$key] = $value;
			}
		}

		// if we have an error we get them all by checking parent classes for additional errors
		if( count( $this->mErrors ) > 0 ){
			parent::verify( $pParamHash );
		}

		return( count( $this->mErrors )== 0 );
	}


	function verifyActionContent( &$pParamHash ){
		global $gBitSystem;

		// the action content must be associated with an action
		if( empty( $pParamHash['key_id'] ) ){
			$this->mErrors['action_content'] = tra( 'Error trying to store action content, unknown key for related action.' );
		}else{
			$pParamHash['salsa_action_content_store']['action_KEY'] = $pParamHash['key_id'];
		}

		// if its an update we supply the key
		if( $this->isValid() && !empty( $this->mInfo['action_content_key'] ) ){ 
			$pParamHash['salsa_action_content_store']['key'] = $this->mInfo['action_content_key'];
		}

		if( $gBitSystem->getConfig( 'diasalsa_organization_key' ) ){
			$pParamHash['salsa_action_content_store']['organization_KEY'] = $gBitSystem->getConfig( 'diasalsa_organization_key' ); 
		}else{
			$this->mErrors['organization_key'] = tra( 'The Salsa organization key is not set, please report this error to an administrator' ); 
		}

		// some jibberish shit DIA adds, so we mimic it
		$pParamHash['salsa_action_content_store']['Name'] = 'Main Content Set';

		$pParamHash['salsa_action_content_store']['object'] = 'action_content';

		return( count( $this->mErrors )== 0 );
	}


	function verifyActionContentDetail( &$pParamHash ){
		global $gBitSystem;

		// the action content detail must be associated with an action content
		if( empty( $pParamHash['action_content_key'] ) ){
			$this->mErrors['action_content'] = tra( 'Error trying to store action content detail, unknown key for related action content.' );
		}else{
			$pParamHash['salsa_action_content_detail_store']['action_content_KEY'] = $pParamHash['action_content_key'];
		}

		// if its an update we supply the key
		if( $this->isValid() && !empty( $this->mInfo['action_content_detail_key'] ) ){ 
			$pParamHash['salsa_action_content_detail_store']['key'] = $this->mInfo['action_content_detail_key'];
		}

		if( !empty( $pParamHash['subject_editable'] ) ){
			// deprecated $pParamHash['action_store']['subject_editable'] = 1;
			// for dia
			$pParamHash['salsa_action_content_detail_store']['Fixed_Subject'] = 0;
		}	
		else{
			// deprecated $pParamHash['action_store']['subject_editable'] = 0;
			// for dia
			$pParamHash['salsa_action_content_detail_store']['Fixed_Subject'] = 1;
		}

		if( !empty( $pParamHash['content_editable'] ) ){
			// deprecated $pParamHash['action_store']['content_editable'] = 1;
			// for dia
			$pParamHash['salsa_action_content_detail_store']['Fixed_Content'] = 0;
		}
		else{
			// deprecated $pParamHash['action_store']['content_editable'] = 0;
			// for dia
			$pParamHash['salsa_action_content_detail_store']['Fixed_Content'] = 1;
		}

		if( !empty( $pParamHash['recommended_subject'] ) ) {
			// deprecated $pParamHash['action_store']['recommended_subject'] = $pParamHash['recommended_subject'];
			// for dia
			$pParamHash['salsa_action_content_detail_store']['Recommended_Subject'] = $pParamHash['recommended_subject'];
		}
		else{
			$this->mErrors['recommended_subject'] = tra( 'You did not provide a subject for your letter.' );
		}

		if( !empty( $pParamHash['recommended_content'] ) ) {
			// deprecated $pParamHash['action_store']['recommended_content'] = $pParamHash['recommended_content'];
			// for dia
			$pParamHash['salsa_action_content_detail_store']['Recommended_Content'] = $pParamHash['recommended_content'];
		}
		else{
			$this->mErrors['recommended_content'] = tra( 'You did not provide any text for your letter.' );
		}

		if( $gBitSystem->getConfig( 'diasalsa_organization_key' ) ){
			$pParamHash['salsa_action_content_detail_store']['organization_KEY'] = $gBitSystem->getConfig( 'diasalsa_organization_key' ); 
		}else{
			$this->mErrors['organization_key'] = tra( 'The Salsa organization key is not set, please report this error to an administrator' ); 
		}

		$pParamHash['salsa_action_content_detail_store']['object'] = 'action_content_detail';

		return( count( $this->mErrors )== 0 );
	}


	function verifyActionContentMap( &$pParamHash ){
		if( !empty( $pParamHash['content_id'] ) ){ 
			$pParamHash['action_content_map_store']['content_id'] = $pParamHash['content_id'];
		}else{
			$this->mErrors['disalsa_map_content_id'] = tra( 'Error trying to map invalid content record.' ); 
		}
		if( !empty( $pParamHash['action_content_key'] ) ){ 
			$pParamHash['action_content_map_store']['action_c_key_id'] = $pParamHash['action_content_key'];
		}else{
			$this->mErrors['disalsa_map_action_c_id'] = tra( 'Error trying to map action content, no action content key.' ); 
		}
		if( !empty( $pParamHash['action_content_detail_key'] ) ){ 
			$pParamHash['action_content_map_store']['action_c_detail_key_id'] = $pParamHash['action_content_detail_key'];
		}else{
			$this->mErrors['disalsa_map_action_c_id'] = tra( 'Error trying to map action content, no action content detail key.' ); 
		}
		return( count( $this->mErrors )== 0 );
	}


	// DIA's target data model is frustratingly messy - we try to deal with their mishigas here
	function verifyTargets( &$pParamHash ){
		global $gBitSystem;
		// targets must be associated with an action
		if( empty( $pParamHash['key_id'] ) ){
			$this->mErrors['action_targets'] = tra( 'Error trying to store targets, unknown key for related action.' );
		}

		// if we've target hash is already set clear it out and load up the current stored targets so we know what already exists
		if( !empty( $this->mInfo['targets'] ) ){
			$this->mInfo['targets'] = NULL;
		}
		$this->loadSalsaActionTargets();

		// prep data to store
		if( !empty( $pParamHash['action_targets'] ) ){
			$keyIds = array();

			// auto targets
			foreach( $pParamHash['action_targets'] as $target_id ){
				if( !is_numeric( $target_id ) ){
					// check if its already stored, if not add it, otherwise we can safely skip it
					if( empty( $this->mInfo['targets']['auto'] ) || empty( $this->mInfo['targets']['auto'][$target_id] ) ){
						// put it in our storage hash - this is a new target record so key is null
						$pParamHash['salsa_action_target_store']['auto'][] = array( 'key' => NULL,
																			 'target' => $target_id,
																			 'organization_KEY' => $gBitSystem->getConfig( 'diasalsa_organization_key' ),
																			 'action_KEY' => $pParamHash['key_id'],
																			 'action_content_KEY' => $pParamHash['action_content_key'],
																			 'object' => 'action_target',
																			 'method' => 'Email/Webform',
																			);
					}
				}else{
					$keyIds[] = $target_id; 
				}
			}

			// individual target groupings
			// targetData is targets we need to make sure are stored
			$targetData = $this->getTargetsByIds( implode(",", $keyIds ) );
			$actionTargetKeys = array();
			foreach( $targetData as $target_id => $data ){
				$key = NULL;
				// this is painful but whatever - we loop over existing target groups to see if the target is already assigned
				foreach( $this->mInfo['targets'] as $district_type => $target_objects ){
					if( $district_type == $data['district_type'] ){
						if( !empty( $target_objects ) ){
							// target objects is a list of target groups organized by district type, we only want the first if it exists
							// dia can store multiple groups for the same district type which is really stupid, we'll ignore all but the first
							foreach( $target_objects as $target_key => $target_group ){
								// we want the key - we keep a record of it so we know all we are storing - see the expunge verification process below
								$actionTargetKeys[] = $key = $target_key;
								// break so we only check the first
								break;
							}
						}

						// we need to push all target_ids into one hash - so we may have created this before
						if( empty( $pParamHash['salsa_action_target_store'][$district_type] ) ){
							// the extra array nesting is to mirror the need for auto (above) to be an array of records
							$pParamHash['salsa_action_target_store'][$district_type][0] = array( 'key' => $key,
																				 'target' => $district_type,
																				 'organization_KEY' => $gBitSystem->getConfig( 'diasalsa_organization_key' ),
																				 'action_KEY' => $pParamHash['key_id'],
																				 'action_content_KEY' => $pParamHash['action_content_key'],
																				 'object' => 'action_target',
																				 'method' => 'Email/Webform',
																				);
						}

						// we add the key to the hash to store
						$pParamHash['salsa_action_target_store'][$district_type][0]['target_KEYS'][] = $target_id;
					}
				}
			}

			// targets to expunge
			// auto
			foreach( $this->mInfo['targets']['auto'] as $district_type => $target ){
				if( !in_array( $district_type, $pParamHash['action_targets'] ) ){ 
					$pParamHash['salsa_action_target_expunge'][] = array( 'key' => $target['action_target_key'],  
																		 'object' => 'action_target',
																		);
				}
			}
			// individual groups
			foreach( $this->mInfo['targets'] as $district_type => $target_objects ){
				if( $district_type != 'auto' && !empty( $target_objects ) ){
					foreach( $target_objects as $target_key => $target_group ){
						// if we're not storing/updating it above we're going to delete it
						if( !in_array( $target_key, $actionTargetKeys ) ){
							$pParamHash['salsa_action_target_expunge'][] = array( 'key' => $target_key,  
																				 'object' => 'action_target',
																				);
						}
					}
				}
			}
		}else{
			$this->mErrors['action_targets'] = tra( 'You must select targets for your letter. Please click the Targets tab and select at least one target for your letter.' );
		}

		// vd($pParamHash['salsa_action_target_store']);
		// vd( $pParamHash['salsa_action_target_expunge'] );

		return( count( $this->mErrors )== 0 );
	}

	/**
	 * Remove action 
	 */
	function expunge() {
		$ret = FALSE;
		if( $this->isValid() ) {
			$this->mDb->StartTrans();

			// remove all references in diasalsa_track
			$query_track = "DELETE FROM `".BIT_DB_PREFIX."diasalsa_track` WHERE `action_id` = ?";
			$result = $this->mDb->query( $query_track, array( $this->mActionId ) );

			// remove the action content detial record
			$query_detail = "DELETE FROM `".BIT_DB_PREFIX."diasalsa_action_content_map` WHERE `content_id` = ?";
			$result = $this->mDb->query( $query_detail, array( $this->mContentId ) );

			// remove the action content links
			$query_link = "DELETE FROM `".BIT_DB_PREFIX."diasalsa_action_cnxn_map` WHERE `action_content_id` = ?";
			$result = $this->mDb->query( $query_link, array( $this->mContentId ) );

			// remove the action record
			$query = "DELETE FROM `".BIT_DB_PREFIX."diasalsa_actions` WHERE `content_id` = ?";
			$result = $this->mDb->query( $query, array( $this->mContentId ) );

			if( LibertyMime::expunge() ) {
				if( $this->expungeSalsaAction() ){
					$ret = TRUE;
					$this->mDb->CompleteTrans();
				}
			} else {
				$this->mDb->RollbackTrans();
			}
		}
		return $ret;
	}



	/**
	 * Actions can not have comments 
	 */
	function isCommentable(){
		global $gBitSystem;	
		return FALSE;
	}

	/**
	* Get the URL for any given action image
	* @param $pParamHash pass in full set of data returned from action query
	* @return url to image
	* @access public
	**/
	function getImageThumbnails( $pParamHash ) {
		global $gBitSystem, $gThumbSizes;
		$ret = NULL;
		if( !empty( $pParamHash['image_attachment_path'] )) {
			$thumbHash = array(
				'mime_image'   => FALSE,
				'storage_path' => $pParamHash['image_attachment_path']
			);
			$ret = liberty_fetch_thumbnails( $thumbHash );
			$ret['original'] = "/".$pParamHash['image_attachment_path'];
		}
		return $ret;
	}



	/* DIASalsa REST handlers */
	function loadSalsaAction(){
		if( $this->isValid() ){
			$url = $this->getServiceUrl( 'load' ); 
			$reqHash = array( 'object' => 'action',
								'key' => $this->mActionId,
							);
			$rslt = $this->curlExec( $url, $reqHash );
			$xml = $this->string2XML( $rslt );
			$item = $xml->getElementsByTagName( 'item' )->item(0);
			$data = dom2array_full( $item );
		}
		else{
			$this->mErrors['diasalsa_load'] = tra('Error attemped to load data from DIA for an invalid Action.');
		}
	}

	function storeSalsaAction( &$pParamHash ){
		if( !empty( $pParamHash['salsa_action_store'] ) ){
			$pParamHash['salsa_action_store']['xml'] = 'y';
			$url = $this->getServiceUrl( 'store' ); 
			$rslt = $this->curlExec( $url, $pParamHash['salsa_action_store'] );
			$xml = $this->string2XML( $rslt );
			if( $this->validateXMLRequest( $xml ) ){
				if( $success = $xml->getElementsByTagName( 'success' )->item(0) ){
					$pParamHash['key_id'] = $success->getAttribute('key');
				}
				$this->storeSalsaActionRequiredFields( $pParamHash );
			}
		}
		else{
			$this->mErrors['diasalsa_store'] = tra('Salsa action store hash not set in storeSalsaAction');
		}
		return ( count( $this->mErrors ) == 0 );
	}

	/** 
	 * this is separate because http_build_query will not build a url from a complex array in a way
	 * that is frieldly to java which is what DIA is built on
	 **/  
	function storeSalsaActionRequiredFields( &$pParamHash ){
		if( !empty( $pParamHash['salsa_action_store'] ) ){
			$url = $this->getServiceUrl( 'store' ); 
			$url .= "?key=".$pParamHash['salsa_action_store']['key'];
			$url .= "&object=action";
			$url .= "&xml=y";
			$request = array( "First_Name", "Last_Name", "Street", "Street_2", "City", "State", "Zip", "Email" );
			$optional = array( "Street_2" );
			foreach( $request as $value ){
				if( !in_array( $value, $optional ) ){ 
					$url .= "&Required=".$value;
				}
				$url .= "&Request=".$value;
			}
			if( empty( $this->mConnection ) ){
				$this->initConnection();
			}
			curl_setopt($this->mConnection, CURLOPT_URL, $url );
			return curl_exec( $this->mConnection );
		}
	}

	// Expunge removes an Action its Action Content and Action Content Detail records
	function expungeSalsaAction(){
		$expungeHash = array();
		if( $this->verifySalsaActionExpunge( $expungeHash ) ){
			$url = $this->getServiceUrl( 'expunge' ); 
			// @TODO if we care: targets
			// action content detail first
			$rslt = $this->curlExec( $url, $expungeHash['action_content_detail_expunge'] );
			$xml = $this->string2XML( $rslt );
			$this->validateXMLRequest( $xml, 'action_content_detail_expunge' );
			// action content second
			$rslt = $this->curlExec( $url, $expungeHash['action_content_expunge'] );
			$xml = $this->string2XML( $rslt );
			$this->validateXMLRequest( $xml, 'action_content_expunge' );
			// action last
			$rslt = $this->curlExec( $url, $expungeHash['action_expunge'] );
			$xml = $this->string2XML( $rslt );
			$this->validateXMLRequest( $xml, 'action_expunge' );
		}
		return ( count( $this->mErrors ) == 0 );
	}

	// Action Content - Salsa's way of mapping email letter parts to an action 
	function storeSalsaActionContent( &$pParamHash ){
		if( !empty( $pParamHash['salsa_action_content_store'] ) ){
			$pParamHash['salsa_action_content_store']['xml'] = 'y';
			$url = $this->getServiceUrl( 'store' ); 
			$rslt = $this->curlExec( $url, $pParamHash['salsa_action_content_store'] );
			$xml = $this->string2XML( $rslt );
			if( $this->validateXMLRequest( $xml ) ){
				if( $success = $xml->getElementsByTagName( 'success' )->item(0) ){
					$pParamHash['action_content_key'] = $success->getAttribute('key');
				}
			}
		}
		else{
			$this->mErrors['diasalsa_store'] = 'Salsa action content store hash not set in storeSalsaActionContent';
		}
		return ( count( $this->mErrors ) == 0 );
	}


	function loadSalsaActionContentDetail(){
		if( $this->isValid() && !empty( $this->mInfo['action_content_detail_key'] ) ){
			$url = $this->getServiceUrl( 'load' ); 
			$reqHash = array( 'object' => 'action_content_detail',
								'key' => $this->mInfo['action_content_detail_key'],
							);
			$rslt = $this->curlExec( $url, $reqHash );
			$xml = $this->string2XML( $rslt );
			if( $this->validateXMLRequest( $xml ) ){
				$item = $xml->getElementsByTagName( 'item' )->item(0);
				$data = dom2array_full( $item );
				$this->mInfo['recommended_subject'] = !empty($data['Recommended_Subject'])?$data['Recommended_Subject']['#text']:"";
				$this->mInfo['recommended_content'] = !empty($data['Recommended_Content'])?$data['Recommended_Content']['#text']:"";
				// flip these since affirming checkboxes are easier to understand then negating checkboxes
				$this->mInfo['subject_editable'] = ( !empty($data['Fixed_Subject']) && $data['Fixed_Subject']['#text'] == 'true' )?false:true;
				$this->mInfo['content_editable'] = ( !empty($data['Fixed_Content']) && $data['Fixed_Content']['#text'] == 'true' )?false:true;
			}
		}
		else{
			$this->mErrors['diasalsa_load'] = tra('Error attemped to load data from DIA for an invalid Action.');
		}
	}


	function loadSalsaActionTargets(){
		// base array to hold shit - we want to know null stuff too, to make our html setup easier
		$this->mInfo['targets']['auto'] = array();
		foreach( $this->mTargetTypeDesc as $key=>$value){ 
			$this->mInfo['targets'][$key] = array();
		}

		if( $this->isValid() && !empty( $this->mKeyId ) ){
			$url = $this->getServiceUrl( 'list' ); 
			$reqHash = array( 'object' => 'action_target',
								'condition' => 'action_KEY='.$this->mKeyId,
							);
			$rslt = $this->curlExec( $url, $reqHash );
			//vd( $rslt );
			$xml = $this->string2XML( $rslt );
			$targets = array();
			if( $this->validateXMLRequest( $xml ) ){
				if( $items = $xml->getElementsByTagName( 'item' ) ){
					foreach( $items as $item ){
						$key = $this->getFirstValueByTagName( $item, 'action_target_KEY');
						$target = $this->getFirstValueByTagName( $item, 'target' );
						$target_keys = array();

						if( !empty( $item->getElementsByTagName( 'target_KEYS' )->item(0)->nodeValue ) ){
							$target_keys = $this->getTargetsByIds( $this->getFirstValueByTagName( $item, 'target_KEYS') );
						}

						$targets[$key] = array( 
										'action_target_key' => $key, 
										'target' => $target, 
										'target_desc' => $this->getTargetDesc( $target ), 
										'target_keys' => $target_keys,
									); 
					}
				}
			}

			$this->sortTargets( $targets );
		}
	}

	//DIA makes a mess of organizing targets in an easy to understand way, so we sort their shit
	function sortTargets( &$pTargets ){ 
		foreach( $pTargets as $key=>$target ){
			if( empty( $target['target_keys'] ) ){
				// no specific person within a target type (e.g. all senate, all house etc)
				$this->mInfo['targets']['auto'][$target['target']] = $target;
			}else{
				// specific targets, group by their legislative body
				$this->mInfo['targets'][$target['target']][$key] = $target;
			}
		}
		//	vd( $this->mInfo['targets'] );
	}


	function getTargetsByIds( $pKeyIds ){
		$ret = array();
		$url = $this->getServiceUrl( 'get_targets_by_ids' ); 
		$reqHash = array( 
					'url'=>'legislator.jsp?method=getLegislatorsFromIDs&include=display_name,district_code&person_legislator_ids='.$pKeyIds,
				);
		$rslt = $this->curlExec( $url, $reqHash );
		$xml = $this->string2XML( $rslt );
		if( $this->validateXMLRequest( $xml ) ){
			if( $items = $xml->getElementsByTagName( 'legislator' ) ){
				foreach( $items as $item ){
					$target_key = $this->getFirstValueByTagName( $item, 'person_legislator_KEY' );
					$ret[ $target_key ] = array( 
							'display_name' => $this->getFirstValueByTagName( $item, 'display_name' ),	
							'district_code' => $this->getFirstValueByTagName( $item, 'district_code' ),	
							'district_type' => $this->getFirstValueByTagName( $item, 'district_type' ),
							'person_legislator_key' => $target_key, 
						);
				}
			}
		}
		return $ret;
	}


	function getTargetDesc( $pTargetCode = NULL ){
		$ret = NULL;
		if( empty( $this->mTargetTypeDesc ) ){
			$this->mTargetTypeDesc['FE'] = tra("President");
			$this->mTargetTypeDesc['FS'] = tra("US Senate");
			$this->mTargetTypeDesc['FH'] = tra("US House");
			$this->mTargetTypeDesc['SE'] = tra("Governors");
			$this->mTargetTypeDesc['SS'] = tra("State Senate");
			$this->mTargetTypeDesc['SH'] = tra("State House");
		}
		if( !empty( $pTargetCode ) ){
			$ret = $this->mTargetTypeDesc[ $pTargetCode ];
		}
		return $ret;
	}


	// Convenience
	function getFirstValueByTagName( &$pXMLNode, $pTag ){
		return $pXMLNode->getElementsByTagName( $pTag )->item(0)->nodeValue;
	}


	// Action Content Detail - such as email letters are stored separately
	function storeSalsaActionContentDetail( &$pParamHash ){
		if( !empty( $pParamHash['salsa_action_content_detail_store'] ) ){
			$pParamHash['salsa_action_content_detail_store']['xml'] = 'y';
			$url = $this->getServiceUrl( 'store' ); 
			$rslt = $this->curlExec( $url, $pParamHash['salsa_action_content_detail_store'] );
			$xml = $this->string2XML( $rslt );
			if( $this->validateXMLRequest( $xml ) ){
				if( $success = $xml->getElementsByTagName( 'success' )->item(0) ){
					$pParamHash['action_content_detail_key'] = $success->getAttribute('key');
				}
			}
		}
		else{
			$this->mErrors['diasalsa_store'] = 'Salsa action content detail store hash not set in storeSalsaActionContentDetail';
		}
		return ( count( $this->mErrors ) == 0 );
	}


	// store targets
	function storeSalsaTargets( &$pParamHash ){
		if( !empty( $pParamHash['salsa_action_target_store'] ) ){
			// double nested array
			foreach( $pParamHash['salsa_action_target_store'] as $district_type ){
				foreach( $district_type as $target ){
					if( !empty( $target['target_KEYS'] ) ){
						// tidy this here into a comma delim string to make life easy
						$target['target_KEYS'] = implode( ",", $target['target_KEYS'] );
					}
					$url = $this->getServiceUrl( 'store' ); 
					$rslt = $this->curlExec( $url, $target );
					// salsa sucks it and gives us no validation code whether 
					// things were stored ok or not 
					// so we just assume it all will work out, like Obama and his magic unicorn.
				}
			}
			// we may need to remove some records as part of 'store' so we do so with another hash
			if( !empty( $pParamHash['salsa_action_target_expunge'] ) ){
				foreach( $pParamHash['salsa_action_target_expunge'] as $target ){
					$url = $this->getServiceUrl( 'expunge' ); 
					$rslt = $this->curlExec( $url, $target );
				}
			}
		}
		return ( count( $this->mErrors ) == 0 );
	}


	// Get the key for the action's default related action content detail
	function getSalsaActionContentDetailKey( $pActionKeyId ){
		$action_content_key = NULL;
		$key = NULL;
		if( @$this->verifyId( $pActionKeyId ) ) {
			// serious faggotry here, salsa has no way to request an action content detail via is action key, 
			// we first have to get the associatead 'action_content' object first

			// request action_content
			$listHash1['object'] = 'action_content';
			$listHash1['condition'] = 'action_KEY='.$pActionKeyId;
			$url1 = $this->getServiceUrl( 'list' ); 
			$rslt1 = $this->curlExec( $url1, $listHash1 );
			$xml1 = $this->string2XML( $rslt1 );
			if( $this->validateXMLRequest( $xml1 ) ){
				if( $items1 = $xml1->getElementsByTagName( 'item' ) ){
					foreach( $items1 as $item ){
						if( $item->getElementsByTagName('Name')->item(0)->nodeValue == 'Main Content Set' ){
							$action_content_key = $item->getElementsByTagName('action_content_KEY')->item(0)->nodeValue;
						}
					}
				}
			}

			// request action_content_detail
			if( !empty( $action_content_key ) ){
				$listHash2['object'] = 'action_content_detail';
				$listHash2['condition'] = 'action_content_KEY='.$action_content_key;
				$url2 = $this->getServiceUrl( 'list' ); 
				$rslt2 = $this->curlExec( $url2, $listHash2 );
				$xml2 = $this->string2XML( $rslt2 );
				if( $this->validateXMLRequest( $xml2 ) ){
					// in this case we'll take the first one since we have no way of distinguishing
					if( $item = $xml2->getElementsByTagName( 'item' )->item(0) ){
						// whew, we got it, that sucked
						$key = $item->getAttribute('action_content_detail_KEY');
					}
				}
			}	
		}
		return $key;
	}


	function verifySalsaActionExpunge( &$pParamHash ){
		global $gBitSystem;
		if( $this->isValid() ){
			// action
			if( @$this->verifyId( $this->mKeyId ) ) {
				$pParamHash['action_expunge']['key'] = $this->mKeyId;
			}else{
				$this->mErrors['key'] = tra( 'Action Key id not set' );
			}

			$pParamHash['action_expunge']['object'] = 'action';

			// action content 
			if( !empty( $this->mInfo['action_content_key'] ) ){
				$pParamHash['action_content_expunge']['key'] = $this->mInfo['action_content_key'];
			}else{
				$this->mErrors['action_content_key'] = tra( 'Action Content Key id not set' );
			}

			$pParamHash['action_content_expunge']['object'] = 'action_content';

			// action content detail
			if( !empty( $this->mInfo['action_content_detail_key'] ) ){
				$pParamHash['action_content_detail_expunge']['key'] = $this->mInfo['action_content_detail_key'];
			}else{
				$this->mErrors['action_content_key'] = tra( 'Action Content Key id not set' );
			}

			$pParamHash['action_content_detail_expunge']['object'] = 'action_content_detail';

			// universal
			if( $gBitSystem->getConfig( 'diasalsa_organization_key' ) ){
				$pParamHash['action_expunge']['organization_KEY'] = $pParamHash['action_content_expunge']['organization_KEY'] = $pParamHash['action_content_detail_expunge']['organization_KEY'] = $gBitSystem->getConfig( 'diasalsa_organization_key' ); 
			}
			else{
				$this->mErrors['organization_key'] = tra( 'The Salsa organization key is not set, please report this error to an administrator' ); 
			}

			$pParamHash['action_expunge']['xml'] = 'y';
			$pParamHash['action_content_expunge']['xml'] = 'y';
			$pParamHash['action_content_detail_expunge']['xml'] = 'y';

		}
		else{
			$this->mErrors['diasalsa_expunge'] = 'Expunge Salsa Action called on invalid action.';
		}

		return ( count( $this->mErrors ) == 0 );
	}


	/* letter handlers */
	function sendLetter( &$pParamHash ){
		if( $this->verifyLetter( $pParamHash ) ){
			$url = $this->getServiceUrl( 'send_letter' ); 
			$rslt = $this->curlExec( $url, $pParamHash['salsa_send_letter'] );
			$xml = $this->string2XML( $rslt );
			if( $this->validateXMLRequest( $xml ) ){
				$this->expungeSalsaUser( $userHash );
			}
		}

		return ( count( $this->mErrors ) == 0 );
	}


	/* register that a letter has been sent */
	function registerLetterSent( &$pParamHash ){
		$ret = FALSE;
		if( $this->isValid() && !empty( $pParamHash['email'] ) && !$this->verifyLetterSent( $pParamHash['email'] ) ){
			$query = "INSERT INTO `".BIT_DB_PREFIX."diasalsa_track` ( `action_id`, `email` ) VALUES (?,?)";
			$result = $this->mDb->query( $query, array( $this->mActionId, $pParamHash['email'] ) );
			$ret = TRUE;
		}

		$this->expungeSalsaUser( $userHash );

		return $ret;
	}


	/* verify if a user has already sent the letter */
	function verifyLetterSent( $pEmail=NULL ){
		global $gBitUser;

		if( $this->isValid() ){
			if( !empty( $pEmail ) ){
				$email = $pEmail;
			}else{
				$email = $gBitUser->getField('email');
			}
			return $this->mDb->getOne( "SELECT `email` FROM `".BIT_DB_PREFIX."diasalsa_track` WHERE `action_id`=? AND `email`=?", array( $this->mActionId, $email ) );
		}
		return FALSE;
	}


	function verifyLetter( &$pParamHash ){
		global $gBitUser;
		// we have an email address
		if( !empty( $pParamHash['email' ] ) ){
		// is it a registered user
			// does it match the gBitUser email
			// user must be logged in to send using the email address of a registered user
			if( !$gBitUser->getField('email') != $_REQUEST['email'] ){
				// give them the login tpl	
			}
		}else{
			$this->mErrors['email'] = tra( 'You must provide an email address to send a letter' );
		}

		// $pParamHash['salsa_send_letter'][''] = '';

		return ( count( $this->mErrors ) == 0 );
	}


	/* target handlers */
	function getLegislator( &$pParamHash ){
		$url = $this->getServiceUrl( 'get_legislator' ); 
		return $this->curlExec( $url, $pParamHash );
	}


	/* user handlers */
	function expungeSalsaUser( &$pParamHash ){
		$expungeHash['object'] = 'supporter';
		$expungeHash['xml'] = 'y';
		// $expungeHash[''];
		$url = $this->getServiceUrl( 'expunge' ); 
		$rslt = $this->curlExec( $url, $expungeHash );
		$xml = $this->string2XML( $rslt );
		// if expunge of remote user fails we log the error instead of reporting it back to the user
		if( !$this->validateXMLRequest( $xml ) ){
			bit_log_error( $this->mErrors['diasalsa_request'] );
		}
	}


	/* other */
	function loginAdmin(){
		global $gBitSystem, $gBitUser;

		if( $gBitSystem->getConfig( 'diasalsa_admin_email' ) && $gBitSystem->getConfig( 'diasalsa_admin_password' ) ){
			$email = $gBitSystem->getConfig( 'diasalsa_admin_email' ); # Action Manager email goes here
			$password = $gBitSystem->getConfig( 'diasalsa_admin_password' ); # Action Manager password goes here
		}else{
			if( $gBitUser->isAdmin() ){
				$error = tra("Salsa Admin account settings not set, please visit the <a href='".KERNEL_PKG_URL."admin/index.php?page=".DIASALSA_PKG_NAME."'>Salsa administration</a> area and insert the necessary settings to connect to Salsa.");
			}else{
				$error = tra("The Action package is not properly configured, please report this incident to an administrator, error: connenction cridentials not set.");
			}
			$gBitSystem->fatalError( tra( $error ) );
		}
		
		//method #1 for building post objects to send to Salsa
		$userHash["email"] = $email;
		$userHash["password"] = $password;

		$url = $this->getServiceUrl( 'authenticate' ); 
		$auth = $this->curlExec( $url, $userHash );

		$xml = $this->string2XML( $auth );

		$this->verifyXMLRequest( $xml );
	}

	function initConnection(){
		if( empty( $this->mConnection ) ){
			//Initialize CURL connection
			$ch = curl_init();

			//Set basic connection parameters:
			//      See http://us.php.net/curl_setopt for more information on these settings
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 100);

			//Set Parameters to maintain cookies across sessions
			curl_setopt($ch, CURLOPT_COOKIESESSION, TRUE);
			curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies_file');
			curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies_file');
			
			$this->mConnection = $ch;
		}
	}

	function curlExec( &$pUrl, &$pParamHash ){
		global $gBitSystem;

		if( empty( $this->mConnection ) ){
			$this->initConnection();
		}

		curl_setopt($this->mConnection, CURLOPT_URL, $pUrl );
		curl_setopt($this->mConnection, CURLOPT_POSTFIELDS, http_build_query($pParamHash, '', '&'));
		// vd( $pUrl.http_build_query($pParamHash, '', '&'));

		return curl_exec( $this->mConnection );
	}

	function string2XML( &$pString ){
		$xml = new DOMDocument();
		$xml->loadXML( $pString );
		return $xml;
	}


	function verifyXMLRequest( &$pXML ){
		global $gBitSystem;

		$errors = $pXML->getElementsByTagName( 'error' );
		if( $errors->length > 0 ){
			// tidy up before we fatal
			$this->closeConnection();
			// fatal
			$gBitSystem->fatalError( 'DIA Salsa Error: '.$errors->item(0)->nodeValue );
		}
	}

	function validateXMLRequest( &$pXML, $pRequestType=NULL ){
		global $gBitSystem;

		$errors = $pXML->getElementsByTagName( 'error' );
		if( $errors->length > 0 ){
			$error_type = !empty( $pRequestType )?$pRequestType:'diasalsa_request';
			$this->mErrors[$error_type] = 'DIA Salsa Request Error: '.$errors->item(0)->nodeValue;
			return FALSE;
		}
		return TRUE;
	}

	function closeConnection(){
		//Close the connection
		if( $this->mConnection ){
			curl_close( $this->mConnection );
		}
		$this->mConnection = NULL;
	}

	function getServiceUrl( $pServiceType = NULL ){
		global $gBitSystem;

		$baseUrl = $gBitSystem->getConfig( 'salsa_account_url', 'https://hq-org2.democracyinaction.org' );
		// $baseUrl = 'https://sandbox.salsalabs.com';
		$url = '';

		switch( $pServiceType ){
			case 'authenticate':
				$url = "/api/authenticate.sjs";
				break;
			case 'load':
				$url = "/api/getObject.sjs";
				break;
			case 'store':
				$url = "/save";
				break;
			case 'expunge':
				$url = "/delete";
				break;
			case 'get_legislator':
				$baseUrl = '';
				$url = 'http://warehouse.democracyinaction.org/o/0/p/salsa/warehouse/public/lookupLegislator.sjs';
				break;
			case 'get_targets_by_ids':
				$baseUrl = '';
				$url = 'https://hq-org2.democracyinaction.org/dia/api/warehouse/pipe.jsp';
				break;
			case 'send_letter':
				$url = '';
				break;
			case 'list':
				$url = '/api/getObjects.sjs';
				break;
			default:
				$url = '';
				break;
		}

		return $baseUrl.$url;
	}

	/**
	 * Generate a valid url for the Action
	 *
	 * @param	object	ActionId of the item to use
	 * @return	object	Url String
	 */
	function getDisplayUrl( $pContentId = NULL, $pParamHash = NULL ) {
		global $gBitSystem;

		if( !empty( $pParamHash['action_id'] ) || !empty( $this->mActionId ) ){
			$actionId = !empty( $pParamHash['action_id'] )?$pParamHash['action_id']:$this->mActionId;
		}

		if( !empty( $actionId ) && ( $gBitSystem->isFeatureActive( 'pretty_urls' ) || $gBitSystem->isFeatureActive( 'pretty_urls_extended' ) ) && empty( $pParamHash['override_pretty_urls']  ) ) {
			$ret = DIASALSA_PKG_URL.$actionId;
		}elseif( !empty( $actionId ) ){
			$ret = DIASALSA_PKG_URL."index.php?action_id=".$actionId;
		}

		// if all else fails try to get a value from a content id
		if( empty( $ret ) ){
			$contentId = !empty( $pContentId )?$pContentId:( !empty( $this->mContentId )?$this->mContentId:NULL );
			$ret = @LibertyContent::getDisplayUrl( $contentId, $pParamHash );
		}
		return $ret;
	}

	function getSalsaDisplayUrl( $pContentId = NULL, $pParamHash = NULL ){
		global $gBitSystem;

		$ret = NULL;
		$key = NULL;
		if( !empty( $pParamHash['key_id'] ) ){
			$key = $pParamHash['key_id'];
		}
		elseif( $this->isValid() && !empty( $this->mInfo['key_id'] ) ){
			$key = $this->mInfo['key_id'];
		}

		if( !empty( $key ) ){
			$ret = 'http://org2.democracyinaction.org/o/'.$gBitSystem->getConfig( 'diasalsa_organization_key' ).'/p/dia/action/public/?action_KEY='.$key;
			// $ret = 'https://sandbox.salsalabs.com/o/'.$gBitSystem->getConfig( 'diasalsa_organization_key' ).'/p/dia/action/public/?action_KEY='.$key;
		}
		return $ret;
	}


	/*----- Content Linking -----*/
	function verifyLinkContent( &$pParamHash ){
		if( !empty( $pParamHash['content_links'] ) ){
			foreach( $pParamHash['content_links'] as $content_id ){
				// the content id is a number and its not already associated with another action
				if( @$this->verifyId( $content_id ) &&
					!$this->mDb->getOne( "SELECT `to_content_id` FROM `".BIT_DB_PREFIX."diasalsa_action_cnxn_map` WHERE `to_content_id`=? AND `action_content_id` != ?", array( $content_id, $this->mContentId ) ) ) {
					$pParamHash['content_links_store'][] = $content_id;
				}
			}
		}
		// no exceptions currently
		return TRUE;
	}

	function linkContentMixed( &$pParamHash ){
		// batch process we drop all first, then add them back
		$this->mDb->StartTrans();
		$this->expungeAllContentLinks();

		if( !empty( $pParamHash['content_links_store'] ) ){
			foreach( $pParamHash['content_links_store'] as $content_id ){
				$this->linkContent( $content_id );
			}
		}

		$this->mDb->CompleteTrans();
	}

	function linkContent( $pLinkId ){
		if( $this->isValid() && isset( $pLinkId ) ){
			// already linked then skip it
			if( !$this->mDb->getOne( "SELECT `action_content_id` FROM `".BIT_DB_PREFIX."diasalsa_action_cnxn_map` WHERE `action_content_id`=? AND `to_content_id`=?", array( $this->mContentId, $pLinkId ) ) ) {
				$query = "INSERT INTO `".BIT_DB_PREFIX."diasalsa_action_cnxn_map` ( `action_content_id`, `to_content_id` ) VALUES (?,?)";
				$result = $this->mDb->query( $query, array( $this->mContentId, $pLinkId ) );
			}
		}
		return( count( $this->mErrors ) == 0 );
	}

	/* expunges a one to one content to action link */
	function expungeContentLink( $pContentId ){
		if( $this->isValid() && @$this->verifyId( $pContentId )) {
			$query = "DELETE FROM `".BIT_DB_PREFIX."diasalsa_action_cnxn_map` WHERE `action_content_id` = ? AND `to_content_id` = ?";
			$result = $this->mDb->query( $query, array( $this->mContentId, $pContentId ) );
		}
	}

	/* expunges all links to content for a given action */
	function expungeAllContentLinks(){
		if( $this->isValid() ) {
			$this->mDb->StartTrans();
			$query = "DELETE FROM `".BIT_DB_PREFIX."diasalsa_action_cnxn_map` WHERE `action_content_id` = ?";
			$result = $this->mDb->query( $query, array( $this->mContentId ) );
			$this->mDb->CompleteTrans();
		}
	}

	function loadLinkContent(){
		if ( $this->isValid() && $this->mContentId ){

			$sort_mode = 'lc.' . $this->mDb->convertSortmode( 'title_asc' );

			$query = "
				SELECT
					lc.*, lct.`content_description`
				FROM `".BIT_DB_PREFIX."liberty_content` lc
					INNER JOIN `".BIT_DB_PREFIX."diasalsa_action_cnxn_map` dacm ON dacm.`to_content_id` = lc.`content_id`
					LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_content_types` lct ON ( lct.`content_type_guid` = lc.`content_type_guid` )
				WHERE dacm.`action_content_id` = ?
				ORDER BY $sort_mode";

			$result = $this->mDb->query($query,array( $this->mContentId ),99999,0);

			$ret = array();

			while ($res = $result->fetchRow()) {
				$ret[] = $res;
			}

			$this->mInfo['content_links'] = $ret;
		}
	}
	/*----- END Content Linking -----*/


	/*----- Statistics and Management -----*/
	function getSendersList( &$pParamHash = NULL ){
		$selectSql = $joinSql = $whereSql = NULL; $bindVars = array();

		if( !empty( $pParamHash ) && !empty( $pParamHash['exclude_group'] ) ){
			// return any senders not in the same group as the action, both registered and anony
			$query = "
				SELECT
					uu.*, dt.*
				FROM `".BIT_DB_PREFIX."diasalsa_track` dt
					LEFT OUTER JOIN `".BIT_DB_PREFIX."users_users` uu ON ( uu.`email` = dt.`email` )
					$joinSql
				WHERE 
					(
						( uu.`user_id` IS NULL ) 
						OR
						( uu.`user_id` NOT IN ( SELECT user_id 
												FROM `".BIT_DB_PREFIX."users_groups_map` ugm 
													INNER JOIN `".BIT_DB_PREFIX."groups` g ON ( g.`group_id` = ugm.`group_id` ) 
													INNER JOIN `".BIT_DB_PREFIX."groups_content_cnxn_map` gccm ON ( gccm.`group_content_id` = g.`content_id` )
													INNER JOIN `".BIT_DB_PREFIX."diasalsa_actions` a ON ( a.`content_id` = gccm.`to_content_id` )
													INNER JOIN `".BIT_DB_PREFIX."diasalsa_track` dt ON ( dt.`action_id` = a.`action_id` )
												WHERE dt.`action_id` = ? ) ) 
					)
					AND
					dt.`action_id` = ? 
				ORDER BY uu.real_name ASC, uu.login ASC, dt.email ASC";

				// double the pleasure
				$bindVars[] = $this->mActionId;
				$bindVars[] = $this->mActionId;
		}else{
			$sort_mode = 'uu.real_name ASC, login ASC';

			if( !empty( $pParamHash ) && !empty( $pParamHash['group_non_senders'] ) ){
				// group non senders
				$joinSql = " LEFT OUTER JOIN `".BIT_DB_PREFIX."diasalsa_track` dt ON ( dt.`email` = uu.`email` )";
				$whereSql = " AND dt.`email` IS NULL";
			}else{
				// group senders
				$joinSql = " INNER JOIN `".BIT_DB_PREFIX."diasalsa_track` dt ON ( dt.`email` = uu.`email` )";
			}

			$query = "
				SELECT 
					uu.*
				FROM `".BIT_DB_PREFIX."users_users` uu
					INNER JOIN `".BIT_DB_PREFIX."users_groups_map` ugm ON ugm.`user_id` = uu.`user_id` 
					INNER JOIN `".BIT_DB_PREFIX."groups` g ON g.`group_id` = ugm.`group_id` 
					INNER JOIN `".BIT_DB_PREFIX."groups_content_cnxn_map` gccm ON gccm.`group_content_id` = g.`content_id`
					INNER JOIN `".BIT_DB_PREFIX."diasalsa_actions` a ON a.`content_id` = gccm.`to_content_id`
					$joinSql
				WHERE a.`content_id` = ? $whereSql 
				ORDER BY $sort_mode";

			$bindVars[] = $this->mContentId;
		}

		// get them all - someday maybe we need to use pagination
		$result = $this->mDb->query($query,$bindVars,99999,0);

		$ret = array();

		while ($res = $result->fetchRow()) {
			$ret[] = $res;
		}

		return $ret;
	}

	function getSendersCount( $pParamHash ){
		$ret = NULL;

		if( !empty( $pParamHash['action_id'] )){
			$query_cant = "
				SELECT COUNT(*)
				FROM `".BIT_DB_PREFIX."diasalsa_track` dt
				WHERE dt.`action_id` = ?";
			
			$ret = $this->mDb->getOne( $query_cant, array( $pParamHash['action_id'] ) );
		}
		
		return $ret;
	}

	// convenience function
	function getNonSendersList(){
		$listHash['group_non_senders'] = TRUE;
		return $this->getSendersList( $listHash );
	}
	/*----- END Statistics and Management -----*/


	function getActionLink( $pContentId ){
		$ret = NULL;
		if( !empty( $pContentId ) ){
			$ret = $this->mDb->getOne( "SELECT `action_content_id` FROM `".BIT_DB_PREFIX."diasalsa_action_cnxn_map` WHERE `to_content_id`=?", array( $pContentId ) );
		}
		return $ret;
	}

	function getActionsByGroupId( $pGroupId ){
		$ret = NULL;
		if( !empty( $pGroupId ) && @$this->verifyId( $pGroupId ) ){
			$listHash['group_id'] = $pGroupId;
			$listHash['content_type_guid'] = SALSAACTION_CONTENT_TYPE_GUID;
			// @TODO someday add pagination and max results to this list - for now get them all
			$ret = $this->getContentList( $listHash );
		}
		return $ret;
	}

	function getActionList( &$pListHash ){
		global $gBitUser, $gBitSystem;

		if( empty( $pListHash['sort_mode'] ) ){
			$pListHash['sort_mode'] = 'created_asc';
		}

		LibertyContent::prepGetList( $pListHash );

		$selectSql = ''; $joinSql = ''; $whereSql = '';
		$bindVars = array();
		array_push( $bindVars, $this->mContentTypeGuid );

		$this->getServicesSql( 'content_list_sql_function', $selectSql, $joinSql, $whereSql, $bindVars, NULL, $pListHash );

		// this will set $find, $sort_mode, $max_records and $offset
		extract( $pListHash );

		$sortModePrefix = 'lc.';
		$sort_mode = $sortModePrefix . $this->mDb->convertSortmode( $pListHash['sort_mode'] );

		// this could go in groups pkg
		if( $gBitSystem->isPackageActive( 'group' ) ){
			$selectSql .= ", lcg.`title` AS group_title, lcg.`content_id` AS group_content_id";
			$joinSql .= "LEFT OUTER JOIN `".BIT_DB_PREFIX."groups_content_cnxn_map` gccm ON lc.`content_id` = gccm.`to_content_id`
						 LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_content` lcg ON gccm.`group_content_id` = lcg.`content_id`";
		}

		$query = "
			SELECT sa.*, lc.*, 
				acm.`action_c_key_id` AS `action_content_key`, 
				acm.`action_c_detail_key_id` AS `action_content_detail_key`, 
				lc.*, lch.`hits`, lcds.`data` AS `summary`, lc.`last_modified` AS sort_date,
				lf.storage_path AS `image_attachment_path` 
				$selectSql
			FROM `".BIT_DB_PREFIX."diasalsa_actions` sa
				LEFT JOIN 		`".BIT_DB_PREFIX."diasalsa_action_content_map` acm ON acm.`content_id` = sa.`content_id`
				INNER JOIN 		`".BIT_DB_PREFIX."liberty_content` lc 		ON lc.`content_id` 		   = sa.`content_id`
				INNER JOIN		`".BIT_DB_PREFIX."users_users`			 uu ON uu.`user_id`			   = lc.`user_id`
				LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_content_hits` lch ON lc.`content_id`         = lch.`content_id`
				LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_content_data` lcds ON (lc.`content_id` = lcds.`content_id` AND lcds.`data_type`='summary')
				LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_attachments`   la ON la.`content_id`         = lc.`content_id` AND la.`is_primary` = 'y'
				LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_files`         lf ON lf.`file_id`            = la.`foreign_id`
				$joinSql
			WHERE lc.`content_type_guid` = ? $whereSql
			ORDER BY $sort_mode";

        $query_cant = "
            SELECT COUNT(*)
            FROM `".BIT_DB_PREFIX."diasalsa_actions` sa
                INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON( lc.`content_id` = sa.`content_id` ) $joinSql
            WHERE lc.`content_type_guid` = ? $whereSql";
        $result = $this->mDb->query( $query, $bindVars, $max_records, $offset);
        $ret = array();
        while( $res = $result->fetchRow() ) {
			$res['thumbnail_url'] = SalsaAction::getImageThumbnails( $res );				
			$res['display_url'] = SalsaAction::getDisplayUrl( NULL, $res );
			$res['action_count'] = SalsaAction::getSendersCount( $res );
            $ret[] = $res;
        }
        $pListHash["cant"] = $this->mDb->getOne( $query_cant, $bindVars );

		// add all pagination info to pListHash
		LibertyContent::postGetList( $pListHash );
		return $ret;
	}

}




/* SERVICES */

/* edit content service */
function diasalsa_content_edit( &$pObject, &$pParamHash ) {
	global $gBitSystem, $gBitSmarty, $gBitUser, $gBitThemes;
	$excludeContent = array( SALSAACTION_CONTENT_TYPE_GUID, BITCOMMENT_CONTENT_TYPE_GUID, BITUSER_CONTENT_TYPE_GUID, BITGROUP_CONTENT_TYPE_GUID, BITBOARD_CONTENT_TYPE_GUID );
	if ( $gBitSystem->isPackageActive( 'diasalsa' ) &&
		 !in_array( $pObject->getContentType(), $excludeContent ) 
		 ) {
		$action = new SalsaAction();

		if( !empty( $_REQUEST['connect_group_content_id'] ) ){
			$connect_group_content_id = $_REQUEST['connect_group_content_id'];
		}

		if( !empty( $_REQUEST['link_action_content_id'] ) ){
			// for preview we override any current link - we might be changing it
			$action_content_id = $_REQUEST['link_action_content_id'];
		}elseif( $pObject->isValid() ){
			// get the current linked action if it exists
			$action_content_id = $action->getActionLink( $pObject->mContentId ); 
		}
		if( !empty( $action_content_id ) ){
			$action2 = new SalsaAction( NULL, $action_content_id );
			$action2->load();
			$gBitSmarty->assign( 'linkedAction', $action2->mInfo );
		}

		if( $pObject->isValid() ){
			/* redundant to groups too bad */
			if( empty( $contect_group_content_id ) ){
				$listHash['mapped_content_id'] = $pObject->mContentId;
				$listHash['offset'] = 0;
				$group = new BitGroup();
				$groups = $group->getList( $listHash );
				if ( count( $groups ) == 1 ) {
					$connect_group_content_id = $groups[0]['content_id'];
				}
			}
		}

		if( !empty( $connect_group_content_id ) ){
			$actionOptions = $action->getActionsByGroupId( $connect_group_content_id );
			$options[ '' ] = "Select one";
			foreach( $actionOptions as $opt ) {
				$options[ $opt['content_id'] ] = $opt['title'];
			}
			$gBitSmarty->assign( 'actionOptions', $options );
		}

		$gBitThemes->loadAjax( 'mochikit', array( 'Iter.js', 'DOM.js' ));
		$gBitThemes->loadJavascript( DIASALSA_PKG_PATH.'scripts/DIASalsa.js', TRUE );
	}
}

function diasalsa_content_preview( &$pObject, &$pParamHash ) {
	global $gBitSystem;
	if ( $gBitSystem->isPackageActive( 'diasalsa' ) ) {		
		diasalsa_content_edit( $pObject, $pParamHash );
	}
}

/* store content service */
function diasalsa_content_store( &$pObject, &$pParamHash ) {
	global $gBitSystem, $gLibertySystem, $gBitUser;
	$errors = NULL;

	if( $gBitSystem->isPackageActive( 'diasalsa' ) && !empty( $pParamHash['link_action_content_id'] ) && is_numeric( $pParamHash['link_action_content_id'] ) ) {
		// check if it was already linked
		$action = new SalsaAction();
		if( $action_content_id = $action->getActionLink( $pObject->mContentId ) ){ 
			// delete it
			$action->mContentId = $action_content_id;
			$action->load();
			$action->expungeContentLink( $pObject->mContentId );
		}

		// store the new link
		$action2 = new SalsaAction( NULL, $pParamHash['link_action_content_id'] );
		$action2->load();
		$action2->linkContent( $pObject->mContentId );
	}
}

/* expunge content service */
function diasalsa_content_expunge( &$pObject) {
	global $gBitSystem;
	if ( $gBitSystem->isPackageActive( 'diasalsa' ) ) {		
		if( $pObject->isValid() ){
			// unlink action if it exists
			$action = new SalsaAction();
			if( $action_content_id = $action->getActionLink( $pObject->mContentId ) ){ 
				$action = new SalsaAction( NULL, $action_content_id );
				$action->load();
				$action->mDb->StartTrans();
				$action->expungeContentLink( $pObject->mContentId );
				$action->mDb->CompleteTrans();
			}
		}
	}
}




/* some XML utilities */

function dom2array_full($node){
    $result = array();
    if($node->nodeType == XML_TEXT_NODE) {
        $result = $node->nodeValue;
    }
    else {
        if($node->hasAttributes()) {
            $attributes = $node->attributes;
            if(!is_null($attributes)) 
                foreach ($attributes as $index=>$attr) 
                    $result[$attr->name] = $attr->value;
        }
        if($node->hasChildNodes()){
            $children = $node->childNodes;
            for($i=0;$i<$children->length;$i++) {
                $child = $children->item($i);
                if($child->nodeName != '#text')
                if(!isset($result[$child->nodeName]))
                    $result[$child->nodeName] = dom2array($child);
                else {
                    $aux = $result[$child->nodeName];
                    $result[$child->nodeName] = array( $aux );
                    $result[$child->nodeName][] = dom2array($child);
                }
            }
        }
    }
    return $result;
}

function dom2array($node) {
  $res = array();
  if($node->nodeType == XML_TEXT_NODE){
      $res = $node->nodeValue;
  }
  else{
      if($node->hasAttributes()){
          $attributes = $node->attributes;
          if(!is_null($attributes)){
              $res['@attributes'] = array();
              foreach ($attributes as $index=>$attr) {
                  $res['@attributes'][$attr->name] = $attr->value;
              }
          }
      }
      if($node->hasChildNodes()){
          $children = $node->childNodes;
          for($i=0;$i<$children->length;$i++){
              $child = $children->item($i);
              $res[$child->nodeName] = dom2array($child);
          }
      }
  }
  return $res;
}


function diasalsa_content_load_sql( &$pObject, $pParamHash=NULL ) {
	global $gBitSystem;

	$ret = array();

	if( $pObject->getContentType() != SALSAACTION_CONTENT_TYPE_GUID ){
		$now = $gBitSystem->getUTCTime();

		$ret['select_sql'] = " , dialc.`title` AS `action_title`, dialc.`content_id` AS `action_content_id`, diaa.`action_id`";
		$ret['join_sql'] = " LEFT OUTER JOIN `".BIT_DB_PREFIX."diasalsa_action_cnxn_map` diam ON ( diam.`to_content_id`=lc.`content_id` )";
		$ret['join_sql'] .= " LEFT OUTER JOIN `".BIT_DB_PREFIX."diasalsa_actions` diaa ON ( diaa.`content_id`=diam.`action_content_id` AND ( diaa.`expire_date` > ".$now." OR diaa.`expire_date` IS NULL ) )";
		$ret['join_sql'] .= " LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_content` dialc ON ( dialc.`content_id`=diaa.`content_id` )";
	}

	return $ret;
}
