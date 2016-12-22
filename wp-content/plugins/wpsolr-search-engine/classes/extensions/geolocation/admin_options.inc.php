<?php

/**
 * Included file to display admin options
 */
global $license_manager;

WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_GEOLOCATION, true );

$extension_options_name = WPSOLR_Option::OPTION_GEOLOCATION;
$settings_fields_name   = 'extension_geolocation_opt';

$options          = get_option( $extension_options_name, array(
	'is_extension_active' => '0',
) );
$is_plugin_active = WpSolrExtensions::is_plugin_active( WpSolrExtensions::OPTION_GEOLOCATION );

?>

<div id="extension_groups-options" class="wdm-vertical-tabs-content">
	<form action="options.php" method="POST" id='extension_groups_settings_form'>
		<?php
		settings_fields( $settings_fields_name );
		?>

		<div class='wrapper'>
			<h4 class='head_div'>Geolocation</h4>

			<div class="wdm_note">
				Geolocation enhances the search with distances from the visitor location.
				<br/>A visitor can sort and filter results by their distance from his location.
				<br/>Distances can also be added to the results.
			</div>

			<div class="wdm_row">
				<div class='col_left'>
					Activate geolocation of visitors
					<?php echo WPSOLR_Help::get_help( WPSOLR_Help::HELP_GEOLOCATION ); ?>
				</div>
				<div class='col_right'>
					<input type='checkbox' <?php echo $is_plugin_active ? '' : 'readonly' ?>
					       name='<?php echo $extension_options_name; ?>[is_extension_active]'
					       value='is_extension_active'
						<?php checked( 'is_extension_active', isset( $options['is_extension_active'] ) ? $options['is_extension_active'] : '' ); ?>>

					<p>
						This will activate a javascript code on your search page that will ask your
						visitors if they authorize us to collect their coordinates.
						If yes, those coordinates will be used to localize search: sort by distance
						from the visitor's location, show distance facets from the visitor's
						location.
						Warning: some browsers (chrome, safari) require that your website uses https
						for geolocation activation.
					</p>
				</div>
				<div class="clear"></div>
			</div>

			<div class="wdm_row">
				<div class='col_left'>
					Default when no sort is selected by the user
					<?php echo WPSOLR_Help::get_help( WPSOLR_Help::HELP_GEOLOCATION ); ?>
				</div>
				<div class='col_right'>
					<select
						name='<?php echo $extension_options_name; ?>[<?php echo WPSOLR_Option::OPTION_GEOLOCATION_DEFAULT_SORT; ?>]'>
						<?php
						$current_value = WPSOLR_Global::getOption()->get_option_geolocation_default_sort();
						foreach ( OptionGeoLocation::get_sort_fields() as $sort ) {
							$selected = ( $current_value === $sort['code'] ) ? 'selected' : '';
							?>
							<option
								value="<?php echo $sort['code'] ?>" <?php echo $selected ?> ><?php echo $sort['label'] ?></option>
						<?php } ?>
						This sort is used for any geolocation search without any sort selected.
					</select>
				</div>
			</div>

			<div class="wdm_row">
				<div class='col_left'>
					Text showing distance in each search result
					<?php echo WPSOLR_Help::get_help( WPSOLR_Help::HELP_GEOLOCATION ); ?>
				</div>
				<div class='col_right'>
					<input type='text'
					       name='<?php echo $extension_options_name; ?>[<?php echo WPSOLR_Option::OPTION_GEOLOCATION_RESULT_DISTANCE_LABEL; ?>]'
					       placeholder="%1$s: %2$s"
						   value="<?php echo( ! empty( $options[ WPSOLR_Option::OPTION_GEOLOCATION_RESULT_DISTANCE_LABEL ] ) ? $options[ WPSOLR_Option::OPTION_GEOLOCATION_RESULT_DISTANCE_LABEL ] : '' ); ?>">

					<p>
						Leave empty if you do not wish to show distance.
						%1$s is replaced by the field label.
						%2$s is replaced by the distance.
						Use your theme/plugin .mo files or WPML string module to localize it.
					</p>

				</div>
				<div class="clear"></div>
			</div>

			<div class="wdm_row">
				<div class='col_left'>
					Attach the geo localization js to your own search form(s)
					<?php echo WPSOLR_Help::get_help( WPSOLR_Help::HELP_JQUERY_SELECTOR ); ?>
				</div>
				<div class='col_right'>
					<input type='text'
					       name='<?php echo $extension_options_name; ?>[<?php echo WPSOLR_Option::OPTION_GEOLOCATION_JQUERY_SELECTOR; ?>]'
					       placeholder=".search_box1, #search_box2"
						   value="<?php echo( ! empty( $options[ WPSOLR_Option::OPTION_GEOLOCATION_JQUERY_SELECTOR ] ) ? $options[ WPSOLR_Option::OPTION_GEOLOCATION_JQUERY_SELECTOR ] : '' ); ?>">

					<p>
						Enter a jQuery selector for your search box(es). The javascript will
						collect
						the visitor localization precisely when the search is submitted from the
						search box(es).

						Also, the submitted form is added the css class "wpsolr_geo_loading" while the browser is retrieving the visitor location. You can use this class to show some loading icon for instance.
					</p>

				</div>
				<div class="clear"></div>
			</div>

			<div class="wdm_row">
				<div class='col_left'>
					Add a user agreement checkbox on search form(s)
					<?php echo WPSOLR_Help::get_help( WPSOLR_Help::HELP_GEOLOCATION ); ?>
				</div>
				<div class='col_right'>
					<input type='checkbox'
					       name='<?php echo $extension_options_name; ?>[<?php echo WPSOLR_Option::OPTION_GEOLOCATION_IS_SHOW_USER_AGREEMENT_AJAX; ?>]'
					       value='1'
						<?php checked( '1', isset( $options[ WPSOLR_Option::OPTION_GEOLOCATION_IS_SHOW_USER_AGREEMENT_AJAX ] ) ? $options[ WPSOLR_Option::OPTION_GEOLOCATION_IS_SHOW_USER_AGREEMENT_AJAX ] : '?' ); ?>>

					<p>
						If you select this option, a checkbox will be added to the search form(s), wpsolr Ajax and your
						search form(s) defined above,
						asking the user to confirm his agreement to use his geolocation.
						If you do not select this option, the geolocation will be used without
						consent.
						(but the browser will ask anyway in both cases).
					</p>

					<input type='checkbox'
					       name='<?php echo $extension_options_name; ?>[<?php echo WPSOLR_Option::OPTION_GEOLOCATION_IS_SHOW_USER_AGREEMENT_AJAX_IS_DEFAULT_YES; ?>]'
					       value='1'
						<?php checked( '1', isset( $options[ WPSOLR_Option::OPTION_GEOLOCATION_IS_SHOW_USER_AGREEMENT_AJAX_IS_DEFAULT_YES ] ) ? $options[ WPSOLR_Option::OPTION_GEOLOCATION_IS_SHOW_USER_AGREEMENT_AJAX_IS_DEFAULT_YES ] : '?' ); ?>>

					The user agreement checkbox is preselected<br/><br/>

					<input type='text'
					       name='<?php echo $extension_options_name; ?>[<?php echo WPSOLR_Option::OPTION_GEOLOCATION_USER_AGREEMENT_LABEL; ?>]'
					       placeholder="<?php echo OptionLocalization::get_term( OptionLocalization::get_options(), 'geolocation_ask_user' ); ?>"
					       value="<?php echo( ! empty( $options[ WPSOLR_Option::OPTION_GEOLOCATION_USER_AGREEMENT_LABEL ] ) ? $options[ WPSOLR_Option::OPTION_GEOLOCATION_USER_AGREEMENT_LABEL ] : '' ); ?>">

					<p>
						Text of the checkbox. Will be shown on the front-end (and translated in WPML/POLYLANG string
						modules if not empty. Else will show the localization translation).
					</p>

				</div>
				<div class="clear"></div>
			</div>

			<div class="wdm_row">
				<div class='col_left'>
					Filter out results with no coordinates
					<?php echo WPSOLR_Help::get_help( WPSOLR_Help::HELP_GEOLOCATION ); ?>
				</div>
				<div class='col_right'>
					<input type='checkbox'
					       name='<?php echo $extension_options_name; ?>[<?php echo WPSOLR_Option::OPTION_GEOLOCATION_IS_FILTER_EMPTY_COORDINATES; ?>]'
					       value='1'
						<?php checked( '1', isset( $options[ WPSOLR_Option::OPTION_GEOLOCATION_IS_FILTER_EMPTY_COORDINATES ] ) ? $options[ WPSOLR_Option::OPTION_GEOLOCATION_IS_FILTER_EMPTY_COORDINATES ] : '?' ); ?>>

					<p>
						If some of your post types does not contain geolocation coordinates, this option will filter them out from the results. It prevents showing messy wrong distances of 1000s of kilometers.
					</p>

				</div>
				<div class="clear"></div>
			</div>

			<div class='wdm_row'>
				<div class="submit">
					<?php if ( $license_manager->get_license_is_activated( OptionLicenses::LICENSE_PACKAGE_GEOLOCATION ) ) { ?>
						<div class="wpsolr_premium_block_class">
							<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_GEOLOCATION, OptionLicenses::TEXT_LICENSE_ACTIVATED, true, true ); ?>
						</div>
						<input
							name="save_selected_options_res_form"
							id="save_selected_extension_groups_form" type="submit"
							class="button-primary wdm-save"
							value="Save Options"/>
					<?php } else { ?>
						<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_GEOLOCATION, 'Save Options', true, true ); ?>
						<br/>
					<?php } ?>
				</div>
			</div>
		</div>

	</form>
</div>