<?php

return [

    'admission' => [

        'steps' => [

            1 => [
                'title' => 'Thông tin học sinh',

                'fields' => [

                    [
                        'name' => 'HoVaTenHocSinh',
                        'label' => 'Họ và tên học sinh',
                        'type' => 'text',
                        'required' => true,
                        'col' => 'md:col-span-2',
                    ],

                    [
                        'name' => 'GioiTinh',
                        'label' => 'Giới tính',
                        'type' => 'select',
                        'options' => ['Nam', 'Nữ'],
                    ],

                    [
                        'name' => 'NgaySinh',
                        'label' => 'Ngày sinh',
                        'type' => 'date',
                    ],

                ]
            ],

            2 => [
                'title' => 'Địa chỉ',

                'fields' => [

                    [
                        'name' => 'TTSN',
                        'label' => 'Số nhà',
                        'type' => 'text',
                    ],

                    [
                        'name' => 'TTTTP',
                        'label' => 'Tỉnh / Thành phố',
                        'type' => 'select-search',
                        'source' => 'provinces',
                    ],

                ]
            ],

        ]

    ]

];
