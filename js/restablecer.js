document.querySelector('form').onsubmit = function(e){
    const pass = document.querySelector('input[name="nuevaPassword"]').value;
    const confirmPass = document.querySelector('input[name="confirmarPassword"]').value;

    if(pass!=confirmPass){
        alert('Las contraseñas no coinciden');
        e.preventDefault();
    }
}