# Changelog

Versions and bullets are arranged chronologically from latest to oldest.

## v0.0.2 (Unreleased)

ENHANCEMENTS:

- Add SpecialAchievementsBeforeGetEarned Hook.
- Add 'priority' property to achievement.
- Use $wg* and the ExtensionFunction to register achievements.
- Add body text to notifications.
- Add new achievements sign-up and enable-achievement-badges.
- Link to the achievement fragment from notification.
- Log achievements to Special:Log and show on Special:Achievements.

BUG FIXES:

- Fix duplicated items on Special:Achievements
- Do not allow user who disables AB to earn achievement

## v0.0.1

- Run prettier on Markdown.
- Provide basic process for registering and firing a achievement.
- Make the extension could be provided as a beta feature.
- Add a bare special page `[[Special:Achievements]]`.
- Add a Github actions workflow to lint PHP, JSON and LESS.
