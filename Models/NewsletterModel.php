<?php
namespace API\Models;

use API\Core\Model;

class NewsletterModel extends Model {
    public function subscribe($email) {
        $email = mysqli_real_escape_string($this->db, $email);
        
        // Check if already exists
        $check = $this->query($this->db, "SELECT id FROM newsl WHERE email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            return ['status' => 'exists', 'message' => 'You are already subscribed!'];
        }

        $sql = "INSERT INTO newsl (email) VALUES ('$email')";
        if ($this->query($this->db, $sql)) {
            return ['status' => 'success', 'message' => 'Thank you for joining our studio!'];
        }
        return ['status' => 'error', 'message' => 'Something went wrong. Please try again.'];
    }
}
