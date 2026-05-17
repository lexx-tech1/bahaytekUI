module.exports = (req, res) => {
  const params = new URLSearchParams({
    client_id:    process.env.FACEBOOK_APP_ID,
    redirect_uri: process.env.FACEBOOK_CALLBACK_URL,
    scope:        'email,public_profile',
    response_type:'code',
  });
  res.redirect(`https://www.facebook.com/v20.0/dialog/oauth?${params}`);
};
