# Documentación:
 - Toda la información acerca del desarrollo del proyecto se encuentra en este DOC.

## Configuraciones.
> Al archivo vite.config.js le seteamos por defecto el origen del backend con "/API" como proxi. En la instancia de AXIOS.CREATE la urlBase es /API. Esta se utiliza para los llamados al endpoint
    Ejemplo: API.POST(....)
             API.GET
             etc...
 > Permite un código más limpio y reutilizable

## Modificaciones en Back-End.
> Se añadió las políticas CORS para las respuestas por parte del servidor a solicitudes HTTP del cliente. Estas fueron añadidas en el index.php del backend del proyecto mediante HEADERS. Además, en cada consumo de la API se aclara la instrucción "withCredentials: true" que es esencial para que CORS permita enviar cookies o headers sensibles como el JWT Token.

> Se modificó consulta SQL en endpoint de /ESTADISTICA. Colocamos que devuelva el ID del usuario porque en React se necesita una clave (key) única y estable para cada elemento renderizado en bucles (.map()).
 > Sin el id: React usaría índices como key, lo cual rompe buenas prácticas y puede causar bugs al actualizar listas dinámicas

> Se modificó la respueta JSON del endpoint /login. Colocamos que devuelva el ID del usuario para ser utilizado en otros componentes PUT o GET solicitados.

> Se modificó el archivo "validateData" dentro del directorio HELPERS del backend en lo que es la validación del nombre del usuario, coloqué que el mínimo de caracteres sea 1 y el máximo 30 (Aclarado en enunciado de /registro con react)

> Se añadió una conidición al endpoint PUT y POST de /mazos para que el nombre del mazo no sea mayor a 20 caracterés

> Se añadió un nuevo endpoint en cardsController "/mazos/{id}/cartas" el cuál devuelve las cartas de un mazo que se le pasa el ID como parametro. Este endpoint se generó para crear el botón "Ver" para los mazos creados por un usuario. Tiene seguridad JWT para que el mazó a visualizar sea el del usuario logueado.

> Se añadió al endpoint /jugadas una consulta SQL que seteé a "en_mazo" las cartas del usuario tras finalizar una partida (5 jugadas). Esto surgió debido a que cuando se finalizaba una partida y el usuario queria volver a ver su mazo no podia porque las cartas estaban descartadas.

> Se modificó el endpoint "'/usuarios/{usuario}/partidas/{partida}/cartas'" para que se obtenga los atributos tanto del usuario que inicializo la partida como la del SERVIDOR (El cual no inicializa la partida), manteniendo la seguridad JWT.

> Se modificó el endpoint "/partidas" para que se valide si un usuario o el servidor ya cuentan con una partida en curso. En caso de ser así se retorna un error.

> Se creo el endpoint "/partidas/en-curso" para válidar si el usuario logueado cuenta con una partida en curso y en caso de ser así retornar el id_partida, el mazo_id, las cartas en juego y un mensaje. En caso de que no, retorna un error similar al endpoint de "/partidas".

## Notaciones de Código.
> Agregando el usuariosPagina.length > 0 ? ... : ..., en el StatPage, se evita que el <tbody> contenga nada que no sea un <tr> válido.

> El HOOK "useMemo" ayuda a optimizar el rendimiento evitando cálculos repetidos cuando los datos relevantes no han cambiado.

> A la web se le importó fuentes mediante "fonts.googleapis" para encabezados y párrafos. Puede suceder (Desconozco exactamente el mótivo) que en alguna otra PC no se reflejen, lo he probado al clonar el repositorio del proyecto desde github.

## Librerias Instaladas.
> Se instaló la libreria "react-router-dom" con "npm install react-router-dom" en la raíz del proyeto para linkear componentes mediante <a Link to...>.
 > Usar solo <a href=""> recarga la página completa, y eso rompe el flujo de una SPA (Single Page App) como React. En cambio, Link de React Router actualiza la URL y renderiza solo el componente correspondiente sin recargar.

> Se instaló "toastify" mediante "npm install react-toastify", libreria de ventanas emergentes. Lo utilizan endpoints como:
 > Login
 > Registro
 > Update