<?php

namespace AppBundle\Helper\Dictionary;

/**
 * All methods listed at @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html
 */
class HttpMethod
{
    const POST    = 'POST';
    const GET     = 'GET';
    const PUT     = 'PUT';
    const PATCH   = 'PATCH';
    const LINK    = 'LINK';
    const DELETE  = 'DELETE';
    const OPTIONS = 'OPTIONS';
    const HEAD    = 'HEAD';
    const TRACE   = 'TRACE';
    const CONNECT = 'CONNECT';
}