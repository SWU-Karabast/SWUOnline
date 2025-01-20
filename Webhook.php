<?php
  // Configuration
  $secret = getenv('WEBHOOK_SECRET'); // Get secret from environment variable
  $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
  $payload = file_get_contents('php://input');

  // Validate the signature to ensure the request is from GitHub
  $hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);
  if (!hash_equals($hash, $signature)) {
    http_response_code(403); // Forbidden
    die('Invalid signature');
  }

  // Execute `git pull` in the current directory
  exec('git pull origin main 2>&1', $output, $result);

  // Respond with the output of the `git pull` command
  http_response_code($result === 0 ? 200 : 500); // 200 OK if success, 500 Internal Server Error if failure
  echo implode("\n", $output);
?>
