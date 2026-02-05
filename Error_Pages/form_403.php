 <?php
// Handle form submission and save log
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $reason) {
        $logEntry = sprintf(
            "[%s] Name: %s | Email: %s | Reason: %s\n",
            date('Y-m-d H:i:s'),
            htmlspecialchars($name, ENT_QUOTES),
            htmlspecialchars($email, ENT_QUOTES),
            htmlspecialchars($reason, ENT_QUOTES)
        );

        file_put_contents('access_requests.log', $logEntry, FILE_APPEND | LOCK_EX);
        $message = "Your request has been logged. We'll get back to you soon.";
    } else {
        $message = "⚠️ Please fill all fields correctly.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Access Request | The Velvet Cage</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Orbitron&display=swap');
  body {
    margin:0; min-height:100vh;
    background: radial-gradient(circle at center, #0a0a0f 0%, #12121a 100%);
    font-family: 'Orbitron', monospace, sans-serif;
    color: #00ffcc;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
  }
  h1 {
    font-weight: 700;
    font-size: 2.5rem;
    letter-spacing: 0.2rem;
    margin-bottom: 1rem;
    color: #ffcc00;
    text-shadow: 0 0 15px #ffcc00aa;
  }
  form {
    background: #11121a;
    padding: 2rem 3rem;
    border-radius: 15px;
    box-shadow: 0 0 40px #00ffcc55;
    max-width: 400px;
    width: 100%;
  }
  label {
    display: block;
    margin-bottom: 0.4rem;
    font-size: 0.9rem;
    letter-spacing: 0.05rem;
  }
  input, textarea {
    width: 100%;
    background: #12121a;
    border: 2px solid #00ffcc;
    color: #00ffcc;
    padding: 0.6rem 0.8rem;
    margin-bottom: 1.2rem;
    border-radius: 8px;
    font-family: inherit;
    font-size: 1rem;
    transition: border-color 0.3s ease;
  }
  input:focus, textarea:focus {
    border-color: #ffcc00;
    outline: none;
  }
  textarea {
    resize: vertical;
    min-height: 80px;
  }
  button {
    background: #ffcc00;
    border: none;
    color: #11121a;
    font-weight: 700;
    font-size: 1.1rem;
    padding: 0.8rem 2rem;
    border-radius: 30px;
    cursor: pointer;
    letter-spacing: 0.1rem;
    box-shadow: 0 0 15px #ffcc0099;
    transition: background-color 0.3s ease;
  }
  button:hover {
    background: #e6b800;
  }
  .message {
    margin-top: 1rem;
    font-size: 1rem;
    font-weight: 600;
    color: #ffcc00;
    text-align: center;
    text-shadow: 0 0 8px #ffcc00aa;
  }
  footer {
    margin-top: 3rem;
    font-size: 0.8rem;
    color: #006655;
  }
</style>
</head>
<body>
  <h1>The Velvet Cage</h1>
  <form method="POST" novalidate>
    <label for="name">Your Name</label>
    <input type="text" id="name" name="name" required placeholder="Enter your full name" autocomplete="name" />
    
    <label for="email">Email Address</label>
    <input type="email" id="email" name="email" required placeholder="you@example.com" autocomplete="email" />
    
    <label for="reason">Why should you be granted access?</label>
    <textarea id="reason" name="reason" required placeholder="Tell us your secret mission..."></textarea>
    
    <button type="submit">Apply for Access</button>
    <?php if ($message): ?>
      <div class="message"><?= $message ?></div>
    <?php endif; ?>
  </form>
  <footer>
    &copy; <?= date('Y') ?> The Velvet Cage – Exclusive Access Portal
  </footer>
</body>
</html>