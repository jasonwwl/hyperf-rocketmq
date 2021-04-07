<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
return [
    'default' => [
        'endpoint' => env('ROCKETMQ_ENDPOINT', 'localhost'),
        'instance_id' => env('ROCKETMQ_INSTANCE_ID'),
        'access_id' => env('ROCKETMQ_ACCESS_ID'),
        'access_key' => env('ROCKETMQ_ACCESS_KEY'),
        'group' => env('ROCKETMQ_GROUP'),
    ],
];
