<?php

session_start();


if (!isset($_SESSION['id_user']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.html');
    exit;
}


$id_user = intval($_POST['id_user'] ?? 0);
if ($id_user <= 0) {
    header('Location: ../admin_dashboard.php?error=' . urlencode('ID valid necesar.'));
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

    
    $sqlDelete = "DELETE FROM Utilizatori WHERE id_user = :id_user";
    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->bindParam(':id_user', $id_user, PDO::PARAM_INT);
    $stmtDelete->execute();

    
    header('Location: ../admin_dashboard.php');
    exit;

} catch (PDOException $e) {
    
    $msg = 'Eroare la ștergerea utilizatorului: ' . htmlspecialchars($e->getMessage());
    header('Location: ../admin_dashboard.php?error=' . urlencode($msg));
    exit;
}
