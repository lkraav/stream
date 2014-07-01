<?php

class WP_Stream_Connector_GravityForms extends WP_Stream_Connector {

	/**
	 * Connector slug
	 *
	 * @var string
	 */
	public static $name = 'gravityforms';

	/**
	 * Holds tracked plugin minimum version required
	 *
	 * @const string
	 */
	const PLUGIN_MIN_VERSION = '1.8.8';

	/**
	 * Actions registered for this connector
	 *
	 * @var array
	 */
	public static $actions = array(
		'gform_after_save_form',
		'gform_pre_confirmation_save',
		'gform_pre_notification_save',
		'gform_notification_delete',
		'gform_confirmation_delete',
		'gform_notification_status',
		'gform_confirmation_status',
		'gform_form_status_change',
		'gform_form_reset_views',
		'gform_before_delete_form',
		'gform_form_trash',
		'gform_form_restore',
		'gform_form_duplicate',
		'gform_export_separator', // Export entries
		'gform_export_options', // Export forms
		'gform_import_form_xml_options', // Import
		'gform_delete_lead',
		'gform_insert_note',
		'gform_delete_note',
		'gform_update_status',
		'gform_update_is_read',
		'gform_update_is_starred',
		'update_option',
		'add_option',
		'delete_option',
		'update_site_option',
		'add_site_option',
		'delete_site_option',
	);

	/**
	 * Tracked option keys
	 *
	 * @var array
	 */
	public static $options = array();

	/**
	 * Tracking registered Settings, with overridden data
	 *
	 * @var array
	 */
	public static $options_override = array();

	/**
	 * Check if plugin dependencies are satisfied and add an admin notice if not
	 *
	 * @return bool
	 */
	public static function is_dependency_satisfied() {
		if ( ! class_exists( 'GFForms' ) ) {
			//WP_Stream::notice(
			//	sprintf( __( '<strong>Stream Gravity Forms Connector</strong> requires the <a href="%1$s" target="_blank">Gravity Forms</a> plugin to be installed and activated.', 'stream' ), esc_url( 'http://www.gravityforms.com/' ) ),
			//	true
			//);
		} elseif ( version_compare( GFForms::$version, self::PLUGIN_MIN_VERSION, '<' ) ) {
			//WP_Stream::notice(
			//	sprintf( __( 'Please <a href="%1$s" target="_blank">install Gravity Forms</a> version %2$s or higher for the <strong>Stream Gravity Forms Connector</strong> plugin to work properly.', 'stream' ), esc_url( 'http://www.gravityforms.com/' ), self::PLUGIN_MIN_VERSION ),
			//	true
			//);
		} else {
			return true;
		}
	}

	/**
	 * Return translated connector label
	 *
	 * @return string Translated connector label
	 */
	public static function get_label() {
		return __( 'Gravity Forms', 'gravityforms' );
	}

	/**
	 * Return translated action labels
	 *
	 * @return array Action label translations
	 */
	public static function get_action_labels() {
		return array(
			'created'    => __( 'Created', 'stream' ),
			'updated'    => __( 'Updated', 'stream' ),
			'exported'   => __( 'Exported', 'stream' ),
			'imported'   => __( 'Imported', 'stream' ),
			'added'      => __( 'Added', 'stream' ),
			'deleted'    => __( 'Deleted', 'stream' ),
			'trashed'    => __( 'Trashed', 'stream' ),
			'restored'   => __( 'Restored', 'stream' ),
			'duplicated' => __( 'Duplicated', 'stream' ),
		);
	}

	/**
	 * Return translated context labels
	 *
	 * @return array Context label translations
	 */
	public static function get_context_labels() {
		return array(
			'forms'    => __( 'Forms', 'gravityforms' ),
			'settings' => __( 'Settings', 'gravityforms' ),
			'export'   => __( 'Import/Export', 'gravityforms' ),
			'entries'  => __( 'Entries', 'gravityforms' ),
			'notes'    => __( 'Notes', 'gravityforms' ),
		);
	}

	/**
	 * Add action links to Stream drop row in admin list screen
	 *
	 * @filter wp_stream_action_links_{connector}
	 *
	 * @param  array $links  Previous links registered
	 * @param  int   $record Stream record
	 *
	 * @return array             Action links
	 */
	public static function action_links( $links, $record ) {
		if ( 'forms' === $record->context ) {
			$links[ __( 'Edit', 'gravityforms' ) ] = add_query_arg(
				array(
					'page' => 'gf_edit_forms',
					'id' => $record->object_id,
				),
				admin_url( 'admin.php' )
			);
		} elseif ( 'entries' === $record->context ) {
			$links[ __( 'View', 'gravityforms' ) ] = add_query_arg(
				array(
					'page' => 'gf_entries',
					'view' => 'entry',
					'lid' => $record->object_id,
					'id' => wp_stream_get_meta( $record->ID, 'form_id', true ),
				),
				admin_url( 'admin.php' )
			);
		} elseif ( 'notes' === $record->context ) {
			$links[ __( 'View', 'gravityforms' ) ] = add_query_arg(
				array(
					'page' => 'gf_entries',
					'view' => 'entry',
					'lid' => wp_stream_get_meta( $record->ID, 'lead_id', true ),
					'id' => wp_stream_get_meta( $record->ID, 'form_id', true ),
				),
				admin_url( 'admin.php' )
			);
		} elseif ( 'settings' === $record->context ) {
			$links[ __( 'Edit Settings', 'stream' ) ] = add_query_arg(
				array(
					'page' => 'gf_settings',
				),
				admin_url( 'admin.php' )
			);
		}

		return $links;
	}

	public static function register() {
		parent::register();

		self::$options = array(
			'rg_gforms_disable_css'         => array(
				'label' => __( 'Output CSS', 'gravityforms' ),
			),
			'rg_gforms_enable_html5'        => array(
				'label' => __( 'Output HTML5', 'gravityforms' ),
			),
			'gform_enable_noconflict'       => array(
				'label' => __( 'No-Conflict Mode', 'gravityforms' ),
			),
			'rg_gforms_currency'            => array(
				'label' => __( 'Currency', 'gravityforms' ),
			),
			'rg_gforms_captcha_public_key'  => array(
				'label' => __( 'reCAPTCHA Public Key', 'gravityforms' ),
			),
			'rg_gforms_captcha_private_key' => array(
				'label' => __( 'reCAPTCHA Private Key', 'gravityforms' ),
			),
			'rg_gforms_key'                 => null,
		);
	}

	/**
	 * Track Create/Update actions on Forms
	 *
	 * @param $form
	 * @param $is_new
	 */
	public static function callback_gform_after_save_form( $form, $is_new ) {
		$title = $form['title'];
		$id    = $form['id'];

		self::log(
			sprintf(
				__( '%s form "%s"', 'stream' ),
				$is_new ? __( 'Created', 'stream' ) : __( 'Updated', 'stream' ),
				$title
			),
			array(
				'action' => $is_new,
				'id'     => $id,
				'title'  => $title,
			),
			$id,
			array(
				'forms' => $is_new ? 'created' : 'updated',
			)
		);
	}

	/**
	 * Track saving form confirmations
	 *
	 * @param $confirmation
	 * @param $form
	 * @param bool $is_new
	 *
	 * @return mixed
	 */
	public static function callback_gform_pre_confirmation_save( $confirmation, $form, $is_new = true ) {
		if ( ! isset( $is_new ) ) {
			$is_new = false;
		}

		self::log(
			sprintf(
				__( '"%s" confirmation has been %s for "%s"', 'stream' ),
				$confirmation['name'],
				$is_new ? __( 'Created', 'stream' ) : __( 'Updated', 'stream' ),
				$form['title']
			),
			array(
				'is_new'  => $is_new,
				'form_id' => $form['id'],
			),
			$form['id'],
			array(
				'forms' => 'updated',
			)
		);

		return $confirmation;
	}

	/**
	 * Track saving form notifications
	 *
	 * @param $notification
	 * @param $form
	 * @param bool $is_new
	 *
	 * @return mixed
	 */
	public static function callback_gform_pre_notification_save( $notification, $form, $is_new = true ) {
		if ( ! isset( $is_new ) ) {
			$is_new = false;
		}

		self::log(
			sprintf(
				__( '"%s" notification has been %s for "%s"', 'stream' ),
				$notification['name'],
				$is_new ? __( 'Created', 'stream' ) : __( 'Updated', 'stream' ),
				$form['title']
			),
			array(
				'is_update' => $is_new,
				'form_id'   => $form['id'],
			),
			$form['id'],
			array(
				'forms' => 'updated',
			)
		);

		return $notification;
	}

	/**
	 * Track deletion of notifications
	 *
	 * @param $notification
	 * @param $form
	 */
	public static function callback_gform_notification_delete( $notification, $form ) {
		self::log(
			sprintf(
				__( '"%s" notification has been deleted from "%s"', 'stream' ),
				$notification['name'],
				$form['title']
			),
			array(
				'form_id'      => $form['id'],
				'notification' => $notification,
			),
			$form['id'],
			array(
				'forms' => 'updated',
			)
		);
	}

	/**
	 * Track deletion of confirmations
	 *
	 * @param $confirmation
	 * @param $form
	 */
	public static function callback_gform_confirmation_delete( $confirmation, $form ) {
		self::log(
			sprintf(
				__( '"%s" confirmation has been deleted from "%s"', 'stream' ),
				$confirmation['name'],
				$form['title']
			),
			array(
				'form_id'      => $form['id'],
				'confirmation' => $confirmation,
			),
			$form['id'],
			array(
				'forms' => 'updated',
			)
		);
	}

	/**
	 * Track status change of confirmations
	 *
	 * @param $confirmation
	 * @param $form
	 * @param $is_active
	 */
	public static function callback_gform_confirmation_status( $confirmation, $form, $is_active ) {
		self::log(
			sprintf(
				__( '"%s" confirmation has been %s from "%s"', 'stream' ),
				$confirmation['name'],
				$is_active ? __( 'activated', 'stream' ) : __( 'deactivated', 'stream' ),
				$form['title']
			),
			array(
				'form_id'      => $form['id'],
				'confirmation' => $confirmation,
				'is_active'    => $is_active,
			),
			null,
			array(
				'forms' => 'updated',
			)
		);
	}

	/**
	 * Track status change of confirmations
	 *
	 * @param $id
	 */
	public static function callback_gform_form_reset_views( $id ) {
		$form = self::get_form( $id );

		self::log(
			__( '"%s" form views has been reset', 'stream' ),
			array(
				'title'   => $form['title'],
				'form_id' => $form['id'],
			),
			$form['id'],
			array(
				'forms' => 'updated',
			)
		);
	}

	/**
	 * Track status change of notifications
	 *
	 * @param $notification
	 * @param $form
	 * @param $is_active
	 */
	public static function callback_gform_notification_status( $notification, $form, $is_active ) {
		self::log(
			sprintf(
				__( '"%s" notification has been %s from "%s"', 'stream' ),
				$notification['name'],
				$is_active ? __( 'activated', 'stream' ) : __( 'deactivated', 'stream' ),
				$form['title']
			),
			array(
				'form_id'      => $form['id'],
				'notification' => $notification,
				'is_active'    => $is_active,
			),
			$form['id'],
			array(
				'forms' => 'updated',
			)
		);
	}

	/**
	 * Track status change of forms
	 *
	 * @param $id
	 * @param $action
	 */
	public static function callback_gform_form_status_change( $id, $action ) {
		$form    = self::get_form( $id );
		$actions = array(
			'activated'   => __( 'Activated', 'stream' ),
			'deactivated' => __( 'Deactivated', 'stream' ),
			'trashed'     => __( 'Trashed', 'default' ),
			'restored'    => __( 'Restored', 'default' ),
		);

		self::log(
			sprintf(
				__( '%s form "%s"', 'stream' ),
				$actions[ $action ],
				$form['title']
			),
			array(
				'form_title' => $form['title'],
				'form_id'    => $id,
			),
			$form['id'],
			array(
				'forms' => $action,
			)
		);
	}

	public static function callback_update_option( $option, $old, $new ) {
		self::check( $option, $old, $new );
	}

	public static function callback_add_option( $option, $val ) {
		self::check( $option, null, $val );
	}

	public static function callback_delete_option( $option ) {
		self::check( $option, null, null );
	}

	public static function callback_update_site_option( $option, $old, $new ) {
		self::check( $option, $old, $new );
	}

	public static function callback_add_site_option( $option, $val ) {
		self::check( $option, null, $val );
	}

	public static function callback_delete_site_option( $option ) {
		self::check( $option, null, null );
	}

	public static function check( $option, $old_value, $new_value ) {
		if ( ! array_key_exists( $option, self::$options ) ) {
			return;
		}

		if ( is_null( self::$options[ $option ] ) ) {
			call_user_func( array( __CLASS__, 'check_' . str_replace( '-', '_', $option ) ), $old_value, $new_value );
		} else {
			$data         = self::$options[ $option ];
			$option_title = $data['label'];
			$context      = isset( $data['context'] ) ? $data['context'] : 'settings';

			self::log(
				__( '"%s" setting was updated', 'stream' ),
				compact( 'option_title', 'option', 'old_value', 'new_value' ),
				null,
				array(
					$context => isset( $data['action'] ) ? $data['action'] : 'updated',
				)
			);
		}
	}

	public static function check_rg_gforms_key( $old_value, $new_value ) {
		$is_update = ( $new_value && strlen( $new_value ) );
		$option    = 'rg_gforms_key';

		self::log(
			sprintf(
				__( 'Gravity Forms License Key was %s', 'stream' ),
				$is_update ? __( 'updated', 'stream' ) : __( 'deleted', 'stream' )
			),
			compact( 'option', 'old_value', 'new_value' ),
			null,
			array(
				'settings' => $is_update ? 'updated' : 'deleted',
			)
		);
	}

	public static function callback_gform_export_separator( $dummy, $form_id ) {
		$form = self::get_form( $form_id );

		self::log(
			__( 'Form "%s" was exported', 'stream' ),
			array(
				'form_title' => $form['title'],
				'form_id'    => $form_id,
			),
			$form_id,
			array(
				'export' => 'exported',
			)
		);

		return $dummy;
	}

	public static function callback_gform_import_form_xml_options( $dummy ) {
		self::log(
			__( 'Started Import process', 'stream' ),
			array(),
			null,
			array(
				'export' => 'imported',
			)
		);

		return $dummy;
	}

	public static function callback_gform_export_options( $dummy, $forms ) {
		$ids    = wp_list_pluck( $forms, 'id' );
		$titles = wp_list_pluck( $forms, 'title' );

		self::log(
			__( 'Started Forms Export process, for "%d" forms', 'stream' ),
			array(
				'count'  => count( $forms ),
				'ids'    => $ids,
				'titles' => $titles,
			),
			null,
			array(
				'export' => 'imported',
			)
		);

		return $dummy;
	}

	public static function callback_gform_before_delete_form( $id ) {
		$form = self::get_form( $id );

		self::log(
			__( 'Deleted form "%s"', 'stream' ),
			array(
				'form_title' => $form['title'],
				'form_id'    => $id,
			),
			$form['id'],
			array(
				'forms' => 'deleted',
			)
		);
	}

	public static function callback_gform_form_duplicate( $id, $new_id ) {
		$form = self::get_form( $id );
		$new  = self::get_form( $new_id );

		self::log(
			__( 'Created form "%s" as duplicate from "%s"', 'stream' ),
			array(
				'new_form_title' => $new['title'],
				'form_title'     => $form['title'],
				'form_id'        => $id,
				'new_id'         => $new_id,
			),
			$new_id,
			array(
				'forms' => 'duplicated',
			)
		);
	}

	public static function callback_gform_delete_lead( $lead_id ) {
		$lead = GFFormsModel::get_lead( $lead_id );
		$form = self::get_form( $lead['form_id'] );

		self::log(
			__( 'Deleted lead #%d from "%s"', 'stream' ),
			array(
				'lead_id'    => $lead_id,
				'form_title' => $form['title'],
				'form_id'    => $form['id'],
			),
			$lead_id,
			array(
				'entries' => 'deleted',
			)
		);
	}

	public static function callback_gform_insert_note( $note_id, $lead_id, $user_id, $user_name, $note, $note_type ) {
		$lead = GFFormsModel::get_lead( $lead_id );
		$form = self::get_form( $lead['form_id'] );

		self::log(
			__( 'Added note #%d to lead #%d on "%s"', 'stream' ),
			array(
				'note_id'    => $note_id,
				'lead_id'    => $lead_id,
				'form_title' => $form['title'],
				'form_id'    => $form['id'],
			),
			$note_id,
			array(
				'notes' => 'added',
			)
		);
	}

	public static function callback_gform_delete_note( $note_id, $lead_id ) {
		$lead = GFFormsModel::get_lead( $lead_id );
		$form = self::get_form( $lead['form_id'] );

		self::log(
			__( 'Deleted note #%d from lead #%d on "%s"', 'stream' ),
			array(
				'note_id'    => $note_id,
				'lead_id'    => $lead_id,
				'form_title' => $form['title'],
				'form_id'    => $form['id'],
			),
			$note_id,
			array(
				'notes' => 'deleted',
			)
		);
	}

	public static function callback_gform_update_status( $lead_id, $status, $prev = '' ) {
		$lead = GFFormsModel::get_lead( $lead_id );
		$form = self::get_form( $lead['form_id'] );

		if ( 'active' === $status && 'trash' === $prev ) {
			$status = 'restore';
		}

		$actions = array(
			'active'  => __( 'Activated', 'stream' ),
			'spam'    => __( 'Spam', 'stream' ),
			'trash'   => __( 'Trashed', 'default' ),
			'restore' => __( 'Restored', 'default' ),
		);

		if ( ! isset( $actions[ $status ] ) ) {
			return;
		}

		self::log(
			sprintf(
				__( '%s lead #%d on "%s"', 'stream' ),
				$actions[ $status ],
				$lead_id,
				$form['title']
			),
			array(
				'lead_id'    => $lead_id,
				'form_title' => $form['title'],
				'form_id'    => $form['id'],
				'status'     => $status,
				'prev'       => $prev,
			),
			$lead_id,
			array(
				'entries' => $status,
			)
		);
	}

	public static function callback_gform_update_is_read( $lead_id, $status ) {
		$lead = GFFormsModel::get_lead( $lead_id );
		$form = self::get_form( $lead['form_id'] );

		self::log(
			sprintf(
				__( 'Marked lead #%d on "%s" as %s', 'stream' ),
				$lead_id,
				$form['title'],
				$status ? __( 'Read', 'stream' ) : __( 'Unread', 'stream' )
			),
			array(
				'lead_id'    => $lead_id,
				'form_title' => $form['title'],
				'form_id'    => $form['id'],
				'status'     => $status,
			),
			$lead_id,
			array(
				'entries' => 'updated',
			)
		);
	}

	public static function callback_gform_update_is_starred( $lead_id, $status ) {
		$lead = GFFormsModel::get_lead( $lead_id );
		$form = self::get_form( $lead['form_id'] );

		self::log(
			sprintf(
				__( '%s lead #%d on "%s"', 'stream' ),
				$status ? __( 'Starred', 'stream' ) : __( 'Unstarred', 'stream' ),
				$lead_id,
				$form['title']
			),
			array(
				'lead_id'    => $lead_id,
				'form_title' => $form['title'],
				'form_id'    => $form['id'],
				'status'     => $status,
			),
			$lead_id,
			array(
				'entries' => 'updated',
			)
		);
	}

	private static function get_form( $form_id ) {
		return reset( GFFormsModel::get_forms_by_id( $form_id ) );
	}

}
