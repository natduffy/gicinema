<?php
wp_enqueue_script( 'pods-cleditor' );
wp_enqueue_style( 'pods-cleditor' );

$type = 'textarea';
$attributes = array();
$attributes[ 'tabindex' ] = 2;
$attributes = PodsForm::merge_attributes( $attributes, $name, PodsForm::$field_type, $options, 'pods-ui-field-cleditor' );
?>
<textarea<?php PodsForm::attributes( $attributes, $name, PodsForm::$field_type, $options ); ?>><?php echo esc_html( $value ); ?></textarea>
<script>
    jQuery( function ( $ ) {
        var $textarea = $( 'textarea#<?php echo $attributes[ 'id' ]; ?>' );
        var editorWidth = $textarea.outerWidth();
        $textarea.cleditor( {
            width : editorWidth
        } );
    } );
</script>
