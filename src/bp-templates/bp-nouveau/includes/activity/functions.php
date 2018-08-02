<?php
/**
 * Activity functions
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Scripts for the Activity component
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $scripts  The array of scripts to register.
 *
 * @return array The same array with the specific activity scripts.
 */
function bp_nouveau_activity_register_scripts( $scripts = array() ) {
	if ( ! isset( $scripts['bp-nouveau'] ) ) {
		return $scripts;
	}

	return array_merge( $scripts, array(
		'bp-nouveau-activity' => array(
			'file'         => 'js/buddypress-activity%s.js',
			'dependencies' => array( 'bp-nouveau' ),
			'footer'       => true,
		),
		'bp-nouveau-activity-post-form' => array(
			'file'         => 'js/buddypress-activity-post-form%s.js',
			'dependencies' => array( 'bp-nouveau', 'bp-nouveau-activity', 'json2', 'wp-backbone' ),
			'footer'       => true,
		),
	) );
}

/**
 * Enqueue the activity scripts
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_activity_enqueue_scripts() {
	if ( ! bp_is_activity_component() && ! bp_is_group_activity() ) {
		return;
	}

	wp_enqueue_script( 'bp-nouveau-activity' );
}

/**
 * Localize the strings needed for the Activity Post form UI
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $params Associative array containing the JS Strings needed by scripts.
 *
 * @return array The same array with specific strings for the Activity Post form UI if needed.
 */
function bp_nouveau_activity_localize_scripts( $params = array() ) {
	if ( ! bp_is_activity_component() && ! bp_is_group_activity() ) {
		return $params;
	}

	$activity_params = array(
		'user_id'     => bp_loggedin_user_id(),
		'object'      => 'user',
		'backcompat'  => (bool) has_action( 'bp_activity_post_form_options' ),
		'post_nonce'  => wp_create_nonce( 'post_update', '_wpnonce_post_update' ),
	);

	$user_displayname = bp_get_loggedin_user_fullname();

	if ( buddypress()->avatar->show_avatars ) {
		$width  = bp_core_avatar_thumb_width();
		$height = bp_core_avatar_thumb_height();
		$activity_params = array_merge( $activity_params, array(
			'avatar_url'    => bp_get_loggedin_user_avatar( array(
				'width'  => $width,
				'height' => $height,
				'html'   => false,
			) ),
			'avatar_width'  => $width,
			'avatar_height' => $height,
			'user_domain'   => bp_loggedin_user_domain(),
			'avatar_alt'    => sprintf(
				/* translators: %s = member name */
				__( 'Profile photo of %s', 'buddyboss' ),
				$user_displayname
			),
		) );
	}

	/**
	 * Filters the included, specific, Action buttons.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $value The array containing the button params. Must look like:
	 * array( 'buttonid' => array(
	 *  'id'      => 'buttonid',                            // Id for your action
	 *  'caption' => __( 'Button caption', 'text-domain' ),
	 *  'icon'    => 'dashicons-*',                         // The dashicon to use
	 *  'order'   => 0,
	 *  'handle'  => 'button-script-handle',                // The handle of the registered script to enqueue
	 * );
	 */
	$activity_buttons = apply_filters( 'bp_nouveau_activity_buttons', array() );

	if ( ! empty( $activity_buttons ) ) {
		$activity_params['buttons'] = bp_sort_by_key( $activity_buttons, 'order', 'num' );

		// Enqueue Buttons scripts and styles
		foreach ( $activity_params['buttons'] as $key_button => $buttons ) {
			if ( empty( $buttons['handle'] ) ) {
				continue;
			}

			if ( wp_style_is( $buttons['handle'], 'registered' ) ) {
				wp_enqueue_style( $buttons['handle'] );
			}

			if ( wp_script_is( $buttons['handle'], 'registered' ) ) {
				wp_enqueue_script( $buttons['handle'] );
			}

			unset( $activity_params['buttons'][ $key_button ]['handle'] );
		}
	}

	// Activity Objects
	if ( ! bp_is_single_item() && ! bp_is_user() ) {
		$activity_objects = array(
			'profile' => array(
				'text'                     => __( 'Post in: Profile', 'buddyboss' ),
				'autocomplete_placeholder' => '',
				'priority'                 => 5,
			),
		);

		// the groups component is active & the current user is at least a member of 1 group
		if ( bp_is_active( 'groups' ) && bp_has_groups( array( 'user_id' => bp_loggedin_user_id(), 'max' => 1 ) ) ) {
			$activity_objects['group'] = array(
				'text'                     => __( 'Post in: Group', 'buddyboss' ),
				'autocomplete_placeholder' => __( 'Start typing the group name...', 'buddyboss' ),
				'priority'                 => 10,
			);
		}

		/**
		 * Filters the activity objects to apply for localized javascript data.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param array $activity_objects Array of activity objects.
		 */
		$activity_params['objects'] = apply_filters( 'bp_nouveau_activity_objects', $activity_objects );
	}

	$activity_strings = array(
		'whatsnewPlaceholder' => sprintf( __( "Write here or use @ to mention someone.", 'buddyboss' ), bp_get_user_firstname( $user_displayname ) ),
		'whatsnewLabel'       => __( 'Post what\'s new', 'buddyboss' ),
		'whatsnewpostinLabel' => __( 'Post in', 'buddyboss' ),
		'postUpdateButton'    => __( 'Post Update', 'buddyboss' ),
		'cancelButton'        => __( 'Cancel', 'buddyboss' ),
	);

	if ( bp_is_group() ) {
		$activity_params = array_merge(
			$activity_params,
			array(
				'object'  => 'group',
				'item_id' => bp_get_current_group_id(),
			)
		);
	}

	$params['activity'] = array(
		'params'  => $activity_params,
		'strings' => $activity_strings,
	);

	return $params;
}

/**
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_get_activity_directory_nav_items() {
	$nav_items = array();

	$nav_items['all'] = array(
		'component' => 'activity',
		'slug'      => 'all', // slug is used because BP_Core_Nav requires it, but it's the scope
		'li_class'  => array( 'dynamic' ),
		'link'      => bp_get_activity_directory_permalink(),
		'text'      => __( 'All Members', 'buddyboss' ),
		'count'     => '',
		'position'  => 5,
	);

	// deprecated hooks
	$deprecated_hooks = array(
		array( 'bp_before_activity_type_tab_all', 'activity', 0 ),
		array( 'bp_activity_type_tabs', 'activity', 46 ),
	);

	if ( is_user_logged_in() ) {
		$deprecated_hooks = array_merge(
			$deprecated_hooks,
			array(
				array( 'bp_before_activity_type_tab_friends', 'activity', 6 ),
				array( 'bp_before_activity_type_tab_groups', 'activity', 16 ),
				array( 'bp_before_activity_type_tab_favorites', 'activity', 26 ),
			)
		);

		// If the user has favorite create a nav item
		if ( bp_get_total_favorite_count_for_user( bp_loggedin_user_id() ) ) {
			$nav_items['favorites'] = array(
				'component' => 'activity',
				'slug'      => 'favorites', // slug is used because BP_Core_Nav requires it, but it's the scope
				'li_class'  => array(),
				'link'      => bp_loggedin_user_domain() . bp_get_activity_slug() . '/favorites/',
				'text'      => __( 'Saved', 'buddyboss' ),
				'count'     => false,
				'position'  => 35,
			);
		}
	}

	// Check for deprecated hooks :
	foreach ( $deprecated_hooks as $deprectated_hook ) {
		list( $hook, $component, $position ) = $deprectated_hook;

		$extra_nav_items = bp_nouveau_parse_hooked_dir_nav( $hook, $component, $position );

		if ( ! empty( $extra_nav_items ) ) {
			$nav_items = array_merge( $nav_items, $extra_nav_items );
		}
	}

	/**
	 * Filters the activity directory navigation items.
	 *
	 * Use this filter to introduce your custom nav items for the activity directory.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $nav_items The list of the activity directory nav items.
	 */
	return apply_filters( 'bp_nouveau_get_activity_directory_nav_items', $nav_items );
}

/**
 * Make sure bp_get_activity_show_filters() will return the filters and the context
 * instead of the output.
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $output  HTML output
 * @param array  $filters Optional.
 * @param string $context
 *
 * @return array
 */
function bp_nouveau_get_activity_filters_array( $output = '', $filters = array(), $context = '' ) {
	return array(
		'filters' => $filters,
		'context' => $context,
	);
}

/**
 * Get Dropdown filters of the activity component
 *
 * @since BuddyPress 3.0.0
 *
 * @return array the filters
 */
function bp_nouveau_get_activity_filters() {
	add_filter( 'bp_get_activity_show_filters', 'bp_nouveau_get_activity_filters_array', 10, 3 );

	$filters_data = bp_get_activity_show_filters();

	remove_filter( 'bp_get_activity_show_filters', 'bp_nouveau_get_activity_filters_array', 10, 3 );

	$action = '';
	if ( 'group' === $filters_data['context'] ) {
		$action = 'bp_group_activity_filter_options';
	} elseif ( 'member' === $filters_data['context'] || 'member_groups' === $filters_data['context'] ) {
		$action = 'bp_member_activity_filter_options';
	} else {
		$action = 'bp_activity_filter_options';
	}

	$filters = $filters_data['filters'];

	if ( $action ) {
		return bp_nouveau_parse_hooked_options( $action, $filters );
	}

	return $filters;
}

/**
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_activity_secondary_avatars( $action, $activity ) {
	switch ( $activity->component ) {
		case 'groups':
		case 'friends':
			// Only insert avatar if one exists.
			if ( $secondary_avatar = bp_get_activity_secondary_avatar() ) {
				$reverse_content = strrev( $action );
				$position        = strpos( $reverse_content, 'a<' );
				$action          = substr_replace( $action, $secondary_avatar, -$position - 2, 0 );
			}
			break;
	}

	return $action;
}

/**
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_activity_scope_newest_class( $classes = '' ) {
	if ( ! is_user_logged_in() ) {
		return $classes;
	}

	$user_id    = bp_loggedin_user_id();
	$my_classes = array();

	/*
	 * HeartBeat requests will transport the scope.
	 * See bp_nouveau_ajax_querystring().
	 */
	$scope = '';

	if ( ! empty( $_POST['data']['bp_heartbeat']['scope'] ) ) {
		$scope = sanitize_key( $_POST['data']['bp_heartbeat']['scope'] );
	}

	// Add specific classes to perform specific actions on the client side.
	if ( $scope && bp_is_activity_directory() ) {
		$component = bp_get_activity_object_name();

		/*
		 * These classes will be used to count the number of newest activities for
		 * the 'Mentions', 'My Groups' & 'My Connections' tabs
		 */
		if ( 'all' === $scope ) {
			if ( 'groups' === $component && bp_is_active( $component ) ) {
				// Is the current user a member of the group the activity is attached to?
				if ( groups_is_user_member( $user_id, bp_get_activity_item_id() ) ) {
					$my_classes[] = 'bp-my-groups';
				}
			}

			// Connections can post in groups the user is a member of
			if ( bp_is_active( 'friends' ) && (int) $user_id !== (int) bp_get_activity_user_id() ) {
				if ( friends_check_friendship( $user_id, bp_get_activity_user_id() ) ) {
					$my_classes[] = 'bp-my-friends';
				}
			}

			// A mention can be posted by a friend within a group
			if ( true === bp_activity_do_mentions() ) {
				$new_mentions = bp_get_user_meta( $user_id, 'bp_new_mentions', true );

				// The current activity is one of the new mentions
				if ( is_array( $new_mentions ) && in_array( bp_get_activity_id(), $new_mentions ) ) {
					$my_classes[] = 'bp-my-mentions';
				}
			}

		/*
		 * This class will be used to highlight the newest activities when
		 * viewing the 'Mentions', 'My Groups' or the 'My Connections' tabs
		 */
		} elseif ( 'friends' === $scope || 'groups' === $scope || 'mentions' === $scope ) {
			$my_classes[] = 'newest_' . $scope . '_activity';
		}

		// Leave other components do their specific stuff if needed.
		/**
		 * Filters the classes to be applied to the newest activity item.
		 *
		 * Leave other components do their specific stuff if needed.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param array  $my_classes Array of classes to output to class attribute.
		 * @param string $scope      Current scope for the activity type.
		 */
		$my_classes = (array) apply_filters( 'bp_nouveau_activity_scope_newest_class', $my_classes, $scope );

		if ( ! empty( $my_classes ) ) {
			$classes .= ' ' . join( ' ', $my_classes );
		}
	}

	return $classes;
}

/**
 * Get the activity query args for the widget.
 *
 * @since BuddyPress 3.0.0
 *
 * @return array The activity arguments.
 */
function bp_nouveau_activity_widget_query() {
	$args       = array();
	$bp_nouveau = bp_nouveau();

	if ( isset( $bp_nouveau->activity->widget_args ) ) {
		$args = $bp_nouveau->activity->widget_args;
	}

	/**
	 * Filter to edit the activity widget arguments.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $args The activity arguments.
	 */
	return apply_filters( 'bp_nouveau_activity_widget_query', $args );
}

/**
 * Register notifications filters for the activity component.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_activity_notification_filters() {
	$notifications = array(
		array(
			'id'       => 'new_at_mention',
			'label'    => __( 'New mentions', 'buddyboss' ),
			'position' => 5,
		),
		array(
			'id'       => 'update_reply',
			'label'    => __( 'New update replies', 'buddyboss' ),
			'position' => 15,
		),
		array(
			'id'       => 'comment_reply',
			'label'    => __( 'New update comment replies', 'buddyboss' ),
			'position' => 25,
		),
	);

	foreach ( $notifications as $notification ) {
		bp_nouveau_notifications_register_filter( $notification );
	}
}

/**
 * Add controls for the settings of the customizer for the activity component.
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $controls Optional. The controls to add.
 *
 * @return array the controls to add.
 */
function bp_nouveau_activity_customizer_controls( $controls = array() ) {
	return array_merge( $controls, array(
		'act_dir_layout' => array(
			'label'      => __( 'Use column navigation for the Activity directory.', 'buddyboss' ),
			'section'    => 'bp_nouveau_dir_layout',
			'settings'   => 'bp_nouveau_appearance[activity_dir_layout]',
			'type'       => 'checkbox',
		),
		'act_dir_tabs' => array(
			'label'      => __( 'Use tab styling for Activity directory navigation.', 'buddyboss' ),
			'section'    => 'bp_nouveau_dir_layout',
			'settings'   => 'bp_nouveau_appearance[activity_dir_tabs]',
			'type'       => 'checkbox',
		),
	) );
}
