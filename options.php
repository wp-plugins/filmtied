<div class="wrap">
    <?php screen_icon(); ?>
    <div class="filmtied">
    <h2>Filmtied</h2>
    <p>
        <form method="post" action="options.php">
        <?php wp_nonce_field('update-options'); ?>

            <div class="filmtiedGroup">
                <div class="filmtiedGroupTitle">General</div>
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
                    <th scope="row">On post display</th>
                    <td>
                        <input type="checkbox" name="filmtied_trigger_display" value="1" <?= (get_option('filmtied_trigger_display') == '1') ? 'checked="checked"' : '' ?> />
                    </td>
                    </tr>

                    <tr valign="top">
                    <th scope="row">On post save</th>
                    <td>
                        <input type="checkbox" name="filmtied_trigger_publish" value="1" <?= (get_option('filmtied_trigger_publish') == '1') ? 'checked="checked"' : '' ?> />
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

                </table>
            </div> <br>

            <div class="filmtiedGroup">
                <div class="filmtiedGroupTitle">Cache</div>
                <table class="form-table">
                    <tr valign="top">
                    <th scope="row">Type</th>
                    <td>
                    <select name="filmtied_cache_type" id="filmtied_cache_type">
                        <option value="" <?= (get_option('filmtied_cache_type') == '') ? 'selected="selected"' : '' ?>>None</option>
                        <option value="file" <?= (get_option('filmtied_cache_type') == 'file') ? 'selected="selected"' : '' ?>>File</option>
                        <option value="database" <?= (get_option('filmtied_cache_type') == 'database') ? 'selected="selected"' : '' ?>>Database</option>
                        <option value="memcache" <?= (get_option('filmtied_cache_type') == 'memcache') ? 'selected="selected"' : '' ?>>Memcache</option>
                        <option value="memcached" <?= (get_option('filmtied_cache_type') == 'memcached') ? 'selected="selected"' : '' ?>>Memcached</option>
                    </select>
                    </td>
                    </tr>
                </table>
                <table class="form-table" id="filmtied-directory-options" style="display: <?= (get_option('filmtied_cache_type') == 'file') ? 'block' : 'none'; ?>">
                    <tr valign="top">
                    <th scope="row">Cache directory</th>
                    <td><input name="filmtied_cache_dir" type="text" id="filmtied_cache_dir" value="<?php echo get_option('filmtied_cache_dir'); ?>" /></td>
                    </tr>
                    </tr>
                </table>
                <table class="form-table" id="filmtied-memcache-options" style="display: <?= (get_option('filmtied_cache_type') == 'memcache' || get_option('filmtied_cache_type') == 'memcached') ? 'block' : 'none'; ?>">
                    <tr valign="top">
                    <th scope="row">Host</th>
                    <td><input name="filmtied_cache_host" type="text" id="filmtied_cache_host" value="<?php echo get_option('filmtied_cache_host'); ?>" /></td>
                    </tr>
                    </tr>
                    <tr valign="top">
                    <th scope="row">Port</th>
                    <td><input name="filmtied_cache_port" type="text" id="filmtied_cache_port" value="<?php echo get_option('filmtied_cache_port'); ?>" /></td>
                    </tr>
                    </tr>
                </table>
            </div>

            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="filmtied_affiliate_id,filmtied_api_token,filmtied_link_position,filmtied_cache_type,filmtied_cache_dir,filmtied_cache_host,filmtied_cache_port,filmtied_trigger_display,filmtied_trigger_publish" />
            <?php if (function_exists('submit_button')): ?>
                <?php submit_button(); ?>
            <?php else: ?>
                <br/><input type="submit" value="Save settings" />
            <?php endif; ?>
        </form>
    </p>
    </div>
</div>

<script>
document.getElementById('filmtied_cache_type').onchange = function(e) {
    if (!e)
        var e = window.event;
    var value = this.options[this.selectedIndex].value;

    if (value == '') {
        filmtiedHideElementById('filmtied-directory-options');
        filmtiedHideElementById('filmtied-memcache-options');
    } else if (value == 'database') {
        filmtiedHideElementById('filmtied-directory-options');
        filmtiedHideElementById('filmtied-memcache-options');
    } else if (value == 'file') {
        filmtiedShowElementById('filmtied-directory-options');
        filmtiedHideElementById('filmtied-memcache-options');
    } else if (value == 'memcache' || value == 'memcached') {
        filmtiedHideElementById('filmtied-directory-options');
        filmtiedShowElementById('filmtied-memcache-options');
    }
}

function filmtiedHideElementById(id) {
    var e = document.getElementById(id);
    e.style.display = 'none';
}

function filmtiedShowElementById(id) {
    var e = document.getElementById(id);
    e.style.display = 'block';
}

</script>


<style>
    .filmtied .form-table tr th {
        width: 120px;
    }
    .filmtied .form-table tr td input[type="text"] {
        width: 350px;
    }
    .filmtiedGroup {
        border: 1px solid #cccccc; width: auto; padding: 10px;
        width: 515px;
    }
    .filmtiedGroupTitle {
        font-weight: bold;
    }
</style>