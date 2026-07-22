<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$email = $_POST['email'] ?? '';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email']);
    exit;
}

global $con;

// Fetch active SMTP config
$smtpResult = mysqli_query($con, "SELECT * FROM smtp_configs WHERE is_active=1 LIMIT 1");
if (!$smtpResult || mysqli_num_rows($smtpResult) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No SMTP config']);
    exit;
}
$smtpConfig = mysqli_fetch_assoc($smtpResult);

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->CharSet = 'UTF-8';
    $mail->Host = $smtpConfig['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtpConfig['smtp_user'];
    $mail->Password = $smtpConfig['smtp_pass'];
    $mail->SMTPSecure = ($smtpConfig['smtp_port'] == 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtpConfig['smtp_port'];

    $mail->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Welcome to the Inner Circle — Sri Shringaar';

    $mail->Body = '
    <div style="font-family: Georgia, serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e1e1e1; border-radius: 12px; overflow: hidden;">
        <div style="background: #faf9f6; padding: 40px 30px; text-align: center; border-bottom: 2px solid #C9A96E;">
            <img src="https://srishringarr.com/main_logo.png" alt="Sri Shringaar" style="height: 60px; margin-bottom: 20px;">
            <h1 style="color: #5C1A1B; margin: 0; font-size: 26px; letter-spacing: 1px; font-weight: 500;">Welcome to the Inner Circle</h1>
            <p style="color: #888; margin-top: 8px; font-size: 14px; letter-spacing: 2px; text-transform: uppercase;">Heritage · Craftsmanship · Devotion</p>
        </div>
        
        <div style="padding: 35px 30px; color: #333; line-height: 1.7;">
            <p style="font-size: 16px;">Dear Patron,</p>
            <p style="font-size: 15px;">Thank you for joining our cherished community at <strong>Sri Shringaar Fashion Studio</strong>. We are honoured to have you with us.</p>
            
            <div style="background: #fcfaf7; border-left: 3px solid #C9A96E; padding: 20px 25px; margin: 25px 0;">
                <p style="margin: 0; font-size: 14px; color: #555;">As a member of the Inner Circle, you will receive:</p>
                <ul style="margin: 12px 0 0 0; padding-left: 20px; color: #555; font-size: 14px;">
                    <li style="margin-bottom: 8px;">Early access to new heritage collections</li>
                    <li style="margin-bottom: 8px;">Private viewings and bespoke consultations</li>
                    <li style="margin-bottom: 8px;">Exclusive invitations to studio events</li>
                    <li>Curated stories from our atelier</li>
                </ul>
            </div>
            
            <p style="font-size: 15px;">Whether you are seeking timeless bridal jewellery or couture for a special occasion, our team is here to guide you with care.</p>
            
            <div style="text-align: center; margin: 35px 0 20px 0;">
                <a href="https://srishringarr.com" style="display: inline-block; background: linear-gradient(135deg, #C9A96E, #A88B4A); color: #0A0A0A; text-decoration: none; padding: 14px 32px; border-radius: 30px; font-size: 12px; letter-spacing: 2px; text-transform: uppercase; font-weight: 600;">Explore the Collection</a>
            </div>
            
            <p style="font-size: 14px; color: #888; text-align: center; margin-top: 30px;">With grace,<br><strong style="color: #5C1A1B;">Team Sri Shringaar</strong></p>
        </div>
        
        <div style="background: #1A1A1A; padding: 25px 30px; text-align: center; color: #999;">
            <p style="margin: 0; font-size: 12px;">© ' . date('Y') . ' Sri Shringaar Fashion Studio. All Rights Reserved.</p>
            <p style="margin: 8px 0 0 0; font-size: 11px; color: #666;">Vile Parle, Mumbai · Established 1952</p>
        </div>
    </div>';

    $mail->send();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    error_log("Newsletter welcome email error: " . $mail->ErrorInfo);
    echo json_encode(['status' => 'error', 'message' => 'Email failed']);
}
