<?php

header('Content-Type: application/json; charset=UTF-8');
session_start();
if (!isset($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$username = 'student';
$password = 'STUDENT';
$tns = '(DESCRIPTION=
           (ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))
           (CONNECT_DATA=(SID=XE))
         )';
$dsn = "oci:dbname=" . $tns . ";charset=AL32UTF8";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $sql = "
      SELECT
        id_carte,
        titlu,
        autor,
        nr_exemplare,
        Exemplare_Disponibile(id_carte) AS disponibile
      FROM Carti
      ORDER BY titlu
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $carti = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($carti);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
