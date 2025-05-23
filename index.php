<?php
session_start(); // Start the session

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tdl";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // First check if student number exists
    $check_student = "SELECT * FROM accounts WHERE username = '$username'";
    $student_result = mysqli_query($conn, $check_student);
    
    if (mysqli_num_rows($student_result) > 0) {
        $student_data = mysqli_fetch_assoc($student_result); // Get the student data
        
        // Student exists, now check password
        $check_password = "SELECT * FROM accounts WHERE username = '$username' AND password = '$password'";
        $password_result = mysqli_query($conn, $check_password);
        
        if (mysqli_num_rows($password_result) > 0) {
            // Both student number and password are correct
            $_SESSION['username'] = $username;
            $success = "Login successful!";
            header("Location: to_do_list.php");
            exit();
        } else {
            // Student exists but password is wrong
            $error = "Incorrect password. Please try again.";
        }
    } else {
        // Student number doesn't exist
        $error = "Username not found.";
    }
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhosNext?</title>
    <link href="https://fonts.googleapis.com/css?family=Inter:600,400" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="image/wn_2twoogo.jpg">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Inter', 'Poppins', Arial, sans-serif;
            background: linear-gradient(135deg, #e0eaff 0%, #f8fafc 100%);
            position: relative;
        }
        .background-pattern {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: 0;
            pointer-events: none;
            background-image: radial-gradient(rgba(66,135,245,0.07) 1px, transparent 1px), 
                              radial-gradient(rgba(184,211,255,0.08) 1px, transparent 1px);
            background-size: 40px 40px, 80px 80px;
            background-position: 0 0, 20px 20px;
            animation: moveGrid 18s linear infinite alternate;
        }
        @keyframes moveGrid {
            0% { background-position: 0 0, 20px 20px; }
            100% { background-position: 20px 20px, 0 0; }
        }
        .background-shapes {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: 0;
            pointer-events: none;
        }
        .background-shapes .circle1 {
            position: absolute;
            top: 5%; left: 10%;
            width: 180px; height: 180px;
            background: rgba(66, 135, 245, 0.10);
            border-radius: 50%;
            filter: blur(2px);
            animation: moveCircle1 12s ease-in-out infinite alternate;
        }
        .background-shapes .circle2 {
            position: absolute;
            bottom: 8%; right: 12%;
            width: 140px; height: 140px;
            background: rgba(66, 135, 245, 0.13);
            border-radius: 50%;
            filter: blur(1.5px);
            animation: moveCircle2 14s ease-in-out infinite alternate;
        }
        .background-shapes .blob1 {
            position: absolute;
            top: 60%; left: 2%;
            width: 120px; height: 80px;
            background: rgba(184, 211, 255, 0.18);
            border-radius: 60% 40% 50% 50% / 50% 60% 40% 50%;
            transform: rotate(-15deg);
            filter: blur(1.5px);
            animation: moveBlob1 16s ease-in-out infinite alternate;
        }
        .container-fluid {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
        }
        .form-container {
            width: 100%;
            max-width: 420px;
            padding: 2.5rem 2rem;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            position: relative;
            z-index: 2;
            box-shadow: 0 8px 32px 0 rgba(66,135,245,0.12), 
                       0 2px 16px rgba(0,0,0,0.06),
                       inset 0 0 0 1px rgba(255,255,255,0.5);
            text-align: center;
            animation: fadeInCard 1.2s cubic-bezier(.39,.575,.565,1) both;
            overflow: hidden;
        }
        .form-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent,
                rgba(66, 135, 245, 0.03),
                transparent
            );
            transform: rotate(45deg);
            animation: shine 8s infinite;
        }
        @keyframes shine {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }
        @keyframes fadeInCard {
            0% { opacity: 0; transform: translateY(40px) scale(0.98);}
            100% { opacity: 1; transform: none;}
        }
        @keyframes borderAnim {
            0% { border-image-source: linear-gradient(120deg, #4287f5 40%, #b8d3ff 100%);}
            100% { border-image-source: linear-gradient(240deg, #b8d3ff 40%, #4287f5 100%);}
        }
        .icon-box {
            background: linear-gradient(135deg, rgb(184, 211, 255) 0%, rgb(66, 135, 245) 100%);
            border-radius: 50%;
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 4px 12px rgba(66,135,245,0.2);
            animation: pulseIcon 2s infinite;
        }
        @keyframes pulseIcon {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .icon-box svg {
            width: 32px;
            height: 32px;
            color: white;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        .enter-heading {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 12px;
            font-family: 'Inter', 'Poppins', Arial, sans-serif;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .enter-desc {
            color: #4a5568;
            margin-bottom: 32px;
            font-size: 1.1rem;
            line-height: 1.6;
        }
        .input-group {
            text-align: left;
            position: relative;
        }
        .input-group label {
            font-weight: 600;
            color: #222;
            margin-bottom: 6px;
            display: block;
        }
        .input-group input[type="text"],
        .input-group input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid rgba(66, 135, 245, 0.2);
            border-radius: 12px;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 2px 8px rgba(66, 135, 245, 0.05);
        }
        .input-group input[type="text"]:focus,
        .input-group input[type="password"]:focus {
            border-color: rgb(66, 135, 245);
            box-shadow: 0 0 0 4px rgba(66, 135, 245, 0.15);
            transform: translateY(-1px);
        }
        .input-group input[type="text"]::placeholder,
        .input-group input[type="password"]::placeholder {
            color: #94a3b8;
            transition: all 0.3s ease;
        }
        .input-group input[type="text"]:focus::placeholder,
        .input-group input[type="password"]:focus::placeholder {
            opacity: 0.7;
            transform: translateX(5px);
        }
        .decorative-divider {
            width: 100%;
            margin: 0 auto 18px auto;
            display: flex;
            justify-content: center;
        }
        .decorative-divider svg {
            width: 120px;
            height: 18px;
            display: block;
        }
        .btn-green {
            background: linear-gradient(135deg, rgb(66, 135, 245) 0%, #1d5ecb 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 14px 0;
            width: 100%;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(66,135,245,0.2);
            position: relative;
            overflow: hidden;
        }
        .btn-green::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transition: 0.5s;
        }
        .btn-green:hover::before {
            left: 100%;
        }
        .btn-green:hover, .btn-green:focus {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(66,135,245,0.3);
        }
        .btn-green:active {
            transform: translateY(1px);
        }
        .info-tip {
            background: rgba(66,135,245,0.08);
            color: #2256a0;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.98rem;
            margin-bottom: 18px;
            margin-top: -10px;
            display: inline-block;
            border: 1px solid rgba(66,135,245,0.1);
            transition: all 0.3s ease;
        }
        .info-tip:hover {
            background: rgba(66,135,245,0.12);
            transform: translateY(-1px);
        }
        .alert {
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            border: none;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        .alert-success {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
            border: 1px solid rgba(25, 135, 84, 0.2);
        }
        /* Sparkle animation */
        .sparkle {
            position: absolute;
            pointer-events: none;
            z-index: 3;
            width: 18px; height: 18px;
            opacity: 0.7;
            animation: sparkleMove 3.5s linear infinite;
        }
        @keyframes sparkleMove {
            0% { transform: translateY(0) scale(1) rotate(0deg); opacity: 0.7; }
            50% { transform: translateY(-30px) scale(1.2) rotate(20deg); opacity: 1; }
            100% { transform: translateY(-60px) scale(0.8) rotate(-20deg); opacity: 0; }
        }
        .no-account-text {
    color: #4a5568;
    font-size: 0.95rem;
    margin-top: 12px;
}

.signup-link {
    color: #1d5ecb;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    position: relative;
}

.signup-link:hover {
    color: #4287f5;
}

.signup-link::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: #4287f5;
    transition: width 0.3s ease;
}

.signup-link:hover::after {
    width: 100%;
}
        @media (max-width: 768px) {
            .form-container {
                width: 90%;
                padding: 1.5rem;
            }
            .input-group input[type="text"],
            .input-group input[type="password"] {
                font-size: 0.9rem;
                padding: 12px;
            }
            .btn-green {
                padding: 12px 0;
                font-size: 0.9rem;
            }
            body {
                overflow: hidden;
            }
        }
        @media (max-width: 480px) {
            .form-container {
                width: 95%;
                padding: 1.2rem;
            }
            .input-group input[type="text"],
            .input-group input[type="password"] {
                font-size: 0.8rem;
                padding: 10px;
            }
            .btn-green {
                font-size: 0.85rem;
                padding: 10px 0;
            }
        }
        @keyframes moveCircle1 {
            0% { top: 5%; left: 10%; }
            100% { top: 10%; left: 14%; }
        }
        @keyframes moveCircle2 {
            0% { bottom: 8%; right: 12%; }
            100% { bottom: 12%; right: 8%; }
        }
        @keyframes moveBlob1 {
            0% { top: 60%; left: 2%; transform: rotate(-15deg) scale(1); }
            100% { top: 63%; left: 5%; transform: rotate(-8deg) scale(1.08); }
        }
    </style>
</head>

<body>
    <div class="background-pattern"></div>
    <div class="background-shapes" id="parallax-bg">
        <div class="circle1"></div>
        <div class="circle2"></div>
        <div class="blob1"></div>
    </div>
    <!-- Sparkles -->
    <svg class="sparkle" style="top: 60%; left: 30%; animation-delay: 0s;" viewBox="0 0 20 20"><polygon points="10,2 12,8 18,10 12,12 10,18 8,12 2,10 8,8" fill="rgb(66,135,245)"/></svg>
    <svg class="sparkle" style="top: 40%; left: 70%; animation-delay: 1.2s;" viewBox="0 0 20 20"><polygon points="10,2 12,8 18,10 12,12 10,18 8,12 2,10 8,8" fill="rgb(184,211,255)"/></svg>
    <svg class="sparkle" style="top: 75%; left: 55%; animation-delay: 2.1s;" viewBox="0 0 20 20"><polygon points="10,2 12,8 18,10 12,12 10,18 8,12 2,10 8,8" fill="rgb(66,135,245)"/></svg>
    <div class="container-fluid">
        <div class="form-container">
            <div class="decorative-divider">
                <svg viewBox="0 0 120 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0 9 Q30 18 60 9 T120 9" stroke="rgb(66,135,245)" stroke-width="2" fill="none"/>
                </svg>
            </div>
            <div class="icon-box">
                <!-- User icon SVG -->
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20c0-4 4-7 8-7s8 3 8 7"/>
                </svg>
            </div>
            <h2 class="enter-heading">
                Login
            </h2>

               <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
       <form method="POST" action="">
        <div class="input-group mb-4">
            <label for="username" class="form-label">Username</label>
            <input class="form-control" type="text" id="username" name="username" 
                   placeholder="e.g. dave123" required autocomplete="off" 
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
        </div>
        
        <div class="input-group mb-4">
            <label for="password" class="form-label">Password</label>
            <input class="form-control" type="password" id="password" name="password" 
                   placeholder="Enter your password" required autocomplete="off">
        </div>
        
        <div class="info-tip">
            Tip: Any username will work, but make sure it's unique.
        </div>
        
        <button class="btn-green" type="submit" name="submit">Login</button>
        
        <div class="no-account-text mt-3">
            Don't have an account yet? <a href="register.php" class="signup-link">Sign up</a>
        </div>
    </form>
        </div>
    </div>
</body>
</html>