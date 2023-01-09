<?php

use app\Request;

return [
    'think\Request'          => Request::class,
    'think\exception\Handle' => app\admin\exception\Http::class,
];