<?php  
session_start();
$_SESSION['isin'] = 'iamin';

// Password protection
$admin_password = "import_stud123#"; // Hidden password
$is_authenticated = isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;

// Handle login
if (isset($_POST['admin_login'])) {
    $entered_password = $_POST['admin_password'] ?? '';
    if ($entered_password === $admin_password) {
        $_SESSION['admin_authenticated'] = true;
        $is_authenticated = true;
    } else {
        $login_error = "Invalid password. Access denied.";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_authenticated']);
    $is_authenticated = false;
    header("Location: csv.php");
    exit;
}

// If not authenticated, show login form
if (!$is_authenticated) {
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>UPHSL Admin - Login Required</title>
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
            
            .login-container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                padding: 40px;
                max-width: 450px;
                width: 100%;
                position: relative;
                overflow: hidden;
            }
            
            .login-container::before {
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
                width: 80px;
                height: 80px;
                margin: 0 auto 20px;
                background: var(--primary-color);
                border-radius: 15px;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 10px 25px rgba(28, 77, 161, 0.3);
            }
            
            .logo img {
                width: 60px;
                height: 60px;
                object-fit: contain;
            }
            
            .university-name {
                color: var(--primary-color);
                font-family: 'Barlow Semi Condensed', sans-serif;
                font-size: 20px;
                font-weight: 800;
                margin-bottom: 10px;
            }
            
            .page-title {
                color: var(--secondary-color);
                font-family: 'Barlow Semi Condensed', sans-serif;
                font-size: 16px;
                font-weight: 600;
                margin-bottom: 20px;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .form-label {
                display: block;
                color: var(--text-dark);
                font-weight: 600;
                margin-bottom: 8px;
                font-size: 14px;
            }
            
            .form-input {
                width: 100%;
                padding: 12px 15px;
                border: 2px solid #e1e5e9;
                border-radius: 8px;
                font-size: 14px;
                transition: all 0.3s ease;
                font-family: 'Montserrat', sans-serif;
            }
            
            .form-input:focus {
                outline: none;
                border-color: var(--primary-color);
                box-shadow: 0 0 0 3px rgba(28, 77, 161, 0.1);
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
                font-family: 'Montserrat', sans-serif;
            }
            
            .submit-btn:hover {
                background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(28, 77, 161, 0.3);
            }
            
            .error {
                color: #dc3545;
                text-align: center;
                margin-bottom: 20px;
                padding: 10px;
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                border-radius: 8px;
                font-size: 14px;
            }
            
            @media (max-width: 768px) {
                .login-container {
                    padding: 30px 20px;
                    margin: 10px;
                }
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="header">
                <div class="logo">
                    <img src="../assets/images/Logos/2026_Logo.png" alt="UPHSL Logo 2025">
                </div>
                <div class="university-name">University of Perpetual Help System</div>
                <div class="page-title">Admin Access Required</div>
            </div>
            
            <?php if (isset($login_error)) { ?>
                <div class="error"><?php echo $login_error; ?></div>
            <?php } ?>
            
            <form method="post">
                <div class="form-group">
                    <label for="admin_password" class="form-label">Password</label>
                    <input type="password" name="admin_password" id="admin_password" class="form-input" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="admin_login" class="submit-btn">🔐 Login</button>
                </div>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

include "dbconnect.php";

// Set character set to UTF-8 for proper handling of special characters
if (isset($con)) {
    mysqli_set_charset($con, 'utf8mb4');
}

include "campus_table_manager.php";

// Ensure campus tables exist on initial load
$table_result = ensureCampusTablesExist($con);
logTableCreation($con, $table_result);
// Ensure temporary tables exist for CSV New Enroll tab
$tmp_table_result = ensureTmpStudentTablesExist($con);
logTableCreation($con, $tmp_table_result);

// Handle Post/Redirect/Get token to avoid double-submit on refresh
if (!isset($_SESSION['import_csrf'])) {
    $_SESSION['import_csrf'] = bin2hex(random_bytes(16));
}

// Helper: clean a CSV/Excel field value
function cleanImportField($value, $fieldType = 'generic') {
    // Ensure it's a string
    if (!is_string($value)) { $value = strval($value); }
    // Remove UTF-8 BOM if present
    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
    // Trim whitespace (including non-breaking spaces)
    $value = trim(str_replace("\xC2\xA0", ' ', $value));
    // Remove surrounding quotes only if both start and end are quotes
    if ((strlen($value) >= 2) && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
        $value = substr($value, 1, -1);
    }
    // Normalize internal whitespace to single spaces
    $value = preg_replace('/\s+/', ' ', $value);
    // Placeholder handling only for non-name fields; allow names like "NA" or "-" legitimately
    if ($fieldType === 'course' || $fieldType === 'generic') {
        $upper = strtoupper($value);
        // Consider only strong placeholders as empty; do NOT treat 'NA' or '-' as empty
        if ($upper === 'NULL' || $upper === 'N/A' || $upper === 'NONE') {
            $value = '';
        }
    }
    return $value;
}


// Handle Excel file upload and import
if (isset($_POST["btnsubmit"]) && isset($_FILES["excel_file"])) {
    $campid = $_POST["campid"];
    $table = mapCampusToTable($campid);
    
    echo "<script>console.log('Import request - Campus: $campid, Table: $table');</script>";
    
    if ($table === null) {
        $err = "Invalid campus selected.";
    } else if (!tableExists($con, $table)) {
        $err = "Campus database table not available.";
    } else {
        $file = $_FILES["excel_file"];
        
        // Check if file was uploaded successfully
        if ($file["error"] == UPLOAD_ERR_OK) {
            $fileType = pathinfo($file["name"], PATHINFO_EXTENSION);
            
            // Check if file is CSV format
            if (strtolower($fileType) == 'csv') {
                // Move uploaded file to temporary location
                $uploadDir = "uploads/";
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = $uploadDir . uniqid() . "_" . $file["name"];
                if (move_uploaded_file($file["tmp_name"], $fileName)) {
                    // Process CSV file
                    $result = processExcelFile($con, $fileName, $table);
                    
                    if ($result['success']) {
                        $success = "Successfully imported " . $result['imported'] . " student records to " . strtoupper($table) . " table.";
                        
                        // Count different types of skipped records
                        $duplicateCount = !empty($result['duplicateRecords']) ? count($result['duplicateRecords']) : 0;
                        $errorCount = !empty($result['skippedRecords']) ? count($result['skippedRecords']) : 0;
                        $totalSkipped = $duplicateCount + $errorCount;
                        
                        if ($totalSkipped > 0) {
                            // Build summary with reasons
                            $skipReasons = [];
                            if ($duplicateCount > 0) {
                                $skipReasons[] = $duplicateCount . " duplicate" . ($duplicateCount > 1 ? "s" : "");
                            }
                            if ($errorCount > 0) {
                                $skipReasons[] = $errorCount . " error" . ($errorCount > 1 ? "s" : "");
                            }
                            
                            if (!empty($skipReasons)) {
                                $success .= " " . $totalSkipped . " record" . ($totalSkipped > 1 ? "s were" : " was") . " skipped (" . implode(", ", $skipReasons) . ").";
                            } else {
                                $success .= " " . $totalSkipped . " record" . ($totalSkipped > 1 ? "s were" : " was") . " skipped.";
                            }
                            
                            // Create detailed message for browser dialog
                            $detailedMessage = "IMPORT SUMMARY:\n\n";
                            $detailedMessage .= "✅ Successfully imported: " . $result['imported'] . " new records\n";
                            $detailedMessage .= "⚠️ Skipped records: " . $totalSkipped . " total\n\n";
                            
                            if ($duplicateCount > 0) {
                                $detailedMessage .= "📋 DUPLICATE RECORDS ({$duplicateCount}):\n";
                                $detailedMessage .= "Reason: Student number already exists in database\n";
                                $detailedMessage .= "─────────────────────────────────────────────\n";
                                $duplicatesToShow = array_slice($result['duplicateRecords'], 0, 20);
                                foreach ($duplicatesToShow as $duplicate) {
                                    $detailedMessage .= "• " . $duplicate . "\n";
                                }
                                if ($duplicateCount > 20) {
                                    $detailedMessage .= "... and " . ($duplicateCount - 20) . " more duplicates\n";
                                }
                                $detailedMessage .= "\n";
                            }
                            
                            if ($errorCount > 0) {
                                $detailedMessage .= "❌ ERROR RECORDS ({$errorCount}):\n";
                                $detailedMessage .= "Reasons may include: Insufficient columns, empty required fields, database errors\n";
                                $detailedMessage .= "─────────────────────────────────────────────\n";
                                $errorsToShow = array_slice($result['skippedRecords'], 0, 20);
                                foreach ($errorsToShow as $error) {
                                    $detailedMessage .= "• " . $error . "\n";
                                }
                                if ($errorCount > 20) {
                                    $detailedMessage .= "... and " . ($errorCount - 20) . " more errors\n";
                                }
                                $detailedMessage .= "\n";
                            }
                            
                            // Store detailed message for JavaScript alert
                            $success .= "<script>setTimeout(function(){ alert(" . json_encode($detailedMessage) . "); }, 1000);</script>";
                        }
                    } else {
                        $err = "Error processing CSV file: " . $result['message'];
                    }
                    
                    
                    // Clean up uploaded file
                    unlink($fileName);
                } else {
                    $err = "Failed to upload file.";
                }
            } else {
                $err = "Please upload a CSV file (.csv).";
            }
        } else {
            $err = "Error uploading file: " . $file["error"];
        }
    }
}


// Function to update progress
function updateProgress($progress, $status, $details = '') {
    // Store progress in session for now (simpler approach)
    $_SESSION['import_progress'] = $progress;
    $_SESSION['import_status'] = $status;
    $_SESSION['import_details'] = $details;
}

// Function to process Excel file with specific format
function processExcelFile($con, $filePath, $table) {
    // Reset progress
    updateProgress(0, 'Starting import...', 'Initializing...');
    
    // Optimize memory usage for large files
    ini_set('memory_limit', '512M');
    ini_set('max_execution_time', 300); // 5 minutes for large files
    
    // For now, we'll process as CSV. For full Excel support, consider installing PhpSpreadsheet
    $handle = fopen($filePath, "r");
    if (!$handle) {
        updateProgress(0, 'Error', 'Could not open file');
        return ['success' => false, 'message' => 'Could not open file'];
    }
    
    // Count total lines first for progress calculation
    $totalLines = 0;
    while (($data = fgetcsv($handle, 2000, ",")) !== FALSE) {
        $totalLines++;
    }
    rewind($handle);
    
    updateProgress(5, 'Reading Excel file...', "Found {$totalLines} rows to process");
    
    $imported = 0;
    $skipped = 0;
    $lineNumber = 0;
    $batchSize = 200; // Process in larger batches for better performance with large files
    $batchData = [];
    $batchLineNumbers = [];
    $batchStudentNumbers = [];
    $skippedRecords = []; // Track skipped records with detailed reasons
    $duplicateRecords = []; // Track duplicate student numbers specifically
    
    // Table should already exist from campus_table_manager.php
    // Just verify it exists
    updateProgress(10, 'Setting up database...', 'Verifying table structure');
    $checkTableSQL = "SHOW TABLES LIKE '{$table}'";
    $result = mysqli_query($con, $checkTableSQL);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        fclose($handle);
        updateProgress(0, 'Error', 'Table does not exist. Please ensure campus tables are created first.');
        return ['success' => false, 'message' => 'Table does not exist. Please ensure campus tables are created first.'];
    }
    
    // Start transaction for better performance
    mysqli_autocommit($con, FALSE);
    updateProgress(15, 'Validating data format...', 'Checking column structure');
    
    while (($data = fgetcsv($handle, 2000, ",")) !== FALSE) {
        $lineNumber++;
        
        // Update progress every 500 rows
        // Update progress every 1000 rows for large files (optimized for 6000+ records)
        if ($lineNumber % 1000 == 0) {
            $progress = 15 + (($lineNumber / $totalLines) * 75); // 15% to 90%
            $remaining = $totalLines - $lineNumber;
            $processed = $lineNumber;
            $timeEstimate = $remaining > 0 ? " (~" . round($remaining / 1000) . "k rows remaining)" : "";
            updateProgress($progress, 'Processing student records...', "Processed {$processed} of {$totalLines} rows{$timeEstimate}");
        }
        
    // No header skipping: every row is a data row
        
    // We need at least 3 columns: stud_num, lname, fname (course optional at the end)
    $colCount = count($data);
    if ($colCount < 3) {
            $skippedRecords[] = "Row {$lineNumber}: Insufficient columns (found {$colCount}, expected 3-4) - Data: " . implode('|', array_slice($data, 0, 3));
            $skipped++;
            continue;
        }
        
    // Map columns with cleaning. Robust to variable extra name columns:
    // Assumption: Column 1 = stud_num, Column 2 = lname, Last column = course (optional),
    // fname = concatenate columns 3 .. last-1 (or just column 3 if only 3-4 cols)
    $studNum = cleanImportField($data[0], 'generic');
    $lname = cleanImportField($data[1], 'name');
    if ($colCount == 3) {
        $fname = cleanImportField($data[2], 'name');
        $course = '';
    } else {
        $course = cleanImportField($data[$colCount - 1], 'course');
        $fnameParts = [];
        for ($i = 2; $i < $colCount - 1; $i++) {
            $p = cleanImportField($data[$i], 'name');
            if ($p !== '') { $fnameParts[] = $p; }
        }
        $fname = trim(implode(' ', $fnameParts));
    }

    // No auto-recovery: rely strictly on provided columns
        
    // Skip empty rows (course is optional)
    if (empty($studNum) || empty($lname) || empty($fname)) {
        $emptyFields = [];
        if (empty($studNum)) $emptyFields[] = "Student Number";
        if (empty($lname)) $emptyFields[] = "Last Name";
        if (empty($fname)) $emptyFields[] = "First Name";
        $skippedRecords[] = "Row {$lineNumber}: Empty required fields - " . implode(", ", $emptyFields) . " - Raw data: " . implode('|', array_slice($data, 0, 4));
        $skipped++;
        continue;
    }

    // Normalize casing for names and course for better display/storage
    // Preserve case-sensitive short names like "NA" or "-" by only Title-Casing if length > 2
    if (mb_strlen($lname, 'UTF-8') > 2) {
        $lname = mb_convert_case($lname, MB_CASE_TITLE, 'UTF-8');
    }
    if (mb_strlen($fname, 'UTF-8') > 2) {
        $fname = mb_convert_case($fname, MB_CASE_TITLE, 'UTF-8');
    }
    // Normalize course spacing if present (course is optional)
    if (!empty($course)) {
        $course = preg_replace('/\s+/', ' ', $course);
    } else {
        $course = null; // Set to NULL if empty
    }
        
    // Escape data
    $studNum = mysqli_real_escape_string($con, $studNum);
    $lname = mysqli_real_escape_string($con, $lname);
    $fname = mysqli_real_escape_string($con, $fname);
    $course = ($course !== null) ? mysqli_real_escape_string($con, $course) : 'NULL';
        
    // Add to batch (handle NULL course properly)
    $courseValue = ($course === 'NULL') ? 'NULL' : "'{$course}'";
    $batchRecord = "('{$studNum}', '{$lname}', '{$fname}', {$courseValue})";
    $batchData[] = $batchRecord;
    
    // Store the original line data for tracking
    $batchLineNumbers[] = $lineNumber;
    $batchStudentNumbers[] = $studNum;
        
        // Process batch when it reaches batch size
        if (count($batchData) >= $batchSize) {
            $result = processBatch($con, $table, $batchData, $batchLineNumbers, $batchStudentNumbers);
            $imported += $result['imported'];
            $skipped += $result['skipped'];
            if (!empty($result['duplicateRecords'])) {
                $duplicateRecords = array_merge($duplicateRecords, $result['duplicateRecords']);
            }
            if (!empty($result['skippedRecords'])) {
                $skippedRecords = array_merge($skippedRecords, $result['skippedRecords']);
            }
            $batchData = []; // Reset batch
            $batchLineNumbers = [];
            $batchStudentNumbers = [];
        }
    }
    
    // Process remaining batch
    if (!empty($batchData)) {
        $result = processBatch($con, $table, $batchData, $batchLineNumbers, $batchStudentNumbers);
        $imported += $result['imported'];
        $skipped += $result['skipped'];
        if (!empty($result['duplicateRecords'])) {
            $duplicateRecords = array_merge($duplicateRecords, $result['duplicateRecords']);
        }
        if (!empty($result['skippedRecords'])) {
            $skippedRecords = array_merge($skippedRecords, $result['skippedRecords']);
        }
    }
    
    updateProgress(95, 'Finalizing import...', 'Saving to database');
    
    // Commit transaction
    if (mysqli_commit($con)) {
        $success = true;
        updateProgress(100, 'Import completed!', "Successfully imported {$imported} records, {$skipped} skipped");
    } else {
        mysqli_rollback($con);
        $success = false;
        updateProgress(0, 'Error', 'Database transaction failed');
    }
    
    mysqli_autocommit($con, TRUE);
    fclose($handle);
    
        return [
            'success' => $success,
            'imported' => $imported,
            'skipped' => $skipped,
            'skippedRecords' => $skippedRecords,
            'duplicateRecords' => $duplicateRecords
        ];
}

// Function to process batch of records
function processBatch($con, $table, $batchData, $lineNumbers = [], $studentNumbers = []) {
    $imported = 0;
    $skipped = 0;
    $duplicateRecords = [];
    $skippedRecords = [];
    
    if (empty($batchData)) {
        return ['imported' => 0, 'skipped' => 0, 'duplicateRecords' => [], 'skippedRecords' => []];
    }
    
    // First, check which student numbers already exist in the database
    $studentNumsToCheck = array_filter($studentNumbers); // Remove empty values
    if (!empty($studentNumsToCheck)) {
        $escapedNumbers = array_map(function($num) use ($con) {
            return "'" . mysqli_real_escape_string($con, $num) . "'";
        }, $studentNumsToCheck);
        
        $checkSQL = "SELECT `stud_num` FROM `{$table}` WHERE `stud_num` IN (" . implode(',', $escapedNumbers) . ")";
        $checkResult = mysqli_query($con, $checkSQL);
        
        $existingNumbers = [];
        if ($checkResult) {
            while ($row = mysqli_fetch_assoc($checkResult)) {
                $existingNumbers[$row['stud_num']] = true;
            }
        }
        
        // Separate records into new and duplicate
        $newBatchData = [];
        $newLineNumbers = [];
        $newStudentNumbers = [];
        
        foreach ($batchData as $index => $record) {
            $studentNumber = isset($studentNumbers[$index]) ? $studentNumbers[$index] : '';
            $lineNumber = isset($lineNumbers[$index]) ? $lineNumbers[$index] : 'Unknown';
            
            if (!empty($studentNumber) && isset($existingNumbers[$studentNumber])) {
                // This is a duplicate
                $duplicateRecords[] = "Row {$lineNumber}: Student Number '{$studentNumber}' (duplicate)";
                $skipped++;
            } else {
                // This is a new record
                $newBatchData[] = $record;
                $newLineNumbers[] = $lineNumber;
                $newStudentNumbers[] = $studentNumber;
            }
        }
        
        // Only insert new records
        if (!empty($newBatchData)) {
            $values = implode(',', $newBatchData);
            $sql = "INSERT INTO `{$table}` (`stud_num`, `lname`, `fname`, `course`) 
                    VALUES {$values}";
            
            if (mysqli_query($con, $sql)) {
                $imported = mysqli_affected_rows($con);
            } else {
                // If batch insert fails, try individual inserts to identify specific errors
                foreach ($newBatchData as $index => $record) {
                    $sql = "INSERT INTO `{$table}` (`stud_num`, `lname`, `fname`, `course`) 
                            VALUES {$record}";
                    
                    $lineNumber = isset($newLineNumbers[$index]) ? $newLineNumbers[$index] : 'Unknown';
                    $studentNumber = isset($newStudentNumbers[$index]) ? $newStudentNumbers[$index] : 'Unknown';
                    
                    if (mysqli_query($con, $sql)) {
                        if (mysqli_affected_rows($con) > 0) {
                            $imported++;
                        } else {
                            $skipped++;
                            $duplicateRecords[] = "Row {$lineNumber}: Student Number '{$studentNumber}' (duplicate)";
                        }
                    } else {
                        $skipped++;
                        $skippedRecords[] = "Row {$lineNumber}: Student Number '{$studentNumber}' - Database error: " . mysqli_error($con);
                    }
                }
            }
        }
    } else {
        // Fallback: if we can't check duplicates, use INSERT IGNORE
        $values = implode(',', $batchData);
        $sql = "INSERT IGNORE INTO `{$table}` (`stud_num`, `lname`, `fname`, `course`) 
                VALUES {$values}";
        
        if (mysqli_query($con, $sql)) {
            $imported = mysqli_affected_rows($con);
            $skipped = count($batchData) - $imported;
            // Can't identify specific duplicates in this case
        } else {
            $skipped = count($batchData);
            $skippedRecords[] = "Batch insert failed: " . mysqli_error($con);
        }
    }
    
    return ['imported' => $imported, 'skipped' => $skipped, 'duplicateRecords' => $duplicateRecords, 'skippedRecords' => $skippedRecords];
}

// Temporary-students importer (merged from csv_tmp.php)
$err_tmp = '';
$success_tmp = '';

// Small helper to normalize CSV fields for temporary import
function cleanField($value) {
    if (!is_string($value)) { $value = strval($value); }
    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
    $value = trim(str_replace("\xC2\xA0", ' ', $value));
    if ((strlen($value) >= 2) && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
        $value = substr($value, 1, -1);
    }
    $value = preg_replace('/\s+/', ' ', $value);
    return $value;
}

// Process a batch of temp-student rows with duplicate check
function processBatchTmp($con, $table, $batchValues, $batchLocators) {
    $inserted = 0;
    $skipped = 0;
    $duplicates = [];
    if (empty($batchValues)) return ['inserted'=>0,'skipped'=>0,'duplicates'=>[]];

    // Check existing locator_num in DB
    $escapedLocs = array_map(function($n) use ($con) { return "'" . mysqli_real_escape_string($con, $n) . "'"; }, $batchLocators);
    $exist = [];
    if (!empty($escapedLocs)) {
        $checkSQL = "SELECT `locator_num` FROM `{$table}` WHERE `locator_num` IN (" . implode(',', $escapedLocs) . ")";
        $res = @mysqli_query($con, $checkSQL);
        if ($res) {
            while ($r = mysqli_fetch_assoc($res)) { $exist[$r['locator_num']] = true; }
        }
    }

    $newValues = [];
    foreach ($batchValues as $idx => $val) {
        $locator = $batchLocators[$idx] ?? '';
        if ($locator !== '' && isset($exist[$locator])) {
            $duplicates[] = $locator;
            $skipped++;
        } else {
            $newValues[] = $val;
        }
    }

    if (!empty($newValues)) {
        $sql = "INSERT INTO `{$table}` (`locator_num`,`stud_name`) VALUES " . implode(',', $newValues) . " ON DUPLICATE KEY UPDATE stud_name=VALUES(stud_name)";
        if (@mysqli_query($con, $sql)) {
            $affected = mysqli_affected_rows($con);
            $inserted += $affected;
        } else {
            // If batch insert fails, try individual inserts to continue
            foreach ($newValues as $v) {
                $ins = "INSERT INTO `{$table}` (`locator_num`,`stud_name`) VALUES {$v} ON DUPLICATE KEY UPDATE stud_name=VALUES(stud_name)";
                if (@mysqli_query($con, $ins)) { $inserted += mysqli_affected_rows($con); }
                else { $skipped++; }
            }
        }
    }

    return ['inserted'=>$inserted, 'skipped'=>$skipped, 'duplicates'=>$duplicates];
}

// Handle temporary CSV upload and import (tab: CSV New Enroll)
if (isset($_POST['btnsubmit_tmp']) && isset($_FILES['csv_file_tmp'])) {
    $campid_tmp = $_POST['campid_tmp'] ?? '';
    $table_tmp = mapCampusToTmpTable($campid_tmp);

    if ($table_tmp === null) {
        $err_tmp = 'Invalid campus selected.';
    } else if (!tableExists($con, $table_tmp)) {
        $err_tmp = 'Campus temporary table not available.';
    } else {
        $file = $_FILES['csv_file_tmp'];
        if ($file['error'] == UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                $err_tmp = 'Please upload a CSV file.';
            } else {
                $uploadDir = 'uploads/';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                $fileName = $uploadDir . uniqid('csvtmp_') . '_' . basename($file['name']);
                if (move_uploaded_file($file['tmp_name'], $fileName)) {
                    $handle = @fopen($fileName, 'r');
                    if (!$handle) {
                        $err_tmp = 'Failed to open uploaded CSV.';
                        @unlink($fileName);
                    } else {
                        mysqli_autocommit($con, FALSE);
                        $batchSize = 200;
                        $batchValues = [];
                        $batchLocators = [];
                        $inserted = 0;
                        $skipped = 0;
                        $lineNum = 0;
                        $duplicateRecords = [];
                        $skippedRecords = [];

                        while (($data = fgetcsv($handle, 2000, ',')) !== FALSE) {
                            $lineNum++;
                            if (count($data) < 3) {
                                $skipped++;
                                $skippedRecords[] = "Row {$lineNum}: Insufficient columns (need at least 3 columns)";
                                continue;
                            }
                            $locator = cleanField($data[0]);
                            $name = cleanField($data[2]);
                            if ($locator === '' || $name === '') {
                                $skipped++;
                                $skippedRecords[] = "Row {$lineNum}: Empty fields";
                                continue;
                            }
                            $escLocator = mysqli_real_escape_string($con, $locator);
                            $escName = mysqli_real_escape_string($con, $name);
                            $batchValues[] = "('{$escLocator}','{$escName}')";
                            $batchLocators[] = $locator;

                            if (count($batchValues) >= $batchSize) {
                                $res = processBatchTmp($con, $table_tmp, $batchValues, $batchLocators);
                                $inserted += $res['inserted'];
                                $skipped += $res['skipped'];
                                if (!empty($res['duplicates'])) $duplicateRecords = array_merge($duplicateRecords, $res['duplicates']);
                                $batchValues = [];
                                $batchLocators = [];
                            }
                        }

                        if (!empty($batchValues)) {
                            $res = processBatchTmp($con, $table_tmp, $batchValues, $batchLocators);
                            $inserted += $res['inserted'];
                            $skipped += $res['skipped'];
                            if (!empty($res['duplicates'])) $duplicateRecords = array_merge($duplicateRecords, $res['duplicates']);
                        }

                        if (mysqli_commit($con)) {
                            $success_tmp = "Import completed. Approximately {$inserted} rows inserted.";
                            if (!empty($skippedRecords)) {
                                $success_tmp .= ' ' . count($skippedRecords) . ' lines skipped.';
                            }
                        } else {
                            mysqli_rollback($con);
                            $err_tmp = 'Database commit failed.';
                        }

                        mysqli_autocommit($con, TRUE);
                        fclose($handle);
                        @unlink($fileName);
                    }
                } else {
                    $err_tmp = 'Failed to move uploaded file.';
                }
            }
        } else {
            $err_tmp = 'Error uploading file: ' . $file['error'];
        }
    }
}

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPHSL Excel Import - Student Data</title>
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
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
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
            padding: 40px 40px 30px;
            background: linear-gradient(135deg, var(--tertiary-color), #ffffff);
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
        
        .navigation {
            background: var(--tertiary-color);
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #e9ecef;
        }
        
        .navigation a {
            display: inline-block;
            margin: 0 10px;
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-family: 'Montserrat', sans-serif;
        }
        
        .navigation a:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(28, 77, 161, 0.3);
        }
        
        .navigation a.active {
            background: linear-gradient(135deg, var(--alt-color-1), var(--alt-color-2));
        }
        
        .navigation a.logout {
            background: linear-gradient(135deg, #dc3545, #c82333);
            margin-left: 20px;
        }
        
        .navigation a.logout:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
        }
        
        .form-container {
            padding: 40px;
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
        
        .file-input-group {
            text-align: center;
            padding: 30px;
            border: 2px dashed #e1e5e9;
            border-radius: 15px;
            background: var(--tertiary-color);
            transition: all 0.3s ease;
        }
        
        .file-input-group:hover {
            border-color: var(--primary-color);
            background: #f0f8ff;
        }
        
        .file-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Montserrat', sans-serif;
            background: white;
        }
        
        .file-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(28, 77, 161, 0.1);
        }
        
        .file-info {
            margin-top: 10px;
            font-size: 14px;
        }
        
        .info-section {
            background: #e3f2fd;
            border-left: 4px solid var(--primary-color);
            padding: 25px;
            margin: 30px 0;
            border-radius: 0 15px 15px 0;
        }
        
        .info-section h3 {
            color: var(--primary-color);
            font-family: 'Barlow Semi Condensed', sans-serif;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .info-section p, .info-section li {
            color: var(--text-light);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 8px;
        }
        
        .info-section ul {
            margin-left: 20px;
        }
        
        .submit-section {
            text-align: center;
            padding: 30px;
        }
        
        .submit-btn {
            padding: 15px 40px;
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
            font-family: 'Montserrat', sans-serif;
            min-width: 200px;
        }
        
        .submit-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(28, 77, 161, 0.3);
        }
        
        .submit-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        @keyframes progressAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .progress-bar-animated {
            background: linear-gradient(-45deg, var(--alt-color-1), var(--alt-color-2), var(--alt-color-1), var(--alt-color-2));
            background-size: 400% 400%;
            animation: progressAnimation 2s ease infinite;
        }
        
        .progress-container {
            transition: all 0.3s ease;
            margin-top: 20px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .header, .form-container {
                padding: 30px 20px;
            }
            
            .navigation a {
                display: block;
                margin: 5px 0;
            }
        }
    </style>

<script>
function checkCampus(campval) {
    var fileInput = document.getElementById("excel_file");
    var submitBtn = document.getElementById("btnsubmit");
    
    if (campval === "") {
        fileInput.disabled = true;
        submitBtn.disabled = true;
    } else {
        fileInput.disabled = false;
        submitBtn.disabled = false;
        // Re-validate file if one is already selected
        validateFile();
    }
}

function validateFile() {
    var fileInput = document.getElementById("excel_file");
    var submitBtn = document.getElementById("btnsubmit");
    var campid = document.getElementById("campid").value;
    
    if (fileInput.files.length > 0 && campid !== "") {
        var fileName = fileInput.files[0].name;
        var fileExt = fileName.split('.').pop().toLowerCase();
        
        if (fileExt === 'csv') {
            submitBtn.disabled = false;
            document.getElementById("file-info").innerHTML = 
                '<div style="color: #28a745; font-size: 14px; margin-top: 10px; font-weight: 600;">✓ Valid CSV file selected</div>';
        } else {
            submitBtn.disabled = true;
            document.getElementById("file-info").innerHTML = 
                '<div style="color: #dc3545; font-size: 14px; margin-top: 10px; font-weight: 600;">✗ Please select a CSV file (.csv)</div>';
        }
    } else {
        submitBtn.disabled = true;
        document.getElementById("file-info").innerHTML = '';
    }
}

// Temporary-tab campus/file validation (global so onchange handlers can call it)
function checkCampusTmp(campval) {
    var fileInput = document.getElementById("csv_file_tmp");
    var submitBtn = document.getElementById("btnsubmit_tmp");
    
    if (!fileInput || !submitBtn) return;
    if (campval === "") {
        fileInput.disabled = true;
        submitBtn.disabled = true;
    } else {
        fileInput.disabled = false;
        submitBtn.disabled = false;
        // Re-validate file if one is already selected
        validateFileTmp();
    }
}

function validateFileTmp() {
    var fileInput = document.getElementById("csv_file_tmp");
    var submitBtn = document.getElementById("btnsubmit_tmp");
    var campid = document.getElementById("campid_tmp");
    if (!fileInput || !submitBtn || !campid) return;
    var campval = campid.value;
    if (fileInput.files.length > 0 && campval !== "") {
        var fileName = fileInput.files[0].name;
        var fileExt = fileName.split('.').pop().toLowerCase();
        
        if (fileExt === 'csv') {
            submitBtn.disabled = false;
            var el = document.getElementById("file-info-tmp");
            if (el) el.innerHTML = '<div style="color: #28a745; font-size: 14px; margin-top: 10px; font-weight: 600;">✓ Valid CSV file selected</div>';
        } else {
            submitBtn.disabled = true;
            var el = document.getElementById("file-info-tmp");
            if (el) el.innerHTML = '<div style="color: #dc3545; font-size: 14px; margin-top: 10px; font-weight: 600;">✗ Please select a CSV file (.csv)</div>';
        }
    } else {
        submitBtn.disabled = true;
        var el = document.getElementById("file-info-tmp");
        if (el) el.innerHTML = '';
    }
}

function showProgress() {
    document.getElementById("progress-container").style.display = "block";
    document.getElementById("btnsubmit").value = "Processing...";
    document.getElementById("btnsubmit").disabled = true;
    
    // Start real-time progress tracking
    startRealTimeProgress();
}

function startRealTimeProgress() {
    // Since the separate progress tracker is having issues, use simulation
    // This will provide a good user experience while the import happens
    simulateProgress();
}

function simulateProgress() {
    let progress = 0;
    let steps = [
        { percent: 10, text: "Reading Excel file...", details: "Analyzing file structure" },
        { percent: 25, text: "Validating data format...", details: "Checking column structure" },
        { percent: 40, text: "Processing student records...", details: "Importing data in batches" },
        { percent: 60, text: "Processing student records...", details: "Continuing import..." },
        { percent: 80, text: "Processing student records...", details: "Almost complete..." },
        { percent: 95, text: "Finalizing import...", details: "Saving to database" },
        { percent: 100, text: "Import completed!", details: "Processing finished" }
    ];
    
    let currentStep = 0;
    
    function updateProgress() {
        if (currentStep < steps.length) {
            const step = steps[currentStep];
            progress = step.percent;
            
            document.getElementById("progress-bar").style.width = progress + "%";
            document.getElementById("progress-bar").textContent = progress + "%";
            document.getElementById("progress-text").textContent = step.text;
            document.getElementById("progress-details").textContent = step.details;
            
            currentStep++;
            
            // Vary the timing based on the step
            let delay = currentStep < 3 ? 1000 : (currentStep < 6 ? 2000 : 1500);
            setTimeout(updateProgress, delay);
        } else {
            // Re-enable form when simulation completes
            setTimeout(() => {
                document.getElementById("btnsubmit").value = "Import Data";
                document.getElementById("btnsubmit").disabled = false;
                document.getElementById("progress-container").style.display = "none";
            }, 2000);
        }
    }
    
    updateProgress();
}
</script>

<script>
// Tab switching logic
function switchTab(tab) {
    var main = document.getElementById('tab-main');
    var tmp = document.getElementById('tab-tmp');
    var tabs = document.querySelectorAll('.navigation .tablink');
    tabs.forEach(function(el){ el.classList.remove('active'); });
    var activeBtn = document.querySelector('.navigation .tablink[data-tab="'+tab+'"]');
    if (activeBtn) activeBtn.classList.add('active');
    if (tab === 'tmp') {
        if (main) main.style.display = 'none';
        if (tmp) tmp.style.display = 'block';
    } else {
        if (tmp) tmp.style.display = 'none';
        if (main) main.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', function(){
    var tablinks = document.querySelectorAll('.navigation .tablink');
    tablinks.forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            var t = this.getAttribute('data-tab');
            switchTab(t);
        });
    });
    // initialize to main tab
    switchTab('main');
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
            <div class="page-title">CSV Import - Student Data</div>
        </div>

        <div class="navigation">
            <a href="#" class="tablink active" data-tab="main">📊 CSV Import</a>
            <a href="#" class="tablink" data-tab="tmp">📥 CSV New Enroll</a>
            <a href="guestold_student.php">💳 Payment Portal</a>
            <a href="?logout=1" class="logout">🚪 Logout</a>
        </div>

        <div class="form-container">
            <div id="tab-main" class="tab-content">
                <?php if (!empty($err)) { ?>
                    <div class="alert alert-error"><?php echo $err; ?></div>
                <?php } ?>

                <?php if (!empty($success)) { ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php } ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="campid" class="form-label">Select Campus</label>
                        <select name="campid" id="campid" class="campus-select" onchange="checkCampus(this.value)">
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

                    <div class="file-input-group">
                        <label for="excel_file" class="form-label">Select CSV File to Import</label>
                        <input type="file" name="excel_file" id="excel_file" accept=".csv" class="file-input" onchange="validateFile()" disabled>
                        <div id="file-info" class="file-info"></div>
                    </div>

                    <div class="info-section">
                        <h3>📋 File Format Requirements</h3>
                        <p><strong>File Structure:</strong></p>
                        <ul>
                            <li><strong>No header rows.</strong> Each row represents one student record.</li>
                            <li><strong>Data Columns (in order):</strong></li>
                            <li>&nbsp;&nbsp;- Column 1: <strong>Student Number</strong> (must be unique)</li>
                            <li>&nbsp;&nbsp;- Column 2: <strong>Last Name</strong></li>
                            <li>&nbsp;&nbsp;- Column 3: <strong>First Name</strong></li>
                            <li>&nbsp;&nbsp;- Column 4: <strong>Course</strong> (optional)</li>
                        </ul>
                        <p><strong>Supported format:</strong> .csv only</p>
                        <p><strong>Performance:</strong> Optimized for large files (6000+ rows)</p>
                        <p><strong>Note:</strong> Duplicate student numbers will be ignored. Empty rows are automatically skipped.</p>
                        <p style="color: var(--primary-color); font-weight: 600; margin-top: 15px;">📋 Get CSV file from Sir Arnold</p>
                    </div>

                    <div class="submit-section">
                        <input type="submit" value="📊 Import Data" id="btnsubmit" name="btnsubmit" class="submit-btn" disabled>
                        
                        <div id="progress-container" class="progress-container fade-in" style="display: none;">
                            <div style="background-color: #f0f0f0; border-radius: 10px; padding: 3px; margin-bottom: 10px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);">
                                <div id="progress-bar" class="progress-bar-animated" style="height: 20px; border-radius: 8px; width: 0%; transition: width 0.3s ease; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: bold; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">
                                    0%
                                </div>
                            </div>
                            <div id="progress-text" style="text-align: center; color: var(--text-light); font-size: 12px; font-weight: 500;">
                                Preparing to process file...
                            </div>
                            <div id="progress-details" style="text-align: center; color: var(--text-light); font-size: 11px; margin-top: 5px; font-style: italic;">
                                <!-- Progress details will be shown here -->
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div id="tab-tmp" class="tab-content" style="display:none;">
                <?php if (!empty($err_tmp)) { ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($err_tmp); ?></div>
                <?php } ?>

                <?php if (!empty($success_tmp)) { ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_tmp); ?></div>
                <?php } ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="campid_tmp" class="form-label">Select Campus</label>
                        <select name="campid_tmp" id="campid_tmp" class="campus-select" onchange="checkCampusTmp(this.value)">
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

                    <div class="file-input-group">
                        <label for="csv_file_tmp" class="form-label">Select CSV (at least 3 columns: student id in column 1, student name in column 3)</label>
                        <input type="file" name="csv_file_tmp" id="csv_file_tmp" accept=".csv" class="file-input" onchange="validateFileTmp()" disabled>
                        <div id="file-info-tmp" class="file-info"></div>
                    </div>

                    <div class="info-section">
                        <p><strong>File format:</strong> CSV with at least 3 columns per row (no header). Column 1 = locator; Column 3 = student name.</p>
                        <p><strong>Behavior:</strong> duplicates (existing locator_num) will be skipped; new rows will be inserted into the campus `_tmp_stud` table.</p>
                    </div>

                    <div class="submit-section">
                        <input type="submit" value="📄 Upload & Import" id="btnsubmit_tmp" name="btnsubmit_tmp" class="submit-btn" disabled>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>