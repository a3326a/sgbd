<?php

session_start();

if (!isset($_SESSION['id_user']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.html');
    exit;
}

$titlu        = trim($_POST['titlu'] ?? '');
$autor        = trim($_POST['autor'] ?? '');
$nr_exemplare = intval($_POST['nr_exemplare'] ?? 0);

if ($titlu === '' || $nr_exemplare < 0) {
    header("Location: ../admin_dashboard.php?error=invalid_input");
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
      INSERT INTO Carti (titlu, autor, nr_exemplare)
      VALUES (:titlu, :autor, :nr_exemplare)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':titlu',        $titlu);
    $stmt->bindParam(':autor',        $autor);
    $stmt->bindParam(':nr_exemplare', $nr_exemplare, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../admin_dashboard.php?success=book_added");
    exit;

} catch (PDOException $e) {
    echo "Eroare la adăugarea cărții: " . htmlspecialchars($e->getMessage());
    exit;
}
