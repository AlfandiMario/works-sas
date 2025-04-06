<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Medical Information Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;300;400;600;700;800&display=swap" rel="stylesheet" />
</head>

<body>
    <div class="container my-4">
        <div class="card">
            <div class="card-header">
                <h4>Response</h4>
            </div>
            <div class="card-body">
                <?php
                $defaultMsg = 'Terima kasih telah mengisi form. Semoga hari anda menyenangkan!';
                ?>
                <p><?php echo htmlspecialchars($_GET['message'] ?? $defaultMsg, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
    </div>
</body>

</html>