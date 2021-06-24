<?php
    class Database extends mysqli {

        /**
         * Fungsi untuk mengeksekusi query pemanggilan PROCEDURE.
         * @return array Sebuah array tiga dimensi (3D) yang berisi hasil pemanggilan PROCEDURE.
         * D1: set/tabel (index), D2: baris/record (index), D3: kolom/field (associative).
         * Jika hanya ada 1 set, maka pengembalian hanya D2 dan D3 saja.
         * Jika query gagal, maka akan mengembalikan array kosong.
         */
        public function call_procedure($procedure): array {
            $result = array();
            $query = "CALL $procedure";

            // mengeksekusi multi query
            if ($this->multi_query($query)) {
                do {
                    // menyimpan set pertama
                    if ($queryResult = $this->store_result()) {
                        $set = array();

                        while ($row = $queryResult->fetch_assoc())
                            array_push($set, $row);

                        array_push($result, $set);
                        $queryResult->free_result();
                    }
                    // menuju ke set selanjutnya
                } while ($this->next_result());
            }

            // hanya mengembalikan D2 dan D3, jika hanya ada 1 set yang ditemukan.
            return (sizeof($result) === 1) ? $result[0] : $result;
        }

        /**
         * Fungsi untuk mengembalikan pesan jika terjadi error pada eksekusi query terakhir.
         * @param string $invalidMessage [opsional] Pesan error yang hendak ditampilkan. Secara default akan menampilkan pesan error bawaan dari MySQL.
         * @return array|null Mengembalikan null jika tidak ada error, dan akan mengembalikan array yang berisi status dan pesan error jika terdapat error.
         */
        public function last_query_error($invalidMessage = '') {
            if ($this->errno !== 0) {
                $errMsg = (empty($invalidMessage)) ? "[$this->errno] $this->error" : $invalidMessage;
                $SQLException = new mysqli_sql_exception($errMsg);
                return array(
                    'error' => true,
                    'message' => $SQLException->__toString()
                );
            }
            return null;
        }


        /**
         * Fungsi untuk mengecek sekaligus mendapatkan nilai Primary Key untuk suatu tabel.
         * @param string $tableName Nama tabel pada database.
         * @param string $pkFieldName Nama field yang menjadi Primary Key pada tabel.
         * @param function $code_generator Fungsi yang akan mengembalikan string kode. Misal: fungsi code_generator()
         * @return string Kode PK yang tidak ditemukan pada tabel.
         * @throws mysqli_sql_exception Jika terjadi kesalahan saat mengeksekusi query.
         */
        public function get_valid_PK($tableName, $pkFieldName, $code_generator) {
            $id = '';
            do {
                $id = $code_generator;
                $checkPK = $this->query("SELECT $pkFieldName FROM $tableName WHERE $pkFieldName = '$id'");
                if (!$checkPK) throw new mysqli_sql_exception($this->error);
            } while ($checkPK && $checkPK->num_rows > 0);
            return $id;
        }


        /**
         * Fungsi untuk mengeksekusi query dengan menggunakan statement SQL.
         * @param string $query Query SQL yang akan dieksekusi dengan menggunakan tanda ? untuk mengganti parameter di posisi yang sesuai.
         * @param string $paramTypes Sebuah string yang mengandung satu / lebih karakter untuk menspesifikasikan tipe dari parameter
         * @param mixed $values Satu / lebih variabel yang menjadi parameter pada query.
         * @return boolean|mysqli_result Mengembalikan 'true' jika eksekusi query berhasil, atau 'false' jika gagal, atau akan mengembalikan $resultSet jika ada.
         * @throws InvalidArgumentException Jika terdapat argumen yang tidak valid.
         * @throws mysqli_sql_exception Jika terdapat error pada SQL.
         */
        public function query_statement($query, $paramTypes, ...$values) {
            $stmt = $this->prepare($query);
            if (!$stmt) throw new mysqli_sql_exception($this->error);

            if (strlen($paramTypes) !== sizeof($values)) throw new InvalidArgumentException('Number of \'$paramTypes\' doesn\'t match with number of \'$values\'');
            $stmt->bind_param($paramTypes, ...$values);
            $success = $stmt->execute();
            $resultSet = $stmt->get_result();

            if ($resultSet === false) {
                return $success;
            } else {
                return $resultSet;
            }
        }
    }
?>