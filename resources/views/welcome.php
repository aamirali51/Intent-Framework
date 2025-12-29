<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intent Framework</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%);
            color: #fafafa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            text-align: center;
            padding: 2rem;
        }
        .logo {
            font-size: 4rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }
        .tagline {
            font-size: 1.25rem;
            color: #888;
            margin-bottom: 2rem;
        }
        .status {
            display: inline-block;
            background: rgba(74, 222, 128, 0.1);
            border: 1px solid rgba(74, 222, 128, 0.3);
            color: #4ade80;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            margin-bottom: 3rem;
        }
        .info {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 2rem;
            max-width: 400px;
            margin: 0 auto;
        }
        .info h2 {
            font-size: 1rem;
            color: #888;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .info code {
            display: block;
            background: #0a0a0a;
            padding: 1rem;
            border-radius: 8px;
            font-family: ui-monospace, monospace;
            font-size: 0.875rem;
            color: #60a5fa;
            text-align: left;
            overflow-x: auto;
        }
        .footer {
            margin-top: 3rem;
            color: #555;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">Intent</div>
        <p class="tagline">AI-native, zero-boilerplate PHP framework</p>
        <div class="status">✓ It works!</div>
        <div class="info">
            <h2>Next Step</h2>
            <code>// routes.php<br>Route::get('/', fn($req, $res) =&gt;<br>&nbsp;&nbsp;$res-&gt;html('Hello World')<br>);</code>
        </div>
        <p class="footer">This page disappears once you define your own '/' route.</p>
    </div>
</body>
</html>
