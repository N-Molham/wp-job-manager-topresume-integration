<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 24-Jun-17
 * Time: 9:57 PM
 */
wp_enqueue_script( 'jmfe-date-field' );
wp_enqueue_style( 'jquery-ui' );

$is_repeatable_field = false !== strpos( $field['name'], '%%repeated-row-index%%' ) || 0 !== preg_match( '/_\d+$/', $field['name'] );
?>
<input type="text" class="jmfe-date-picker input-text"
	   name="<?php echo esc_attr( isset( $field['name'] ) ? $field['name'] : $key ); ?>"
	   id="<?php echo esc_attr( $is_repeatable_field ? $field['name'] : $key ); ?>"
	   placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
	   value="<?php echo isset( $field['value'] ) ? esc_attr( $field['value'] ) : ''; ?>"
	   maxlength="<?php echo empty( $field['maxlength'] ) ? '' : $field['maxlength']; ?>" />

<?php if ( ! empty( $field['description'] ) ) : ?>
	<span class="description">
		<small class="description"><?php echo $field['description']; ?></small>
	</span>
<?php endif; ?>
