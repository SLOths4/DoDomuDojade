-- Migration strategy note:
-- 1) `schema/schema.sql` represents the full target schema for fresh installations.
-- 2) Migrations in `schema/migrations/*.sql` are incremental and must be safe for upgrades from older schemas.
-- Expected state after applying `schema/schema.sql` directly:
--   - `announcement.id` is `varchar(255)` primary key,
--   - `announcement.created_at` exists as `date not null`.
-- Expected state after running this migration on older DBs:
--   - database converges to the same structure as above, without failing when parts already exist.

-- Adapt announcement table in an idempotent and data-safe way.
DO $$
DECLARE
    v_id_data_type text;
    v_has_created_at boolean;
    v_has_pk boolean;
BEGIN
    SELECT data_type
    INTO v_id_data_type
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'announcement'
      AND column_name = 'id';

    IF v_id_data_type IS NULL THEN
        RAISE EXCEPTION 'Column public.announcement.id does not exist';
    END IF;

    -- Type conversion safety:
    -- int/bigint -> varchar is lossless text cast; existing PK uniqueness is preserved.
    IF v_id_data_type <> 'character varying' THEN
        EXECUTE 'ALTER TABLE public.announcement ALTER COLUMN id DROP DEFAULT';
        EXECUTE 'ALTER TABLE public.announcement ALTER COLUMN id TYPE varchar(255) USING id::varchar(255)';
    END IF;

    SELECT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'announcement'
          AND column_name = 'created_at'
    ) INTO v_has_created_at;

    IF NOT v_has_created_at THEN
        EXECUTE 'ALTER TABLE public.announcement ADD COLUMN IF NOT EXISTS created_at date DEFAULT now() NOT NULL';
    END IF;

    SELECT EXISTS (
        SELECT 1
        FROM information_schema.table_constraints
        WHERE table_schema = 'public'
          AND table_name = 'announcement'
          AND constraint_name = 'announcement_pkey'
          AND constraint_type = 'PRIMARY KEY'
    ) INTO v_has_pk;

    -- Ensure PK is exactly on `id` (safe when existing data already had PK semantics).
    IF v_has_pk THEN
        EXECUTE 'ALTER TABLE public.announcement DROP CONSTRAINT announcement_pkey';
    END IF;

    EXECUTE 'ALTER TABLE public.announcement ADD CONSTRAINT announcement_pkey PRIMARY KEY (id)';
END $$;

DROP SEQUENCE IF EXISTS announcement_id_seq;

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
