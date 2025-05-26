CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('presenter', 'attendee') DEFAULT 'attendee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE streams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    presenter_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'inactive',
    channel_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (presenter_id) REFERENCES users(id)
);

CREATE TABLE hand_raises (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stream_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stream_id) REFERENCES streams(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
