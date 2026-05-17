const jwt = require('jsonwebtoken');

function makeToken(user) {
  return jwt.sign(
    {
      id:        user._id,
      email:     user.email,
      firstName: user.firstName,
      lastName:  user.lastName,
      role:      user.role,
    },
    process.env.JWT_SECRET,
    { expiresIn: '7d' }
  );
}

function verifyToken(token) {
  return jwt.verify(token, process.env.JWT_SECRET);
}

module.exports = { makeToken, verifyToken };
