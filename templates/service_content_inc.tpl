{strip}
{if $gBitSystem->isPackageActive('diasalsa')}
	{if $gContent|is_object && $gContent->getField('action_content_id')}
		<a class="actionicon floaticon" href="{$smarty.const.DIASALSA_PKG_URL}action/{$gContent->getField('action_id')}" title="Take Action: {$gContent->getField('action_title')}">
			<img class="icon" src="{$smarty.const.DIASALSA_PKG_URL}icons/takeaction_lrg.png" alt="Take Action" />
		</a>
	{/if}
{/if}
{/strip}
