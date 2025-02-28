<?php
/*
Author: Marcin Gierada
Author URI: http://www.teastudio.pl/
Author Email: m.gierada@teastudio.pl
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if (! defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

$locale = get_locale();
?>
<div class="postbox">
  <div class="inside hndle">
    <p class="inner"><?php _e('Version', 'wp-posts-carousel') ?>: <?php echo self::VERSION ?></p>
  </div>
</div>

<div class="inside-ad">
  <a href="https://coolcatideas.com" target="_blank" title="<?php _e("See more about", "categories-all-in-one"); ?> Cool Cat Ideas - purrfect-digital solutions">
    <img src="<?php echo plugins_url($locale === 'pl_PL' ? '/../images/ad-pl.png' : '/../images/ad-en.png', __FILE__) ?>" style="margin: 0 auto;display:block;width:255px;" alt="Cool Cat Ideas Software House - ad" />
  </a>
</div>