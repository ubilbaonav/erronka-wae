const loginError = new URLSearchParams(window.location.search).get('error');
// Login-ak error=1 bidaltzen badu, errore-mezua erakusten da modalaren barruan.
if (loginError === '1') {
  const error = document.querySelector('#login-error');
  if (error) {
    error.textContent = 'Posta edo pasahitz baliogabea.';
    error.hidden = false;
  }
}

document.querySelector('#logout')?.addEventListener('click', () => {
  // Orrialdearen kokapenaren arabera logout PHP fitxategiaren bidea egokitzen da.
  window.location.href = window.location.pathname.includes('/paginas/')
    ? '../auth/logout.php'
    : 'auth/logout.php';
});

function getCookie(name) {
  // Cookie baten balioa irakurtzen du, izenaren arabera.
  const cookie = document.cookie
    .split('; ')
    .find((item) => item.startsWith(`${name}=`));
  return cookie ? decodeURIComponent(cookie.split('=').slice(1).join('=')) : '';
}

document.addEventListener('DOMContentLoaded', () => {
  // Baimena aurretik emanda badago, cookie-en bannerra ezkutatzen da.
  const cookieBanner = document.getElementById('cookie-banner');

  if (getCookie('cookies_accepted') === 'true') cookieBanner?.classList.add('hidden');
});
