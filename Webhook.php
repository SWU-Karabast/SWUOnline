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

  // Decode the payload to extract branch information
  $data = json_decode($payload, true);

  // Check if the push is to the 'main' branch
  if ($data['ref'] !== 'refs/heads/main') {
    http_response_code(200); // No action taken
    die('Not a push to the main branch');
  }

  // Execute `git pull` in the current directory
  // Ensure that sudo is configured to allow the "daemon" user to run git pull without a password prompt
  // by adding the following line to the sudoers file: "daemon ALL=(ALL) NOPASSWD: /usr/bin/git"
  exec('sudo git pull origin main 2>&1', $output, $result);

  // Respond with the output of the `git pull` command
  http_response_code($result === 0 ? 200 : 500); // 200 OK if success, 500 Internal Server Error if failure
  echo implode("\n", $output);
?>
