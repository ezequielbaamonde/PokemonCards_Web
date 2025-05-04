<?PHP
function validateUsername(string $username): ?string {
    if (!preg_match('/^[a-zA-Z0-9]{6,20}$/', $username)) {
        return 'El nombre de usuario debe tener entre 6 y 20 caracteres alfanuméricos.';
    }
    return null;
}

function validatePassword(string $password): ?string {
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        return 'La clave debe tener al menos 8 caracteres, incluyendo mayúsculas, minúsculas, números y caracteres especiales.';
    }
    return null;
}

function validateName(string $name): ?string {
    if (strlen($name) < 2 || strlen($name) > 20) {
        return 'El nombre debe tener entre 1 y 20 caracteres.';
    }
    return null;
}