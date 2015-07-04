<?php
class LPR_Fill_In_Blank{
    function __construct(){
        require_once( 'class-lpr-question-type-fill-in-blank.php' );
        add_action( 'wp_enqueue_scripts', array( $this, 'load_assets' ) );
        add_filter( 'lpr_question_types', array( $this, 'add_new_question_type' ) );
    }

    function load_assets(){
        wp_enqueue_style( 'fib', plugins_url( 'assets/style.css', LPR_FILL_IN_BLANK_FILE ) );
    }

    function add_new_question_type( $types ){
        $types[] = 'fill_in_blank';
        return $types;
    }
}
new LPR_Fill_In_Blank();