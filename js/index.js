// Obtener los modales
const modalRegistro = document.getElementById('miModalRegistro');
const modalRecuperar = document.getElementById('miModalRecuperar');

// Botones que abre el modal
const btnRegistro = document.querySelector('.abrir-modal-registro');
const btnRecuperar = document.querySelector('.abrir-modal-recuperar');

// Obtener el elemento span que cierra el modal
const spanRegistro = document.querySelector('.cerrarRegistro');
const spanRecuperar = document.querySelector('.cerrarRecuperar');

// Abrir modal registro
btnRegistro.onclick = function(){
    modalRegistro.style.display = "flex"
}

btnRecuperar.onclick = function(){
    modalRecuperar.style.display = "flex"
}

spanRegistro.onclick = function(){
    modalRegistro.style.display = "none"
}

spanRecuperar.onclick = function(){
    modalRecuperar.style.display = "none"
}

// Cerrar el modal cuando el usuario hace click fuera del modal

window.onclick = function(event){
    if(event.target == modalRegistro){
        modalRegistro.style.display = "none"
    }
    if(event.target==modalRecuperar){
        modalRecuperar.style.display = "none"
    }
}
