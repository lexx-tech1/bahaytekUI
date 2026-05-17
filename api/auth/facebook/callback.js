const dbConnect    = require('../../_lib/db');
const User         = require('../../_lib/User');
const { makeToken } = require('../../_lib/token');

const FRONTEND = process.env.FRONTEND_URL;

module.exports = async (req, res) => {
  const { code, error } = req.query;
  if (error || !code) return res.redirect(`${FRONTEND}/login.html?error=facebook`);

  try {
    /* Exchange code for access token */
    const tokenParams = new URLSearchParams({
      client_id:     process.env.FACEBOOK_APP_ID,
      client_secret: process.env.FACEBOOK_APP_SECRET,
      redirect_uri:  process.env.FACEBOOK_CALLBACK_URL,
      code,
    });
    const tokenRes  = await fetch(`https://graph.facebook.com/v20.0/oauth/access_token?${tokenParams}`);
    const tokenData = await tokenRes.json();
    if (!tokenData.access_token) throw new Error('No access token from Facebook');

    /* Get user profile */
    const profileRes = await fetch(
      `https://graph.facebook.com/me?fields=id,first_name,last_name,email,picture.type(large)&access_token=${tokenData.access_token}`
    );
    const profile = await profileRes.json();

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
    res.redirect(`${FRONTEND}/login.html?token=${token}`);
  } catch (err) {
    console.error('Facebook callback error:', err.message);
    res.redirect(`${FRONTEND}/login.html?error=facebook`);
  }
};
