<?php

if(is_admin()){
    require_once 'admin/settings.php';
}
require_once 'front/avatar.php';

add_filter('pre_get_avatar_data', 'get_avatar_data_exid', 10, 2);
function get_avatar_data_exid($args, $id_or_email) {
    static $defava; 
    $defava = get_option('avatar_default');

    // Если используется ава по умолчанию, чтобы работал параметр 'default' если он установлен для функций аватарок
    if(rcl_get_option('rla_force')){
        $args['url'] = letter_avatars_generate($id_or_email);
    }
    elseif(!$args['default'] || $defava == $args['default']){
        $args['default'] = letter_avatars_generate($id_or_email);
    }
    return $args;
}

function letter_avatars_generate($user_id) {
    $avatar = new Rcl_Avatar_Generator($user_id);

    $avatar->filename(rcl_get_option('rla_uhexid'));
    $avatar->generate_avatar(
        rcl_get_option('rla_force_utf8'),
        rcl_get_option('rla_font_color'),
        rcl_get_option('rla_font_size')
    );

    return $avatar->fileurl();
}

add_action('admin_enqueue_scripts', 'letter_avatars_admin_js');
function letter_avatars_admin_js($hook) {
    if(is_admin()) { 
        wp_enqueue_style('wp-color-picker'); 
        add_action('admin_footer', 'rcl_letter_avatar_options_admin_ex', 99);
    }
}

add_action('wp_ajax_rcl_clear_avatar_cache', 'clear_avatar_cache');
function clear_avatar_cache() {
    check_ajax_referer('clear_avatar_cache', '__nonce');
    if(current_user_can('manage_options')) {
        WP_Filesystem();
        global $wp_filesystem;
        $upload_dir = wp_upload_dir();
        $wp_filesystem->rmdir( $upload_dir['basedir'] . '/rcl-uploads/letter_avatar', true);
        wp_die('success');
    }
    wp_die('failed');
}

function rcl_letter_avatar_options_admin_ex(){
?>
    <script type="text/javascript">
        (function($) {
            $(function() {
                $('input[name*="rla_font_color"]').wpColorPicker({defaultColor: '#fff'});
            });
            $('#rcl_clear_avatar').click(function(e){
                e.preventDefault();
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    data: {
                        action: 'rcl_clear_avatar_cache',
                        __nonce: '<?php echo wp_create_nonce('clear_avatar_cache'); ?>'
                    },
                    success: function(data){
                        if(data==='success') alert('Кеш аватарок очищен');
                    }
                });
            });
        })(jQuery);
    </script>
<?php
}
