const dbConnect    = require('../../_lib/db');
const User         = require('../../_lib/User');
const { makeToken } = require('../../_lib/token');

const FRONTEND = process.env.FRONTEND_URL;

module.exports = async (req, res) => {
  const { code, error } = req.query;
  if (error || !code) return res.redirect(`${FRONTEND}/login.html?error=google`);

  try {
    /* Exchange code for tokens */
    const tokenRes = await fetch('https://oauth2.googleapis.com/token', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        code,
        client_id:     process.env.GOOGLE_CLIENT_ID,
        client_secret: process.env.GOOGLE_CLIENT_SECRET,
        redirect_uri:  process.env.GOOGLE_CALLBACK_URL,
        grant_type:    'authorization_code',
      }),
    });
    const tokenData = await tokenRes.json();
    if (!tokenData.access_token) throw new Error('No access token from Google');

    /* Get user profile */
    const profileRes = await fetch('https://www.googleapis.com/oauth2/v3/userinfo', {
      headers: { Authorization: `Bearer ${tokenData.access_token}` },
    });
    const profile = await profileRes.json();

    await dbConnect();
    let user = await User.findOne({ googleId: profile.sub });
    if (!user) {
      user = await User.findOne({ email: profile.email });
      if (user) {
        user.googleId = profile.sub;
        if (!user.avatar && profile.picture) user.avatar = profile.picture;
        await user.save();
      } else {
        user = await User.create({
          googleId:  profile.sub,
          firstName: profile.given_name  || profile.name || 'User',
          lastName:  profile.family_name || '',
          email:     profile.email,
          avatar:    profile.picture || null,
        });
      }
    }

    const token = makeToken(user);
    res.redirect(`${FRONTEND}/login.html?token=${token}`);
  } catch (err) {
    console.error('Google callback error:', err.message);
    res.redirect(`${FRONTEND}/login.html?error=google`);
  }
};
