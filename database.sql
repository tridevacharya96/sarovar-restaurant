-- database.sql
CREATE DATABASE sarovar_restaurant;
USE sarovar_restaurant;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE menu_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50)
);

CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    is_veg TINYINT(1) DEFAULT 1,
    is_available TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES menu_categories(id)
);

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    guests INT NOT NULL,
    special_requests TEXT,
    status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    address TEXT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','preparing','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
    payment_method ENUM('cod','online') DEFAULT 'cod',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    menu_item_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);

CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150),
    image VARCHAR(255) NOT NULL,
    category VARCHAR(50)
);

-- Insert Categories
INSERT INTO menu_categories (name, description, icon) VALUES
('Starters', 'Delicious appetizers to begin your meal', 'fa-utensils'),
('Main Course', 'Hearty and flavorful main dishes', 'fa-bowl-food'),
('Breads', 'Fresh baked breads and rotis', 'fa-bread-slice'),
('Rice & Biryani', 'Aromatic rice dishes', 'fa-bowl-rice'),
('Desserts', 'Sweet endings to your meal', 'fa-ice-cream'),
('Beverages', 'Refreshing drinks and juices', 'fa-glass-water');

-- Insert Menu Items
INSERT INTO menu_items (category_id, name, description, price, is_veg, is_featured) VALUES
(1, 'Paneer Tikka', 'Marinated cottage cheese grilled to perfection', 220.00, 1, 1),
(1, 'Veg Spring Rolls', 'Crispy rolls filled with fresh vegetables', 160.00, 1, 0),
(1, 'Chicken Tikka', 'Tender chicken marinated in spices and grilled', 280.00, 0, 1),
(1, 'Fish Fry', 'Fresh fish marinated and deep fried', 320.00, 0, 0),
(1, 'Mushroom Tikka', 'Grilled mushrooms with Indian spices', 200.00, 1, 0),
(2, 'Dal Makhani', 'Slow cooked black lentils in rich tomato gravy', 180.00, 1, 1),
(2, 'Paneer Butter Masala', 'Cottage cheese in creamy tomato sauce', 240.00, 1, 1),
(2, 'Chicken Curry', 'Traditional chicken curry with aromatic spices', 300.00, 0, 1),
(2, 'Mutton Rogan Josh', 'Slow cooked mutton in Kashmiri spices', 380.00, 0, 0),
(2, 'Palak Paneer', 'Cottage cheese in spinach gravy', 220.00, 1, 0),
(2, 'Butter Chicken', 'Tender chicken in rich buttery tomato gravy', 320.00, 0, 1),
(2, 'Veg Kadai', 'Mixed vegetables in spicy kadai masala', 190.00, 1, 0),
(3, 'Butter Naan', 'Soft leavened bread with butter', 40.00, 1, 0),
(3, 'Garlic Naan', 'Naan topped with garlic and herbs', 50.00, 1, 0),
(3, 'Tandoori Roti', 'Whole wheat bread from tandoor', 30.00, 1, 0),
(3, 'Paratha', 'Layered whole wheat flatbread', 45.00, 1, 0),
(4, 'Veg Biryani', 'Fragrant basmati rice with vegetables', 220.00, 1, 1),
(4, 'Chicken Biryani', 'Aromatic rice with tender chicken', 300.00, 0, 1),
(4, 'Mutton Biryani', 'Slow cooked mutton with basmati rice', 380.00, 0, 0),
(4, 'Jeera Rice', 'Basmati rice tempered with cumin', 120.00, 1, 0),
(5, 'Gulab Jamun', 'Soft milk dumplings in sugar syrup', 80.00, 1, 1),
(5, 'Rasgulla', 'Soft cottage cheese balls in sugar syrup', 80.00, 1, 0),
(5, 'Kheer', 'Creamy rice pudding with dry fruits', 100.00, 1, 0),
(5, 'Ice Cream', 'Assorted flavors of ice cream', 120.00, 1, 0),
(6, 'Mango Lassi', 'Refreshing mango yogurt drink', 80.00, 1, 1),
(6, 'Masala Chai', 'Spiced Indian tea', 40.00, 1, 0),
(6, 'Fresh Lime Soda', 'Refreshing lime soda', 60.00, 1, 0),
(6, 'Cold Coffee', 'Chilled coffee with ice cream', 100.00, 1, 0);

-- Insert Gallery
INSERT INTO gallery (title, image, category) VALUES
('Restaurant Interior', 'gallery1.jpg', 'interior'),
('Dining Area', 'gallery2.jpg', 'interior'),
('Special Thali', 'gallery3.jpg', 'food'),
('Biryani', 'gallery4.jpg', 'food'),
('Desserts', 'gallery5.jpg', 'food'),
('Private Dining', 'gallery6.jpg', 'interior');