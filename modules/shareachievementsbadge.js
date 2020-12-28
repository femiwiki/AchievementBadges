(function () {
  'use strict';

  // https://developers.facebook.com/docs/plugins/share-button/
  // prettier-ignore
  (function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = "https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v3.0";
    fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));

  // Initialize Facebook SDK
  var facebookAppId = mw.config.get('wgAchievementBadgesFacebookAppId');
  window.fbAsyncInit = function () {
    FB.init({
      appId: facebookAppId,
      status: true,
      xfbml: true,
      version: 'v2.7',
    });
  };

  var button = window.document.getElementById('share-achievement-facebook');
  button.addEventListener('click', function (e) {
    FB.ui(
      {
        method: 'share',
        href: button.href,
      },
      function (response) {}
    );
    e.preventDefault();
  });
})();
