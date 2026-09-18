<?php
/**
 * Campus Tables Initialization Script
 * Run this script once to create all campus tables including Isabela and Roxas
 * Access via: online_payment/init_campus_tables.php
 */

include "dbconnect.php";
include "campus_table_manager.php";

// Ensure all campus tables exist (including Isabela and Roxas)
$result = ensureCampusTablesExist($con);
logTableCreation($con, $result);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>UPHSL - Campus Tables Initialization</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1c4da1;
            margin-bottom: 20px;
        }
        .success {
            color: #28a745;
            font-weight: bold;
            margin: 10px 0;
        }
        .info {
            color: #666;
            margin: 10px 0;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
            margin: 10px 0;
        }
        ul {
            margin: 10px 0;
            padding-left: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏫 Campus Tables Initialization</h1>
        
        <?php if ($result['total_created'] > 0): ?>
            <div class="success">✅ Successfully created <?php echo $result['total_created']; ?> new table(s):</div>
            <ul>
                <?php foreach ($result['created'] as $table): ?>
                    <li class="success">✓ <?php echo htmlspecialchars($table); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="info">ℹ️ No new tables needed to be created.</div>
        <?php endif; ?>
        
        <?php if ($result['total_existing'] > 0): ?>
            <div class="info">📋 <?php echo $result['total_existing']; ?> table(s) already exist:</div>
            <ul>
                <?php foreach ($result['existing'] as $table): ?>
                    <li class="info">• <?php echo htmlspecialchars($table); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        
        <div class="info" style="margin-top: 30px;">
            <strong>All campus tables are now initialized:</strong>
            <ul>
                <li>binan (UPHB)</li>
                <li>medical_university (UPHMU)</li>
                <li>gma (UPHG)</li>
                <li>manila (UPHM)</li>
                <li>pangasinan (PHCP)</li>
                <li><strong>isabela (UPHI) - NEW</strong></li>
                <li><strong>roxas (UPHR) - NEW</strong></li>
            </ul>
        </div>
        
        <div class="info" style="margin-top: 20px;">
            <p><strong>Note:</strong> Tables are also automatically created when users access:</p>
            <ul>
                <li>New Enrollees page (guest.php)</li>
                <li>Entrance Exam page (guest_exam.php)</li>
                <li>Old Students page (guestold_student.php)</li>
                <li>CSV Import page (csv.php)</li>
            </ul>
        </div>
    </div>
</body>
</html>

