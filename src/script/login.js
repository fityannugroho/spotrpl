const username = document.querySelector('#username');
const password = document.querySelector('#password');
const loginBtn = document.querySelector('#loginBtn');
const passwordField = password.parentElement.parentElement;

/**
 *
 * @param {HTMLInputElement} passwordInputEl
 */
const isPasswordValid = (passwordInputEl) => {
  const passwordVal = passwordInputEl.value;
  return passwordVal !== '' ? true : false;
};

const displayPasswordFieldRespons = () => {
  if (isPasswordValid(password)) {
    displayValidFieldRespons(passwordField);
  }
};

// === Validasi kolom email ===
username.addEventListener('blur', (e) => displayEmptyFieldRespons(e.target.parentElement));

// === Validasi kolom kata sandi ===
password.addEventListener('keyup', displayPasswordFieldRespons);
password.addEventListener('blur', (e) => displayEmptyFieldRespons(e.target.parentElement.parentElement));

// === Validasi form login ===
setInterval(() => {
  if (username.value !== '' && password.value !== '') {
    if (loginBtn.disabled) {
      loginBtn.attributes.removeNamedItem('disabled');
    }
  } else {
    loginBtn.setAttribute('disabled', '');
  }
}, 1000);
