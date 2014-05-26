<?php defined('SYSPATH') or die('No direct script access.');

return array(
    'redis'     =>  array(
        'driver'    =>  'redis',
        'server'    =>  array(
            'host'          =>  'localhost',
            'port'          =>  6379,
            'persistent'    =>  FALSE,
            'timeout'       =>  1
        )
    )
);
