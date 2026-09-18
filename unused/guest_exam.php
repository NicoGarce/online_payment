<?php 	
session_start();

// Check maintenance before any output
require_once '../app/config/database.php';
require_once '../app/includes/functions.php';

// Check if Guest Exam or Online Payment section is in maintenance
// isSectionInMaintenance already checks main section if sub-page is not enabled
if (isSectionInMaintenance('online-payment', 'guest-exam')) {
    $maintenance_message = getSectionMaintenanceMessage('online-payment', null, 'guest-exam');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Online Payment - Maintenance</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                background: #f5f5f5;
            }
            .maintenance-container {
                text-align: center;
                max-width: 600px;
                padding: 3rem;
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            }
            .maintenance-icon {
                font-size: 4rem;
                color: #1c4da1;
                margin-bottom: 1.5rem;
            }
            h1 {
                font-size: 2rem;
                color: #1c4da1;
                margin-bottom: 1rem;
            }
            p {
                font-size: 1.1rem;
                color: #666;
                line-height: 1.6;
                margin-bottom: 2rem;
            }
            .btn {
                display: inline-block;
                padding: 0.75rem 1.5rem;
                background: #1c4da1;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
            }
        </style>
    </head>
    <body>
        <div class="maintenance-container">
            <div class="maintenance-icon">🔧</div>
            <h1>Under Maintenance</h1>
            <p><?php echo htmlspecialchars($maintenance_message); ?></p>
            <a href="../index.php" class="btn">Go to Homepage</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$_SESSION['isin'] = 'iamin';

include "dbconnect.php";
include "campus_table_manager.php";
include "campus_fees.php";

// Ensure all campus tables exist (including Isabela and Roxas)
ensureCampusTablesExist($con);

if (isset($_POST["btnsubmit"])) {
    date_default_timezone_set("Asia/Manila");
    $transid = $_POST["campid"] ."_". date("HismdY");
    header("Location: payment_exam.php?payee=&transid=$transid");
    die;
}
?>	

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UPHSL Online Payment - Entrance Exam</title>
    <link rel="icon" type="image/png" href="../assets/images/Logos/logo.png">
    <link rel="shortcut icon" type="image/png" href="../assets/images/Logos/logo.png">
    
    <!-- Import UPHSL Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@600;800&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-color: #1c4da1;
            --secondary-color: #527bbd;
            --tertiary-color: #f8f9fa;
            --text-dark: #2a2a2a;
            --text-light: #666;
            --alt-color-1: #ffc63e;
            --alt-color-2: #e0b03c;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 700px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--alt-color-1), var(--secondary-color));
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            background: var(--primary-color);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(28, 77, 161, 0.3);
        }
        
        .logo img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        
        .university-name {
            color: var(--primary-color);
            font-family: 'Barlow Semi Condensed', sans-serif;
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 10px;
        }
        
        .page-title {
            color: var(--secondary-color);
            font-family: 'Barlow Semi Condensed', sans-serif;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .welcome-text {
            color: var(--text-light);
            font-size: 18px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .campus-select {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            background: white;
            transition: all 0.3s ease;
            cursor: pointer;
            font-family: 'Montserrat', sans-serif;
        }
        
        .campus-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(28, 77, 161, 0.1);
        }
        
        .fees-display {
            background: linear-gradient(135deg, var(--tertiary-color), #e9ecef);
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
            border: 2px solid #e9ecef;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .fees-title {
            color: var(--primary-color);
            font-family: 'Barlow Semi Condensed', sans-serif;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .fees-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .fee-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid var(--alt-color-1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .fee-item:hover {
            transform: translateY(-2px);
        }
        
        .fee-label {
            color: var(--text-light);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .fee-amount {
            color: var(--primary-color);
            font-size: 18px;
            font-weight: 700;
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px 30px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 20px;
            font-family: 'Montserrat', sans-serif;
        }
        
        .submit-btn:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(28, 77, 161, 0.3);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .exam-info {
            background: #e3f2fd;
            border-left: 4px solid var(--primary-color);
            padding: 20px;
            margin-top: 20px;
            border-radius: 0 10px 10px 0;
        }
        
        .exam-info h4 {
            color: var(--primary-color);
            font-family: 'Barlow Semi Condensed', sans-serif;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .exam-info p {
            color: var(--text-light);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        
        .exam-info ul {
            color: var(--text-light);
            font-size: 14px;
            line-height: 1.6;
            margin-left: 20px;
        }
        
        .exam-info li {
            margin-bottom: 5px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .fees-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .university-name {
                font-size: 20px;
            }
            
            .page-title {
                font-size: 18px;
            }
            
            .welcome-text {
                font-size: 16px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .container {
                padding: 20px 15px;
            }
            
            .logo {
                width: 60px;
                height: 60px;
                font-size: 20px;
            }
            
            .fees-display {
                padding: 20px 15px;
            }
        }
    </style>

<script>
<?php echo getCourseAmountsJS(); ?>

function updateFees() {
    var campusSelect = document.getElementById('campid');
    var selectedCampus = campusSelect.value;
    var feesDiv = document.getElementById('fees-display');
    var submitSection = document.getElementById('submit-section');
    
    if (selectedCampus && courseAmounts[selectedCampus]) {
        var fees = courseAmounts[selectedCampus];
                var campusName = campusSelect.options[campusSelect.selectedIndex].text;
                
                feesDiv.innerHTML = '<div class="fees-title">📊 Entrance Exam Fees - ' + campusName + '</div>' +
                                   '<div class="fees-grid">' +
                                   '<div class="fee-item">' +
                                   '<div class="fee-label">🎓 Baccalaureate</div>' +
                                   '<div class="fee-amount">₱' + fees['Baccalaureate'].toFixed(2) + '</div>' +
                                   '</div>' +
                                   '<div class="fee-item">' +
                                   '<div class="fee-label">🎓 Graduate School</div>' +
                                   '<div class="fee-amount">₱' + fees['Graduate School'].toFixed(2) + '</div>' +
                                   '</div>' +
                                   '<div class="fee-item">' +
                                   '<div class="fee-label">⚖️ Law/Juris Doctor</div>' +
                                   '<div class="fee-amount">₱' + fees['Law/Juris Doctor'].toFixed(2) + '</div>' +
                                   '</div>' +
                                   '<div class="fee-item">' +
                                   '<div class="fee-label">📚 Basic Education</div>' +
                                   '<div class="fee-amount">₱' + fees['Basic Education'].toFixed(2) + '</div>' +
                                   '</div>' +
                           '</div>';
        feesDiv.style.display = 'block';
        submitSection.style.display = 'block';
                
                // Add animation
                feesDiv.style.opacity = '0';
                feesDiv.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    feesDiv.style.transition = 'all 0.5s ease';
                    feesDiv.style.opacity = '1';
                    feesDiv.style.transform = 'translateY(0)';
                }, 100);
    } else {
        feesDiv.style.display = 'none';
        submitSection.style.display = 'none';
    }
}
        
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.6s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });
</script>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="../assets/images/Logos/2026_Logo.png" alt="UPHSL Logo 2025">
            </div>
            <div class="university-name">University of Perpetual Help System</div>
            <div class="page-title">Entrance Exam Payment</div>
            <div class="welcome-text">Welcome, Future Perpetualites!</div>
        </div>

	<form method="post">
            <div class="form-group">
                <label class="form-label" for="campid">Select Your Campus</label>
                <select name="campid" id="campid" class="campus-select" onchange="updateFees()" required>
                    <option value="">Choose your campus...</option>
                    <option value="UPHB">🏫 Binan Campus</option>
                    <option value="UPHMU">🏥 Medical University</option>
                    <option value="UPHG">🏢 GMA Campus</option>
                    <option value="UPHM">🏛️ Manila Campus</option>
                    <option value="PHCP">🏘️ Pangasinan Campus</option>
                    <option value="UPHI">🏛️ Isabela Campus</option>
                    <option value="UPHR">🏛️ Roxas Campus</option>
		</select>
	</div>
	
            <div id="fees-display" class="fees-display" style="display: none;">
			<!-- Fees will be populated by JavaScript when campus is selected -->
		</div>
            
            <div class="exam-info">
                <h4>📋 Important Information</h4>
                <p><strong>Entrance Exam Requirements:</strong></p>
                <ul>
                    <li>Valid ID (School ID, Driver's License, or Passport)</li>
                    <li>Payment confirmation receipt</li>
                    <li>Completed application form</li>
                    <li>Recent 2x2 ID photo</li>
                </ul>
                <p><strong>Note:</strong> Please arrive 30 minutes before your scheduled exam time. Late arrivals may not be accommodated.</p>
	</div>
	
            <div id="submit-section" style="display: none;">
                <button type="submit" name="btnsubmit" class="submit-btn">
                    💳 Proceed to Payment
                </button>
	</div>
	</form>	
    </div>
</body>
</html>