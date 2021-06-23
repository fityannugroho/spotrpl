/** Elemen input yang menerima data 'konfirmasi kata sandi' pada halaman registrasi */
const regConfirmPassword = document.querySelector('#cpassword');
/** Elemen input yang menerima data 'nim' pada halaman registrasi */
const regNIM = document.querySelector('#nim');
/** Elemen input yang menerima data 'kata sandi' pada halaman registrasi */
const regPassword = document.querySelector('#password');
/** Tombol untuk mensubmit form registrasi */
const registerBtn = document.querySelector('#registerBtn');

const form = document.querySelector('form');

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
regNIM.addEventListener('input', (e) => displayNimFieldRespons(e.target));
regNIM.addEventListener('blur', (e) => displayEmptyFieldRespons(e.target.parentElement));

// Event handler validasi kolom kata sandi
regPassword.addEventListener('input', (e) => displayRegPasswordFieldRespons(e.target));
regPassword.addEventListener('blur', (e) => displayEmptyFieldRespons(e.target.parentElement.parentElement));

// Event handler validasi kolom konfirmasi kata sandi
regConfirmPassword.addEventListener('blur', displayConfirmPasswordFieldRespons);

// Event handler validasi kolom konfirmasi kata sandi, serta mencocokkannya dengan kolom kata sandi
regConfirmPassword.addEventListener('input', displayConfirmPasswordFieldRespons);
regConfirmPassword.addEventListener('blur', displayConfirmPasswordFieldRespons);

// Validasi form registrasi
form.addEventListener('input', () => {
  if (isNimValid(regNIM) && isRegPasswordValid(regPassword) && isConfirmPasswordValid()) {
    if (registerBtn.disabled) {
      registerBtn.attributes.removeNamedItem('disabled');
    }
  } else {
    registerBtn.setAttribute('disabled', '');
  }
});


/** Elemen datalist untuk menyimpan daftar nama provinsi */
const datalistProvinsi = document.querySelector('#daftarProvinsi');
/** Elemen datalist untuk menyimpan daftar nama kabupaten/kota */
const datalistKabKota = document.querySelector('#daftarKabKota');
/** Elemen datalist untuk menyimpan daftar nama kecamatan */
const datalistKec = document.querySelector('#daftarKec');
/** Elemen datalist untuk menyimpan daftar nama desa/kelurahan */
const datalistDesaKel = document.querySelector('#daftarDesaKel');
/** Elemen input untuk menerima data provinsi */
const provinsiEl = document.querySelector('#provinsi');
/** Elemen input untuk menerima data kab/kota */
const kabKotaEl = document.querySelector('#kabKota');
/** Elemen input untuk menerima data kecamatan */
const kecEl = document.querySelector('#kec');
/** Elemen input untuk menerima data desa/kelurahan */
const desaKelEl = document.querySelector('#desaKel');


/**
 * Fungsi untuk mengosongkan semua opsi yang ada di dalam datalist.
 * @param {HTMLDataListElement} datalist Datalist yang akan dikosongkan.
 */
const removeOptionsFromDatalist = (datalist) => {
  const optionList = datalist.querySelectorAll('option');
  optionList.forEach((opt) => opt.remove());
}


const printProvinces = async () => {
  try {
    const daftarProvinsi = await listRegion();

    daftarProvinsi.forEach((provinsi) => {
      const provinceOption = document.createElement('option');

      provinceOption.value = provinceOption.innerHTML = provinsi.name;
      provinceOption.setAttribute('data-id', provinsi.id);
      datalistProvinsi.appendChild(provinceOption);
    });
  } catch (error) {
    console.error(error);
  }
}


const printRegencies = async (provinceId) => {
  removeOptionsFromDatalist(datalistKabKota);

  try {
    const daftarKabKota = await listRegion(2, provinceId);

    daftarKabKota.forEach((kabKota) => {
      const kabKotaOption = document.createElement('option');

      kabKotaOption.value = kabKotaOption.innerHTML = kabKota.name;
      kabKotaOption.setAttribute('data-id', kabKota.id);
      datalistKabKota.appendChild(kabKotaOption);
    });
  } catch (error) {
    console.error(error);
  }
}



const printDistricts = async (regencyId) => {
  removeOptionsFromDatalist(datalistKec);

  try {
    const daftarKec = await listRegion(3, regencyId);

    daftarKec.forEach((kec) => {
      const kecOption = document.createElement('option');

      kecOption.value = kecOption.innerHTML = kec.name;
      kecOption.setAttribute('data-id', kec.id);
      datalistKec.appendChild(kecOption);
    });
  } catch (error) {
    console.error(error);
  }
}


const printVillages = async (districtId) => {
  removeOptionsFromDatalist(datalistDesaKel);

  try {
    const daftarDesaKel = await listRegion(4, districtId);

    daftarDesaKel.forEach((desaKel) => {
      const desaKelOption = document.createElement('option');

      desaKelOption.value = desaKelOption.innerHTML = desaKel.name;
      desaKelOption.setAttribute('data-id', desaKel.id);
      datalistDesaKel.appendChild(desaKelOption);
    });
  } catch (error) {
    console.error(error);
  }
}


const onInputProvinsi = () => {
  const optionList = datalistProvinsi.querySelectorAll('option');

  kabKotaEl.value = '';
  kecEl.value = '';
  desaKelEl.value = '';
  removeOptionsFromDatalist(datalistKabKota);
  provinsiEl.removeAttribute('data-id');

  optionList.forEach((opt) => {
    if (provinsiEl.value === opt.value) {
      provinsiEl.setAttribute('data-id', opt.getAttribute('data-id'));
    }
  });

  // menampilkan opsi daftar kab/kota
  if (provinsiEl.hasAttribute('data-id')) {
    printRegencies(provinsiEl.getAttribute('data-id'));
  }
}

const onInputKabKota = () => {
  const optionList = datalistKabKota.querySelectorAll('option');

  kecEl.value = '';
  desaKelEl.value = '';
  removeOptionsFromDatalist(datalistKec);
  kabKotaEl.removeAttribute('data-id');

  optionList.forEach((opt) => {
    if (kabKotaEl.value === opt.value) {
      kabKotaEl.setAttribute('data-id', opt.getAttribute('data-id'));
    }
  });

  // menampilkan opsi daftar kec
  if (kabKotaEl.hasAttribute('data-id')) {
    printDistricts(kabKotaEl.getAttribute('data-id'));
  }
}


const onInputKecamatan = () => {
  const optionList = datalistKec.querySelectorAll('option');

  desaKelEl.value = '';
  removeOptionsFromDatalist(datalistDesaKel);
  kecEl.removeAttribute('data-id');

  optionList.forEach((opt) => {
    if (kecEl.value === opt.value) {
      kecEl.setAttribute('data-id', opt.getAttribute('data-id'));
    }
  });

  // menampilkan opsi daftar desa/kel
  if (kecEl.hasAttribute('data-id')) {
    printVillages(kecEl.getAttribute('data-id'));
  }
}


const onInputDesaKel = () => {
  const optionList = datalistDesaKel.querySelectorAll('option');

  desaKelEl.removeAttribute('data-id');

  optionList.forEach((opt) => {
    if (desaKelEl.value === opt.value) {
      desaKelEl.setAttribute('data-id', opt.getAttribute('data-id'));
    }
  });
}


// Menampilkan opsi daftar provinsi
printProvinces();

// Event handler saat memasukkan nilai pada input elemen
provinsiEl.addEventListener('input', onInputProvinsi);
kabKotaEl.addEventListener('input', onInputKabKota);
kecEl.addEventListener('input', onInputKecamatan);
desaKelEl.addEventListener('input', onInputDesaKel);


/**
 * Fungsi rekursif untuk mengecek apakah suatu field memiliki satu / lebih prasyarat field dengan nilai yang harus valid.
 * @param {HTMLInputElement} field Elemen input yang akan dicek.
 * @return true jika semua prasyarat field valid, atau false jika sebaliknya.
 */
 const checkPrerequisite = (field) => {
  let valid = true;
  if (field.hasAttribute('data-prerequisite')) {
    const prerequisiteField = document.querySelector(field.getAttribute('data-prerequisite'));

    // rekursif ke field prasyarat
    if (prerequisiteField.hasAttribute('data-prerequisite')) {
      const parentPrerequisiteField = document.querySelector(prerequisiteField.getAttribute('data-prerequisite'));
      valid = checkPrerequisite(parentPrerequisiteField);
    }

    // cek prasyarat field saat ini, jika prasyarat field saat ini terpenuhi
    valid = (prerequisiteField.hasAttribute('data-id')) ? valid && true : false ;
  }
  return valid;
}


/**
 * Fungsi rekursif untuk menonaktifkan semua field yang memiliki field prasyarat dengan id berikut.
 * @param {string} prerequisiteId Id field prasyarat.
 */
const disabledFieldsWithThisPrerequisite = (prerequisiteId) => {
  prerequisiteId = '#' + prerequisiteId;
  const fieldsWithThisPrerequisite = document.querySelectorAll(`[data-prerequisite='${prerequisiteId}']`);

  fieldsWithThisPrerequisite.forEach((field) => {
    // rekursif ke field yang memiliki field prasyarat dengan id berikut.
    if (field.hasAttribute('data-prerequisite')) {
      disabledFieldsWithThisPrerequisite(field.id);
    }
    field.setAttribute('disabled', '');
    field.value = '';
  });
}

const setFieldsWithPrerequisite = () => {
  const fieldsWithPrerequisite = document.querySelectorAll('[data-prerequisite]');
  if (fieldsWithPrerequisite.length > 0) {
    fieldsWithPrerequisite.forEach((field) => {
      const prerequisiteField = document.querySelector(field.getAttribute('data-prerequisite'));

      prerequisiteField.addEventListener('input', () => {
        if (checkPrerequisite(field)) {
          field.removeAttribute('disabled');
        } else {
          disabledFieldsWithThisPrerequisite(prerequisiteField.id);
        }
      })
    });
  }
}


disabledFieldsWithThisPrerequisite(provinsiEl.id);

provinsiEl.value = '';
setFieldsWithPrerequisite();
provinsiEl.addEventListener('input', setFieldsWithPrerequisite);
