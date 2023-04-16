<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options from the database
delete_option( 'vktfe_due' );
delete_option( 'vktfe_note' );
delete_option( 'vktfe_note_checkout' );