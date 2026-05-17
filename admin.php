<?php
// ============================================================
//  BAHAYTEK ADMIN PANEL  —  admin.php
//  Requires PHP 7.4+  &  MySQL 5.7+ / MariaDB 10.3+
//  Place this file in your project root (same level as your
//  HTML files). Edit the DB_ constants below to match your
//  host settings.
// ============================================================

// ── DATABASE CONFIG ─────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'bahaytek_db');
define('DB_USER', 'root');       // change to your MySQL username
define('DB_PASS', '');           // change to your MySQL password
define('DB_PORT', '3306');
define('ADMIN_PASSWORD', 'bahaytek2025'); // simple admin password

session_start();

// ── DATABASE CONNECTION ──────────────────────────────────────
function db(): PDO {
    static $pdo;
    if ($pdo) return $pdo;
    try {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (PDOException $e) {
        die('<div style="font-family:sans-serif;padding:40px;color:#dc2626;background:#fee2e2;margin:40px;border-radius:12px">
            <strong>Database connection failed.</strong><br><br>'
            .htmlspecialchars($e->getMessage()).'<br><br>
            Please check your DB_HOST, DB_USER, DB_PASS, and DB_NAME constants in admin.php.</div>');
    }
    return $pdo;
}

// ── SIMPLE AUTH ──────────────────────────────────────────────
if (isset($_POST['login_password'])) {
    if ($_POST['login_password'] === ADMIN_PASSWORD) {
        $_SESSION['bt_admin'] = true;
    } else {
        $login_error = 'Incorrect password.';
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}
if (empty($_SESSION['bt_admin'])) {
    // Show login page
    showLogin($login_error ?? '');
    exit;
}

// ── ROUTING ──────────────────────────────────────────────────
$page   = $_GET['page']   ?? 'dashboard';
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// ── FLASH MESSAGES ───────────────────────────────────────────
function flash(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}
function getFlash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ── HELPER: Sanitize output ──────────────────────────────────
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// ── HELPER: Currency ─────────────────────────────────────────
function peso(float $n): string { return '₱'.number_format($n, 2); }

// ── HELPER: Status badge ─────────────────────────────────────
function badge(string $val): string {
    $map = [
        'in'        => ['#dcfce7','#166534'],
        'low'       => ['#fef9c3','#854d0e'],
        'out'       => ['#fee2e2','#991b1b'],
        'open'      => ['#dcfce7','#166534'],
        'full'      => ['#fee2e2','#991b1b'],
        'completed' => ['#ede9fe','#5b21b6'],
        'ongoing'   => ['#dbeafe','#1e40af'],
        'soon'      => ['#fef9c3','#854d0e'],
        'active'    => ['#dcfce7','#166534'],
        'inactive'  => ['#f1f5f9','#64748b'],
        'confirmed' => ['#dcfce7','#166534'],
        'pending'   => ['#fef9c3','#854d0e'],
        'cancelled' => ['#fee2e2','#991b1b'],
        'customer'  => ['#dbeafe','#1e40af'],
        'trainer'   => ['rgba(128,177,85,.2)','#336a29'],
        'admin'     => ['#ede9fe','#5b21b6'],
    ];
    [$bg, $color] = $map[$val] ?? ['#f1f5f9','#64748b'];
    return "<span style='background:{$bg};color:{$color};border-radius:20px;padding:3px 10px;font-size:.7rem;font-weight:700;text-transform:capitalize;white-space:nowrap'>".e($val)."</span>";
}

// ============================================================
//  CRUD HANDLERS — process POST/GET actions before HTML output
// ============================================================

// ── PRODUCTS ─────────────────────────────────────────────────
if ($page === 'products') {
    if ($action === 'delete' && $id) {
        db()->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
        flash('Product deleted.');
        header('Location: admin.php?page=products'); exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['store','update'])) {
        $d = [
            ':name'       => trim($_POST['name']),
            ':category'   => $_POST['category'],
            ':icon'       => trim($_POST['icon']) ?: '🌿',
            ':description'=> trim($_POST['description']),
            ':price'      => (float)$_POST['price'],
            ':stock'      => (int)$_POST['stock'],
            ':status'     => $_POST['status'],
            ':image_file' => trim($_POST['image_file']) ?: null,
        ];
        if ($action === 'store') {
            $sql = 'INSERT INTO products (name,category,icon,description,price,stock,status,image_file)
                    VALUES (:name,:category,:icon,:description,:price,:stock,:status,:image_file)';
            db()->prepare($sql)->execute($d);
            flash('Product added successfully!');
        } else {
            $d[':id'] = $id;
            $sql = 'UPDATE products SET name=:name,category=:category,icon=:icon,description=:description,
                    price=:price,stock=:stock,status=:status,image_file=:image_file WHERE id=:id';
            db()->prepare($sql)->execute($d);
            flash('Product updated.');
        }
        header('Location: admin.php?page=products'); exit;
    }
}

// ── WORKSHOPS ────────────────────────────────────────────────
if ($page === 'workshops') {
    if ($action === 'delete' && $id) {
        db()->prepare('DELETE FROM workshops WHERE id=?')->execute([$id]);
        flash('Workshop deleted.');
        header('Location: admin.php?page=workshops'); exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['store','update'])) {
        $d = [
            ':icon'             => trim($_POST['icon']) ?: '🌿',
            ':title'            => trim($_POST['title']),
            ':description'      => trim($_POST['description']),
            ':date'             => trim($_POST['date']),
            ':time'             => trim($_POST['time']),
            ':duration'         => trim($_POST['duration']),
            ':location'         => trim($_POST['location']),
            ':max_participants' => (int)$_POST['max_participants'],
            ':enrolled'         => (int)$_POST['enrolled'],
            ':fee'              => (float)$_POST['fee'],
            ':status'           => $_POST['status'],
            ':trainer'          => trim($_POST['trainer']),
        ];
        if ($action === 'store') {
            $sql = 'INSERT INTO workshops (icon,title,description,date,time,duration,location,max_participants,enrolled,fee,status,trainer)
                    VALUES (:icon,:title,:description,:date,:time,:duration,:location,:max_participants,:enrolled,:fee,:status,:trainer)';
            db()->prepare($sql)->execute($d);
            flash('Workshop added!');
        } else {
            $d[':id'] = $id;
            $sql = 'UPDATE workshops SET icon=:icon,title=:title,description=:description,date=:date,
                    time=:time,duration=:duration,location=:location,max_participants=:max_participants,
                    enrolled=:enrolled,fee=:fee,status=:status,trainer=:trainer WHERE id=:id';
            db()->prepare($sql)->execute($d);
            flash('Workshop updated.');
        }
        header('Location: admin.php?page=workshops'); exit;
    }
}

// ── SERVICES ─────────────────────────────────────────────────
if ($page === 'services') {
    if ($action === 'delete' && $id) {
        db()->prepare('DELETE FROM services WHERE id=?')->execute([$id]);
        flash('Service deleted.');
        header('Location: admin.php?page=services'); exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['store','update'])) {
        $d = [
            ':icon'       => trim($_POST['icon']) ?: '🔬',
            ':title'      => trim($_POST['title']),
            ':description'=> trim($_POST['description']),
            ':status'     => $_POST['status'],
            ':sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];
        if ($action === 'store') {
            db()->prepare('INSERT INTO services (icon,title,description,status,sort_order)
                VALUES (:icon,:title,:description,:status,:sort_order)')->execute($d);
            flash('Service added!');
        } else {
            $d[':id'] = $id;
            db()->prepare('UPDATE services SET icon=:icon,title=:title,description=:description,
                status=:status,sort_order=:sort_order WHERE id=:id')->execute($d);
            flash('Service updated.');
        }
        header('Location: admin.php?page=services'); exit;
    }
}

// ── CONSULTATIONS ────────────────────────────────────────────
if ($page === 'consultations') {
    if ($action === 'delete' && $id) {
        db()->prepare('DELETE FROM consultations WHERE id=?')->execute([$id]);
        flash('Consultation deleted.');
        header('Location: admin.php?page=consultations'); exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['store','update'])) {
        $d = [
            ':client_name'=> trim($_POST['client_name']),
            ':email'      => trim($_POST['email']),
            ':phone'      => trim($_POST['phone']) ?: null,
            ':service'    => trim($_POST['service']),
            ':date'       => $_POST['date'] ?: null,
            ':time'       => trim($_POST['time']) ?: null,
            ':topic'      => trim($_POST['topic']) ?: null,
            ':status'     => $_POST['status'],
            ':notes'      => trim($_POST['notes']) ?: null,
        ];
        if ($action === 'store') {
            $sql = 'INSERT INTO consultations (client_name,email,phone,service,date,time,topic,status,notes)
                    VALUES (:client_name,:email,:phone,:service,:date,:time,:topic,:status,:notes)';
            db()->prepare($sql)->execute($d);
            flash('Consultation booking added!');
        } else {
            $d[':id'] = $id;
            $sql = 'UPDATE consultations SET client_name=:client_name,email=:email,phone=:phone,
                    service=:service,date=:date,time=:time,topic=:topic,status=:status,notes=:notes WHERE id=:id';
            db()->prepare($sql)->execute($d);
            flash('Consultation updated.');
        }
        header('Location: admin.php?page=consultations'); exit;
    }
}

// ── USERS ────────────────────────────────────────────────────
if ($page === 'users') {
    if ($action === 'delete' && $id) {
        db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
        flash('User deleted.');
        header('Location: admin.php?page=users'); exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['store','update'])) {
        $d = [
            ':first_name' => trim($_POST['first_name']),
            ':last_name'  => trim($_POST['last_name']),
            ':email'      => trim($_POST['email']),
            ':phone'      => trim($_POST['phone']) ?: null,
            ':role'       => $_POST['role'],
            ':status'     => $_POST['status'],
            ':joined_date'=> $_POST['joined_date'] ?: date('Y-m-d'),
        ];
        if ($action === 'store') {
            db()->prepare('INSERT INTO users (first_name,last_name,email,phone,role,status,joined_date)
                VALUES (:first_name,:last_name,:email,:phone,:role,:status,:joined_date)')->execute($d);
            flash('User added!');
        } else {
            $d[':id'] = $id;
            db()->prepare('UPDATE users SET first_name=:first_name,last_name=:last_name,email=:email,
                phone=:phone,role=:role,status=:status,joined_date=:joined_date WHERE id=:id')->execute($d);
            flash('User updated.');
        }
        header('Location: admin.php?page=users'); exit;
    }
}

// ============================================================
//  FETCH DATA for current page
// ============================================================
$data = [];
$editRow = null;
$search = trim($_GET['q'] ?? '');
$filterStatus = $_GET['status'] ?? '';
$filterCat = $_GET['cat'] ?? '';

switch ($page) {
    case 'dashboard':
        $data['products_total']   = db()->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $data['products_low']     = db()->query("SELECT COUNT(*) FROM products WHERE status='low'")->fetchColumn();
        $data['products_out']     = db()->query("SELECT COUNT(*) FROM products WHERE status='out'")->fetchColumn();
        $data['inventory_value']  = db()->query('SELECT SUM(price*stock) FROM products')->fetchColumn() ?? 0;
        $data['workshops_open']   = db()->query("SELECT COUNT(*) FROM workshops WHERE status='open'")->fetchColumn();
        $data['total_enrolled']   = db()->query('SELECT SUM(enrolled) FROM workshops')->fetchColumn() ?? 0;
        $data['consult_pending']  = db()->query("SELECT COUNT(*) FROM consultations WHERE status='pending'")->fetchColumn();
        $data['users_active']     = db()->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
        $data['categories']       = db()->query('SELECT * FROM v_inventory_summary ORDER BY total_products DESC')->fetchAll();
        $data['recent_consult']   = db()->query('SELECT * FROM consultations ORDER BY created_at DESC LIMIT 5')->fetchAll();
        $data['low_stock_items']  = db()->query("SELECT name,stock,category FROM products WHERE status IN ('low','out') ORDER BY stock ASC LIMIT 6")->fetchAll();
        break;

    case 'products':
        $where = []; $params = [];
        if ($search)       { $where[] = '(name LIKE ? OR description LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($filterCat)    { $where[] = 'category=?';  $params[] = $filterCat; }
        if ($filterStatus) { $where[] = 'status=?';    $params[] = $filterStatus; }
        $sql = 'SELECT * FROM products'.($where ? ' WHERE '.implode(' AND ', $where) : '').' ORDER BY category, name';
        $stmt = db()->prepare($sql); $stmt->execute($params);
        $data['rows'] = $stmt->fetchAll();
        $data['count'] = count($data['rows']);
        if (in_array($action, ['edit']) && $id) $editRow = db()->prepare('SELECT * FROM products WHERE id=?')->execute([$id]) ? db()->prepare('SELECT * FROM products WHERE id=?')->execute([$id]) : null;
        if ($action === 'edit' && $id) { $s=db()->prepare('SELECT * FROM products WHERE id=?'); $s->execute([$id]); $editRow=$s->fetch(); }
        break;

    case 'workshops':
        $where=[]; $params=[];
        if ($filterStatus) { $where[]='status=?'; $params[]=$filterStatus; }
        $sql='SELECT * FROM workshops'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY id';
        $stmt=db()->prepare($sql); $stmt->execute($params);
        $data['rows']=$stmt->fetchAll();
        if ($action==='edit'&&$id) { $s=db()->prepare('SELECT * FROM workshops WHERE id=?'); $s->execute([$id]); $editRow=$s->fetch(); }
        break;

    case 'services':
        $data['rows'] = db()->query('SELECT * FROM services ORDER BY sort_order, id')->fetchAll();
        if ($action==='edit'&&$id) { $s=db()->prepare('SELECT * FROM services WHERE id=?'); $s->execute([$id]); $editRow=$s->fetch(); }
        break;

    case 'consultations':
        $where=[]; $params=[];
        if ($search)       { $where[]='(client_name LIKE ? OR email LIKE ? OR topic LIKE ?)'; $params[]="%$search%"; $params[]="%$search%"; $params[]="%$search%"; }
        if ($filterStatus) { $where[]='status=?'; $params[]=$filterStatus; }
        $sql='SELECT * FROM consultations'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY date DESC, created_at DESC';
        $stmt=db()->prepare($sql); $stmt->execute($params);
        $data['rows']=$stmt->fetchAll();
        $data['services'] = db()->query('SELECT title FROM services WHERE status="active" ORDER BY sort_order')->fetchAll(PDO::FETCH_COLUMN);
        if ($action==='edit'&&$id) { $s=db()->prepare('SELECT * FROM consultations WHERE id=?'); $s->execute([$id]); $editRow=$s->fetch(); }
        break;

    case 'users':
        $where=[]; $params=[];
        if ($search) { $where[]='(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)'; $params[]="%$search%"; $params[]="%$search%"; $params[]="%$search%"; }
        if ($filterStatus) { $where[]='status=?'; $params[]=$filterStatus; }
        $sql='SELECT * FROM users'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY role, first_name';
        $stmt=db()->prepare($sql); $stmt->execute($params);
        $data['rows']=$stmt->fetchAll();
        if ($action==='edit'&&$id) { $s=db()->prepare('SELECT * FROM users WHERE id=?'); $s->execute([$id]); $editRow=$s->fetch(); }
        break;
}

$flash = getFlash();

// ============================================================
//  LOGIN PAGE
// ============================================================
function showLogin(string $err = ''): void { ?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>BahayTek Admin — Login</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:linear-gradient(135deg,#336a29 0%,#2e5e22 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}
.login-box{background:#fff;border-radius:22px;width:400px;overflow:hidden;box-shadow:0 40px 80px rgba(0,0,0,.3)}
.login-head{background:linear-gradient(135deg,#336a29,#498428);padding:34px;text-align:center}
.login-head h1{font-family:'DM Serif Display',serif;font-size:2rem;color:#fff;margin-bottom:4px}<span class="b">.b{color:#c1d95c}</span>
.login-head p{font-size:.8rem;color:rgba(255,255,255,.6)}
.login-body{padding:30px}
label{display:block;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#2e4a1e;margin-bottom:6px}
input[type=password]{width:100%;padding:11px 14px;border:1.5px solid #c8ddb0;border-radius:10px;font-size:.9rem;font-family:inherit;outline:none}
input[type=password]:focus{border-color:#80b155}
.err{background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:9px;font-size:.8rem;margin-bottom:16px}
button{width:100%;padding:13px;border:none;border-radius:11px;background:#336a29;color:#fff;font-weight:800;font-size:.92rem;cursor:pointer;font-family:inherit;margin-top:16px}
button:hover{background:#498428}
</style></head><body>
<div class="login-box">
  <div class="login-head">
    <h1><span class="b">BAHAY</span>TEK</h1>
    <p>Admin Database Panel</p>
  </div>
  <div class="login-body">
    <?php if($err): ?><div class="err">⚠ <?=e($err)?></div><?php endif ?>
    <form method="POST">
      <label>Admin Password</label>
      <input type="password" name="login_password" placeholder="Enter password" autofocus>
      <button type="submit">Enter Admin Panel →</button>
    </form>
  </div>
</div>
</body></html>
<?php }

// ============================================================
//  HTML LAYOUT STARTS
// ============================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= ucfirst($page) ?> — BahayTek Admin</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
/* ── RESET & BASE ─────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:'DM Sans',sans-serif;background:#f5f9f0;color:#1a2e12;display:flex;min-height:100vh}
a{text-decoration:none;color:inherit}
:root{
  --forest:#336a29;--green:#498428;--emerald:#80b155;--mint:#c1d95c;
  --cream:#f5f9f0;--sand:#edf5e3;--border:#c8ddb0;--dark:#1a2e12;
  --charcoal:#2e4a1e;--gray:#5a7248;--white:#fff;
  --sh:0 4px 24px rgba(51,106,41,.09);
}

/* ── SIDEBAR ──────────────────────────────────── */
.sidebar{width:220px;min-height:100vh;background:linear-gradient(180deg,var(--forest) 0%,#2e5e22 100%);flex-shrink:0;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow:hidden}
.sb-logo{padding:20px 20px 16px;border-bottom:1px solid rgba(255,255,255,.1)}
.sb-logo .brand{font-family:'DM Serif Display',serif;font-size:1.15rem;color:#fff;display:block;line-height:1}
.sb-logo .brand .b{color:var(--mint)}
.sb-logo .sub{font-size:.5rem;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:rgba(193,217,92,.6);margin-top:3px;display:block}
nav{padding:12px 0;flex:1}
.nav-link{display:flex;align-items:center;gap:12px;padding:11px 20px;color:rgba(255,255,255,.6);font-size:.84rem;font-weight:500;transition:all .15s;border-left:3px solid transparent;cursor:pointer}
.nav-link:hover{color:#fff;background:rgba(255,255,255,.07)}
.nav-link.active{color:#fff;background:rgba(193,217,92,.15);border-left-color:var(--mint);font-weight:700}
.nav-link .ico{font-size:1rem;flex-shrink:0;width:20px;text-align:center}
.sb-footer{padding:14px 20px;border-top:1px solid rgba(255,255,255,.08)}
.sb-footer p{font-size:.65rem;color:rgba(255,255,255,.28);line-height:1.7}
.sb-footer a{color:rgba(255,255,255,.35);font-size:.72rem}
.sb-footer a:hover{color:var(--mint)}

/* ── MAIN ─────────────────────────────────────── */
.main{flex:1;min-width:0;display:flex;flex-direction:column}
.topbar{background:#fff;border-bottom:1px solid var(--border);padding:14px 28px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}
.topbar-title{font-weight:700;font-size:.9rem;color:var(--dark)}
.topbar-right{display:flex;align-items:center;gap:12px;font-size:.8rem;color:var(--gray)}
.avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--forest),var(--emerald));color:#fff;font-weight:800;font-size:.8rem;display:flex;align-items:center;justify-content:center}
.content{padding:28px;flex:1}

/* ── FLASH ────────────────────────────────────── */
.flash{padding:13px 18px;border-radius:11px;margin-bottom:20px;font-size:.84rem;font-weight:600;display:flex;align-items:center;gap:10px}
.flash.success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
.flash.error  {background:#fee2e2;color:#991b1b;border:1px solid #fecaca}

/* ── PAGE HEADER ──────────────────────────────── */
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px}
.page-header h1{font-family:'DM Serif Display',serif;font-size:1.6rem;color:var(--dark);margin-bottom:2px}
.page-header p{font-size:.8rem;color:var(--gray)}
.btn{padding:10px 20px;border-radius:10px;font-size:.84rem;font-weight:700;cursor:pointer;border:none;font-family:inherit;transition:all .2s;display:inline-flex;align-items:center;gap:7px}
.btn-primary{background:var(--forest);color:#fff}
.btn-primary:hover{background:var(--emerald)}
.btn-sm{padding:5px 12px;font-size:.74rem;border-radius:7px;font-weight:600}
.btn-outline{background:#fff;border:1.5px solid var(--border);color:var(--charcoal)}
.btn-outline:hover{border-color:var(--emerald);color:var(--forest)}
.btn-danger{background:#fff;border:1.5px solid #fecaca;color:#dc2626}
.btn-danger:hover{background:#fee2e2}

/* ── STATS CARDS ──────────────────────────────── */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;margin-bottom:24px}
.stat-card{background:#fff;border-radius:14px;border:1.5px solid var(--border);padding:20px;position:relative;overflow:hidden}
.stat-card::after{content:'';position:absolute;top:-20px;right:-16px;width:80px;height:80px;border-radius:50%;background:currentColor;opacity:.05}
.stat-icon{font-size:1.4rem;margin-bottom:8px}
.stat-num{font-family:'DM Mono',monospace;font-size:1.9rem;font-weight:500;line-height:1;margin-bottom:4px}
.stat-lbl{font-weight:700;font-size:.84rem;color:var(--dark);margin-bottom:3px}
.stat-sub{font-size:.72rem;color:var(--gray)}

/* ── TABLE ────────────────────────────────────── */
.table-wrap{background:#fff;border-radius:14px;border:1.5px solid var(--border);overflow:hidden;margin-bottom:24px}
.table-toolbar{padding:16px 18px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;border-bottom:1px solid var(--sand)}
table{width:100%;border-collapse:collapse;font-size:.82rem}
th{padding:11px 14px;text-align:left;font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--gray);background:var(--cream);border-bottom:1.5px solid var(--border);white-space:nowrap}
td{padding:11px 14px;border-bottom:1px solid var(--sand);vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:nth-child(even) td{background:#fafdf6}
tr:hover td{background:rgba(193,217,92,.06)}
.td-name{font-weight:700;color:var(--dark);margin-bottom:2px}
.td-sub{font-size:.72rem;color:var(--gray);max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.price{font-family:'DM Mono',monospace;color:var(--forest);font-weight:600}
.no-rows{text-align:center;padding:48px;color:var(--gray)}
.no-rows .ico{font-size:2rem;margin-bottom:10px}

/* ── SEARCH BAR ───────────────────────────────── */
.search-bar{display:flex;align-items:center;gap:8px;background:var(--cream);border:1.5px solid var(--border);border-radius:10px;padding:8px 14px;flex:1;max-width:320px}
.search-bar:focus-within{border-color:var(--emerald)}
.search-bar input{border:none;background:none;font-family:inherit;font-size:.84rem;color:var(--dark);outline:none;flex:1;min-width:0}

/* ── SELECT FILTER ────────────────────────────── */
select.filter-sel{padding:8px 12px;border:1.5px solid var(--border);border-radius:9px;font-size:.82rem;font-family:inherit;color:var(--charcoal);background:#fff;outline:none;cursor:pointer}
select.filter-sel:focus{border-color:var(--emerald)}

/* ── FORMS ────────────────────────────────────── */
.form-card{background:#fff;border-radius:14px;border:1.5px solid var(--border);padding:28px;margin-bottom:24px;max-width:780px}
.form-card h2{font-family:'DM Serif Display',serif;font-size:1.2rem;color:var(--dark);margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--sand)}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:0 20px}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:0 20px}
.col-span-2{grid-column:1/-1}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:var(--charcoal);margin-bottom:5px}
.form-group input,.form-group select,.form-group textarea{
  width:100%;padding:10px 13px;border:1.5px solid var(--border);border-radius:10px;
  font-size:.84rem;font-family:inherit;color:var(--dark);background:var(--cream);
  outline:none;transition:border-color .2s
}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{border-color:var(--emerald);background:#fff}
.form-group textarea{resize:vertical;min-height:80px}
.form-group input::placeholder,.form-group textarea::placeholder{color:#a8c07e}
.form-actions{display:flex;gap:10px;margin-top:6px}
.form-actions .btn{flex:1;justify-content:center;padding:12px}

/* ── WORKSHOP CARDS ───────────────────────────── */
.ws-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;margin-bottom:24px}
.ws-card{background:#fff;border-radius:16px;border:1.5px solid var(--border);overflow:hidden;transition:all .2s}
.ws-card:hover{box-shadow:var(--sh);transform:translateY(-3px)}
.ws-top{padding:16px 18px 0;display:flex;justify-content:space-between;align-items:flex-start}
.ws-icon{width:42px;height:42px;border-radius:11px;background:var(--cream);display:flex;align-items:center;justify-content:center;font-size:1.2rem}
.ws-body{padding:12px 18px 18px}
.ws-title{font-weight:800;font-size:.9rem;color:var(--dark);margin-bottom:4px}
.ws-desc{font-size:.76rem;color:var(--gray);margin-bottom:12px;line-height:1.55}
.ws-meta{display:flex;flex-direction:column;gap:4px;margin-bottom:12px;font-size:.74rem;color:var(--gray)}
.ws-meta span{display:flex;gap:7px}
.ws-meta strong{color:var(--charcoal)}
.progress-bar{background:var(--sand);border-radius:20px;height:5px;overflow:hidden;margin-bottom:12px}
.progress-fill{height:100%;background:linear-gradient(90deg,var(--emerald),var(--mint));border-radius:20px}
.ws-actions{display:flex;gap:8px}

/* ── SERVICE CARDS ────────────────────────────── */
.svc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin-bottom:24px}
.svc-card{background:#fff;border-radius:16px;border:1.5px solid var(--border);padding:24px;position:relative;overflow:hidden}
.svc-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--emerald),var(--mint))}
.svc-icon{width:48px;height:48px;border-radius:12px;background:var(--cream);display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:14px}
.svc-title{font-weight:800;font-size:.95rem;color:var(--dark);margin-bottom:6px}
.svc-desc{font-size:.78rem;color:var(--gray);line-height:1.65;margin-bottom:16px}
.svc-footer{display:flex;align-items:center;justify-content:space-between}

/* ── USER AVATAR ──────────────────────────────── */
.u-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--emerald),var(--mint));color:#fff;font-weight:800;font-size:.78rem;display:flex;align-items:center;justify-content:center;flex-shrink:0}

/* ── DASHBOARD EXTRAS ─────────────────────────── */
.dash-2col{display:grid;grid-template-columns:1.3fr 1fr;gap:16px;margin-bottom:16px}
.dash-card{background:#fff;border-radius:14px;border:1.5px solid var(--border);padding:20px}
.dash-card h3{font-weight:800;font-size:.88rem;color:var(--dark);margin-bottom:16px;display:flex;align-items:center;gap:8px}
.dash-card h3::before{content:'';width:8px;height:8px;border-radius:2px;background:var(--emerald);display:inline-block;flex-shrink:0}
.bar-row{margin-bottom:10px}
.bar-meta{display:flex;justify-content:space-between;font-size:.76rem;margin-bottom:4px}
.bar-meta span{color:var(--charcoal);font-weight:600}
.bar-meta small{color:var(--gray)}
.bar-bg{background:var(--sand);border-radius:20px;height:7px;overflow:hidden}
.bar-fill{height:100%;background:linear-gradient(90deg,var(--emerald),var(--mint));border-radius:20px}
.activity-row{display:flex;gap:12px;padding-bottom:12px;margin-bottom:12px;border-bottom:1px solid var(--sand);align-items:flex-start}
.activity-row:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}
.a-icon{width:32px;height:32px;border-radius:9px;background:var(--cream);display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0}
.a-name{font-size:.78rem;font-weight:700;color:var(--dark);margin-bottom:2px}
.a-sub{font-size:.72rem;color:var(--gray)}
.inv-banner{background:linear-gradient(135deg,var(--forest),var(--green));border-radius:14px;padding:20px 26px;display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.inv-banner .lbl{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:rgba(255,255,255,.5);margin-bottom:4px}
.inv-banner .val{font-family:'DM Mono',monospace;font-size:2rem;color:var(--mint);font-weight:500}
.inv-banner .sub{font-size:.74rem;color:rgba(255,255,255,.45);margin-top:4px}
.alert-banner{background:#fef9c3;border:1.5px solid #fde68a;border-radius:11px;padding:13px 18px;display:flex;align-items:center;gap:12px;margin-bottom:16px}
.alert-banner p{font-size:.8rem;color:#92400e;font-weight:600}
.alert-banner small{font-size:.74rem;color:#b45309;font-weight:400;display:block}

/* ── PAGINATION ───────────────────────────────── */
.pg{display:flex;align-items:center;gap:4px;justify-content:flex-end;padding:12px 18px}
.pg a,.pg span{padding:5px 10px;border-radius:7px;font-size:.78rem;font-weight:600;color:var(--gray);background:var(--cream);border:1px solid var(--border)}
.pg a:hover{border-color:var(--emerald);color:var(--forest)}
.pg .cur{background:var(--forest);color:#fff;border-color:var(--forest)}

/* ── RESPONSIVE ───────────────────────────────── */
@media(max-width:900px){
  .sidebar{width:68px}.sidebar .sb-logo .sub,.sidebar nav .nav-link span,.sidebar .sb-footer p,.sidebar .sb-footer a{display:none}
  .sb-logo{padding:14px 0;text-align:center}.sb-logo .brand{font-size:.8rem}.nav-link{padding:12px 0;justify-content:center}
  .dash-2col{grid-template-columns:1fr}.grid-2,.grid-3{grid-template-columns:1fr}
}
</style>
</head>
<body>

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar">
  <div class="sb-logo">
    <span class="brand"><span class="b">BAHAY</span>TEK</span>
    <span class="sub">Admin Panel</span>
  </div>
  <nav>
    <?php
    $nav = [
      'dashboard'     => ['🏠','Dashboard'],
      'products'      => ['📦','Products'],
      'workshops'     => ['🎓','Workshops'],
      'services'      => ['⚙️','Services'],
      'consultations' => ['📅','Consultations'],
      'users'         => ['👥','Users'],
    ];
    foreach ($nav as $pg => [$ico, $lbl]): ?>
      <a href="admin.php?page=<?=$pg?>" class="nav-link <?=$page===$pg?'active':''?>">
        <span class="ico"><?=$ico?></span><span><?=$lbl?></span>
      </a>
    <?php endforeach ?>
  </nav>
  <div class="sb-footer">
    <p>© 2025 BahayTek<br>Camarines Norte<br>BSIT 2A IPT Project</p>
  </div>
  </div>
</aside>

<!-- ══ MAIN ══ -->
<div class="main">
  <div class="topbar">
    <div class="topbar-title">
      <?= $nav[$page][0] ?? '' ?> <?= ucfirst($page) ?>
    </div>
    <div class="topbar-right">
      <a href="index.html" style="font-size:.84rem;color:var(--forest);font-weight:600;margin-right:16px;text-decoration:none">← Back to Website</a>
      <span><?= date('M d, Y') ?></span>
      <div class="avatar">A</div>
      <a href="admin.php?logout=1" style="font-size:.84rem;color:var(--gray);margin-left:12px;text-decoration:none;cursor:pointer">Logout</a>
    </div>
  </div>

  <div class="content">
    <?php if ($flash): ?>
      <div class="flash <?= $flash['type'] ?>">
        <?= $flash['type']==='success' ? '✓' : '⚠' ?> <?= e($flash['msg']) ?>
      </div>
    <?php endif ?>

<?php
// ============================================================
//  PAGE VIEWS
// ============================================================

// ── DASHBOARD ────────────────────────────────────────────────
if ($page === 'dashboard'):
  $inv = (float)$data['inventory_value'];
  $maxCat = max(array_column($data['categories'], 'total_products') ?: [1]);
?>
<div class="page-header">
  <div><h1>Dashboard</h1><p>BahayTek Operations Overview — <?= date('F Y') ?></p></div>
</div>

<?php if ($data['products_low'] > 0): ?>
<div class="alert-banner">
  <span style="font-size:1.3rem">⚠️</span>
  <div>
    <p>Low Stock Alert — <?= $data['products_low'] ?> product<?= $data['products_low']>1?'s are':' is'?> running low.</p>
    <small><a href="admin.php?page=products&status=low" style="color:#92400e;text-decoration:underline">View low stock products →</a></small>
  </div>
</div>
<?php endif ?>

<div class="inv-banner">
  <div>
    <div class="lbl">Total Inventory Value</div>
    <div class="val"><?= peso($inv) ?></div>
    <div class="sub">Across <?= $data['products_total'] ?> active SKUs</div>
  </div>
  <span style="font-size:3.5rem;opacity:.25">🌿</span>
</div>

<div class="stats-grid">
  <?php
  $cards = [
    ['📦','Products',     $data['products_total'],   $data['products_low'].' low · '.$data['products_out'].' out','#336a29'],
    ['🎓','Open Workshops',$data['workshops_open'],   $data['total_enrolled'].' total enrolled',              '#1d4ed8'],
    ['📅','Consultations', (int)db()->query('SELECT COUNT(*) FROM consultations')->fetchColumn(),
                                                       $data['consult_pending'].' pending',                    '#b68a3b'],
    ['👥','Active Users',  $data['users_active'],     'of '.( (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn() ).' registered', '#6d28d9'],
  ];
  foreach ($cards as [$ico,$lbl,$val,$sub,$col]): ?>
    <div class="stat-card" style="color:<?=$col?>">
      <div class="stat-icon"><?=$ico?></div>
      <div class="stat-num" style="color:<?=$col?>"><?=$val?></div>
      <div class="stat-lbl"><?=$lbl?></div>
      <div class="stat-sub"><?=$sub?></div>
    </div>
  <?php endforeach ?>
</div>

<div class="dash-2col">
  <div class="dash-card">
    <h3>Products by Category</h3>
    <?php foreach ($data['categories'] as $cat): ?>
      <div class="bar-row">
        <div class="bar-meta">
          <span><?= e($cat['category']) ?></span>
          <small><?= $cat['total_products'] ?> items · <?= peso($cat['inventory_value']) ?></small>
        </div>
        <div class="bar-bg">
          <div class="bar-fill" style="width:<?= round($cat['total_products']/$maxCat*100) ?>%"></div>
        </div>
      </div>
    <?php endforeach ?>
  </div>

  <div class="dash-card">
    <h3>Recent Consultations</h3>
    <?php foreach ($data['recent_consult'] as $c): ?>
      <div class="activity-row">
        <div class="a-icon">📅</div>
        <div style="flex:1">
          <div class="a-name"><?= e($c['client_name']) ?></div>
          <div class="a-sub"><?= e($c['service']) ?> · <?= e($c['date'] ?? 'TBD') ?></div>
        </div>
        <?= badge($c['status']) ?>
      </div>
    <?php endforeach ?>
  </div>
</div>

<?php if (!empty($data['low_stock_items'])): ?>
<div class="dash-card">
  <h3 style="color:#92400e;margin-bottom:14px">⚠ Low / Out of Stock</h3>
  <table>
    <thead><tr><th>Product</th><th>Category</th><th>Stock</th></tr></thead>
    <tbody>
      <?php foreach ($data['low_stock_items'] as $item): ?>
        <tr>
          <td><div class="td-name"><?= e($item['name']) ?></div></td>
          <td><?= e($item['category']) ?></td>
          <td style="font-family:monospace;font-weight:700;color:<?= $item['stock']<=2?'#dc2626':'#b68a3b' ?>"><?= $item['stock'] ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>
<?php endif ?>

<?php // ── PRODUCTS ──────────────────────────────────────────────
elseif ($page === 'products'):
  $cats = ['Solar','Water','Biogas','Cooking','Garden','Kiln','Fuel','Tools','Compost'];
  if (in_array($action, ['add','edit'])): ?>

<div class="page-header">
  <div><h1><?= $action==='add'?'Add Product':'Edit Product' ?></h1><p><?= $action==='add'?'Create a new catalog item':'Update product details'?></p></div>
  <a href="admin.php?page=products" class="btn btn-outline">← Back to Products</a>
</div>
<div class="form-card">
  <h2><?= $action==='add'?'New Product Details':'Edit: '.e($editRow['name']??'') ?></h2>
  <form method="POST" action="admin.php?page=products&action=<?= $action==='add'?'store':('update&id='.$id) ?>">
    <div class="grid-2">
      <div class="col-span-2 form-group">
        <label>Product Name *</label>
        <input type="text" name="name" value="<?= e($editRow['name']??'') ?>" required placeholder="e.g. Solar Box Oven">
      </div>
      <div class="form-group">
        <label>Category *</label>
        <select name="category" required>
          <?php foreach ($cats as $c): ?>
            <option value="<?=$c?>" <?= ($editRow['category']??'')===$c?'selected':'' ?>><?=$c?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="form-group">
        <label>Icon (emoji)</label>
        <input type="text" name="icon" value="<?= e($editRow['icon']??'🌿') ?>" placeholder="🌿">
      </div>
      <div class="form-group">
        <label>Price (₱) *</label>
        <input type="number" name="price" step="0.01" min="0" value="<?= e($editRow['price']??'') ?>" required placeholder="0.00">
      </div>
      <div class="form-group">
        <label>Stock (units) *</label>
        <input type="number" name="stock" min="0" value="<?= e($editRow['stock']??'') ?>" required placeholder="0">
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <option value="in"  <?= ($editRow['status']??'')==='in' ?'selected':'' ?>>In Stock</option>
          <option value="low" <?= ($editRow['status']??'')==='low'?'selected':'' ?>>Low Stock</option>
          <option value="out" <?= ($editRow['status']??'')==='out'?'selected':'' ?>>Out of Stock</option>
        </select>
      </div>
      <div class="form-group">
        <label>Image Filename (optional)</label>
        <input type="text" name="image_file" value="<?= e($editRow['image_file']??'') ?>" placeholder="The Solar Box Oven.jpg">
      </div>
      <div class="col-span-2 form-group">
        <label>Description</label>
        <textarea name="description" rows="3"><?= e($editRow['description']??'') ?></textarea>
      </div>
    </div>
    <div class="form-actions">
      <a href="admin.php?page=products" class="btn btn-outline">Cancel</a>
      <button type="submit" class="btn btn-primary">💾 Save Product</button>
    </div>
  </form>
</div>

<?php else: ?>
<div class="page-header">
  <div>
    <h1>Products</h1>
    <p><?= $data['count'] ?> items<?= $filterCat?" · {$filterCat}":''; ?></p>
  </div>
  <a href="admin.php?page=products&action=add" class="btn btn-primary">+ Add Product</a>
</div>
<div class="table-wrap">
  <div class="table-toolbar">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;width:100%">
      <input type="hidden" name="page" value="products">
      <div class="search-bar"><span>🔍</span><input name="q" value="<?=e($search)?>" placeholder="Search products..."></div>
      <select name="cat" class="filter-sel" onchange="this.form.submit()">
        <option value="">All Categories</option>
        <?php foreach ($cats as $c): ?><option value="<?=$c?>" <?=$filterCat===$c?'selected':''?>><?=$c?></option><?php endforeach ?>
      </select>
      <select name="status" class="filter-sel" onchange="this.form.submit()">
        <option value="">All Status</option>
        <option value="in"  <?=$filterStatus==='in' ?'selected':''?>>In Stock</option>
        <option value="low" <?=$filterStatus==='low'?'selected':''?>>Low Stock</option>
        <option value="out" <?=$filterStatus==='out'?'selected':''?>>Out of Stock</option>
      </select>
      <button type="submit" class="btn btn-outline btn-sm">🔍 Search</button>
      <?php if($search||$filterCat||$filterStatus): ?><a href="admin.php?page=products" class="btn btn-outline btn-sm">✕ Clear</a><?php endif ?>
    </form>
  </div>
  <div style="overflow-x:auto">
    <table>
      <thead><tr><th>Icon</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody>
        <?php if (empty($data['rows'])): ?>
          <tr><td colspan="7"><div class="no-rows"><div class="ico">🔍</div>No products found</div></td></tr>
        <?php else: foreach ($data['rows'] as $row): ?>
          <tr>
            <td style="font-size:1.3rem"><?= e($row['icon']) ?></td>
            <td><div class="td-name"><?= e($row['name']) ?></div><div class="td-sub"><?= e($row['description']) ?></div></td>
            <td style="color:#80b155;font-weight:700;font-size:.78rem"><?= e($row['category']) ?></td>
            <td class="price"><?= peso((float)$row['price']) ?></td>
            <td style="text-align:center;font-weight:700;color:<?= $row['stock']<5?'#b68a3b':'#498428' ?>"><?= $row['stock'] ?></td>
            <td><?= badge($row['status']) ?></td>
            <td style="text-align:right">
              <div style="display:flex;gap:6px;justify-content:flex-end">
                <a href="admin.php?page=products&action=edit&id=<?=$row['id']?>" class="btn btn-outline btn-sm">Edit</a>
                <a href="admin.php?page=products&action=delete&id=<?=$row['id']?>" class="btn btn-danger btn-sm"
                   onclick="return confirm('Delete \'<?= addslashes($row['name']) ?>\'?')">Del</a>
              </div>
            </td>
          </tr>
        <?php endforeach; endif ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif ?>

<?php // ── WORKSHOPS ─────────────────────────────────────────────
elseif ($page === 'workshops'):
  if (in_array($action, ['add','edit'])): ?>
<div class="page-header">
  <div><h1><?= $action==='add'?'Add Workshop':'Edit Workshop'?></h1></div>
  <a href="admin.php?page=workshops" class="btn btn-outline">← Back</a>
</div>
<div class="form-card">
  <h2><?= $action==='add'?'New Workshop':'Edit: '.e($editRow['title']??'')?></h2>
  <form method="POST" action="admin.php?page=workshops&action=<?= $action==='add'?'store':('update&id='.$id) ?>">
    <div class="grid-2">
      <div class="col-span-2 form-group"><label>Workshop Title *</label><input type="text" name="title" value="<?=e($editRow['title']??'')?>" required placeholder="e.g. Biogas Digester Construction"></div>
      <div class="form-group"><label>Icon (emoji)</label><input type="text" name="icon" value="<?=e($editRow['icon']??'🌿')?>"></div>
      <div class="form-group"><label>Trainer *</label><input type="text" name="trainer" value="<?=e($editRow['trainer']??'')?>" required placeholder="Engr. Reyes"></div>
      <div class="form-group"><label>Date</label><input type="text" name="date" value="<?=e($editRow['date']??'')?>" placeholder="May 10, 2025"></div>
      <div class="form-group"><label>Time</label><input type="text" name="time" value="<?=e($editRow['time']??'')?>" placeholder="8:00 AM – 5:00 PM"></div>
      <div class="form-group"><label>Duration</label><input type="text" name="duration" value="<?=e($editRow['duration']??'1 day')?>" placeholder="1 day"></div>
      <div class="form-group"><label>Location</label><input type="text" name="location" value="<?=e($editRow['location']??'Bahay Teknik Lab')?>"></div>
      <div class="form-group"><label>Max Participants</label><input type="number" name="max_participants" min="1" value="<?=e($editRow['max_participants']??20)?>"></div>
      <div class="form-group"><label>Enrolled</label><input type="number" name="enrolled" min="0" value="<?=e($editRow['enrolled']??0)?>"></div>
      <div class="form-group"><label>Fee (₱)</label><input type="number" name="fee" step="0.01" min="0" value="<?=e($editRow['fee']??0)?>"></div>
      <div class="form-group"><label>Status</label>
        <select name="status">
          <?php foreach (['open','ongoing','full','completed','soon'] as $s): ?>
            <option value="<?=$s?>" <?=($editRow['status']??'')===$s?'selected':''?>><?=$s?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="col-span-2 form-group"><label>Description</label><textarea name="description"><?=e($editRow['description']??'')?></textarea></div>
    </div>
    <div class="form-actions">
      <a href="admin.php?page=workshops" class="btn btn-outline">Cancel</a>
      <button type="submit" class="btn btn-primary">💾 Save Workshop</button>
    </div>
  </form>
</div>

<?php else: ?>
<div class="page-header">
  <div><h1>Workshops</h1><p><?= count($data['rows']) ?> programs</p></div>
  <a href="admin.php?page=workshops&action=add" class="btn btn-primary">+ Add Workshop</a>
</div>
<form method="GET" style="margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap">
  <input type="hidden" name="page" value="workshops">
  <select name="status" class="filter-sel" onchange="this.form.submit()">
    <option value="">All Status</option>
    <?php foreach (['open','ongoing','full','completed','soon'] as $s): ?>
      <option value="<?=$s?>" <?=$filterStatus===$s?'selected':''?>><?=$s?></option>
    <?php endforeach ?>
  </select>
  <?php if($filterStatus): ?><a href="admin.php?page=workshops" class="btn btn-outline btn-sm">✕ Clear</a><?php endif ?>
</form>
<div class="ws-grid">
  <?php foreach ($data['rows'] as $w):
    $pct = $w['max_participants'] > 0 ? round($w['enrolled']/$w['max_participants']*100) : 0; ?>
    <div class="ws-card">
      <div class="ws-top">
        <div class="ws-icon"><?= e($w['icon']) ?></div>
        <?= badge($w['status']) ?>
      </div>
      <div class="ws-body">
        <div class="ws-title"><?= e($w['title']) ?></div>
        <div class="ws-desc"><?= e($w['description']) ?></div>
        <div class="ws-meta">
          <span>📅 <strong><?= e($w['date']) ?></strong></span>
          <span>⏰ <strong><?= e($w['time']) ?></strong></span>
          <span>📍 <strong><?= e($w['location']) ?></strong></span>
          <span>👤 <strong><?= e($w['trainer']) ?></strong></span>
          <span>💰 <strong><?= peso((float)$w['fee']) ?></strong></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.7rem;color:var(--gray);margin-bottom:4px">
          <span>Enrollment</span><span style="font-weight:700;color:var(--dark)"><?= $w['enrolled'] ?>/<?= $w['max_participants'] ?></span>
        </div>
        <div class="progress-bar"><div class="progress-fill" style="width:<?=$pct?>%"></div></div>
        <div class="ws-actions">
          <a href="admin.php?page=workshops&action=edit&id=<?=$w['id']?>" class="btn btn-outline btn-sm" style="flex:1;justify-content:center">Edit</a>
          <a href="admin.php?page=workshops&action=delete&id=<?=$w['id']?>" class="btn btn-danger btn-sm"
             onclick="return confirm('Delete this workshop?')">Del</a>
        </div>
      </div>
    </div>
  <?php endforeach ?>
</div>
<?php endif ?>

<?php // ── SERVICES ──────────────────────────────────────────────
elseif ($page === 'services'):
  if (in_array($action, ['add','edit'])): ?>
<div class="page-header">
  <div><h1><?= $action==='add'?'Add Service':'Edit Service'?></h1></div>
  <a href="admin.php?page=services" class="btn btn-outline">← Back</a>
</div>
<div class="form-card">
  <h2><?= $action==='add'?'New Service':'Edit: '.e($editRow['title']??'')?></h2>
  <form method="POST" action="admin.php?page=services&action=<?= $action==='add'?'store':('update&id='.$id) ?>">
    <div class="grid-2">
      <div class="form-group"><label>Icon (emoji)</label><input type="text" name="icon" value="<?=e($editRow['icon']??'🔬')?>"></div>
      <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" min="0" value="<?=e($editRow['sort_order']??0)?>"></div>
      <div class="col-span-2 form-group"><label>Service Title *</label><input type="text" name="title" value="<?=e($editRow['title']??'')?>" required placeholder="e.g. Research"></div>
      <div class="col-span-2 form-group"><label>Description</label><textarea name="description" rows="4"><?=e($editRow['description']??'')?></textarea></div>
      <div class="form-group"><label>Status</label><select name="status"><option value="active" <?=($editRow['status']??'')==='active'?'selected':''?>>Active</option><option value="inactive" <?=($editRow['status']??'')==='inactive'?'selected':''?>>Inactive</option></select></div>
    </div>
    <div class="form-actions">
      <a href="admin.php?page=services" class="btn btn-outline">Cancel</a>
      <button type="submit" class="btn btn-primary">💾 Save Service</button>
    </div>
  </form>
</div>

<?php else: ?>
<div class="page-header">
  <div><h1>Services</h1><p><?= count($data['rows']) ?> offerings</p></div>
  <a href="admin.php?page=services&action=add" class="btn btn-primary">+ Add Service</a>
</div>
<div class="svc-grid">
  <?php foreach ($data['rows'] as $s): ?>
    <div class="svc-card">
      <div class="svc-icon"><?= e($s['icon']) ?></div>
      <div class="svc-title"><?= e($s['title']) ?></div>
      <div class="svc-desc"><?= e($s['description']) ?></div>
      <div class="svc-footer">
        <?= badge($s['status']) ?>
        <div style="display:flex;gap:7px">
          <a href="admin.php?page=services&action=edit&id=<?=$s['id']?>" class="btn btn-outline btn-sm">Edit</a>
          <a href="admin.php?page=services&action=delete&id=<?=$s['id']?>" class="btn btn-danger btn-sm"
             onclick="return confirm('Delete this service?')">Del</a>
        </div>
      </div>
    </div>
  <?php endforeach ?>
</div>
<?php endif ?>

<?php // ── CONSULTATIONS ─────────────────────────────────────────
elseif ($page === 'consultations'):
  if (in_array($action, ['add','edit'])): ?>
<div class="page-header">
  <div><h1><?= $action==='add'?'Add Booking':'Edit Booking'?></h1></div>
  <a href="admin.php?page=consultations" class="btn btn-outline">← Back</a>
</div>
<div class="form-card">
  <h2><?= $action==='add'?'New Consultation Booking':'Edit Booking #'.$id?></h2>
  <form method="POST" action="admin.php?page=consultations&action=<?= $action==='add'?'store':('update&id='.$id) ?>">
    <div class="grid-2">
      <div class="form-group"><label>Client Name *</label><input type="text" name="client_name" value="<?=e($editRow['client_name']??'')?>" required placeholder="Full Name"></div>
      <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?=e($editRow['phone']??'')?>" placeholder="09XXXXXXXXX"></div>
      <div class="col-span-2 form-group"><label>Email *</label><input type="email" name="email" value="<?=e($editRow['email']??'')?>" required placeholder="email@example.com"></div>
      <div class="form-group"><label>Service *</label>
        <select name="service" required>
          <?php foreach ($data['services'] as $svc): ?>
            <option value="<?=e($svc)?>" <?=($editRow['service']??'')===$svc?'selected':''?>><?=e($svc)?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="form-group"><label>Status</label>
        <select name="status">
          <option value="pending"   <?=($editRow['status']??'')==='pending'   ?'selected':''?>>Pending</option>
          <option value="confirmed" <?=($editRow['status']??'')==='confirmed' ?'selected':''?>>Confirmed</option>
          <option value="cancelled" <?=($editRow['status']??'')==='cancelled' ?'selected':''?>>Cancelled</option>
        </select>
      </div>
      <div class="form-group"><label>Date</label><input type="date" name="date" value="<?=e($editRow['date']??'')?>"></div>
      <div class="form-group"><label>Time</label><input type="text" name="time" value="<?=e($editRow['time']??'')?>" placeholder="9:00 AM"></div>
      <div class="col-span-2 form-group"><label>Topic / Notes</label><textarea name="topic"><?=e($editRow['topic']??'')?></textarea></div>
      <div class="col-span-2 form-group"><label>Internal Notes</label><textarea name="notes"><?=e($editRow['notes']??'')?></textarea></div>
    </div>
    <div class="form-actions">
      <a href="admin.php?page=consultations" class="btn btn-outline">Cancel</a>
      <button type="submit" class="btn btn-primary">💾 Save Booking</button>
    </div>
  </form>
</div>

<?php else: ?>
<div class="page-header">
  <div><h1>Consultations</h1><p><?= count($data['rows']) ?> booking records</p></div>
  <a href="admin.php?page=consultations&action=add" class="btn btn-primary">+ Add Booking</a>
</div>
<div class="table-wrap">
  <div class="table-toolbar">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;width:100%">
      <input type="hidden" name="page" value="consultations">
      <div class="search-bar"><span>🔍</span><input name="q" value="<?=e($search)?>" placeholder="Search by name, email..."></div>
      <select name="status" class="filter-sel" onchange="this.form.submit()">
        <option value="">All Status</option>
        <option value="pending"   <?=$filterStatus==='pending'  ?'selected':''?>>Pending</option>
        <option value="confirmed" <?=$filterStatus==='confirmed'?'selected':''?>>Confirmed</option>
        <option value="cancelled" <?=$filterStatus==='cancelled'?'selected':''?>>Cancelled</option>
      </select>
      <button type="submit" class="btn btn-outline btn-sm">🔍 Search</button>
      <?php if($search||$filterStatus): ?><a href="admin.php?page=consultations" class="btn btn-outline btn-sm">✕ Clear</a><?php endif ?>
    </form>
  </div>
  <div style="overflow-x:auto">
    <table>
      <thead><tr><th>Client</th><th>Service</th><th>Date & Time</th><th>Topic</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody>
        <?php if (empty($data['rows'])): ?>
          <tr><td colspan="6"><div class="no-rows"><div class="ico">📅</div>No consultations found</div></td></tr>
        <?php else: foreach ($data['rows'] as $c): ?>
          <tr>
            <td>
              <div class="td-name"><?=e($c['client_name'])?></div>
              <div class="td-sub"><?=e($c['email'])?> · <?=e($c['phone']??'')?></div>
            </td>
            <td style="color:#80b155;font-weight:700;font-size:.78rem;white-space:nowrap"><?=e($c['service'])?></td>
            <td><div style="font-size:.8rem"><?=e($c['date']??'—')?></div><div style="font-size:.74rem;color:var(--gray)"><?=e($c['time']??'')?></div></td>
            <td style="font-size:.76rem;color:var(--gray);max-width:200px"><?=e(mb_strimwidth($c['topic']??'',0,60,'…'))?></td>
            <td><?=badge($c['status'])?></td>
            <td style="text-align:right">
              <div style="display:flex;gap:6px;justify-content:flex-end">
                <a href="admin.php?page=consultations&action=edit&id=<?=$c['id']?>" class="btn btn-outline btn-sm">Edit</a>
                <a href="admin.php?page=consultations&action=delete&id=<?=$c['id']?>" class="btn btn-danger btn-sm"
                   onclick="return confirm('Delete this booking?')">Del</a>
              </div>
            </td>
          </tr>
        <?php endforeach; endif ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif ?>

<?php // ── USERS ──────────────────────────────────────────────────
elseif ($page === 'users'):
  if (in_array($action, ['add','edit'])): ?>
<div class="page-header">
  <div><h1><?= $action==='add'?'Add User':'Edit User'?></h1></div>
  <a href="admin.php?page=users" class="btn btn-outline">← Back</a>
</div>
<div class="form-card">
  <h2><?= $action==='add'?'New User':'Edit: '.e(($editRow['first_name']??'').' '.($editRow['last_name']??''))?></h2>
  <form method="POST" action="admin.php?page=users&action=<?= $action==='add'?'store':('update&id='.$id) ?>">
    <div class="grid-2">
      <div class="form-group"><label>First Name *</label><input type="text" name="first_name" value="<?=e($editRow['first_name']??'')?>" required></div>
      <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" value="<?=e($editRow['last_name']??'')?>" required></div>
      <div class="col-span-2 form-group"><label>Email *</label><input type="email" name="email" value="<?=e($editRow['email']??'')?>" required></div>
      <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?=e($editRow['phone']??'')?>"></div>
      <div class="form-group"><label>Joined Date</label><input type="date" name="joined_date" value="<?=e($editRow['joined_date']??date('Y-m-d'))?>"></div>
      <div class="form-group"><label>Role</label>
        <select name="role">
          <option value="customer" <?=($editRow['role']??'')==='customer'?'selected':''?>>Customer</option>
          <option value="trainer"  <?=($editRow['role']??'')==='trainer' ?'selected':''?>>Trainer</option>
          <option value="admin"    <?=($editRow['role']??'')==='admin'   ?'selected':''?>>Admin</option>
        </select>
      </div>
      <div class="form-group"><label>Status</label>
        <select name="status">
          <option value="active"   <?=($editRow['status']??'')==='active'  ?'selected':''?>>Active</option>
          <option value="inactive" <?=($editRow['status']??'')==='inactive'?'selected':''?>>Inactive</option>
        </select>
      </div>
    </div>
    <div class="form-actions">
      <a href="admin.php?page=users" class="btn btn-outline">Cancel</a>
      <button type="submit" class="btn btn-primary">💾 Save User</button>
    </div>
  </form>
</div>

<?php else: ?>
<div class="page-header">
  <div><h1>Users</h1><p><?= count($data['rows']) ?> registered users</p></div>
  <a href="admin.php?page=users&action=add" class="btn btn-primary">+ Add User</a>
</div>
<div class="table-wrap">
  <div class="table-toolbar">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;width:100%">
      <input type="hidden" name="page" value="users">
      <div class="search-bar"><span>🔍</span><input name="q" value="<?=e($search)?>" placeholder="Search by name or email..."></div>
      <select name="status" class="filter-sel" onchange="this.form.submit()">
        <option value="">All Status</option>
        <option value="active"   <?=$filterStatus==='active'  ?'selected':''?>>Active</option>
        <option value="inactive" <?=$filterStatus==='inactive'?'selected':''?>>Inactive</option>
      </select>
      <button type="submit" class="btn btn-outline btn-sm">🔍 Search</button>
      <?php if($search||$filterStatus): ?><a href="admin.php?page=users" class="btn btn-outline btn-sm">✕ Clear</a><?php endif ?>
    </form>
  </div>
  <div style="overflow-x:auto">
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Joined</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody>
        <?php if (empty($data['rows'])): ?>
          <tr><td colspan="7"><div class="no-rows"><div class="ico">👥</div>No users found</div></td></tr>
        <?php else: foreach ($data['rows'] as $u): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div class="u-avatar"><?=strtoupper(substr($u['first_name'],0,1).substr($u['last_name'],0,1))?></div>
                <div class="td-name"><?=e($u['first_name'].' '.$u['last_name'])?></div>
              </div>
            </td>
            <td style="font-size:.78rem;color:var(--gray)"><?=e($u['email'])?></td>
            <td style="font-size:.78rem;color:var(--gray)"><?=e($u['phone']??'—')?></td>
            <td><?=badge($u['role'])?></td>
            <td style="font-size:.76rem;color:var(--gray)"><?=e($u['joined_date'])?></td>
            <td><?=badge($u['status'])?></td>
            <td style="text-align:right">
              <div style="display:flex;gap:6px;justify-content:flex-end">
                <a href="admin.php?page=users&action=edit&id=<?=$u['id']?>" class="btn btn-outline btn-sm">Edit</a>
                <a href="admin.php?page=users&action=delete&id=<?=$u['id']?>" class="btn btn-danger btn-sm"
                   onclick="return confirm('Delete user <?=addslashes($u['first_name'])?>?')">Del</a>
              </div>
            </td>
          </tr>
        <?php endforeach; endif ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif ?>
<?php endif ?>

  </div><!-- /content -->
</div><!-- /main -->
</body>
</html>