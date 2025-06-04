CREATE EXTENSION IF NOT EXISTS postgres_fdw;
-- Grant all privileges on the database to the user
GRANT ALL PRIVILEGES ON DATABASE cs_database TO postgres;

-- Grant all privileges on the schema (usually 'public')
GRANT ALL PRIVILEGES ON SCHEMA public TO postgres;

-- Grant all privileges on existing tables (if any)
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO postgres;

-- Grant all privileges on sequences (required for Django migrations)
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO postgres;


CREATE EXTENSION IF NOT EXISTS postgres_fdw SCHEMA PUBLIC;
GRANT USAGE ON FOREIGN DATA WRAPPER postgres_fdw TO postgres; --(Target User)

CREATE SERVER central_server
FOREIGN DATA WRAPPER postgres_fdw
OPTIONS (host '127.0.0.1', port '6432', dbname 'central_database');
ALTER SERVER central_server OWNER TO postgres;--(Target User)

CREATE USER MAPPING FOR postgres
SERVER central_server
OPTIONS (user 'postgres', password 'kong9983');

GRANT USAGE ON SCHEMA public TO postgres;
GRANT SELECT ON ALL TABLES IN SCHEMA public TO postgres;

CREATE TYPE role_enum AS ENUM ('global_admin', 'department_admin', 'lecturer','student');
CREATE FOREIGN TABLE role (
    role_id SERIAL,
    role_name role_enum
)
SERVER central_server
OPTIONS (schema_name 'public', table_name 'role');


CREATE FOREIGN TABLE if not exists year_semester (
    year_semester_id VARCHAR(10),
    academic_year VARCHAR(10),
    semester_name VARCHAR(15),
    start_date DATE,
    end_date DATE
)
SERVER central_server
OPTIONS (schema_name 'public', table_name 'year_semester');

CREATE FOREIGN TABLE departments (
    department_id VARCHAR(10),
    department_name VARCHAR(100)
)
SERVER central_server
OPTIONS (schema_name 'public', table_name 'departments');

CREATE FOREIGN TABLE users (
    user_id BIGSERIAL,
    username VARCHAR(10),
    password_hash VARCHAR(255),
    role_id INT
)
SERVER central_server
OPTIONS (schema_name 'public', table_name 'users');

-- Address Table
CREATE FOREIGN TABLE user_department (
    user_id BIGINT,
    department_id VARCHAR(10)
)
SERVER central_server
OPTIONS (schema_name 'public', table_name 'user_department');

CREATE TYPE scholarship_type_enum AS ENUM ('merit based', 'need based');
CREATE FOREIGN TABLE scholarship (
    scholarship_id VARCHAR(5),
    scholarship_name VARCHAR(100),
    scholarship_type scholarship_type_enum ,
    amount DECIMAL(6, 2)
)
SERVER central_server
OPTIONS (schema_name 'public', table_name 'scholarship');

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

-- Staff Table
CREATE TYPE status_enum AS ENUM ('active', 'inactive', 'on leave');
CREATE TYPE gender_enum AS ENUM ('male', 'female', 'other');

CREATE TABLE staff (
    staff_id BIGINT PRIMARY KEY,
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
    status status_enum DEFAULT 'active'
--     FOREIGN KEY (user_id) REFERENCES foreign_users(user_id)
);

CREATE TYPE student_type_enum AS ENUM ('international', 'local');
CREATE TYPE student_status_enum AS ENUM ('active', 'inactive', 'completed', 'withdraw','suspended');

-- Students Table
CREATE TABLE students (
    student_id BIGINT PRIMARY KEY,
    user_id BIGSERIAL NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    gender gender_enum NOT NULL,
    date_of_birth DATE NOT NULL,
    personal_email VARCHAR(100) UNIQUE,
    uni_email VARCHAR(100) UNIQUE,
    phone VARCHAR(15),
    address_id VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    nationality VARCHAR(50) NOT NULL,
    student_type student_type_enum DEFAULT 'local',
    education_level VARCHAR(100) NOT NULL,
    institution_name VARCHAR(100) NOT NULL,
    status student_status_enum DEFAULT 'active',
    FOREIGN KEY (address_id) REFERENCES Address(address_id)
);

-- Programme Table
CREATE TABLE programme (
    programme_id VARCHAR(10) PRIMARY KEY,
    programme_name VARCHAR(100) NOT NULL,
    department_id VARCHAR(10) NOT NULL,
    duration_years INT NOT NULL,
    local_fees DECIMAL(10, 2),
    international_fees DECIMAL(10, 2),
    description TEXT,
--     FOREIGN KEY (department_id) REFERENCES Departments(department_id),
    CONSTRAINT chk_duration_positive CHECK (duration_years > 0)
);

CREATE OR REPLACE FUNCTION update_program_end_date()
RETURNS TRIGGER AS $$
DECLARE
    programme_duration INT;
    remaining_years INT;
BEGIN
    -- Get the programme duration from the programme table
    SELECT duration_years INTO programme_duration
    FROM programme
    WHERE programme_id = NEW.programme_id;

    -- Check if the programme_duration is found
    IF programme_duration IS NULL THEN
        RAISE EXCEPTION 'No program found with programme_id %', NEW.programme_id;
    END IF;

    -- Calculate the remaining years to complete the program
    remaining_years := programme_duration - (NEW.current_year - 1);

    -- If the remaining years are less than or equal to 0, set programme_end_date to NULL
    IF remaining_years <= 0 THEN
        NEW.programme_end_date := NULL; -- The program is already completed
    ELSE
        -- Calculate the end date by adding the remaining years to the start date
        NEW.programme_end_date := NEW.programme_start_date + (remaining_years * INTERVAL '1 year');
    END IF;

    -- Return the modified record
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_update_program_end_date
BEFORE INSERT ON programme_enrolment
FOR EACH ROW
EXECUTE FUNCTION update_program_end_date();

ALTER TYPE enrolment_status_enum ADD VALUE 'enrolled';
ALTER TYPE enrolment_status_enum ADD VALUE 'not-enrolled';
UPDATE programme_enrolment
SET status = 'enrolled'
WHERE status = 'enroled';
-- Update existing records if necessary
UPDATE programme_enrolment
SET status = 'not-enrolled'
WHERE status = 'not-enroled';

ALTER TABLE programme_enrolment
ALTER COLUMN status SET DEFAULT 'not-enrolled';

CREATE TYPE enrolment_status_enum AS ENUM ('enroled', 'in-progress','not-enroled');

ALTER TABLE programme_enrolment
ADD COLUMN programme_start_date DATE NOT NULL;
ALTER TABLE programme_enrolment
ADD COLUMN programme_end_date DATE NOT NULL;
ALTER TABLE programme_enrolment
ALTER COLUMN enrolment_date SET DEFAULT CURRENT_DATE;
CREATE SEQUENCE enroled_prog_seq START 1;
Alter SEQUENCE enroled_prog_seq START with 1;



-- Programme Enrolment Table
CREATE TABLE programme_enrolment (
    enrolment_id VARCHAR(10) PRIMARY KEY DEFAULT 'CSE' || LPAD(nextval('enroled_prog_seq')::TEXT, 6, '0'), -- Unique ID for each enrollment record
    student_id BIGINT NOT NULL,
    programme_id VARCHAR(10) NOT NULL,
    programme_start_date DATE NOT NULL,
    programme_end_date DATE NOT NULL,
    academic_year VARCHAR(10) NOT NULL,
    enrolment_date DATE NOT NULL DEFAULT CURRENT_DATE,  -- Set default to current date
    current_year INT,
    status enrolment_status_enum DEFAULT 'not-enrolled',
    progress_step INT DEFAULT 0,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (programme_id) REFERENCES programme(programme_id)
);

-- CREATE OR REPLACE FUNCTION set_enrolment_id()
-- RETURNS TRIGGER AS $$
-- BEGIN
--     NEW.enrolment_id := 'CSE' || LPAD(nextval('enroled_prog_seq')::TEXT, 6, '0');
--     RETURN NEW;
-- END;
-- $$ LANGUAGE plpgsql;
--
-- CREATE TRIGGER enrolment_id_trigger
-- BEFORE INSERT ON programme_enrolment
-- FOR EACH ROW
-- EXECUTE FUNCTION set_enrolment_id();
CREATE TYPE enrolment_status_enum AS ENUM ('completed', 'in-progress');

-- Indexes for ProgramEnrolment Table
CREATE INDEX idx_program_enrolment_student_id ON programme_enrolment(student_id);

CREATE TYPE finance_status_enum AS ENUM ('not-paid', 'partially-paid', 'fully-paid');
-- Student Finance Table
CREATE SEQUENCE finance_id_seq START 1;

CREATE TABLE student_finance (
    finance_id VARCHAR(10) PRIMARY KEY DEFAULT 'CSF' || LPAD(nextval('finance_id_seq')::TEXT, 6, '0'),
    student_id BIGINT NOT NULL,
    programme_id VARCHAR(10) NOT NULL,
    academic_year VARCHAR(10) NOT NULL,
    base_fees DECIMAL(10, 2) NOT NULL,
    scholarship_amount DECIMAL(10, 2) DEFAULT 0.00,
    amount_paid DECIMAL(10, 2) DEFAULT 0.00,
    due_date DATE,
    status finance_status_enum DEFAULT 'not-paid',
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (programme_id) REFERENCES programme(programme_id),
    CONSTRAINT chk_base_fees CHECK (base_fees >= 0),
    CONSTRAINT chk_scholarship_amount CHECK (scholarship_amount >= 0),
    CONSTRAINT chk_amount_paid CHECK (amount_paid >= 0)
);
CREATE INDEX idx_student_finance_student_id ON student_finance(student_id);

SELECT
    finance_id,
    (base_fees - scholarship_amount) AS amount_due
FROM student_finance;

CREATE OR REPLACE FUNCTION populate_student_finance()
RETURNS TRIGGER AS $$
DECLARE
    v_student_type VARCHAR(20);
    v_local_fees DECIMAL(10, 2);
    v_international_fees DECIMAL(10, 2);
    v_base_fees DECIMAL(10, 2);
    v_scholarship_amount DECIMAL(10, 2);
    v_due_date DATE;
BEGIN
    -- Fetch student type and fees from related tables
    SELECT s.student_type, p.local_fees, p.international_fees
    INTO v_student_type, v_local_fees, v_international_fees
    FROM students s
    JOIN programme p ON NEW.programme_id = p.programme_id
    WHERE s.student_id = NEW.student_id;

    -- Determine base fees based on student type
    IF v_student_type = 'local' THEN
        v_base_fees := v_local_fees;
    ELSIF v_student_type = 'international' THEN
        v_base_fees := v_international_fees;
    ELSE
        RAISE EXCEPTION 'Invalid student type: %', v_student_type;
    END IF;

    -- Fetch scholarship amount from scholarship_assignment table
    SELECT COALESCE(SUM(s.amount), 0)
    INTO v_scholarship_amount
    FROM scholarship_assignment sa
    JOIN scholarship s ON sa.scholarship_id = s.scholarship_id
    WHERE sa.student_id = NEW.student_id;

    -- Calculate due date (10th September of the current year)
    v_due_date := DATE (DATE_PART('year', CURRENT_DATE) || '-09-10');

    -- Insert into student_finance table
    INSERT INTO student_finance (
        student_id,
        programme_id,
        academic_year,
        base_fees,
        scholarship_amount,
        amount_paid,
        due_date,
        status
    )
    VALUES (
        NEW.student_id,
        NEW.programme_id,
        NEW.academic_year,
        v_base_fees,
        v_scholarship_amount,
        0.00, -- Default amount_paid
        v_due_date,
        'not-paid' -- Default status
    );

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_populate_student_finance
AFTER INSERT ON programme_enrolment
FOR EACH ROW
EXECUTE FUNCTION populate_student_finance();

CREATE OR REPLACE FUNCTION update_finance_status()
RETURNS TRIGGER AS $$
BEGIN
    NEW.status :=
        CASE
            WHEN NEW.amount_paid = 0 THEN 'not-paid'
            WHEN NEW.amount_paid < (NEW.base_fees - NEW.scholarship_amount) THEN 'partially-paid'
            ELSE 'fully-paid'
        END;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_finance_status
BEFORE INSERT OR UPDATE ON student_finance
FOR EACH ROW
EXECUTE FUNCTION update_finance_status();

INSERT INTO student_finance (
    finance_id,
    student_id,
    programme_id,
    academic_year,
    base_fees,
    scholarship_amount,
    amount_paid,
    due_date,
    status
)
SELECT
    'CSF' || LPAD(nextval('finance_id_seq')::TEXT, 6, '0'), -- Generate finance_id
    31000002, -- student_id
    'UNCS01', -- programme_id
    '2024/5', -- academic_year
    CASE
        WHEN s.student_type = 'local' THEN p.local_fees
        WHEN s.student_type = 'international' THEN p.international_fees
    END AS base_fees, -- Determine base_fees
    COALESCE(0, 0) AS scholarship_amount, -- Fetch scholarship_amount
    0.00 AS amount_paid, -- Default amount_paid
    DATE(DATE_PART('year', '2024-08-17'::DATE) || '-09-10') AS due_date, -- Set due_date to 10th September
    'not paid' AS status -- Default status
FROM students s
JOIN programme p ON p.programme_id = 'UNCS01'
LEFT JOIN scholarship_assignment sa ON sa.student_id = 31000002
WHERE s.student_id = 31000002;

DROP TABLE payments;
CREATE SEQUENCE payment_id_seq START 1;
-- Payments Table
CREATE TABLE payments (
    payment_id VARCHAR(10) PRIMARY KEY DEFAULT 'CSP' || LPAD(nextval('payment_id_seq')::TEXT, 6, '0'),
    finance_id VARCHAR(10) NOT NULL,
    payment_amount DECIMAL(10, 2) NOT NULL,
    payment_date DATE NOT NULL,
    FOREIGN KEY (finance_id) REFERENCES student_finance(finance_id)
);

-- Scholarship Assignment Table
CREATE TABLE scholarship_assignment (
    scholarship_id VARCHAR(5),
    student_id BIGINT,
    PRIMARY KEY (scholarship_id, student_id),
--     FOREIGN KEY (scholarship_id) REFERENCES scholarship(scholarship_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(student_id)
);
-- Create sequences for timetable_id
CREATE SEQUENCE module_timetable_id_seq START 1;
-- Create sequences for exam_id
ALTER SEQUENCE exam_id_seq RESTART WITH 1;
CREATE SEQUENCE exam_id_seq START 1;
-- Create sequences for assignment_id
CREATE SEQUENCE assignment_id_seq START 1;
-- Create sequences for grade_id
CREATE SEQUENCE grade_id_seq START 1;
-- Create modules table

CREATE TABLE modules (
    module_id VARCHAR(10) PRIMARY KEY,
    module_name VARCHAR(100) NOT NULL,
    department_id VARCHAR(10),
    semester VARCHAR(5) CHECK (semester IN ('1', '2', '1 & 2')),
    level INT,
    credits INT,
    exam_weight DECIMAL(5, 2),
    assignment_weight DECIMAL(5, 2)
);
ALTER TABLE modules
ADD COLUMN  available_slots INT;

ALTER TABLE modules
ADD COLUMN prerequisite_module_id VARCHAR(10);
ALTER TABLE modules
ADD FOREIGN KEY (prerequisite_module_id) REFERENCES modules(module_id);


UPDATE modules SET available_slots = available_slots - 1 WHERE module_id = :module_id;
-- Update the available_slots to be 50 for all records
UPDATE modules
SET available_slots = 50;

CREATE TYPE module_type_enum AS ENUM ('Compulsory', 'Optional');
ALTER TABLE programme_module
ALTER COLUMN module_type module_type_enum DEFAUlT 'Compulsory';
-- Create programme_module table

-- Step 1: Create the enum type if not already created
CREATE TYPE module_type_enum AS ENUM ('Compulsory', 'Optional');

CREATE TABLE programme_module (
    programme_id VARCHAR(10),
    module_id VARCHAR(10),
    module_type module_type_enum DEFAULT 'Compulsory',
    PRIMARY KEY (programme_id, module_id),
    FOREIGN KEY (programme_id) REFERENCES programme(programme_id),
    FOREIGN KEY (module_id) REFERENCES modules(module_id) ON DELETE CASCADE
);

CREATE TABLE assigned_lecturers (
    staff_id BIGINT NOT NULL,
    module_id VARCHAR(10) NOT NULL,
    PRIMARY KEY (staff_id, module_id),
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id),
    FOREIGN KEY (module_id) REFERENCES modules(module_id)
);

DELETE FROM student_modules
    WHERE student_id = :student_id
      AND module_id = :module_id
      AND academic_year = :academic_year;
-- Create moduleTimetable table
CREATE TABLE module_timetable (
    timetable_id VARCHAR(10) PRIMARY KEY DEFAULT 'CST' || LPAD(nextval('module_timetable_id_seq')::TEXT, 6, '0'),
    module_id VARCHAR(10),
    staff_id BIGINT,
    type VARCHAR(20),
    start_time TIME,
    end_time TIME,
    date DATE,
    location varchar(30),
    FOREIGN KEY (module_id) REFERENCES modules(module_id) ON DELETE CASCADE
);

CREATE TABLE exam (
    exam_id VARCHAR(10) PRIMARY KEY DEFAULT 'CSX' || LPAD(nextval('exam_id_seq')::TEXT, 6, '0'),
    module_id VARCHAR(10),
    exam_date DATE,
    start_time TIME,
    end_time TIME,
    location VARCHAR(100),
    academic_year VARCHAR(10) NOT NULL,
    FOREIGN KEY (module_id) REFERENCES modules(module_id) ON DELETE CASCADE
);

-- Create assignment table
CREATE TABLE assignment (
    assignment_id VARCHAR(10) PRIMARY KEY DEFAULT 'CSA' || LPAD(nextval('assignment_id_seq')::TEXT, 6, '0'),
    module_id VARCHAR(10),
    title VARCHAR(100),
    description TEXT,
    due_date DATE,
    document VARCHAR(50),
    FOREIGN KEY (module_id) REFERENCES modules(module_id) ON DELETE CASCADE
);

CREATE TYPE submission_status_enum AS ENUM ('in-progress', 'submitted', 'overdue');
CREATE SEQUENCE submission_id_seq start 1;

CREATE TABLE submission (
    submission_id VARCHAR(10) PRIMARY KEY DEFAULT 'CSS' || LPAD(nextval('submission_id_seq')::TEXT, 6, '0'),
    assignment_id VARCHAR(10) NOT NULL,
    student_id BIGINT NOT NULL,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    file_path VARCHAR(255), -- Path to the uploaded file
    status submission_status_enum DEFAULT 'in-progress', -- Status of the submission
    FOREIGN KEY (assignment_id) REFERENCES assignment(assignment_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);
SELECT DISTINCT programme_name FROM programme;
-- Create grade table
CREATE TABLE grade (
    grade_id VARCHAR(10) PRIMARY KEY DEFAULT 'CSG' || LPAD(nextval('grade_id_seq')::TEXT, 6, '0'),
    module_id VARCHAR(10),
    student_id BIGINT,
    assignment_marks DECIMAL(5, 2),
    exam_marks DECIMAL(5, 2),
    total_marks DECIMAL(5, 2),
    grade VARCHAR(5),
    academic_year VARCHAR(10) NOT NULL,
    FOREIGN KEY (module_id) REFERENCES modules(module_id) ON DELETE CASCADE
);

SELECT s.*, sp.programme_id
FROM programme_enrolment sp
JOIN students s ON sp.student_id = s.student_id
JOIN programme p ON sp.programme_id = p.programme_id
WHERE sp.programme_id = 'p.programme_id';

ALTER TYPE module_enrol_status ADD VALUE 'Enrolled';

UPDATE student_modules
SET status = 'Enrolled'
WHERE status = 'Enroled';

ALTER TABLE student_modules
ALTER COLUMN status SET DEFAULT 'Enrolled';

CREATE TYPE module_enrol_status AS ENUM ('Enrolled', 'Completed');
-- Create student_modules table
CREATE TABLE student_modules (
    student_id BIGINT,
    module_id VARCHAR(10),
    academic_year VARCHAR(10) NOT NULL,
    status module_enrol_status DEFAULT 'Enrolled', -- Status (e.g., "enrolled", "completed")
    PRIMARY KEY (student_id, module_id),
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(module_id)
);

-- Create academic_tutor_assigned table
CREATE TABLE academic_tutor_assigned (
    student_id BIGINT,
    staff_id BIGINT,
    PRIMARY KEY (student_id, staff_id)
);

CREATE TYPE request_status AS ENUM ('pending', 'approved', 'rejected');

ALTER TABLE disability_requests
ADD COLUMN status request_status DEFAULT 'pending';

CREATE TABLE disability_requests (
    disability_id VARCHAR(10) PRIMARY KEY,
    student_id BIGINT NOT NULL,
    disability_type VARCHAR(100) NOT NULL,
    requested_accommodation VARCHAR(255) NOT NULL,
    document VARCHAR(255),
    status request_status DEFAULT 'pending',
    FOREIGN KEY (student_id) REFERENCES students(student_id)
);


CREATE TABLE ec_requests (
    ec_id VARCHAR(10) PRIMARY KEY,
    student_id BIGINT NOT NULL,
    ec_type VARCHAR(50) NOT NULL,
    ec_description TEXT NOT NULL,
    supporting_document VARCHAR(255) NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status request_status DEFAULT 'pending',
    FOREIGN KEY (student_id) REFERENCES students(student_id)
);
