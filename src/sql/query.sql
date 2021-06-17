CREATE DATABASE IF NOT EXISTS SpotRPL;
USE SpotRPL;


CREATE TABLE IF NOT EXISTS Mahasiswa (
    nim VARCHAR(7) PRIMARY KEY NOT NULL,
    nama_lengkap TEXT NOT NULL,
    kata_sandi TEXT NOT NULL
);


CREATE TABLE IF NOT EXISTS Dosen (
    kode VARCHAR(4) PRIMARY KEY NOT NULL,
    nama TEXT NOT NULL,
    kata_sandi TEXT NOT NULL
);


CREATE TABLE IF NOT EXISTS Mata_Kuliah (
    kode VARCHAR(5) PRIMARY KEY NOT NULL,
    nama TEXT NOT NULL,
    semester INT NOT NULL,
    sks INT NOT NULL DEFAULT 1,
    thn_mulai YEAR NOT NULL,
    thn_selesai YEAR NOT NULL,
    jml_pertemuan INT NOT NULL DEFAULT 16,
    dosen_pengampu1 VARCHAR(4) NOT NULL,
    dosen_pengampu2 VARCHAR(4),
    FOREIGN KEY(dosen_pengampu1) REFERENCES Dosen(kode),
    FOREIGN KEY(dosen_pengampu2) REFERENCES Dosen(kode)
);


CREATE TABLE IF NOT EXISTS RPS (
    kode VARCHAR(8) PRIMARY KEY NOT NULL,
    mata_kuliah VARCHAR(5) NOT NULL,
    nama_file TEXT NOT NULL,
    mimetype TEXT NOT NULL,
    FOREIGN KEY(mata_kuliah) REFERENCES Mata_Kuliah(kode)
);


CREATE TABLE IF NOT EXISTS Silabus (
    kode VARCHAR(8) PRIMARY KEY NOT NULL,
    mata_kuliah VARCHAR(5) NOT NULL,
    nama_file TEXT NOT NULL,
    mimetype TEXT NOT NULL,
    FOREIGN KEY(mata_kuliah) REFERENCES Mata_Kuliah(kode)
);


CREATE TABLE IF NOT EXISTS Kelas (
    kode VARCHAR(8) PRIMARY KEY NOT NULL,
    mata_kuliah VARCHAR(5) NOT NULL,
    nama VARCHAR(2) NOT NULL,
    kapasitas INT NOT NULL,
    FOREIGN KEY(mata_kuliah) REFERENCES Mata_Kuliah(kode)
);


CREATE TABLE IF NOT EXISTS Kontrak_Kelas (
    kode VARCHAR(8) PRIMARY KEY NOT NULL,
    mahasiswa VARCHAR(7) NOT NULL,
    kelas VARCHAR(8) NOT NULL,
    FOREIGN KEY(mahasiswa) REFERENCES Mahasiswa(nim),
    FOREIGN KEY(kelas) REFERENCES Kelas(kode)
);


CREATE TABLE IF NOT EXISTS Pertemuan (
    kode VARCHAR(8) PRIMARY KEY NOT NULL,
    kelas VARCHAR(8) NOT NULL,
    nomor_pert INT NOT NULL,
    topik TEXT NOT NULL,
    deskripsi TEXT,
    waktu_akses DATETIME NOT NULL,
    FOREIGN KEY(kelas) REFERENCES Kelas(kode)
);


CREATE TABLE IF NOT EXISTS Materi (
    kode VARCHAR(8) PRIMARY KEY NOT NULL,
    pertemuan VARCHAR(8) NOT NULL,
    judul TEXT NOT NULL,
    deskripsi TEXT,
    nama_file TEXT,
    mimetype TEXT,
    url TEXT,
    FOREIGN KEY(pertemuan) REFERENCES Pertemuan(kode)
);


CREATE TABLE IF NOT EXISTS Kehadiran (
    kode INT(8) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    pertemuan VARCHAR(8) NOT NULL,
    mahasiswa VARCHAR(7) NOT NULL,
    hadir BOOLEAN NOT NULL DEFAULT FALSE,
    keterangan TEXT,
    FOREIGN KEY(pertemuan) REFERENCES Pertemuan(kode),
    FOREIGN KEY(mahasiswa) REFERENCES Mahasiswa(nim)
);


CREATE TABLE IF NOT EXISTS Tugas (
    kode VARCHAR(8) PRIMARY KEY NOT NULL,
    pertemuan VARCHAR(8) NOT NULL,
    judul TEXT NOT NULL,
    deskripsi TEXT,
    deadline DATETIME NOT NULL,
    lampiran TEXT,
    mimetype TEXT,
    FOREIGN KEY(pertemuan) REFERENCES Pertemuan(kode)
);


CREATE TABLE IF NOT EXISTS Submit_Tugas (
    kode VARCHAR(8) PRIMARY KEY NOT NULL,
    mahasiswa VARCHAR(7) NOT NULL,
    tugas VARCHAR(8) NOT NULL,
    file_tugas TEXT NOT NULL,
    mimetype TEXT NOT NULL,
    waktu_pengumpulan DATETIME NOT NULL DEFAULT current_timestamp(),
    FOREIGN KEY(tugas) REFERENCES Tugas(kode),
    FOREIGN KEY(mahasiswa) REFERENCES Mahasiswa(nim)
);


CREATE TABLE IF NOT EXISTS Ujian (
    kode VARCHAR(8) PRIMARY KEY NOT NULL,
    pertemuan VARCHAR(8) NOT NULL,
    durasi TIME NOT NULL,
    catatan TEXT,
    FOREIGN KEY(pertemuan) REFERENCES Pertemuan(kode)
);


CREATE TABLE IF NOT EXISTS Soal (
    kode VARCHAR(8) PRIMARY KEY NOT NULL,
    pilihan_ganda BOOLEAN NOT NULL DEFAULT FALSE,
    pertanyaan TEXT NOT NULL,
    poin_benar FLOAT NOT NULL DEFAULT 1.0,
    poin_salah FLOAT NOT NULL DEFAULT 0,
    nama_file TEXT,
    mimetype TEXT
);


CREATE TABLE IF NOT EXISTS Paket_Soal (
    kode VARCHAR(8) PRIMARY KEY NOT NULL,
    ujian VARCHAR(8) NOT NULL,
    soal VARCHAR(8) NOT NULL,
    FOREIGN KEY(ujian) REFERENCES Ujian(kode),
    FOREIGN KEY(soal) REFERENCES Soal(kode)
);


CREATE TABLE IF NOT EXISTS Opsi_PG (
    kode VARCHAR(8) PRIMARY KEY NOT NULL,
    soal VARCHAR(8) NOT NULL,
    opsi_benar TEXT NOT NULL,
    opsi_salah1 TEXT NOT NULL,
    opsi_salah2 TEXT,
    opsi_salah3 TEXT,
    opsi_salah4 TEXT,
    FOREIGN KEY(soal) REFERENCES Soal(kode)
);


CREATE TABLE IF NOT EXISTS Jawaban_Ujian (
    kode VARCHAR(8) PRIMARY KEY NOT NULL,
    ujian VARCHAR(8) NOT NULL,
    mahasiswa VARCHAR(7) NOT NULL,
    soal VARCHAR(8) NOT NULL,
    jawaban TEXT NOT NULL,
    poin FLOAT DEFAULT 0.0,
    FOREIGN KEY(ujian) REFERENCES Ujian(kode),
    FOREIGN KEY(mahasiswa) REFERENCES Mahasiswa(nim),
    FOREIGN KEY(soal) REFERENCES Soal(kode)
);


CREATE TABLE IF NOT EXISTS Nilai_Ujian (
    kode INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    ujian VARCHAR(8) NOT NULL,
    mahasiswa VARCHAR(7) NOT NULL,
    nilai FLOAT NOT NULL DEFAULT 0,
    sudah_dinilai BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY(ujian) REFERENCES Ujian(kode),
    FOREIGN KEY(mahasiswa) REFERENCES Mahasiswa(nim)
);


-- Membuat PROCEDURE dengan parameter kode matkul untuk mengambil data mata kuliah pada tabel Mata_Kuliah ditambah data nama_dosen_pengampu1 dan nama_dosen_pengampu2 yang diambil dari tabel Dosen.
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_subject (IN kode_matkul VARCHAR(5))
BEGIN
    SELECT
        mk.kode,
        mk.nama,
        mk.semester,
        mk.sks,
        mk.thn_mulai,
        mk.thn_selesai,
        mk.jml_pertemuan,
        mk.dosen_pengampu1 AS kode_dosen1,
        d1.nama AS nama_dosen1,
        mk.dosen_pengampu2 AS kode_dosen2,
        d2.nama AS nama_dosen2
    FROM Mata_Kuliah AS mk
    INNER JOIN Dosen AS d1
        ON d1.kode = mk.dosen_pengampu1
    LEFT JOIN Dosen AS d2
        ON d2.kode = mk.dosen_pengampu2
    WHERE mk.kode = kode_matkul;
END //
DELIMITER ;


-- Membuat PROCEDURE tanpa parameter untuk mengambil semua data mata kuliah pada tabel Mata_Kuliah ditambah data nama_dosen_pengampu1 dan nama_dosen_pengampu2 yang diambil dari tabel Dosen.
-- Data diurutkan secara menaik (ASC) berdasarkan kode mata kuliah.
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS daftar_matkul ()
BEGIN
    SELECT
        mk.kode,
        mk.nama,
        mk.semester,
        mk.sks,
        mk.thn_mulai,
        mk.thn_selesai,
        mk.dosen_pengampu1 AS kode_dosen1,
        d1.nama AS nama_dosen1,
        mk.dosen_pengampu2 AS kode_dosen2,
        d2.nama AS nama_dosen2
    FROM Mata_Kuliah AS mk
    INNER JOIN Dosen AS d1
        ON d1.kode = mk.dosen_pengampu1
    LEFT JOIN Dosen AS d2
        ON d2.kode = mk.dosen_pengampu2
    ORDER BY mk.kode ASC;
END; //
DELIMITER ;


-- Membuat PROCEDURE dengan parameter nim untuk mengambil semua data kelas yang dikontrak oleh seorang mahasiswa dengan nim yang sesuai parameter.
-- Data diambil dari tabel Kontrak_Kelas, tabel Kelas, tabel Mata_Kuliah, dan tabel Dosen.
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS daftar_kelas_saya (IN nim VARCHAR(7))
BEGIN
    SELECT
        ks.kode AS kode_kelas,
        ks.nama AS nama_kelas,
        mk.kode AS kode_mk,
        mk.nama AS nama_mk,
        mk.semester,
        mk.sks,
        mk.thn_mulai,
        mk.thn_selesai,
        d1.kode AS kode_dosen1,
        d1.nama AS nama_dosen1,
        d2.kode AS kode_dosen2,
        d2.nama AS nama_dosen2
    FROM Kontrak_Kelas AS kk
    INNER JOIN Kelas AS ks
        ON ks.kode = kk.kelas
    INNER JOIN Mata_Kuliah AS mk
        ON mk.kode = ks.mata_kuliah
    INNER JOIN Dosen AS d1
        ON d1.kode = mk.dosen_pengampu1
    INNER JOIN Dosen AS d2
        ON d2.kode = mk.dosen_pengampu2
    WHERE kk.mahasiswa = nim
    ORDER BY mk.kode ASC;
END; //
DELIMITER ;


DELIMITER //
CREATE PROCEDURE IF NOT EXISTS available_class (IN nim VARCHAR(7))
BEGIN
    SELECT
        kls.kode AS kode_kls,
        kls.nama AS nama_kls,
        mk.kode AS kode_mk,
        mk.nama AS nama_mk
    FROM Kelas AS kls
    INNER JOIN Mata_Kuliah AS mk
        ON mk.kode = kls.mata_kuliah
    WHERE kls.kode NOT IN (
        SELECT kks.kelas FROM Kontrak_Kelas AS kks WHERE mahasiswa = nim
    );
END; //
DELIMITER ;


DELIMITER //
CREATE PROCEDURE IF NOT EXISTS already_exists_meeting (IN kode_kelas VARCHAR(8))
BEGIN
    SELECT nomor_pert FROM Pertemuan WHERE kelas = kode_kelas;
END; //
DELIMITER ;


-- Membuat FUNCTION dengan parameter thetimestamp untuk mengecek apakah suatu stempel waktu (misal: “2021-08-01 23:59:59”) sudah berlalu dari waktu saat ini.
DELIMITER //
CREATE FUNCTION IF NOT EXISTS has_timestamp_passed (the_timestamp TEXT)
RETURNS BOOLEAN
BEGIN
    RETURN UNIX_TIMESTAMP() > UNIX_TIMESTAMP(the_timestamp);
END; //
DELIMITER ;


-- Membuat PROCEDURE dengan parameter kode_kelas dan no_pert untuk mendapatkan data pertemuan beserta data kelas dan data mata kuliah terkait, jika memang data pertemuan sudah dapat diakses
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_meeting (
    IN kode_kelas VARCHAR(8),
    IN no_pert INT
)
BEGIN
    DECLARE is_exist BOOLEAN DEFAULT FALSE;
    DECLARE is_accessible BOOLEAN DEFAULT FALSE;
    DECLARE access_time TIMESTAMP;
    IF EXISTS(
        SELECT kode FROM Pertemuan
        WHERE kelas = kode_kelas AND nomor_pert = no_pert
    ) THEN
        SET is_exist = TRUE;
        SELECT waktu_akses INTO access_time FROM Pertemuan
            WHERE kelas = kode_kelas AND nomor_pert = no_pert;
        SET is_accessible = has_timestamp_passed(access_time);
    END IF;
    IF is_exist AND is_accessible THEN
        SELECT
            is_exist,
            is_accessible,
            pr.kode,
            pr.nomor_pert,
            pr.topik,
            pr.deskripsi,
            pr.waktu_akses,
            ks.kode AS kode_kelas,
            ks.nama AS nama_kelas,
            mk.kode AS kode_mk,
            mk.nama AS nama_mk
        FROM Pertemuan AS pr
        INNER JOIN Kelas AS ks
            ON ks.kode = pr.kelas
        INNER JOIN Mata_Kuliah AS mk
            ON mk.kode = ks.mata_kuliah
        WHERE pr.kelas = kode_kelas AND pr.nomor_pert = no_pert;
    ELSEIF is_exist THEN
        SELECT
            is_exist,
            is_accessible,
            access_time,
            ks.kode AS kode_kelas,
            ks.nama AS nama_kelas,
            mk.kode AS kode_mk,
            mk.nama AS nama_mk
        FROM Kelas AS ks
        INNER JOIN Mata_Kuliah AS mk
            ON mk.kode = ks.mata_kuliah
        WHERE ks.kode = kode_kelas;
    ELSE
        SELECT
            is_exist,
            ks.kode AS kode_kelas,
            ks.nama AS nama_kelas,
            mk.kode AS kode_mk,
            mk.nama AS nama_mk
        FROM Kelas AS ks
        INNER JOIN Mata_Kuliah AS mk
            ON mk.kode = ks.mata_kuliah
        WHERE ks.kode = kode_kelas;
    END IF;
END //
DELIMITER ;


-- Membuat PROCEDURE dengan parameter kode_kelas dan no_pert untuk mendapatkan daftar pertemuan yang dapat diakses saat ini untuk kelas yang sesuai dengan kode_kelas.
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS daftar_pertemuan_dibuka (
    IN kode_kelas VARCHAR(8)
)
BEGIN
    SELECT kode, nomor_pert, waktu_akses FROM Pertemuan
    WHERE kelas = kode_kelas AND has_timestamp_passed(waktu_akses);
END; //
DELIMITER ;


-- Membuat PROCEDURE dengan parameter kode_pertemuan untuk mengambil (SELECT) semua materi untuk pertemuan tersebut.
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS materi_pertemuan (IN kode_pert VARCHAR(8))
BEGIN
    SELECT kode, judul, deskripsi, nama_file, mimetype, url
    FROM Materi WHERE pertemuan = kode_pert;
END //
DELIMITER ;


-- membuat PROCEDURE dengan parameter kode_kelas untuk mengambil (SELECT) semua nim yang mengontrak suatu kelas dengan kode = kode_kelas dari tabel Kontrak_Kelas.
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS list_mhs_di_kls (IN kode_kelas TEXT)
BEGIN
    SELECT mh.nim, mh.nama_lengkap
    FROM Kontrak_Kelas AS ks
    INNER JOIN Mahasiswa AS mh
        ON mh.nim = ks.mahasiswa
    WHERE ks.kelas = kode_kelas;
END //
DELIMITER ;


DELIMITER //
CREATE PROCEDURE IF NOT EXISTS absensi_pertemuan (IN kode_pertemuan TEXT)
BEGIN
    SELECT kd.kode, mh.nim, mh.nama_lengkap, kd.hadir, kd.keterangan
    FROM Kehadiran AS kd
    INNER JOIN Mahasiswa AS mh
        ON mh.nim = kd.mahasiswa
    WHERE pertemuan = kode_pertemuan;
END //
DELIMITER ;


-- Membuat PROCEDURE untuk mendapatkan data semua soal
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS ambil_soal (IN kode_ujian TEXT)
BEGIN
    SELECT
        sol.kode AS kode_soal,
        sol.pertanyaan,
        sol.poin_benar,
        sol.poin_salah,
        sol.nama_file,
        sol.mimetype,
        opt.kode AS kode_pg,
        opt.opsi_benar,
        opt.opsi_salah1,
        opt.opsi_salah2,
        opt.opsi_salah3,
        opt.opsi_salah4
    FROM Paket_Soal AS pkt
    INNER JOIN Soal AS sol
        ON sol.kode = pkt.soal
    LEFT JOIN Opsi_PG AS opt
        ON opt.soal = sol.kode
    WHERE pkt.ujian = kode_ujian;
END //
DELIMITER ;


-- Membuat PROCEDURE untuk mendapatkan data soal PG saja
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS ambil_soal_pg (IN kode_ujian TEXT)
BEGIN
    SELECT
        sol.kode AS kode_soal,
        sol.pertanyaan,
        sol.poin_benar,
        sol.poin_salah,
        sol.nama_file,
        sol.mimetype,
        opt.kode AS kode_pg,
        opt.opsi_benar,
        opt.opsi_salah1,
        opt.opsi_salah2,
        opt.opsi_salah3,
        opt.opsi_salah4
    FROM Paket_Soal AS pkt
    INNER JOIN Soal AS sol
        ON sol.kode = pkt.soal
    INNER JOIN Opsi_PG AS opt
        ON opt.soal = sol.kode
    WHERE pkt.ujian = kode_ujian;
END //
DELIMITER ;


-- Membuat PROCEDURE untuk mendapatkan data soal esai saja
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS ambil_soal_esai (IN kode_ujian TEXT)
BEGIN
    SELECT
        sol.kode AS kode_soal,
        sol.pertanyaan,
        sol.poin_benar,
        sol.poin_salah,
        sol.nama_file,
        sol.mimetype
    FROM Paket_Soal AS pkt
    INNER JOIN Soal AS sol
        ON sol.kode = pkt.soal
    WHERE pkt.ujian = kode_ujian AND sol.pilihan_ganda = FALSE;
END //
DELIMITER ;


--
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS detail_ujian (IN kode_ujian TEXT)
BEGIN
    SELECT
        uji.kode AS kode_ujian,
        uji.durasi,
        uji.catatan,
        prt.kode AS kode_prt,
        prt.nomor_pert AS nomor_prt,
        prt.topik AS topik_prt,
        kls.kode AS kode_kls,
        kls.nama AS nama_kls,
        mk.kode AS kode_mk,
        mk.nama AS nama_mk,
        mk.semester,
        dsn1.kode AS kode_dsn1,
        dsn1.nama AS nama_dsn1,
        dsn2.kode AS kode_dsn2,
        dsn2.nama AS nama_dsn2
    FROM Ujian AS uji
    INNER JOIN Pertemuan AS prt
        ON prt.kode = uji.pertemuan
    INNER JOIN Kelas AS kls
        ON kls.kode = prt.kelas
    INNER JOIN Mata_Kuliah AS mk
        ON mk.kode = kls.mata_kuliah
    INNER JOIN Dosen AS dsn1
        ON dsn1.kode = mk.dosen_pengampu1
    LEFT JOIN Dosen AS dsn2
        ON dsn2.kode = mk.dosen_pengampu2
    WHERE uji.kode = kode_ujian;
END //
DELIMITER ;


--
DELIMITER //
CREATE FUNCTION IF NOT EXISTS has_done_exam (
    kode_ujian VARCHAR(8),
    nim VARCHAR(7)
)
RETURNS BOOLEAN
BEGIN
    DECLARE n_jwb INT DEFAULT 0;
    DECLARE n_soal INT DEFAULT 0;
    SELECT COUNT(kode) INTO n_jwb FROM Jawaban_Ujian WHERE ujian = kode_ujian AND mahasiswa = nim;
    SELECT COUNT(kode) INTO n_soal FROM Paket_Soal WHERE ujian = kode_ujian;
    RETURN n_jwb = n_soal;
END //
DELIMITER ;


--
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_key_answer (IN kode_ujian VARCHAR(8))
BEGIN
    SELECT
        pkt.soal AS kode_soal,
        opt.opsi_benar AS kunci
    FROM Paket_Soal AS pkt
    INNER JOIN Opsi_PG AS opt
        ON opt.soal = pkt.soal
    WHERE pkt.ujian = kode_ujian;
END //
DELIMITER ;


--
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_pg_answer (
    IN kode_ujian VARCHAR(8),
    IN nim VARCHAR(7)
)
BEGIN
    SELECT
        jwb.soal AS kode_soal,
        jwb.jawaban,
        jwb.poin
    FROM Jawaban_Ujian AS jwb
    INNER JOIN Soal AS sol
        ON sol.kode = jwb.soal
    WHERE jwb.ujian = kode_ujian AND jwb.mahasiswa = nim AND sol.pilihan_ganda = TRUE
    ORDER BY jwb.soal ASC;
END //
DELIMITER ;


--
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_esai_answer (
    IN kode_ujian VARCHAR(8),
    IN nim VARCHAR(7)
)
BEGIN
    SELECT
        jwb.soal AS kode_soal,
        jwb.jawaban,
        jwb.poin
    FROM Jawaban_Ujian AS jwb
    INNER JOIN Soal AS sol
        ON sol.kode = jwb.soal
    WHERE jwb.ujian = kode_ujian AND jwb.mahasiswa = nim AND sol.pilihan_ganda = FALSE
    ORDER BY jwb.soal ASC;
END //
DELIMITER ;


-- membuat TRIGGER untuk menambahkan data nilai ujian setiap kali membuat ujian
DELIMITER //
CREATE TRIGGER IF NOT EXISTS simpan_nilai_ujian AFTER INSERT ON Ujian
FOR EACH ROW
BEGIN
    DECLARE finished BOOLEAN DEFAULT FALSE;
    DECLARE nim VARCHAR(7);
    DECLARE list_mhs CURSOR FOR
        SELECT kk.mahasiswa FROM Kontrak_Kelas AS kk
        INNER JOIN Pertemuan AS prt
            ON prt.kode = NEW.pertemuan
        WHERE kk.kelas = prt.kelas
    ;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET finished = TRUE;

    OPEN list_mhs;
    getMhs: LOOP
        FETCH list_mhs INTO nim;
        IF finished = TRUE THEN
            LEAVE getMhs;
        END IF;

        -- memasukkan data nilai ke tabel nilai
        INSERT INTO Nilai_Ujian (ujian, mahasiswa)
            VALUES (NEW.kode, nim)
        ;
    END LOOP getMhs;
    CLOSE list_mhs;

END //
DELIMITER ;


--
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_exam_rank (IN kode_ujian VARCHAR(8))
BEGIN
    SELECT
        mhs.nim,
        mhs.nama_lengkap,
        nil.nilai,
        nil.sudah_dinilai,
        DENSE_RANK() OVER (ORDER BY nilai DESC) AS rank
    FROM Nilai_Ujian AS nil
    INNER JOIN Mahasiswa AS mhs
        ON mhs.nim = nil.mahasiswa
    WHERE nil.ujian = kode_ujian;
END //
DELIMITER ;


-- Membuat TRIGGER untuk menambahkan (INSERT) data kehadiran pada tabel Kehadiran secara otomatis setelah adanya penambahan pada tabel Pertemuan
DELIMITER //
CREATE TRIGGER IF NOT EXISTS buat_presensi AFTER INSERT ON Pertemuan
FOR EACH ROW
BEGIN
    DECLARE finished INTEGER DEFAULT FALSE;
    DECLARE nim VARCHAR(7);
    DECLARE list_mhs CURSOR FOR SELECT mahasiswa FROM Kontrak_Kelas WHERE kelas = NEW.kelas;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET finished = TRUE;

    OPEN list_mhs;
    getMhs: LOOP
        FETCH list_mhs INTO nim;
        IF finished = TRUE THEN
            LEAVE getMhs;
        END IF;

        -- menambahkan data ke tabel kehadiran
        INSERT INTO Kehadiran(mahasiswa, pertemuan) VALUES (nim, NEW.kode);

    END LOOP getMhs;
    CLOSE list_mhs;
END //
DELIMITER ;




-- Membuat TRIGGER untuk menghapus semua data Submit_Tugas yang terhubung ke data Tugas yang akan dihapus.
DELIMITER //
CREATE TRIGGER IF NOT EXISTS del_related_submit_tugas BEFORE DELETE ON Tugas
FOR EACH ROW
BEGIN
    DELETE FROM Submit_Tugas WHERE tugas = OLD.kode;
END //
DELIMITER ;


--
DELIMITER //
CREATE TRIGGER IF NOT EXISTS del_related_materi BEFORE DELETE ON Pertemuan
FOR EACH ROW
BEGIN
    DELETE FROM Materi WHERE pertemuan = OLD.kode;
END //
DELIMITER ;


--
DELIMITER //
CREATE TRIGGER IF NOT EXISTS del_related_tugas BEFORE DELETE ON Pertemuan
FOR EACH ROW
BEGIN
    DELETE FROM Tugas WHERE pertemuan = OLD.kode;
END //
DELIMITER ;


--
DELIMITER //
CREATE TRIGGER IF NOT EXISTS del_related_kehadiran BEFORE DELETE ON Pertemuan
FOR EACH ROW
BEGIN
    DELETE FROM Kehadiran WHERE pertemuan = OLD.kode;
END //
DELIMITER ;


--
DELIMITER //
CREATE TRIGGER IF NOT EXISTS del_related_ujian BEFORE DELETE ON Pertemuan
FOR EACH ROW
BEGIN
    DELETE FROM Ujian WHERE pertemuan = OLD.kode;
END //
DELIMITER ;


--
DELIMITER //
CREATE TRIGGER IF NOT EXISTS del_related_jwb_ujian BEFORE DELETE ON Ujian
FOR EACH ROW
BEGIN
    DELETE FROM Jawaban_Ujian WHERE ujian = OLD.kode;
END //
DELIMITER ;


--
DELIMITER //
CREATE TRIGGER IF NOT EXISTS del_related_nilai_ujian BEFORE DELETE ON Ujian
FOR EACH ROW
BEGIN
    DELETE FROM Nilai_Ujian WHERE ujian = OLD.kode;
END //
DELIMITER ;


--
DELIMITER //
CREATE TRIGGER IF NOT EXISTS del_related_paket_soal BEFORE DELETE ON Ujian
FOR EACH ROW
BEGIN
    DELETE FROM Paket_Soal WHERE ujian = OLD.kode;
END //
DELIMITER ;


--
DELIMITER //
CREATE TRIGGER IF NOT EXISTS del_related_soal BEFORE DELETE ON Paket_Soal
FOR EACH ROW
BEGIN
    DELETE FROM Soal WHERE kode = OLD.soal;
END //
DELIMITER ;


--
DELIMITER //
CREATE TRIGGER IF NOT EXISTS del_related_opsi_pg BEFORE DELETE ON Soal
FOR EACH ROW
BEGIN
    DELETE FROM Opsi_PG WHERE kode = OLD.kode;
END //
DELIMITER ;


DELIMITER //
CREATE TRIGGER IF NOT EXISTS del_related_pertemuan BEFORE DELETE ON Kelas
FOR EACH ROW
BEGIN
    DELETE FROM Pertemuan WHERE kelas = OLD.kode;
END //
DELIMITER ;


DELIMITER //
CREATE TRIGGER IF NOT EXISTS del_related_kontrak_kls BEFORE DELETE ON Kelas
FOR EACH ROW
BEGIN
    DELETE FROM Kontrak_Kelas WHERE kelas = OLD.kode;
END //
DELIMITER ;


--
DELIMITER //
CREATE TRIGGER IF NOT EXISTS del_related_kontrak_mhs BEFORE DELETE ON Mahasiswa
FOR EACH ROW
BEGIN
    DELETE FROM Kontrak_Kelas WHERE mahasiswa = OLD.nim;
END //
DELIMITER ;


--
DELIMITER //
CREATE TRIGGER IF NOT EXISTS del_related_rps BEFORE DELETE ON Mata_Kuliah
FOR EACH ROW
BEGIN
    DELETE FROM RPS WHERE mata_kuliah = OLD.kode;
END //
DELIMITER ;


DELIMITER //
CREATE TRIGGER IF NOT EXISTS del_related_silabus BEFORE DELETE ON Mata_Kuliah
FOR EACH ROW
BEGIN
    DELETE FROM Silabus WHERE mata_kuliah = OLD.kode;
END //
DELIMITER ;


DELIMITER //
CREATE TRIGGER IF NOT EXISTS del_related_kelas BEFORE DELETE ON Mata_Kuliah
FOR EACH ROW
BEGIN
    DELETE FROM Kelas WHERE mata_kuliah = OLD.kode;
END //
DELIMITER ;
