<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar | 4TechLead</title>
</head>
<body>
<main>
    <h1>Entrar no 4TechLead</h1>

    @if ($errors->any())
        <div role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>

        <label for="password">Senha</label>
        <input id="password" name="password" type="password" required>

        <label>
            <input name="remember" type="checkbox" value="1">
            Lembrar de mim
        </label>

        <button type="submit">Entrar</button>
    </form>
</main>
</body>
</html>
