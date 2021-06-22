'use strict';

const assert = require('assert'),
  AchievementsPage = require('../pageobjects/achievements.page'),
  UserLoginPage = require('wdio-mediawiki/LoginPage');

describe('Special:Achievements', function () {
  it('shows a logged-in user description of sign-up', function () {
    UserLoginPage.login(browser.config.mwUser, browser.config.mwPwd);
    AchievementsPage.open();

    assert(AchievementsPage.signUpDescription.isExisting());
  });
});
