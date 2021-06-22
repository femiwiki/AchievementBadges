'use strict';

const Page = require('wdio-mediawiki/Page');

class AchievementsPage extends Page {
  get signUpDescription() {
    return $('#achievement-sign-up .achievement-description');
  }
  open() {
    super.openTitle('Special:Achievements');
  }
}

module.exports = new AchievementsPage();
