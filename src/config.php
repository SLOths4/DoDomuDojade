<?php /** @noinspection SpellCheckingInspection */
return [
    "Airly" => [
        "AirlyApiKey" => "A4gV88G4CKrt7J79a9t6Nwn5VHRvGKC4",
        "AirlyLocationId" => "63816",
        "AirlyEndpoint" => "https://airapi.airly.eu/v2/measurements/location?locationId="
    ],
    "Metar" => [
      "metar_url" => "https://awiacja.imgw.pl/metar00.php?airport=EPPO",
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
        "db_user" => "null",
        "db_password" => "null",
        "db_name" => "database",
        "db_host" => "sqlite:/Users/franek/Documents/GitHub/DoDomuDojade/database.sqlite", //ściezka do bazy danych
        "db_type" => "sqlite",
        "announcement_table_name" => "announcements",
        "allowed_fields" => ['title', 'text', 'valid_until'],
        "date_format" => "Y-m-d"
    ]
];