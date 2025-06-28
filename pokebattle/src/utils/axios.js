import axios from 'axios';

/* Instanciación de API con AXIOS*/
const API = axios.create({
  baseURL: '/api', //URL base de la API
  headers: {
    "Content-Type": "application/json"
  },
  withCredentials: true, // Si se usan cookies o tokens en headers debemos habilitar credenciales
});

export default API;