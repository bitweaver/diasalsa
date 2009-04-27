/* Dependencies: MochiKit Base Async Iter DOM, BitAjax.js  */
DIASalsa = {
	"path":"./lookup_legislator.php?",
	"orgTargetsBackedup":false,
	"orgTargets":[],
	"backupOrgTargets": function( form ){
		/*
		if( !DIASalsa.orgTargetsBackedup ){
			var o = form.target_ids.options;
			for( i=0; i<o.length; i++ ){
				DIASalsa.orgTargets.push( o[i].cloneNode(true) );
			}
			DIASalsa.orgTargetsBackedup = true;
		}
		*/
	},
	"getTargetList": function( form ){
		var url = DIASalsa.path;
		var o = form.district_type;
		var type = o.options[o.selectedIndex].value;
		url += "district_type=" + type;
		o = form.region;
		var region = o.options[o.selectedIndex].value
		url += "&region=" + region;
		url += "&xml=y";
		BitBase.showSpinner();
		var r = doSimpleXMLHttpRequest(url);
		r.addCallback( DIASalsa.getTargetListCallback, type, region ); 
		r.addErrback( BitAjax.error );
	},
	"getTargetListCallback": function( type, region, rslt ){
		// clear out old options
		var e = $('targets_options_list');
		var count = e.childNodes.length;
		for (n=count; n>0; n--){
		   e.removeChild(e.childNodes[n-1]);
		}
		// add new options container
		var ul = UL( {id:type+'_'+region+'_targets_options', class:'listmenu'} );
		e.appendChild( ul );
		//parse xml
		var xml = rslt.responseXML.documentElement;
		var legs = xml.getElementsByTagName( 'legislator' );
		var count2 = legs.length;
		for( n=0;n<count2;n++ ){
			var target_id = DIASalsa.getXMLTagValue( legs[n], 'person_legislator_ID' );
			var desc = DIASalsa.getXMLTagValue( legs[n], 'display_name' ) + " ("+DIASalsa.getXMLTagValue( legs[n], 'district_code' )+")";
			// add new options
			DIASalsa.addTargetOption( type, target_id, desc, region );
		}
		if( count2 < 1 ){
			alert( 'Sorry, we could not find any results matching your reqeust' );
		}

		BitBase.hideSpinner();
	},
	"addTarget": function( type, target_id, desc, region ){
		// DIASalsa.backupOrgTargets();
		// if already attached skip it
		if( !$( 'target_'+target_id ) ){
			region = typeof( region )!='undefined'?region:"";
			var li = LI( {id:'target_'+target_id}, 
							A( {class:"floaticon", href:'javascript:void(0);', onclick:"DIASalsa.removeTarget('"+type+"','"+target_id+"','"+desc+"','"+region+"')"},
								IMG( {class:'icon', src:BitSystem.urls.themes+'icon_styles/tango/small/list-remove.png', alt:'remove'} ) 
							),
							desc,
							INPUT( {type:'hidden',name:"action_targets[]",value:target_id} )
						); 
			var ul = $( type+'_targets' );
			ul.style.display = 'block';
			ul.appendChild( li );
			e = $('target_option_'+target_id);
			e.parentNode.removeChild( e );
			e = null;
		}
	},
	"addTargetOption": function( type, target_id, desc, region ){
		// if already attached skip it
		if( !$( 'target_option_'+target_id ) ){
			region = typeof( region )!='undefined'?region:"";
			var li = LI( {id:'target_option_'+target_id}, 
							A( {class:"floaticon", href:'javascript:void(0);', onclick:"DIASalsa.addTarget('"+type+"','"+target_id+"','"+desc+"','"+region+"')"},
								IMG( {class:'icon', src:BitSystem.urls.themes+'icon_styles/tango/small/list-add.png', alt:'add'} ) 
							),
							desc
						); 

			var ulid = type+ ( (typeof(region) == 'undefined' || region == "") ? "" : '_'+region ) + '_targets_options'; 
			var ul = $( ulid );
			if( ul ){
				ul.appendChild( li );
			}
		}
	},
	"removeTarget": function( type, target_id, desc, region ){
		// DIASalsa.backupOrgTargets( form );
		e = $('target_'+target_id);
		e.parentNode.removeChild( e );
		e = null;
		region = typeof( region )!='undefined'?region:"";
		DIASalsa.addTargetOption( type, target_id, desc, region );
	},
	"resetTargetForm": function( form ){
		var o = form.target_ids.options;
		// remove all ids
		for( i=o.length; i>0; i-- ){
			o.parentNode.removeChild( o[i] );
		}
		// attach org
		var list = DIASalsa.orgTargets;
		for( i=0; i<list.length; i++ ){
			o.addChild( list[i] );
		}
	},
	"getXMLTagValue": function( xml, tag ){
		var value = null;
		var node = xml.getElementsByTagName( tag );
		if( typeof( node[0] ) != 'undefined' ){
			value = this.getNodeValue( node[0].firstChild );
		}
		value = value != null ? value:"";
		return value;
	},
	"getNodeValue": function( node ){
		if( node != null ){
			return node.nodeValue;
		}
		return null;
	},
	"getContent": function( page ){
		if( BitGroup && BitGroup.connect_group_content_id ){
			var params = {connect_group_content_id:BitGroup.connect_group_content_id,
						  list_page:(typeof(page) != 'undefined'?page:0)};
			var url = BitSystem.urls.diasalsa+"view_content_options_inc.php";
			var str = queryString(params);
			str += "&exclude_content_type_guid[]=bitboard";
			str += "&exclude_content_type_guid[]=bitaction";
			BitAjax.updater( 'content_options', url, str );
		}else{
			alert("You appear to be editing an action outside a group. Content can not be linked.");
		}
	},
	"addContent": function( id, title, desc ){
		// if already attached skip it
		if( !$( 'content_link_'+id ) ){
			var li = LI( {id:'content_link_'+id}, 
							A( {class:"floaticon", href:'javascript:void(0);', onclick:"DIASalsa.removeContent("+id+")"},
								IMG( {class:'icon', src:BitSystem.urls.themes+'icon_styles/tango/small/list-remove.png', alt:'remove'} ) 
							),
							A( {href:'javascript:void(0);',
								onclick:"popUpWin('/index.php?content_id="+id+"','standard',null,null);"}, 
								title ),
							" ("+desc+")",
							INPUT( {type:'hidden',name:"content_links[]",value:id} )
						); 
			var ul = $( 'content_links' );
			ul.style.display = 'block';
			ul.appendChild( li );
			/* lets not remove for now
			e = $('content_option_'+id);
			e.parentNode.removeChild( e );
			e = null;
			*/
		}
	},
	"removeContent": function( id ){
		e = $('content_link_'+id);
		e.parentNode.removeChild( e );
		e = null;
	},
	"swapFrameContent": function( frmid, targetid ){
		var ifrm = document.getElementById(frmid);
		if (ifrm.contentDocument) {
			var d = ifrm.contentDocument;
		} else if (ifrm.contentWindow) {
			var d = ifrm.contentWindow.document;
		} else {
			var d = window.frames[frmid].document;
		}
		// this func gets called every time the frame loads - so make sure we are getting the bw content
		try{ 
			var elm = d.getElementById("bw_swap_content");
			try{ 
				elm.parentNode.removeChild( elm );
				ifrm.parentNode.removeChild( ifrm ); 
				$(targetid).appendChild( elm );
			}
			catch( e1 ){}
			// ensure the page displays even if the frame swap fails
			elm.style.display = 'block';
		}
		catch( e ){}
	},
	"linkAction": function( o ){
		var id = o.options[o.selectedIndex].value;
		if( id != "" ){
			var title = o.options[o.selectedIndex].label;

			var div = DIV( {class:'row',id:'linked_action'}, 
							A( {href:'javascript:void(0);',
								onclick:"popUpWin('/index.php?content_id="+id+"','standard',null,null);"}, 
								title ),
							INPUT( {type:'hidden',name:"link_action_content_id",value:id} )
						); 
			MochiKit.DOM.swapDOM( $('linked_action'), div );
		}
	}
}
