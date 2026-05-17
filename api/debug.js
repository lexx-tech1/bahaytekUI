module.exports = (req, res) => {
  res.json({
    FRONTEND_URL:          process.env.FRONTEND_URL          || 'NOT SET',
    GOOGLE_CLIENT_ID:      process.env.GOOGLE_CLIENT_ID      ? 'SET' : 'NOT SET',
    GOOGLE_CALLBACK_URL:   process.env.GOOGLE_CALLBACK_URL   || 'NOT SET',
    FACEBOOK_APP_ID:       process.env.FACEBOOK_APP_ID       ? 'SET' : 'NOT SET',
    FACEBOOK_CALLBACK_URL: process.env.FACEBOOK_CALLBACK_URL || 'NOT SET',
    MONGODB_URI:           process.env.MONGODB_URI           ? 'SET' : 'NOT SET',
    JWT_SECRET:            process.env.JWT_SECRET            ? 'SET' : 'NOT SET',
  });
};
