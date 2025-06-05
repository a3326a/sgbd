<?php

session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.html');
    exit;
}

$id_rezervare = intval($_POST['id_rezervare'] ?? 0);
if ($id_rezervare <= 0) {
    header("Location: ../admin_dashboard.php?error=no_rez_id");
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
      UPDATE Rezervari
      SET status = 'anulat'
      WHERE id_rezervare = :id_rezervare
        AND status = 'activ'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_rezervare', $id_rezervare, PDO::PARAM_INT);
    $stmt->execute();

    
    if ($stmt->rowCount() === 0) {
        header("Location: ../admin_dashboard.php?error=cant_cancel");
        exit;
    }

    header("Location: ../admin_dashboard.php?success=reservation_canceled");
    exit;

} catch (PDOException $e) {
    echo "Eroare la anularea rezervării: " . htmlspecialchars($e->getMessage());
    exit;
}
