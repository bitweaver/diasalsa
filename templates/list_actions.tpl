{strip}
<div class="listing actions">
	<div class="header">
		<h1>{tr}Recent Letter Writing Campaigns{/tr}</h1>
	</div>

	<div class="body">
		{if $actionList}
			<ul class="data">
				{foreach from=$actionList item=action name=actions}
					<li class="action{if $smarty.foreach.actions.last} last{/if}">
						{if $gBitUser->isAdmin()}
							<div class="floaticon">
								{smartlink ititle="Delete" ibiticon="icons/accessories-text-editor" ifile="edit.php" content_id=$action.content_id action=edit}
								{smartlink ititle="Delete" ibiticon="icons/edit-delete" ifile="remove_action.php" content_id=$action.content_id action=delete}
							</div>
						{/if}
						<a href="{$action.display_url}" class="thumb">
							<img class="thumb" src="{if $action.thumbnail_url.avatar}{$action.thumbnail_url.avatar}{else}{$smarty.const.THEMES_STYLE_URL}images/action_list_icon.png{/if}" alt="{$action.title|escape}" title="{$action.title|escape}" />
						</a>
						<div class="wrapper">
							<h2><a href="{$action.display_url}">{$action.title|escape}</a></h2>
							<p>{$action.summary}</p>
							<strong>{tr}Action Started{/tr}:</strong> {$action.created|bit_long_date}<br/>
							{if $action.group_content_id}<strong>{tr}Created by Group{/tr}:</strong> <a href="{$smarty.const.BIT_ROOT_URL}?content_id={$action.group_content_id}">{$action.group_title}</a><br />{/if}
							<strong>{tr}Number of People{/tr}:</strong> {$action.action_count}<br />
							<a class="actionlink" href="{$action.display_url}">Take Action Now!</a>
						</div>
						<div class="clear"></div>
					</li>
				{/foreach}
			</ul>
		{else}
			<p class="norecords">
				{tr}No actions found{/tr}
			</p>
		{/if}
		{pagination}
	</div><!-- end .body -->
</div><!-- end .actions -->
{/strip}
