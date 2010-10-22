<DIV class="panel_headline"><?php echo $headline;?></DIV>
<div id="panel">
<fieldset>

	<legend>Site Profile</legend>

	<form method="POST">
	
	<table class="management" style="width:auto;">
		<?php if ($edit == true):?>
		<TR>
			<TH>Site ID:</TH>
			<TD><?php $this->out( $site['site_id'] );?></TD>
			<input type="hidden" name="<?php echo $this->getNs();?>site_id" value="<?php $this->out( $site['site_id'] );?>">

		</TR>
		<?php endif;?>
		<TR>
			<TH>Domain:</TH>
			<?php if ($edit == true):?>
			<input type="hidden" name="<?php echo $this->getNs();?>domain" value="<?php $this->out( $site['domain'] );?>">
			<TD><?php $this->out( $site['domain'] );?></TD>
			<?php else:?>
			<TD>
				
				<select name="<?php echo $this->getNs();?>protocol">
					<option value="http://">http://</option>
				    <option value="https://">https://</option>
				</select>
  
				<input type="text" name="<?php echo $this->getNs();?>domain" size="52" maxlength="70" value="<?php $this->out( $site['domain'] );?>"><BR>
				<span class="validation_error"><?php $this->out( $validation_errors['domain'] );?></span>
			</TD>
			<?php endif;?>
		</TR>
		<TR>
			<TH>Site Name:</TH>
			<TD><input type="text" name="<?php echo $this->getNs();?>name" size="52" maxlength="70" value="<?php $this->out( $site['name'] );?>"></TD>
		</TR>
		<TR>
			<TH>Description:</TH>
			<TD>
				<textarea name="<?php echo $this->getNs();?>description" cols="52" rows="3"><?php $this->out( $site['description'] );?></textarea>
			</TD>
		</TR>
	</table>
	<BR>
	<?php echo $this->createNonceFormField($action);?>
	<input type="hidden" name="<?php echo $this->getNs();?>action" value="<?php $this->out( $action, false );?>">
	<input type="submit" name="<?php echo $this->getNs();?>submit_btn" value="Save Profile">
	
	</form>
	
</fieldset>


<form method="post" name="owa_options">

	<fieldset name="owa-options" class="options">
	<legend>Site Settings</legend>
			
		<div class="setting" id="p3p_policy">	
			<div class="title">P3P Compact Privacy Policy</div>
			<div class="description">This setting controls the P3P compact privacy policy that is returned to the browser when OWA sets cookies. Click <a href="http://www.p3pwriter.com/LRN_111.asp">here</a> for more information on compact privacy policies and choosing the right one for your web site.</div>
			<div class="field"><input type="text" size="50" name="<?php echo $this->getNs();?>config[p3p_policy]" value="<?php $this->out( $config['p3p_policy'] );?>"></div>
		</div>
		
		<div class="setting" id="url_params">	
			<div class="title">URL Parameters</div>
			<div class="description">This setting controls the URL parameters that OWA should ignore when processing requests. This is useful for avoiding duplicate URLs due to the use of tracking or others state parameters in your URLs. Parameter names should be separated by comma.</div>
			<div class="field"><input type="text" size="50" name="<?php echo $this->getNs();?>config[query_string_filters]" value="<?php $this->out( $config['query_string_filters'] );?>"></div>
		</div>
	
		<div class="setting" id="google_maps_api_key">
			<div class="title">Google Maps API Key</div>
			<div class="description">Google maps API key is needed to produce Google maps of visitor geo-locations. You may obtain an API key from <a href="http://www.google.com/apis/maps/signup.html">this Google web site</a> for free.</div>
			<div class="field"><input type="text" size="90" name="<?php echo $this->getNs();?>config[google_maps_api_key]" value="<?php $this->out( $config['google_maps_api_key'] );?>"></div>
		</div>
    			
		<div class="setting" id="ecommerce_reporting">	
			<div class="title">e-commerce Reporting</div>
			<div class="description">Adds e-commerce metrics/statistics to reports.</div>
			<div class="field">
				<select name="<?php echo $this->getNs();?>config[enableEcommerceReporting]">
					<option value="0" <?php if ( ! $this->getValue( 'enableEcommerceReporting', $config ) ):?>SELECTED<?php endif;?>>Off</option>
					<option value="1" <?php if ( $this->getValue( 'enableEcommerceReporting', $config ) ):?>SELECTED<?php endif;?>>On</option>
				</select>
			</div>
		</div>

		<BR>
		
		<?php echo $this->createNonceFormField('base.sitesEditSettings');?>
		<input type="hidden" name="<?php echo $this->getNs();?>site_id" value="<?php $this->out( $site['site_id'] );?>">
		<input type="hidden" name="<?php echo $this->getNs();?>module" value="base">
		<input type="hidden" name="<?php echo $this->getNs();?>action" value="base.sitesEditSettings">
		<input type="submit" name="<?php echo $this->getNs();?>submit_btn" value="Save Settings">
	</fieldset>
</form>
</div>