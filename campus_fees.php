<?php
/**
 * Campus-specific entrance exam fees
 * Edit this file to update fees across all exam payment pages
 */

// Course entrance exam amounts by campus
$course_amounts = array(
    'UPHB' => array( // Binan
        'Baccalaureate' => 500.00,
        'Graduate School' => 300.00,
        'Law/Juris Doctor' => 400.00,
        'Basic Education' => 200.00
    ),
    'UPHMU' => array( // Medical University
        'Baccalaureate' => 600.00,
        'Graduate School' => 400.00,
        'Law/Juris Doctor' => 500.00,
        'Basic Education' => 250.00
    ),
    'UPHG' => array( // GMA
        'Baccalaureate' => 450.00,
        'Graduate School' => 280.00,
        'Law/Juris Doctor' => 380.00,
        'Basic Education' => 180.00
    ),
    'UPHM' => array( // Manila
        'Baccalaureate' => 550.00,
        'Graduate School' => 350.00,
        'Law/Juris Doctor' => 450.00,
        'Basic Education' => 220.00
    ),
    'PHCP' => array( // Pangasinan
        'Baccalaureate' => 480.00,
        'Graduate School' => 320.00,
        'Law/Juris Doctor' => 420.00,
        'Basic Education' => 190.00
    )
);

// Generate JavaScript object for frontend use
function getCourseAmountsJS() {
    global $course_amounts;
    $js = "var courseAmounts = {\n";
    foreach ($course_amounts as $campus => $courses) {
        $js .= "    '$campus': {\n";
        foreach ($courses as $course => $amount) {
            $js .= "        '$course': $amount,\n";
        }
        $js .= "    },\n";
    }
    $js .= "};";
    return $js;
}
?>

