<?php
return [
    'service_manager' => [
        'services'=>[
            'JWT_SECRET'=>getenv('JWT_SECRET')
                ? getenv('JWT_SECRET')
                : 'Secret key for the JWT. The longer the more secure. Nulla semper eros eget dolor commodo, ut ultrices metus auctor. Mauris arcu odio, suscipit non eros non, egestas porttitor erat. Integer id lectus tristique, dapibus metus luctus, lacinia magna. Nunc urna turpis, tempor ac commodo sit amet, mattis ut elit. Curabitur ut lacus nec orci luctus rhoncus. Cras venenatis fringilla suscipit. Etiam id feugiat risus. Nullam molestie dictum pulvinar. Quisque vehicula varius nisi. Proin placerat augue mi, et molestie magna cursus ut. Integer ullamcorper risus sed erat gravida sollicitudin et sed risus. Suspendisse potenti. Integer lorem neque, vehicula vel dui vitae, fringilla ullamcorper augue. Sed ut diam ipsum. Mauris a massa lobortis, dapibus est quis, tempus massa.'
            ,
        ],
    ],
];
