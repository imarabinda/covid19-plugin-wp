<?php
	/**
	 * Run when the plugin is activated.
	 *
	 * @static
	 * @since 2.0.0
	 **/
	 function on_activation() {

		/** Security checks. */
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
		check_admin_referer( "activate-plugin_{$plugin}" );

		/** Send install Action to our host. */

	}
	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @return void
	 **/
	function __clone() {
		/** Cloning instances of the class is forbidden. */
		_doing_it_wrong( __FUNCTION__, esc_html__( 'The whole idea of the singleton design pattern is that there is a single object therefore, we don\'t want the object to be cloned.', 'covid' ));
	}
	?>
<div class="wrap act_022fz7">
    <div id="covid-plugin-activation">
        <h3><?php esc_html_e('Enter your Envato purchase code to activate', 'covid');?></h3>
        <h4><?php esc_html_e('ContactHunter WordPress Button', 'covid');?></h4>
        <h5><?php esc_html_e('Activating...', 'covid')?></h5>
        <form id="covid-license-form" action="#" method="post">
            <input type="hidden" name="chwp_license[user]" value="<?php echo esc_url(site_url()); ?>">
		  <input type="hidden" name="chwp_license[i]" value="<?php echo htmlentities ($_SERVER['HTTP_CLIENT_IP']); ?>">
            <input type="text" name="chwp_license[key]" placeholder="<?php esc_attr_e('Enter Purchase Code', 'covid');?>" required>
            <input type="submit" name="chwp_license_submit" value="<?php esc_attr_e('Activate', 'covid');?>">
        </form>
        <a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code" title="<?php esc_html_e('Where is my purchase code?', 'covid')?>" target="_blank">
          <?php esc_html_e('Where is my purchase code?', 'covid')?>
        </a>
        <a href="https://1.envato.market/PM" title="<?php esc_html_e('Facing issue while activating?', 'covid')?>" target="_blank">
            <?php esc_html_e('Facing issue while activating?', 'covid')?>
        </a><div id="covid-zfpv1o8r5i77bk6xt7zmji39k"></div>
    </div>
</div>