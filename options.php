<div class="wrap">
    <?php screen_icon(); ?>
    <h2>Filmtied</h2>
    <p>
        <form method="post" action="options.php">
        <?php wp_nonce_field('update-options'); ?>
            <table class="form-table">
                <tr valign="top">
                <th scope="row">Link position</th>
                <td>
                <select name="filmtied_link_position" id="filmtied_link_position">
                    <option value="replace" <?= (get_option('filmtied_link_position') == 'replace') ? 'selected="selected"' : '' ?>>Replace</option>
                    <option value="right" <?= (get_option('filmtied_link_position') == 'right') ? 'selected="selected"' : '' ?>>Right</option>
                    <option value="left" <?= (get_option('filmtied_link_position') == 'left') ? 'selected="selected"' : '' ?>>Left</option>
                </select>
                </td>
                </tr>

                <tr valign="top">
                <th scope="row">API token</th>
                <td><input name="filmtied_api_token" type="text" id="filmtied_api_token" value="<?php echo get_option('filmtied_api_token'); ?>" /> <br/></td>
                </tr>

                <tr valign="top">
                <th scope="row">Affiliate ID</th>
                <td><input name="filmtied_affiliate_id" type="text" id="filmtied_affiliate_id" value="<?php echo get_option('filmtied_affiliate_id'); ?>" /></td>
                </tr>

                <tr valign="top">
                <th scope="row">Cache</th>
                <td>
                <select name="filmtied_cache_type" id="filmtied_cache_type">
                    <option value="" <?= (get_option('filmtied_cache_type') == '') ? 'selected="selected"' : '' ?>>None</option>
                    <option value="file" <?= (get_option('filmtied_cache_type') == 'file') ? 'selected="selected"' : '' ?>>File</option>
                    <option value="database" <?= (get_option('filmtied_cache_type') == 'database') ? 'selected="selected"' : '' ?>>Database</option>
                </select>
                </td>
                </tr>

                <tr valign="top">
                <th scope="row">Cache directory</th>
                <td><input name="filmtied_cache_dir" type="text" id="filmtied_cache_dir" value="<?php echo get_option('filmtied_cache_dir'); ?>" /></td>
                </tr>

            </table>
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="filmtied_affiliate_id,filmtied_api_token,filmtied_link_position,filmtied_cache_type,filmtied_cache_dir" />
            <?php if (function_exists('submit_button')): ?>
                <?php submit_button(); ?>
            <?php else: ?>
                <br/><input type="submit" value="Save settings" />
            <?php endif; ?>
        </form>
    </p>
</div>
