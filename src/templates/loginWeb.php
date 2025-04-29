<!DOCTYPE html>
<html lang="en-es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Pokemon Cards Web - Login</title>
    <!-- METAS -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto|Varela+Round">
    <link rel="stylesheet"href="https://fonts.googleapis.com/css?family=Tangerine">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../styles/loginStyle.css">
    <!-- ICONO -->
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">

    <!-- SCRIPTS -->
    <!-- NECESITA INTERNET -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
</head>

<body>
    <div class="text-center">
        <!-- BOTÓN -->
        <a href="#myModal" class="trigger-btn" data-toggle="modal">Ingresar Usuario</a>
    </div>

    <!-- MODAL HTML -->
    <div id="myModal" class="modal fade">
        <div class="modal-dialog modal-login">
            <div class="modal-content">
                <!-- HEADER -->
                <div class="modal-header">
                    <div class="avatar">
                        <img src="../img/favicon.ico" alt="Avatar">
                    </div>				
                    <h4 class="modal-title"><strong>LOGIN</strong></h4>	
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>

                <!-- FORMULARIO -->
                <div class="modal-body">
                    <form method="POST" action="../config/loginValidacion.php">
                        <div class="form-group">
                            <input type="text" id="user" class="form-control" name="username" placeholder="Usuario" required="required">		
                        </div>
                        <div class="form-group">
                            <input type="password" id="password" class="form-control" name="password" placeholder="Contraseña" required="required">	
                        </div>

                        <!-- SUBMIT -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg btn-block login-btn">Aceptar</button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <a href="#">Crear Cuenta</a>
                </div>
            </div>
        </div>
    </div>     
</body>
</html>