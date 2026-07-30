const RAILWAY_URL = 'https://unultimointentoporvaleri-production-5064.up.railway.app';

// Toggle password
const togglePass = document.getElementById('togglePass');
const passInput  = document.getElementById('password');
const eyeIcon    = document.getElementById('eyeIcon');

togglePass.addEventListener('click', () => {
  const isPass = passInput.type === 'password';
  passInput.type = isPass ? 'text' : 'password';
  eyeIcon.innerHTML = isPass
    ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
       <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
       <line x1="1" y1="1" x2="23" y2="23"/>`
    : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
});

// Form
const form      = document.getElementById('registroForm');
const submitBtn = document.getElementById('submitBtn');
const btnText   = submitBtn.querySelector('.btn-text');
const btnLoader = submitBtn.querySelector('.btn-loader');
const resultado = document.getElementById('resultado');

form.addEventListener('submit', async (e) => {
  e.preventDefault();

  const registro = document.getElementById('registro').value.trim();
  const password = document.getElementById('password').value.trim();

  if (!registro || !password) {
    showResultado('Por favor completa todos los campos.', 'error');
    return;
  }

  setLoading(true);
  resultado.hidden = true;
  setEstado('Verificando credenciales en el portal UAGRM...');

  try {
    // PASO 1: Verificar via Railway
    const resp = await fetch(RAILWAY_URL + '/api/verificar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username: registro, password })
    });

    const data = await resp.json();

    if (!data.valid) {
      showResultado('Credenciales institucionales incorrectas. Verifica tu Registro y contrasena del portal UAGRM.', 'error');
      setLoading(false);
      setEstado('');
      return;
    }

    // PASO 2: Guardar en BD (no bloquear si falla)
    setEstado('Credenciales verificadas. Guardando registro...');
    fetch('api/registrar.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        registro:   data.codigo  || registro,
        password:   password,
        nombre:     data.nombre  || '',
        cargo:      data.carrera || 'ESTUDIANTE',
        verificado: true
      })
    }).catch(e => console.warn('BD no disponible:', e.message));

    // PASO 3: Guardar datos del carnet en sessionStorage y redirigir
    setEstado('Cargando carnet...');
    sessionStorage.setItem('carnet_data', JSON.stringify({
      registro:    data.codigo      || registro,
      nombre:      data.nombre      || '',
      carrera:     data.carrera     || '',
      facultad:    data.facultad    || '',
      ci:          data.ci          || '',
      telefonos:   data.telefonos   || '',
      foto_base64: data.foto_base64 || '',
    }));

    showResultado('Bienvenido <strong>' + (data.nombre || registro) + '</strong>. Cargando carnet...', 'success');

    setTimeout(() => {
      window.location.href = 'carnet.html';
    }, 1000);

  } catch (err) {
    showResultado('Error de conexion. Por favor intenta mas tarde.', 'error');
    console.error(err);
    setLoading(false);
    setEstado('');
  }
});

function setLoading(loading) {
  submitBtn.disabled = loading;
  btnText.hidden     = loading;
  btnLoader.hidden   = !loading;
}

function showResultado(html, type) {
  resultado.innerHTML  = html;
  resultado.className  = 'resultado ' + type;
  resultado.hidden     = false;
  resultado.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function setEstado(msg) {
  const el = document.getElementById('estadoMsg');
  if (el) el.textContent = msg;
}