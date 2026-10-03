<?php

namespace App\Console\Commands;

use App\Enums\AdminRole;
use App\Models\AdminUser;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('admin:create')]
#[Description('Cria um usuário interno para acessar o painel administrativo')]
final class CreateAdminUserCommand extends Command
{
    public function handle(): int
    {
        $name = trim((string) $this->ask('Nome do administrador'));
        $email = mb_strtolower(trim((string) $this->ask('E-mail')));

        $validator = Validator::make(
            ['name' => $name, 'email' => $email],
            ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:admin_users,email']],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $password = (string) $this->secret('Senha');

        if ($password === '') {
            $this->error('A senha é obrigatória.');

            return self::FAILURE;
        }

        $confirmation = (string) $this->secret('Confirme a senha');

        if (! hash_equals($password, $confirmation)) {
            $this->error('As senhas não conferem.');

            return self::FAILURE;
        }

        $roles = array_map(static fn (AdminRole $role): string => $role->value, AdminRole::cases());
        $role = AdminRole::from($this->choice('Perfil administrativo', $roles, 0));

        AdminUser::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
        ]);

        $this->info("Administrador {$email} criado com sucesso.");

        return self::SUCCESS;
    }
}
