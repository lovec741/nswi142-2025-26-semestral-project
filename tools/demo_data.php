<?php

$componentManager = require_once __DIR__ . '/../src/bootstrap.php';

$models = $componentManager->getAllByClass(Model::class);

foreach ($models as $model) {
	$model->dropTables();
}

$demoUsers = [
    ['john.test@demo.local', 'John Testington III'],
    ['jane.demo@fake.test', 'Jane FakeUser McTester'],
    ['admin.placeholder@example.com', 'Admin PlaceholderName'],
    ['user.temp@localhost', 'Temp Usersworth'],
    ['peter.sandbox@demo.local', 'Peter Sandboxington'],
    ['demo.account@notreal.test', 'Demo Accountson'],
    ['test.user123@example.com', 'Test User Numeric'],
    ['placeholder.person@fake.test', 'Placeholder Personstein'],
    ['sample.data@demo.local', 'Sample Datakins'],
    ['dummy.account@localhost', 'Dummy Accounty McFakeface']
];

$userModel = $componentManager->getByClass(UserModel::class);
foreach ($demoUsers as $demoUser) {
	$userModel->createNewUser($demoUser[0], $demoUser[1], null);
}

$demoEvents = [
    [
        'TechConf Fake 2025',
        'A completely made-up conference about nothing important',
        '2025-03-15',
        '2025-03-17',
        1,
        [
            'Unit Testing for Dummies',
            'Advanced Debugging with Prayers'
        ]
    ],
    [
        'WebDev Nonsense Summit',
        'Learn fake web development from fake experts',
        '2025-04-10',
        '2025-04-12',
        2,
        [
            'CSS is not Programming',
            'JavaScript: Why Bother'
        ]
    ],
    [
        'Database Dreamland Expo',
        'Where databases go to die',
        '2026-05-20',
        '2026-05-22',
        3,
        [
            'SQL for People Who Hate SQL',
            'NoSQL for SQL Lovers',
            'Spreadsheets: The Ultimate Database'
        ]
    ],
    [
        'Cloud Chaos Conference',
        'Hosted in the cloud, literally nowhere',
        '2026-06-05',
        '2026-06-07',
        4,
        [
            'AWS: A Journey to Bankruptcy',
            'Serverless Servers: A Paradox'
        ]
    ],
    [
        'AI Hype Machine 2026',
        'Everything you wanted to know about AI but were afraid to ask ChatGPT',
        '2026-07-18',
        '2026-07-20',
        5,
        [
            'Machine Learning: Garbage In, Garbage Out',
            'Deep Learning: Deeper Confusion',
            'Neural Networks: Pretending to Be Smart'
        ]
    ],
    [
        'Security Theater Spectacular',
        'Learn to pretend your app is secure',
        '2026-08-12',
        '2026-08-14',
        6,
        [
            'Passwords: The Illusion of Safety',
            'Encryption: Trusting Math'
        ]
    ],
    [
        'DevOps Disaster Sprint',
        'Infrastructure goes brrrrr',
        '2026-09-01',
        '2026-09-03',
        7,
        [
            'Docker: Containerized Confusion',
            'Kubernetes: K8s (We Don\'t Know What It Means)',
            'CI/CD: Continuous Incompetence/Continuous Disaster'
        ]
    ],
    [
        'Mobile Mayhem Gathering',
        'Apps that do things on small screens',
        '2026-10-10',
        '2026-10-12',
        8,
        [
            'iOS: Made by Apple, Used by Hipsters',
            'Android: The Other Thing'
        ]
    ],
    [
        'Agile Nonsense Bootcamp',
        'Sprint your way to mediocrity',
        '2026-11-05',
        '2026-11-07',
        9,
        [
            'Scrum: Organized Chaos',
            'Stand-ups: Why We Pretend to Listen',
            'Velocity: A Meaningless Number'
        ]
    ],
    [
        'Code Review Fever Dream',
        'Where code goes to be judged harshly',
        '2026-12-15',
        '2026-12-17',
        10,
        [
            'Nitpicking: An Art Form',
            'Comments: The Real Code'
        ]
    ]
];

$eventsModel = $componentManager->getByClass(EventsModel::class);
foreach ($demoEvents as $demoEvent) {
	$eventsModel->createEvent($demoEvent[4], $demoEvent[0], $demoEvent[1], $demoEvent[2], $demoEvent[3], null, $demoEvent[5], DEMO_IMAGE_NAME);
}

$demoRegistrations = [
    [3, 1, [1, 2]],
    [2, 1, [2]],
    [3, 2, [3]],
    [4, 2, [3, 4]],
    [5, 3, [5, 6, 7]],
    [6, 3, [6]],
    [7, 4, [8]],
    [8, 4, [8, 9]],
    [9, 5, [10, 11]],
    [10, 5, [11, 12]],
    [1, 6, [13, 14]],
    [2, 7, [15, 16, 17]],
    [3, 8, [18]],
    [4, 9, [20, 21, 22]],
    [5, 10, [23]],
    [6, 1, [1]],
    [7, 2, [4]],
    [8, 3, [5, 7]],
    [9, 6, [14]],
    [10, 7, [16, 17]],
    [1, 8, [18, 19]],
    [2, 9, [22]],
    [3, 10, [23, 24]],
    [4, 5, [10]],
    [5, 4, [9]]
];

foreach ($demoRegistrations as $demoRegistration) {
	$eventsModel->createEventRegistration($demoRegistration[0], $demoRegistration[1], $demoRegistration[2]);
}