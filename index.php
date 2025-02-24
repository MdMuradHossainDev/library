<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>লাইব্রেরি ব্যবস্থাপনা সিস্টেম</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Hind Siliguri', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: -50%;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: skewX(-15deg);
            pointer-events: none;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #333;
            font-size: 2em;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 0.9em;
        }

        .input-group {
            position: relative;
            margin-bottom: 25px;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #764ba2;
            font-size: 1.2em;
        }

        .input-group input {
            width: 100%;
            padding: 12px 40px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 1em;
            color: #333;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            border-color: #764ba2;
            outline: none;
            box-shadow: 0 0 10px rgba(118, 75, 162, 0.1);
        }

        .input-group input::placeholder {
            color: #999;
        }

        .error-message {
            background: #ff6b6b;
            color: white;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9em;
            text-align: center;
            display: none;
        }

        .login-button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.3);
        }

        .login-footer {
            text-align: center;
            margin-top: 25px;
            color: #666;
            font-size: 0.9em;
        }

        .login-footer a {
            color: #764ba2;
            text-decoration: none;
            font-weight: 500;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 1.8em;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>স্বাগতম</h1>
            <p>আপনার অ্যাকাউন্টে লগইন করুন</p>
        </div>
        <form method="POST" action="auth/login.php" id="loginForm">
            <div class="error-message" id="error-message"></div>
            
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" placeholder="ইউজারনেম" required id="username">
            </div>
            
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="পাসওয়ার্ড" required id="password">
            </div>
            
            <button type="submit" class="login-button">লগইন</button>
        </form>
        
        <div class="login-footer">
            <p>লাইব্রেরি ব্যবস্থাপনা সিস্টেম &copy; <?php echo date('Y'); ?></p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
