<?php

if (! function_exists('get_direction')) {
    function get_direction()
    {
        return (in_array(app()->getLocale(), ['ar', 'he', 'ur', 'fa'])) ? 'rtl' : 'ltr';
    }
}
