<?php
/*
Author: Marcin Gierada
Author URI: https://coolcatideas.com/
Author Email: info@coolcatideas.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

<style>
	#wpc-admin-bootstrap {
		background: #f5f7f1;
		color: #1f2933;
		display: grid;
		font-size: 14px;
		line-height: 1.45;
		margin-left: -20px;
		min-height: calc(100vh - 32px);
		place-items: center;
	}

	#wpc-admin-bootstrap .cci-wpc-admin-bootstrap-loader {
		align-items: center;
		display: grid;
		gap: 12px;
		justify-items: center;
		padding: 24px;
		text-align: center;
	}

	#wpc-admin-bootstrap .cci-wpc-admin-bootstrap-icon {
		align-items: center;
		background: #f0ecff;
		border: 1px solid #d8ccff;
		border-radius: 7px;
		color: #6d4aff;
		display: inline-flex;
		height: 40px;
		justify-content: center;
		width: 40px;
	}

	#wpc-admin-bootstrap .cci-wpc-admin-bootstrap-spinner {
		animation: wpc-admin-bootstrap-spin 0.85s linear infinite;
		border: 2px solid currentColor;
		border-right-color: transparent;
		border-radius: 999px;
		height: 20px;
		width: 20px;
	}

	#wpc-admin-bootstrap .cci-wpc-admin-bootstrap-label {
		color: #64748b;
		font-weight: 600;
	}

	@keyframes wpc-admin-bootstrap-spin {
		to {
			transform: rotate(360deg);
		}
	}
</style>

<div id="wp-posts-carousel-admin-v2">
	<div id="wpc-admin-bootstrap" role="status" aria-live="polite">
		<div class="cci-wpc-admin-bootstrap-loader">
			<span class="cci-wpc-admin-bootstrap-icon" aria-hidden="true">
				<span class="cci-wpc-admin-bootstrap-spinner"></span>
			</span>
			<span class="cci-wpc-admin-bootstrap-label">
				<?php esc_html_e( 'Loading dashboard...', 'wp-posts-carousel' ); ?>
			</span>
		</div>
	</div>
</div>
