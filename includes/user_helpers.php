<?php
// Funções utilitárias para usuários: validação de CPF e upload/thumbnail de foto de perfil

function validate_cpf(string $cpf): bool
{
    // Remove caracteres não numéricos
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11)
        return false;
    // sequências repetidas inválidas
    if (preg_match('/^(\d)\1{10}$/', $cpf))
        return false;

    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d)
            return false;
    }
    return true;
}

// Note: profile photo upload/thumbnail helpers removed — profile photos are no longer supported.

// CSRF helpers
function generate_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE)
        session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE)
        session_start();
    if (empty($token) || empty($_SESSION['csrf_token']))
        return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generate_csrf_token()) . '">';
}

?>