<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Temporarily unavailable · JCISTEM</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; color: #142943; background: #f4f8fb; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 24px; }
        main { width: min(100%, 520px); padding: clamp(28px, 5vw, 48px); background: #fff; border: 1px solid #d6e5ee; border-radius: 20px; box-shadow: 0 16px 44px rgba(12, 43, 68, .07); }
        .eyebrow { margin: 0 0 12px; color: #087fa7; font-size: .78rem; font-weight: 750; letter-spacing: .13em; text-transform: uppercase; }
        h1 { margin: 0 0 14px; font-size: clamp(1.65rem, 5vw, 2.25rem); line-height: 1.2; }
        p { margin: 0; color: #48647a; line-height: 1.65; }
        a { display: inline-block; margin-top: 28px; padding: 12px 18px; border-radius: 10px; background: #087fa7; color: #fff; font-weight: 650; text-decoration: none; }
        a:focus-visible { outline: 3px solid #55b9d6; outline-offset: 3px; }
    </style>
</head>
<body>
    <main>
        <p class="eyebrow">JCISTEM</p>
        <h1>We’re temporarily unavailable</h1>
        <p>The workspace cannot connect to its database right now. Your work has not been deleted. Please try again in a moment; if the problem continues, ask the chapter administrator to check the database service.</p>
        <a href="{{ url('/') }}">Try again</a>
    </main>
</body>
</html>
