<?php
if (!defined('ABSPATH')) { exit; }
add_action('after_setup_theme',function(){add_theme_support('title-tag');add_theme_support('responsive-embeds');add_theme_support('editor-styles');});
