<?php
/**
 * Widget class.
 *
 * @since 1.0.0
 *
 * @package Soliloquy
 * @author  Thomas Griffin
 */
class Soliloquy_Widget extends WP_Widget {

    /**
     * Holds the class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;

    /**
     * Path to the file.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $file = __FILE__;

    /**
     * Holds the base class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public $base;

    /**
     * Constructor. Sets up and creates the widget with appropriate settings.
     *
     * @since 1.0.0
     */
    public function __construct() {

        // Load the base class object.
        $this->base = Soliloquy::get_instance();

		// Widget options
		$widget_ops = array(
            'classname'   => 'soliloquy',
            'description' => __( 'Place a Soliloquy slider into a widgetized area.', 'soliloquy' )
        );
        $widget_ops = apply_filters( 'soliloquy_widget_ops', $widget_ops );

		// Control options
		$control_ops = array(
            'id_base' => 'soliloquy',
            'height'  => 350,
            'width'   => 225
        );
        $control_ops = apply_filters( 'soliloquy_widget_control_ops', $control_ops );

        $this->WP_Widget( 'soliloquy', apply_filters( 'soliloquy_widget_name', __( 'Soliloquy', 'soliloquy' ) ), $widget_ops, $control_ops );

    }

    /**
     * Outputs the widget within the widgetized area.
     *
     * @since 1.0.0
     *
     * @param array $args     The default widget arguments.
     * @param array $instance The input settings for the current widget instance.
     */
    public function widget( $args, $instance ) {

        // Extract arguments into variables.
        extract( $args );

        $title      = apply_filters( 'widget_title', $instance['title'] );
        $slider_id = $instance['soliloquy_id'];

        do_action( 'soliloquy_widget_before_output', $args, $instance );

        echo $before_widget;

        do_action( 'soliloquy_widget_before_title', $args, $instance );

        // If a title exists, output it.
        if ( $title ) {
            echo $before_title . $title . $after_title;
        }

        do_action( 'soliloquy_widget_before_slider', $args, $instance );

        // If a slider has been selected, output it.
        if ( $slider_id ) {
            soliloquy( $slider_id );
        }

        do_action( 'soliloquy_widget_after_slider', $args, $instance );

        echo $after_widget;

        do_action( 'soliloquy_widget_after_output', $args, $instance );

    }

    /**
     * Sanitizes and updates the widget.
     *
     * @since 1.0.0
     *
     * @param array $new_instance The new input settings for the current widget instance.
     * @param array $old_instance The old input settings for the current widget instance.
     */
    public function update( $new_instance, $old_instance ) {

        // Set $instance to the old instance in case no new settings have been updated for a particular field.
        $instance = $old_instance;

        // Sanitize user inputs.
        $instance['title']        = trim( $new_instance['title'] );
        $instance['soliloquy_id'] = absint( $new_instance['soliloquy_id'] );

        return apply_filters( 'soliloquy_widget_update_instance', $instance, $new_instance );

    }

    /**
     * Outputs the widget form where the user can specify settings.
     *
     * @since 1.0.0
     *
     * @param array $instance The input settings for the current widget instance.
     */
    public function form( $instance ) {

        // Get all avilable sliders and widget properties.
        $sliders   = Soliloquy::get_instance()->get_sliders();
        $title     = isset( $instance['title'] ) ? $instance['title'] : '';
        $slider_id = isset( $instance['soliloquy_id'] ) ? $instance['soliloquy_id'] : false;

        do_action( 'soliloquy_widget_before_form', $instance );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'soliloquy' ); ?></label>
            <input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%;" />
        </p>
        <?php do_action( 'soliloquy_widget_middle_form', $instance ); ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'soliloquy_id' ); ?>"><?php _e( 'Slider', 'soliloquy' ); ?></label>
            <select id="<?php echo esc_attr( $this->get_field_id( 'soliloquy_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'soliloquy_id' ) ); ?>" style="width: 100%;">
                <?php
                foreach ( $sliders as $slider ) {
                    if ( ! empty( $slider['config']['title'] ) ) {
                        $title = $slider['config']['title'];
                    } else if ( ! empty( $slider['config']['slug'] ) ) {
                        $title = $slider['config']['title'];
                    } else {
                        $title = sprintf( __( 'Slider ID #%s', 'soliloquy' ), $slider['id'] );
                    }

                    echo '<option value="' . $slider['id'] . '"' . selected( $slider['id'], $slider_id, false ) . '>' . $title . '</option>';
                }
                ?>
            </select>
        </p>
        <?php
        do_action( 'soliloquy_widget_after_form', $instance );

    }

}