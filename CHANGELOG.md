# Changelog

## v0.0.2 (Unreleased)

New configuration:

- $wgAchievementBadgesAchievementFallbackIcon - This is the path to the fallback icons that displays in Special:Achievements.
- $wgAchievementBadgesReplaceEchoThankYouEdit - When this is set to true, the edit milestone notifications offered by Echo will be not sent.

Enhancements:

- Add new achievements: sign-up, enable-achievement-badges, edit-page, create-page, long-user-page.
- Changes in Special:Achievements:
  - Add an animation on Special:Achievements.
  - Add body text to notifications.
- Log achievements to Special:Log and show on Special:Achievements.
- Add a icon to BetaFeatures preference and Echo notifications.
- Link to the achievement fragment from notification.

Bug Fixes:

- Do not allow system users to achieve achievements.
- Suppress Echo's welcome notification when it is replaced with the sign-up achievement.
- Fix duplicated items on Special:Achievements
- Do not allow user who disables AB to earn achievement

### Developer changes

- Hook calling uses new HookContainer.
- Add SpecialAchievementsBeforeGetEarned Hook.

Achievement registering changes:

- New properties:
  - `type` - There is now two type: `'instant'` and `'stats'`. Read [extension.json] for details.
  - `priority` - This is used to ordering on Special:Achievements. (default: 1000)

### Other changes:

- Introduce Quibble tests.
- Store loggers in classes as a member variable.
- Use LocalUserCreatedHook instead of AddNewAccountHook
- Use a configuration variable and the ExtensionFunction to register achievements.

## v0.0.1

- Run prettier on Markdown.
- Provide basic process for registering and firing a achievement.
- Make the extension could be provided as a beta feature.
- Add a bare special page Special:Achievements.
- Add a Github actions workflow to lint PHP, JSON and LESS.

---

[extension.json] extension.json
