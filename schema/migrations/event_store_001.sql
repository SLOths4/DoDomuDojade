-- Event Store table
CREATE TABLE IF NOT EXISTS event (
    id SERIAL PRIMARY KEY,
    event_id VARCHAR(255) UNIQUE NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    payload JSONB NOT NULL,
    occurred_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_event_event_id ON event(event_id);
CREATE INDEX IF NOT EXISTS idx_event_event_type ON event(event_type);
CREATE INDEX IF NOT EXISTS idx_event_occurred_at ON event(occurred_at DESC);
CREATE INDEX IF NOT EXISTS idx_event_payload ON event USING GIN(payload);
