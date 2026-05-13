<?php
$err = '';
$data = null;
$d = '';

// Legge token da GET o dati cifrati da POST
$token = isset($_GET['t']) ? preg_replace('/[^a-f0-9]/', '', $_GET['t']) : '';
if ($token) {
    $fpath = __DIR__ . '/temp/' . $token . '.dat';
    if (file_exists($fpath)) {
        $d = file_get_contents($fpath);
    } else {
        $err = 'QR scaduto o non valido. Rigenera la scheda operatore.';
    }
}

// Pulisce file temporanei più vecchi di 7 giorni
foreach (glob(__DIR__ . '/temp/*.dat') as $f) {
    if (time() - filemtime($f) > 86400 * 7) unlink($f);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = isset($_POST['d']) ? $_POST['d'] : '';
    $pin = isset($_POST['pin']) ? trim($_POST['pin']) : '';

    if (strlen($pin) !== 6) {
        $err = 'PIN deve essere 6 cifre.';
    } else {
        $raw = base64_decode($d);
        if ($raw === false) {
            $err = 'Dati QR non validi.';
        } else {
            $dec = '';
            for ($i = 0; $i < strlen($raw); $i++) {
                $dec .= chr(ord($raw[$i]) ^ ord($pin[$i % 6]));
            }
            $data = json_decode($dec, true);
            if (!$data) {
                $err = 'PIN errato o dati corrotti.';
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<title>Decodifica QR Operatore</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;min-height:100dvh;background:#191c29;font-family:'Segoe UI',sans-serif;color:#fff;display:flex;align-items:center;justify-content:center;padding:1rem}
.card{background:rgba(26,43,58,.9);border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:2rem;max-width:420px;width:100%;text-align:center}
h1{font-size:1.2rem;color:#ce93d8;margin-bottom:.3rem}
.sub{font-size:.8rem;color:#888;margin-bottom:1.5rem}
.icon{font-size:3rem;margin-bottom:.5rem;opacity:.7}
label{display:block;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:#b0b0b0;margin-bottom:.25rem;text-align:left}
input{width:100%;padding:.6rem;border:1px solid rgba(255,255,255,.18);border-radius:8px;background:rgba(255,255,255,.05);color:#fff;font-size:1.5rem;letter-spacing:8px;text-align:center;font-family:monospace;transition:border-color .3s}
input:focus{outline:none;border-color:#7b1fa2;box-shadow:0 0 0 2px rgba(123,31,162,.2)}
.btn{padding:.7rem;border:none;border-radius:8px;font-weight:600;font-size:.9rem;cursor:pointer;width:100%;margin-top:1rem;background:#7b1fa2;color:#fff;transition:background .3s}
.btn:hover{background:#9c27b0}
.alert{background:rgba(244,67,54,.15);border:1px solid rgba(244,67,54,.3);color:#ef5350;padding:.6rem;border-radius:8px;font-size:.8rem;margin-top:.8rem}
.result{margin-top:1.5rem;background:rgba(76,175,80,.08);border:1px solid rgba(76,175,80,.2);border-radius:12px;padding:1.2rem;text-align:left}
.result table{width:100%}
.result td{padding:5px 6px;font-size:.9rem;vertical-align:top;color:#fff}
.result td:first-child{color:#b0b0b0;white-space:nowrap;width:38%;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px}
.result td:last-child{font-weight:600}
.footer{margin-top:1rem;font-size:.65rem;color:#555;text-align:center}
input::-webkit-outer-spin-button,input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
input[type=number]{-moz-appearance:textfield}
</style>
</head>
<body>
<div class="card">
<?php if ($data): ?>
    <div style="font-size:2rem;margin-bottom:.5rem">&#10003;</div>
    <h1>Dati Operatore</h1>
    <div class="sub">Decodifica completata</div>
    <div class="result">
        <table>
            <?php
            $fields = [
                'Grado' => $data['g'] ?? '',
                'Nome' => $data['n'] ?? '',
                'Cognome' => $data['c'] ?? '',
                'Data Nascita' => $data['dn'] ?? '',
                'Codice Fiscale' => $data['cf'] ?? '',
                'Reparto' => $data['r'] ?? '',
                'Mansione' => $data['m'] ?? '',
            ];
            foreach ($fields as $label => $val) {
                if ($val) echo "<tr><td>" . htmlspecialchars($label) . "</td><td>" . htmlspecialchars($val) . "</td></tr>\n";
            }
            ?>
        </table>
    </div>
    <button class="btn" onclick="window.location.href=window.location.pathname">Nuova decodifica</button>
<?php else: ?>
    <div class="icon">&#9741;</div>
    <h1>Inserisci PIN</h1>
    <div class="sub">Inserisci il PIN a 6 cifre per sbloccare i dati</div>
    <form method="POST">
        <input type="hidden" name="d" value="<?= htmlspecialchars($d) ?>">
        <input type="number" name="pin" maxlength="6" inputmode="numeric" autocomplete="off" autofocus placeholder="000000" style="caret-color:#7b1fa2">
        <button type="submit" class="btn">Sblocca</button>
    </form>
    <?php if ($err): ?>
    <div class="alert"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>
<?php endif; ?>
    <div class="footer">Elaborazione lato server — PIN non memorizzato</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var inp = document.querySelector('input[name="pin"]');
    if (inp) inp.focus();
});
</script>
</body>
</html>
