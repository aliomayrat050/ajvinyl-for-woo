<?php

namespace AJ_VINYL\Includes\Classes;

if (!defined('ABSPATH')) {
    exit;
}

class HTML
{

    public static function view($view, $data = [])
    {
        extract($data, EXTR_SKIP);
        ob_start();
        $dir = trailingslashit(ajvinyl_get_setting('path')) . 'views/' . $view;

        include $dir . '.php';
        return ob_get_clean();
    }
}
