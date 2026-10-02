<?php
if (!defined('ABSPATH')) { exit; }
$lang=\OberHub\Frontend::lang();
?><!doctype html><html lang="<?php echo esc_attr($lang==='de' ? 'de-CH' : $lang); ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head><body><?php wp_body_open(); echo \OberHub\Frontend::render(); wp_footer(); ?></body></html>
