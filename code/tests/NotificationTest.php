<?php
use PHPUnit\Framework\TestCase;

class NotificationTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('pgsql:host=localhost;dbname=testing', 'postgres', 'kong9983');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    }

    public function testEmailNotificationOnEnrolmentCompletion()
    {
        // Simulate student enrolment
        $student_id = 1001;  // Example student ID
        $enrolment_status = 'completed';  // Simulate successful enrolment
    
        // Trigger enrolment completion action (you'll need to implement this in your system)
        $this->pdo->prepare("UPDATE programme_enrolment SET status = ? WHERE student_id = ?")
            ->execute([$enrolment_status, $student_id]);
    
        // Simulate sending email (you could use a mock email service)
        $email_sent = $this->sendEnrolmentEmailNotification($student_id);
    
        // Assert that the email is sent
        $this->assertTrue($email_sent, "Enrolment completion email was not sent.");
    }
    
    public function testEmailPaymentReminder()
{
    $student_id = 1001;  // Example student ID
    $payment_due_date = date('Y-m-d', strtotime("+1 week"));  // 1 week from now
    $payment_status = 'pending';  // Simulate pending payment

    // Update payment status for the student
    $this->pdo->prepare("UPDATE payments SET due_date = ?, status = ? WHERE student_id = ?")
        ->execute([$payment_due_date, $payment_status, $student_id]);

    // Simulate sending reminder email (you could use a mock email service)
    $email_sent = $this->sendPaymentReminderEmail($student_id, $payment_due_date);

    // Assert that the email reminder is sent
    $this->assertTrue($email_sent, "Payment reminder email was not sent.");
}

}
