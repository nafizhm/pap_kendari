<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=pap_kendari', 'root', '');
$stmt = $db->query('SELECT * FROM progres_list_penjualan ORDER BY urutan');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($row) . "\n";
}
