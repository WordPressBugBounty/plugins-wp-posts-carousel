<?php
defined( 'ABSPATH' ) || exit;
/** @var array $args Loading context supplied by the carousel renderer. */
for ( $index = 0; $index < $args['card_count']; $index++ ) :
	?>
	<div class="cci-wpc-skeleton-card">
		<div class="cci-wpc-skeleton-image"></div>
		<div class="cci-wpc-skeleton-line"></div>
		<div class="cci-wpc-skeleton-line cci-wpc-skeleton-line-short"></div>
	</div>
<?php endfor; ?>
