import { useEffect, useState, useRef } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import API from '../../utils/axios';
import { toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

const GamePage = () => {
  //Estados iniciales
  const [cartasJugador, setCartasJugador] = useState([]);
  const [cartasServidor, setCartasServidor] = useState([]);
  const [resultadoJugada, setResultadoJugada] = useState(null);
  const [idPartida, setIdPartida] = useState(null);
  const [loading, setLoading] = useState(true);
  const [indexCartaEliminando, setIndexCartaEliminando] = useState(null);
  const { idMazo } = useParams();

  const navigate = useNavigate();
  const token = localStorage.getItem('token');
  
  //Cargamos la partida al iniciar el componente
  useEffect(() => {
    //Reproducir sonido al INICIAR partida
    const sonido = new Audio('/Sounds/Inicio.mp3');
      sonido.play().catch(e => {
      console.warn('No se pudo reproducir el sonido automáticamente:', e);
    });

    toast.info('Cargando partida...');
    //Válidamos si hay paritda en curso.
    (async () => {
      const retomada = await verificarPartidaEnCurso();
      if (!retomada) await crearPartida();
    })();
  }, []);

  const obtenerAtributosServidor = async (idPartidaReal, cantCartas) => {
    //El SV siempre tiene ID 1
    try {
      const res = await API.get(`/usuarios/1/partidas/${idPartidaReal}/cartas`,{
        headers: {
          Authorization: `Bearer ${token}`
        }
      });

      const atributos = res.data.atributos || [];
      // Simulamos la cantidad de cartas que tenga el usuario para el servidor con los atributos, para "la visual"
      const cartasFake = Array.from({ length: cantCartas }, (_, i) => ({
        id: i + 1,
        nombre: atributos[i % atributos.length]?.nombre || 'Desconocido',
      }));

      setCartasServidor(cartasFake);
    } catch (err) {
      console.error('Error obteniendo atributos del servidor:', err);
      toast.error('No se pudieron cargar las cartas del servidor');
    }
  };

  const verificarPartidaEnCurso = async () => {
    try {
      const res = await API.get('/partidas/en-curso', {
        headers: { Authorization: `Bearer ${token}` }
      });
      
       //Encontró partida
      if (res.status === 200) {
        const cartas = res.data.cartas; //usá la cantidad real que ya se tiene sin que sea inmediato
        toast.info(res.data.message); //Devuelve mensaje
        setIdPartida(res.data.id_partida); //Establecemos id_partida
        setCartasJugador(cartas); //Seteamos las cartas restantes del jugador
        await obtenerAtributosServidor(res.data.id_partida, cartas.length); //Obtenemos atributos del SV y cartas fakes
        setLoading(false);
        return true;
      }
      return false;
    } catch (err) {
      // 204 = No content (sin partida en curso)
      if (err.response?.status !== 204) {
        toast.error('Error consultando partida en curso');
      }
      return false;
    }
  };  

  const crearPartida = async () => {
    console.log('ID del mazo recibido:', idMazo);
    // console.log('Token:', token);
    try {
      const res = await API.post('/partidas',
        { id_mazo: idMazo },
        {
          headers: { Authorization: `Bearer ${token}` }
        }
      );
      console.log('Respuesta del servidor:', res.data);

      const cartas = res.data.cartas || []; //usá la cantidad real que ya se tiene sin que sea inmediato
      setIdPartida(res.data.id_partida); //Seteamos idPartida creada
      setCartasJugador(cartas);

      // Obtener atributos reales del servidor
      await obtenerAtributosServidor(res.data.id_partida, cartas.length);

      toast.success(res.data?.message || 'Partida creada exitosamente');
    } catch (err) {
      console.error('Error creando partida:', err);
      toast.error(err.response?.data?.error || 'No se pudo crear la partida');
      navigate('/mis-mazos');
    } finally {
      setLoading(false);
    }
  };

  const jugarCarta = async (idCarta) => {
    if (!idPartida) {
      toast.error('La partida aún no fue cargada completamente.');
      return;
    } //Si no existe partida

    //Reproducir sonido al jugar carta
    const sonido = new Audio('/Sounds/Jugada.mp3');
      sonido.play().catch(e => {
      console.warn('No se pudo reproducir el sonido:', e);
    });

    try {
      const res = await API.post(
        '/jugadas',
        {
          id_partida: idPartida,
          id_carta: idCarta,
        },
        {
          headers: { Authorization: `Bearer ${token}` },
        }
      );

      setCartasJugador((prev) => prev.filter((c) => c.id !== idCarta)); //Corrobora estado previo

      const indexAEliminar = Math.floor(Math.random() * cartasServidor.length); // Elimina una carta fake aleatoria del sv
      setIndexCartaEliminando(indexAEliminar);

      setTimeout(() => {
        setCartasServidor((prev) => {
          const nuevas = [...prev];
          nuevas.splice(indexAEliminar, 1);
          return nuevas;
        });
        setIndexCartaEliminando(null);
      }, 400);

      setResultadoJugada(res.data);
      //Si hay resultado final, reproducir sonido correspondiente
      if (res.data.resultado_final) {
        let sonidoFinal;

        if (res.data.resultado_final.includes('Usuario ganó')) {
          sonidoFinal = new Audio('/Sounds/victoria.mp3');
        } else if (res.data.resultado_final.includes('Servidor ganó')) {
          sonidoFinal = new Audio('/Sounds/derrota.mp3');
        } else if (res.data.resultado_final.includes('empate')) {
          sonidoFinal = new Audio('/Sounds/empate.mp3');
        }
      
        if (sonidoFinal) {
          sonidoFinal.play().catch(e => {
            console.warn('No se pudo reproducir el sonido final:', e);
          });
        }
      }
    } catch (err) {
      console.error('Error al jugar carta:', err);
      toast.error(err.response?.data?.error || 'Error al jugar carta');
    }
  };

  const reiniciarPartida = async () => {
    setLoading(true);
    setResultadoJugada(null);
    setCartasServidor([]);
    setCartasJugador([]);
    await crearPartida();
  };
  /* 
    toast.info esta modifica el estado global de React mientras el componente se está renderizando.
    React detecta eso como un side effect mal ubicado, porque los side effects (como mostrar toasts, hacer fetch, etc.)
    deben estar dentro de useEffect, no en el cuerpo principal del componente.
  */
  if (loading) return <p>Cargando partida...</p>;

  return (
    <div className="game-page">
      <div className="tablero-juego">
        <h2>Partida en curso...</h2>
        
        {/* Cartas del servidor */}
        <div className="cartas-servidor">
          <h3>Cartas del servidor</h3>
          {cartasServidor.map((carta, i) => (
            <div
              key={i}
              className={`carta-servidor ${indexCartaEliminando === i ? 'eliminando' : ''}`}
            >
              <img src="/Cards/default.png" alt="Carta oculta" />
              <p>{carta.nombre}</p> {/* Nombre atributp */}
            </div>
          ))}
        </div>

        <hr className="separador" /> {/* Línea divisoria */}

        {/* Centro del tablero */}
        {resultadoJugada && (
          <div className="zona-jugada">
            <div>
              <h4>Carta Servidor</h4>
              <p>Fuerza: {resultadoJugada.fuerza_servidor}</p>
            </div>

            {resultadoJugada.resultado_final && (
              resultadoJugada.resultado_final === 'Usuario ganó la partida'
                ? <h4>{localStorage.getItem('usuario') + ' GANÓ LA PARTIDA'}</h4>
                : <h4>{resultadoJugada.resultado_final}</h4>
            )}

            <div>
              <h4>Tu carta</h4>
              <p>Fuerza: {resultadoJugada.fuerza_usuario}</p>
            </div>
          </div>
        )}

        <hr className="separador" /> {/* Línea divisoria */}

        {/* Cartas del jugador */}
        <div className="cartas-usuario">
          <h3>Tu mano</h3>
          {cartasJugador.map((carta) => (
            <div
              key={carta.id}
              className="carta-jugador"
              onDoubleClick={() => jugarCarta(carta.id)}
            >
              <img src={`/Cards/${carta.id}.png`} alt={carta.nombre} />
              <p>{carta.nombre}</p>
              <p>{carta.atributo}</p>
              <p>
                Ataque: {carta.ataque} ({carta.ataque_nombre})
              </p>
            </div>
          ))}
        </div>

        {/* Jugar otra vez */}
        {cartasJugador.length === 0 && (
          <button className="gameBack" onClick= {reiniciarPartida}>
            Jugar otra vez
          </button>
        )}
        {/* Volver */}
        {cartasJugador.length === 0 && (
          <button className="gameBack" onClick= {() => navigate('/mis-mazos')}>
            Volver
          </button>
        )}

      </div>

    </div>

  );
};

export default GamePage;
