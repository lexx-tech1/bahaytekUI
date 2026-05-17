const router    = require('express').Router();
const passport  = require('passport');
const jwt       = require('jsonwebtoken');
const User      = require('../models/User');
const verifyToken = require('../middleware/auth');

const FRONTEND_URL = process.env.FRONTEND_URL || 'http://localhost/bahaytekUI';

function makeToken(user) {
  return jwt.sign(
    { id: user._id, email: user.email, role: user.role },
    process.env.JWT_SECRET,
    { expiresIn: '7d' }
  );
}

/* ── Email / Password Register ─────────────────────────────── */
router.post('/register', async (req, res) => {
  const { firstName, lastName, email, password } = req.body;
  if (!firstName || !lastName || !email || !password)
    return res.status(400).json({ message: 'All fields are required' });
  if (password.length < 6)
    return res.status(400).json({ message: 'Password must be at least 6 characters' });
  try {
    if (await User.findOne({ email }))
      return res.status(409).json({ message: 'Email already in use' });
    const user  = await User.create({ firstName, lastName, email, password });
    const token = makeToken(user);
    res.status(201).json({ token, user: { id: user._id, firstName, lastName, email } });
  } catch (err) {
    res.status(500).json({ message: 'Server error', error: err.message });
  }
});

/* ── Email / Password Login ─────────────────────────────────── */
router.post('/login', (req, res, next) => {
  passport.authenticate('local', { session: false }, (err, user, info) => {
    if (err) return next(err);
    if (!user) return res.status(401).json({ message: info?.message || 'Invalid credentials' });
    const token = makeToken(user);
    res.json({ token, user: { id: user._id, firstName: user.firstName, lastName: user.lastName, email: user.email } });
  })(req, res, next);
});

/* ── Google OAuth ───────────────────────────────────────────── */
router.get('/google', passport.authenticate('google', { scope: ['profile', 'email'], session: false }));

router.get('/google/callback',
  passport.authenticate('google', { session: false, failureRedirect: `${FRONTEND_URL}/login.html?error=google` }),
  (req, res) => {
    const token = makeToken(req.user);
    res.redirect(`${FRONTEND_URL}/login.html?token=${token}`);
  }
);

/* ── Facebook OAuth ─────────────────────────────────────────── */
router.get('/facebook', passport.authenticate('facebook', { scope: ['email'], session: false }));

router.get('/facebook/callback',
  passport.authenticate('facebook', { session: false, failureRedirect: `${FRONTEND_URL}/login.html?error=facebook` }),
  (req, res) => {
    const token = makeToken(req.user);
    res.redirect(`${FRONTEND_URL}/login.html?token=${token}`);
  }
);

/* ── Get Current User ───────────────────────────────────────── */
router.get('/me', verifyToken, async (req, res) => {
  try {
    const user = await User.findById(req.user.id).select('-password');
    if (!user) return res.status(404).json({ message: 'User not found' });
    res.json(user);
  } catch (err) {
    res.status(500).json({ message: 'Server error' });
  }
});

/* ── Logout (client-side token removal, but endpoint for clarity) */
router.post('/logout', (_req, res) => {
  res.json({ message: 'Logged out' });
});

module.exports = router;
