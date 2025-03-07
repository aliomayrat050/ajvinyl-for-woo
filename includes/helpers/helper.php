<?php

if (!defined('ABSPATH')) {
    exit;
}

function ajvinyl_has_setting($name = '')
{

    return ajvinyl()->has_setting($name);
}

function ajvinyl_get_setting($name, $value = null)
{

    if (ajvinyl_has_setting($name)) {
        $value =  ajvinyl()->get_setting($name);
    }

    $value = apply_filters("ajvinyl/setting/{$name}", $value);

    return $value;
}

function ajvinyl_sanitize_input($input)
{
    return sanitize_text_field($input);
}



