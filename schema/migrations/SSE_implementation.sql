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

-- Client event tracking
CREATE TABLE IF NOT EXISTS event_client_mapping (
                                                    id SERIAL PRIMARY KEY,
                                                    event_id VARCHAR(255) NOT NULL,
                                                    client_id VARCHAR(255) NOT NULL,
                                                    published_at TIMESTAMP NOT NULL,
                                                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                    UNIQUE(event_id, client_id),
                                                    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_event_client_mapping_client_id ON event_client_mapping(client_id);
CREATE INDEX IF NOT EXISTS idx_event_client_mapping_event_id ON event_client_mapping(event_id);
CREATE INDEX IF NOT EXISTS idx_event_client_mapping_published_at ON event_client_mapping(published_at DESC);

-- Event audit log (optional - for tracking event worker)
CREATE TABLE IF NOT EXISTS event_audit_log (
                                               id SERIAL PRIMARY KEY,
                                               channel VARCHAR(255) NOT NULL,
                                               event_type VARCHAR(255),
                                               event_id VARCHAR(255),
                                               processed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_event_audit_log_processed_at ON event_audit_log(processed_at DESC);
CREATE INDEX IF NOT EXISTS idx_event_audit_log_event_id ON event_audit_log(event_id);
