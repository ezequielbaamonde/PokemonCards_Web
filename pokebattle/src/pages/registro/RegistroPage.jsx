import React, { useState } from "react";
import axios from "axios";
import { toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

//Api
import API from "../../utils/axios";

// Componente de registro de usuario
function RegistroPage() {
  // Estado para los datos del formulario
  const [formData, setFormData] = useState({
    usuario: "",
    nombre: "",
    password: "",
  });

  // Maneja los cambios en los campos del formulario
  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  // Maneja el envío del formulario
  const handleSubmit = async (e) => {
    e.preventDefault();

    try {
      // Envía los datos al backend para registrar el usuario
      const response = await API.post("/registro", formData);

      // Si el registro fue exitoso
      if (response.status === 201) {
        toast.success(response.data.message);
        setFormData({ usuario: "", nombre: "", password: "" }); // Limpia el formulario
      }
    } catch (err) {
      // Si hay un error del backend, muestra el mensaje correspondiente
      if (err.response && err.response.data && err.response.data.error) {
        toast.error(err.response.data.error);
      } else {
        toast.error("Error inesperado. Intenta más tarde.");
      }
    }
  };

  return (
    <div className='form-container'>
      <h1>Registro de Usuario</h1>
      <form onSubmit={handleSubmit}>
        <div className= 'form-container-div'>
          <label>Usuario (único):</label><br />
          <input
            type="text"
            name="usuario"
            value={formData.usuario}
            onChange={handleChange}
            required
          />
        </div>

        <div className= 'form-container-div'>
          <label>Nombre:</label><br />
          <input
            type="text"
            name="nombre"
            value={formData.nombre}
            onChange={handleChange}
            required
          />
        </div>

        <div className= 'form-container-div'>
          <label>Contraseña:</label><br />
          <input
            type="password"
            name="password"
            value={formData.password}
            onChange={handleChange}
            required
          />
        </div>

        <button className="pokemon-button" type="submit">
          Registrase
        </button>
      </form>
    </div>
  );
}

export default RegistroPage;