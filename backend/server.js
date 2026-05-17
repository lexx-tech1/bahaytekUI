require('dotenv').config();
const express  = require('express');
const mongoose = require('mongoose');
const cors     = require('cors');
const passport = require('./config/passport');
const authRoutes = require('./routes/auth');

const app  = express();
const PORT = process.env.PORT || 3000;

/* ── CORS ── allow requests from XAMPP frontend */
app.use(cors({
  origin: [
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:80',
    process.env.FRONTEND_URL,
  ].filter(Boolean),
  credentials: true,
}));

app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(passport.initialize());

/* ── Routes ── */
app.use('/api/auth', authRoutes);

app.get('/', (_req, res) => res.json({ status: 'BahayTek API running' }));

/* ── Connect MongoDB & Start ── */
mongoose.connect(process.env.MONGODB_URI)
  .then(() => {
    console.log('MongoDB connected');
    app.listen(PORT, () => console.log(`Server running on http://localhost:${PORT}`));
  })
  .catch(err => {
    console.error('MongoDB connection failed:', err.message);
    process.exit(1);
  });
