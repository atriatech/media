<?php

return [
    /*
     * subSizes for the uploaded image.
     * if the crop option is set to false, the image will be resized keeping the aspect ration according to the width and height
     * but if the crop option is set to true, the image will be cropped with the width and height provided.
     * you can add any size you want
     */
    'sub_sizes' => [
        'thumbnail' => [
            'width' => 150,
            'height' => 150,
            'crop' => true,
        ],
        'medium' => [
            'width' => 300,
            'height' => 300,
            'crop' => false,
        ],
        'large' => [
            'width' => 1024,
            'height' => 1024,
            'crop' => false,
        ],
        '1600x800' => [
            'width' => 1600,
            'height' => 800,
            'crop' => false,
        ]
    ],

    /*
     * supported mime types
     * you can add the extensions you want to each category. or add a new category
     * do not remove these default categories
     */
    'mime_types' => [
        'image/*' => '.jpg,.png,.jpeg,.bmp,.gif',
        'video/*' => '.mp4,.mov',
        'audio/*' => '.mp3',
        'other' => '.pdf,.zip'
    ],


    'upload_folder' => 'media'
];
