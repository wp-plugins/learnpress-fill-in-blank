<?php

/**
 * Class LPR_Question_Type_Fill_In_Blank
 *
 * @extend LPR_Question_Type
 */
class LPR_Question_Type_Fill_In_Blank extends LPR_Question_Type{
    /**
     * Constructor
     *
     * @param null $type
     * @param null $options
     */
    function __construct( $type = null, $options = null ){
        static $loaded = false;
        parent::__construct( $type, $options );

        if( $loaded ) return;

        add_shortcode('fib', array( $this, 'shortcode' ) );
        add_action( 'learn_press_question_suggestion_fill_in_blank', array( $this, 'suggestion' ), 5, 2 );
        add_filter( 'learn_press_question_meta_box_args', array( $this, 'admin_options' ) );
        add_action( 'rwmb_before_question_settings', array( $this, 'remove_settings_field' ) );
        $loaded = true;
    }

    /**
     * Add new options to question
     *
     * @param $args
     * @return mixed
     */
    function admin_options( $args ){
        global $post;
        $args['fields']['_lpr_fill_in_blank_mark_result'] = array(
            'name'  => __( 'Mark result', 'learn_press' ),
            'id'    => "_lpr_fill_in_blank_mark_result",
            'type'  => 'radio',
            'clone' => false,
            'desc'  => 'Mark result for this question',
            'std'   => 'correct_blanks',
            'options' => array(
                'correct_blanks' => __( 'Mark is calculated by total of correct blanks', 'learn_press' ),
                'correct_all' => __( 'Requires correct all blanks', 'learn_press' )
            )
        );

        return $args;
    }

    function submit_answer( $quiz_id, $answer ){
        $questions = learn_press_get_question_answers( null, $quiz_id );
        if( !is_array( $questions ) ) $questions = array();
        $questions[$quiz_id][$this->get('ID')] = is_array( $answer ) ? reset( $answer ) : $answer;
        learn_press_save_question_answer( null, $quiz_id, $this->get('ID'), is_array( $answer ) ? reset( $answer ) : $answer);
    }

    /**
     * Replace the slots with input fields let user can type
     *
     * @param $atts
     * @param null $content
     * @return string
     */
    function shortcode( $atts, $content = null ){

        /*$atts = shortcode_atts(
            array(
                'fill' => ''
            ),
            $atts
        );*/
        return '<input class="fib-input" type="text" name="fib[]" />';
    }

    function suggestion( $ques, $answered ){
        $pattern = get_shortcode_regex();
        $passage = $ques->get_passage();
        if ( preg_match_all( '/'. $pattern .'/s', $passage, $matches )
            && array_key_exists( 2, $matches )
            && in_array( 'fib', $matches[2] )
        ){
            $shortcodes = $matches[2];
            foreach( $shortcodes as $k => $shortcode ){
                if( 'fib' != $shortcode ) continue;
                $atts = shortcode_parse_atts( $matches[3][$k] );
                $atts = shortcode_atts(
                    array(
                        'fill'  => ''
                    ),
                    $atts
                );
                if( array_key_exists( $k, $answered ) ) {
                    if( $answered[ $k ] != '' ){
                        if( $atts['fill'] == $answered[ $k ] ) {
                            $replace = '<strong class="fib-correct correct">' . $answered[$k] . '</strong>';
                        }else{
                            $replace = '<strong class="fib-wrong wrong"><i>' . $answered[$k] . '</i></strong> &nbsp;<i class="fib-fill">('.$atts['fill'].')</i>';
                        }
                    }else{
                        $replace = '.....<i class="fib-fill">('.$atts['fill'].')</i>';
                    }

                }else {
                    $replace = '.....<i class="fib-fill">('.$atts['fill'].')</i>';
                }

                $passage = preg_replace( '!' . str_replace( array('[', ']'), array('\[', '\]'), $matches[0][$k] ) . '!', $replace, $passage, 1 );
            }

        }else{
            $passage = preg_replace( '!\[fib.*\]!', '', $passage );
        }
        printf( '<div class="lpr-question-hint">%s</div>', $passage );
    }

    /**
     * Return the passage of this question
     *
     * @return string
     */
    function get_passage(){
        return get_post_meta( $this->get( 'ID' ), '_lpr_question_passage', true );
    }

    /**
     * Display admin interface
     *
     * @param array $args
     */
    function admin_interface( $args = array() ){
        $post_id = $this->get('ID');
        $this->admin_interface_head( $args );
        $editor_name = 'lpr_question[' . $post_id . '][passage]';
        $editor_id = preg_replace( array( '!(\[)|(\]\[)!', '!\]$!' ), array( '_', '' ), $editor_name );
        $passage = get_post_meta( $post_id, '_lpr_question_passage', true );
        wp_editor( $passage, $editor_id, array( 'textarea_name' => $editor_name ) );
        $this->admin_interface_foot( $args );
        $this->_admin_enqueue_script();
    }

    private function _admin_enqueue_script(){
        ob_start();
        $key = 'question_' . $this->get('ID');
        ?>
        <script type="text/javascript">
            (function() {
                var $form = $('#post');
                $form.unbind('learn_press_question_before_update.<?php echo $key;?>').bind('learn_press_question_before_update.<?php echo $key;?>', function () {
                    var $question = $('.lpr-question-fill-in-blank[data-id="<?php echo $this->get('ID');?>"]', $form);
                    var $input = $( 'textarea[id*="lpr_question_<?php echo $this->get('ID');?>_passage"]', $question );
                    if( $input.length && $input.val().length == 0 ){
                        var message = $('.lpr-question-title input', $question).val();
                        message += ": " + '<?php _e( 'Please enter passage of the question', 'learn_press' );?>';
                        window.learn_press_before_update_quiz_message.push(message);

                        return false;
                    }
                });
            })();
        </script>
        <?php
        $script = ob_get_clean();
        $script = preg_replace( '!</?script.*>!', '', $script );
        learn_press_enqueue_script( $script );
    }

    /**
     *
     */
    function remove_settings_field( $instance ){
        global $post;
        $question_settings = (get_post_meta($post->ID, '_lpr_question', true) );
        if( ! $question_settings || ( $question_settings && $question_settings['type'] != 'fill_in_blank' ) ){
            if( ! empty( $instance->meta_box['fields']['_lpr_fill_in_blank_mark_result'] ) ) {
                unset($instance->meta_box['fields']['_lpr_fill_in_blank_mark_result']);
            }
        }
    }

    /**
     * @return int
     */
    function save_post_action(){
        if( $post_id = $this->ID ){
            $post_data = isset( $_POST['lpr_question'] ) ? $_POST['lpr_question'] : array();
            $post_answers = array();
            if( isset( $post_data[$post_id] ) && $post_data = $post_data[$post_id] ){
                $post_args = array(
                    'ID'            => $post_id,
                    'post_title'    => $post_data['text'],
                    'post_type'     => 'lpr_question'
                );
                wp_update_post( $post_args );
                $passage = ! empty( $post_data['passage'] ) ? $post_data['passage'] : null;

                update_post_meta( $post_id, '_lpr_question_passage', $passage );
            }
            $post_data['answer']    = $post_answers;
            $post_data['type']      = $this->get_type();
            update_post_meta( $post_id, '_lpr_question', $post_data );
        }
        return intval( $post_id );
    }

    /**
     * Render question in front end
     *
     * @param null $args
     */
    function render( $args = null ){
        $answer = false;
        is_array( $args ) && extract( $args );

        if( $answer ) settype( $answer, 'array' );
        else $answer = array();

        $passage = get_post_meta( $this->get( 'ID' ), '_lpr_question_passage', true );
        ?>
        <div class="lp-question-wrap question-<?php echo $this->get('ID');?>">
            <h4><?php echo get_the_title( $this->get('ID') );?></h4>
            <div class="">
                <?php echo do_shortcode( $passage );?>
            </div>
        </div>
    <?php
    }

    /**
     * Check the result of the question
     *
     * @param array $a
     * @return array
     */
    function check( $a = array() ){
        $question_id = $this->get('ID');
        $question_mark = learn_press_get_question_mark( $question_id );
        $passage = get_post_meta( $question_id, '_lpr_question_passage', true );
        $pattern = get_shortcode_regex();
        $return = array(
            'fills' => array(),
            'correct' => false,
            'mark' => 0
        );

        if (   preg_match_all( '/'. $pattern .'/s', $passage, $matches )
            && array_key_exists( 2, $matches )
            && in_array( 'fib', $matches[2] )
        ){
            $shortcodes = $matches[2];
            $total_fills = sizeof( $shortcodes );
            $correct_number = 0;
            foreach( $shortcodes as $k => $shortcode ){
                if( 'fib' != $shortcode ) continue;
                $atts = shortcode_parse_atts( $matches[3][$k] );
                $atts = shortcode_atts(
                    array(
                        'fill'  => ''
                    ),
                    $atts
                );
                if( ! empty( $a['answer'] ) && array_key_exists( $k, $a['answer'] ) ){
                    if( $atts['fill'] == $a['answer'][ $k ] ){
                        $return['fills'][$k] = array($atts['fill'], $a['answer'][ $k ], true);
                        $correct_number++;
                    }else{
                        $return['fills'][$k] = array($atts['fill'], $a['answer'][ $k ], false);
                        $return['correct'] = false;
                    }
                }else{
                    $return['fills'][$k] = array( $atts['fill'], null, null );
                    $return['correct'] = false;
                }
            }
            if( $correct_number == $total_fills ){
                $return['correct'] = true;
            }
            if( 'correct_all' == get_post_meta( $question_id, '_lpr_fill_in_blank_mark_result', true ) ) {
                $return['mark'] = ($correct_number == $total_fills) ? $question_mark : 0;
            }else{
                $return['mark'] = ($correct_number / $total_fills) * $question_mark;
            }
        }
        return $return;
    }
}
// anonymous instance to register hooks immediately after add-on loaded
new LPR_Question_Type_Fill_In_Blank();