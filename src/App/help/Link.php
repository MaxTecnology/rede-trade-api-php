<?php

namespace App\help;

class Link
{

    function isValid($url)
    {
        $url = trim($url);
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        $components = parse_url($url);
        if (!isset($components['scheme']) || !in_array($components['scheme'], ['http', 'https'])) {
            return false;
        }
        if (!isset($components['host']) || !filter_var($components['host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return false;
        }
        return true;
    }
}
