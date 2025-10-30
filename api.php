<?php
// Getting posted data
$data = $_POST["data"];
if (!empty($data)) {
    // Splitting the data
    preg_match("#^[\\d]{16}\\|[\\d]{2}\\|[\\d]{4}\\|[\\d]{3}$#", $data, $matches);
    $datas = explode("|", $matches[0]);

    $num = $datas[0];
    $expm = $datas[1];
    $expy = $datas[2];
    $cvv = $datas[3];

    $format = $num . "|" . $expm . "|" . $expy . "|" . $cvv;

    if ($expy >= 2023 && $expm <= 12) {
        $rand = rand(1, 3);
        if ($rand == 1) {
            echo "{\"error\":1,\"msg\":\"<div><b style='color:#008000;'>Live</b> | " . $format . " $0.5 Checked - OshekharO</div>\"}";
        } elseif ($rand == 2) {
            echo "{\"error\":2,\"msg\":\"<div><b style='color:#FF0000;'>Die</b> | " . $format . " [GATE:01] @/Checked - OshekharO</div>\"}";
        } elseif ($rand == 3) {
            echo "{\"error\":3,\"msg\":\"<div><b style='color:#800080;'>Unknown</b> | " . $format . " | [GATE:01] @/Checked - OshekharO</div>\"}";
        }
    } else {
        echo "{\"error\":4,\"msg\":\"<b>Check the validity of a credit card</b> | " . $format . " [GATE:01] @/Checked - OshekharO\"}";
    }
}
?>
