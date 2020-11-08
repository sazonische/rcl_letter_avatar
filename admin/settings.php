<?php
if (!defined('ABSPATH')) exit;
add_filter('admin_options_wprecall','rcl_letter_avatars');
function rcl_letter_avatars($content){
    $opt = new Rcl_Options(__FILE__);
    $content .= $opt->options(
        __('Настройки Letter Avatar'),
        array(
            $opt->options_box(
                __('Настройки Letter Avatar'),
                array(
                    array(
                        'type' => 'text',
                        'title' => __('Цвет шрифта'),
                        'slug' => 'rla_font_color',
                        'notice'=>__('Выбор цвета шрифта в аватарах')
                    ),
                    array(
                        'type' => 'number',
                        'default'=>'78',
                        'title' => __('Размер шрифта'),
                        'slug' => 'rla_font_size',
                        'notice'=>__('Выбор размера шрифта в аватарах')
                    ),
                    array(
                        'type' => 'select',
                        'slug' => 'rla_force',
                        'title' => __('Форсировать url до аватара'),
                        'values' => array(__('No','wp-recall'),__('Yes','wp-recall')),
                        'notice'=>__('Форсирование пути к аватару без граватара')
                    ),
                    array(
                        'type' => 'text',
                        'title' => __('Соль для пользовательского ID'),
                        'slug' => 'rla_uhexid',
                        'notice'=>__('Введите любые комбинации из чисел и сохраните перед очисткой кеша (рекомендуется даже при форсировании аватара)<br/>')
                    ),
                    array(
                        'type' => 'select',
                        'slug' => 'rla_force_utf8',
                        'title' => __('Форсировать принудительно вывод в utf8'),
                        'values' => array(__('No','wp-recall'),__('Yes','wp-recall')),
                        'notice'=>__('Фикс русских символов в неблагоприятной среде PHP (Использовать только при некорректном выводе символов в аватарах)')
                    ),
                    array(
                        'type' => 'custom',
                        'slug' => 'rla_clear',
                        'content' => '<a id="rcl_clear_avatar" href="#" class="button button-primary">' . __('Очистить кеш') . '</a>'
                    )
                )
            ),
            $opt->options_box(
                __('Инфо'),
                array(
                    array(
                        'type' => 'custom',
                        'slug' => 'rla_info',
                        'content'=>__('Сайт автора <a href="https://mmcs.pro/" target="_blank">MMCS.PRO</a><br/>VK <a href="https://vk.com/sazonische" target="_blank">sazonische</a><br/>Telegram <a href="https://t.me/SAZONISCHE" target="_blank">sazonische</a><br/><br/>')
                    )
                )
            )            
        )
    );

    return $content;
}