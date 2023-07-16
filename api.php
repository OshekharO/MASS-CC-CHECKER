<?php
if (!empty($_POST["data"])) {
    $data = $_POST["data"];
    $bins = explode("|", $data)[0];
    $bankInfo = explode("|", file_get_contents("http://bins.pro/search?action=searchbins&bins=" . $bins . "&bank=&country="))[0];

    $expYear = substr($bankInfo, 0, 4);
    $expMonth = substr($bankInfo, 4, 2);
    $cvv = substr($bankInfo, 6, 3);

    $result = rand(1, 5);
    if ($result == 1) {
        echo "{\"error\":1,\"msg\":\"<div><b style='color:#00800;'>Live</b> | " . $expYear . " - " . $expMonth . " - " . $cvv . " [$0.5 Checked - Shinji]</div>\"}";
    } elseif ($result == 2) {
        echo "{\"error\":2,\"msg\":\"<div style='color:#FF0000;'>Die</b> | " . $expYear . " [GATE:01] @/Checked - Shinji</div>\"}";
    } elseif ($result == 3) {
        echo "{\"error\":3,\"msg\":\"<div><b style='color:#FF0000;'>Unknown</b> | " . $expYear . " | [GATE:01] @/ChkNET-ID</div>\"}";
    } elseif ($result == 4) {
        echo "{\"error\":2,\"msg\":\"<b>Check the validity of a credit card</b> | " . $expYear . " [GATE:01] @/ChkNET-ID</div>\"}";
    } elseif ($result == 5) {
        echo "{\"error\":3,\"msg\":\"<div><b style='color:red;'>Unknown</b> | " . $expYear . " | [GATE:01] @/ChkNET-ID</div>\"}";
    }
}
?>
