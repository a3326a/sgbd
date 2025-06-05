<?php
session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.html');
    exit;
}
$numeAdmin = $_SESSION['nume'];
$errorMessage = '';
if (isset($_GET['error'])) {
    $errorMessage = htmlspecialchars($_GET['error']);
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
    $q_carte = '';
    $bindCarte = false;
    if (isset($_GET['q_carte']) && trim($_GET['q_carte']) !== '') {
        $q_carte = trim($_GET['q_carte']);
        $q_carte_param = '%' . mb_strtolower($q_carte, 'UTF-8') . '%';
        $bindCarte = true;
    }
    $q_user = '';
    $bindUser = false;
    if (isset($_GET['q_user']) && trim($_GET['q_user']) !== '') {
        $q_user = trim($_GET['q_user']);
        $q_user_param = '%' . mb_strtolower($q_user, 'UTF-8') . '%';
        $bindUser = true;
    }
    $sqlUsers = "
      SELECT id_user, nume, email, rol
      FROM Utilizatori
      ORDER BY id_user
    ";
    $stmtUsers = $pdo->prepare($sqlUsers);
    $stmtUsers->execute();
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
    if ($bindCarte) {
        $sqlCarti = "
          SELECT id_carte, titlu, autor, nr_exemplare
          FROM Carti
          WHERE LOWER(titlu) LIKE :bind_carte
             OR LOWER(autor) LIKE :bind_carte
          ORDER BY id_carte
        ";
        $stmtCarti = $pdo->prepare($sqlCarti);
        $stmtCarti->bindValue(':bind_carte', $q_carte_param);
    } else {
        $sqlCarti = "
          SELECT id_carte, titlu, autor, nr_exemplare
          FROM Carti
          ORDER BY id_carte
        ";
        $stmtCarti = $pdo->prepare($sqlCarti);
    }
    $stmtCarti->execute();
    $carti = $stmtCarti->fetchAll(PDO::FETCH_ASSOC);
    if ($bindUser) {
        $sqlRez = "
          SELECT R.id_rezervare,
                 U.nume AS nume_user,
                 C.titlu AS titlu_carte,
                 R.data_rezervare,
                 R.status
          FROM Rezervari R
          JOIN Utilizatori U ON R.id_user = U.id_user
          JOIN Carti C       ON R.id_carte = C.id_carte
          WHERE LOWER(U.nume) LIKE :bind_user
          ORDER BY R.data_rezervare DESC
        ";
        $stmtRez = $pdo->prepare($sqlRez);
        $stmtRez->bindValue(':bind_user', $q_user_param);
    } else {
        $sqlRez = "
          SELECT R.id_rezervare,
                 U.nume AS nume_user,
                 C.titlu AS titlu_carte,
                 R.data_rezervare,
                 R.status
          FROM Rezervari R
          JOIN Utilizatori U ON R.id_user = U.id_user
          JOIN Carti C       ON R.id_carte = C.id_carte
          ORDER BY R.data_rezervare DESC
        ";
        $stmtRez = $pdo->prepare($sqlRez);
    }
    $stmtRez->execute();
    $rezervari = $stmtRez->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Eroare la interogare: " . htmlspecialchars($e->getMessage());
    exit;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard – Biblioteca</title>
  <link rel="stylesheet" href="css/admin_dashboard.css">
  <script>
    <?php if ($errorMessage !== ''): ?>
      window.addEventListener('DOMContentLoaded', function() {
        alert("<?php echo $errorMessage; ?>");
      });
    <?php endif; ?>
  </script>
</head>
<body>
  <header>
    <h2>Administrare Biblioteca</h2>
    <nav>
      <span><?php echo htmlspecialchars($numeAdmin); ?> (Admin)</span> |
      <a href="backend/logout.php">Logout</a>
    </nav>
  </header>

  <main>
    <section>
      <h3>Utilizatori</h3>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Nume</th>
              <th>Email</th>
              <th>Rol / Acțiune</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td><?php echo htmlspecialchars($u['ID_USER']); ?></td>
              <td><?php echo htmlspecialchars($u['NUME']); ?></td>
              <td><?php echo htmlspecialchars($u['EMAIL']); ?></td>
              <td style="display: flex; align-items: center;">
                <span><?php echo htmlspecialchars($u['ROL']); ?></span>
                <?php if ($u['ROL'] === 'user'): ?>
                  <form method="POST" action="backend/delete_user.php" style="margin-left: 10px; display: inline;" onsubmit="return confirm('Ești sigur că vrei să ștergi contul lui <?php echo addslashes(htmlspecialchars($u['NUME'])); ?>?');">
                    <input type="hidden" name="id_user" value="<?php echo $u['ID_USER']; ?>">
                    <button type="submit" class="delete-button">Șterge</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section style="margin-top: 40px;">
      <h3>Cărți</h3>

      <form action="backend/admin_add_book.php" method="POST" class="add-book-form">
        <h4>Adaugă carte nouă</h4>
        <label for="titlu">Titlu:</label>
        <input type="text" name="titlu" id="titlu" required>
        <label for="autor">Autor:</label>
        <input type="text" name="autor" id="autor">
        <label for="nr_exemplare">Nr. exemplare:</label>
        <input type="number" name="nr_exemplare" id="nr_exemplare" min="0" required>
        <button type="submit">Adaugă</button>
      </form>

      <form method="GET" action="admin_dashboard.php" style="margin: 20px 0; display: flex; align-items: center;">
        <input
          type="text"
          name="q_carte"
          placeholder="Caută carte după titlu sau autor..."
          value="<?php echo htmlspecialchars($q_carte); ?>"
          style="width: 300px; padding: 6px;"
        >
        <input type="hidden" name="q_user" value="<?php echo htmlspecialchars($q_user); ?>">
        <button type="submit" style="margin-left: 8px; padding: 6px 12px;">Caută</button>
        <?php if ($q_carte !== ''): ?>
          <a href="admin_dashboard.php?<?php echo 'q_user=' . urlencode($q_user); ?>" style="margin-left: 12px; text-decoration: none; color: #444;">
            Resetează
          </a>
        <?php endif; ?>
      </form>

      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID Carte</th>
              <th>Titlu</th>
              <th>Autor</th>
              <th>Nr. Ex.</th>
              <th>Acțiune</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($carti) === 0): ?>
              <tr>
                <td colspan="5" style="text-align: center; padding: 12px;">Nu s-au găsit cărți.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($carti as $carte): ?>
              <tr>
                <td><?php echo htmlspecialchars($carte['ID_CARTE']); ?></td>
                <td><?php echo htmlspecialchars($carte['TITLU']); ?></td>
                <td><?php echo htmlspecialchars($carte['AUTOR']); ?></td>
                <td>
                  <form method="POST" action="backend/update_copies.php" style="display: inline;">
                    <input type="hidden" name="id_carte" value="<?php echo $carte['ID_CARTE']; ?>">
                    <button type="submit" name="action" value="decrease" style="padding: 2px 6px; font-size: 1rem;">–</button>
                  </form>
                  <span style="margin: 0 8px;"><?php echo htmlspecialchars($carte['NR_EXEMPLARE']); ?></span>
                  <form method="POST" action="backend/update_copies.php" style="display: inline;">
                    <input type="hidden" name="id_carte" value="<?php echo $carte['ID_CARTE']; ?>">
                    <button type="submit" name="action" value="increase" style="padding: 2px 6px; font-size: 1rem;">+</button>
                  </form>
                </td>
                <td>
                  <form method="POST" action="backend/admin_delete_book.php" style="margin:0; display:inline;">
                    <input type="hidden" name="id_carte" value="<?php echo $carte['ID_CARTE']; ?>">
                    <button type="submit" onclick="return confirm('Ești sigur că vrei să ștergi cartea „<?php echo addslashes(htmlspecialchars($carte['TITLU'])); ?>”?');" class="delete-button">
                      Șterge
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section style="margin-top: 40px;">
      <h3>Rezervări</h3>

      <form method="GET" action="admin_dashboard.php" style="margin: 20px 0; display: flex; align-items: center;">
        <input
          type="text"
          name="q_user"
          placeholder="Caută rezervări după nume utilizator..."
          value="<?php echo htmlspecialchars($q_user); ?>"
          style="width: 300px; padding: 6px;"
        >
        <input type="hidden" name="q_carte" value="<?php echo htmlspecialchars($q_carte); ?>">
        <button type="submit" style="margin-left: 8px; padding: 6px 12px;">Caută</button>
        <?php if ($q_user !== ''): ?>
          <a href="admin_dashboard.php?<?php echo 'q_carte=' . urlencode($q_carte); ?>" style="margin-left: 12px; text-decoration: none; color: #444;">
            Resetează
          </a>
        <?php endif; ?>
      </form>

      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID Rezervare</th>
              <th>Utilizator</th>
              <th>Carte</th>
              <th>Data Rez.</th>
              <th>Status</th>
              <th>Acțiune</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($rezervari) === 0): ?>
              <tr>
                <td colspan="6" style="text-align: center; padding: 12px;">Nu s-au găsit rezervări.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($rezervari as $r): ?>
              <tr>
                <td><?php echo htmlspecialchars($r['ID_REZERVARE']); ?></td>
                <td><?php echo htmlspecialchars($r['NUME_USER']); ?></td>
                <td><?php echo htmlspecialchars($r['TITLU_CARTE']); ?></td>
                <td><?php echo date('d-m-Y', strtotime($r['DATA_REZERVARE'])); ?></td>
                <td><?php echo htmlspecialchars($r['STATUS']); ?></td>
                <td>
                  <?php if ($r['STATUS'] === 'activ'): ?>
                    <form method="POST" action="backend/admin_cancel_reservation.php" style="margin:0;">
                      <input type="hidden" name="id_rezervare" value="<?php echo $r['ID_REZERVARE']; ?>">
                      <button type="submit" class="delete-button">Anulează</button>
                    </form>
                  <?php else: ?>
                    <span style="color: gray;">–</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
