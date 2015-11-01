<?php
return [
    'adminEmail' => 'admin@example.com',
    'zakupki'    => [
        'ftp' => [
            'host'     => '194.105.148.53',
            'port'     => 21,
            'username' => 'free',
            'password' => 'free',
        ],
        'excludedRegionDirs' => [
            '_logs',
            'fcs_undefined',
        ],
        'contractsPath' => '/contracts',
    ],
    'stopList' => [
        'usernames' => [
            'text' => [
                'нет', 'нету', 'net', 'nety', 'netu', '-',
            ],
            'regexes' => [
                "/^[^a-zа-я0-9]+$/iu"
            ],

        ],
        'domains' => [
            'text' => [

            ],
            'regexes' => [

            ],
        ],
    ],

];
