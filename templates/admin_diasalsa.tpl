{strip}
{if $errorMsg || $successMsg}
	{formfeedback success=$successMsg error=$errorMsg}
{/if}
{form}
	<input type="hidden" name="page" value="{$page}" />
	<div class="row">
		{formlabel label="DIA Salsa Organization Key" for=diasalsa_organization_key}
		{forminput}
			<input type="text" name="diasalsa_organization_key" size="50" value="{if $gBitSystem->getConfig('diasalsa_organization_key')}{$gBitSystem->getConfig('diasalsa_organization_key')}{/if}" />
			{formhelp note=''}
		{/forminput}
	</div>
	<div class="row">
		{formlabel label="DIA Salsa Admin Email Address" for=diasalsa_admin_email}
		{forminput}
			<input type="text" name="diasalsa_admin_email" size="50" value="{if $gBitSystem->getConfig('diasalsa_admin_email')}{$gBitSystem->getConfig('diasalsa_admin_email')}{/if}" />
			{formhelp note=''}
		{/forminput}
	</div>
	<div class="row">
		{formlabel label="DIA Salsa Admin Password" for=diasalsa_admin_password}
		{forminput}
			<input type="password" name="diasalsa_admin_password" size="50" value="{if $gBitSystem->getConfig('diasalsa_admin_password')}{$gBitSystem->getConfig('diasalsa_admin_password')}{/if}" />
			{formhelp note=''}
		{/forminput}
	</div>
	<div class="row">
		{formlabel label="Confirm Password" for=diasalsa_admin_password}
		{forminput}
			<input type="password" name="diasalsa_admin_password_confirm" size="50" value="" />
			{formhelp note='Please enter the password again'}
		{/forminput}
	</div>
	<div class="row submit">
	<div class="row submit">
		<input type="submit" name="diasalsa_preferences" value="{tr}Change Preferences{/tr}" />
	</div>
{/form}
{/strip}
