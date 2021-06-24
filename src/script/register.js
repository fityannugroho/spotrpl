/** Elemen input yang menerima data 'konfirmasi kata sandi' pada halaman registrasi */
const regConfirmPassword = document.querySelector('#cpassword');
/** Elemen input yang menerima data 'nim' pada halaman registrasi */
const regNIM = document.querySelector('#nim');
/** Elemen input yang menerima data 'kata sandi' pada halaman registrasi */
const regPassword = document.querySelector('#password');
/** Tombol untuk mensubmit form registrasi */
const registerBtn = document.querySelector('#registerBtn');

const formRegistrasiMhs = document.querySelector('#registerMhs');

/**
 * Validasi kolom konfirmasi kata sandi dan menampilkan respon validasi.
 */
const displayConfirmPasswordFieldRespons = () => {
  const confirmPasswordVal = regConfirmPassword.value;
  const passwordVal = regPassword.value;
  const parentConfirmPasswordField = regConfirmPassword.parentElement.parentElement;
  const parentPasswordField = regPassword.parentElement.parentElement;

  if (passwordVal === '') {
    displayInvalidFieldRespons(parentPasswordField, `Kolom Kata Sandi tidak boleh kosong!`);
  }

  if (confirmPasswordVal === '') {
    displayInvalidFieldRespons(parentConfirmPasswordField, `Kolom ini tidak boleh kosong!`);
  }

  if (passwordVal !== '' && confirmPasswordVal !== '') {
    if (isRegPasswordValid(regPassword)) {
      if (passwordVal === confirmPasswordVal) {
        displayValidFieldRespons(parentPasswordField);
        displayValidFieldRespons(parentConfirmPasswordField);
      } else {
        displayInvalidFieldRespons(parentConfirmPasswordField, 'Kata Sandi tidak cocok!');
      }
    }
  }
};


/**
 * Validasi kolom NIM dan menampilkan respon validasi.
 * @param {HTMLElement} nimInputEl
 */
 const displayNimFieldRespons = (nimInputEl) => {
  const parentField = nimInputEl.parentElement;

  if (!isFieldEmpty(parentField) && isNimValid(nimInputEl)) {
    displayValidFieldRespons(parentField);
  } else {
    displayInvalidFieldRespons(parentField, `NIM harus berupa angka dengan panjang 7 karakter.`);
  }
};

/**
 * Validasi kolom registrasi password dan menampilkan respon validasi.
 * @param {HTMLInputElement} regPasswordInputEl
 */
const displayRegPasswordFieldRespons = (regPasswordInputEl) => {
  const parentField = regPasswordInputEl.parentElement.parentElement;
  const regPasswordVal = regPasswordInputEl.value;

  // Memulai validasi
  if (!isFieldEmpty(parentField)) {
    const pswdMeter = passwordMeter(regPasswordVal);

    if (!pswdMeter.isPswdPassingMinLen()) {
      displayInvalidFieldRespons(parentField, 'Kata sandi minimal harus 8 karakter dengan mengandung : huruf kecil, huruf kapital, serta angka atau simbol.');
    } else if (!pswdMeter.isPswdPassingMinScore()) {
      displayInvalidFieldRespons(parentField, `Kata sandi lemah! Coba kombinasikan dengan angka atau simbol. (Skor: ${pswdMeter.score})`);
    } else {
      displayValidFieldRespons(parentField, `Kata sandi cukup kuat. (Skor: ${pswdMeter.score})`)
    }
  }
};

/**
 * Validasi kolom konfirmasi kata sandi.
 * @returns true, jika kolom konfirmasi kata sandi valid. Begitupun sebaliknya.
 */
 const isConfirmPasswordValid = () => {
  const confirmPasswordVal = regConfirmPassword.value;
  const passwordVal = regPassword.value;
  let valid = false;

  if (passwordVal !== '' && confirmPasswordVal !== '') {
    if (isRegPasswordValid(regPassword) && passwordVal === confirmPasswordVal) {
      valid = true;
    }
  }

  return valid;
};


/**
 * Validasi kolom NIM.
 * @param {HTMLElement} nimInputEl
 * @returns true, jika kolom NIM valid. Begitupun sebaliknya.
 */
const isNimValid = (nimInputEl) => {
  const nimVal = nimInputEl.value;
  return (nimVal !== '' && isNumberOnly(nimVal) && nimVal.length === 7) ? true : false;
};

/**
 * Validasi kolom registrasi password.
 * @param {HTMLInputElement} regPasswordInputEl
 * @returns true, jika kolom nama lengkap valid. Begitupun sebaliknya.
 */
const isRegPasswordValid = (regPasswordInputEl) => {
  const regPasswordVal = regPasswordInputEl.value;
  const pswdMeter = passwordMeter(regPasswordVal);

  return pswdMeter.isPswdPassingMinLen() && pswdMeter.isPswdPassingMinScore();
};

// Event handler validasi kolom nim
if (regNIM) {
  regNIM.addEventListener('input', (e) => displayNimFieldRespons(e.target));
  regNIM.addEventListener('blur', (e) => displayEmptyFieldRespons(e.target.parentElement));
}

// Event handler validasi kolom kata sandi
if (regPassword) {
  regPassword.addEventListener('input', (e) => displayRegPasswordFieldRespons(e.target));
  regPassword.addEventListener('blur', (e) => displayEmptyFieldRespons(e.target.parentElement.parentElement));
}

// Event handler validasi kolom konfirmasi kata sandi
if (regConfirmPassword) {
  // Event handler validasi kolom konfirmasi kata sandi, serta mencocokkannya dengan kolom kata sandi
  regConfirmPassword.addEventListener('input', displayConfirmPasswordFieldRespons);
  regConfirmPassword.addEventListener('blur', displayConfirmPasswordFieldRespons);
}

// Validasi form registrasi mhs
if (formRegistrasiMhs) {
  formRegistrasiMhs.addEventListener('input', () => {
    if (isNimValid(regNIM) && isRegPasswordValid(regPassword) && isConfirmPasswordValid()) {
      if (registerBtn.disabled) {
        registerBtn.attributes.removeNamedItem('disabled');
      }
    } else {
      registerBtn.setAttribute('disabled', '');
    }
  });
}
