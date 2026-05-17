const dbConnect        = require('../_lib/db');
const User             = require('../_lib/User');
const { makeToken }    = require('../_lib/token');
const verifyRecaptcha  = require('../_lib/recaptcha');

module.exports = async (req, res) => {
  if (req.method !== 'POST') return res.status(405).json({ message: 'Method not allowed' });

  const { firstName, lastName, email, password, captchaToken } = req.body;

  if (!firstName || !lastName || !email || !password)
    return res.status(400).json({ message: 'All fields are required' });
  if (password.length < 6)
    return res.status(400).json({ message: 'Password must be at least 6 characters' });

  const captchaOk = await verifyRecaptcha(captchaToken);
  if (!captchaOk)
    return res.status(400).json({ message: 'Please complete the reCAPTCHA' });

  try {
    await dbConnect();
    if (await User.findOne({ email }))
      return res.status(409).json({ message: 'Email already in use' });

    const user  = await User.create({ firstName, lastName, email, password });
    const token = makeToken(user);
    res.status(201).json({
      token,
      user: { id: user._id, firstName: user.firstName, lastName: user.lastName, email: user.email },
    });
  } catch (err) {
    res.status(500).json({ message: 'Server error', error: err.message });
  }
};
