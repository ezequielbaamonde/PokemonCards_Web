import React, { useState, useEffect, useMemo } from "react";
import axios from "axios";
import API from "../../utils/axios"

function calcularPromedio(ganadas, total) {
    return total === 0 ? 0 : (ganadas / total) * 100; /*Si total es 0, devuelve 0 para evitar una división por cero.
                                                      Si no, divide ganadas entre total y multiplica el resultado por 100,
                                                      obteniendo así el porcentaje de partidas ganadas.*/
}

function StatPage() {
    const [orden, setOrden] = useState("mejor");
    const [pagina, setPagina] = useState(1);
    const [usuarios, setUsuarios] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const porPagina = 5;

    useEffect(() => {
        setLoading(true);
        API.get("/estadistica")
            .then(res => {
                console.log("res.data:", res.data);
                const adaptados = res.data.map(u => { /*res.data es el array y la funcion MAP recorre el arreglo. 
                    u toma cada elemento, y ADAPTADOS es el resultado de la función aplicada*/
                    return {
                        ...u,
                        total: u.total_partidas,
                        promedio: calcularPromedio(u.ganadas, u.total_partidas)
                    };
                });
                setUsuarios(adaptados); //Setea la lista de usuarios de lo que devolvió la API
                setError(null);
            })
            .catch(err => {
                console.error("Error al cargar estadísticas:", err);
                setError("No se pudieron cargar las estadísticas.");
            })
            .finally(() => setLoading(false));
    }, []);

    const usuariosOrdenados = useMemo(() => { //useMemo ayuda a optimizar el rendimiento evitando cálculos repetidos cuando los datos relevantes no han cambiado.
        const copia = [...usuarios];
        //.sort() modifica el array original, por eso antes se hace const copia = [...usuarios]; para no alterar el array original usuarios.
        copia.sort((a, b) => // ordena el array copia de usuarios según el promedio de cada usuario.
        //Si orden es "mejor", ordena de mayor a menor (b.promedio - a.promedio), es decir, los usuarios con mejor promedio aparecen primero.
        //Si no, ordena de menor a mayor (a.promedio - b.promedio).
            orden === "mejor"
                ? b.promedio - a.promedio
                : a.promedio - b.promedio
        );
        return copia;
    }, [usuarios, orden]);

    const mejorPromedio = usuariosOrdenados[0]?.promedio ?? 0;

    const totalPaginas = Math.ceil(usuariosOrdenados.length / porPagina);
    const usuariosPagina = usuariosOrdenados.slice(
        (pagina - 1) * porPagina,
        pagina * porPagina
    );

    if (loading) return <p>Cargando estadísticas...</p>;
    if (error) return <p>{error}</p>;

    return (
        <div className="statpage-container">
            <h2>Estadísticas de Usuarios</h2>
            <div className="botones-perfom">
                <button
                    onClick={() => setOrden("mejor")}
                    disabled={orden === "mejor"}
                >
                    Ordenar por Mejor Performance
                </button>
                <button
                    onClick={() => setOrden("peor")}
                    disabled={orden === "peor"}
                    style={{ marginLeft: 8 }}
                >
                    Ordenar por Peor Performance
                </button>
            </div>
            <table width="100%" border="1" cellPadding={8} className="tabla-stat">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Partidas</th>
                        <th>Ganadas</th>
                        <th>Perdidas</th>
                        <th>Empatadas</th>
                        <th>Promedio Ganadas (%)</th>
                    </tr>
                </thead>
                <tbody>
                 {usuariosPagina.length > 0 ? (
                   usuariosPagina.map((u) => (
                     <tr
                       key={u.id}
                       style={
                         u.promedio === mejorPromedio && orden === "mejor"
                           ? { background: "#EE4232", fontWeight: "bold" }
                           : {}
                       }
                     >
                       <td>{u.nombre}</td>
                       <td>{u.total}</td>
                       <td>{u.ganadas}</td>
                       <td>{u.perdidas}</td>
                       <td>{u.empatadas}</td>
                       <td>{u.promedio.toFixed(2)}</td>
                     </tr>
                   ))
                 ) : (
                   <tr>
                     <td colSpan="6" style={{ textAlign: "center", padding: 10 }}>
                       No hay usuarios para mostrar
                     </td>
                   </tr>
                  )}
                </tbody>
            </table>
            <div style={{ marginTop: 16 }}>
                <button
                    onClick={() => setPagina(p => Math.max(1, p - 1))}
                    disabled={pagina === 1}
                >
                    Anterior
                </button>
                <span style={{ margin: "0 8px" }}>
                    Página {pagina} de {totalPaginas}
                </span>
                <button
                    onClick={() => setPagina(p => Math.min(totalPaginas, p + 1))}
                    disabled={pagina === totalPaginas}
                >
                    Siguiente
                </button>
            </div>
        </div>
    );
}

export default StatPage;