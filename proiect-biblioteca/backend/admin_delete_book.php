<?php

session_start();


if (!isset($_SESSION['id_user']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.html');
    exit;
}


$id_carte = intval($_POST['id_carte'] ?? 0);
if ($id_carte <= 0) {
    header('Location: ../admin_dashboard.php?error=invalid_id');
    exit;
}


$username = 'student';      // înlocuiește cu user-ul vostru
$password = 'STUDENT';      // înlocuiește cu parola voastră
$tns = '(DESCRIPTION=
           (ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))
           (CONNECT_DATA=(SID=XE))
        )';
$dsn = "oci:dbname=" . $tns . ";charset=AL32UTF8";

try {
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    
    $sql = "DELETE FROM Carti WHERE id_carte = :id_carte";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_carte', $id_carte, PDO::PARAM_INT);
    $stmt->execute();

    
    header('Location: ../admin_dashboard.php?success=book_deleted');
    exit;

} catch (PDOException $e) {
    
    $msg = $e->getMessage();
    if (strpos($msg, 'ORA-02292') !== false) {
        $errorText = "Nu se poate șterge cartea (există rezervări sau legături în baza de date).";
    } else {
        $errorText = "Eroare la ștergerea cărții: " . htmlspecialchars($msg);
    }
    header('Location: ../admin_dashboard.php?error=' . urlencode($errorText));
    exit;
}
