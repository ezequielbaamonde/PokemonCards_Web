import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import API from '../../utils/axios';
import { toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

const MazosPage = () => {
  const [mazos, setMazos] = useState([]);
  const [mazoSeleccionado, setMazoSeleccionado] = useState(null);
  const [mazoEditandoId, setMazoEditandoId] = useState(null);
  const [nuevoNombre, setNuevoNombre] = useState('');
  const [cartasDelMazo, setCartasDelMazo] = useState([]);
  
  const navigate = useNavigate();

  const usuario = localStorage.getItem('usuario');
  const token = localStorage.getItem('token');

  useEffect(() => {
    obtenerMazos();
  }, []);

  const obtenerMazos = async () => {
    try {
      const res = await API.get(`/usuarios/${usuario}/mazos`, {
        headers: {
          Authorization: `Bearer ${token}`
        }
      });
      setMazos(res.data.mazos || []);
      if (res.data.mazos?.length >= 3) {
        toast.info('Ya tienes 3 mazos creados. Elimina uno para poder crear otro.');
      }
    } catch (error) {
      const mensaje = error.response?.data?.mensaje || 'Error al cargar los mazos.';
      toast.error(mensaje);
    }
  };

  const eliminarMazo = async (mazoId) => {
    try {
      await API.delete(`/mazos/${mazoId}`, {
        headers: { Authorization: `Bearer ${token}` }
      });
      toast.success('Mazo eliminado correctamente');
      obtenerMazos();
    } catch (err) {
      toast.error(err.response?.data?.error || 'No se pudo eliminar el mazo');
    }
  };

  const guardarNuevoNombre = async (id) => {
    const nombreTrim = nuevoNombre.trim(); // Elimina espacios al inicio y al final
  
    if (nombreTrim === '') {
      toast.error('El nombre del mazo no puede estar vacío.');
      return;
    }
  
    try {
      const response = await API.put(`/mazos/${id}`,
        { nombre: nombreTrim },
        {
          headers: {
            Authorization: `Bearer ${token}`
          }
        }
      );
  
      toast.success(response.data.message || 'Nombre actualizado');
      setMazoEditandoId(null);
      setNuevoNombre('');
      obtenerMazos(); // Actualiza la lista
    } catch (err) {
      const msg = err.response?.data?.error || 'Error al actualizar el nombre del mazo';
      toast.error(msg);
    }
  };

  const verCartasDelMazo = async (mazo) => {
  try {
      const res = await API.get(`/mazos/${mazo.id}/cartas`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      setCartasDelMazo(res.data);
      setMazoSeleccionado(mazo);
    } catch (err) {
      toast.error('Error al obtener las cartas del mazo');
    }
  };


  return (
    <div className="mazos-container">
      <h2>Mazos de {localStorage.getItem('nombre')}</h2>
      
      {/* Si no tiene mazos */}
      {mazos.length === 0 && (
        <div className="mazo-card sin-mazos">
          <h3 className="mazo-nombre">NO TIENES MAZOS</h3>
          <p style={{ color: '#ccc' }}>Crea un nuevo mazo para comenzar a jugar.</p>
        </div>
      )}

      {/* Obtengo mazos */}
      {mazos.map((mazo) => (
        <div key={mazo.id} className="mazo-card">
          {mazoEditandoId === mazo.id ? (
            <div className="mazo-edicion">
              <input
                type="text"
                value={nuevoNombre}
                onChange={(e) => setNuevoNombre(e.target.value)}
              />
              <button onClick={() => guardarNuevoNombre(mazo.id)}>Guardar</button>
              <button onClick={() => setMazoEditandoId(null)}>Cancelar</button>
            </div>
          ) : (
            <h3 className="mazo-nombre">{mazo.nombre}</h3>
          )}

          <div className="mazo-botones">
            <button onClick={() => verCartasDelMazo(mazo)}>Ver Mazo</button>
            <button onClick={() => eliminarMazo(mazo.id)}>Eliminar</button>
            <button onClick={() => {
              setMazoEditandoId(mazo.id);
              setNuevoNombre(mazo.nombre);
            }}>
              Editar
            </button>
            <button onClick={() => navigate(`/jugar/${mazo.id}`)}>Jugar</button>
          </div>
        </div>
      ))}

      <button
        onClick={() => navigate('/crear-mazo')}
        disabled={mazos.length >= 3}
        className="pokemon-button"
      >
        Crear Mazo
      </button>

      {/* Si mazoSeleccionado no es null o undefined, entonces mostrar el modal */}
      {mazoSeleccionado && (
        <div className="modal-overlay" onClick={() => { 
          setMazoSeleccionado(null);
          setCartasDelMazo([]);
        }}>
          <div className="modal-cartas" onClick={(e) => e.stopPropagation()}>
            <h3>{mazoSeleccionado.nombre}</h3>
      
            {cartasDelMazo.length > 0 ? (
              <div className="cartas-grid">
                {cartasDelMazo.map((carta) => (
                  <div key={carta.id} className="carta-card">
                    <img
                      src={`/Cards/${carta.id}.png`}
                      alt={carta.nombre}
                      onError={(e) => {
                        e.target.onerror = null;
                        e.target.src = '/Cards/default.png';
                      }}
                    />
                    <h4>{carta.nombre}</h4>
                    <p>Atributo: {carta.atributo}</p>
                    <p>Ataque: {carta.ataque} ({carta.ataque_nombre})</p>
                  </div>
                ))}
              </div>
            ) : (
              <p style={{ color: 'white' }}>Este mazo no tiene cartas.</p>
            )}

            <button onClick={() => {
              setMazoSeleccionado(null);
              setCartasDelMazo([]);
            }}>
              Cerrar
            </button>
          </div>
        </div>
      )}
    </div>
  );
};

export default MazosPage;