<?php
function validateUsername(string $username): ?string {
    if (strlen($username) < 6 || strlen($username) > 20) {
        return 'El nombre de usuario debe tener entre 6 y 20 caracteres.';
    }

    // Verifica si $username contiene solo caracteres alfanuméricos (letras y números)
    if (!ctype_alnum($username)) {
        foreach (str_split($username) as $char) {
            if (!ctype_alnum($char)) {
                return "Carácter inválido en nombre de usuario: '$char'. Solo se permiten caracteres alfanuméricos.";
            }
        }
    }

    // No puede ser solo números
    if (ctype_digit($username)) {
        return 'El nombre de usuario no puede estar compuesto solo por números.';
    }

    return null;
}

function validatePassword(string $password): ?string {
    if (strlen($password) < 8) {
        return 'La clave debe tener al menos 8 caracteres.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'La clave debe contener al menos una letra mayúscula.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'La clave debe contener al menos una letra minúscula.';
    }
    if (!preg_match('/\d/', $password)) {
        return 'La clave debe contener al menos un número.';
    }
    if (!preg_match('/[\W_]/', $password)) {
        return 'La clave debe contener al menos un carácter especial.';
    }
    return null;
}

function validateName(string $name): ?string {
    if (strlen($name) < 1 || strlen($name) > 30) {
        return 'El nombre debe tener entre 1 y 30 caracteres.';
    }
    if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u', $name)) {
        foreach (preg_split('//u', $name, -1, PREG_SPLIT_NO_EMPTY) as $char) {
            if (!preg_match('/[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/u', $char)) {
                return "Carácter inválido en el nombre: '$char'.";
            }
        }
    }
    return null;
}