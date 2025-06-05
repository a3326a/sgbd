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

    
    $email       = $_POST['email'] ?? '';
    $passwordIn  = $_POST['parola'] ?? '';

    $sql  = "SELECT id_user, nume, rol 
             FROM Utilizatori 
             WHERE email = :email 
               AND parola = :parola";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email',  $email);
    $stmt->bindParam(':parola', $passwordIn);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        
        session_start();
        $_SESSION['id_user'] = $user['ID_USER'];
        $_SESSION['nume']    = $user['NUME'];
        $_SESSION['rol']     = $user['ROL'];

        
        if ($user['ROL'] === 'admin') {
            header('Location: ../admin_dashboard.php');
        } else {
            header('Location: ../user_dashboard.php');
        }
        exit;
    } else {
        
        header('Location: ../login.html?error=invalid_credentials');
        exit;
    }

} catch (PDOException $e) {
    
    echo "Eroare la conectare/interogare: " . htmlspecialchars($e->getMessage());
    exit;
}
