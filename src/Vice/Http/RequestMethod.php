<?php

namespace Vice\Http;

interface RequestMethod
{
    const HEAD    = 'HEAD';
    const GET     = 'GET';
    const POST    = 'POST';
    const PUT     = 'PUT';
    const PATCH   = 'PATCH';
    const DELETE  = 'DELETE';
    const PURGE   = 'PURGE';
    const OPTIONS = 'OPTIONS';
    const TRACE   = 'TRACE';
    const CONNECT = 'CONNECT';
}
