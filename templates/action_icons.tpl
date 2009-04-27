<div class="floaticon">
	{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='icon' serviceHash=$gContent->mInfo}

	{if $print_page ne 'y'}
		{if $gContent->hasUpdatePermission()}
			<a title="{tr}Link this action to content{/tr}" href="{$smarty.const.DIASALSA_PKG_URL}link.php?action_id={$action.action_id}">{biticon ipackage="icons" iname="mail-attachment" iexplain="Link"}</a>
		{/if}
		{if $gContent->hasUpdatePermission()}
			<a title="{tr}Edit this action{/tr}" href="{$smarty.const.DIASALSA_PKG_URL}edit.php?action_id={$gContent->mInfo.action_id}">{biticon ipackage="icons" iname="accessories-text-editor" iexplain="Edit Action"}</a>
		{/if}
		{if $gContent->hasAdminPermission() || $gContent->isOwner()}
			<a title="{tr}Remove this action{/tr}" href="{$smarty.const.DIASALSA_PKG_URL}remove_action.php?action_id={$gContent->mInfo.action_id}">{biticon ipackage="icons" iname="edit-delete" iexplain="Remove Action"}</a>
		{/if}
	{/if}<!-- end print_page -->
</div><!-- end .floaticon -->
