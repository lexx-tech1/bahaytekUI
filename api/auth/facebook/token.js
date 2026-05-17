const dbConnect       = require('../../_lib/db');
const User            = require('../../_lib/User');
const { makeToken }   = require('../../_lib/token');
const verifyRecaptcha = require('../../_lib/recaptcha');

module.exports = async (req, res) => {
  if (req.method !== 'POST') return res.status(405).json({ message: 'Method not allowed' });

  const { accessToken, captchaToken } = req.body;
  if (!accessToken) return res.status(400).json({ message: 'Access token required' });

  const captchaOk = await verifyRecaptcha(captchaToken);
  if (!captchaOk) return res.status(400).json({ message: 'Please complete the reCAPTCHA' });

  try {
    const profileRes = await fetch(
      `https://graph.facebook.com/me?fields=id,first_name,last_name,email,picture.type(large)&access_token=${accessToken}`
    );
    const profile = await profileRes.json();
    if (profile.error) return res.status(401).json({ message: 'Invalid Facebook token' });

    await dbConnect();
    let user = await User.findOne({ facebookId: profile.id });
    if (!user) {
      const email = profile.email;
      user = email ? await User.findOne({ email }) : null;
      if (user) {
        user.facebookId = profile.id;
        if (!user.avatar && profile.picture?.data?.url) user.avatar = profile.picture.data.url;
        await user.save();
      } else {
        user = await User.create({
          facebookId: profile.id,
          firstName:  profile.first_name || 'User',
          lastName:   profile.last_name  || '',
          email:      email || `fb_${profile.id}@bahaytek.noemail`,
          avatar:     profile.picture?.data?.url || null,
        });
      }
    }

    const token = makeToken(user);
    res.json({
      token,
      user: { id: user._id, firstName: user.firstName, lastName: user.lastName, email: user.email },
    });
  } catch (err) {
    res.status(500).json({ message: 'Server error', error: err.message });
  }
};
