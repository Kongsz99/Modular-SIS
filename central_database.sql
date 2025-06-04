-- create database share_database;
create database central_database;
CREATE EXTENSION IF NOT EXISTS postgres_fdw;

CREATE SEQUENCE enrolment_id_seq START 1;
CREATE SEQUENCE payment_id_seq START 1;
CREATE SEQUENCE disability_id_seq START 1;
CREATE SEQUENCE finance_id_seq START 1;
CREATE SEQUENCE scholarships_id_seq START 1;
CREATE SEQUENCE ec_id_seq START 1;

CREATE SEQUENCE address_id_seq START 1;
CREATE SEQUENCE student_id_seq START 31000001;
CREATE SEQUENCE staff_id_seq START 60001;
CREATE SEQUENCE admin_id_seq START 90001;
ALTER SEQUENCE disability_id_seq RESTART WITH 1;

CREATE TYPE role_enum AS ENUM ('global_admin', 'department_admin', 'lecturer','student');

CREATE TABLE role (
    role_id SERIAL PRIMARY KEY,
    role_name role_enum
);

create table permissions (
  permissions_id BIGINT PRIMARY KEY,
  permission_name varchar(100) not null unique
);

create table role_permissions (
  role_id int not null,   -- Use int (or serial) for smaller dataset
  permission_id int not null,  -- Use int (or serial) for smaller dataset
  primary key (role_id, permission_id),
  foreign key (role_id) references role (role_id),
  foreign key (permission_id) references permissions (permissions_id)
);

CREATE TABLE departments (
    department_id VARCHAR(10) PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL
);

CREATE TABLE users (
    user_id BIGSERIAL NOT NULL PRIMARY KEY,
    username VARCHAR(10) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
--     department_id VARCHAR(10),
--     FOREIGN KEY (department_id) REFERENCES departments(department_id),
    FOREIGN KEY (role_id) REFERENCES Role(role_id)
);

CREATE TABLE IF NOT EXISTS user_department (
    user_id BIGINT NOT NULL,
    department_id VARCHAR(10) NOT NULL,
    PRIMARY KEY (user_id, department_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE CASCADE

);

CREATE TABLE address (
    address_id VARCHAR(10) PRIMARY KEY,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(50) NOT NULL,
    state VARCHAR(50),
    postcode VARCHAR(10),
    country VARCHAR(100) NOT NULL
);-- Indexes for Address Table
CREATE INDEX idx_address_city ON Address(city);
CREATE INDEX idx_address_postcode ON Address(postcode);

CREATE TYPE gender_enum AS ENUM ('male', 'female', 'other');
CREATE TYPE status_enum AS ENUM ('active', 'inactive', 'on leave');


CREATE TABLE admin (
    admin_id BIGINT PRIMARY KEY DEFAULT nextval('admin_id_seq'),
    user_id BIGSERIAL NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    gender gender_enum NOT NULL,
    date_of_birth DATE NOT NULL,
    personal_email VARCHAR(100) UNIQUE,
    uni_email VARCHAR(100) UNIQUE,
    phone VARCHAR(15),
    address_id VARCHAR(10),
    start_date DATE,
    status status_enum DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
);

-- YearSemester Table
CREATE TABLE year_semester (
    year_semester_id VARCHAR(10) PRIMARY KEY,
    academic_year VARCHAR(10) NOT NULL,
    semester_name VARCHAR(15) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    CONSTRAINT chk_semester_dates CHECK (start_date < end_date)
);

CREATE TYPE scholarship_type_enum AS ENUM ('merit based', 'need based');
-- Scholarship Table
CREATE TABLE scholarship (
    scholarship_id VARCHAR(5) PRIMARY KEY DEFAULT 'SC' || LPAD(nextval('scholarships_id_seq')::TEXT, 3, '0'),
    scholarship_name VARCHAR(100) NOT NULL UNIQUE,
    scholarship_type scholarship_type_enum NOT NULL,
    amount DECIMAL(6, 2) NOT NULL
);
DEFAULT 'D' || LPAD(nextval('disability_id_seq')::TEXT, 4, '0')
-- Disability Accommodation Table

CREATE OR REPLACE FUNCTION get_formatted_id(seq_name TEXT, prefix TEXT, padding_length INT)
RETURNS TEXT AS $$
DECLARE
    next_val BIGINT;
    formatted_id TEXT;
BEGIN
    -- Get the next value from the sequence
    EXECUTE 'SELECT nextval(''' || seq_name || ''')' INTO next_val;

    -- Format the ID using the prefix and padding
    formatted_id := prefix || LPAD(next_val::TEXT, padding_length, '0');

    RETURN formatted_id;
END;
$$ LANGUAGE plpgsql;
