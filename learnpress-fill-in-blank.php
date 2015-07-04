<?php
/*
Plugin Name: LearnPress Fill In Blank
Plugin URI: http://thimpress.com/learnpress
Description: Supports type of question Fill In Blank lets user fill out the text into one ( or more than one ) space
Author: thimpress
Version: 0.9.0
Author URI: http://thimpress.com
Tags: learnpress
*/
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( ! defined( 'LPR_FILL_IN_BLANK_PATH' ) ) {
    define( 'LPR_FILL_IN_BLANK_FILE', __FILE__ );
    define( 'LPR_FILL_IN_BLANK_PATH', dirname(__FILE__) );
}
/**
 * Register entry point for addon
 */
function learn_press_register_fill_in_blank_add_on() {
    require_once( LPR_FILL_IN_BLANK_PATH . '/includes/class-fill-in-blank.php' );
}
add_action( 'learn_press_register_add_ons', 'learn_press_register_fill_in_blank_add_on' );
