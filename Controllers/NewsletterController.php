<?php
namespace API\Controllers;

use API\Models\NewsletterModel;

class NewsletterController {
    private $model;

    public function __construct() {
        $this->model = new NewsletterModel();
    }

    public function subscribe() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->response(['status' => 'error', 'message' => 'Invalid request method'], 405);
        }

        $email = $_POST['email'] ?? '';
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->response(['status' => 'error', 'message' => 'Please provide a valid email address'], 400);
        }

        $result = $this->model->subscribe($email);

        // Send welcome email if newly subscribed (fire and forget)
        if ($result['status'] === 'success') {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];
            $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/');
            $emailUrl = $protocol . $host . $uri . '/send-newsletter-welcome.php';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $emailUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['email' => $email]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_exec($ch);
            curl_close($ch);
        }

        $this->response($result);
    }

    private function response($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
