/**
 * Mengubah tampilan icon pada password toggle dari icon bergambar 'eyes-off' ke 'eyes-on', atau sebaliknya.
 *
 * @param {HTMLButtonElement} passwordToggle Elemen button yang digunakan sebagai password toggle.
 */
const changeIconVisibility = (passwordToggle) => {
  const icon = passwordToggle.querySelector('i');

  if (icon.innerHTML === 'visibility_off') {
    icon.innerHTML = 'visibility';
  } else {
    icon.innerHTML = 'visibility_off';
  }
};


/**
 * Mengubah tipe input pada passwordField dari 'text' ke 'password' atau sebaliknya.
 *
 * Kata sandi menjadi terlihat jika tipe input = 'text', dan tertutup jika = 'password'.
 *
 * @param {HTMLInputElement} passwordInput Elemen input yang akan dimodifikasi.
 */
const changePasswordVisibility = (passwordInput) => {
  if (passwordInput.type === 'password') {
    passwordInput.type = 'text';
  } else {
    passwordInput.type = 'password';
  }
};


/**
 * Validasi kolom email dan menampilkan respon validasi.
 *
 * Respon validasi yang ditampilkan belum termasuk jika kolom kosong.
 *
 * @param {HTMLInputElement} emailInputEl Elemen Input yang menerima masukan email.
 */
 const displayEmailFieldRespons = (emailInputEl) => {
  const emailVal = emailInputEl.value;
  const parentField = emailInputEl.parentElement;

  if (!isFieldEmpty(parentField) && validateEmail(emailVal)) {
    displayValidFieldRespons(parentField);
  } else {
    displayInvalidFieldRespons(parentField, `Email harus mengandung karakter @ dan .`);
  }
};


/**
 * Menampilkan pesan error pada suatu field jika field tersebut kosong.
 * @param {HTMLElement} field Elemen dengan class '.field'.
 */
const displayEmptyFieldRespons = (field) => {
  if (isFieldEmpty(field)) {
    displayInvalidFieldRespons(field, `Kolom ini tidak boleh kosong!`);
  }
};


/**
 * Menampilkan pesan error di bawah inputField pada suatu field.
 * @param {HTMLElement} field Elemen dengan class '.field'.
 * @param {String} errorMessage Pesan error yang akan ditampilkan.
 */
const displayInvalidFieldRespons = (field, errorMessage) => {
  const inputEl = field.querySelector('input');
  const messageElement = field.querySelector('.alert-message');

  inputEl.style.boxShadow = '0 0 1px 2px red';

  messageElement.innerHTML = errorMessage;
  messageElement.classList.remove('valid');
  messageElement.classList.add('show');
};


/**
 * Menampilkan respon bahwa inputan sudah valid pada suatu field
 * @param {HTMLElement} field Elemen dengan class '.field'.
 * @param {String} validMessage Pesan valid yang akan ditampilkan (default = null).
 */
const displayValidFieldRespons = (field, validMessage = '') => {
  const inputEl = field.querySelector('input');
  const messageElement = field.querySelector('.alert-message');

  inputEl.style.boxShadow = '0 0 1px 2px green';

  if (messageElement) {
    messageElement.innerHTML = validMessage;
    messageElement.classList.add('valid');
  } else {
    messageElement.classList.remove('show');
  }
};


/**
 * Mencari semua karakter pada string yang merupakan huruf (baik kecil ataupun kapital).
 * @param {String} str string yang akan diuji.
 * @returns semua karakter huruf pada string dalam bentuk Array, atau NULL jika tidak ada.
 */
const findLetterOnly = (str) => {
  const letterOnlyTester = /^[a-zA-Z]*$/;
  return str.match(letterOnlyTester);
}


/**
 * Mencari semua karakter pada string yang merupakan huruf kecil (a sampai z).
 * @param {String} str string yang akan diuji.
 * @returns semua karakter huruf kecil pada string dalam bentuk Array, atau NULL jika tidak ada.
 */
 const findLowercaseChars = (str) => {
  const lowercaseTester = /[a-z]/g;
  return str.match(lowercaseTester);
};


/**
 * Mencari semua karakter pada string yang merupakan angka (0 sampai 9).
 * @param {String} str string yang akan diuji.
 * @returns semua karakter angka pada string dalam bentuk Array, atau NULL jika tidak ada.
 */
const findNumberChars = (str) => {
  const numberTesting = /[0-9]/g;
  return str.match(numberTesting);
};


/**
 * Mengecek apakah semua karakter pada string merupakan angka (0 sampai 9).
 * @param {String} str string yang akan diuji.
 * @returns Array yang berisi string, atau NULL jika ditemukan setidaknya 1 karakter bukan angka.
 */
const findNumberOnly = (str) => {
  const numberOnlyTester = /^\d*$/;
  return str.match(numberOnlyTester);
}


/**
 * Mencari semua karakter pada string yang merupakan simbol / karakter spesial (selain huruf dan angka).
 * @param {String} str string yang akan diuji.
 * @returns semua karakter simbol pada string dalam bentuk Array, atau NULL jika tidak ada.
 */
const findSymbolChars = (str) => {
  const symbolTester = /[!-/:-@{-~!"^_`\[\]\\]/g;
  return str.match(symbolTester);
};


/**
 * Mencari semua karakter pada string yang merupakan huruf kapital (A sampai Z).
 * @param {String} str string yang akan diuji.
 * @returns semua karakter huruf kapital pada string dalam bentuk Array, atau NULL jika tidak ada.
 */
const findUppercaseChars = (str) => {
  const uppercaseTester = /[A-Z]/g;
  return str.match(uppercaseTester);
};


/**
 * Validasi kolom email.
 * @param {HTMLInputElement} emailInputEl Elemen Input yang menerima masukan email
 * @returns TRUE, jika kolom email valid. Berlaku sebaliknya.
 */
 const isEmailValid = (emailInputEl) => {
  const emailVal = emailInputEl.value;
  return emailVal !== '' && validateEmail(emailVal) ? true : false;
};


/**
 * Mengecek apakah suatu kolom kosong atau tidak.
 * @param {HTMLElement} field kolom inputan yang akan dicek.
 * @returns TRUE, jika kolom belum terisi, serta menampilkan pesan kesalahan. Berlaku sebaliknya.
 */
 const isFieldEmpty = (field) => {
  const inputVal = field.querySelector('input').value;
  return (inputVal === '') ? true : false;
};


/**
 * Mengecek apakah semua karakter pada string merupakan huruf (baik kecil ataupun besar/kapital).
 * @param {String} str string yang akan diuji.
 * @returns TRUE, jika semua karakter pada string merupakan huruf. Berlaku sebaliknya.
 */
const isLetterOnly = (str) => {
  const letterOnlyTester = /^[a-zA-Z]*$/;
  return letterOnlyTester.test(str);
}


/**
 * Mengecek apakah ada setidaknya 1 karakter yang merupakan huruf kecil (a sampai z) pada string.
 * @param {String} str string yang akan diuji.
 * @returns TRUE, jika ditemukan setidaknya 1 karakter huruf kecil pada string. Berlaku sebaliknya.
 */
const isLowercaseExist = (str) => {
  const lowercaseTester = /[a-z]/g;
  return lowercaseTester.test(str);
};


/**
 * Mengecek apakah semua karakter pada string merupakan huruf kecil (a sampai z).
 * @param {String} str string yang akan diuji.
 * @returns TRUE, jika semua karakter pada string merupakan huruf kecil. Berlaku sebaliknya.
 */
const isLowercaseOnly = (str) => {
  const lowercaseOnlyTester = /^[a-z]*$/;
  return lowercaseOnlyTester.test(str);
};


/**
 * Mengecek apakah ada setidaknya 1 karakter yang merupakan angka (0 sampai 9) pada string.
 * @param {String} str string yang akan diuji.
 * @returns TRUE, jika ditemukan setidaknya 1 karakter angka pada string. Berlaku sebaliknya.
 */
 const isNumberExist = (str) => {
  const numberTesting = /[0-9]/g;
  return numberTesting.test(str);
}


/**
 * Mengecek apakah semua karakter pada string merupakan angka (0 sampai 9).
 * @param {String} str string yang akan diuji.
 * @returns TRUE, jika semua karakter pada string merupakan angka. Berlaku sebaliknya.
 */
const isNumberOnly = (str) => {
  const numberOnlyTester = /^\d*$/;
  return numberOnlyTester.test(str);
}


/**
 * Mengecek apakah ada setidaknya 1 karakter yang merupakan simbol / karakter spesial (selain huruf dan angka) pada string.
 * @param {String} str string yang akan diuji.
 * @returns TRUE, jika ditemukan setidaknya 1 karakter simbol pada string. Berlaku sebaliknya.
 */
 const isSymbolExist = (str) => {
  const symbolTester = /[!-/:-@{-~!"^_`\[\]\\]/g;
  return symbolTester.test(str);
};


/**
 * Mengecek apakah semua karakter pada string merupakan simbol / karakter spesial (selain huruf dan angka).
 * @param {String} str string yang akan diuji.
 * @returns TRUE, jika semua karakter pada string merupakan simbol. Berlaku sebaliknya.
 */
const isSymbolOnly = (str) => {
  const symbolOnlyTester = /^[!-/:-@{-~!"^_`\[\]\\]*$/;
  return symbolOnlyTester.test(str);
};


/**
 * Mengecek apakah ada setidaknya 1 karakter yang merupakan huruf kapital (A sampai Z) pada string.
 * @param {String} str string yang akan diuji.
 * @returns TRUE, jika ditemukan setidaknya 1 karakter huruf kapital pada string. Berlaku sebaliknya.
 */
const isUppercaseExist = (str) => {
  const uppercaseTester = /[A-Z]/g;
  return uppercaseTester.test(str);
};


/**
 * Mengecek apakah semua karakter pada string merupakan huruf kapital (A sampai Z).
 * @param {String} str string yang akan diuji.
 * @returns TRUE, jika semua karakter pada string merupakan huruf kapital. Berlaku sebaliknya.
 */
const isUppercaseOnly = (str) => {
  const uppercaseOnlyTester = /^[A-Z]*$/;
  return uppercaseOnlyTester.test(str);
};


/**
 * Mengecek apakah suatu string mengandung setidaknya 1 karakter kosong (spasi).
 * @param {String} str string.
 * @returns TRUE, jika ditemukan setidaknya 1 karakter kosong pada string. Berlaku sebaliknya.
 */
const isWhitespaceExist = (str) => {
  const whitespaceTester = /\s/g;
  return whitespaceTester.test(str);
}


/**
 * Fungsi sederhana untuk menghitung tingkat kekuatan kata sandi.
 * @param {String} password Kata sandi yang akan diuji.
 * @returns Objek yang terdiri dari score, minScore, fungsi isPswdPassingMinScore(), dan funsgi isPswdPassingMinLen()
 */
 const passwordMeter = (password) => {
  let point = {
    len: 4,
    lwr: 1,
    upr: 1,
    num: 4,
    sym: 8
  };

  let minimum = {
    len: 8,
    lwr: 3,
    upr: 3,
    num: 1,
    sym: 1
  };

  const getMinScore = () => {
    return (point.len * minimum.len)
      + (point.lwr * minimum.lwr)
      + (point.upr * minimum.upr)
      + (point.num * minimum.num)
      + (point.sym * minimum.sym)
    ;
  }

  const getPswdScore = () => {
    const nLwr = (findLowercaseChars(password)) ? findLowercaseChars(password).length : 0;
    const nUpr = (findUppercaseChars(password)) ? findUppercaseChars(password).length : 0;
    const nNum = (findNumberChars(password)) ? findNumberChars(password).length : 0;
    const nSym = (findSymbolChars(password)) ? findSymbolChars(password).length : 0;

    return point.len * password.length
      + point.lwr * (password.length - nLwr)
      + point.upr * (password.length - nUpr)
      + point.num * nNum
      + point.sym * nSym
    ;
  }

  return ({
    /** Memberikan skor uji kekuatan kata sandi */
    score: getPswdScore(),

    /** Memberikan skor minimal kekuatan kata sandi yang harus dicapai */
    minScore: getMinScore(),

    /**
     * Fungsi untuk mengecek apakah skor kekuatan kata sandi sudak melebihi skor minimal.
     * @returns TRUE, jika skor kekuatan kata sandi sudak melebihi skor minimal. Berlaku sebaliknya.
     */
    isPswdPassingMinScore: () => (getPswdScore() >= getMinScore()),

    /**
     * Fungsi untuk mengecek apakah panjang kata sandi sudak melebihi panjang minimal.
     * @returns TRUE, jika skor panjang kata sandi sudak melebihi panjang minimal. Berlaku sebaliknya.
     */
    isPswdPassingMinLen: () => (password.length >= minimum.len)
  });
}


/**
 * Memvalidasi parameter email adalah alamat email yang valid.
 * @param {String} emailAddress Alamat email yang akan divalidasi.
 * @returns TRUE, jika pola string dari nilai parameter email sesuai kriteria. Berlaku sebaliknya.
 */
const validateEmail = (emailAddress) => {
  const emailPattern = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return emailPattern.test(emailAddress);
};


/** List elemen div dari semua field yang menerima inputan kata sandi. */
const passwordFields = document.querySelectorAll('.password-field');


// === Memberikan event click pada passwordToggle lalu memperlihatkan password dan tombol visibility. ===
passwordFields.forEach((field) => {
  const inputEl = field.querySelector('input');
  const visibilityToggle = field.querySelector('.password-toggle');

  visibilityToggle.addEventListener('click', () => {
    changeIconVisibility(visibilityToggle);
    changePasswordVisibility(inputEl);
  });
});
