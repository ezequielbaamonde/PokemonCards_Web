import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import API from '../../utils/axios';
import { toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

const CreateMazosPage = () => {
  //Estados
  const [nombre, setNombre] = useState('');
  const [cartas, setCartas] = useState([]);
  const [cartasDisponibles, setCartasDisponibles] = useState([]);
  const [imagenAmpliada, setImagenAmpliada] = useState(null);
  const [filtroAtributo, setFiltroAtributo] = useState('');
  const [filtroNombre, setFiltroNombre] = useState('');

  //Hooks
  const navigate = useNavigate();
  //Datos
  const token = localStorage.getItem('token');
  const usuario = localStorage.getItem('usuario');

  useEffect(() => {
    obtenerCartas();
  }, [filtroAtributo, filtroNombre]); //Dependencias para que se actualice instanteneamente al cambiar los filtros

  const obtenerCartas = async () => {
    try {
      const res = await API.get('/cartas', {
        headers: { Authorization: `Bearer ${token}` },
        params: {
          atributo: filtroAtributo,
          nombre: filtroNombre
        }
      });
      setCartasDisponibles(res.data);
    } catch (err) {
      toast.error('Error al obtener las cartas');
    }
  };

  const limpiarFiltros = () => {
    setFiltroAtributo('');
    setFiltroNombre('');
    // obtenerCartas();
  };
   
  /*Seleccionador de cartas
    Se maneja el cambio de estado de las cartas seleccionadas*/
  const handleCheckboxChange = (id) => {
    setCartas((prev) => { //Prev es es simplemente el valor anterior del estado cartas antes de la actualización.

      if (prev.includes(id)) { // Verifica si la carta ya está seleccionada
        const sonido = new Audio('/Sounds/de-select.mp3');
        sonido.play().catch(err => console.warn('No se pudo reproducir sonido:', err));

        return prev.filter((c) => c !== id); // Elimina la carta si ya está seleccionada
      } else if (prev.length < 5) { //Si el array tiene menos de 5 cartas
        const sonido = new Audio('/Sounds/select.mp3');
        sonido.play().catch(err => console.warn('No se pudo reproducir el sonido:', err));

        return [...prev, id];
      } else {
        toast.warn('Solo se puede seleccionar 5 cartas');
        const sonido = new Audio('/Sounds/error.mp3');
        sonido.play().catch(err => console.warn('No se pudo reproducir sonido:', err));

        return prev;
      }
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!nombre.trim()) {
      toast.error('El nombre del mazo no puede estar vacío');
      const sonido = new Audio('/Sounds/error.mp3');
      sonido.play().catch(err => console.warn('No se pudo reproducir sonido:', err));
      return;
    }

    if (cartas.length !== 5) {
      toast.error('Se debe seleccionar exactamente 5 cartas');
      const sonido = new Audio('/Sounds/error.mp3');
      sonido.play().catch(err => console.warn('No se pudo reproducir sonido:', err));
      return;
    }

    try {
      await API.post(
        '/mazos',
        { nombre: nombre.trim(), cartas },
        { headers: { Authorization: `Bearer ${token}` } }
      );
      toast.success('Mazo creado exitosamente');
      
      const sonido = new Audio('/Sounds/confirm.mp3');
      sonido.play().catch(err => console.warn('No se pudo reproducir sonido:', err));  

      navigate('/mis-mazos');
    } catch (err) {
      const msg = err.response?.data?.error || 'Error al crear el mazo';
      toast.error(msg);
    }
  };

  return (
    <div className="create-mazo-container">
      {/* <h2>Crear nuevo mazo</h2> */}
      <form onSubmit={handleSubmit} className="create-mazo-form">
        <label>
          Nombre del mazo:
          <input
            type="text"
            value={nombre}
            onChange={(e) => setNombre(e.target.value)}
            className="input-nombre"
            placeholder='Máximo 20 caracteres'
          />
        </label>
        

        <div className="filtros-container">
          <input
            type="text"
            placeholder="Buscar por nombre"
            value={filtroNombre}
            onChange={(e) => setFiltroNombre(e.target.value)}
          />
          <input
            type="text"
            placeholder="Buscar por atributo"
            value={filtroAtributo}
            onChange={(e) => setFiltroAtributo(e.target.value)}
          />
          <button type="button" onClick={limpiarFiltros}>
            Limpiar filtros
          </button>
        </div>

        <div className="cartas-grid">
          {cartasDisponibles.map((carta) => ( // Aquí mapeamos las cartas disponibles, "carta" es el objeto de cada carta
            <div
              key={carta.id}
              className={`carta-card ${
                cartas.includes(carta.id) ? 'seleccionada' : ''
              }`}
              onClick={() => handleCheckboxChange(carta.id)}
            >
              <img
               src={`/Cards/${carta.id}.png`} //Uso backticks ( ` ), para cadenas con variables o expresiones embebidas
               alt={carta.nombre}
               onClick={() => setImagenAmpliada(`/Cards/${carta.id}.png`)}
               onError={(e) => {
                 e.target.onerror = null;
                 e.target.src = '/Cards/default.png';
               }}
              />
              
              <h4>{carta.nombre}</h4>
              <p>Atributo: {carta.atributo}</p>
              <p>Ataque: {carta.ataque}<br/>({carta.ataque_nombre})</p>
              <input
                type="checkbox"
                checked={cartas.includes(carta.id)}
                onChange={() => handleCheckboxChange(carta.id)}
              />
            </div>
          ))}

          {/* Mostramos las carta ampliada */}
          {/* Falta realizar dorso default */}
          {imagenAmpliada && (
            <div className="modal-overlay" onClick={() => setImagenAmpliada(null)}>
              <div className="modal-content" onClick={(e) => e.stopPropagation()}> {/*Evita que el clic en la imagen cierre el modal*/}
                <img src={imagenAmpliada} alt="Carta ampliada" />
                <button onClick={() => setImagenAmpliada(null)}>Cerrar</button>
              </div>
            </div>
          )}
        </div>

        <button type="submit"
          className="pokemon-button">
          Crear mazo
        </button>
        <button type="button"
          className="pokemon-button-return"
          onClick={() => navigate('/mis-mazos')}>
          Volver
        </button>

      </form>
    </div>
  );
};

export default CreateMazosPage