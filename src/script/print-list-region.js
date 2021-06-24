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
