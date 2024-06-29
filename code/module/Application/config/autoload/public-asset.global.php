<?php

namespace Application;

return [
    'public_assets' => [
        // use your module name here
        __NAMESPACE__ => [
            // this is the path where your public assests are
            // can be a string/path or an array of paths where the
            // system should look
            'path' => realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'public'),
            // this is a white list of extensions, anything that is loaded
            // with a different extension will be returned a 404
            'whitelist' => ['js','map','css','jpg','jpeg','png','gif','svg','ttf','woff',],
        ],
    ],
];
