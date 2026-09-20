<?php
/**
 * OLP — DragonPay Return (retback) — Standalone
 * Backend parity with uphsledu/online_payment/retback.php & retback_ts.php
 * but fully redesigned UI using OLP design system (header/footer + style.css).
 *
 * DragonPay redirects here via GET:
 *   txnid, refno, status (S/F/P/U/R/K/V/A), message, digest, param1 (amount), param2 (desc)
 * Example: /retback.php?txnid=20-1234-567&refno=PAMKFB89&status=P&message=...&digest=...&param1=5000.00&param2=DESC
 */
$page_title = "Payment Result";
require_once __DIR__ . '/includes/config.php';

// Legacy/simple setup: keep the merchant password locally to match the original build.
if (!defined('MERCHANT_PASSWORD')) define('MERCHANT_PASSWORD', 'uSw92BkgTsVRqZT');

// ------------------------------------------------------------------
// 1) Normalize inputs (GET is DragonPay's redirect; be tolerant)
//    DragonPay OfflineGateway returns: txnid, refno, status, amount, message,
//    merchantid, param1, param2, signature, signatures, settledate, expiry, procid
//    Older docs used: digest (sha1). New uses signature (hex) + signatures (base64)
// ------------------------------------------------------------------
$txnid  = trim($_GET['txnid']  ?? '');
$refno  = trim($_GET['refno']  ?? '');
$rawStatus = strtoupper(trim($_GET['status'] ?? ''));
$dragonMsg = $_GET['message'] ?? '';          // DragonPay's own message (optional)
$digest    = $_GET['digest']  ?? '';
$signature = $_GET['signature'] ?? '';        // new: hex SHA? (OfflineGateway)
$signatures= $_GET['signatures'] ?? '';       // new: base64 RSA?
$param1    = $_GET['param1']  ?? '';          // amount (forwarded)
$param2    = $_GET['param2']  ?? '';          // description (forwarded)
$amountParam = $_GET['amount'] ?? '';         // new: DragonPay amount (may duplicate param1)
$merchantidParam = $_GET['merchantid'] ?? '';
$settledate = $_GET['settledate'] ?? '';
$expiryParam = $_GET['expiry'] ?? '';
$procidParam = $_GET['procid'] ?? '';
$billerParam = $_GET['billerId'] ?? '';
// Backward compat: some flows POST? also accept POST
if ($txnid === '' && isset($_POST['txnid']))  $txnid = trim($_POST['txnid']);
if ($refno === '' && isset($_POST['refno']))  $refno = trim($_POST['refno']);
if ($rawStatus === '' && isset($_POST['status'])) $rawStatus = strtoupper(trim($_POST['status']));
if ($dragonMsg === '' && isset($_POST['message'])) $dragonMsg = $_POST['message'];
if ($digest === '' && isset($_POST['digest'])) $digest = $_POST['digest'];
if ($signature === '' && isset($_POST['signature'])) $signature = $_POST['signature'];
if ($signatures === '' && isset($_POST['signatures'])) $signatures = $_POST['signatures'];
if ($param1 === '' && isset($_POST['param1'])) $param1 = $_POST['param1'];
if ($param2 === '' && isset($_POST['param2'])) $param2 = $_POST['param2'];
if ($amountParam === '' && isset($_POST['amount'])) $amountParam = $_POST['amount'];
if ($settledate === '' && isset($_POST['settledate'])) $settledate = $_POST['settledate'];

// ------------------------------------------------------------------
// 2) Map status code → human label + UI meta
// ------------------------------------------------------------------
$statusMap = [
    'S' => ['label' => 'Success',    'color' => 'success', 'icon' => 'fa-circle-check',       'desc' => 'Payment confirmed. Your transaction was successful.'],
    'F' => ['label' => 'Failure',    'color' => 'failed',  'icon' => 'fa-circle-xmark',       'desc' => 'Payment failed or was cancelled. No amount was charged.'],
    'P' => ['label' => 'Pending',    'color' => 'pending', 'icon' => 'fa-clock',              'desc' => 'Payment is pending. Please complete it via your chosen channel and check your email.'],
    'U' => ['label' => 'Unknown',    'color' => 'pending', 'icon' => 'fa-circle-question',    'desc' => 'Status unknown. Please check your email or contact support.'],
    'R' => ['label' => 'Refund',     'color' => 'refund',  'icon' => 'fa-rotate-left',        'desc' => 'Payment has been refunded.'],
    'K' => ['label' => 'Chargeback', 'color' => 'failed',  'icon' => 'fa-triangle-exclamation','desc' => 'Chargeback issued. Please contact support.'],
    'V' => ['label' => 'Void',       'color' => 'failed',  'icon' => 'fa-ban',                'desc' => 'Transaction was voided.'],
    'A' => ['label' => 'Authorized', 'color' => 'pending', 'icon' => 'fa-shield-halved',      'desc' => 'Payment authorized — awaiting capture.'],
];
$statusInfo = $statusMap[$rawStatus] ?? ['label' => ($rawStatus ?: 'Unknown'), 'color' => 'pending', 'icon' => 'fa-circle-info', 'desc' => 'We could not determine the final status. Please keep your reference number.'];
$statusLabel = $statusInfo['label'];
$statusColor = $statusInfo['color'];
$statusIcon  = $statusInfo['icon'];

// sanitize amount — DragonPay now sends both param1 (forwarded) and amount (gateway amount); prefer param1, fall back to amount
$amountSource = $param1 !== '' ? $param1 : $amountParam;
$amountRaw = is_numeric($amountSource) ? (float)$amountSource : 0.0;
$amountFormatted = $amountSource !== '' && is_numeric($amountSource) ? number_format((float)$amountSource, 2, '.', ',') : ($amountSource !== '' ? $amountSource : '—');
$description = $param2 !== '' ? $param2 : ($dragonMsg !== '' ? $dragonMsg : '—');
// Strip internal OLP marker for display (if checkout appended " | OLP")
$descriptionDisplay = preg_replace('/\s*\|\s*OLP\s*$/', '', $description);
$descriptionDisplay = preg_replace('/\s*\(OLP\)\s*$/', '', $descriptionDisplay);

// ------------------------------------------------------------------
// 3) Optional digest/signature verification (non-blocking, log only)
//    Legacy: digest = sha1(txnid:refno:status:message:password)
//    New OfflineGateway: signature (hex) + signatures (RSA base64) — not verified here (needs RSA pubkey),
//    just displayed. We still attempt sha1 check if digest or signature looks like sha1.
// ------------------------------------------------------------------
$digestValid = null; // null = skipped, true/false = checked
$digestNote = '';
$sigToCheck = $digest !== '' ? $digest : $signature;
if ($sigToCheck !== '' && defined('MERCHANT_PASSWORD') && $txnid !== '' && $refno !== '' && $rawStatus !== '') {
    // Try sha1 candidates
    $candidates = [];
    $candidates[] = implode(':', [$txnid, $refno, $rawStatus, $dragonMsg, MERCHANT_PASSWORD]);
    $candidates[] = implode(':', [$txnid, $refno, $rawStatus, '', MERCHANT_PASSWORD]);
    if ($param2 !== '') $candidates[] = implode(':', [$txnid, $refno, $rawStatus, $param2, MERCHANT_PASSWORD]);
    // Some newer docs use amount in digest
    if ($amountSource !== '') $candidates[] = implode(':', [$txnid, $refno, $rawStatus, $amountSource, $dragonMsg, MERCHANT_PASSWORD]);
    $matched = false;
    foreach ($candidates as $c) {
        if (hash_equals(strtolower(sha1($c)), strtolower($sigToCheck))) { $matched = true; break; }
        // also try hash_hmac sha256
        if (hash_equals(strtolower(hash_hmac('sha256', $c, MERCHANT_PASSWORD)), strtolower($sigToCheck))) { $matched = true; break; }
    }
    $digestValid = $matched;
    $digestNote = $matched ? 'Digest verified.' : 'Showing return as-is — final confirmation via email/postback.';
} elseif ($signatures !== '' || $signature !== '') {
    $digestNote = '';
    $digestValid = null;
} elseif ($digest === '' && $signature === '') {
    $digestNote = '';
}

// ------------------------------------------------------------------
// 4) Backend — update return_data (parity with uphsledu retback.php)
//    Columns: transdate, refno, status (human label), amount, message (description)
//    WHERE txnid = ?
// ------------------------------------------------------------------
$dbOk = false;
$dbError = '';
if (isset($con) && $con instanceof mysqli && $txnid !== '') {
    // Ensure charset
    @mysqli_set_charset($con, 'utf8mb4');
    $stmt = @mysqli_prepare($con, "UPDATE return_data SET transdate=NOW(), refno=?, status=?, amount=?, message=? WHERE txnid=?");
    if ($stmt) {
        $msgToStore = $param2 !== '' ? $param2 : $dragonMsg;
        // bind: refno(s), status(s), amount(d), message(s), txnid(s)
        mysqli_stmt_bind_param($stmt, "ssdss", $refno, $statusLabel, $amountRaw, $msgToStore, $txnid);
        $exec = @mysqli_stmt_execute($stmt);
        $affected = @mysqli_stmt_affected_rows($stmt);
        @mysqli_stmt_close($stmt);
        if ($exec) {
            $dbOk = true;
            // If no row matched (new txnid never inserted via checkout — e.g., direct test), insert gracefully
            if ($affected === 0) {
                $ins = @mysqli_prepare($con, "INSERT INTO return_data (txnid, refno, status, amount, message, transdate) VALUES (?,?,?,?,?,NOW())");
                if ($ins) {
                    @mysqli_stmt_bind_param($ins, "ssdss", $txnid, $refno, $statusLabel, $amountRaw, $msgToStore);
                    @mysqli_stmt_execute($ins);
                    @mysqli_stmt_close($ins);
                }
            }
        } else {
            $dbError = 'Update failed: ' . mysqli_error($con);
        }
    } else {
        $dbError = 'Prepare failed: ' . mysqli_error($con);
        // Fallback: legacy escaped query (should never be needed)
        $q = sprintf("UPDATE return_data SET transdate=NOW(), refno='%s', status='%s', amount='%s', message='%s' WHERE txnid='%s'",
            mysqli_real_escape_string($con, $refno),
            mysqli_real_escape_string($con, $statusLabel),
            mysqli_real_escape_string($con, (string)$amountRaw),
            mysqli_real_escape_string($con, $param2 !== '' ? $param2 : $dragonMsg),
            mysqli_real_escape_string($con, $txnid)
        );
        if (@mysqli_query($con, $q)) { $dbOk = true; $dbError = ''; }
    }
} elseif ($txnid === '') {
    $dbError = 'Missing txnid — nothing to update.';
} else {
    $dbError = 'DB not available — result shown from DragonPay redirect only.';
}

// For display, also fetch transdate if we can
$transdateDisplay = date('M d, Y h:i A');
if (isset($con) && $con instanceof mysqli && $txnid !== '') {
    $rs = @mysqli_query($con, "SELECT transdate FROM return_data WHERE txnid='".mysqli_real_escape_string($con,$txnid)."' ORDER BY transdate DESC LIMIT 1");
    if ($rs && ($row = mysqli_fetch_assoc($rs)) && !empty($row['transdate'])) {
        $transdateDisplay = date('M d, Y h:i A', strtotime($row['transdate']));
    }
}

require_once __DIR__ . '/includes/header.php';
$payments_base = $GLOBALS['payments_base'] ?? '/';
?>
<style>
/* retback — aligned with checkout/pay-summary design system */
.retback-wrap{max-width:900px;margin:0 auto}
.retback-hero{border-radius:20px;overflow:hidden;border:1px solid var(--line);box-shadow:0 12px 30px rgba(15,32,64,.08);background:#fff}
.retback-hero-head{padding:24px;display:flex;align-items:center;gap:16px;color:#fff;position:relative;overflow:hidden}
.retback-hero-head::after{content:"";position:absolute;inset:auto -60px -60px auto;width:280px;height:280px;background:radial-gradient(circle at center, rgba(255,255,255,.18), transparent 62%);pointer-events:none}
.retback-hero-head.success{background:linear-gradient(135deg,#0b7a55 0%, #0e9f6e 55%, #10b981 100%)}
.retback-hero-head.pending{background:linear-gradient(135deg,#b45309 0%, #d97706 55%, #f59e0b 100%)}
.retback-hero-head.failed{background:linear-gradient(135deg,#991b1b 0%, #dc2626 55%, #ef4444 100%)}
.retback-hero-head.refund{background:linear-gradient(135deg,#12307a 0%, #1c4da1 55%, #3b82f6 100%)}
.retback-hero-head.unknown{background:linear-gradient(135deg,#334155 0%, #475569 55%, #64748b 100%)}
.retback-hero-icon{width:56px;height:56px;border-radius:16px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.28);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;box-shadow:0 8px 20px rgba(0,0,0,.12)}
.retback-hero-title{font-family:"Plus Jakarta Sans",sans-serif;font-weight:700;font-size:14px;line-height:1.1;margin:0;letter-spacing:.04em;text-transform:uppercase;opacity:.92}
.retback-hero-sub{opacity:.92;font-size:13px;margin-top:6px;line-height:1.6;max-width:62ch}
.retback-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 11px;border-radius:999px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.28);font-weight:800;font-size:11px;letter-spacing:.06em;text-transform:uppercase;backdrop-filter:blur(6px)}
.retback-body{padding:22px}
@media(max-width:640px){.retback-body{padding:16px}.retback-hero-head{padding:18px}}
.retback-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
@media(max-width:700px){.retback-grid{grid-template-columns:1fr}}
.retback-field{background:#fff;border:1px solid var(--line);border-radius:16px;padding:16px;position:relative;transition:border-color .2s, box-shadow .2s}
.retback-field:hover{border-color:#d6e0ff;box-shadow:0 6px 18px rgba(15,32,64,.06)}
.retback-field label{display:flex;align-items:center;gap:6px;font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#64748b;margin-bottom:8px}
.retback-field strong{font-size:15px;color:#0f2040;word-break:break-word;line-height:1.35}
.retback-field .mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;letter-spacing:.01em}
.copy-btn{margin-left:auto;display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;border:1px solid var(--line);background:#fff;color:var(--blue);font-weight:800;font-size:11px;cursor:pointer;transition:.15s}
.copy-btn:hover{background:#eef2ff;border-color:#c7d7f5}
.copy-btn.copied{background:#ecfdf5;border-color:#a7f3d0;color:#065f46}
.desc-box{background:#fbfdff;border:1px solid #e2e8f0;border-radius:16px;padding:16px}
.desc-box label{display:flex;align-items:center;gap:6px;font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#64748b}
.desc-box p{margin:8px 0 0;font-weight:700;color:#1e293b;word-break:break-word;line-height:1.6}
.meta-row{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.meta-row span{display:inline-flex;align-items:center;gap:6px;padding:7px 12px;border-radius:999px;background:#f1f5f9;border:1px solid #e2e8f0;font-size:12px;font-weight:700;color:#334155}
.meta-row span i{color:var(--blue)}
.retback-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}
.retback-actions .btn{flex:1 1 160px;justify-content:center;min-height:44px}
.retback-note{margin-top:14px;padding:14px;border-radius:14px;border:1px solid #e2e8f0;background:#f8fafc;font-size:13px;color:#475569;display:flex;gap:12px;line-height:1.6}
.retback-note i{margin-top:2px;flex-shrink:0}
.amount-card{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:16px;padding:14px 16px;background:linear-gradient(180deg,#f8fafc 0%, #f1f5f9 100%);border:1px solid var(--line);border-radius:16px}
.amount-big{font-size:24px;font-weight:900;color:#0f2040;letter-spacing:-.02em;line-height:1}
.amount-big small{font-size:12px;font-weight:800;color:#64748b;letter-spacing:.04em}
.retback-stepper{display:flex;align-items:center;gap:8px;margin-top:10px;flex-wrap:wrap}
.retback-stepper span{inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;padding:6px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.28);background:rgba(255,255,255,.14);opacity:.9}
.retback-stepper span.active{background:#fff;color:var(--text);border-color:#fff;opacity:1;box-shadow:0 4px 12px rgba(0,0,0,.12)}
.cashier-banner{display:flex;align-items:center;gap:14px;padding:16px 18px;border-radius:16px;border:2px solid #f59e0b;background:linear-gradient(135deg,#fffbeb 0%, #fef3c7 100%);box-shadow:0 10px 24px rgba(245,158,11,.18);margin-bottom:14px;position:relative;overflow:hidden}
.cashier-banner::before{content:"";position:absolute;left:0;top:0;bottom:0;width:6px;background:linear-gradient(180deg,#f59e0b,#d97706)}
.cashier-banner-icon{width:44px;height:44px;border-radius:12px;background:#f59e0b;color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;box-shadow:0 6px 14px rgba(245,158,11,.28)}
.cashier-banner-text{font-size:15px;font-weight:800;line-height:1.45;color:#78350f}
.cashier-banner-text strong{color:#92400e}
@media(max-width:480px){.cashier-banner{padding:14px 14px 14px 16px;gap:12px}.cashier-banner-text{font-size:14px}.cashier-banner-icon{width:38px;height:38px;font-size:16px}}
@media print{
  .cashier-banner{box-shadow:none;border:2px solid #f59e0b;-webkit-print-color-adjust:exact;print-color-adjust:exact}
}
  .no-print{display:none !important}
  .retback-hero{box-shadow:none;border:1px solid #ddd}
  .retback-hero-head{ -webkit-print-color-adjust:exact; print-color-adjust:exact}
  .copy-btn{display:none !important}
}
</style>

<div class="retback-wrap">
  <!-- breadcrumb -->
  <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;flex-wrap:wrap" class="no-print">
    <a href="<?= $payments_base ?>" class="btn" style="background:#fff;border:1px solid var(--line);padding:8px 14px;font-size:12px">← Back to Home</a>
    <a href="<?= $payments_base ?>instructions" class="btn" style="background:#fff;border:1px solid var(--line);padding:8px 14px;font-size:12px"><i class="fa-solid fa-circle-question"></i> How to Pay</a>
  </div>

  <!-- primary post-payment instruction — most prominent, seen first -->
  <div class="cashier-banner">
    <div class="cashier-banner-text">Next step — Cashier: Once Payment is Successful, Please take a screenshot of this page and proceed to the Cashier to present it and claim your official receipt.</div>
  </div>

  <div class="retback-hero">
    <div class="retback-hero-head <?= htmlspecialchars($statusColor) ?>">
      <div style="flex:1;min-width:0">
        <h1 class="retback-hero-title">Payment Details and Status</h1>
        <div style="font-size:22px;font-weight:900;letter-spacing:-.01em;line-height:1;margin-top:6px;"><?= htmlspecialchars($statusLabel) ?></div>
        <div class="retback-hero-sub">Thank you for your payment request. Please visit the email address you have provided on your posting so that you can see or view instruction details pertaining to your transaction.</div>
      </div>
      <img src="<?= $payments_base ?>assets/dragonpay-xendit-logo-removebg-preview.png" alt="DragonPay" class="dp-logo" style="height:22px;flex-shrink:0;background:#fff;padding:3px 6px;border-radius:8px;border:1px solid rgba(255,255,255,.6)">
    </div>

    <div class="retback-body">
      <div style="background:#f8fafc;border:1px solid var(--line);border-radius:16px;overflow:hidden">
        <div style="display:grid;gap:1px;background:var(--line)">
          <div style="display:flex;justify-content:space-between;gap:12px;background:#fff;padding:13px 16px;align-items:center;flex-wrap:wrap">
            <span style="color:#475569;font-weight:700;font-size:13px">Transaction No :.</span>
            <strong class="mono" style="font-size:13px;color:#0f2040;word-break:break-all"><?= $txnid !== '' ? htmlspecialchars($txnid) : '—' ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;gap:12px;background:#fff;padding:13px 16px;align-items:center;flex-wrap:wrap">
            <span style="color:#475569;font-weight:700;font-size:13px">Reference No. :</span>
            <strong class="mono" style="font-size:13px;color:#0f2040;word-break:break-all"><?= $refno !== '' ? htmlspecialchars($refno) : '—' ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;gap:12px;background:#fff;padding:13px 16px;align-items:center">
            <span style="color:#475569;font-weight:700;font-size:13px">Status :</span>
            <strong style="font-size:20px;font-weight:900;color:#0f2040;letter-spacing:-.01em;line-height:1"><?= htmlspecialchars($statusLabel) ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;gap:12px;background:#fff;padding:13px 16px;align-items:center">
            <span style="color:#475569;font-weight:700;font-size:13px">Amount :</span>
            <strong style="font-size:13px;color:#0f2040;">₱ <?= htmlspecialchars($amountFormatted) ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;gap:12px;background:#fff;padding:13px 16px;align-items:flex-start">
            <span style="color:#475569;font-weight:700;font-size:13px;flex-shrink:0">Description :</span>
            <strong style="font-size:13px;color:#0f2040;text-align:right;max-width:62%;word-break:break-word;"><?= $descriptionDisplay !== '—' ? htmlspecialchars($descriptionDisplay) : '—' ?></strong>
          </div>
        </div>
      </div>

      <div class="retback-actions no-print">
        <button type="button" class="btn btn-primary" onclick="window.print()" style="background:var(--blue);color:#fff;border-color:var(--blue)"><i class="fa-solid fa-print"></i> Print Receipt</button>
        <a href="<?= $payments_base ?>" class="btn" style="background:#fff;border:1px solid var(--line)"><i class="fa-solid fa-house"></i> Back to Payments Hub</a>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
