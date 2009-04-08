-- Table: spiff

-- DROP TABLE spiff;

CREATE TABLE spiff
(
  id serial NOT NULL,
  json text,
  identity_id integer,
  session_id character(40),
  url text,
  derived_from integer,
  title text,
  annotation text,
  CONSTRAINT spiff_pkey PRIMARY KEY (id),
  CONSTRAINT spiff_url_key UNIQUE (url)
)
WITH (OIDS=FALSE);

-- Index: derived_from_index

-- DROP INDEX derived_from_index;

CREATE INDEX derived_from_index
  ON spiff
  USING btree
  (derived_from);

-- Index: identity_index

-- DROP INDEX identity_index;

CREATE INDEX identity_index
  ON spiff
  USING btree
  (identity_id);

-- Index: session_index

-- DROP INDEX session_index;

CREATE INDEX session_index
  ON spiff
  USING btree
  (session_id);

-- Table: spifflist

-- DROP TABLE spifflist;

CREATE TABLE spifflist
(
  id serial NOT NULL,
  identity_id integer,
  json text,
  session_id character(40),
  CONSTRAINT spifflist_pkey PRIMARY KEY (id),
  CONSTRAINT spifflist_identity_id_key UNIQUE (identity_id),
  CONSTRAINT spifflist_session_id_key UNIQUE (session_id)
)
WITH (OIDS=FALSE);
