import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

//Api
import API from "../../utils/axios";

const EditUserPage = ({ setUser }) => { //Recibe prop para actualizar el nombre en el contexto global
  const [nombre, setNombre] = useState('');
  const [password, setPassword] = useState('');
  const [repetirPassword, setRepetirPassword] = useState('');
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault(); // Prevenir el comportamiento por defecto del formulario

    if (!nombre || !password || !repetirPassword) {
      toast.error('Todos los campos son obligatorios.');
      return;
    }

    if (password !== repetirPassword) {
      toast.error('Las contraseñas no coinciden.');
      return;
    }

    // const usuarioId = localStorage.getItem('usuarioId');
    const token = localStorage.getItem('token');
    const userId = localStorage.getItem('usuarioId'); //ID usuario logueado

    try {
      const response = await API.put(`/usuarios/${userId}`, { //Se toma el ID del usuario logueado en localStorage
        nombre,
        password
      }, {
        headers: {
          Authorization: `Bearer ${token}` //Token del localstorage
        }
      });
      // Si el update fue exitoso
      if (response.status === 200) {
        toast.success(response.data.message); // Mensaje del backend

        // Limpiar los campos del formulario
        setNombre('');
        setPassword('');
        setRepetirPassword('');

        localStorage.setItem('nombre', nombre); // Actualizar localStorage
        setUser({ nombre: nombre });// Actualizar contexto global para el navbar
        navigate('/');// Redirigir al inicio
      }  
    } catch (err) {
      const backendError = err.response?.data?.error;
      toast.error(backendError);
      // setMensaje(null); 
    }
  };

  return (
    <div className='form-container'> 
      <h1>Editar Usuario '{localStorage.getItem('usuario')}'</h1>
      <form onSubmit={handleSubmit}>
        <div className= 'form-container-div'>
          <label>Nuevo nombre</label><br />
          <input
            type="text"
            value={nombre}
            onChange={(e) => setNombre(e.target.value)}
          />
        </div>

        <div className='form-container-div'>
          <label>Nueva contraseña</label><br />
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
          />
        </div>

        <div className='form-container-div'>
          <label>Repetir contraseña</label><br />
          <input
            type="password"
            value={repetirPassword}
            onChange={(e) => setRepetirPassword(e.target.value)}
            required
          />
        </div>

        <button className="pokemon-button" type="submit">
          Actualizar
        </button>
      </form>
    </div>
  );
};

export default EditUserPage;