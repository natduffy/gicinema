<?php
/**
 * Handles code field data and operations
 *
 * @package Pods\Fields
 */
class PodsField_Code extends PodsField {

    /**
     * Field Type Group
     *
     * @var string
     * @since 2.0.0
     */
    public static $group = 'Paragraph';

    /**
     * Field Type Identifier
     *
     * @var string
     * @since 2.0.0
     */
    public static $type = 'code';

    /**
     * Field Type Label
     *
     * @var string
     * @since 2.0.0
     */
    public static $label = 'Code (Syntax Highlighting)';

    /**
     * Field Type Preparation
     *
     * @var string
     * @since 2.0.0
     */
    public static $prepare = '%s';

    /**
     * Do things like register/enqueue scripts and stylesheets
     *
     * @since 2.0.0
     */
    public function __construct () {

    }

    /**
     * Add options and set defaults to
     *
     * @return array
     * @since 2.0.0
     */
    public function options () {
        $options = array(
            'output_options' => array(
                'label' => __( 'Output Options', 'pods' ),
                'group' => array(
                    'code_allow_shortcode' => array(
                        'label' => __( 'Allow Shortcodes?', 'pods' ),
                        'default' => 0,
                        'type' => 'boolean',
                        'dependency' => true
                    )
                )
            ),
            'code_max_length' => array(
                'label' => __( 'Maximum Length', 'pods' ),
                'default' => 0,
                'type' => 'number'
            )/*,
            'code_size' => array(
                'label' => __( 'Field Size', 'pods' ),
                'default' => 'medium',
                'type' => 'pick',
                'data' => array(
                    'small' => __( 'Small', 'pods' ),
                    'medium' => __( 'Medium', 'pods' ),
                    'large' => __( 'Large', 'pods' )
                )
            )*/
        );

        return $options;
    }

    /**
     * Define the current field's schema for DB table storage
     *
     * @param array $options
     *
     * @return array
     * @since 2.0.0
     */
    public function schema ( $options ) {
        $schema = 'LONGTEXT';

        return $schema;
    }

    /**
     * Change the way the value of the field is displayed with Pods::get
     *
     * @param mixed $value
     * @param string $name
     * @param array $options
     * @param array $fields
     * @param array $pod
     * @param int $id
     *
     * @since 2.0.0
     */
    public function display ( $value = null, $name = null, $options = null, $pod = null, $id = null ) {
        if ( 1 == pods_var( 'code_allow_shortcode', $options, 0 ) )
            $value = do_shortcode( $value );

        return $value;
    }

    /**
     * Customize output of the form field
     *
     * @param string $name
     * @param mixed $value
     * @param array $options
     * @param array $pod
     * @param int $id
     *
     * @since 2.0.0
     */
    public function input ( $name, $value = null, $options = null, $pod = null, $id = null ) {
        $options = (array) $options;

        if ( is_array( $value ) )
            $value = implode( "\n", $value );

        $field_type = 'codemirror';

        do_action( 'pods_form_ui_field_code_' . $field_type, $name, $value, $options, $pod, $id );
        do_action( 'pods_form_ui_field_code', $field_type, $name, $value, $options, $pod, $id );

        pods_view( PODS_DIR . 'ui/fields/' . $field_type . '.php', compact( array_keys( get_defined_vars() ) ) );
    }

     /**
     * Change the value or perform actions after validation but before saving to the DB
     *
     * @param mixed $value
     * @param int $id
     * @param string $name
     * @param array $options
     * @param array $fields
     * @param array $pod
     * @param object $params
     *
     * @since 2.0.0
     */
    public function pre_save ( $value, $id = null, $name = null, $options = null, $fields = null, $pod = null, $params = null ) {
        $value = esc_attr( $value );

        return $value;
    }
}
