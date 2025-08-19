 <!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>About Developer 🔐</title>
    <link rel="icon" href="assets/icon.png" type="image/x-icon">
    <style>
      body {
        margin: 0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
        background-size: 400% 400%;
        animation: gradient 15s ease infinite;
        color: #fff;
        font-family: Arial;
        padding: 20px;
        text-align: center;
      }

      h1 {
        color: #fff;
        margin: 20px 0;
        text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
      }

      .dev {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        animation: float 3s ease-in-out infinite;
        margin: 15px auto;
        display: block;
        box-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
      }


      .quote {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        padding: 20px;
        border-radius: 15px;
        margin: 20px 0;
        border: 1px solid rgba(255, 255, 255, 0.2);
        font-style: italic;
        font-weight: bold;
      }

      .quote-text {
        font-size: 1.1rem;
      }

      .thank-you {
        font-size: 1.4rem;
        font-weight: bold;
        margin-top: 15px;
        color: #ffffff;
        text-transform: uppercase;
        text-shadow: 0 0 10px rgba(255, 255, 255, 0.7);
      }

      .btn {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: #fff;
        padding: 10px 15px;
        border-radius: 25px;
        margin: 8px;
        text-decoration: none;
        display: inline-block;
        transition: 0.3s;
      }

      .btn:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: translateY(-2px);
      }

      @keyframes gradient {
        0% {
          background-position: 0% 50%;
        }
        50% {
          background-position: 100% 50%;
        }
        100% {
          background-position: 0% 50%;
        }
      }

      @keyframes float {
        0%, 100% {
          transform: translateY(0px);
        }
        50% {
          transform: translateY(-10px);
        }
      }

    </style>
  </head>
  <body>
    <!-- <div class="dev">🤖</div> -->
    <img src="assets/my-image.jpg" alt="My Avatar" class="dev" />
    <h1>Kritarth Ranjan</h1>
    <h2>(AI/ML Engineer)</h2>
    <p>Hi! I'm the person who built this password manager to help keep your passwords safe and secure.</p>

    <!-- Quote Box -->
    <div class="quote" id="q">
      <div class="quote-text" id="quoteText"></div>
      <div class="thank-you">Thanks for using my app! 🙏</div>
    </div>

    <p>If you like this project, feel free to give it a star on GitHub!</p>

    <!-- Buttons -->
    <a href="/password_manager/" class="btn">🔒 Back to App</a>
    <a href="https://github.com/Kritarth123-prince/" class="btn">⭐ GitHub</a>
    <a href="https://www.linkedin.com/in/kritarth-ranjan" class="btn">💼 LinkedIn</a>
    <a href="https://x.com/KritarthRanjan" class="btn">🐦 Twitter</a>
    <a href="https://linktr.ee/Kritarth_Ranjan" class="btn">🌐 Other Socials</a>

    <script>
      const q = [
        "🔐 Your security is my priority",
        "🛡️ Guarding your passwords like they're my own",
        "🌟 Your digital safety matters here",
        "☮️ Peace of mind, one password at a time",
        "⚡ Where security meets simplicity",
        "❤️ Crafted for security, built with care",
        "🎯 Made to protect — designed by a developer who cares",
        "🏗️ Security isn't just a feature — it's the foundation",
        "🧠 Designed by an AI/ML engineer who values your privacy",
        "🔧 Secure by design. Backed by engineering. Driven by trust",
        "🤖 Where machine learning meets meaningful protection",
        "💡 More than just algorithms — this is security with intention"
      ];

      const randomQuote = q[Math.floor(Math.random() * q.length)];
      document.getElementById('quoteText').textContent = randomQuote;
    </script>
  </body>
</html>