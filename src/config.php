<?php /** @noinspection SpellCheckingInspection */
$project_directory = dirname(__DIR__);
return [
    "Airly" => [
        "AirlyApiKey" => "A4gV88G4CKrt7J79a9t6Nwn5VHRvGKC4",
        "AirlyLocationId" => "63816",
        "AirlyEndpoint" => "https://airapi.airly.eu/v2/measurements/location?locationId="
    ],
    "Metar" => [
      "metar_url" => "https://awiacja.imgw.pl/metar00.php?airport=",
        "airport_icao" => "EPPO"
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
            "url" => "https://calendar.google.com/calendar/ical/f6cff184f8c37b50bf51f855f6480537d811fc68e60d1282f824d3524e777ffc%40group.calendar.google.com/private-f030d73c8fc9a45f4fd8970d6dd61f84/basic.ics",
            "description" => "Szkolne kalendarium"
        ]
    ],
    "Database" => [
        "db_host" => "sqlite:" . realpath(__DIR__ . '/../database.sqlite'), //ściezka do bazy danych
        "announcement_table_name" => "announcements",
        "users_table_name" => "users",
        "allowed_fields" => ['title', 'text', 'date','valid_until', 'user_id'],
        "date_format" => "Y-m-d"
    ]
];