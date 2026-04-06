const express = require('express');
const cors = require('cors');
const mysql = require('mysql2/promise');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 5000;

app.use(cors());
app.use(express.json());

// Database connection pool
const pool = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'web_thue_xe',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});

// Test DB Connection
app.get('/api/db-check', async (req, res) => {
    try {
        const [rows] = await pool.query('SELECT 1 + 1 AS solution');
        res.json({ success: true, message: 'Database connection successful', data: rows });
    } catch (error) {
        console.error('DB Connection error:', error);
        res.status(500).json({ success: false, message: 'Database connection failed', error: error.message });
    }
});

// Mock Cars data - fallback if DB is not setup
const mockCars = [
    { id: 1, name: 'Toyota Camry', price: 800000, type: 'Sedan', status: 'available', image: 'https://images.pexels.com/photos/112460/pexels-photo-112460.jpeg?auto=compress&cs=tinysrgb&w=800' },
    { id: 2, name: 'Honda CR-V', price: 1200000, type: 'SUV', status: 'rented', image: 'https://images.pexels.com/photos/116675/pexels-photo-116675.jpeg?auto=compress&cs=tinysrgb&w=800' },
    { id: 3, name: 'Mazda 3', price: 700000, type: 'Sedan', status: 'available', image: 'https://images.pexels.com/photos/120049/pexels-photo-120049.jpeg?auto=compress&cs=tinysrgb&w=800' },
    { id: 4, name: 'Ford Everest', price: 1500000, type: 'SUV', status: 'rented', image: 'https://images.pexels.com/photos/210019/pexels-photo-210019.jpeg?auto=compress&cs=tinysrgb&w=800' }
];

// Get all cars
app.get('/api/cars', async (req, res) => {
    try {
        const [rows] = await pool.query('SELECT * FROM cars');
        res.json(rows.length > 0 ? rows : mockCars);
    } catch (error) {
        console.log("Returning mock data due to DB error:", error.message);
        res.json(mockCars); // fallback to mock data
    }
});

app.get('/', (req, res) => {
    res.send('Car Rental API is running');
});

app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});
