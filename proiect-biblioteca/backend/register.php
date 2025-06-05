<?php

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

    
    $nume     = trim($_POST['nume'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $parola   = trim($_POST['parola'] ?? '');

    if (empty($nume) || empty($email) || empty($parola)) {
        header('Location: ../register.html?error=fields_required');
        exit;
    }

   
    $verif = "
      SELECT COUNT(*) AS CNT
      FROM Utilizatori
      WHERE email = :email
    ";
    $stmtVerif = $pdo->prepare($verif);
    $stmtVerif->bindParam(':email', $email);
    $stmtVerif->execute();
    $row = $stmtVerif->fetch(PDO::FETCH_ASSOC);
    if ($row && intval($row['CNT']) > 0) {
        header('Location: ../register.html?error=email_exists');
        exit;
    }

    
    $sql  = "
      INSERT INTO Utilizatori (nume, email, parola, rol)
      VALUES (:nume, :email, :parola, 'user')
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nume',   $nume);
    $stmt->bindParam(':email',  $email);
    $stmt->bindParam(':parola', $parola);
    $stmt->execute();

    
    header('Location: ../login.html?success=registered');
    exit;

} catch (PDOException $e) {
    
    echo "Eroare la înregistrare: " . htmlspecialchars($e->getMessage());
    exit;
}
