<?php
/**
 * Plugin Name: VK Toret Fakturoid Extension
 * Description: Extension that adds custom note and due to the Fakturoid invoices
 * Version: 1.0.0
 * Author: Daniel Koch
 * Author URI: https://vesmirnekure.cz/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Exit if WooCommerce is not active.
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || ! in_array( 'woo-fakturoid/woo-fakturoid.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

class VKToretFakturoidExtension {
	/**
	 * Constructor
	 */
	public function __construct() {
		// Plugin initialization tasks, such as loading text domain, adding actions and filters, etc.
		$this->actions();
	}

	/**
	 * List all actions of the plugin
	 *
	 * @return void
	 */
	private function actions() {
		add_action( 'admin_menu', array( $this, 'addAdminMenu' ) );
		add_action( 'admin_init', array( $this, 'settingsInit' ) );
		add_action( 'show_user_profile', array( $this, 'addCustomFieldsToUserProfile' ) );
		add_action( 'edit_user_profile', array( $this, 'addCustomFieldsToUserProfile' ) );
		add_action( 'personal_options_update', array( $this, 'saveCustomFieldInUserProfile' ) );
		add_action( 'edit_user_profile_update', array( $this, 'saveCustomFieldInUserProfile' ) );
	}

	/**
	 * Add admin menu page
	 */
	public function addAdminMenu() {
		add_submenu_page(
			'woocommerce',
			'Fakturoid Extension',
			'Fakturoid Extension',
			'manage_options',
			'vktoret-fakturoid-extension',
			array( $this, 'adminPageCallback' )
		);
	}

	public function adminPageCallback() {
		// Render the form
		echo '<div class="wrap">';
		echo '<h1>' . __( 'Fakturoid Extension Settings', 'vk' ) . '</h1>';
		echo '<form method="post" action="options.php">';
		settings_fields( 'vk_toret_fakturoid_extension' );
		do_settings_sections( 'vk_toret_fakturoid_extension' );

		// Submit button
		echo '<input type="submit" class="button-primary" value="' . __( 'Save Settings', 'vk' ) . '">';
		echo '</form>';
		echo '</div>';
	}

	public function settingsInit() {
		// Register settings
		register_setting( 'vk_toret_fakturoid_extension', 'vk_toret_fakturoid_extension_due' );
		register_setting( 'vk_toret_fakturoid_extension', 'vk_toret_fakturoid_extension_note' );
		register_setting( 'vk_toret_fakturoid_extension', 'vk_toret_fakturoid_extension_note_checkout' );
		register_setting( 'vk_toret_fakturoid_extension', 'vk_toret_fakturoid_extension_note_checkout_fields', array(
			$this,
			'sanitizeCallback'
		) );

		// Add settings sections
		add_settings_section( 'vk_toret_fakturoid_extension_section_general', 'General Settings', array(
			$this,
			'sectionGeneralCallback'
		), 'vk_toret_fakturoid_extension' );

		// Add settings fields
		add_settings_field( 'vk_toret_fakturoid_extension_field_due', 'Enable custom due date', array(
			$this,
			'fieldDueCallback'
		), 'vk_toret_fakturoid_extension', 'vk_toret_fakturoid_extension_section_general' );
		add_settings_field( 'vk_toret_fakturoid_extension_field_note', 'Enable custom note', array(
			$this,
			'fieldNoteCallback'
		), 'vk_toret_fakturoid_extension', 'vk_toret_fakturoid_extension_section_general' );
		add_settings_field( 'vk_toret_fakturoid_extension_field_note_checkout', 'Enable custom note in checkout', array(
			$this,
			'fieldNoteCheckoutCallback'
		), 'vk_toret_fakturoid_extension', 'vk_toret_fakturoid_extension_section_general' );

		add_settings_field( 'vk_toret_fakturoid_extension_field_note_checkout_user_roles', 'User roles for note in checkout field', array(
			$this,
			'fieldNoteCheckoutUserRoleCallback'
		), 'vk_toret_fakturoid_extension', 'vk_toret_fakturoid_extension_section_general' );
	}

	public function sectionGeneralCallback() {
		// Render the section description if needed
		echo '<p>' . __( 'General settings for Fakturoid Extension', 'vk' ) . '</p>';
	}

	public function fieldDueCallback() {
		// Get the current value of the custom due date option
		$enableCustomDueDate = get_option( 'vk_toret_fakturoid_extension_due', false );

		// Render the custom due date field
		echo '<input type="checkbox" id="enable_custom_due_date" name="vk_toret_fakturoid_extension_due" value="1" ' . checked( $enableCustomDueDate, true, false ) . '>';
	}

	public function fieldNoteCallback() {
		// Get the current value of the custom note option
		$enableCustomNote = get_option( 'vk_toret_fakturoid_extension_note', false );

		// Render the custom note field
		echo '<input type="checkbox" id="enable_custom_note" name="vk_toret_fakturoid_extension_note" value="1" ' . checked( $enableCustomNote, true, false ) . '>';
	}

	public function fieldNoteCheckoutCallback() {
		// Get the current value of the custom note in checkout option
		$enableCustomNoteInCheckout = get_option( 'vk_toret_fakturoid_extension_note_checkout', false );

		// Render the custom note in checkout field
		echo '<input type="checkbox" id="enable_custom_note_in_checkout" name="vk_toret_fakturoid_extension_note_checkout" value="1" ' . checked( $enableCustomNoteInCheckout, true, false ) . '>';
	}

	// Add custom field for user roles in plugin settings page
	function fieldNoteCheckoutUserRoleCallback() {
		$user_roles           = get_editable_roles();
		$note_checkout_fields = get_option( 'vk_toret_fakturoid_extension_note_checkout_fields', array() );
		?>
		<?php foreach ( $user_roles as $role => $details ) : ?>
            <label for="vk_toret_fakturoid_extension_note_checkout_fields_<?php echo esc_attr( $role ); ?>">
                <input type="checkbox" name="vk_toret_fakturoid_extension_note_checkout_fields[]"
                       id="vk_toret_fakturoid_extension_note_checkout_fields_<?php echo esc_attr( $role ); ?>"
                       value="<?php echo esc_attr( $role ); ?>" <?php checked( in_array( $role, $note_checkout_fields ), true ); ?> />
				<?php echo esc_html( $details['name'] ); ?>
            </label>
            <br/>
		<?php endforeach; ?>
		<?php
	}

	// Add custom field for due date in user profile page
	function addCustomFieldsToUserProfile( $user ) {
		$user_id = $user->ID;

		$due_option  = get_option( 'vk_toret_fakturoid_extension_due', false );
		$note_option = get_option( 'vk_toret_fakturoid_extension_note', false );

		$due_days    = get_user_meta( $user_id, 'vk_toret_fakturoid_extension_due', true );
		$custom_note = get_user_meta( $user_id, 'vk_toret_fakturoid_extension_note', true );

		//Do not add fields if unchecked
		if ( ! $due_option && ! $note_option ) {
			return;
		}
		?>
        <h2><?php echo esc_html__( 'Fakturoid extension', 'vk-toret-fakturoid-extension' ); ?></h2>
        <table class="form-table">
			<?php if ( $due_option ): ?>
                <tr>
                    <th scope="row"><?php echo esc_html__( 'Due Days', 'vk-toret-fakturoid-extension' ); ?></th>
                    <td>
                        <input type="number" name="vk_toret_fakturoid_extension_due"
                               value="<?php echo esc_attr( $due_days ); ?>"/>
                    </td>
                </tr>
			<?php endif; ?>
			<?php if ( $note_option ): ?>
                <tr>
                    <th scope="row"><?php echo esc_html__( 'Custom Note', 'vk-toret-fakturoid-extension' ); ?></th>
                    <td>
                        <textarea name="vk_toret_fakturoid_extension_note" id="vk_toret_fakturoid_extension_note"
                                  cols="5" rows="3"><?php echo esc_attr( $custom_note ); ?></textarea>
                    </td>
                </tr>
			<?php endif; ?>
        </table>
		<?php
	}

	function saveCustomFieldInUserProfile( $user_id ) {
		if ( current_user_can( 'edit_user', $user_id ) ) {
			$fields = array(
				'vk_toret_fakturoid_extension_due',
				'vk_toret_fakturoid_extension_note'
			);
			foreach ( $fields as $field ) {
				if ( isset( $_POST[ $field ] ) ) {
					update_user_meta( $user_id, $field, sanitize_text_field( $_POST[ $field ] ) );
				}
			}

		}
	}

	public function sanitizeCallback( $input ) {
		if ( is_array( $input ) ) {
			return array_map( 'sanitize_text_field', $input );
		}

		return '';
	}
}

// Instantiate the plugin class
new VKToretFakturoidExtension();