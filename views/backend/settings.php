<div class="wrap">
	<form action="options.php" method="post">
		<?php screen_icon(); ?>
		<h2><?php _e('WP QRCode by Google Chart'); ?></h2>
		<h3><?php _e('Generic Settings'); ?></h3>

		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<label for="<?php self::_settings_id('size'); ?>"><?php _e('Size'); ?></label>
					</th>
					<td>
						<input type="text"
							class="small-text"
							id="<?php self::_settings_id('size'); ?>"
							name="<?php self::_settings_name('size'); ?>"
							value="<?php esc_attr_e($settings['size']); ?>" /> px 
						<p class="description"><?php _e('QRCode size. Default size is 150. You can set only numbers.'); ?></p>
					</td>
				</tr>

			</tbody>
		</table>

		<p class="submit">
			<?php settings_fields(self::SETTINGS_NAME); ?>
			<input type="submit" class="button button-primary" value="<?php _e('Save Changes'); ?>" />
		</p>
	</form>
</div>