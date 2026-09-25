<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Autorizar acesso | 4TechLead</title>
</head>
<body>
<main>
    <h1>Autorizar acesso ao 4TechLead</h1>
    <p>
        {{ $client->name }} está solicitando acesso à sua conta para conectar o Codex ao 4TechLead.
    </p>

    @if (count($scopes) > 0)
        <h2>Permissões solicitadas</h2>
        <ul>
            @foreach ($scopes as $scope)
                <li>{{ data_get($scope, 'description') ?: data_get($scope, 'id') }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('mcp.oauth.authorize.approve') }}">
        @csrf
        <input type="hidden" name="auth_token" value="{{ $authToken }}">
        <button type="submit">Autorizar</button>
    </form>

    <form method="POST" action="{{ route('passport.authorizations.deny') }}">
        @csrf
        @method('DELETE')
        <input type="hidden" name="auth_token" value="{{ $authToken }}">
        <button type="submit">Cancelar</button>
    </form>
</main>
</body>
</html>
