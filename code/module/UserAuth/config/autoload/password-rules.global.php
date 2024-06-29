<?php

namespace UserAuth;

return [
    __NAMESPACE__ => [
        'password-rules' => [
            'minSize' => 12,
            'atLeastOneLowerCase' => true,
            'atLeastOneUpperCase' => true,
            'atLeastOneNumber' => true,
            'atLeastOneSpecialCharacters' => '{}[]()\/\'"`~,;:.<>*^@$%+?&!=#_-', // make sure the "-" is the last character
            //'pattern'=>'([a-zA-Z0-9\{\}\[\]\(\)\/\\\'"`~,;:\.<>\*\^\-@\$%\+\?&!=#_]{12,})i',
        ],
    ],
];
