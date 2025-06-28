import { useState } from 'react'
import { Link } from 'react-router-dom';

/* Utilizamos react-icons para los íconos de la navbar */
import { FaHome, FaUserEdit, FaSignOutAlt, FaThList, FaUserPlus, FaSignInAlt } from 'react-icons/fa';

const NavBarComponent = ({ user }) => {
  const botonLogout = () => {
    localStorage.removeItem('usuario');
    localStorage.removeItem('nombre');
    localStorage.removeItem('usuarioId');
    localStorage.removeItem('token');
    window.location.reload(); // O actualizar estado
  };  

  return (
    <nav className="navbar">
      <ul className="navbar-links">
        {!user ? (
          <>
            <li><Link to= "/"><FaHome />Inicio</Link></li>
            <li><Link to="/register"><FaUserPlus />Registrarse</Link></li>
            <li><Link to="/login"><FaSignInAlt />Login</Link></li>
          </>
        ) : (
          <>
            <li><span>Hola, {user.nombre}</span></li>
            <li><Link to= "/"><FaHome /> Inicio</Link></li>
            <li><Link to="/mis-mazos"><FaThList />Mis mazos</Link></li>
            <li><Link to="/update"><FaUserEdit />Actualizar Usuario</Link></li>
            <li><button className="navbar-link-button" onClick={botonLogout}><FaSignOutAlt /> Logout </button></li>
          </>
        )}
      </ul>
    </nav>
  );
};

export default NavBarComponent;