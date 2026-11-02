const loginError = new URLSearchParams(window.location.search).get('error');
if (loginError === '1') {
  const error = document.querySelector('#login-error');
  if (error) {
    error.textContent = 'Posta edo pasahitz baliogabea.';
    error.hidden = false;
  }
}

document.querySelector('#logout')?.addEventListener('click', () => {
  window.location.href = window.location.pathname.includes('/paginas/')
    ? '../auth/logout.php'
    : 'auth/logout.php';
});

function getCookie(name) {
  const cookie = document.cookie
    .split('; ')
    .find((item) => item.startsWith(`${name}=`));
  return cookie ? decodeURIComponent(cookie.split('=').slice(1).join('=')) : '';
}

document.addEventListener('DOMContentLoaded', () => {
  const cookieBanner = document.getElementById('cookie-banner');

  if (getCookie('cookies_accepted') === 'true') cookieBanner?.classList.add('hidden');
});
