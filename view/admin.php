<div class="wrap">
    <h2 class="title"><?php _e( 'Create Child Theme', 'sctc' ); ?></h2>
    <?php echo settings_errors( 'sctc' ); ?>
    <form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="POST" accept-charset="utf-8">
        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'Name', 'sctc' ); ?></th>
                <td><input type="text" name="theme_name" required></td>
            </tr>
        </table>
        <input type="hidden" name="action" value="sct_create_theme">
        <input type="hidden" name="_nonce" value="<?php echo esc_attr( wp_create_nonce( 'sct_create_theme' ) ); ?>">
        <input type="submit" value="<?php esc_attr_e( 'Create!', 'sctc' ); ?>" class="button button-primary">
    </form>
</div>
