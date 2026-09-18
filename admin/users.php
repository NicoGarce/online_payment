<?php
$page_title = "Users - Payments Hub";
require_once __DIR__ . '/../includes/config.php';
paymentsRequireAdmin();
require_once __DIR__ . '/../includes/header.php';

$pdo = olp_getUsersPDO();
if (!$pdo) { echo '<div class="admin-content"><div class="alert err">Users DB not available.</div></div>'; require_once __DIR__.'/../includes/footer.php'; exit; }

$error = '';
$success = '';
$csrf = olp_csrf_token();

function validate_employee_number($emp) {
    // 3-50 chars, alphanumeric + dash/underscore, must contain letter or number
    if ($emp === '' || $emp === null) return true; // allow empty (will be NULL)
    if (strlen($emp) < 3 || strlen($emp) > 50) return false;
    return (bool)preg_match('/^[A-Za-z0-9\-_]+$/', $emp);
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // CSRF check for all POST actions
    $token = $_POST['csrf_token'] ?? '';
    if (!olp_verify_csrf($token)) {
        $error = "Security token invalid. Please refresh.";
    } else if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $empNo = trim($_POST['employee_number'] ?? '');
        $first = trim($_POST['first_name'] ?? '');
        $last = trim($_POST['last_name'] ?? '');
        $role = $_POST['role'] ?? 'hr';
        $pass = $_POST['password'] ?? '';
        if (!in_array($role, ['super_admin','admin','author','hr'])) $role = 'hr';
        if ($username===''||$email===''||$first===''||$last===''||$pass==='') {
            $error = "All fields except Employee No. are required. Employee No. is required for new users.";
        } elseif ($empNo === '') {
            $error = "Employee Number is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email.";
        } elseif (!validate_employee_number($empNo)) {
            $error = "Invalid Employee Number. Use 3-50 chars: letters, numbers, dash, underscore (e.g. EMP-00123).";
        } else {
            // check duplicate username/email/employee_number
            $chk = $pdo->prepare("SELECT id FROM users WHERE username=? OR email=? OR employee_number=? LIMIT 1");
            $chk->execute([$username,$email,$empNo]);
            if ($chk->fetch()) {
                $error = "Username, email or Employee Number already exists.";
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username,email,employee_number,password,first_name,last_name,role) VALUES (?,?,?,?,?,?,?)");
                if ($stmt->execute([$username,$email,$empNo,$hash,$first,$last,$role])) {
                    $success = "User '{$username}' (EMP: {$empNo}) created.";
                } else $error = "Failed to create user.";
            }
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $empNo = trim($_POST['employee_number'] ?? '');
        $first = trim($_POST['first_name'] ?? '');
        $last = trim($_POST['last_name'] ?? '');
        $role = $_POST['role'] ?? 'hr';
        $pass = $_POST['password'] ?? '';
        if (!in_array($role, ['super_admin','admin','author','hr'])) $role = 'hr';
        if ($id<=0||$username===''||$email===''||$first===''||$last==='') {
            $error = "All fields except password (and Employee No. if empty) are required.";
        } elseif ($empNo !== '' && !validate_employee_number($empNo)) {
            $error = "Invalid Employee Number format.";
        } else {
            // duplicate check: allow same id
            if ($empNo === '') {
                $chk = $pdo->prepare("SELECT id FROM users WHERE (username=? OR email=?) AND id!=? LIMIT 1");
                $chk->execute([$username,$email,$id]);
            } else {
                $chk = $pdo->prepare("SELECT id FROM users WHERE (username=? OR email=? OR employee_number=?) AND id!=? LIMIT 1");
                $chk->execute([$username,$email,$empNo,$id]);
            }
            if ($chk->fetch()) {
                $error = "Username, email or Employee Number already taken.";
            } else {
                $empParam = $empNo === '' ? null : $empNo;
                if ($pass !== '') {
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET username=?,email=?,employee_number=?,first_name=?,last_name=?,role=?,password=? WHERE id=?");
                    $ok = $stmt->execute([$username,$email,$empParam,$first,$last,$role,$hash,$id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username=?,email=?,employee_number=?,first_name=?,last_name=?,role=? WHERE id=?");
                    $ok = $stmt->execute([$username,$email,$empParam,$first,$last,$role,$id]);
                }
                if ($ok) $success = "User updated.";
                else $error = "Update failed.";
                // if editing self, update session role/employee
                if ($id == ($_SESSION['olp_user_id'] ?? 0)) {
                    $_SESSION['olp_user_role'] = $role;
                    $_SESSION['user_role'] = $role;
                    $_SESSION['olp_employee_number'] = $empParam;
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id == ($_SESSION['olp_user_id'] ?? 0)) {
            $error = "You cannot delete your own account.";
        } else {
            // prevent deleting last super_admin
            $cnt = $pdo->query("SELECT COUNT(*) as c FROM users WHERE role='super_admin'")->fetch()['c'] ?? 0;
            $target = $pdo->prepare("SELECT role FROM users WHERE id=?");
            $target->execute([$id]);
            $trole = $target->fetch()['role'] ?? '';
            if ($trole==='super_admin' && $cnt<=1) {
                $error = "Cannot delete the last super_admin.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
                if ($stmt->execute([$id])) $success = "User deleted.";
                else $error = "Delete failed.";
            }
        }
    }
}

// Fetch users
$users = $pdo->query("SELECT id,username,email,employee_number,first_name,last_name,role,created_at FROM users ORDER BY created_at DESC")->fetchAll();
?>
<nav class="admin-tabs" aria-label="Admin sections">
  <a href="<?= $payments_base ?>admin/">Dashboard</a>
  <a href="<?= $payments_base ?>admin/users" class="active">Users</a>
  <a href="<?= $payments_base ?>admin/students">Student Management</a>
  <a href="<?= $payments_base ?>admin/monitoring">Payment Monitoring</a>
  <a href="<?= $payments_base ?>admin/qr">QR Codes</a>
  <a href="<?= $payments_base ?>admin/backup">Backup Portal</a>
  <a href="<?= $payments_base ?>admin/logout" class="admin-tab-logout">Logout</a>
</nav>
<div class="admin-content">
  <div style="display:flex; align-items:center; gap:12px; margin-bottom:10px">
    <h2 style="margin:0; font-size:22px">Users</h2>
    <span style="margin-left:auto; background:var(--bg); border:1px solid var(--line); padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; color:var(--muted)"><?= count($users) ?> total • uphsledu_onlinepayment.users (employee_number secured)</span>
    <button onclick="openAddUser()" class="btn" style="background:var(--blue); color:#fff; padding:9px 14px; font-size:13px">Add User</button>
  </div>
  <p style="color:var(--muted); margin:0 0 12px; font-size:13px">OLP users now include <code>employee_number</code> — login via Employee No. / Username / Email. Secured with CSRF + rate limiting (15 min lockout after 5 failures) and <code>password_hash</code>. Independent from main site <code>uphsledu_main</code>.</p>

  <?php if($error): ?><div class="alert err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if($success): ?><div class="alert ok"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <div class="table-container">
    <table class="data-table">
      <thead><tr><th>ID</th><th>Employee No.</th><th>Username</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if(empty($users)): ?><tr><td colspan="8" class="text-center">No users found.</td></tr>
        <?php else: foreach($users as $u): ?>
        <tr>
          <td><?= (int)$u['id'] ?></td>
          <td><strong><?= htmlspecialchars($u['employee_number'] ?? '—') ?></strong></td>
          <td><?= htmlspecialchars($u['username']) ?></td>
          <td><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><span class="badge <?= $u['role']==='super_admin'?'err':($u['role']==='admin'?'warn':'ok') ?>"><?= htmlspecialchars($u['role']) ?></span></td>
          <td style="font-size:12px; color:var(--muted)"><?= htmlspecialchars($u['created_at']) ?></td>
          <td style="display:flex; gap:6px">
            <button onclick="openEditUser(<?= (int)$u['id'] ?>,'<?= htmlspecialchars($u['username'],ENT_QUOTES) ?>','<?= htmlspecialchars($u['email'],ENT_QUOTES) ?>','<?= htmlspecialchars($u['employee_number'] ?? '',ENT_QUOTES) ?>','<?= htmlspecialchars($u['first_name'],ENT_QUOTES) ?>','<?= htmlspecialchars($u['last_name'],ENT_QUOTES) ?>','<?= $u['role'] ?>')" class="btn btn-secondary" style="padding:6px 10px; font-size:12px">Edit</button>
            <?php if((int)$u['id'] !== (int)($_SESSION['olp_user_id'] ?? 0)): ?>
            <form method="POST" onsubmit="return confirm('Delete user <?= htmlspecialchars($u['username']) ?>?')" style="display:inline">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <button type="submit" class="btn btn-danger" style="padding:6px 10px; font-size:12px">Delete</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
  <div class="modal-content">
    <div class="modal-header"><h3>Add User</h3><span class="close" onclick="closeAddUser()">&times;</span></div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-group"><label>Employee Number <span style="color:#d00">*</span></label><input type="text" name="employee_number" class="form-input" required placeholder="EMP-00123" pattern="[A-Za-z0-9\-_]{3,50}" title="3-50 chars: letters, numbers, dash, underscore"><small style="color:var(--muted)">Unique, used for secure login. e.g. EMP-00123</small></div>
        <div class="form-group"><label>Username <span style="color:#d00">*</span></label><input type="text" name="username" class="form-input" required></div>
        <div class="form-group"><label>Email <span style="color:#d00">*</span></label><input type="email" name="email" class="form-input" required></div>
        <div class="form-group"><label>First Name <span style="color:#d00">*</span></label><input type="text" name="first_name" class="form-input" required></div>
        <div class="form-group"><label>Last Name <span style="color:#d00">*</span></label><input type="text" name="last_name" class="form-input" required></div>
        <div class="form-group"><label>Password <span style="color:#d00">*</span></label><input type="password" name="password" class="form-input" required></div>
        <div class="form-group"><label>Role</label>
          <select name="role" class="form-input">
            <option value="hr">hr</option>
            <option value="author">author</option>
            <option value="admin">admin</option>
            <option value="super_admin">super_admin</option>
          </select>
        </div>
        <div class="form-actions"><button type="button" class="btn btn-secondary" onclick="closeAddUser()">Cancel</button><button type="submit" class="btn btn-primary">Create</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
  <div class="modal-content">
    <div class="modal-header"><h3>Edit User</h3><span class="close" onclick="closeEditUser()">&times;</span></div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit_id">
        <div class="form-group"><label>Employee Number</label><input type="text" name="employee_number" id="edit_employee_number" class="form-input" placeholder="EMP-00123" pattern="[A-Za-z0-9\-_]{3,50}"><small style="color:var(--muted)">Leave blank to keep empty, or set unique value</small></div>
        <div class="form-group"><label>Username</label><input type="text" name="username" id="edit_username" class="form-input" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" id="edit_email" class="form-input" required></div>
        <div class="form-group"><label>First Name</label><input type="text" name="first_name" id="edit_first" class="form-input" required></div>
        <div class="form-group"><label>Last Name</label><input type="text" name="last_name" id="edit_last" class="form-input" required></div>
        <div class="form-group"><label>New Password (leave blank to keep)</label><input type="password" name="password" class="form-input"></div>
        <div class="form-group"><label>Role</label>
          <select name="role" id="edit_role" class="form-input">
            <option value="hr">hr</option>
            <option value="author">author</option>
            <option value="admin">admin</option>
            <option value="super_admin">super_admin</option>
          </select>
        </div>
        <div class="form-actions"><button type="button" class="btn btn-secondary" onclick="closeEditUser()">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
      </form>
    </div>
  </div>
</div>

<script>
function openAddUser(){ document.getElementById('addUserModal').style.display='block'; document.body.classList.add('modal-open'); }
function closeAddUser(){ document.getElementById('addUserModal').style.display='none'; document.body.classList.remove('modal-open'); }
function openEditUser(id,u,e,emp,f,l,r){ document.getElementById('edit_id').value=id; document.getElementById('edit_username').value=u; document.getElementById('edit_email').value=e; document.getElementById('edit_employee_number').value=emp; document.getElementById('edit_first').value=f; document.getElementById('edit_last').value=l; document.getElementById('edit_role').value=r; document.getElementById('editUserModal').style.display='block'; document.body.classList.add('modal-open'); }
function closeEditUser(){ document.getElementById('editUserModal').style.display='none'; document.body.classList.remove('modal-open'); }
window.onclick=function(e){ if(e.target.classList.contains('modal')){ e.target.style.display='none'; document.body.classList.remove('modal-open'); } }
document.addEventListener('keydown',e=>{ if(e.key==='Escape'){ document.querySelectorAll('.modal').forEach(m=>m.style.display='none'); document.body.classList.remove('modal-open'); }});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
