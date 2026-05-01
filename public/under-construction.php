<?php
http_response_code(503);
header('Retry-After: 86400');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Υπό Κατασκευή</title>
    <style>
        :root {
            --ink: #1f2933;
            --muted: #607080;
            --green: #2f7d5c;
            --gold: #d9a441;
            --paper: #f7f4ee;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            font-family: Arial, Helvetica, sans-serif;
            background:
                linear-gradient(135deg, rgba(47, 125, 92, 0.14), rgba(217, 164, 65, 0.14)),
                var(--paper);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        main {
            width: min(720px, 100%);
            background: var(--white);
            border: 1px solid rgba(31, 41, 51, 0.12);
            border-radius: 8px;
            padding: clamp(28px, 6vw, 56px);
            box-shadow: 0 18px 50px rgba(31, 41, 51, 0.12);
        }

        .mark {
            width: 54px;
            height: 54px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            color: var(--white);
            background: var(--green);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 24px;
        }

        p.kicker {
            color: var(--green);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0;
            margin: 0 0 10px;
            text-transform: uppercase;
        }

        h1 {
            font-size: clamp(32px, 6vw, 52px);
            line-height: 1.04;
            margin: 0;
        }

        p {
            color: var(--muted);
            font-size: 18px;
            line-height: 1.65;
            margin: 18px 0 0;
        }

        .status {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(31, 41, 51, 0.12);
            display: flex;
            gap: 10px;
            align-items: center;
            color: var(--ink);
            font-size: 15px;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--gold);
            flex: 0 0 auto;
        }
    </style>
</head>
<body>
    <main>
        <div class="mark">ΣΓ</div>
        <p class="kicker">Σύνδεσμος Γονέων &amp; Κηδεμόνων</p>
        <h1>Η ιστοσελίδα ετοιμάζεται.</h1>
        <p>Κάνουμε τις τελευταίες ρυθμίσεις πριν ανοίξει επίσημα. Παρακαλούμε επιστρέψτε σύντομα.</p>
        <div class="status">
            <span class="dot" aria-hidden="true"></span>
            <span>Προσωρινά μη διαθέσιμη για το κοινό.</span>
        </div>
    </main>
</body>
</html>
