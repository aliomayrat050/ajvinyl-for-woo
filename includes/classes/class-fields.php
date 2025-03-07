<?php

namespace AJ_VINYL\Includes\Classes;

if (!defined('ABSPATH')) {
    exit;
}

class Fields
{
    public function __construct() {}

    public static function do_pricing($amount, $qty)
    {
        return (float) $amount / $qty;
    }
}
