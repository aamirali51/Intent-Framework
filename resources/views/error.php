<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $code ?? 500 ?> - Error</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: system-ui, -apple-system, sans-serif;
            background: #0a0a0a;
            color: #fafafa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container { text-align: center; }
        h1 { font-size: 6rem; margin-bottom: 0.5rem; color: #ef4444; }
        p { color: #888; font-size: 1.25rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= $code ?? 500 ?></h1>
        <p><?= $message ?? 'Something went wrong' ?></p>
    </div>
</body>
</html>
