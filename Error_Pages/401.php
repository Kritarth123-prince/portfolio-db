 <?php http_response_code(401); ?>
<!DOCTYPE html>
<html><head><style>
body{margin:0;background:linear-gradient(45deg,#0a0a0a,#1a1a3e);font-family:Arial;height:100vh;display:flex;align-items:center;justify-content:center;color:#00d4ff;overflow:hidden}
.lab{text-align:center;animation:flicker 4s infinite}
.scanner{width:120px;height:120px;border:3px solid #00d4ff;border-radius:50%;margin:20px auto;position:relative;animation:scan 2s infinite}
.scanner::after{content:'👁️';font-size:3rem;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%)}
h1{font-size:1.8rem;margin:15px 0;color:#ff6b6b}
.subtitle{color:#99e6ff;margin:10px 0;font-style:italic}
.terminal{background:rgba(0,0,0,0.8);border:2px solid #00d4ff;border-radius:10px;padding:15px;margin:20px 0;text-align:left;max-width:350px}
.input{background:#000;border:none;color:#00ff88;padding:8px;margin:5px 0;width:100%;border-radius:5px}
.response{color:#ffd700;margin:10px 0;font-style:italic;min-height:20px}
.btn{background:rgba(0,212,255,0.2);border:2px solid #00d4ff;color:#00d4ff;padding:10px 20px;margin:10px;border-radius:20px;text-decoration:none;transition:0.3s}
.btn:hover{background:#00d4ff;color:#000}
@keyframes scan{0%,100%{transform:scale(1);box-shadow:0 0 20px #00d4ff}50%{transform:scale(1.1);box-shadow:0 0 40px #00d4ff}}
@keyframes flicker{0%,100%{opacity:1}95%{opacity:0.95}96%{opacity:1}97%{opacity:0.98}}
</style></head>
<body>
<div class="lab">
  <div class="scanner"></div>
  <h1>Identity Lab</h1>
  <p class="subtitle">🔍 Scanning credentials... ACCESS DENIED</p>
  
  <div class="terminal">
    <div>Username: <input class="input" id="user" placeholder="Enter username"></div>
    <div>Password: <input class="input" id="pass" type="password" placeholder="Enter password"></div>
    <div class="response" id="ai"></div>
  </div>
  
  <a href="#" class="btn" onclick="testAccess()">Test Access</a>
  <a href="/password_manager/login.php" class="btn">Request Clearance</a>
</div>

<script>
const responses = {
  'admin': 'Admin? In your dreams!',
  'root': 'Not even close, grasshopper',
  'godmode': 'Nice try, Neo',
  'guest': 'You must be new here',
  'test': 'How original... NOT',
  'default': ['Wrong universe, buddy', 'Nope, try again', 'Access still denied', 'Computer says no']
};

const passResponses = {
  '123': "That's everyone's luggage code",
  '1234': "That's everyone's luggage code", 
  'password': 'Really? REALLY?!',
  'admin': 'Even my toaster has better security',
  'qwerty': 'Your keyboard called, it wants originality',
  'default': ['Weak sauce!', 'Try harder!', 'Security level: potato', 'My cat could guess better']
};

const funFacts = [
  'Fun fact: The first password was created at MIT in the 1960s',
  'Tip: Good passwords are like good jokes - hard to guess',
  'Did you know? Most hackers just try "password123"',
  'Pro tip: Your pet\'s name isn\'t secure either'
];

function testAccess() {
  const user = document.getElementById('user').value.toLowerCase();
  const pass = document.getElementById('pass').value.toLowerCase();
  const ai = document.getElementById('ai');
  
  if (!user && !pass) {
    ai.innerHTML = '🤖 "Silent treatment? How mysterious..."';
    return;
  }
  
  let response = '';
  
  if (user) {
    if (responses[user]) {
      response += responses[user];
    } else {
      const random = responses.default[Math.floor(Math.random() * responses.default.length)];
      response += random;
    }
  }
  
  if (pass && user) {
    response += '<br>';
  }
  
  if (pass) {
    if (passResponses[pass]) {
      response += passResponses[pass];
    } else {
      const random = passResponses.default[Math.floor(Math.random() * passResponses.default.length)];
      response += random;
    }
  }
  
  if (Math.random() > 0.7) {
    const fact = funFacts[Math.floor(Math.random() * funFacts.length)];
    response += '<br><small>' + fact + '</small>';
  }
  
  ai.innerHTML = '🤖 ' + response;
  
  // Clear inputs after 3 seconds
  setTimeout(() => {
    document.getElementById('user').value = '';
    document.getElementById('pass').value = '';
  }, 3000);
}

// Auto-respond on Enter key
document.getElementById('pass').addEventListener('keypress', function(e) {
  if (e.key === 'Enter') testAccess();
});
</script>
</body>
</html