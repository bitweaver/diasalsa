{strip}
{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='nav' serviceHash=$action}

<div class="admin diasalsa">
	{* include file="bitpackage:diasalsa/action_icons.tpl" *}

	<div class="header">
		<h1>{$action.title|escape}</h1>
		<h2>Action Statistics and Management</h2>
	</div> <!-- end .header -->

	<div class="body">
		<div class="content">		
		{formfeedback success=$successEmailMsg error=$errorEmailMsg}
		{jstabs}
			{jstab title="Activity"}
				{jstabs}
					{jstab title="Non Senders"}
						{legend legend="Group Members who have NOT sent this action email."}
						<ol class="data">
							{foreach from=$groupNonSenders item=member}
								<li>{displayname hash=$member}</li>
							{foreachelse}
								<li>{tr}All group members have sent this email.{/tr}</li>
							{/foreach}
						</ol>
						{/legend}
					{/jstab}
					{jstab title="Senders"}
						{legend legend="Group Members who have sent this action email."}
						<ol class="data">
							{foreach from=$groupSenders item=member}
								<li>{displayname hash=$member}</li>
							{foreachelse}
								<li>{tr}No group members who have sent this email were found.{/tr}</li>
							{/foreach}
						</ol>
						{/legend}
						{legend legend="Non Group Members who have sent this action email."}
						<ol class="data">
							{foreach from=$nonGroupSenders item=member}
								<li>{if $member.user_id}{displayname hash=$member}{else}<a href="mailto:{$member.email}">{$member.email}</a> {tr}(not a registered user){/tr}{/if}</li>
							{foreachelse}
								<li>{tr}No one outside the group has sent this email.{/tr}</li>
							{/foreach}
						</ol>
						{/legend}
					{/jstab}
				{/jstabs}
			{/jstab}
			{if $groupContent && $groupContent->getField('group_id')}
			{jstab title="Email Group Members"}
				{legend legend="Send an email blast to members of the group about this action."}
				{form}
						<input type="hidden" name="content_id" value="{$gContent->getField('content_id')}" />
						<div class="row">
							{formlabel label="Select Email Recipients" for="email_targets"}
							{forminput}
								<input type="radio" name="email_targets" value="nonsenders" checked/>{tr}Only Group Members who have NOT sent the email{/tr}<br />
								<input type="radio" name="email_targets" value="all" />{tr}All Group Members{/tr}<br />
								<input type="radio" name="email_targets" value="senders" />{tr}Only Group Members who have sent the email{/tr}<br />
							{/forminput}
						</div>
						<div class="row">
							{formlabel label="Subject" for="subject"}
							{forminput}
							<input type="text" size="60" maxlength="200" name="email_subject" value="{tr}Take Action:{/tr} {$action.title}" />
							{/forminput}
						</div>
						{textarea name="email_body" label="Email Body"}{$action.summary}<p>{tr}Take Action by going to <a href="{$smarty.const.BIT_BASE_URI}{$action.display_url}">{$gBitSystem->getConfig('site_title')}{/tr}</a><br />{$smarty.const.BIT_BASE_URI}{$action.display_url}</p>{/textarea}
						<div class="row submit">
							<input type="submit" name="send_email" value="{tr}Send{/tr}" />
						</div>
				{/form}
				{/legend}
			{/jstab}
			{/if}
		{/jstabs}

		<ul class="manageoptions">
			<li><a href="{$action.display_url}">{tr}Back to Action{/tr}</a></li>
		</ul>

		</div> <!-- end .content -->
	</div> <!-- end .body -->
</div> <!-- end .display -->

{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='view' serviceHash=$action}
{/strip}
