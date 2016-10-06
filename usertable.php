<?php
if( ! class_exists( 'WP_Users_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-users-list-table.php' );
}

class AIXUser_List_Table extends WP_Users_List_Table {

  public function prepare_items() {
    parent::prepare_items();

    $columns = $this->get_columns();
    $hidden = array(); //$this->get_hidden_columns();
    $sortable = $this->get_sortable_columns();
    $this->_column_headers = array($columns, $hidden, $sortable);    
  }

  function bulk_actions( $which = '' ) {
        if ( is_null( $this->_actions ) ) {
            $no_new_actions = $this->_actions = $this->get_bulk_actions();
            /**
             * Filters the list table Bulk Actions drop-down.
             *
             * The dynamic portion of the hook name, `$this->screen->id`, refers
             * to the ID of the current screen, usually a string.
             *
             * This filter can currently only be used to remove bulk actions.
             *
             * @since 3.5.0
             *
             * @param array $actions An array of the available bulk actions.
             */
            $this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
            $this->_actions = array_intersect_assoc( $this->_actions, $no_new_actions );
            $two = '';
        } else {
            $two = '2';
        }
 
        if ( empty( $this->_actions ) )
            return;
 
        echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . __( 'Select bulk action' ) . '</label>';
        echo '<select name="bulk-action' . $two . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
        echo '<option value="-1">' . __( 'Bulk Actions' ) . "</option>\n";
 
        foreach ( $this->_actions as $name => $title ) {
            $class = 'edit' === $name ? ' class="hide-if-no-js"' : '';
 
            echo "\t" . '<option value="' . $name . '"' . $class . '>' . $title . "</option>\n";
        }
 
        echo "</select>\n";
 
        submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => "doaction$two" ) );
        echo "\n";
    }

  /**
     * Get the current action selected from the bulk actions dropdown.
     *
     * @since 3.1.0
     * @access public
     *
     * @return string|false The action name or False if no action was selected
     */
    public function current_action() {

      if ( isset( $_REQUEST['changeit'] ) &&
	   ( ! empty( $_REQUEST['new_role'] ) || ! empty( $_REQUEST['new_role2'] ) ) ) {
	return 'promote';
      }

      if ( isset( $_REQUEST['filter_action'] ) && ! empty( $_REQUEST['filter_action'] ) )
	return false;
      
      if ( isset( $_REQUEST['bulk-action'] ) && -1 != $_REQUEST['bulk-action'] )
	return $_REQUEST['bulk-action'];
      
      if ( isset( $_REQUEST['bulk-action2'] ) && -1 != $_REQUEST['bulk-action2'] )
	return $_REQUEST['bulk-action2'];
      
      return false;
    }

      /**
     * Return an associative array listing all the views that can be used
     * with this table.
     *
     * Provides a list of roles and user count for that role for easy
     * Filtersing of the user table.
     *
     * @since  3.1.0
     * @access protected
     *
     * @global string $role
     *
     * @return array An array of HTML links, one for each view.
     */
    protected function get_views() {
        global $role;
 
        $wp_roles = wp_roles();
 
        if ( $this->is_site_users ) {
            $url = 'admin.php?page=addressix_users&id=' . $this->site_id;
            switch_to_blog( $this->site_id );
            $users_of_blog = count_users();
            restore_current_blog();
        } else {
            $url = 'admin.php?page=addressix_users';
            $users_of_blog = count_users();
        }
 
        $total_users = $users_of_blog['total_users'];
        $avail_roles =& $users_of_blog['avail_roles'];
        unset($users_of_blog);
 
        $class = empty($role) ? ' class="current"' : '';
        $role_links = array();
        $role_links['all'] = "<a href='$url'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_users, 'users' ), number_format_i18n( $total_users ) ) . '</a>';
        foreach ( $wp_roles->get_names() as $this_role => $name ) {
            if ( !isset($avail_roles[$this_role]) )
                continue;
 
            $class = '';
 
            if ( $this_role === $role ) {
                $class = ' class="current"';
            }
 
            $name = translate_user_role( $name );
            /* translators: User role name with count */
            $name = sprintf( __('%1$s <span class="count">(%2$s)</span>'), $name, number_format_i18n( $avail_roles[$this_role] ) );
            $role_links[$this_role] = "<a href='" . esc_url( add_query_arg( 'role', $this_role, $url ) ) . "'$class>$name</a>";
        }
 
        if ( ! empty( $avail_roles['none' ] ) ) {
 
            $class = '';
 
            if ( 'none' === $role ) {
                $class = ' class="current"';
            }
 
            $name = __( 'No role' );
            /* translators: User role name with count */
            $name = sprintf( __('%1$s <span class="count">(%2$s)</span>'), $name, number_format_i18n( $avail_roles['none' ] ) );
            $role_links['none'] = "<a href='" . esc_url( add_query_arg( 'role', 'none', $url ) ) . "'$class>$name</a>";
 
        }
 
        return $role_links;
    }


}
