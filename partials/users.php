<?php

if ( ! current_user_can( 'list_users' ) ) {
	wp_die(
		'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
		'<p>' . __( 'You are not allowed to browse users.' ) . '</p>',
		403
	);
}

$title = __('Users');
$option = 'per_page';
  $args = array(
         'label' => 'Users',
         'default' => 10,
         'option' => 'users_per_page'
         );
  add_screen_option( $option, $args );
$parent_file = 'users.php';

$wp_list_table = new AIXUser_List_Table();
$pagenum = $wp_list_table->get_pagenum();

if ( !empty($_GET['_wp_http_referer']) ) {
  wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce'), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
  exit;
}

$wp_list_table->prepare_items();
$total_pages = $wp_list_table->get_pagination_arg( 'total_pages' );
if ( $pagenum > $total_pages && $total_pages > 0 ) {
  wp_redirect( add_query_arg( 'paged', $total_pages ) );
  exit;
}


?>
<div class="wrap">
<h1>
<?php
echo esc_html( $title );
if ( current_user_can( 'create_users' ) ) { ?>
	<a href="<?php echo admin_url( 'admin.php?page=addressix_users&new=1' ); ?>" class="page-title-action"><?php echo esc_html_x( 'Add New', 'user' ); ?></a>
<?php
																	 }

if ( strlen( $usersearch ) ) {
  /* translators: %s: search keywords */
  printf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', esc_html( $usersearch ) );
}
?>
</h1>

<?php $wp_list_table->views(); ?>

<form id="users" name="users" method="post" class="users" action="<?php echo esc_url( admin_url('admin-post.php') );?>">
<input type="hidden" name="action" value="users_edit">

  <?php wp_nonce_field('edit-users-verify'); ?>

<?php $wp_list_table->search_box( __( 'Search Users' ), 'user' ); ?>

<?php $wp_list_table->display(); ?>
</form>

<br class="clear" />
</div>


</div>
<?php
    