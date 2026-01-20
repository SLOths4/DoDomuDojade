-- Adapt announcement table
ALTER TABLE announcement
    ALTER COLUMN id DROP DEFAULT,
    ALTER COLUMN id TYPE varchar(255),
    ADD created_at date default now() not null;

DROP SEQUENCE IF EXISTS announcement_id_seq;

ALTER TABLE announcement
    DROP CONSTRAINT announcement_pkey,
    ADD CONSTRAINT announcement_pkey PRIMARY KEY (id);

-- Events (Event Store)
CREATE TABLE IF NOT EXISTS events (
                                      id SERIAL PRIMARY KEY,
                                      event_id VARCHAR(255) UNIQUE NOT NULL,
                                      event_type VARCHAR(255) NOT NULL,
                                      payload JSONB NOT NULL,
                                      occurred_at TIMESTAMP NOT NULL,
                                      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_events_event_id ON events(event_id);
CREATE INDEX IF NOT EXISTS idx_events_event_type ON events(event_type);
CREATE INDEX IF NOT EXISTS idx_events_occurred_at ON events(occurred_at DESC);
CREATE INDEX IF NOT EXISTS idx_events_payload ON events USING GIN(payload);
