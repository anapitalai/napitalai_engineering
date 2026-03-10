<?php
/**
 * PHP Email Form - uses PHPMailer for SMTP delivery
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';

class PHP_Email_Form {
  public $to = '';
  public $from_name = '';
  public $from_email = '';
  public $subject = '';
  public $ajax = false;
  public $smtp = [];

  private $messages = [];

  public function add_message($value, $label, $size = 0) {
    $this->messages[] = ['label' => $label, 'value' => $value, 'size' => $size];
  }

  public function send() {
    // Basic validation
    if (empty($this->to) || empty($this->from_email) || empty($this->subject)) {
      return 'Error: Missing required fields.';
    }

    if (!filter_var($this->from_email, FILTER_VALIDATE_EMAIL)) {
      return 'Error: Invalid sender email address.';
    }

    // Build message body
    $body = '';
    foreach ($this->messages as $msg) {
      if ($msg['size'] > 0) {
        $body .= '<p><strong>' . htmlspecialchars($msg['label']) . ':</strong><br>' . nl2br(htmlspecialchars($msg['value'])) . '</p>';
      } else {
        $body .= '<p><strong>' . htmlspecialchars($msg['label']) . ':</strong> ' . htmlspecialchars($msg['value']) . '</p>';
      }
    }

    try {
      $mail = new PHPMailer(true);

      if (!empty($this->smtp)) {
        $mail->isSMTP();
        // Strip any scheme prefix from host (e.g. https://)
        $host = preg_replace('#^https?://#', '', $this->smtp['host'] ?? '');
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->smtp['username'] ?? '';
        $mail->Password   = $this->smtp['password'] ?? '';
        $port = intval($this->smtp['port'] ?? 587);
        $mail->Port       = $port;
        $mail->SMTPSecure = ($port === 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
      } else {
        $mail->isSendmail();
      }

      // Use the SMTP username as the envelope sender (required by most SMTP servers).
      // Put the client's email in Reply-To so replies go directly to them.
      $smtpUser = $this->smtp['username'] ?? $this->from_email;
      $mail->setFrom($smtpUser, $this->from_name);
      $mail->addAddress($this->to);
      $mail->addReplyTo($this->from_email, $this->from_name);

      $mail->isHTML(true);
      $mail->Subject = $this->subject;
      $mail->Body    = $body;
      $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));

      $mail->send();
      return 'OK';
    } catch (Exception $e) {
      return 'Error: ' . $mail->ErrorInfo;
    }
  }
}
