 <?php http_response_code(403); ?>
<!DOCTYPE html>
<html><head><style>
body{margin:0;background:linear-gradient(135deg,#2c1810,#8b4513);font-family:Arial;height:100vh;display:flex;align-items:center;justify-content:center;color:#ffd700;overflow:hidden}
.club{text-align:center;animation:glow 4s infinite}
.gate{width:150px;height:100px;border:4px solid #ffd700;border-radius:15px;margin:20px auto;position:relative;background:rgba(0,0,0,0.7);animation:shimmer 3s infinite}
.gate::before{content:'🚪';font-size:4rem;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);filter:blur(1px)}
.rope{width:200px;height:5px;background:linear-gradient(90deg,#8b0000,#dc143c);margin:10px auto;border-radius:10px}
h1{font-size:2rem;margin:15px 0;text-shadow:0 0 20px #ffd700}
.message{font-style:italic;margin:15px 0;opacity:0.9}
.doorman{font-size:1.1rem;color:#daa520;margin:20px 0;background:rgba(0,0,0,0.3);padding:15px;border-radius:10px}
.btn{background:rgba(255,215,0,0.3);border:2px solid #ffd700;color:#ffd700;padding:12px 25px;margin:10px;border-radius:25px;text-decoration:none;transition:0.3s}
.btn:hover{background:#ffd700;color:#2c1810;transform:translateY(-2px)}
@keyframes glow{0%,100%{filter:brightness(1)}50%{filter:brightness(1.3)}}
@keyframes shimmer{0%,100%{box-shadow:0 0 30px #ffd700}50%{box-shadow:0 0 50px #ffd700,inset 0 0 20px rgba(255,215,0,0.3)}}
</style></head>
<body>
<div class="club">
  <div class="gate"></div>
  <div class="rope"></div>
  <h1>The Velvet Cage</h1>
  <p class="message">You're not on the list</p>
  <div class="doorman">🎩 "Access here is exclusive. Apply if you dare."</div>
  <a href="https://kritarth.byethost5.com/Error_Pages/form_403.php" class="btn">Apply for Access</a>
  <a href="/" class="btn">Return to Safety</a>
</div>
</body>
</html