 <?php http_response_code(400); ?>
<!DOCTYPE html>
<html><head><style>
body{margin:0;background:radial-gradient(circle,#0f0f23,#000);font-family:Arial;height:100vh;display:flex;align-items:center;justify-content:center;color:#66d9ff;overflow:hidden}
.library{text-align:center;animation:drift 8s ease-in-out infinite}
.scroll{width:100px;height:60px;background:linear-gradient(45deg,#ffd700,#ffaa00);border-radius:10px;margin:20px auto;position:relative;animation:glow 3s infinite}
.scroll::after{content:'📜';font-size:2rem;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%)}
h1{font-size:1.8rem;margin:15px 0;opacity:0.9}
.subtitle{color:#99e6ff;margin:10px 0;font-style:italic}
.fragments{display:flex;gap:15px;justify-content:center;margin:25px 0;flex-wrap:wrap}
.fragment{background:rgba(102,217,255,0.1);border:2px solid #66d9ff;padding:15px 20px;border-radius:15px;cursor:pointer;transition:all 0.4s;min-width:120px}
.fragment:hover{background:#66d9ff;color:#000;transform:scale(1.05)}
.puzzle{display:none;margin:20px 0;padding:20px;border:2px solid #ffd700;border-radius:15px;background:rgba(255,215,0,0.1)}
.map{display:grid;grid-template-columns:repeat(3,40px);gap:5px;margin:15px auto;justify-content:center}
.path{width:40px;height:40px;background:#333;border:2px solid #66d9ff;border-radius:5px;cursor:pointer;transition:0.3s;display:flex;align-items:center;justify-content:center;font-size:1.2rem}
.path:hover{background:#66d9ff;color:#000}
.correct{background:#00ff88!important;color:#000!important}
.wrong{background:#ff4466!important;animation:shake 0.5s}
.wisdom{display:none;color:#ffd700;font-style:italic;margin:15px 0;opacity:0;animation:fadeIn 2s forwards}
.btn{background:rgba(102,217,255,0.2);border:2px solid #66d9ff;color:#66d9ff;padding:10px 20px;margin:10px;border-radius:20px;text-decoration:none;transition:0.3s}
.btn:hover{background:#66d9ff;color:#000}
@keyframes drift{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
@keyframes glow{0%,100%{box-shadow:0 0 20px #ffd700}50%{box-shadow:0 0 40px #ffd700}}
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-3px)}75%{transform:translateX(3px)}}
@keyframes fadeIn{to{opacity:1}}
</style></head>
<body>
<div class="library">
  <div class="scroll"></div>
  <h1>The Protocol Library</h1>
  <p class="subtitle">The protocol is lost. Reconstruct forgotten knowledge to continue.</p>
  
  <div class="fragments">
    <div class="fragment" onclick="showPuzzle()">🧭 The Map</div>
    <div class="fragment" onclick="showBalance()">⚖️ Balance</div>
    <div class="fragment" onclick="showPattern()">🔄 Pattern</div>
  </div>
  
  <div class="puzzle" id="puzzle">
    <div id="puzzleContent"></div>
    <div class="wisdom" id="wisdom"></div>
  </div>
  
  <a href="#" class="btn" onclick="randomSolve()">Auto Restore</a>
  <a href="/" class="btn">Return to Site</a>
</div>

<script>
const quotes = [
  "Balance isn't equality — it's equilibrium",
  "Every path teaches something different", 
  "Patterns reveal the hidden order",
  "Knowledge is the light in dark spaces",
  "Understanding comes through exploration"
];

const maps = [
  ['🌟','❌','❌','❌','✅','❌','❌','❌','🏠'],
  ['🏠','❌','✅','❌','🌟','❌','✅','❌','❌'],
  ['❌','🌟','❌','✅','❌','❌','❌','✅','🏠']
];

function showPuzzle() {
  const puzzle = document.getElementById('puzzle');
  const content = document.getElementById('puzzleContent');
  const randomMap = maps[Math.floor(Math.random() * maps.length)];
  
  content.innerHTML = `
    <div style="color:#ffd700;margin:10px 0">Find the path from 🏠 to 🌟</div>
    <div class="map" id="map"></div>
  `;
  
  const mapEl = document.getElementById('map');
  randomMap.forEach((cell, i) => {
    const path = document.createElement('div');
    path.className = 'path';
    path.textContent = cell;
    path.onclick = () => checkPath(path, cell === '✅');
    mapEl.appendChild(path);
  });
  
  puzzle.style.display = 'block';
}

function showBalance() {
  const puzzle = document.getElementById('puzzle');
  const content = document.getElementById('puzzleContent');
  const weights = [3, 5, 2, 7, 4];
  const target = weights[Math.floor(Math.random() * weights.length)];
  
  content.innerHTML = `
    <div style="color:#ffd700;margin:10px 0">Balance the scales: Which weighs ${target}kg?</div>
    <div style="display:flex;gap:10px;justify-content:center;margin:15px 0">
      ${weights.map(w => `<div class="path" onclick="checkBalance(this, ${w === target})">${w}kg</div>`).join('')}
    </div>
  `;
  puzzle.style.display = 'block';
}

function showPattern() {
  const puzzle = document.getElementById('puzzle');
  const content = document.getElementById('puzzleContent');
  const patterns = [
    {seq: '2, 4, 6, ?', options: [7,8,9], correct: 8},
    {seq: '1, 4, 9, ?', options: [12,16,18], correct: 16},
    {seq: '5, 10, 15, ?', options: [18,20,22], correct: 20}
  ];
  const p = patterns[Math.floor(Math.random() * patterns.length)];
  
  content.innerHTML = `
    <div style="color:#ffd700;margin:10px 0">Complete: ${p.seq}</div>
    <div style="display:flex;gap:10px;justify-content:center;margin:15px 0">
      ${p.options.map(o => `<div class="path" onclick="checkPattern(this, ${o === p.correct})">${o}</div>`).join('')}
    </div>
  `;
  puzzle.style.display = 'block';
}

function checkPath(el, correct) {
  document.querySelectorAll('.path').forEach(p => p.style.pointerEvents = 'none');
  if(correct) {
    el.classList.add('correct');
    showWisdom();
  } else {
    el.classList.add('wrong');
    setTimeout(() => location.reload(), 1500);
  }
}

function checkBalance(el, correct) {
  document.querySelectorAll('.path').forEach(p => p.style.pointerEvents = 'none');
  if(correct) {
    el.classList.add('correct');
    showWisdom();
  } else {
    el.classList.add('wrong');
    setTimeout(() => location.reload(), 1500);
  }
}

function checkPattern(el, correct) {
  document.querySelectorAll('.path').forEach(p => p.style.pointerEvents = 'none');
  if(correct) {
    el.classList.add('correct');
    showWisdom();
  } else {
    el.classList.add('wrong');
    setTimeout(() => location.reload(), 1500);
  }
}

function showWisdom() {
  const wisdom = document.getElementById('wisdom');
  const quote = quotes[Math.floor(Math.random() * quotes.length)];
  wisdom.textContent = `"${quote}"`;
  wisdom.style.display = 'block';
  
  setTimeout(() => {
    wisdom.innerHTML = '✅ Fragment restored. Knowledge reconstructed.<br><small>Errors reveal what we forgot to learn.</small>';
    setTimeout(() => window.location.href = '/', 2000);
  }, 2000);
}

function randomSolve() {
  const puzzles = [showPuzzle, showBalance, showPattern];
  puzzles[Math.floor(Math.random() * puzzles.length)]();
  setTimeout(() => {
    const correctEl = document.querySelector('.path');
    if(correctEl) {
      correctEl.classList.add('correct');
      showWisdom();
    }
  }, 500);
}
</script>
</body>
</html