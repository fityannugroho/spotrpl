const listRegion = async (region = 1, id = null) => {
    const provinsi = 1;
    const kabKota = 2;
    const kec = 3;
    const desaKel = 4;

    let link = '';

    if (region !== provinsi && id === null) {
        throw Error('Nilai id dibutuhkan untuk region selain provinsi!');
    }

    switch (region) {
        case kabKota:
            link = `https://www.emsifa.com/api-wilayah-indonesia/api/regencies/${id}.json`;
            break;
        case kec:
            link = `https://www.emsifa.com/api-wilayah-indonesia/api/districts/${id}.json`;
            break;
        case desaKel:
            link = `https://www.emsifa.com/api-wilayah-indonesia/api/villages/${id}.json`;
            break;
        default:
            link = `https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json`;
            break;
    }

    const response = await fetch(link);
    return await response.json();
}
