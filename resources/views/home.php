<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Intent' ?></title>
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
        h1 { font-size: 3rem; margin-bottom: 1rem; }
        p { color: #888; font-size: 1.25rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= $heading ?? 'Intent' ?></h1>
        <p><?= $message ?? 'AI-native, zero-boilerplate PHP framework' ?></p>
    </div>
</body>
</html>
