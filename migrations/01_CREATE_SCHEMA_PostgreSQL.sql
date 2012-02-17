-- --------------------------------------------------------
-- Run this script with psql; e.g.,
-- $ psql -f ./01_CREATE_SCHEMA_PostgreSQL.sql 
-- or by psql terminal, e.g.,
-- $ psql
-- postgres-# \i ./01_CREATE_SCHEMA_PostgreSQL.sql
-- 
-- * NOTE THAT THIS SCRIPT CREATES ROLE vpu WHOSE 
-- ENCRYPTED PASSWORD IS ALSO vpu THEN CREATES 
-- DATABASE vpu AS OF ROLE vpu
-- -------------------------------------------------------

CREATE ROLE vpu LOGIN ENCRYPTED PASSWORD 'vpu';
CREATE DATABASE vpu OWNER vpu ENCODING 'UTF-8' LC_COLLATE 'zh_TW.UTF-8';
\c vpu vpu;

-- --------------------------------------------------------

--
-- Table structure for table `SuiteResult`
--

CREATE TABLE SuiteResult (
  id integer NOT NULL,
  run_date timestamp NOT NULL,
  failed integer NOT NULL,
  incomplete integer NOT NULL,
  skipped integer NOT NULL,
  success integer NOT NULL
);

CREATE SEQUENCE SuiteResult_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER TABLE public.SuiteResult_id_seq OWNER TO anygoose;
ALTER SEQUENCE SuiteResult_id_seq OWNED BY SuiteResult.id;
ALTER TABLE SuiteResult ALTER COLUMN id SET DEFAULT nextval('SuiteResult_id_seq'::regclass);
ALTER TABLE ONLY SuiteResult ADD CONSTRAINT SuiteResult_pkey PRIMARY KEY (id);


-- --------------------------------------------------------

--
-- Table structure for table `TestResult`
--

CREATE TABLE TestResult (
  id integer NOT NULL,
  run_date timestamp NOT NULL,
  failed integer NOT NULL,
  incomplete integer NOT NULL,
  skipped integer NOT NULL,
  success integer NOT NULL
);

CREATE SEQUENCE TestResult_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER TABLE public.TestResult_id_seq OWNER TO anygoose;
ALTER SEQUENCE TestResult_id_seq OWNED BY TestResult.id;
ALTER TABLE TestResult ALTER COLUMN id SET DEFAULT nextval('TestResult_id_seq'::regclass);
ALTER TABLE ONLY TestResult ADD CONSTRAINT TestResult_pkey PRIMARY KEY (id);
