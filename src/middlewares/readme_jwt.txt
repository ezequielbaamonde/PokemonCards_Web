Explicación:
1. Intenta decodificar un token JWT.
2. Si el token es válido, lo agrega al objeto $request y permite que el flujo de la aplicación continúe.
3. Si el token es inválido o hay un error, devuelve una respuesta HTTP con un mensaje de error y un código de estado 401.