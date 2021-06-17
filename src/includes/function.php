<?php
    /**
     * Fungsi untuk mengeksekusi query pemanggilan PROCEDURE.
     * @param mysqli $conn Link identifier hasil dari mysqli_connect() atau mysqli_init().
     * @param string $procedure Nama PROCEDURE yang akan dipanggil beserta parameter yang diperlukan.
     * @return array Sebuah array tiga dimensi (3D) yang berisi hasil pemanggilan PROCEDURE.
     * D1: set/tabel (index), D2: baris/record (index), D3: kolom/field (associative).
     * Jika hanya ada 1 set, maka pengembalian hanya D2 dan D3 saja.
     */
    function call_procedure($conn, $procedure) {
        $result = array();
        $query = "CALL $procedure";

        // mengeksekusi multi query
        if (mysqli_multi_query($conn, $query)) {
            do {
                // menyimpan set pertama
                if ($queryResult = mysqli_store_result($conn)) {

                    $set = array();

                    while ($row = mysqli_fetch_assoc($queryResult)) {
                        array_push($set, $row);
                    }

                    array_push($result, $set);
                    mysqli_free_result($queryResult);
                }
                // menuju ke set selanjutnya
            } while (mysqli_next_result($conn));
        }

        // hanya mengembalikan D2 dan D3, jika hanya ada 1 set yang ditemukan.
        return (sizeof($result) === 1) ? $result[0] : $result;
    }


    /**
     * Fungsi untuk membuat kode yang terdiri dari beberapa digit angka acak, ditambah dengan awalan atau akhiran tertentu.
     * @param int $numLen Jumlah digit angka acak yang diinginkan (default: 5)
     * @param string $prefix Awalan tertentu pada kode.
     * @param string $suffix Akhiran tertentu pada kode.
     * @return string Kode yang dihasilkan.
     */
    function code_generator($numLen = 5, $prefix = '', $suffix = '') {
        $randNum = '';
        while ($numLen > 0) {
            # code...
            $randNum .= rand(0, 9);
            $numLen--;
        }
        return $prefix . $randNum . $suffix;
    }
?>
