/* Punto de entrada de la aplicacion que renderiza el componente raíz en el DOM */
import { useEffect, useState } from 'react';
import axios from 'axios'; //Framework para hacer peticiones HTTP
import { BrowserRouter, Routes, Route } from 'react-router-dom'; //Rutas
import { ToastContainer } from 'react-toastify'; //Notificaciones emergentes

//Estilos
import './assets/styles/style.css'

//Componentes
import HeaderComponent from './components/HeaderComponent'
import FooterComponent from './components/FooterComponent'
import NavBarComponent from './components/NavBarComponent';

//Páginas
import StatPage from './pages/stat/StatPage'
import RegistroPage from './pages/registro/RegistroPage';
import LoginPage from './pages/login/LoginPage';
import EditUserPage from './pages/user/EditUserPage';
import MazosPage from './pages/mazos/MazosPage'; // Si necesitas la página de mazos
import CreateMazosPage from './pages/mazos/CreateMazosPage';
import GamePage from './pages/game/gamePage';

const App = () => {
  const [user, setUser] = useState(null); //Utilizamos estados dinámicos para los valores de variables, base es NULL
  const [loading, setLoading] = useState(true); //Idem usuarios

  useEffect(() => {
    const usuarioNombre = localStorage.getItem('nombre'); //Obtenemos el nombre del usuario logueado desde localStorage

    if (!usuarioNombre) {
      console.log('No hay usuarios logueados')
      setUser(null)
      setLoading(false)
      return
    } else {
      console.log('Usuario logueado: ' + usuarioNombre)
      setUser({ nombre: usuarioNombre }) // NavBar reciba el nombre como objeto
      setLoading(false)
    }
  }, []); //Si no pongo [] se ejecuta en cada renderizado, si pongo [] se ejecuta una sola vez al inicio
  
  if (loading) return <p>...</p>;

  return (
    <BrowserRouter> {/* Envolvemos la APP con BrowserRouter para el manejo de rutas*/}
      <div className="app-container">
        <header>
          <HeaderComponent /> {/* Componente de encabezado */}
          <NavBarComponent user={user} /> {/* Componente de navegación, le pasamos el usuario logueado para saludo */}
        </header>

        <main className="main-content">
          <Routes>
            <Route path="/" element={<StatPage />} /> {/* INICIO */}
            <Route path="/register" element={<RegistroPage />} />
            <Route path="/login" element={<LoginPage setUser={setUser}/>} />
            <Route path="/update" element={<EditUserPage setUser={setUser} />} />
            <Route path="/mis-mazos" element={<MazosPage />}/>
            <Route path="/crear-mazo" element={<CreateMazosPage />} />
            <Route path="/jugar/:idMazo" element={<GamePage />} />
            {/* Se agregán más más rutas si existen otras páginas */}
          </Routes>
        </main>
        
        <ToastContainer position="top-right" autoClose={3000} /> {/* Componente para notificaciones emergentes */}
        
        <footer>
          <FooterComponent />
        </footer>
      </div>
    </BrowserRouter>
  );
};

export default App
