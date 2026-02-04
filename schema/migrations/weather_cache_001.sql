CREATE TABLE IF NOT EXISTS weather (
    id SERIAL PRIMARY KEY,
    payload JSONB NOT NULL,
    fetched_on TIMESTAMP NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_weather_fetched_on ON weather(fetched_on DESC);
