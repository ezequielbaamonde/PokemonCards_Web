import { useState } from 'react'
import icono from '../assets/images/logo.png'

function HeaderComponent() {
  return (
    <>
        <div className='encabezado'>
          <a href="/" className="logo-link">
            <h1>PokeWeb Cards</h1>
            <img src={icono} alt="logo.png" />
          </a>
        </div>
    </>
  )
}
export default HeaderComponent
