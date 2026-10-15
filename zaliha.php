<?php
require "db.php";
$poruka = "";

// Dropdowni
$proizvodi = $pdo->query("SELECT proizvod_id, naziv_proizvoda FROM proizvod ORDER BY naziv_proizvoda")->fetchAll(PDO::FETCH_ASSOC);
$skladista = $pdo->query("SELECT skladiste_id, naziv_skladista FROM skladiste ORDER BY skladiste_id")->fetchAll(PDO::FETCH_ASSOC);

// UPDATE po retku
if (isset($_POST["azuriraj"])) {
    try {
        $proizvod_id  = (int)$_POST["proizvod_id"];
        $skladiste_id = (int)$_POST["skladiste_id"];
        $kolicina     = (int)$_POST["kolicina"];

        if ($kolicina < 0) {
            throw new Exception("Količina ne smije biti negativna.");
        }

        $stmt = $pdo->prepare("
            UPDATE zaliha
            SET kolicina = ?
            WHERE proizvod_id = ? AND skladiste_id = ?
        ");
        $stmt->execute([$kolicina, $proizvod_id, $skladiste_id]);

        $poruka = "Zaliha ažurirana.";
    } catch (Exception $e) {
        $poruka = "Greška: " . $e->getMessage();
    }
}

// INSERT (ako želiš dodati novi zapis zalihe)
if (isset($_POST["dodaj"])) {
    try {
        $proizvod_id  = (int)$_POST["proizvod_id_new"];
        $skladiste_id = (int)$_POST["skladiste_id_new"];
        $kolicina     = (int)$_POST["kolicina_new"];

        if ($kolicina < 0) {
            throw new Exception("Količina ne smije biti negativna.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO zaliha (proizvod_id, skladiste_id, kolicina)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$proizvod_id, $skladiste_id, $kolicina]);

        $poruka = "Zaliha dodana.";
    } catch (Exception $e) {
        $poruka = "Greška: " . $e->getMessage();
    }
}

// READ tablica
$zalihe = $pdo->query("
    SELECT z.proizvod_id, p.naziv_proizvoda,
           z.skladiste_id, s.naziv_skladista,
           z.kolicina
    FROM zaliha z
    JOIN proizvod p ON p.proizvod_id = z.proizvod_id
    JOIN skladiste s ON s.skladiste_id = z.skladiste_id
    ORDER BY p.naziv_proizvoda, s.skladiste_id
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Zalihe</title>
</head>
<body>

<nav style="margin-bottom:20px; padding:10px; border-bottom:1px solid #ccc;">
    <a href="zaliha.php">Zalihe po skladištima</a> |
    <a href="forma_stavke.php">Stavke narudžbe</a>
</nav>

<h2>Zalihe po skladištima</h2>
<p><strong><?= htmlspecialchars($poruka) ?></strong></p>

<h3>Dodaj zapis zalihe (ako ne postoji)</h3>
<form method="post">
    Proizvod:
    <select name="proizvod_id_new" required>
        <?php foreach ($proizvodi as $p): ?>
            <option value="<?= $p["proizvod_id"] ?>"><?= htmlspecialchars($p["naziv_proizvoda"]) ?></option>
        <?php endforeach; ?>
    </select>

    Skladište:
    <select name="skladiste_id_new" required>
        <?php foreach ($skladista as $s): ?>
            <option value="<?= $s["skladiste_id"] ?>"><?= htmlspecialchars($s["naziv_skladista"]) ?></option>
        <?php endforeach; ?>
    </select>

    Količina:
    <input type="number" name="kolicina_new" min="0" required>

    <button type="submit" name="dodaj">Dodaj</button>
</form>

<h3>Pregled i uređivanje zaliha</h3>
<table border="1" cellpadding="5">
    <tr>
        <th>Proizvod</th>
        <th>Skladište</th>
        <th>Količina</th>
        <th></th>
    </tr>

    <?php foreach ($zalihe as $z): ?>
        <tr>
            <form method="post">
                <td>
                    <?= htmlspecialchars($z["naziv_proizvoda"]) ?>
                    <input type="hidden" name="proizvod_id" value="<?= (int)$z["proizvod_id"] ?>">
                </td>
                <td>
                    <?= htmlspecialchars($z["naziv_skladista"]) ?>
                    <input type="hidden" name="skladiste_id" value="<?= (int)$z["skladiste_id"] ?>">
                </td>
                <td>
                    <input type="number" name="kolicina" min="0" value="<?= (int)$z["kolicina"] ?>" required>
                </td>
                <td>
                    <button type="submit" name="azuriraj">Ažuriraj</button>
                </td>
            </form>
        </tr>
    <?php endforeach; ?>

</table>

</body>
</html>
