<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Autorizar acesso | 4TechLead</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, sans-serif; color: #172033; background: #f4f7fb; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 24px; }
        .card { width: min(100%, 480px); overflow: hidden; background: #fff; border: 1px solid #dbe3ef; border-radius: 20px; box-shadow: 0 18px 50px rgb(24 39 75 / 12%); }
        .header { padding: 32px 32px 20px; text-align: center; }
        .mark { width: 58px; height: 58px; margin: 0 auto 18px; display: grid; place-items: center; border-radius: 16px; color: #fff; background: #1769e0; font-size: 26px; font-weight: 800; }
        h1 { margin: 0; font-size: 24px; line-height: 1.2; }
        .muted { color: #62708a; }
        .intro { margin: 12px 0 0; line-height: 1.5; }
        .content { padding: 0 32px 28px; }
        .identity, .permissions { padding: 16px; border: 1px solid #e1e7f0; border-radius: 12px; }
        .identity { margin-bottom: 20px; background: #f7f9fc; }
        .label { margin: 0 0 5px; color: #62708a; font-size: 13px; }
        .email { margin: 0; font-weight: 650; overflow-wrap: anywhere; }
        .permissions h2 { margin: 0 0 12px; font-size: 15px; }
        .permissions ul { margin: 0; padding: 0; list-style: none; }
        .permissions li { display: flex; gap: 9px; align-items: flex-start; margin-top: 9px; color: #45536b; font-size: 14px; line-height: 1.4; }
        .permissions li:first-child { margin-top: 0; }
        .check { color: #1769e0; font-weight: 800; }
        .actions { display: flex; gap: 12px; padding: 0 32px 32px; }
        button { min-height: 44px; flex: 1; border-radius: 10px; padding: 0 16px; font: inherit; font-weight: 650; cursor: pointer; }
        .deny { color: #344159; background: #fff; border: 1px solid #cbd5e4; }
        .approve { color: #fff; background: #1769e0; border: 1px solid #1769e0; }
        button:focus-visible { outline: 3px solid rgb(23 105 224 / 30%); outline-offset: 2px; }
    </style>
</head>
<body>
    <main class="card">
        <header class="header">
            <div class="mark" aria-hidden="true">4T</div>
            <h1>Autorizar acesso ao {{ $client->name }}</h1>
            <p class="intro muted">O aplicativo solicitou acesso ao seu contexto autorizado no 4TechLead.</p>
        </header>

        <section class="content">
            <div class="identity">
                <p class="label">Você está conectado como</p>
                <p class="email">{{ $user->email }}</p>
            </div>

            <div class="permissions">
                <h2>Permissões solicitadas</h2>
                <ul>
                    @forelse ($scopes as $scope)
                        <li><span class="check" aria-hidden="true">✓</span><span>{{ $scope->description }}</span></li>
                    @empty
                        <li><span class="check" aria-hidden="true">✓</span><span>Usar as funcionalidades autorizadas do MCP.</span></li>
                    @endforelse
                </ul>
            </div>
        </section>

        <footer class="actions">
            <form method="POST" action="{{ route('passport.authorizations.deny') }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="state" value="">
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button class="deny" type="submit">Cancelar</button>
            </form>
            <form method="POST" action="{{ route('mcp.oauth.authorize.approve') }}">
                @csrf
                <input type="hidden" name="state" value="">
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button class="approve" type="submit">Autorizar acesso</button>
            </form>
        </footer>
    </main>
</body>
</html>
