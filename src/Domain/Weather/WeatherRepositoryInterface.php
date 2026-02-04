<?php

namespace App\Domain\Weather;

interface WeatherRepositoryInterface
{
    public function add(WeatherSnapshot $snapshot): int;

    public function fetchLatest(): ?WeatherSnapshot;
}
