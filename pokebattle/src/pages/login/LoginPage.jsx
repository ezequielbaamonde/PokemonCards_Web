import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';
import API from "../../utils/axios";
import { toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

const LoginPage = ({ setUser }) => {
  const [usuario, setUsuario] = useState('');
  const [password, setPassword] = useState('');
  const navigate = useNavigate(); //Redirige al usuario a otra página después del login exitoso.

  const handleSubmit = async (e) => {
    e.preventDefault();

    try {
      const response = await API.post('/login', {
        usuario,
        password
      });

      const { token, id_usuario, nombre } = response.data;
      
      // Guardar el token y el usuario, esto sirve para luego desloguear
      localStorage.setItem('token', token);
      localStorage.setItem('usuario', usuario); // <---- Guarda el nombre del usuario
      localStorage.setItem('nombre', nombre); // <---- Guarda el nombre del usuario
      localStorage.setItem('usuarioId', id_usuario);

      // Actualizar el estado global del usuario
      setUser({ nombre: nombre }); // <---- Un objeto con 'nombre'
      toast.success(response.data.message); // Mensaje de éxito
      navigate('/'); // Redirige al inicio de manera exitosa

    } catch (err) {
      console.error('Error al iniciar sesión:', err);

      // Detalle más específico del error
      let msg = 'Error desconocido al iniciar sesión';
      if (err.response) {
        // El servidor respondió con un código fuera del rango 2xx
        msg = `Error ${err.response.status}: ${err.response.data?.error || err.response.statusText}`;
      } else if (err.request) {
        // La solicitud fue hecha pero no hubo respuesta
        msg = 'No se recibió respuesta del servidor. Verifique su conexión.';
      } else if (err.message) {
        // Algo pasó al configurar la solicitud
        msg = `Error al configurar la solicitud: ${err.message}`;
      }
      toast.error(msg);
    }
  };

  return (
    <div className='form-container'>
      <h1>Iniciar Sesión</h1>
      <form onSubmit={handleSubmit}>
        <div className= 'form-container-div'>
          <label>Usuario:</label><br />
          <input
            type="text"
            value={usuario}
            onChange={(e) => setUsuario(e.target.value)}
            required
          />
        </div>

        <div className= 'form-container-div'>
          <label>Contraseña:</label><br />
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
          />
        </div>
        
        <button className="pokemon-button" type="submit">
          Ingresar
        </button>
        

      </form>
    </div>
  );
};

export default LoginPage;