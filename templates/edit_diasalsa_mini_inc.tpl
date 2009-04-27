{strip}
{if $gBitSystem->isPackageActive('diasalsa') && $gBitUser->hasPermission('p_actions_create') && $actionOptions}
	{legend legend="Actions"}
	{formhelp note="If your group is running any campaigns to take political action, you can link this content to one of those actions."}
	<div class="row">
		{formlabel label="Linked Action" for="link_action_content_id"}
		{forminput}
			<div class="row" id="linked_action">
				{if $linkedAction}
					{jspopup class="popup_link" href=$linkedAction.display_url title=$linkedAction.title}
				{else}
					{tr}This content is not linked to any action{/tr}
				{/if}
				<input type="hidden" name="link_action_content_id" value="{if $preview}{$smarty.post.link_action_content_id}{elseif $linkedAction->mContentId}{$linkedAction->mContentId}{/if}" />
			</div>
		{/forminput}
		{formlabel label="Action Options"}
		<div class="row" id="action_options">
			{html_options name="action_options" options=$actionOptions name="action_options" onchange="DIASalsa.linkAction( this )"}
		</div>
	</div>
	{/legend}
{/if}
{/strip}
