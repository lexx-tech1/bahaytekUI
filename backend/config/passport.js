const passport = require('passport');
const LocalStrategy   = require('passport-local').Strategy;
const GoogleStrategy  = require('passport-google-oauth20').Strategy;
const FacebookStrategy = require('passport-facebook').Strategy;
const User = require('../models/User');

passport.use(new LocalStrategy({ usernameField: 'email' }, async (email, password, done) => {
  try {
    const user = await User.findOne({ email });
    if (!user || !user.password) return done(null, false, { message: 'Invalid credentials' });
    const ok = await user.comparePassword(password);
    if (!ok) return done(null, false, { message: 'Invalid credentials' });
    return done(null, user);
  } catch (err) {
    return done(err);
  }
}));

passport.use(new GoogleStrategy({
  clientID:     process.env.GOOGLE_CLIENT_ID,
  clientSecret: process.env.GOOGLE_CLIENT_SECRET,
  callbackURL:  process.env.GOOGLE_CALLBACK_URL,
}, async (_accessToken, _refreshToken, profile, done) => {
  try {
    let user = await User.findOne({ googleId: profile.id });
    if (!user) {
      user = await User.findOne({ email: profile.emails[0].value });
      if (user) {
        user.googleId = profile.id;
        if (!user.avatar && profile.photos?.[0]?.value) user.avatar = profile.photos[0].value;
        await user.save();
      } else {
        user = await User.create({
          googleId:  profile.id,
          firstName: profile.name.givenName || profile.displayName,
          lastName:  profile.name.familyName || '',
          email:     profile.emails[0].value,
          avatar:    profile.photos?.[0]?.value || null,
        });
      }
    }
    return done(null, user);
  } catch (err) {
    return done(err);
  }
}));

passport.use(new FacebookStrategy({
  clientID:     process.env.FACEBOOK_APP_ID,
  clientSecret: process.env.FACEBOOK_APP_SECRET,
  callbackURL:  process.env.FACEBOOK_CALLBACK_URL,
  profileFields: ['id', 'emails', 'name', 'picture.type(large)'],
}, async (_accessToken, _refreshToken, profile, done) => {
  try {
    let user = await User.findOne({ facebookId: profile.id });
    if (!user) {
      const email = profile.emails?.[0]?.value;
      user = email ? await User.findOne({ email }) : null;
      if (user) {
        user.facebookId = profile.id;
        if (!user.avatar && profile.photos?.[0]?.value) user.avatar = profile.photos[0].value;
        await user.save();
      } else {
        user = await User.create({
          facebookId: profile.id,
          firstName:  profile.name?.givenName || profile.displayName,
          lastName:   profile.name?.familyName || '',
          email:      email || `fb_${profile.id}@bahaytek.noemail`,
          avatar:     profile.photos?.[0]?.value || null,
        });
      }
    }
    return done(null, user);
  } catch (err) {
    return done(err);
  }
}));

module.exports = passport;
