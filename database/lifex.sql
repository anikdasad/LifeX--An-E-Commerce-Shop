-- LifeX database schema + demo seed
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';
START TRANSACTION;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS payment_transactions;
DROP TABLE IF EXISTS product_reviews;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','employee','customer') NOT NULL DEFAULT 'customer',
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE employees (
  employee_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  department VARCHAR(120) DEFAULT NULL,
  CONSTRAINT fk_emp_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
  product_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  description TEXT,
  image_path VARCHAR(255) NOT NULL DEFAULT 'assets/images/products/placeholder.jpg',
  price DECIMAL(12,2) NOT NULL,
  stock_qty INT NOT NULL DEFAULT 0,
  category_id INT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_prod_cat FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE orders (
  order_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  total_amount DECIMAL(12,2) NOT NULL,
  status ENUM('Pending','Processing','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  payment_method VARCHAR(60) NOT NULL,
  payment_status ENUM('paid','unpaid') NOT NULL DEFAULT 'unpaid',
  shipping_method ENUM('standard','express') NOT NULL DEFAULT 'standard',
  shipping_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
  ship_name VARCHAR(120) NOT NULL,
  ship_phone VARCHAR(60) NOT NULL,
  ship_address VARCHAR(255) NOT NULL,
  ship_city VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_items (
  order_item_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_item_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  CONSTRAINT fk_item_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product_reviews (
  review_id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  user_id INT NOT NULL,
  rating TINYINT NOT NULL,
  comment VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_review_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
  CONSTRAINT fk_review_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payment_transactions (
  payment_tx_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NULL,
  gateway VARCHAR(60) NOT NULL,
  status VARCHAR(60) NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_tx_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO categories (name) VALUES
('Phones'),
('Laptops'),
('Headphones'),
('Wearables'),
('Gaming'),
('Accessories');

INSERT INTO products (name, description, price, stock_qty, category_id, image_path, created_at) VALUES
('Nova Phone 01','Cinematic display, fast charging, and a clean UI that feels effortless.',54445.95,55,1,'https://source.unsplash.com/800x800/?android-phone&sig=1','2026-01-13 12:00:00'),
('AirBook 02','Lightweight performance for creators, coders, and everyday work.',57657.07,73,2,'https://source.unsplash.com/800x800/?ultrabook&sig=2','2026-01-12 12:00:00'),
('Pulse Headphones 03','Deep bass, crisp vocals, and low-latency wireless for gaming and calls.',5584.37,12,3,'https://source.unsplash.com/800x800/?headset&sig=3','2026-01-11 12:00:00'),
('Orbit Watch 04','Health tracking, smart notifications, and week-long battery life.',33309.51,9,4,'https://source.unsplash.com/800x800/?smart-band&sig=4','2026-01-10 12:00:00'),
('Titan Controller 05','Precision sticks, adaptive triggers, and a premium grip for long sessions.',5816.43,13,5,'https://source.unsplash.com/800x800/?gaming-keyboard&sig=5','2026-01-09 12:00:00'),
('Essential Accessory 06','Minimal design accessories that match your setup perfectly.',2271.09,59,6,'https://source.unsplash.com/800x800/?mousepad&sig=6','2026-01-08 12:00:00'),
('Nova Phone 07','Cinematic display, fast charging, and a clean UI that feels effortless.',19747.83,20,1,'https://source.unsplash.com/800x800/?mobile-phone&sig=7','2026-01-07 12:00:00'),
('AirBook 08','Lightweight performance for creators, coders, and everyday work.',103520.63,79,2,'https://source.unsplash.com/800x800/?macbook&sig=8','2026-01-06 12:00:00'),
('Pulse Headphones 09','Deep bass, crisp vocals, and low-latency wireless for gaming and calls.',4527.58,55,3,'https://source.unsplash.com/800x800/?noise-cancelling&sig=9','2026-01-05 12:00:00'),
('Orbit Watch 10','Health tracking, smart notifications, and week-long battery life.',5124.98,10,4,'https://source.unsplash.com/800x800/?smartwatch&sig=10','2026-01-04 12:00:00'),
('Titan Controller 11','Precision sticks, adaptive triggers, and a premium grip for long sessions.',7363.29,23,5,'https://source.unsplash.com/800x800/?gaming-mouse&sig=11','2026-01-03 12:00:00'),
('Essential Accessory 12','Minimal design accessories that match your setup perfectly.',4729.12,44,6,'https://source.unsplash.com/800x800/?keyboard&sig=12','2026-01-02 12:00:00'),
('Nova Phone 13','Cinematic display, fast charging, and a clean UI that feels effortless.',35688.10,78,1,'https://source.unsplash.com/800x800/?iphone&sig=13','2026-01-01 12:00:00'),
('AirBook 14','Lightweight performance for creators, coders, and everyday work.',94249.37,75,2,'https://source.unsplash.com/800x800/?gaming-laptop&sig=14','2025-12-31 12:00:00'),
('Pulse Headphones 15','Deep bass, crisp vocals, and low-latency wireless for gaming and calls.',4557.56,84,3,'https://source.unsplash.com/800x800/?headphones&sig=15','2025-12-30 12:00:00'),
('Orbit Watch 16','Health tracking, smart notifications, and week-long battery life.',10248.50,73,4,'https://source.unsplash.com/800x800/?fitness-tracker&sig=16','2025-12-29 12:00:00'),
('Titan Controller 17','Precision sticks, adaptive triggers, and a premium grip for long sessions.',17011.78,64,5,'https://source.unsplash.com/800x800/?game-controller&sig=17','2025-12-28 12:00:00'),
('Essential Accessory 18','Minimal design accessories that match your setup perfectly.',5096.92,51,6,'https://source.unsplash.com/800x800/?camera-lens&sig=18','2025-12-27 12:00:00'),
('Nova Phone 19','Cinematic display, fast charging, and a clean UI that feels effortless.',51291.25,28,1,'https://source.unsplash.com/800x800/?phone-camera&sig=19','2025-12-26 12:00:00'),
('AirBook 20','Lightweight performance for creators, coders, and everyday work.',108988.08,43,2,'https://source.unsplash.com/800x800/?laptop&sig=20','2025-12-25 12:00:00'),
('Pulse Headphones 21','Deep bass, crisp vocals, and low-latency wireless for gaming and calls.',19709.50,48,3,'https://source.unsplash.com/800x800/?earbuds&sig=21','2025-12-24 12:00:00'),
('Orbit Watch 22','Health tracking, smart notifications, and week-long battery life.',27402.45,82,4,'https://source.unsplash.com/800x800/?wearable&sig=22','2025-12-23 12:00:00'),
('Titan Controller 23','Precision sticks, adaptive triggers, and a premium grip for long sessions.',5398.12,58,5,'https://source.unsplash.com/800x800/?console&sig=23','2025-12-22 12:00:00'),
('Essential Accessory 24','Minimal design accessories that match your setup perfectly.',1651.76,24,6,'https://source.unsplash.com/800x800/?powerbank&sig=24','2025-12-21 12:00:00'),
('Nova Phone 25','Cinematic display, fast charging, and a clean UI that feels effortless.',76089.42,90,1,'https://source.unsplash.com/800x800/?smartphone&sig=25','2025-12-20 12:00:00'),
('AirBook 26','Lightweight performance for creators, coders, and everyday work.',65347.76,78,2,'https://source.unsplash.com/800x800/?notebook-computer&sig=26','2025-12-19 12:00:00'),
('Pulse Headphones 27','Deep bass, crisp vocals, and low-latency wireless for gaming and calls.',12780.34,49,3,'https://source.unsplash.com/800x800/?wireless-headphones&sig=27','2025-12-18 12:00:00'),
('Orbit Watch 28','Health tracking, smart notifications, and week-long battery life.',22976.50,107,4,'https://source.unsplash.com/800x800/?watch&sig=28','2025-12-17 12:00:00'),
('Titan Controller 29','Precision sticks, adaptive triggers, and a premium grip for long sessions.',17948.07,16,5,'https://source.unsplash.com/800x800/?gaming-setup&sig=29','2025-12-16 12:00:00'),
('Essential Accessory 30','Minimal design accessories that match your setup perfectly.',2511.47,90,6,'https://source.unsplash.com/800x800/?usb-c&sig=30','2025-12-15 12:00:00'),
('Nova Phone 31','Cinematic display, fast charging, and a clean UI that feels effortless.',20519.06,94,1,'https://source.unsplash.com/800x800/?android-phone&sig=31','2025-12-14 12:00:00'),
('AirBook 32','Lightweight performance for creators, coders, and everyday work.',126161.65,92,2,'https://source.unsplash.com/800x800/?ultrabook&sig=32','2025-12-13 12:00:00'),
('Pulse Headphones 33','Deep bass, crisp vocals, and low-latency wireless for gaming and calls.',17102.28,54,3,'https://source.unsplash.com/800x800/?headset&sig=33','2025-12-12 12:00:00'),
('Orbit Watch 34','Health tracking, smart notifications, and week-long battery life.',32566.67,7,4,'https://source.unsplash.com/800x800/?smart-band&sig=34','2025-12-11 12:00:00'),
('Titan Controller 35','Precision sticks, adaptive triggers, and a premium grip for long sessions.',18128.36,83,5,'https://source.unsplash.com/800x800/?gaming-keyboard&sig=35','2025-12-10 12:00:00'),
('Essential Accessory 36','Minimal design accessories that match your setup perfectly.',1259.49,32,6,'https://source.unsplash.com/800x800/?backpack&sig=36','2025-12-09 12:00:00'),
('Nova Phone 37','Cinematic display, fast charging, and a clean UI that feels effortless.',49674.13,36,1,'https://source.unsplash.com/800x800/?mobile-phone&sig=37','2025-12-08 12:00:00'),
('AirBook 38','Lightweight performance for creators, coders, and everyday work.',149306.39,116,2,'https://source.unsplash.com/800x800/?macbook&sig=38','2025-12-07 12:00:00'),
('Pulse Headphones 39','Deep bass, crisp vocals, and low-latency wireless for gaming and calls.',18769.08,62,3,'https://source.unsplash.com/800x800/?noise-cancelling&sig=39','2025-12-06 12:00:00'),
('Orbit Watch 40','Health tracking, smart notifications, and week-long battery life.',16661.55,118,4,'https://source.unsplash.com/800x800/?smartwatch&sig=40','2025-12-05 12:00:00'),
('Titan Controller 41','Precision sticks, adaptive triggers, and a premium grip for long sessions.',7486.82,115,5,'https://source.unsplash.com/800x800/?gaming-mouse&sig=41','2025-12-04 12:00:00'),
('Essential Accessory 42','Minimal design accessories that match your setup perfectly.',4807.28,58,6,'https://source.unsplash.com/800x800/?charger&sig=42','2025-12-03 12:00:00'),
('Nova Phone 43','Cinematic display, fast charging, and a clean UI that feels effortless.',59024.68,53,1,'https://source.unsplash.com/800x800/?iphone&sig=43','2025-12-02 12:00:00'),
('AirBook 44','Lightweight performance for creators, coders, and everyday work.',105490.15,27,2,'https://source.unsplash.com/800x800/?gaming-laptop&sig=44','2025-12-01 12:00:00'),
('Pulse Headphones 45','Deep bass, crisp vocals, and low-latency wireless for gaming and calls.',7457.23,34,3,'https://source.unsplash.com/800x800/?headphones&sig=45','2025-11-30 12:00:00'),
('Orbit Watch 46','Health tracking, smart notifications, and week-long battery life.',3895.48,80,4,'https://source.unsplash.com/800x800/?fitness-tracker&sig=46','2025-11-29 12:00:00'),
('Titan Controller 47','Precision sticks, adaptive triggers, and a premium grip for long sessions.',8975.26,5,5,'https://source.unsplash.com/800x800/?game-controller&sig=47','2025-11-28 12:00:00'),
('Essential Accessory 48','Minimal design accessories that match your setup perfectly.',1493.42,52,6,'https://source.unsplash.com/800x800/?mousepad&sig=48','2025-11-27 12:00:00'),
('Nova Phone 49','Cinematic display, fast charging, and a clean UI that feels effortless.',53761.95,93,1,'https://source.unsplash.com/800x800/?phone-camera&sig=49','2025-11-26 12:00:00'),
('AirBook 50','Lightweight performance for creators, coders, and everyday work.',59153.46,116,2,'https://source.unsplash.com/800x800/?laptop&sig=50','2025-11-25 12:00:00'),
('Pulse Headphones 51','Deep bass, crisp vocals, and low-latency wireless for gaming and calls.',20826.39,56,3,'https://source.unsplash.com/800x800/?earbuds&sig=51','2025-11-24 12:00:00'),
('Orbit Watch 52','Health tracking, smart notifications, and week-long battery life.',16414.10,86,4,'https://source.unsplash.com/800x800/?wearable&sig=52','2025-11-23 12:00:00'),
('Titan Controller 53','Precision sticks, adaptive triggers, and a premium grip for long sessions.',16121.06,13,5,'https://source.unsplash.com/800x800/?console&sig=53','2025-11-22 12:00:00'),
('Essential Accessory 54','Minimal design accessories that match your setup perfectly.',2010.44,19,6,'https://source.unsplash.com/800x800/?keyboard&sig=54','2025-11-21 12:00:00'),
('Nova Phone 55','Cinematic display, fast charging, and a clean UI that feels effortless.',56571.60,18,1,'https://source.unsplash.com/800x800/?smartphone&sig=55','2025-11-20 12:00:00'),
('AirBook 56','Lightweight performance for creators, coders, and everyday work.',45061.57,73,2,'https://source.unsplash.com/800x800/?notebook-computer&sig=56','2025-11-19 12:00:00'),
('Pulse Headphones 57','Deep bass, crisp vocals, and low-latency wireless for gaming and calls.',5824.95,83,3,'https://source.unsplash.com/800x800/?wireless-headphones&sig=57','2025-11-18 12:00:00'),
('Orbit Watch 58','Health tracking, smart notifications, and week-long battery life.',4335.07,31,4,'https://source.unsplash.com/800x800/?watch&sig=58','2025-11-17 12:00:00'),
('Titan Controller 59','Precision sticks, adaptive triggers, and a premium grip for long sessions.',15328.15,37,5,'https://source.unsplash.com/800x800/?gaming-setup&sig=59','2025-11-16 12:00:00'),
('Essential Accessory 60','Minimal design accessories that match your setup perfectly.',3145.60,65,6,'https://source.unsplash.com/800x800/?camera-lens&sig=60','2025-11-15 12:00:00'),
('Nova Phone 61','Cinematic display, fast charging, and a clean UI that feels effortless.',28101.12,67,1,'https://source.unsplash.com/800x800/?android-phone&sig=61','2025-11-14 12:00:00'),
('AirBook 62','Lightweight performance for creators, coders, and everyday work.',167156.48,44,2,'https://source.unsplash.com/800x800/?ultrabook&sig=62','2025-11-13 12:00:00'),
('Pulse Headphones 63','Deep bass, crisp vocals, and low-latency wireless for gaming and calls.',5314.14,100,3,'https://source.unsplash.com/800x800/?headset&sig=63','2025-11-12 12:00:00'),
('Orbit Watch 64','Health tracking, smart notifications, and week-long battery life.',14727.74,66,4,'https://source.unsplash.com/800x800/?smart-band&sig=64','2025-11-11 12:00:00'),
('Titan Controller 65','Precision sticks, adaptive triggers, and a premium grip for long sessions.',8290.52,31,5,'https://source.unsplash.com/800x800/?gaming-keyboard&sig=65','2025-11-10 12:00:00'),
('Essential Accessory 66','Minimal design accessories that match your setup perfectly.',4627.36,93,6,'https://source.unsplash.com/800x800/?powerbank&sig=66','2025-11-09 12:00:00'),
('Nova Phone 67','Cinematic display, fast charging, and a clean UI that feels effortless.',83194.91,102,1,'https://source.unsplash.com/800x800/?mobile-phone&sig=67','2025-11-08 12:00:00'),
('AirBook 68','Lightweight performance for creators, coders, and everyday work.',123142.98,115,2,'https://source.unsplash.com/800x800/?macbook&sig=68','2025-11-07 12:00:00'),
('Pulse Headphones 69','Deep bass, crisp vocals, and low-latency wireless for gaming and calls.',5482.70,38,3,'https://source.unsplash.com/800x800/?noise-cancelling&sig=69','2025-11-06 12:00:00'),
('Orbit Watch 70','Health tracking, smart notifications, and week-long battery life.',20486.37,26,4,'https://source.unsplash.com/800x800/?smartwatch&sig=70','2025-11-05 12:00:00'),
('Titan Controller 71','Precision sticks, adaptive triggers, and a premium grip for long sessions.',14655.77,73,5,'https://source.unsplash.com/800x800/?gaming-mouse&sig=71','2025-11-04 12:00:00'),
('Essential Accessory 72','Minimal design accessories that match your setup perfectly.',4736.78,47,6,'https://source.unsplash.com/800x800/?usb-c&sig=72','2025-11-03 12:00:00'),
('Nova Phone 73','Cinematic display, fast charging, and a clean UI that feels effortless.',41234.61,105,1,'https://source.unsplash.com/800x800/?iphone&sig=73','2025-11-02 12:00:00'),
('AirBook 74','Lightweight performance for creators, coders, and everyday work.',96156.81,109,2,'https://source.unsplash.com/800x800/?gaming-laptop&sig=74','2025-11-01 12:00:00'),
('Pulse Headphones 75','Deep bass, crisp vocals, and low-latency wireless for gaming and calls.',15629.74,34,3,'https://source.unsplash.com/800x800/?headphones&sig=75','2025-10-31 12:00:00'),
('Orbit Watch 76','Health tracking, smart notifications, and week-long battery life.',10050.52,50,4,'https://source.unsplash.com/800x800/?fitness-tracker&sig=76','2025-10-30 12:00:00'),
('Titan Controller 77','Precision sticks, adaptive triggers, and a premium grip for long sessions.',3949.99,106,5,'https://source.unsplash.com/800x800/?game-controller&sig=77','2025-10-29 12:00:00'),
('Essential Accessory 78','Minimal design accessories that match your setup perfectly.',2588.47,29,6,'https://source.unsplash.com/800x800/?backpack&sig=78','2025-10-28 12:00:00'),
('Nova Phone 79','Cinematic display, fast charging, and a clean UI that feels effortless.',57125.45,97,1,'https://source.unsplash.com/800x800/?phone-camera&sig=79','2025-10-27 12:00:00'),
('AirBook 80','Lightweight performance for creators, coders, and everyday work.',136624.96,51,2,'https://source.unsplash.com/800x800/?laptop&sig=80','2025-10-26 12:00:00');


COMMIT;
