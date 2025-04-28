<?php
use PHPUnit\Framework\TestCase;

class SupportServicesTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('pgsql:host=localhost;dbname=testing', 'postgres', 'kong9983');
        $this->pdo->beginTransaction();

        // Insert dummy student for testing
        // $this->pdo->exec("INSERT INTO students (student_id, user_id, first_name, last_name, gender, date_of_birth, nationality, education_level, institution_name)
        //                   VALUES (1001, 2001, 'John', 'Doe', 'male', '1999-01-01', 'Local', 'Undergraduate', 'Test University')");
    }

    protected function tearDown(): void
    {
        $this->pdo->rollBack();
    }

    public function testSubmitDisabilityAccommodationRequest()
{
    $disability_id =  rand(10001, 99999);
    $student_id = 1001;  // Using the student ID inserted earlier
    $disability_type = 'Physical Disability';
    $requested_accommodation = 'Wheelchair access';
    $document_path = 'path/to/accommodation_document.pdf';

    // Submit Disability Accommodation request
    $stmt = $this->pdo->prepare("INSERT INTO disability_requests (disability_id, student_id, disability_type, requested_accommodation, document) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$disability_id, $student_id, $disability_type, $requested_accommodation, $document_path]);

    // Verify Disability Accommodation request submission
    $check = $this->pdo->prepare("SELECT * FROM disability_requests WHERE student_id = ?");
    $check->execute([$student_id]);
    $result = $check->fetch();

    $this->assertNotEmpty($result, "Disability accommodation request was not submitted correctly.");
    $this->assertEquals($disability_type, $result['disability_type'], "Disability type does not match.");
    $this->assertEquals($requested_accommodation, $result['requested_accommodation'], "Requested accommodation does not match.");
    $this->assertEquals($document_path, $result['document'], "Document path does not match.");
}

    public function testApproveOrRejectDisabilityAccommodationRequest()
    {
        $disability_id = rand(10001, 99999);
        $student_id = 1001;  // Using the student ID inserted earlier
        $disability_type = 'Physical Disability';
        $requested_accommodation = 'Wheelchair access';
        $document_path = 'path/to/accommodation_document.pdf';

        // Submit Disability Accommodation request
        $stmt = $this->pdo->prepare("INSERT INTO disability_requests (disability_id, student_id, disability_type, requested_accommodation, document) VALUES (?,?,?,?,?)");
        $stmt->execute([$disability_id, $student_id, $disability_type, $requested_accommodation, $document_path]);

        // Approve the Disability Accommodation request (simulating Global Admin action)
        $approval_date = date('Y-m-d H:i:s');
        $global_admin_id = 3001;  // Assuming Global Admin staff ID
        $stmt = $this->pdo->prepare("UPDATE disability_requests SET status = 'approved', request_date = ?, approved_by = ? WHERE student_id = ?");
        $stmt->execute([$approval_date, $global_admin_id, $student_id]);

        // Verify the Disability Accommodation request approval
        $check = $this->pdo->prepare("SELECT * FROM disability_requests WHERE student_id = ?");
        $check->execute([$student_id]);
        $result = $check->fetch();

        $this->assertEquals('approved', $result['status'], "Disability accommodation request was not approved correctly.");
        $this->assertEquals($global_admin_id, $result['approved_by'], "Global Admin approval mismatch.");
    }
    

        public function testSubmitExtenuatingCircumstancesRequest()
    {
        $ec_id = rand(10001, 99999);
        $student_id = 1001;  // Using the student ID inserted earlier
        $ec_type = 'Health';
        $ec_description = 'Hospitalization for 2 weeks';
        $document_path = 'path/to/ec_document.pdf';

        // Submit EC request
        $stmt = $this->pdo->prepare("INSERT INTO ec_requests (ec_id, student_id, ec_type, ec_description, supporting_document) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$ec_id, $student_id, $ec_type, $ec_description, $document_path]);

        // Verify EC request submission
        $check = $this->pdo->prepare("SELECT * FROM ec_requests WHERE student_id = ?");
        $check->execute([$student_id]);
        $result = $check->fetch();

        $this->assertNotEmpty($result, "Extenuating Circumstances request was not submitted correctly.");
        $this->assertEquals($ec_type, $result['ec_type'], "EC type does not match.");
        $this->assertEquals($ec_description, $result['ec_description'], "EC description does not match.");
        $this->assertEquals($document_path, $result['supporting_document'], "Supporting document path does not match.");
    }

        
    public function testApproveOrRejectECRequest()
    {
        $ec_id = rand(10001, 99999);
        $student_id = 1001;  // Using the student ID inserted earlier
        $ec_type = 'Health';
        $ec_description = 'Hospitalization for 2 weeks';
        $document_path = 'path/to/ec_document.pdf';

        // Submit EC request
        $stmt = $this->pdo->prepare("INSERT INTO ec_requests (ec_id, student_id, ec_type, ec_description, supporting_document) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$ec_id, $student_id, $ec_type, $ec_description, $document_path]);

        // Approve the EC request (simulating Global Admin action)
        $approval_date = date('Y-m-d H:i:s');
        $global_admin_id = 3001;  // Assuming Global Admin staff ID
        $stmt = $this->pdo->prepare("UPDATE ec_requests SET status = 'approved', approved_by = ? WHERE student_id = ?");
        $stmt->execute([$global_admin_id, $student_id]);

        // Verify the EC request approval
        $check = $this->pdo->prepare("SELECT * FROM ec_requests WHERE student_id = ?");
        $check->execute([$student_id]);
        $result = $check->fetch();

        $this->assertEquals('approved', $result['status'], "Extenuating Circumstances request was not approved correctly.");
        $this->assertEquals($global_admin_id, $result['approved_by'], "Global Admin approval mismatch.");
    }
}
