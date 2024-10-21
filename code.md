-- Table: Users
CREATE TABLE Users (
    user_id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    date_of_birth DATE,
    role VARCHAR(20) CHECK (role IN ('student', 'academic_staff', 'admin')),
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    address_id VARCHAR(10),
    created_at DATE
);

-- Table: Students
CREATE TABLE Students (
    student_id SERIAL PRIMARY KEY,
    user_id INT REFERENCES Users(user_id),
    student_type VARCHAR(20),
    department_id VARCHAR(10)
);

-- Table: Program
CREATE TABLE Program (
    program_id VARCHAR(10) PRIMARY KEY,
    program_name VARCHAR(100),
    duration_years INT,
    local_fees NUMERIC(10, 2),
    international_fees NUMERIC(10, 2)
);

-- Table: Program Enrollment
CREATE TABLE Program_Enrollment (
    enrollment_id SERIAL PRIMARY KEY,
    student_id INT REFERENCES Students(student_id),
    program_id VARCHAR(10) REFERENCES Program(program_id),
    enrollment_date DATE,
    current_year INT,
    expected_graduation DATE,
    status VARCHAR(50)
);

-- Table: Student Modules
CREATE TABLE Student_Modules (
    student_id INT REFERENCES Students(student_id),
    module_id VARCHAR(10),
    start_date DATE,
    end_date DATE,
    status VARCHAR(20),
    PRIMARY KEY (student_id, module_id)
);

-- Table: Student Finance
CREATE TABLE Student_Finance (
    finance_id SERIAL PRIMARY KEY,
    student_id INT REFERENCES Students(student_id),
    program_id VARCHAR(10) REFERENCES Program(program_id),
    academic_year VARCHAR(10),
    total_yearly_fee NUMERIC(10, 2),
    semester VARCHAR(20),
    installment_amount NUMERIC(10, 2),
    amount_due NUMERIC(10, 2),
    amount_paid NUMERIC(10, 2),
    status VARCHAR(20),
    due_date DATE
);

-- Table: Exam
CREATE TABLE Exam (
    exam_id VARCHAR(10) PRIMARY KEY,
    module_id VARCHAR(10),
    exam_date DATE,
    start_time TIME,
    end_time TIME,
    location VARCHAR(100)
);

-- Table: Academic Staff
CREATE TABLE Academic_Staff (
    staff_id SERIAL PRIMARY KEY,
    user_id INT REFERENCES Users(user_id),
    department_id VARCHAR(10)
);

-- Table: Admin Staff
CREATE TABLE Admin_Staff (
    staff_id SERIAL PRIMARY KEY,
    user_id INT REFERENCES Users(user_id)
);

-- Table: Student Result
CREATE TABLE Student_Result (
    student_id INT REFERENCES Students(student_id),
    exam_id VARCHAR(10) REFERENCES Exam(exam_id),
    score NUMERIC(5, 2),
    grade VARCHAR(2),
    graded_by INT REFERENCES Academic_Staff(staff_id),
    PRIMARY KEY (student_id, exam_id)
);

-- Table: Notifications
CREATE TABLE Notifications (
    notification_id SERIAL PRIMARY KEY,
    user_id INT REFERENCES Users(user_id),
    message TEXT,
    created_at TIMESTAMP,
    is_read BOOLEAN
);

-- Table: Modules
CREATE TABLE Modules (
    module_id VARCHAR(10) PRIMARY KEY,
    module_name VARCHAR(100),
    department_id VARCHAR(10),
    year INT,
    credits INT,
    staff_id INT REFERENCES Admin_Staff(staff_id)
);

-- Table: Department
CREATE TABLE Department (
    department_id VARCHAR(10) PRIMARY KEY,
    department_name VARCHAR(100),
    head_of_department VARCHAR(50)
);

-- Table: Address
CREATE TABLE Address (
    address_id VARCHAR(10) PRIMARY KEY,
    street VARCHAR(100),
    postcode VARCHAR(10),
    city VARCHAR(50),
    state VARCHAR(50),
    country VARCHAR(50)
);
