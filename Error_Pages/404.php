 <?php http_response_code(404); ?>
<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      margin: 0;
      background: radial-gradient(circle, #1a252f, #2c3e50);
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ecf0f1;
      text-align: center;
      font-family: Arial;
      padding: 10px;
      overflow: hidden;
    }

    .fog {
      position: absolute;
      width: 200%;
      height: 200%;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      animation: drift 15s infinite;
    }

    .fog:nth-child(2) {
      animation-delay: -5s;
      left: -50%;
    }

    .fog:nth-child(3) {
      animation-delay: -10s;
      right: -50%;
    }

    .content {
      position: relative;
      z-index: 2;
      max-width: 90vw;
    }

    .road {
      width: min(150px, 40vw);
      height: min(60px, 15vw);
      background: linear-gradient(90deg, #7f8c8d 0%, rgba(127, 140, 141, 0.3) 70%, transparent);
      margin: 15px auto;
      border-radius: 5px;
      opacity: 0.4;
      position: relative;
    }

    .road::after {
      content: '';
      position: absolute;
      width: 100%;
      height: 2px;
      background: repeating-linear-gradient(90deg, #bdc3c7 0px, #bdc3c7 10px, transparent 10px, transparent 20px);
      top: 50%;
      opacity: 0.3;
    }

    .steps {
      font-size: clamp(1.5rem, 5vw, 2rem);
      cursor: pointer;
      animation: glow 2s infinite;
      margin: 10px 0;
    }

    .compass {
      font-size: clamp(2rem, 6vw, 2.5rem);
      animation: spin 3s infinite;
      filter: drop-shadow(0 0 10px #f39c12);
    }

    h1 {
      font-size: clamp(1.2rem, 4vw, 1.5rem);
      margin: 15px 0;
      text-shadow: 0 0 10px rgba(236, 240, 241, 0.5);
    }

    p {
      margin: 10px 0;
      opacity: 0.8;
      font-size: clamp(0.9rem, 3vw, 1rem);
    }

    .btn {
      background: linear-gradient(45deg, #3498db, #2980b9);
      border: none;
      color: #fff;
      padding: clamp(6px, 2vw, 8px) clamp(12px, 4vw, 16px);
      margin: 5px;
      border-radius: 15px;
      cursor: pointer;
      transition: all 0.3s;
      font-size: clamp(0.8rem, 3vw, 0.9rem);
      box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
      min-height: 40px;
    }

    @media (max-width: 480px) {
      .btn {
        display: block;
        margin: 8px auto;
        max-width: 200px;
      }
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
    }

    .red {
      background: linear-gradient(45deg, #e74c3c, #c0392b);
      box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
    }

    .msg {
      height: 25px;
      margin: 10px 0;
      color: #f39c12;
      font-weight: bold;
      text-shadow: 0 0 5px #f39c12;
    }

    @keyframes glow {
      0%, 100% {
        opacity: 0.6;
        transform: scale(1);
      }
      50% {
        opacity: 1;
        transform: scale(1.1);
      }
    }

    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    @keyframes drift {
      0%, 100% {
        transform: translateX(-100px) translateY(-50px);
      }
      50% {
        transform: translateX(100px) translateY(50px);
      }
    }
  </style>
</head>
<body>
  <div class="fog"></div>
  <div class="fog"></div>
  <div class="fog"></div>

  <div class="content">
    <div class="road"></div>
    <div class="steps" onclick="follow()">👣</div>
    <div class="compass">🧭</div>
    <h1>The Forgotten Road</h1>
    <p>This path has faded from the map</p>
    <div class="msg" id="msg"></div>

    <button class="btn" onclick="go('/')">Find New Path</button>
    <button class="btn red" onclick="follow()">Follow Steps</button>
   <button class="btn" onclick="window.location.href='/../'">Back to Map</button>
  </div>

  <script>
    const routes = [
      '→ Mystic castle ruins',
      '→ Enchanted forest path',
      '→ Abandoned lighthouse',
      '→ Hidden crystal cave',
      '→ Ancient stone bridge'
    ];

    function follow() {
      document.getElementById('msg').textContent = routes[Math.floor(Math.random() * routes.length)];
      if ('vibrate' in navigator) navigator.vibrate(100);
    }

    function go(path) {
      location.href = path;
    }
  </script>
</body>
</html>