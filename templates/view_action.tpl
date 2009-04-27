{strip}
{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='nav' serviceHash=$action}

<div class="display diasalsa">
	{if !($preview)}
		{* include file="bitpackage:diasalsa/action_icons.tpl" *}
	{/if}

	<div class="header">
		<h1>{$action.title|escape}</h1>

		{if !$preview}
		<div class="date">
			{tr}Created by {displayname hash=$action} on {$action.created|bit_long_date}{/tr}
		</div>
		{/if}
	</div> <!-- end .header -->

	<div class="body">
		<div class="content">		
			{* include file="bitpackage:liberty/services_inc.tpl" serviceLocation='body' serviceHash=$action *}

			<img class="icon floaticon" style="margin-top:-18px" src="{$smarty.const.DIASALSA_PKG_URL}icons/takeaction_lrg.png" alt="Take Action" />

			<ul class="manageoptions">
				{if $gContent->hasAdminPermission() && !$preview}
					<li><a href="{$smarty.const.DIASALSA_PKG_URL}manage.php?action_id={$action.action_id}">{tr}View Action Stats{/tr}</a></li>
				{/if}
				{if $gBitSystem->isPackageActive( 'sharethis' )}
					<li style="border:none; padding:0">
						{assign var=title value=$gContent->getTitle()|default:$serviceHash.title}
						{assign var=display_url value=$gContent->getDisplayUrl()|default:$serviceHash.display_url}
						<script language="javascript" type="text/javascript">
							var obj = SHARETHIS.addEntry({ldelim}
								title:'{$title|addslashes}',
								url:'{$smarty.const.BIT_BASE_URI}{$display_url|escape}'
							{rdelim}, {ldelim}button:true{rdelim} );
						</script>
					</li>
				{/if}
			</ul>

			{if $gContent->verifyLetterSent()}
				<h3>{tr}Thank You, you have already taken action on this!{/tr}</h3>
				<p>{tr}Please consider sharing this action with friends.{/tr}</p>
			{/if}

			{if $action.thumbnail_url}
				<div class="image">
					{jspopup notra=1 href=$action.thumbnail_url.original alt=$action.title|escape title=$action.title|escape" img=$action.thumbnail_url.medium}
				</div>
			{/if}

			{$action.parsed_data}

			<div id="salsa_body" style="clear:both">
				{if !$preview}
					{if !$gContent->verifyLetterSent()}
						<h2>Take Action!</h2>
						<iframe src="{$action.salsa_display_url}" id="salsa_iframe" onload="if( typeof(DIASalsa)!='undefined' ){ldelim}DIASalsa.swapFrameContent( 'salsa_iframe', 'salsa_body' );{rdelim}" width="100%" height="1600" marginheight="0" marginwidth="0" border="0" frameborder="0"></iframe>
						{* for testing <iframe src="{$action.display_url}&thankyou=y" id="salsa_iframe" onload="if( typeof(DIASalsa)!='undefined' ){ldelim}DIASalsa.swapFrameContent( 'salsa_iframe', 'salsa_body' );{rdelim}else{ldelim}alert('un');{rdelim}" width="100%" height="1600" marginheight="0" marginwidth="0" border="0" frameborder="0"></iframe> *}
					{/if}
				{else}
					{* for preview we show the basic letter not all the dia fanciness *}
					<div class="row">
						{formlabel label="Subject" for="letter_subject_preview"}
						{forminput}
							<input size=50 name="letter_subject_preview" type="text" readonly value="{$action.recommended_subject|escape}" />
							{if $action.subject_editable}
								{formhelp note="Editable"}
							{else}
								{formhelp note="Not editable."}
							{/if}
						{/forminput}
					</div>
					<div class="row">
						{formlabel label="Letter Body" for="letter_content_preview"}
						{forminput}
							<textarea rows=16 cols=100 name="letter_content" readonly/>{$action.recommended_content|escape}</textarea>
							{if $action.content_editable}
								{formhelp note="Editable"}
							{else}
								{formhelp note="Not editable."}
							{/if}
						{/forminput}
					</div>
				{/if}
			</div>


		{* stupid dia wont let us to this via proxy. maybe one day.
			
			{if !$gBitUser->isRegistered() || TRUE}
			<h3>{tr}Take Action: Send the following letter to lawmakers{/tr}</h3>
			<br />
			{form enctype="multipart/form-data" id="send_letter_form"}
				{legend legend="Letter"}
				<div class="row">
					{formlabel label="Letter Subject" for="letter_subject"}
					{forminput}
						<input size=50 name="letter_subject" type="text" {if !$action.subject_editable || $preview}readonly{/if} value="{$action.suggested_subject|escape}" />
						{if $action.subject_editable}{formhelp note="You can customize the subject of your letter by editing the above text."}{/if}
					{/forminput}
				</div>
				<div class="row">
					{formlabel label="Letter Body" for="letter_content"}
					{forminput}
						<textarea rows=16 cols=100 name="letter_content" {if !$action.letter_editable || $preview}readonly{/if} />{$action.suggested_content|escape}</textarea>
						{if $action.letter_editable}
							{formhelp note="You can customize the body of your letter by editing the above text."}
						{else}
							{formhelp note="The above letter will be sent."}
						{/if}
					{/forminput}
				</div>
				{/legend}

			{if !$preview}

				<br />

				{legend legend="Your Information"}
				<div class="row">
					{formhelp note="Your signature will be added using the information you provide."} 
					{formhelp note="We don't store any of your personal information except your email address to prevent spamming."}
				</div>
				<div class="row">
					{formlabel label="First Name" for="first_name"}
					{forminput}
						<input size=50 name="first_name" type="text" value="{if $gBitUser->isRegistered()}{$gBitUser->getDisplayName()}{/if}" />
						{formhelp note=""}
					{/forminput}
				</div>
				<div class="row">
					{formlabel label="Last Name" for="last_name"}
					{forminput}
						<input size=50 name="last_name" type="text" value="{if $gBitUser->isRegistered()}{$gBitUser->getDisplayName()}{/if}" />
						{formhelp note=""}
					{/forminput}
				</div>
				<div class="row">
					{formlabel label="Email" for="email"}
					{forminput}
						<input size=50 name="email" type="text" value="{if $gBitUser->isRegistered()}{$gBitUser->getField('email')}{/if}" />
						{formhelp note=""}
					{/forminput}
				</div>
				<div class="row">
					{formlabel label="Street 1" for="street_1"}
					{forminput}
						<input size=50 name="street_1" type="text" value="" />
						{formhelp note=""}
					{/forminput}
				</div>
				<div class="row">
					{formlabel label="Street 2" for="street_2"}
					{forminput}
						<input size=50 name="street_2" type="text" value="" />
						{formhelp note=""}
					{/forminput}
				</div>
				<div class="row">
					{formlabel label="City" for="city"}
					{forminput}
						<input size=50 name="city" type="text" value="" />
						{formhelp note=""}
					{/forminput}
				</div>
				<div class="row">
					{formlabel label="State/Province" for="state"}
					{forminput}
						<select name='state'>
							<option value="">Select a state</option>
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

							<option value="AA" >Armed Forces (the) Americas</option>
							<option value="AE" >Armed Forces Europe</option>
							<option value="AP" >Armed Forces Pacific</option>
							<option value="AB" >Alberta</option>
							<option value="BC" >British Columbia</option>
							<option value="MB" >Manitoba</option>

							<option value="NF" >Newfoundland</option>
							<option value="NB" >New Brunswick</option>
							<option value="NS" >Nova Scotia</option>
							<option value="NT" >Northwest Territories</option>
							<option value="NU" >Nunavut</option>
							<option value="ON" >Ontario</option>

							<option value="PE" >Prince Edward Island</option>
							<option value="QC" >Quebec</option>
							<option value="SK" >Saskatchewan</option>
							<option value="YT" >Yukon Territory</option>
							<option value="ot" >Other</option>
						</select>
						{formhelp note=""}
					{/forminput}
				</div>
				<div class="row">
					{formlabel label="Zipcode" for="zipcode"}
					{forminput}
						<input size=50 name="zipcode" type="text" value="" />
						{formhelp note=""}
					{/forminput}
				</div>
				{/legend}
				<div class="row submit">
					<input type="submit" name="send_letter" value="{tr}Send My Letter{/tr}" />
				</div>
			{/if}
			{/form}
			{/if}
		*}
		</div> <!-- end .content -->
	</div> <!-- end .body -->
</div> <!-- end .display -->

{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='view' serviceHash=$action}
{/strip}
