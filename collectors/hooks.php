<?php
/*
Copyright 2009-2017 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Collector_Hooks extends QM_Collector {

	public $id = 'hooks';
	protected static $hide_core;

	public function name() {
		return __( 'Hooks & Actions', 'query-monitor' );
	}

	public function process() {

		global $wp_actions, $wp_filter;

		self::$hide_qm   = self::hide_qm();
		self::$hide_core = ( defined( 'QM_HIDE_CORE_HOOKS' ) && QM_HIDE_CORE_HOOKS );

		if ( is_admin() and ( $admin = QM_Collectors::get( 'admin' ) ) ) {
			$this->data['screen'] = $admin->data['base'];
		} else {
			$this->data['screen'] = '';
		}

		$hooks = $all_parts = $components = array();

		if ( has_filter( 'all' ) ) {
			$hooks['all'] = self::process_action( 'all', $wp_filter, self::$hide_qm, self::$hide_core );
		}

		if ( defined( 'QM_SHOW_ALL_HOOKS' ) && QM_SHOW_ALL_HOOKS ) {
			// Show all hooks
			$hook_names = array_keys( $wp_filter );
		} else {
			// Only show action hooks that have been called at least once
			$hook_names = array_keys( $wp_actions );
		}

		foreach ( $hook_names as $name ) {

			$hooks[ $name ] = self::process_action( $name, $wp_filter, self::$hide_qm, self::$hide_core );

			$all_parts    = array_merge( $all_parts, $hooks[ $name ]['parts'] );
			$components   = array_merge( $components, $hooks[ $name ]['components'] );

		}

		$this->data['hooks'] = $hooks;
		$this->data['parts'] = array_unique( array_filter( $all_parts ) );
		$this->data['components'] = array_unique( array_filter( $components ) );

	}

	public static function process_action( $name, array $wp_filter, $hide_qm = false, $hide_core = false ) {

		$actions = $components = array();

		if ( isset( $wp_filter[ $name ] ) ) {

			# http://core.trac.wordpress.org/ticket/17817
			$action = $wp_filter[ $name ];

			foreach ( $action as $priority => $callbacks ) {

				foreach ( $callbacks as $callback ) {

					$callback = QM_Util::populate_callback( $callback );

					if ( isset( $callback['component'] ) ) {
						if (
							( $hide_qm && 'query-monitor' === $callback['component']->context )
							|| ( $hide_core && 'core' === $callback['component']->context ) 
						) {
							continue;
						}

						$components[ $callback['component']->name ] = $callback['component']->name;
					}

					$actions[] = array(
						'priority'  => $priority,
						'callback'  => $callback,
					);

				}

			}

		}

		$parts = array_filter( preg_split( '#[_/-]#', $name ) );

		return array(
			'name'       => $name,
			'actions'    => $actions,
			'parts'      => $parts,
			'components' => $components,
		);

	}

}

function register_qm_collector_hooks( array $collectors, QueryMonitor $qm ) {
	$collectors['hooks'] = new QM_Collector_Hooks;
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_hooks', 20, 2 );
