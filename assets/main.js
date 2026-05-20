// ── Toggle password visibility ──
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

// ── Form submission ──
const form      = document.getElementById('registroForm');
const submitBtn = document.getElementById('submitBtn');
const btnText   = submitBtn.querySelector('.btn-text');
const btnLoader = submitBtn.querySelector('.btn-loader');
const resultado = document.getElementById('resultado');

form.addEventListener('submit', async (e) => {
  e.preventDefault();

  const registro = document.getElementById('registro').value.trim();
  const password = document.getElementById('password').value;
  const nombre   = document.getElementById('nombre').value.trim();
  const cargo    = document.getElementById('cargo').value.trim();

  // Basic validation
  if (!registro || !password || !nombre || !cargo) {
    showResultado('Por favor completa todos los campos.', 'error');
    return;
  }

  // Show loader
  setLoading(true);
  resultado.hidden = true;

  try {
    const res = await fetch('api/registrar.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ registro, password, nombre, cargo })
    });

    const data = await res.json();

    if (data.success) {
      showResultado(
        `✅ Credenciales verificadas correctamente. El trabajador <strong>${nombre}</strong> fue registrado en el sistema.`,
        'success'
      );
      form.reset();
    } else {
      showResultado(
        `❌ ${data.message || 'Las credenciales institucionales son incorrectas. Por favor verifica e intenta de nuevo.'}`,
        'error'
      );
    }

  } catch (err) {
    showResultado('❌ Error de conexión con el servidor. Por favor intenta más tarde.', 'error');
  } finally {
    setLoading(false);
  }
});

function setLoading(loading) {
  submitBtn.disabled = loading;
  btnText.hidden  = loading;
  btnLoader.hidden = !loading;
}

function showResultado(html, type) {
  resultado.innerHTML = html;
  resultado.className = `resultado ${type}`;
  resultado.hidden = false;
  resultado.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}