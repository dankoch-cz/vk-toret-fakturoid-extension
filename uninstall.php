<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options from the database
delete_option( 'vk_toret_fakturoid_extension_due' );
delete_option( 'vk_toret_fakturoid_extension_note' );
delete_option( 'vk_toret_fakturoid_extension_note_checkout' );