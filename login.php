<?php
// ============================================================
//  BAHAYTEK  —  login.php
//  Handles: email/password login, registration,
//           Google OAuth 2.0, Facebook OAuth 2.0
//
//  HOW TO SET UP GOOGLE LOGIN:
//  1. Go to https://console.cloud.google.com
//  2. Create a project → APIs & Services → Credentials
//  3. Create OAuth 2.0 Client ID (Web application)
//  4. Add Authorized redirect URI:
//     http://localhost/login.php?action=google_callback
//  5. Copy Client ID and Client Secret below
//
//  HOW TO SET UP FACEBOOK LOGIN:
//  1. Go to https://developers.facebook.com/apps
//  2. Create App → Consumer → add Facebook Login product
//  3. Settings → Valid OAuth Redirect URIs:
//     http://localhost/login.php?action=facebook_callback
//  4. Copy App ID and App Secret below
// ============================================================

session_start();

// ── DATABASE CONFIG (same as admin.php) ─────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'bahaytek_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3306');

// ── GOOGLE OAUTH CONFIG ──────────────────────────────────────
define('GOOGLE_CLIENT_ID',     'PASTE_YOUR_GOOGLE_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'PASTE_YOUR_GOOGLE_CLIENT_SECRET_HERE');
define('GOOGLE_REDIRECT_URI',  'http://localhost/login.php?action=google_callback');

// ── FACEBOOK OAUTH CONFIG ────────────────────────────────────
define('FB_APP_ID',       'PASTE_YOUR_FACEBOOK_APP_ID_HERE');
define('FB_APP_SECRET',   'PASTE_YOUR_FACEBOOK_APP_SECRET_HERE');
define('FB_REDIRECT_URI', 'http://localhost/login.php?action=facebook_callback');

// ── WHERE TO SEND USERS AFTER LOGIN ─────────────────────────
define('REDIRECT_ADMIN',    'admin.php');   // for admin/trainer roles
define('REDIRECT_USER',     'index.html');  // for regular customers
define('REDIRECT_REGISTER', 'index.html');  // after successful signup

// ── DATABASE CONNECTION ──────────────────────────────────────
function db(): PDO {
    static $pdo;
    if ($pdo) return $pdo;
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    return $pdo;
}

// ── HELPERS ──────────────────────────────────────────────────
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function setFlash(string $msg, string $type = 'error'): void {
    $_SESSION['auth_flash'] = ['msg' => $msg, 'type' => $type];
}
function getFlash(): ?array {
    $f = $_SESSION['auth_flash'] ?? null;
    unset($_SESSION['auth_flash']);
    return $f;
}

// Start a user session after login
function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['bt_user'] = [
        'id'       => $user['id'],
        'name'     => trim($user['first_name'].' '.$user['last_name']),
        'email'    => $user['email'],
        'role'     => $user['role'],
        'avatar'   => $user['avatar_url'] ?? null,
        'provider' => $user['provider'] ?? 'email',
    ];
}

function redirectAfterLogin(string $role): void {
    if (in_array($role, ['admin','trainer'])) {
        header('Location: '.REDIRECT_ADMIN); exit;
    }
    header('Location: '.REDIRECT_USER); exit;
}

// ── cURL helper (used for OAuth token exchanges) ─────────────
function curlPost(string $url, array $data): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true) ?? [];
}

function curlGet(string $url, array $headers = []): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true) ?? [];
}

// ── Find or create a user from OAuth data ───────────────────
function findOrCreateOAuthUser(string $provider, string $providerId, string $email,
                                string $firstName, string $lastName, ?string $avatar): array {
    // 1. Look for existing user with same provider + provider_id
    $s = db()->prepare('SELECT * FROM users WHERE provider=? AND provider_id=? LIMIT 1');
    $s->execute([$provider, $providerId]);
    $user = $s->fetch();
    if ($user) {
        // Update avatar in case it changed
        db()->prepare('UPDATE users SET avatar_url=?, updated_at=NOW() WHERE id=?')
             ->execute([$avatar, $user['id']]);
        $user['avatar_url'] = $avatar;
        return $user;
    }

    // 2. Look for existing email (user may have registered with email before)
    if ($email) {
        $s = db()->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
        $s->execute([$email]);
        $user = $s->fetch();
        if ($user) {
            // Link OAuth to existing account
            db()->prepare('UPDATE users SET provider=?,provider_id=?,avatar_url=?,email_verified=1,updated_at=NOW() WHERE id=?')
                 ->execute([$provider, $providerId, $avatar, $user['id']]);
            $user['provider']    = $provider;
            $user['provider_id'] = $providerId;
            $user['avatar_url']  = $avatar;
            return $user;
        }
    }

    // 3. Create new user
    db()->prepare('INSERT INTO users (first_name,last_name,email,provider,provider_id,avatar_url,email_verified,role,status,joined_date)
        VALUES (?,?,?,?,?,?,1,"customer","active",CURDATE())')
        ->execute([$firstName, $lastName, $email, $provider, $providerId, $avatar]);

    $s = db()->prepare('SELECT * FROM users WHERE id=? LIMIT 1');
    $s->execute([db()->lastInsertId()]);
    return $s->fetch();
}

// ── Already logged in? Redirect away ────────────────────────
if (!empty($_SESSION['bt_user'])) {
    redirectAfterLogin($_SESSION['bt_user']['role']);
}

// ============================================================
//  ACTION ROUTER
// ============================================================
$action = $_GET['action'] ?? 'login';
$tab    = $_GET['tab']    ?? 'login'; // login | register

// ── EMAIL LOGIN ──────────────────────────────────────────────
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        setFlash('Please fill in all fields.');
    } else {
        $s = db()->prepare('SELECT * FROM users WHERE email=? AND provider="email" AND status="active" LIMIT 1');
        $s->execute([$email]);
        $user = $s->fetch();
        if ($user && password_verify($password, $user['password'])) {
            loginUser($user);
            redirectAfterLogin($user['role']);
        } else {
            setFlash('Incorrect email or password.');
        }
    }
    header('Location: login.php?tab=login'); exit;
}

// ── EMAIL REGISTER ───────────────────────────────────────────
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $email     = trim($_POST['email']      ?? '');
    $phone     = trim($_POST['phone']      ?? '');
    $password  = $_POST['password']        ?? '';
    $confirm   = $_POST['confirm_password']?? '';

    if (!$firstName || !$lastName || !$email || !$password) {
        setFlash('Please fill in all required fields.'); header('Location: login.php?tab=register'); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('Please enter a valid email address.'); header('Location: login.php?tab=register'); exit;
    }
    if (strlen($password) < 8) {
        setFlash('Password must be at least 8 characters.'); header('Location: login.php?tab=register'); exit;
    }
    if ($password !== $confirm) {
        setFlash('Passwords do not match.'); header('Location: login.php?tab=register'); exit;
    }

    // Check if email already exists
    $s = db()->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
    $s->execute([$email]);
    if ($s->fetch()) {
        setFlash('An account with this email already exists.'); header('Location: login.php?tab=register'); exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    db()->prepare('INSERT INTO users (first_name,last_name,email,phone,password,provider,email_verified,role,status,joined_date)
        VALUES (?,?,?,?,?,"email",1,"customer","active",CURDATE())')
        ->execute([$firstName, $lastName, $email, $phone ?: null, $hash]);

    $s = db()->prepare('SELECT * FROM users WHERE id=? LIMIT 1');
    $s->execute([db()->lastInsertId()]);
    $user = $s->fetch();
    loginUser($user);
    setFlash('Welcome to BahayTek, '.$firstName.'! Your account has been created.', 'success');
    header('Location: '.REDIRECT_REGISTER); exit;
}

// ── GOOGLE — START OAUTH ─────────────────────────────────────
if ($action === 'google') {
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    $params = http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'access_type'   => 'online',
        'prompt'        => 'select_account',
    ]);
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?'.$params); exit;
}

// ── GOOGLE — CALLBACK ────────────────────────────────────────
if ($action === 'google_callback') {
    if (($_GET['state'] ?? '') !== ($_SESSION['oauth_state'] ?? '')) {
        setFlash('Invalid state. Please try again.'); header('Location: login.php'); exit;
    }
    unset($_SESSION['oauth_state']);

    if (isset($_GET['error'])) {
        setFlash('Google login was cancelled.'); header('Location: login.php'); exit;
    }

    // Exchange code for tokens
    $tokens = curlPost('https://oauth2.googleapis.com/token', [
        'code'          => $_GET['code'],
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ]);

    if (empty($tokens['access_token'])) {
        setFlash('Google login failed. Please try again.'); header('Location: login.php'); exit;
    }

    // Get user profile
    $profile = curlGet('https://www.googleapis.com/oauth2/v3/userinfo', [
        'Authorization: Bearer '.$tokens['access_token'],
    ]);

    if (empty($profile['sub'])) {
        setFlash('Could not retrieve your Google profile.'); header('Location: login.php'); exit;
    }

    $user = findOrCreateOAuthUser(
        'google',
        $profile['sub'],
        $profile['email']            ?? '',
        $profile['given_name']       ?? $profile['name'] ?? 'Google',
        $profile['family_name']      ?? 'User',
        $profile['picture']          ?? null
    );

    loginUser($user);
    redirectAfterLogin($user['role']);
}

// ── FACEBOOK — START OAUTH ───────────────────────────────────
if ($action === 'facebook') {
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    $params = http_build_query([
        'client_id'     => FB_APP_ID,
        'redirect_uri'  => FB_REDIRECT_URI,
        'scope'         => 'email,public_profile',
        'state'         => $state,
        'response_type' => 'code',
    ]);
    header('Location: https://www.facebook.com/v18.0/dialog/oauth?'.$params); exit;
}

// ── FACEBOOK — CALLBACK ──────────────────────────────────────
if ($action === 'facebook_callback') {
    if (($_GET['state'] ?? '') !== ($_SESSION['oauth_state'] ?? '')) {
        setFlash('Invalid state. Please try again.'); header('Location: login.php'); exit;
    }
    unset($_SESSION['oauth_state']);

    if (isset($_GET['error'])) {
        setFlash('Facebook login was cancelled.'); header('Location: login.php'); exit;
    }

    // Exchange code for access token
    $params = http_build_query([
        'client_id'     => FB_APP_ID,
        'redirect_uri'  => FB_REDIRECT_URI,
        'client_secret' => FB_APP_SECRET,
        'code'          => $_GET['code'],
    ]);
    $tokens = curlGet('https://graph.facebook.com/v18.0/oauth/access_token?'.$params);

    if (empty($tokens['access_token'])) {
        setFlash('Facebook login failed. Please try again.'); header('Location: login.php'); exit;
    }

    // Get user profile
    $params = http_build_query([
        'fields'       => 'id,first_name,last_name,email,picture.type(large)',
        'access_token' => $tokens['access_token'],
    ]);
    $profile = curlGet('https://graph.facebook.com/v18.0/me?'.$params);

    if (empty($profile['id'])) {
        setFlash('Could not retrieve your Facebook profile.'); header('Location: login.php'); exit;
    }

    $avatar = $profile['picture']['data']['url'] ?? null;
    $user = findOrCreateOAuthUser(
        'facebook',
        $profile['id'],
        $profile['email']      ?? '',
        $profile['first_name'] ?? 'Facebook',
        $profile['last_name']  ?? 'User',
        $avatar
    );

    loginUser($user);
    redirectAfterLogin($user['role']);
}

// ── LOGOUT ───────────────────────────────────────────────────
if ($action === 'logout') {
    session_destroy();
    header('Location: login.php'); exit;
}

// ── Determine which tab to show ──────────────────────────────
$activeTab = $tab === 'register' ? 'register' : 'login';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — BahayTek</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ── RESET ────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{height:100%}
body{
  font-family:'DM Sans',sans-serif;
  min-height:100%;
  background:linear-gradient(145deg,#1a2e12 0%,#336a29 40%,#2e5e22 70%,#1a3d10 100%);
  display:flex;align-items:center;justify-content:center;
  padding:20px;position:relative;overflow:hidden;
}

/* ── BACKGROUND DECORATION ────────────────────── */
body::before,body::after{
  content:'';position:fixed;border-radius:50%;pointer-events:none;
}
body::before{
  width:600px;height:600px;top:-200px;right:-150px;
  background:radial-gradient(circle,rgba(193,217,92,.08) 0%,transparent 70%);
}
body::after{
  width:500px;height:500px;bottom:-180px;left:-100px;
  background:radial-gradient(circle,rgba(128,177,85,.06) 0%,transparent 70%);
}

/* Floating leaf particles */
.leaf{position:fixed;font-size:1.2rem;opacity:.08;pointer-events:none;animation:float linear infinite}
@keyframes float{0%{transform:translateY(100vh) rotate(0deg)}100%{transform:translateY(-10vh) rotate(360deg)}}

/* ── AUTH CARD ────────────────────────────────── */
.auth-card{
  width:100%;max-width:460px;
  background:#fff;border-radius:24px;
  box-shadow:0 40px 80px rgba(0,0,0,.35),0 0 0 1px rgba(255,255,255,.1);
  overflow:hidden;position:relative;z-index:10;
  animation:slideUp .5s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes slideUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}

/* ── CARD HEADER ──────────────────────────────── */
.card-head{
  background:linear-gradient(145deg,#336a29 0%,#498428 60%,#3d7030 100%);
  padding:32px 36px 28px;text-align:center;position:relative;overflow:hidden;
}
.card-head::before{
  content:'';position:absolute;top:-60px;right:-60px;
  width:200px;height:200px;border-radius:50%;
  background:radial-gradient(circle,rgba(193,217,92,.12) 0%,transparent 70%);
}
.logo-wrap{display:inline-flex;flex-direction:column;align-items:center;margin-bottom:8px}
.logo{font-family:'DM Serif Display',serif;font-size:2rem;color:#fff;line-height:1;letter-spacing:-1px}
.logo .b{color:#c1d95c}
.logo-tag{font-size:.52rem;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:rgba(193,217,92,.65);margin-top:3px}
.card-tagline{font-size:.8rem;color:rgba(255,255,255,.58);margin-top:2px}

/* ── TABS ─────────────────────────────────────── */
.tab-strip{
  display:flex;border-bottom:2px solid #edf5e3;
  background:#fafdf6;
}
.tab-btn{
  flex:1;padding:14px;font-size:.86rem;font-weight:700;
  color:#5a7248;background:none;border:none;cursor:pointer;
  font-family:inherit;border-bottom:3px solid transparent;
  margin-bottom:-2px;transition:all .2s;
}
.tab-btn.active{color:#336a29;border-bottom-color:#336a29;background:#fff}
.tab-btn:not(.active):hover{color:#498428;background:rgba(128,177,85,.05)}

/* ── CARD BODY ────────────────────────────────── */
.card-body{padding:28px 36px 32px}

/* ── FLASH MESSAGES ───────────────────────────── */
.flash{
  padding:12px 16px;border-radius:10px;margin-bottom:20px;
  font-size:.82rem;font-weight:600;display:flex;align-items:center;gap:9px;
}
.flash.error  {background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
.flash.success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}

/* ── FORM ─────────────────────────────────────── */
.form-group{margin-bottom:16px}
.form-group label{
  display:block;font-size:.7rem;font-weight:700;
  text-transform:uppercase;letter-spacing:.5px;color:#2e4a1e;margin-bottom:5px;
}
.form-group input{
  width:100%;padding:11px 14px;
  border:1.5px solid #c8ddb0;border-radius:11px;
  font-size:.88rem;font-family:inherit;color:#1a2e12;
  background:#fafdf6;outline:none;transition:all .2s;
}
.form-group input:focus{border-color:#80b155;background:#fff;box-shadow:0 0 0 4px rgba(128,177,85,.1)}
.form-group input::placeholder{color:#a8c07e}
.form-group.half{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.pass-hint{font-size:.69rem;color:#5a7248;margin-top:4px;display:flex;align-items:center;gap:5px}

/* ── SUBMIT BUTTON ────────────────────────────── */
.btn-submit{
  width:100%;padding:13px;border:none;border-radius:11px;
  background:linear-gradient(135deg,#336a29,#498428);
  color:#fff;font-weight:800;font-size:.9rem;cursor:pointer;
  font-family:inherit;letter-spacing:.2px;
  transition:all .2s;box-shadow:0 4px 15px rgba(51,106,41,.3);
  margin-top:4px;
}
.btn-submit:hover{background:linear-gradient(135deg,#2e5e22,#3d7030);transform:translateY(-2px);box-shadow:0 8px 22px rgba(51,106,41,.35)}
.btn-submit:active{transform:translateY(0)}

/* ── FORGOT PASSWORD ──────────────────────────── */
.forgot-row{display:flex;justify-content:flex-end;margin-top:-8px;margin-bottom:16px}
.forgot-row a{font-size:.75rem;color:#80b155;text-decoration:none;font-weight:600}
.forgot-row a:hover{color:#336a29}

/* ── DIVIDER ──────────────────────────────────── */
.divider{
  display:flex;align-items:center;gap:12px;
  margin:22px 0 20px;color:#a8c07e;font-size:.75rem;font-weight:600;
}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#c8ddb0}

/* ── SOCIAL BUTTONS ───────────────────────────── */
.social-btns{display:flex;gap:10px}
.btn-social{
  flex:1;padding:11px 14px;border-radius:11px;
  font-size:.82rem;font-weight:700;cursor:pointer;
  font-family:inherit;transition:all .2s;
  display:flex;align-items:center;justify-content:center;gap:9px;
  border:1.5px solid;text-decoration:none;
}
.btn-google{
  background:#fff;border-color:#dadce0;color:#3c4043;
}
.btn-google:hover{background:#f8f9fa;border-color:#c0c4c9;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.btn-facebook{
  background:#1877f2;border-color:#1877f2;color:#fff;
}
.btn-facebook:hover{background:#166fe5;border-color:#166fe5;box-shadow:0 2px 8px rgba(24,119,242,.3)}

/* ── GOOGLE ICON SVG ──────────────────────────── */
.g-icon{width:18px;height:18px;flex-shrink:0}

/* ── FACEBOOK ICON ────────────────────────────── */
.fb-icon{font-size:1.1rem;font-weight:900;line-height:1;font-style:normal}

/* ── FOOTER LINKS ─────────────────────────────── */
.card-footer{
  padding:16px 36px;background:#fafdf6;
  border-top:1px solid #edf5e3;text-align:center;
}
.card-footer p{font-size:.76rem;color:#5a7248}
.card-footer a{color:#336a29;font-weight:700;text-decoration:none}
.card-footer a:hover{text-decoration:underline}

/* ── TERMS ────────────────────────────────────── */
.terms{font-size:.69rem;color:#a8c07e;text-align:center;margin-top:14px;line-height:1.6}
.terms a{color:#80b155}

/* ── BACK LINK ────────────────────────────────── */
.back-link{
  position:fixed;top:20px;left:20px;z-index:20;
  display:flex;align-items:center;gap:7px;
  color:rgba(255,255,255,.7);font-size:.8rem;font-weight:600;
  text-decoration:none;transition:color .2s;
}
.back-link:hover{color:#c1d95c}

/* ── STRENGTH INDICATOR ───────────────────────── */
#strengthBar{height:3px;border-radius:20px;background:#edf5e3;margin-top:6px;overflow:hidden;transition:all .3s}
#strengthFill{height:100%;border-radius:20px;width:0%;transition:all .3s}
#strengthLabel{font-size:.68rem;margin-top:3px;font-weight:600}
</style>
</head>
<body>

<!-- Floating leaves background -->
<span class="leaf" style="left:5%;animation-duration:18s;animation-delay:0s">🌿</span>
<span class="leaf" style="left:15%;animation-duration:22s;animation-delay:4s">🍃</span>
<span class="leaf" style="left:75%;animation-duration:16s;animation-delay:2s">🌱</span>
<span class="leaf" style="left:88%;animation-duration:20s;animation-delay:7s">🍃</span>
<span class="leaf" style="left:45%;animation-duration:25s;animation-delay:11s">🌿</span>

<a href="index.html" class="back-link">← Back to Website</a>

<div class="auth-card">
  <!-- Header -->
  <div class="card-head">
    <div class="logo-wrap">
      <div class="logo"><span class="b">BAHAY</span>TEK</div>
      <div class="logo-tag">Product Development Services</div>
    </div>
    <p class="card-tagline">Sustainable technology from Camarines Norte</p>
  </div>

  <!-- Tabs -->
  <div class="tab-strip">
    <button class="tab-btn <?= $activeTab==='login'?'active':'' ?>"
            onclick="switchTab('login')">Log In</button>
    <button class="tab-btn <?= $activeTab==='register'?'active':'' ?>"
            onclick="switchTab('register')">Sign Up</button>
  </div>

  <!-- Body -->
  <div class="card-body">

    <!-- Flash message -->
    <?php if ($flash): ?>
      <div class="flash <?= $flash['type'] ?>">
        <?= $flash['type']==='success' ? '✓' : '⚠' ?> <?= e($flash['msg']) ?>
      </div>
    <?php endif ?>

    <!-- ── LOGIN FORM ── -->
    <div id="loginPanel" style="display:<?= $activeTab==='login'?'block':'none' ?>">
      <form method="POST" action="login.php?action=login">
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" placeholder="you@email.com" autocomplete="email" required>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
        </div>
        <div class="forgot-row"><a href="#">Forgot password?</a></div>
        <button type="submit" class="btn-submit">Log In to BahayTek →</button>
      </form>

      <div class="divider">or continue with</div>

      <div class="social-btns">
        <a href="login.php?action=google" class="btn-social btn-google">
          <!-- Google SVG icon -->
          <svg class="g-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
          </svg>
          Continue with Google
        </a>
        <a href="login.php?action=facebook" class="btn-social btn-facebook">
          <i class="fb-icon">f</i> Facebook
        </a>
      </div>
    </div>

    <!-- ── REGISTER FORM ── -->
    <div id="registerPanel" style="display:<?= $activeTab==='register'?'block':'none' ?>">
      <form method="POST" action="login.php?action=register">
        <div class="form-group half">
          <div>
            <label>First Name *</label>
            <input type="text" name="first_name" placeholder="Juan" autocomplete="given-name" required>
          </div>
          <div>
            <label>Last Name *</label>
            <input type="text" name="last_name" placeholder="dela Cruz" autocomplete="family-name" required>
          </div>
        </div>
        <div class="form-group">
          <label>Email Address *</label>
          <input type="email" name="email" placeholder="you@email.com" autocomplete="email" required>
        </div>
        <div class="form-group">
          <label>Phone (optional)</label>
          <input type="tel" name="phone" placeholder="09XXXXXXXXX" autocomplete="tel">
        </div>
        <div class="form-group">
          <label>Password *</label>
          <input type="password" name="password" id="passwordInput" placeholder="Min. 8 characters"
                 autocomplete="new-password" required oninput="checkStrength(this.value)">
          <div id="strengthBar"><div id="strengthFill"></div></div>
          <div id="strengthLabel" class="pass-hint"></div>
        </div>
        <div class="form-group">
          <label>Confirm Password *</label>
          <input type="password" name="confirm_password" placeholder="Repeat your password"
                 autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn-submit">Create My Account →</button>
        <p class="terms">By signing up you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.</p>
      </form>

      <div class="divider">or sign up with</div>

      <div class="social-btns">
        <a href="login.php?action=google" class="btn-social btn-google">
          <svg class="g-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
          </svg>
          Sign up with Google
        </a>
        <a href="login.php?action=facebook" class="btn-social btn-facebook">
          <i class="fb-icon">f</i> Facebook
        </a>
      </div>
    </div>

  </div><!-- /card-body -->

  <!-- Footer -->
  <div class="card-footer">
    <p id="footerText">
      <?php if ($activeTab==='register'): ?>
        Already have an account? <a href="#" onclick="switchTab('login');return false">Log In</a>
      <?php else: ?>
        Don't have an account? <a href="#" onclick="switchTab('register');return false">Sign Up Free</a>
      <?php endif ?>
    </p>
  </div>
</div><!-- /auth-card -->

<script>
function switchTab(tab) {
  const isLogin = tab === 'login';
  document.getElementById('loginPanel').style.display    = isLogin ? 'block' : 'none';
  document.getElementById('registerPanel').style.display = isLogin ? 'none'  : 'block';
  document.querySelectorAll('.tab-btn').forEach((b, i) => {
    b.classList.toggle('active', isLogin ? i === 0 : i === 1);
  });
  document.getElementById('footerText').innerHTML = isLogin
    ? 'Don\'t have an account? <a href="#" onclick="switchTab(\'register\');return false">Sign Up Free</a>'
    : 'Already have an account? <a href="#" onclick="switchTab(\'login\');return false">Log In</a>';
  // Update URL without reload
  history.replaceState(null, '', 'login.php?tab=' + tab);
}

function checkStrength(val) {
  const bar   = document.getElementById('strengthFill');
  const label = document.getElementById('strengthLabel');
  if (!val) { bar.style.width='0%'; label.textContent=''; return; }
  let score = 0;
  if (val.length >= 8)  score++;
  if (val.length >= 12) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const levels = [
    { w:'20%', color:'#ef4444', text:'Weak' },
    { w:'40%', color:'#f97316', text:'Fair' },
    { w:'60%', color:'#eab308', text:'Good' },
    { w:'80%', color:'#22c55e', text:'Strong' },
    { w:'100%',color:'#16a34a', text:'Very Strong ✓' },
  ];
  const lvl = levels[Math.min(score, 4)];
  bar.style.width = lvl.w;
  bar.style.background = lvl.color;
  label.textContent = lvl.text;
  label.style.color = lvl.color;
}
</script>
<div style="position:fixed;bottom:8px;right:8px;font-size:.65rem;color:rgba(0,0,0,.25);text-decoration:none;"><a href="admin.php" style="color:rgba(0,0,0,.25);text-decoration:none;">Admin Panel</a></div>
</body>
</html>