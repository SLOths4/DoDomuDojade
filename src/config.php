<?php /** @noinspection SpellCheckingInspection */
return [
    "Airly" => [
        "AirlyApiKey" => "A4gV88G4CKrt7J79a9t6Nwn5VHRvGKC4",
        "AirlyLocationId" => "63816",
        "AirlyEndpoint" => "https://airapi.airly.eu/v2/measurements/location?locationId="
    ],
    "Metar" => [
      "metar_url" => "https://examle.metar.url.pl",
    ],
    "API" => [
        [
            "title" => "IMGW",
            "url" => "https://danepubliczne.imgw.pl/api/data/synop/id/12330",
            "description" => "Dane meteorologiczne z Poznańskiej stacji"
        ],
        [
            "title" => "ZTM",
            "url" => "https://www.peka.poznan.pl/vm/method.vm",
            "description" => "link do vm przystanku AWF73"
        ]
    ],
    "Calendar" => [
        [
            "title" => "Kalendarz wydarzeń szkolnych",
            "url" => "https://calendar.google.com/calendar/ical/c_e22b26a985cffb8ff2a9afd9e3516d5ca1e5d608c2d3bf20807da38a40f71431%40group.calendar.google.com/public/basic.ics",
            "description" => "Szkolne kalendarium"
        ]
    ],
    "Database" => [
        "db_user" => "root",
        "db_password" => "{pass]",
        "db_name" => "anouncements",
        "db_host" => "localhost",
        "db_type" => "sqlite"
    ]
];