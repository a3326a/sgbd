<?php

header('Content-Type: application/json; charset=UTF-8');
session_start();
if (!isset($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$id_user = $_SESSION['id_user'];
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
        R.id_rezervare,
        C.titlu             AS titlu_carte,
        R.data_rezervare,
        R.status
      FROM Rezervari R
      JOIN Carti C ON R.id_carte = C.id_carte
      WHERE R.id_user = :id_user
      ORDER BY R.data_rezervare DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_user', $id_user, PDO::PARAM_INT);
    $stmt->execute();
    $rez = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rez);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
