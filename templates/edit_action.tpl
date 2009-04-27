{strip}
<div class="edit diasalsa">
	<div class="header">
		{ if !$action.content_id }
			<h1>{tr}Create Action{/tr}</h1>
		{else}
			<h1>{tr}Edit Action{/tr}</h1>
		{/if}
	</div>

 	{if $errors}
 		{formfeedback warning=`$errors`}
 	{/if}

	<div class="body">
		{form enctype="multipart/form-data" id="edit_action_form"}
			<div class="servicetabs">
			{jstabs id="servicetabs"}
				{jstab title="Options"} {* here we assign edit_content_status_tpl to customize the status input presentation. this gets passed along to liberty::edit_service_mini_inc.tpl *}
					{legend legend="Expiration"}
					<div class="row">
						{formlabel label="Expiration Date" for=""}
						<input type="checkbox" name="expire" value="y" {if $action.expire}checked="checked"{/if} onchange="BitBase.toggleElementDisplay( 'exp_date', 'block' )"/> Set expiration date
					</div>
					<div class="row" id="exp_date" {if !$action.expire}style="display:none"{/if}>
						{forminput}
							{html_select_date prefix="expire_" time=$action.expire_date start_year="-1" end_year="+10"} {tr}at{/tr}&nbsp;
							<span dir="ltr">{html_select_time prefix="expire_" time=$action.expire_date display_seconds=false}&nbsp;{$siteTimeZone}</span>
							{formhelp note="Your action will expire on this date. If you do not want your action to expire, uncheck the checkbox above."}
						{/forminput}
					</div>
					{/legend}
					{include file="bitpackage:liberty/edit_services_inc.tpl" serviceFile=content_edit_mini_tpl}
				{/jstab}
				{jstab title="Targets"}
					{legend legend="Selected Targets"}
						{formhelp note="Lawmakers you select will be targeted. Options are below. Click the + icon to add lawmakers, click the - icon to remove them."}
						<div class="row">
							{foreach from=$action.targets key=target_type item=target_group} 
								{if $target_type == 'auto'}
									{assign var=target_type_label value="Auto Targets"}
								{else}
									{assign var=target_type_label value=$target_type_desc.$target_type}
								{/if}
								{assign var=target_display value=""}
								{if empty($target_group)}
									{assign var=target_display value="display:none;"}
								{/if}
								<ul id="{$target_type}_targets" class="listmenu" {if empty($target_group)}style="display:none;"{/if}>
								{formlabel label=$target_type_label}
								{foreach from=$target_group item=targets} 
									{if !$targets.target_keys}
										{* these are auto targets *}
										<li id="target_{$targets.target}">
											<a class="floaticon" href="javascript:void(0);" onclick="DIASalsa.removeTarget('{$target_type}','{$targets.target}','{$targets.target_desc}')">{biticon iname="list-remove" iexplain="remove"}</a>
											{$targets.target_desc}
											<input type="hidden" name="action_targets[]" value="{$targets.target}"/>
										</li>
									{else}
										{foreach from=$targets.target_keys item=target} 
										<li id="target_{$target.person_legislator_key}">
											<a class="floaticon" href="javascript:void(0);" onclick="DIASalsa.removeTarget('{$target_type}','{$target.person_legislator_key}','{$target.display_name}')">{biticon iname="list-remove" iexplain="remove"}</a>
											{$target.display_name}
											<input type="hidden" name="action_targets[]" value="{$target.person_legislator_key}"/>
										</li>
										{/foreach}
									{/if}
								{/foreach}
								</ul>
							{/foreach}
							{* <input type="button" value="Reset" onclick="DIASalsa.resetTargetForm( this.form );"> *}
						</div>
					{/legend}
					{legend legend="Auto Target Options"}
						{formhelp note="You can target federal and state lawmakers. When you assign auto targets, the appropriate lawmaker for a user will be identified for them when they enter their zip code."}
						<div class="row">
							<ul id="auto_targets_options" class="listmenu">
								{foreach from=$target_type_desc key=target item=target_desc}
									{* dont display any that have been assigned *}
									{if !$action.targets.auto.$target}
										<li id="target_option_{$target}"><a class="floaticon" href="javascript:void(0);" onclick="DIASalsa.addTarget('auto', '{$target}', '{$target_desc}')">{biticon iname="list-add" iexplain="add"}</a>{$target_desc}</li>
									{/if}
								{/foreach}
							</ul>
						</div>
					{/legend}
					{legend legend="Individual Legislators"}
						{formhelp note="You can target individual federal and state lawmakers. Target lawmakers will be sent letters from people in their districts."}
						<div class="row">
							<select name="district_type">
								<option value="FS">US Senate
								<option value="FH">US House
								<option value="SE">Governor
								<option value="SS">State Senate
								<option value="SH">State House
							</select>
							<select name="region">
								<option value="AL" >Alabama</option>
								<option value="AK" >Alaska</option>
								<option value="AS" >American Samoa</option>
								<option value="AZ" >Arizona</option>
								<option value="AR" >Arkansas</option>
								<option value="CA" >California</option>

								<option value="CO" >Colorado</option>
								<option value="CT" >Connecticut</option>
								<option value="DE" >Delaware</option>
								<option value="DC" >D.C.</option>
								<option value="FL" >Florida</option>
								<option value="GA" >Georgia</option>

								<option value="GU" >Guam</option>
								<option value="HI" >Hawaii</option>
								<option value="ID" >Idaho</option>
								<option value="IL" >Illinois</option>
								<option value="IN" >Indiana</option>
								<option value="IA" >Iowa</option>

								<option value="KS" >Kansas</option>
								<option value="KY" >Kentucky</option>
								<option value="LA" >Louisiana</option>
								<option value="ME" >Maine</option>
								<option value="MD" >Maryland</option>
								<option value="MA" >Massachusetts</option>

								<option value="MI" >Michigan</option>
								<option value="MN" >Minnesota</option>
								<option value="MS" >Mississippi</option>
								<option value="MO" >Missouri</option>
								<option value="MT" >Montana</option>
								<option value="NE" >Nebraska</option>

								<option value="NV" >Nevada</option>
								<option value="NH" >New Hampshire</option>
								<option value="NJ" >New Jersey</option>
								<option value="NM" >New Mexico</option>
								<option value="NY" >New York</option>
								<option value="NC" >North Carolina</option>

								<option value="ND" >North Dakota</option>
								<option value="OH" >Ohio</option>
								<option value="OK" >Oklahoma</option>
								<option value="OR" >Oregon</option>
								<option value="PA" >Pennsylvania</option>
								<option value="PR" >Puerto Rico</option>

								<option value="RI" >Rhode Island</option>
								<option value="SC" >South Carolina</option>
								<option value="SD" >South Dakota</option>
								<option value="TN" >Tennessee</option>
								<option value="TX" >Texas</option>
								<option value="UT" >Utah</option>

								<option value="VT" >Vermont</option>
								<option value="VI" >Virgin Islands</option>
								<option value="VA" >Virginia</option>
								<option value="WA" >Washington</option>
								<option value="WV" >West Virginia</option>
								<option value="WI" >Wisconsin</option>
								<option value="WY" >Wyoming</option>
							</select>
							<input type="button" value="Lookup" onclick="DIASalsa.getTargetList( this.form )";>
						</div>
						<div class="row" id="targets_options_list">
						</div>
					{/legend}
				{/jstab}
				{jstab title="Content"}
					{legend legend="Linked Content"}
						{formhelp note="You can link this Action to any content in your group. A \"Take Action\" graphic will appear all content pages linked to this action."}
						<div class="row">
							<ul id="content_links" class="listmenu">
								{foreach from=$action.content_links item=clink} 
									<li id="content_link_{$clink.content_id}">
										<a class="floaticon" href="javascript:void(0);" onclick="DIASalsa.removeContent({$clink.content_id})">{biticon iname="list-remove" iexplain="remove"}</a>
										{jspopup class="popup_link" href="`$smarty.const.BIT_ROOT_URL`index.php?content_id=`$clink.content_id`" title=$clink.title width="null" height="null"} ({$clink.content_description})
										<input type="hidden" name="content_links[]" value="{$clink.content_id}"/>
									</li>
								{/foreach}
							</ul>
						</div>
					{/legend}
					{legend legend="Content Options"}
						<div class="row" id="content_options">
							<input type="button" value="Get Content List" onclick="DIASalsa.getContent()";>
						</div>
					{/legend}
				{/jstab}

				{if $gBitUser->hasPermission('p_liberty_attach_attachments') }
				{jstab title="Attachments"}
					{legend legend="Attachments"}
						{include file="bitpackage:liberty/edit_storage.tpl"}
					{/legend}
				{/jstab}
				{/if}

				{include file="bitpackage:liberty/edit_services_inc.tpl" serviceFile=content_edit_tab_tpl display_help_tab=1}
			{/jstabs}
			</div>
			<div class="editcontainer">
			{jstabs}
				{if $preview eq 'y'}
					{jstab title="Preview"}
						{legend legend="Preview"}
						<div class="preview">
							{include file="bitpackage:diasalsa/view_action.tpl" preview=y}
						</div>
						{/legend}
					{/jstab}
				{/if}
				{jstab title="Edit"}
					{legend legend="Action"}
						<input type="hidden" name="content_id" value="{$action.content_id}" />
						<input type="hidden" name="object" value="action"/>
						<input type="hidden" name="key_id" value="{$action.key_id}"/>
						<!--
						<input type="hidden" name="action_manager_KEY" value="8975"/>
						<input type="hidden" name="workflow_instance_task_KEY" value="0"/>
						<input type="hidden" name="workflow_instance_KEY" value="26289"/>
						<input type="hidden" name="workflow_task_KEY" value="44"/>
						<input type="hidden" name="table" value="action"/>
						<input type="hidden" name="key" value="[[action_KEY]]"/>
						<input type="hidden"name="redirect" value="workflow.jsp?workflow_instance_KEY=26289&whichTab=2"/>
						<input type="hidden" name="successMessage" value=""/>
						-->

						<div class="row">
							{formlabel label="Action Title" for="title"}
							{forminput}
								<input  size=50 name='title' value='{$action.title}' />
								{formhelp note=""}
							{/forminput}
						</div>

						<div class="row">
							{formlabel label="Action Description" for="summary"}
							{forminput}
								<input size=50 name="summary" value="{$action.summary}" />
								{formhelp note="This is an optional short description of your action and will appear in action lists."}
							{/forminput}
						</div>

						<div class="row">
							{textarea}{$action.data}{/textarea}
							{formhelp note="This test will appear on the action page above the letter."}
						</div>
						<div class="row">
							{formlabel label="Thank You Page" for="thankyou_data"}
							{forminput}
								{textarea id="thankyou_data" name="thankyou_data" noformat=y}{$action.thankyou_data}{/textarea}
								{formhelp note="This test will be displayed after a person sends the letter."}
							{/forminput}
						</div>
					{/legend}
					{legend legend="Letter"}
						<div class="row">
							{formlabel label="Letter Subject" for="recommended_subject"}
							{forminput}
								<input size=50 name="recommended_subject" value="{$action.recommended_subject}" />
								{formhelp note=""}
							{/forminput}
						</div>

						<div class="row">
							{formlabel label="Letter Subject Can Be Edited" for="subject_editable"}
							{forminput}
								<input type="checkbox" name="subject_editable" value="y" {if $action.subject_editable || !$gContent->isValid()}checked='y'{/if} />
								{formhelp note="Check this if you want to allow people to customize the letter subject."}
							{/forminput}
						</div>

						<div class="row">
							{formlabel label="Letter Body" for="recommended_content"}
							{forminput}
								{formhelp note="Plain text only"}
								<textarea rows=10 cols=100 name='recommended_content'>{$action.recommended_content}</textarea>
							{/forminput}
						</div>

						<div class="row">
							{formlabel label="Letter Body Can Be Edited" for="content_editable"}
							{forminput}
								<input type="checkbox" name="content_editable" value="y" {if $action.content_editable || !$gContent->isValid()}checked='y'{/if} />
								{formhelp note="Check this if you want to allow people to customize the letter text. Highly recommended as personalized letters tend to have a greater impact."}
							{/forminput}
						</div>
					{/legend}
						<div class="row submit">
							<input type="submit" name="cancel" value="{tr}Cancel{/tr}" />&nbsp;
							<input type="submit" name="preview" value="{tr}Preview{/tr}" />&nbsp;
							<input type="submit" name="store_action" value="{tr}Save{/tr}" />&nbsp;
							<input type="submit" name="store_action_continue" value="{tr}Save and Continue Editing{/tr}" />
						</div>
				{/jstab}
			{/jstabs}
			</div>
		{/form}
	</div><!-- end .body -->
</div><!-- end .diasalsa -->
{/strip}
