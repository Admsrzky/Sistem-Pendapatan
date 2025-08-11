-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for rekam_jasa
CREATE DATABASE IF NOT EXISTS `rekam_jasa` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `rekam_jasa`;

-- Dumping structure for table rekam_jasa.jasa
CREATE TABLE IF NOT EXISTS `jasa` (
  `idjasa` int NOT NULL AUTO_INCREMENT,
  `jasa_nama` varchar(128) DEFAULT NULL,
  `jasa_harga` int DEFAULT NULL,
  PRIMARY KEY (`idjasa`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb3;

-- Dumping data for table rekam_jasa.jasa: ~5 rows (approximately)
INSERT INTO `jasa` (`idjasa`, `jasa_nama`, `jasa_harga`) VALUES
	(10, 'Jasa Jilid Softcover', 4000),
	(11, 'Fotocopy ', 1000),
	(12, 'Print Berwarna', 2000),
	(13, 'Print Tidak Berwarna', 1000),
	(14, 'Jilid Hardcover', 40000);

-- Dumping structure for table rekam_jasa.pengguna
CREATE TABLE IF NOT EXISTS `pengguna` (
  `idpengguna` int NOT NULL AUTO_INCREMENT,
  `pengguna_nama` varchar(64) DEFAULT NULL,
  `pengguna_username` varchar(32) DEFAULT NULL,
  `pengguna_password` varchar(128) DEFAULT NULL,
  `pengguna_level` enum('Admin','User') DEFAULT NULL,
  PRIMARY KEY (`idpengguna`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;

-- Dumping data for table rekam_jasa.pengguna: ~1 rows (approximately)
INSERT INTO `pengguna` (`idpengguna`, `pengguna_nama`, `pengguna_username`, `pengguna_password`, `pengguna_level`) VALUES
	(1, 'Administrator', 'admin', '$2y$10$9kgZVoKm/uCj9faKX5H2q.tgawINM9ARl8D8r1TNe1MoG7HH6aDc2', 'Admin'),
	(6, 'Fajar Aristama', 'fajar', '$2y$10$mXvKI8KWXaWEjUScGnLdO.RVlCmcaUxsidtntyytT4QwxoKcAltMK', 'User');

-- Dumping structure for table rekam_jasa.transaksi
CREATE TABLE IF NOT EXISTS `transaksi` (
  `idtransaksi` int NOT NULL AUTO_INCREMENT,
  `transaksi_no` varchar(20) DEFAULT NULL,
  `transaksi_tgl` date DEFAULT NULL,
  `transaksi_nama` varchar(128) DEFAULT NULL,
  `transaksi_total_harga` decimal(15,2) DEFAULT NULL,
  `pengguna_id` int NOT NULL,
  PRIMARY KEY (`idtransaksi`,`pengguna_id`),
  KEY `fk_transaksi_pengguna1_idx` (`pengguna_id`),
  CONSTRAINT `fk_transaksi_pengguna1` FOREIGN KEY (`pengguna_id`) REFERENCES `pengguna` (`idpengguna`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3;

-- Dumping data for table rekam_jasa.transaksi: ~1 rows (approximately)
INSERT INTO `transaksi` (`idtransaksi`, `transaksi_no`, `transaksi_tgl`, `transaksi_nama`, `transaksi_total_harga`, `pengguna_id`) VALUES
	(10, 'TRX00001', '2025-08-08', 'Fajar Aristamaa', 41000.00, 1);

-- Dumping structure for table rekam_jasa.transaksi_items
CREATE TABLE IF NOT EXISTS `transaksi_items` (
  `id_transaksi_item` int NOT NULL AUTO_INCREMENT,
  `transaksi_id` int NOT NULL,
  `jasa_id` int NOT NULL,
  `jumlah` int NOT NULL,
  `harga` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id_transaksi_item`),
  KEY `fk_transaksi_items_to_transaksi` (`transaksi_id`),
  KEY `fk_transaksi_items_to_jasa` (`jasa_id`),
  CONSTRAINT `fk_transaksi_items_to_jasa` FOREIGN KEY (`jasa_id`) REFERENCES `jasa` (`idjasa`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_transaksi_items_to_transaksi` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`idtransaksi`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table rekam_jasa.transaksi_items: ~2 rows (approximately)
INSERT INTO `transaksi_items` (`id_transaksi_item`, `transaksi_id`, `jasa_id`, `jumlah`, `harga`, `subtotal`) VALUES
	(3, 10, 11, 1, 1000.00, 1000.00),
	(4, 10, 14, 1, 40000.00, 40000.00);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
