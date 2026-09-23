<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in · JCI Carmona</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin-ui.css') }}">
</head>
<body class="login-body">
    <div class="login-wrap">
        <aside class="login-panel">
            <div class="login-brand">
                <div class="login-mark"><x-icon name="shieldBrand" /></div>
                <div>
                    <strong>JCI CARMONA</strong>
                    <span>Carmona City, Cavite</span>
                </div>
            </div>
            <h1>Project Management System</h1>
            <p>Sign in to the workspace that matches your chapter role.</p>
            <ul class="login-roles">
                <li><b>Admin</b> monitors projects, reviews, and reports</li>
                <li><b>Treasurer</b> records funds, dues, and the ledger</li>
                <li><b>BOD</b> reviews proposals and chapter reports</li>
                <li><b>Member</b> manages assigned projects and dues</li>
            </ul>
        </aside>
        <main class="login-main">
            <div class="login-box">
                <div class="eyebrow">SIGN IN</div>
                <h2>Enter your workspace</h2>
                <p class="login-hint">Demo password for every test account is <strong>password</strong>.</p>
                @if ($errors->any())
                    <div class="login-error">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('login.attempt') }}" class="login-form">
                    @csrf
                    <label class="field">
                        <span>Email</span>
                        <input type="email" name="email" value="{{ old('email', 'admin@jcicarmona.org') }}" required autocomplete="username">
                    </label>
                    <label class="field">
                        <span>Password</span>
                        <input type="password" name="password" value="password" required autocomplete="current-password">
                    </label>
                    <div class="login-row">
                        <label class="remember"><input type="checkbox" name="remember"> Keep me signed in</label>
                    </div>
                    <button class="btn btn-primary login-submit" type="submit">Enter workspace</button>
                </form>
                <div class="test-accounts">
                    <strong>Test accounts</strong>
                    <div class="test-grid">
                        @foreach ($accounts as $account)
                            <button type="button" class="test-account" data-email="{{ $account['email'] }}">
                                <span>{{ $account['role'] }}</span>
                                <b>{{ $account['email'] }}</b>
                                <em>{{ $account['name'] }}</em>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.querySelectorAll('.test-account').forEach((btn) => {
            btn.addEventListener('click', () => {
                document.querySelector('[name=email]').value = btn.dataset.email;
                document.querySelector('[name=password]').value = 'password';
            });
        });
    </script>
</body>
</html>
