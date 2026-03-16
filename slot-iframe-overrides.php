<?php
/**
 * Plugin Name: Slot Iframe Overrides
 * Description: Замена урла поста на свой iframe.
 * Author: Vlad
 * Version: 1.0.0
 * Text Domain: slot-iframe-overrides
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Auto-updates via Plugin Update Checker (GitHub).
if ( file_exists( plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php' ) ) {
    require plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php';

    if ( class_exists( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
        $slot_iframe_overrides_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/CruentoVulpes/slot-iframe-overrides/',
            __FILE__,
            'slot-iframe-overrides'
        );

        $slot_iframe_overrides_update_checker->setBranch( 'main' );
    }
}

function sio_register_meta() {
	register_post_meta(
		'post',
		'sio_iframe_override_url',
		[
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'esc_url_raw',
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		]
	);
}
add_action( 'init', 'sio_register_meta' );

function sio_add_meta_box() {
	add_meta_box(
		'sio_iframe_override_meta',
		__( 'Slot iframe override', 'slot-iframe-overrides' ),
		'sio_render_meta_box',
		'post',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'sio_add_meta_box' );

function sio_render_meta_box( $post ) {
	wp_nonce_field( 'sio_save_meta', 'sio_meta_nonce' );

	$value = get_post_meta( $post->ID, 'sio_iframe_override_url', true );
	?>
	<p>
		<label for="sio_iframe_override_url">
			<strong><?php esc_html_e( 'Override slot iframe URL', 'slot-iframe-overrides' ); ?></strong>
		</label>
	</p>
	<p>
		<input
			type="url"
			id="sio_iframe_override_url"
			name="sio_iframe_override_url"
			class="widefat"
			value="<?php echo esc_attr( $value ); ?>"
			placeholder="https://..."
		/>
	</p>
	<p class="description">
		<?php esc_html_e( 'If set, frontend script will replace iframe src with this URL after service import is done.', 'slot-iframe-overrides' ); ?>
	</p>
	<?php
}

function sio_save_meta( $post_id ) {
	if ( ! isset( $_POST['sio_meta_nonce'] ) || ! wp_verify_nonce( $_POST['sio_meta_nonce'], 'sio_save_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( isset( $_POST['post_type'] ) && 'post' === $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	if ( isset( $_POST['sio_iframe_override_url'] ) ) {
		$raw   = wp_unslash( $_POST['sio_iframe_override_url'] );
		$value = esc_url_raw( $raw );

		if ( ! empty( $value ) ) {
			update_post_meta( $post_id, 'sio_iframe_override_url', $value );
		} else {
			delete_post_meta( $post_id, 'sio_iframe_override_url' );
		}
	}
}
add_action( 'save_post', 'sio_save_meta' );


function sio_enqueue_front_script() {
	if ( ! is_single() ) {
		return;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return;
	}

	$override = get_post_meta( $post_id, 'sio_iframe_override_url', true );
	if ( empty( $override ) ) {
		return;
	}

	$handle = 'sio-iframe-overrides';

	wp_enqueue_script(
		$handle,
		plugins_url( 'assets/js/slot-iframe-overrides.js', __FILE__ ),
		[ 'jquery' ],
		'1.0.0',
		true
	);

	wp_localize_script(
		$handle,
		'SioIframeOverrides',
		[
			'iframeUrl' => esc_url( $override ),
		]
	);
}
add_action( 'wp_enqueue_scripts', 'sio_enqueue_front_script', 100 );

