-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 28, 2025 at 06:17 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `motor`
--

-- --------------------------------------------------------

--
-- Table structure for table `motorcycles`
--

CREATE TABLE `motorcycles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `model` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `motorcycles`
--

INSERT INTO `motorcycles` (`id`, `user_id`, `model`, `description`, `image_path`, `video_path`, `created_at`) VALUES
(1, 1, 'Sport Bikes', 'fsdfsdfsdf', 'uploads/images/img_68372032945697.33004885.png', NULL, '2025-05-28 22:39:46'),
(2, 1, 'adasdas', 'asdsa', 'uploads/images/img_683723a15f4ab7.98747170.jpg', 'uploads/videos/vid_683723a15ff3f4.51533998.mp4', '2025-05-28 22:54:25'),
(3, 1, '', '', 'uploads/images/img_683726cc491c02.65616353.jpeg', NULL, '2025-05-28 23:07:56'),
(4, 1, 'Cruisers', 'Relaxed riding posture and strong torque\r\nExamples: Harley-Davidson Iron 883, Honda Rebel 1100', 'uploads/images/img_6837358b1391b1.47425327.jfif', NULL, '2025-05-29 00:10:51');

-- --------------------------------------------------------

--
-- Table structure for table `motorcycle_ratings`
--

CREATE TABLE `motorcycle_ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `motorcycle_id` int(11) NOT NULL,
  `rating` enum('like','dislike') NOT NULL,
  `rated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `motorcycle_ratings`
--

INSERT INTO `motorcycle_ratings` (`id`, `user_id`, `motorcycle_id`, `rating`, `rated_at`) VALUES
(1, 1, 1, 'like', '2025-05-28 22:53:43'),
(2, 1, 2, 'dislike', '2025-05-28 23:14:17'),
(3, 1, 3, 'dislike', '2025-05-28 23:50:39'),
(4, 1, 4, 'like', '2025-05-29 00:11:02'),
(5, 2, 2, 'like', '2025-05-29 00:15:47');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `warnings` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`, `failed_attempts`, `warnings`) VALUES
(1, 'cheepcode', '20221240@nbsc.edu.ph', '$2y$10$RrOsKeuS77TYRcWUrpxNfeq.wZXCthXYyMmS8FsTYImDPQIWoUVPS', '2025-05-28 13:39:24', 0, 'WARNING: A suspicious login attempt was detected for your account (20221240@nbsc.edu.ph). Please be cautious and change your password if this wasn\'t you.\nWARNING: A suspicious login attempt was detected for your account (20221240@nbsc.edu.ph). Please be cautious and change your password if this wasn\'t you.\n'),
(2, 'bogy', '20221937@nbsc.edu.ph', '$2y$10$CguTRhGpzTYWaKhxIkMXB.oSWA3U4Vvoi.DzmwtJZ2qodApj4GhOC', '2025-05-28 16:14:28', 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `motorcycles`
--
ALTER TABLE `motorcycles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `motorcycle_ratings`
--
ALTER TABLE `motorcycle_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rating` (`user_id`,`motorcycle_id`),
  ADD KEY `motorcycle_id` (`motorcycle_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `motorcycles`
--
ALTER TABLE `motorcycles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `motorcycle_ratings`
--
ALTER TABLE `motorcycle_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `motorcycles`
--
ALTER TABLE `motorcycles`
  ADD CONSTRAINT `motorcycles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `motorcycle_ratings`
--
ALTER TABLE `motorcycle_ratings`
  ADD CONSTRAINT `motorcycle_ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `motorcycle_ratings_ibfk_2` FOREIGN KEY (`motorcycle_id`) REFERENCES `motorcycles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
