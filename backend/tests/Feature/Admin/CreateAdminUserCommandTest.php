<?php

use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;

it('creates an administrator with a confirmed password shorter than twelve characters', function (): void {
    $this->artisan('admin:create')
        ->expectsQuestion('Nome do administrador', 'Admin Example')
        ->expectsQuestion('E-mail', 'admin@example.com')
        ->expectsQuestion('Senha', 'short')
        ->expectsQuestion('Confirme a senha', 'short')
        ->expectsChoice('Perfil administrativo', 'super_admin', ['super_admin', 'tenant_manager', 'mcp_manager'])
        ->assertSuccessful();

    $adminUser = AdminUser::query()->where('email', 'admin@example.com')->firstOrFail();

    expect(Hash::check('short', $adminUser->password))->toBeTrue();
});
