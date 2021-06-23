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
        if ($conn->multi_query($query)) {
            do {
                // menyimpan set pertama
                if ($queryResult = $conn->store_result()) {
                    $set = array();

                    while ($row = $queryResult->fetch_assoc())
                        array_push($set, $row);

                    array_push($result, $set);
                    $queryResult->free_result();
                }
                // menuju ke set selanjutnya
            } while ($conn->next_result());
        }

        // hanya mengembalikan D2 dan D3, jika hanya ada 1 set yang ditemukan.
        return (sizeof($result) === 1) ? $result[0] : $result;
    }


    /**
     * Fungsi untuk membuat kode yang terdiri dari beberapa digit angka acak, ditambah dengan awalan atau akhiran tertentu.
     * @param int $numLen Jumlah digit angka acak yang diinginkan (default: 5).
     * @param string $prefix Awalan tertentu pada kode.
     * @param string $suffix Akhiran tertentu pada kode.
     * @return string Kode yang dihasilkan.
     */
    function code_generator($numLen = 5, $prefix = '', $suffix = '') {
        $randNum = '';
        while ($numLen > 0) {
            $randNum .= rand(0, 9);
            $numLen--;
        }
        return $prefix . $randNum . $suffix;
    }


    /**
     * Fungsi untuk mengupload file ke lokasi tertentu dengan melakukan validasi file terlebih dahulu.
     * @param $_FILES $file File yang akan diupload.
     * @param string $destination Lokasi tempat penyimpanan file.
     * @return array mengembalikan status & pesan dari proses upload. Status error akan bernilai 'true' jika upload file gagal, berlaku sebaliknya.
     */
    function upload_file($file, $destination) {
        /**
         * Persyaratan File :
         * 1. Ekstensi yang diperbolehkan terbatas, tercantum dalam variabel $allowedExt.
         * 2. Ukuran maksimal file tidak lebih dari 5 MB.
         * 3. Tidak ada error dalam file.
         */
        $allowedExt = array('jpg', 'jpeg', 'png', 'pdf', 'pptx', 'docx', 'zip', 'rar');
        $maxAllowedSize = 5000000; // 5 MB

        // mendapatkan nama file & ekstensinya
        $breakFileName = explode('.', $file['name']);
        $fileExt = strtolower(end($breakFileName));

        // mendapatkan informasi file lainnya
        $fileSize = $file['size'];
        $fileTmp = $file['tmp_name'];
        $fileError = $file['error'];

        // memastikan tidak ada error pada file & file yang diupload sesuai persyaratan
        if ($fileError !== 0 || !in_array($fileExt, $allowedExt) || $fileSize > $maxAllowedSize) {
            // jika file tidak sesuai persyaratan atau terjadi error
            return array(
                'error' => true,
                'message' => 'Terjadi kesalahan saat mengupload file! Pastikan file yang akan diupload sudah memenuhi persyaratan.'
            );
        }

        // mengupload file direktori server & menginsert data materi ke database mysql
        if (move_uploaded_file($fileTmp, $destination)) {
            return array(
                'error' => false,
                'message' => 'File berhasil diupload'
            );

        } else {
            return array(
                'error' => true,
                'message' => 'File gagal diupload'
            );
        }
    }


    /**
     * Fungsi untuk mendapatkan nama dari sebuah file dan ekstensinya.
     * @param $_FILES $file File yang akan dicek namanya.
     * @return array Array yang berisi nama file (name) dan ekstensi file (ext).
     */
    function break_filename($file) {
        $breakFileName = explode('.', $file['name']);
        $fileExt = strtolower(array_pop($breakFileName));
        $filename = implode('.', $breakFileName);

        return array(
            'name' => $filename,
            'ext' => $fileExt
        );
    }


    /**
     * Fungsi untuk mendapatkan url dari halaman saat ini.
     * @return string alamat url dari halaman saat ini.
     */
    function get_url_of_this_page() {
        return ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }


    /**
     * Fungsi untuk mengembalikan pesan jika terjadi error pada eksekusi query terakhir.
     * @param mysqli $conn Link identifier hasil dari mysqli_connect() atau mysqli_init().
     * @param string $invalidMessage Pesan error yang hendak ditampilkan. Secara default akan menampilkan pesan error bawaan dari MySQL.
     * @return array|null Mengembalikan null jika tidak ada error, dan akan mengembalikan array yang berisi status dan pesan error jika terdapat error.
     */
    function last_query_error($conn, $invalidMessage = '') {
        if ($conn->errno !== 0) {
            return array(
                'error' => true,
                'message' => (empty($invalidMessage)) ? "MySQL Error: [$conn->errno] $conn->error." : $invalidMessage
            );
        }
        return null;
    }


    /**
     * Fungsi untuk mengecek sekaligus mendapatkan nilai Primary Key untuk suatu tabel.
     * @param mysqli $conn Link identifier hasil dari mysqli_connect() atau mysqli_init().
     * @param string $tableName Nama tabel pada database.
     * @param string $pkFieldName Nama field yang menjadi Primary Key pada tabel.
     * @param function $code_generator Fungsi yang akan mengembalikan string kode. Misal: fungsi code_generator()
     * @return string Kode PK yang tidak ditemukan pada tabel.
     */
    function get_valid_PK($conn,  $tableName, $pkFieldName, $code_generator) {
        $id = '';
        do {
            $id = $code_generator;
            $checkPK = $conn->query("SELECT $pkFieldName FROM $tableName WHERE $pkFieldName = '$id'");
            if (!$checkPK) throw new mysqli_sql_exception($conn->error);
        } while ($checkPK && $checkPK->num_rows > 0);
        return $id;
    }


    /**
     * Fungsi untuk mencetak & menampilkan pesan pada browser-console.
     * @param string $message Pesan yang akan dicetak.
     * @param boolean $error Apakah pesan merupakan pesan error? Default false.
     */
    function print_console($message, $error = false) {
        $message = base64_encode($message);
        $script = ($error) ? "console.error(atob('$message'))" : "console.log(atob('$message'))";
        echo "<script>$script</script>";
    }


    /**
     * Fungsi untuk mengeksekusi query dengan menggunakan statement SQL.
     * @param mysqli $conn Link identifier hasil dari mysqli_connect() atau mysqli_init().
     * @param string $query Query SQL yang akan dieksekusi dengan menggunakan tanda ? untuk mengganti parameter di posisi yang sesuai.
     * @param string $paramTypes Sebuah string yang mengandung satu / lebih karakter untuk menspesifikasikan tipe dari parameter
     * @param mixed $values Satu / lebih variabel yang menjadi parameter pada query.
     * @return boolean Mengembalikan 'true' jika eksekusi query berhasil, atau 'false' jika gagal.
     * @throws InvalidArgumentException Jika terdapat argumen yang tidak valid.
     * @throws mysqli_sql_exception Jika terdapat error pada SQL.
     */
    function query_statement($conn, $query, $paramTypes, ...$values) {
        if (!$conn) throw new InvalidArgumentException('Invalid argument value for \'$conn\'', E_USER_ERROR);

        $stmt = $conn->prepare($query);
        if (!$stmt) throw new mysqli_sql_exception($conn->error);

        if (strlen($paramTypes) !== sizeof($values)) throw new InvalidArgumentException('Number of \'$paramTypes\' doesn\'t match with number of \'$values\'');
        $stmt->bind_param($paramTypes, ...$values);

        return $stmt->execute();
    }
?>
