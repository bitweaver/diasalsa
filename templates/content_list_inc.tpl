{strip}
{if $contentList}
	<ul class="listmenu">
		{foreach from=$contentList item=content} 
			<li id="content_option_{$content.content_id}">
				<a class="floaticon" href="javascript:void(0);" onclick="DIASalsa.addContent({$content.content_id},'{$content.title}','{$content.content_description}' )">{biticon iname="list-add" iexplain="remove"}</a>
				{jspopup class="popup_link" href="`$smarty.const.BIT_ROOT_URL`index.php?content_id=`$content.content_id`" title=$content.title width="null" height="null"} ({$content.content_description})
			</li>
		{/foreach}
	</ul>
	{* pagination *}
	{if $listInfo.current_page > 1}
		{if $gBitThemes->isAjaxRequest()}
			&nbsp;<a href="javascript:void(0);" onclick="DIASalsa.getContent( {$listInfo.current_page-1} );">&laquo;</a>
		{/if}
	{/if}
	{tr}Page <strong>{$listInfo.current_page}</strong> of <strong>{$listInfo.total_pages}</strong>{/tr}
	{if $listInfo.current_page < $listInfo.total_pages}
		{if $gBitThemes->isAjaxRequest()}
			&nbsp;<a href="javascript:void(0);" onclick="DIASalsa.getContent( {$listInfo.current_page+1} );">&raquo;</a>
		{/if}
	{/if}
{else}
	{tr}Sorry, we could not find any content.{/tr}
{/if}
{/strip}
