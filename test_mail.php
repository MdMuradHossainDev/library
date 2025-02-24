<?php
// Test email configuration
$to = "test@example.com";
$subject = "Test Email";
$message = "This is a test email.";
$headers = "From: library@example.com";

if(mail($to, $subject, $message, $headers)) {
    echo "Test email sent successfully!";
} else {
    echo "Failed to send test email.";
}

// Display mail configuration
echo "<pre>";
echo "\nMail Configuration:\n";
echo "sendmail_path: " . ini_get('sendmail_path') . "\n";
echo "SMTP: " . ini_get('SMTP') . "\n";
echo "smtp_port: " . ini_get('smtp_port') . "\n";
echo "</pre>";
?>
