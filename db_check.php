<?php
require 'config.php';
$res = $conn->query("SHOW TABLES");
$html = "";
while($row = $res->fetch_array()) {
    $html .= "TABLE: " . $row[0] . "\n";
    $c = $conn->query("DESCRIBE `" . $row[0] . "`");
    while($r = $c->fetch_assoc()) {
        $html .= "  - " . $r['Field'] . "\n";
    }
    $html .= "\n";
}
echo "<pre>$html</pre>";
?>
