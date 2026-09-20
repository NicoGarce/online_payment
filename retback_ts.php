<?php
// retback_ts — legacy test return, aligned with retback.php minimal UI
$page_title = "Payment Result";
require_once __DIR__ . '/includes/config.php';
// Legacy/simple setup: keep the merchant password locally to match the original working build.
if (!defined('MERCHANT_PASSWORD')) define('MERCHANT_PASSWORD', 'uSw92BkgTsVRqZT');

$s = "";
$status = strtoupper(trim($_GET["status"] ?? ""));
$map = ['S'=>'Success','F'=>'Failure','P'=>'Pending','U'=>'Unknown','R'=>'Refund','K'=>'Chargeback','V'=>'Void','A'=>'Authorized'];
$s = $map[$status] ?? ($status ?: 'Unknown');

$txnid = trim($_GET["txnid"] ?? "");
$refno = trim($_GET["refno"] ?? "");
$param1 = $_GET["param1"] ?? "0";
$param2 = $_GET["param2"] ?? "";
$dragonMsg = $_GET["message"] ?? "";
$amount = is_numeric($param1) ? floatval($param1) : 0;
$amountFmt = is_numeric($param1) ? number_format((float)$param1,2,'.',',') : htmlspecialchars($param1);

if (isset($con) && $con instanceof mysqli) {
    @mysqli_set_charset($con,'utf8mb4');
    $stmt = @mysqli_prepare($con, "UPDATE return_data SET transdate=now(), refno=?, status=?, amount=?, message=? WHERE txnid=?");
    if ($stmt) {
        @mysqli_stmt_bind_param($stmt, "ssdss", $refno, $s, $amount, $param2, $txnid);
        @mysqli_stmt_execute($stmt);
        @mysqli_stmt_close($stmt);
    }
}
$statusMap = [
    'S'=>['label'=>'Success','color'=>'success','icon'=>'fa-circle-check','desc'=>'Payment confirmed.'],
    'F'=>['label'=>'Failure','color'=>'failed','icon'=>'fa-circle-xmark','desc'=>'Payment failed.'],
    'P'=>['label'=>'Pending','color'=>'pending','icon'=>'fa-clock','desc'=>'Waiting for deposit — check email.'],
    'U'=>['label'=>'Unknown','color'=>'pending','icon'=>'fa-circle-question','desc'=>'Status unknown.'],
    'R'=>['label'=>'Refund','color'=>'refund','icon'=>'fa-rotate-left','desc'=>'Refunded.'],
    'K'=>['label'=>'Chargeback','color'=>'failed','icon'=>'fa-triangle-exclamation','desc'=>'Chargeback.'],
    'V'=>['label'=>'Void','color'=>'failed','icon'=>'fa-ban','desc'=>'Voided.'],
    'A'=>['label'=>'Authorized','color'=>'pending','icon'=>'fa-shield-halved','desc'=>'Authorized.'],
];
$info = $statusMap[$status] ?? ['label'=>$s,'color'=>'pending','icon'=>'fa-circle-info','desc'=>''];
$payments_base = $GLOBALS['payments_base'] ?? '/';
require_once __DIR__ . '/includes/header.php';
?>
<style>
.retback-wrap{max-width:900px;margin:0 auto}
.retback-hero{border-radius:20px;overflow:hidden;border:1px solid var(--line);box-shadow:0 12px 30px rgba(15,32,64,.08);background:#fff}
.retback-hero-head{padding:24px;display:flex;align-items:center;gap:16px;color:#fff;position:relative;overflow:hidden}
.retback-hero-head::after{content:"";position:absolute;inset:auto -60px -60px auto;width:280px;height:280px;background:radial-gradient(circle at center, rgba(255,255,255,.18), transparent 62%);pointer-events:none}
.retback-hero-head.success{background:linear-gradient(135deg,#0b7a55 0%, #0e9f6e 55%, #10b981 100%)}
.retback-hero-head.pending{background:linear-gradient(135deg,#b45309 0%, #d97706 55%, #f59e0b 100%)}
.retback-hero-head.failed{background:linear-gradient(135deg,#991b1b 0%, #dc2626 55%, #ef4444 100%)}
.retback-hero-head.refund{background:linear-gradient(135deg,#12307a 0%, #1c4da1 55%, #3b82f6 100%)}
.retback-hero-head.unknown{background:linear-gradient(135deg,#334155 0%, #475569 55%, #64748b 100%)}
.retback-hero-icon{width:56px;height:56px;border-radius:16px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.28);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
.retback-hero-title{font-family:"Plus Jakarta Sans",sans-serif;font-weight:700;font-size:14px;margin:0;letter-spacing:.04em;text-transform:uppercase;opacity:.92}
.retback-hero-sub{opacity:.92;font-size:13px;margin-top:6px;line-height:1.6}
.retback-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 11px;border-radius:999px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.28);font-weight:800;font-size:11px;letter-spacing:.06em;text-transform:uppercase}
.retback-body{padding:22px}
@media(max-width:640px){.retback-body{padding:16px}.retback-hero-head{padding:18px}}
.cashier-banner{display:flex;align-items:center;gap:14px;padding:16px 18px;border-radius:16px;border:2px solid #f59e0b;background:linear-gradient(135deg,#fffbeb 0%, #fef3c7 100%);box-shadow:0 10px 24px rgba(245,158,11,.18);margin-bottom:14px;position:relative;overflow:hidden}
.cashier-banner::before{content:"";position:absolute;left:0;top:0;bottom:0;width:6px;background:linear-gradient(180deg,#f59e0b,#d97706)}
.cashier-banner-icon{width:44px;height:44px;border-radius:12px;background:#f59e0b;color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;box-shadow:0 6px 14px rgba(245,158,11,.28)}
.cashier-banner-text{font-size:15px;font-weight:800;line-height:1.45;color:#78350f}
@media(max-width:480px){.cashier-banner{padding:14px 14px 14px 16px;gap:12px}.cashier-banner-text{font-size:14px}.cashier-banner-icon{width:38px;height:38px;font-size:16px}}
</style>
<div class="retback-wrap">
  <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;flex-wrap:wrap" class="no-print">
    <a href="<?= $payments_base ?>" class="btn" style="background:#fff;border:1px solid var(--line);padding:8px 14px;font-size:12px">← Back to Home</a>
  </div>
  <div class="cashier-banner">
    <div class="cashier-banner-text">Next step — Cashier: Once Payment is Successful, Please take a screenshot of this page and proceed to the Cashier to present it and claim your official receipt.</div>
  </div>
  <div class="retback-hero">
    <div class="retback-hero-head <?= htmlspecialchars($info['color']) ?>">
      <div style="flex:1;min-width:0">
        <h1 class="retback-hero-title">Payment Details and Status</h1>
        <div style="font-size:22px;font-weight:900;letter-spacing:-.01em;line-height:1;margin-top:6px;"><?= htmlspecialchars($info['label']) ?></div>
        <div class="retback-hero-sub">Thank you for your payment request. Please visit the email address you have provided on your posting so that you can see or view instruction details pertaining to your transaction.</div>
      </div>
      <img src="<?= $payments_base ?>assets/dragonpay-xendit-logo-removebg-preview.png" alt="DragonPay" class="dp-logo" style="height:22px;flex-shrink:0;background:#fff;padding:3px 6px;border-radius:8px;border:1px solid rgba(255,255,255,.6)">
    </div>
    <div class="retback-body">
      <div style="background:#f8fafc;border:1px solid var(--line);border-radius:16px;overflow:hidden">
        <div style="display:grid;gap:1px;background:var(--line)">
          <div style="display:flex;justify-content:space-between;gap:12px;background:#fff;padding:13px 16px;align-items:center;flex-wrap:wrap"><span style="color:#475569;font-weight:700;font-size:13px">Transaction No :.</span><strong class="mono" style="font-size:13px;color:#0f2040;word-break:break-all"><?= $txnid ? htmlspecialchars($txnid) : '—' ?></strong></div>
          <div style="display:flex;justify-content:space-between;gap:12px;background:#fff;padding:13px 16px;align-items:center;flex-wrap:wrap"><span style="color:#475569;font-weight:700;font-size:13px">Reference No. :</span><strong class="mono" style="font-size:13px;color:#0f2040;word-break:break-all"><?= $refno ? htmlspecialchars($refno) : '—' ?></strong></div>
          <div style="display:flex;justify-content:space-between;gap:12px;background:#fff;padding:13px 16px;align-items:center"><span style="color:#475569;font-weight:700;font-size:13px">Status :</span><strong style="font-size:20px;font-weight:900;color:#0f2040;letter-spacing:-.01em;line-height:1"><?= htmlspecialchars($s) ?></strong></div>
          <div style="display:flex;justify-content:space-between;gap:12px;background:#fff;padding:13px 16px;align-items:center"><span style="color:#475569;font-weight:700;font-size:13px">Amount :</span><strong style="font-size:13px;color:#0f2040;">₱ <?= $amountFmt ?></strong></div>
          <div style="display:flex;justify-content:space-between;gap:12px;background:#fff;padding:13px 16px;align-items:flex-start"><span style="color:#475569;font-weight:700;font-size:13px;flex-shrink:0">Description :</span><strong style="font-size:13px;color:#0f2040;text-align:right;max-width:62%;word-break:break-word;"><?= $param2 ? htmlspecialchars($param2) : ($dragonMsg ? htmlspecialchars($dragonMsg) : '—') ?></strong></div>
        </div>
      </div>
      <div class="retback-actions no-print" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
        <button class="btn btn-primary" onclick="window.print()" style="background:var(--blue);color:#fff;border-color:var(--blue);flex:1 1 160px;justify-content:center;min-height:44px"><i class="fa-solid fa-print"></i> Print Receipt</button>
        <a href="<?= $payments_base ?>" class="btn" style="background:#fff;border:1px solid var(--line);flex:1 1 160px;justify-content:center;min-height:44px">Back to Hub</a>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
